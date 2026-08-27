<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\EventListener;

use Brotkrueml\Schema\Configuration\Configuration;
use Brotkrueml\Schema\Core\Model\TypeInterface;
use Brotkrueml\Schema\Event\RenderAdditionalTypesEvent;
use Brotkrueml\Schema\Manager\SchemaManager;
use Brotkrueml\Schema\Type\TypeFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use WebVision\WvT3unity\Event\ManipulateHeadDataEvent;
use WebVision\WvT3unity\UserFunc\ContentJson;

/**
 * Enriches the SeoData array from {@see ContentJson} for the head data
 * with the TypoScript configurable rich snippets in JSON-LD format.
 *
 * {@see ManipulateHeadDataEvent}
 */
final class SchemaOrgEventListener
{
    public function __construct(
        private readonly SchemaManager $schemaManager,
        private readonly TypeFactory $typeFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Configuration $configuration,
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    /**
     * @throws ContentRenderingException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __invoke(ManipulateHeadDataEvent $event): void
    {
        $sitePageArgument = $event->request->getAttribute('routing');
        // EventListener seems to be fired too early, abort
        if (!$sitePageArgument instanceof PageArguments) {
            return;
        }
        // Routing Object type matches, but detected pageType is not for Unity Head
        if ($sitePageArgument->getPageType() !== '3210') {
            return;
        }
        /** @var ContentObjectRenderer|null $cObj */
        $cObj = $event->request->getAttribute('currentContentObject');
        if (!$cObj instanceof ContentObjectRenderer) {
            return;
        }
        if (($event->configuration['schema.'] ?? []) !== []) {
            $coaContentObject = $cObj->getContentObject('COA');
            if ($coaContentObject === null) {
                return;
            }
            $cObj->render($coaContentObject, $event->configuration['schema.']);
        }

        // Honour EXT:schema's global switch for automatically emitting the WebPage type.
        if ($this->configuration->automaticWebPageSchemaGeneration) {
            $webPageType = $cObj->data['tx_schema_webpagetype'] ?? '';
            if ($webPageType === '') {
                $webPageType = 'WebPage';
            }
            $generatedType = $this->typeFactory->create($webPageType);
            $generatedType->setId($this->buildWebPageId($cObj, $event));
            $generatedType->setProperties([
                'dateModified' => (new \DateTimeImmutable())->setTimestamp((int)$cObj->data['tstamp'])->format(\DateTimeImmutable::ATOM),
                'datePublished' => (new \DateTimeImmutable())->setTimestamp((int)$cObj->data['crdate'])->format(\DateTimeImmutable::ATOM),
                'name' => $cObj->data['title'] ?? '',
            ]);
            $this->schemaManager->addType($generatedType);
        }

        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $sitePageArgument->getPageId())->get();

        if ($this->configuration->automaticBreadcrumbSchemaGeneration && !$this->isBreadcrumbExcludedByBackendLayout($rootLine)) {
            $this->schemaManager->addType(
                $this->buildBreadCrumbList($cObj, $event, $rootLine)
            );
        }

