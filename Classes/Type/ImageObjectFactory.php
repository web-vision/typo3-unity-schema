<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Type;

use Brotkrueml\Schema\Core\Model\TypeInterface;
use Brotkrueml\Schema\Type\TypeFactory;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Builds a schema.org ImageObject from a FAL file, so image metadata (caption, credit, dimensions)
 * can be reused across every structured data provider that has an image (Article, Product,
 * ProfilePage, carousel items, ...).
 */
final class ImageObjectFactory
{
    public function __construct(
        private readonly TypeFactory $typeFactory,
    ) {
    }

    public function fromFile(FileInterface $file, ServerRequestInterface $request): TypeInterface
    {
        $imageObject = $this->typeFactory->create('ImageObject');
        $imageObject->setProperties(array_filter(
            [
                'contentUrl' => $this->getAbsoluteUrl($file, $request),
                'width' => $this->getProperty($file, 'width'),
                'height' => $this->getProperty($file, 'height'),
                'name' => $this->getProperty($file, 'title'),
                'caption' => $this->getCaption($file),
                'copyrightHolder' => $this->getProperty($file, 'copyright'),
            ],
            static fn (mixed $value): bool => $value !== null && $value !== '',
        ));

        return $imageObject;
    }

    private function getAbsoluteUrl(FileInterface $file, ServerRequestInterface $request): string
    {
        $publicUrl = (string)($file->getPublicUrl() ?? '');
        if ($publicUrl === '' || str_starts_with($publicUrl, 'http')) {
            return $publicUrl;
        }

        $normalizedParams = $request->getAttribute('normalizedParams');
        if (!$normalizedParams instanceof NormalizedParams) {
            return $publicUrl;
        }

        return rtrim($normalizedParams->getSiteUrl(), '/') . '/' . ltrim($publicUrl, '/');
    }

    private function getProperty(FileInterface $file, string $key): mixed
    {
        return $file->hasProperty($key) ? $file->getProperty($key) : null;
    }

    private function getCaption(FileInterface $file): ?string
    {
        foreach (['caption', 'description', 'alternative'] as $key) {
            $value = $this->getProperty($file, $key);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
