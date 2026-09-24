<?php

declare(strict_types=1);

namespace Acme\SitePackageProvider\StructuredData\Provider;

use Brotkrueml\Schema\Core\Model\TypeInterface;
use Brotkrueml\Schema\Type\TypeFactory;
use Psr\Http\Message\ServerRequestInterface;
use WebVision\UnitySchema\StructuredData\StructuredDataProviderInterface;

/**
 * Fixture provider yielding several nodes of the same type for one content element, like a
 * site package exposing one ImageObject per image of a gallery element.
 */
final class SiteGalleryProvider implements StructuredDataProviderInterface
{
    public function __construct(
        private readonly TypeFactory $typeFactory,
    ) {
    }

    public function supports(array $contentRecord): bool
    {
        return ($contentRecord['CType'] ?? '') === 'site_gallery';
    }

    /**
     * @return iterable<TypeInterface>
     */
    public function provide(array $contentRecord, ServerRequestInterface $request): iterable
    {
        foreach (['first.jpg', 'second.jpg', 'third.jpg'] as $fileName) {
            $imageObject = $this->typeFactory->create('ImageObject');
            $imageObject->setProperties(['contentUrl' => 'https://acme.com/fileadmin/' . $fileName]);

            yield $imageObject;
        }
    }
}
