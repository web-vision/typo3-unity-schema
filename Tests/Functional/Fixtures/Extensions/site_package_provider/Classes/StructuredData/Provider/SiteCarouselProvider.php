<?php

declare(strict_types=1);

namespace Acme\SitePackageProvider\StructuredData\Provider;

use Brotkrueml\Schema\Core\Model\TypeInterface;
use Brotkrueml\Schema\Type\TypeFactory;
use Psr\Http\Message\ServerRequestInterface;
use WebVision\UnitySchema\StructuredData\StructuredDataProviderInterface;

/**
 * Fixture provider mirroring DEVELOPER.md's documented CarouselProvider example: a consuming
 * site package registering a provider purely by implementing the interface, relying on
 * unity-schema's cross-package autoconfiguration to get tagged and picked up by the registry.
 */
final class SiteCarouselProvider implements StructuredDataProviderInterface
{
    public function __construct(
        private readonly TypeFactory $typeFactory,
    ) {
    }

    public function supports(array $contentRecord): bool
    {
        return ($contentRecord['CType'] ?? '') === 'site_carousel';
    }

    /**
     * @return iterable<TypeInterface>
     */
    public function provide(array $contentRecord, ServerRequestInterface $request): iterable
    {
        $listItem = $this->typeFactory->create('ListItem');
        $listItem->setProperties(['position' => 1]);

        $itemList = $this->typeFactory->create('ItemList');
        $itemList->setProperties(['itemListElement' => [$listItem]]);

        yield $itemList;
    }
}
