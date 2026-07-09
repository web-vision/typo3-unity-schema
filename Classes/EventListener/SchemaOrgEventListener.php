<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\EventListener;

use Brotkrueml\Schema\Event\RenderAdditionalTypesEvent;
use Brotkrueml\Schema\Manager\SchemaManager;
use Brotkrueml\Schema\Type\TypeFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
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
    ) {
    }

    /**
     * @throws ContentRenderingException
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

        $webPageType = $cObj->data['tx_schema_webpagetype'] ?? '';
        if ($webPageType === '') {
            $webPageType = 'WebPage';
        }
        $generatedType = $this->typeFactory->create($webPageType);
        $url = $cObj->typoLink_URL([
            'parameter' => 't3://page?uid=' . $cObj->data['uid'],
            'forceAbsoluteUrl' => true,
        ]);
        $generatedType->setId($url);
        $generatedType->setProperties([
            'dateModified' => (new \DateTimeImmutable())->setTimestamp((int)$cObj->data['tstamp'])->format(\DateTimeImmutable::ATOM),
            'datePublished' => (new \DateTimeImmutable())->setTimestamp((int)$cObj->data['crdate'])->format(\DateTimeImmutable::ATOM),
            'name' => $cObj->data['title'] ?? '',
        ]);
        $this->schemaManager->addType($generatedType);
        $this->dispatchRenderAdditionalTypesEvent($event);
        // as the SchemaManager and no other caller inside EXT:schema have a plain return of the JSON, we need to take
        // the output here and strip the pre-rendered <script> tags. Otherwise, the returned value is not JSON-decodable
        $json = strip_tags($this->schemaManager->renderJsonLd());
        $event->headData['jsonLd'] = $json;
    }

    /**
     * EXT:schema normally dispatches this event itself from a PageRenderer hook bound to
     * render-postProcess, filled into the page via addHeaderData()/addFooterData(). Unity's head
     * request never renders a full HTML document (disableAllHeaderCode is set), so that hook never
     * fires. Dispatch it here instead, so downstream listeners (e.g. content-element based
     * structured data) get the same, documented EXT:schema extension point.
     *
     * {@see \Brotkrueml\Schema\Hooks\PageRenderer\SchemaMarkupInjection::dispatchRenderAdditionalTypesEvent()}
     */
    private function dispatchRenderAdditionalTypesEvent(ManipulateHeadDataEvent $event): void
    {
        /** @var RenderAdditionalTypesEvent $additionalTypesEvent */
        $additionalTypesEvent = $this->eventDispatcher->dispatch(new RenderAdditionalTypesEvent(
            $this->schemaManager->hasWebPage(),
            $this->schemaManager->hasBreadcrumbList(),
            $event->request,
        ));
        foreach ($additionalTypesEvent->getAdditionalTypes() as $additionalType) {
            $this->schemaManager->addType($additionalType);
        }
        foreach ($additionalTypesEvent->getMainEntitiesOfWebPage() as $mainEntity) {
            $this->schemaManager->addMainEntityOfWebPage($mainEntity);
        }
    }
}
