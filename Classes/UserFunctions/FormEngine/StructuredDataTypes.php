<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\UserFunctions\FormEngine;

use Brotkrueml\Schema\Type\TypeProvider;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Provides a user function used as itemsProcFunc in the TCA definition for
 * tt_content.tx_schema_type, listing every schema.org type known to EXT:schema.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final class StructuredDataTypes
{
    public function __construct(
        private readonly TypeProvider $typeProvider,
    ) {
    }

    /**
     * @param array{items: list<array{label: string, value: string}>} $params
     */
    public function get(array &$params): void
    {
        $types = $this->typeProvider->getTypes();
        sort($types);
        foreach ($types as $type) {
            $params['items'][] = [
                'label' => $type,
                'value' => $type,
            ];
        }
    }
}