        $this->dispatchRenderAdditionalTypesEvent($event, $rootLine);
        // as the SchemaManager and no other caller inside EXT:schema have a plain return of the JSON, we need to take
        // the output here and strip the pre-rendered <script> tags. Otherwise, the returned value is not JSON-decodable
        $json = strip_tags($this->schemaManager->renderJsonLd());
        $event->headData['jsonLd'] = $json;
    }

    /**
     * Resolve the WebPage @id.
     *
     * The link is built through the TypoScript-configured typolink of the WebPage schema
     * (UnityHead.10.schema.10.id.typolink), only pinning the current page as the link target so the
     * @id stays per-page. Routing it through that configuration keeps the URL overridable from the
     * outside: a site package can attach a typolink `userFunc` there to rewrite the CMS URL into a
     * decoupled storefront (e.g. Magento) URL, without this extension knowing about that target.
     */
    private function buildWebPageId(ContentObjectRenderer $cObj, ManipulateHeadDataEvent $event, ?int $pageUid = null): string
    {
        $typolinkConfiguration = $event->configuration['schema.']['10.']['id.']['typolink.'] ?? [];
        $typolinkConfiguration['parameter'] = 't3://page?uid=' . ($pageUid ?? (int)($cObj->data['uid'] ?? 0));
        $typolinkConfiguration['forceAbsoluteUrl'] ??= '1';

        return $cObj->typoLink_URL($typolinkConfiguration);
    }

    /**
     * Builds a custom BreadcrumbList schema.
     *
     * The breadcrumb structure follows EXT:schema's default implementation.
     * The difference is that the generated page URLs are built using the
     * UnityHead.10.schema.10.id.typolink TypoScript configuration, allowing
     * the base URL to be overridden.
     *
     * @param array<int, mixed> $rootLine
     */
    private function buildBreadCrumbList(
        ContentObjectRenderer $cObj,
        ManipulateHeadDataEvent $event,
        array $rootLine
    ): TypeInterface {
        $breadcrumbList = $this->typeFactory->create('BreadcrumbList');

        foreach (\array_values($rootLine) as $index => $page) {
            $itemType = $this->typeFactory->create('WebPage');
            $link = $this->buildWebPageId($cObj, $event, (int)$page['uid']);

            $itemType->setId($link);

            $item = $this->typeFactory->create('ListItem')->setProperties([
                'position' => $index + 1,
                'name' => \is_string($page['nav_title'] ?? null) && $page['nav_title'] !== ''
                    ? $page['nav_title']
                    : ($page['title'] ?? ''),
                'item' => $itemType,
            ]);

            $breadcrumbList->addProperty('itemListElement', $item);
        }

        return $breadcrumbList;
    }

    /**
     * EXT:schema normally dispatches this event itself from a PageRenderer hook bound to
     * render-postProcess, filled into the page via addHeaderData()/addFooterData(). Unity's head
     * request never renders a full HTML document (disableAllHeaderCode is set), so that hook never
     * fires. Dispatch it here instead, so downstream listeners (e.g. content-element based
     * structured data) get the same, documented EXT:schema extension point.
     *
     * {@see \Brotkrueml\Schema\Hooks\PageRenderer\SchemaMarkupInjection::dispatchRenderAdditionalTypesEvent()}
     *
     * @param array<int, mixed> $rootLine
     *
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    private function dispatchRenderAdditionalTypesEvent(ManipulateHeadDataEvent $event, array $rootLine): void
    {
        $breadcrumbListAlreadyDefined = $this->schemaManager->hasBreadcrumbList()
            || $this->isBreadcrumbExcludedByBackendLayout($rootLine);

        /** @var RenderAdditionalTypesEvent $additionalTypesEvent */
        $additionalTypesEvent = $this->eventDispatcher->dispatch(new RenderAdditionalTypesEvent(
            $this->schemaManager->hasWebPage(),
            $breadcrumbListAlreadyDefined,
            $event->request,
        ));
        foreach ($additionalTypesEvent->getAdditionalTypes() as $additionalType) {
            $this->schemaManager->addType($additionalType);
        }
        foreach ($additionalTypesEvent->getMainEntitiesOfWebPage() as $mainEntity) {
            $this->schemaManager->addMainEntityOfWebPage($mainEntity);
        }
    }

    /**
     * Checks whether breadcrumb generation should be skipped for the
     * current page based on configured backend layouts.
     *
     * The current page's backend layout is checked first. If no layout is set,
     * parent pages are evaluated for backend layouts configured for the next level,
     * which are inherited by child pages in TYPO3.
     *
     * @param array<int, mixed> $rootLine
     *
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    private function isBreadcrumbExcludedByBackendLayout(array $rootLine): bool
    {
        try {
            $excludedBackendLayouts = GeneralUtility::trimExplode(
                ',',
                (string)($this->extensionConfiguration->get(
                    'unity_schema',
                    'automaticBreadcrumbExcludeAdditionalBackendLayouts'
                ) ?? ''),
                true
            );
        } catch (
            ExtensionConfigurationExtensionNotConfiguredException |
            ExtensionConfigurationPathDoesNotExistException
        ) {
            return false;
        }

        if ($excludedBackendLayouts === []) {
            return false;
        }

        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        $currentPage = \array_shift($rootLine);

        $pageRecord = $pageRepository->getPage((int)$currentPage['uid']);

        $backendLayout = $pageRecord['backend_layout'] ?? '';

        if ($backendLayout !== '') {
            return \in_array(
                (string)$backendLayout,
                $excludedBackendLayouts,
                true
            );
        }

        foreach ($rootLine as $page) {
            $pageRecord = $pageRepository->getPage((int)$page['uid']);

            $backendLayout = $pageRecord['backend_layout_next_level'] ?? '';

            if ($backendLayout !== '') {
                return \in_array(
                    (string)$backendLayout,
                    $excludedBackendLayouts,
                    true
                );
            }
        }

        return false;
    }
}
