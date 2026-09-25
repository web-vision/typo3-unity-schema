<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\StructuredData\Provider;

use Brotkrueml\Schema\Core\Model\TypeInterface;
use Brotkrueml\Schema\Type\TypeFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
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
 *
 * Editor input is never trusted: properties unknown for the type, invalid JSON and values which
 * are not a non-empty scalar or a non-empty list of non-empty scalars (e.g. a nested JSON object)
 * are skipped and logged instead of being set, as EXT:schema throws for unknown properties, which
 * would break the whole head request of the page.
 */
final class GenericPropertiesProvider implements StructuredDataProviderInterface
{
    public function __construct(
        private readonly TypeFactory $typeFactory,
        private readonly LoggerInterface $logger,
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
        $uid = (int)($contentRecord['uid'] ?? 0);
        foreach ($this->decodeProperties((string)($contentRecord['tx_schema_properties'] ?? ''), $uid) as $name => $value) {
            if (!is_string($name) || !$type->hasProperty($name) || !$this->isApplicableValue($value)) {
                $this->logger->warning(
                    'Skipped structured data property "{property}" of tt_content:{uid}, as it is unknown for the type or its value is not a non-empty scalar or list of scalars.',
                    ['property' => (string)$name, 'uid' => $uid]
                );
                continue;
            }

            $type->setProperty($name, $value);
        }

        yield $type;
    }

    private function isApplicableValue(mixed $value): bool
    {
        if (is_scalar($value)) {
            return $value !== '';
        }

        return is_array($value)
            && $value !== []
            && array_is_list($value)
            && array_filter($value, static fn (mixed $item): bool => !is_scalar($item) || $item === '') === [];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodeProperties(string $json, int $uid): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            $this->logger->warning(
                'Skipped structured data properties of tt_content:{uid}, as they are not a JSON object{error}',
                ['uid' => $uid, 'error' => json_last_error() !== JSON_ERROR_NONE ? ': ' . json_last_error_msg() : '']
            );
            return [];
        }

        return $decoded;
    }
}
