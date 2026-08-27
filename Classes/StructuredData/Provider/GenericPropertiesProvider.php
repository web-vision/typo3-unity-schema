<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\StructuredData\Provider;

use Brotkrueml\Schema\Core\Model\TypeInterface;
use Brotkrueml\Schema\Type\TypeFactory;
use Psr\Http\Message\ServerRequestInterface;
use WebVision\UnitySchema\StructuredData\StructuredDataProviderInterface;

/**
 * Built-in, config-driven provider: turns a content element into a schema.org type purely from
 * the editor-facing tt_content.tx_schema_type / tx_schema_properties fields, without requiring any
 * PHP in the consuming project. Only suited for flat, scalar properties (or lists of scalars, e.g.
 * sameAs) - a plain JSON object decodes into a PHP array, and EXT:schema's renderer treats every
 * array property value as a list of scalars, not as a nested object. Any property whose value must
 * be another type (offers, acceptedAnswer, aggregateRating, an author given as a Person, a
 * carousel's items, ...) needs a dedicated {@see StructuredDataProviderInterface} implementation
 * instead, see DEVELOPER.md.
 */
final class GenericPropertiesProvider implements StructuredDataProviderInterface
{
    public function __construct(
        private readonly TypeFactory $typeFactory,
    ) {
    }

    public function supports(array $contentRecord): bool
    {
        return (string)($contentRecord['tx_schema_type'] ?? '') !== '';
    }

    /**
     * @return iterable<TypeInterface>
     */
    public function provide(array $contentRecord, ServerRequestInterface $request): iterable
    {
        $type = $this->typeFactory->create((string)$contentRecord['tx_schema_type']);
        $properties = $this->decodeProperties((string)($contentRecord['tx_schema_properties'] ?? ''));
        if ($properties !== []) {
            $type->setProperties($properties);
        }

        yield $type;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeProperties(string $json): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
