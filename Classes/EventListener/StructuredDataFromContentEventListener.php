<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\EventListener;

use Brotkrueml\Schema\Event\RenderAdditionalTypesEvent;
use TYPO3\CMS\Core\Routing\PageArguments;
use WebVision\UnitySchema\Domain\Repository\PageContentRepository;
use WebVision\UnitySchema\StructuredData\StructuredDataProviderRegistry;

/**
 * Turns the content elements of the current page into schema.org types, using whichever
 * {@see \WebVision\UnitySchema\StructuredData\StructuredDataProviderInterface} matches each one
 * (the built-in generic one and/or ones registered by a consuming extension).
 */
final class StructuredDataFromContentEventListener
{
    public function __construct(
        private readonly PageContentRepository $pageContentRepository,
        private readonly StructuredDataProviderRegistry $providerRegistry,
    ) {
    }

    public function __invoke(RenderAdditionalTypesEvent $event): void
    {
        $pageArguments = $event->getRequest()->getAttribute('routing');
        if (!$pageArguments instanceof PageArguments) {
            return;
        }

        $contentRecords = $this->pageContentRepository->findByPageId($pageArguments->getPageId(), $event->getRequest());
        foreach ($contentRecords as $contentRecord) {
            $this->addTypesForContentRecord($contentRecord, $event);
        }
    }

    /**
     * @param array<string, mixed> $contentRecord
     */
    private function addTypesForContentRecord(array $contentRecord, RenderAdditionalTypesEvent $event): void
    {
        $isMainEntity = (bool)($contentRecord['tx_schema_is_main_entity'] ?? false);
        foreach ($this->providerRegistry->findFor($contentRecord) as $provider) {
            foreach ($provider->provide($contentRecord, $event->getRequest()) as $type) {
                if ($isMainEntity) {
                    $event->addMainEntityOfWebPage($type);
                    continue;
                }
                $event->addType($type);
            }
        }
    }
}
