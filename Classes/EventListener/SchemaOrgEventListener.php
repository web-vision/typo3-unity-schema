<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\EventListener;

use Brotkrueml\Schema\Manager\SchemaManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
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

        // as the SchemaManager and no other caller inside EXT:schema have a plain return of the JSON, we need to take
        // the output here and strip the pre-rendered <script> tags. Otherwise, the returned value is not JSON-decodable
        $json = strip_tags($this->schemaManager->renderJsonLd());
        $event->headData['jsonLd'] = $json;
    }
}
