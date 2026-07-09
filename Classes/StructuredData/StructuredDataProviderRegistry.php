<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\StructuredData;

/**
 * Collects every tagged {@see StructuredDataProviderInterface} service (from this extension and any
 * consuming extension) and resolves the ones matching a given content element.
 */
final class StructuredDataProviderRegistry
{
    /**
     * @param iterable<StructuredDataProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    /**
     * @param array<string, mixed> $contentRecord A tt_content row
     * @return iterable<StructuredDataProviderInterface>
     */
    public function findFor(array $contentRecord): iterable
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($contentRecord)) {
                yield $provider;
            }
        }
    }
}
