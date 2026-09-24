<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\EventListener;

use Brotkrueml\Schema\Core\Model\TypeInterface;
use Brotkrueml\Schema\Event\RenderAdditionalTypesEvent;
use TYPO3\CMS\Core\Routing\PageArguments;
use WebVision\UnitySchema\Domain\Repository\PageContentRepository;
use WebVision\UnitySchema\StructuredData\Provider\GenericPropertiesProvider;
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
     * @param RenderAdditionalTypesEvent $event
     */
    private function addTypesForContentRecord(array $contentRecord, RenderAdditionalTypesEvent $event): void
    {
        $isMainEntity = (bool)($contentRecord['tx_schema_is_main_entity'] ?? false);

        foreach ($this->getTypesForContentRecord($contentRecord, $event) as $type) {
            if ($isMainEntity) {
                $event->addMainEntityOfWebPage($type);
                continue;
            }

            $event->addType($type);
        }
    }

    /**
     * A dedicated provider replaces the node the generic provider built for the same type, but
     * every node yielded by dedicated providers is kept - even several of the same type, e.g. one
     * ImageObject per image of a content element.
     *
     * @param array<string, mixed> $contentRecord
     * @param RenderAdditionalTypesEvent $event
     * @return array<int, TypeInterface>
     */
    private function getTypesForContentRecord(array $contentRecord, RenderAdditionalTypesEvent $event): array
    {
        $genericTypes = [];
        $dedicatedTypes = [];

        foreach ($this->providerRegistry->findFor($contentRecord) as $provider) {
            foreach ($provider->provide($contentRecord, $event->getRequest()) as $type) {
                if ($provider instanceof GenericPropertiesProvider) {
                    $genericTypes[] = $type;
                    continue;
                }
                $dedicatedTypes[] = $type;
            }
        }

        $dedicatedClasses = array_flip(array_map(
            static fn (TypeInterface $type): string => $type::class,
            $dedicatedTypes,
        ));
        $genericTypes = array_filter(
            $genericTypes,
            static fn (TypeInterface $type): bool => !isset($dedicatedClasses[$type::class]),
        );

        return [...array_values($genericTypes), ...$dedicatedTypes];
    }
}
