<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\StructuredData;

use Brotkrueml\Schema\Core\Model\TypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Implement this interface in a consuming extension to turn a specific content element (matched by
 * CType, tx_schema_type or any other record property) into one or more schema.org types. Tag the
 * implementing service with "unity_schema.structured_data_provider" (done automatically for any
 * autowired service implementing this interface, see Configuration/Services.yaml _instanceof).
 */
interface StructuredDataProviderInterface
{
    /**
     * @param array<string, mixed> $contentRecord A tt_content row
     */
    public function supports(array $contentRecord): bool;

    /**
     * @param array<string, mixed> $contentRecord A tt_content row
     * @return iterable<TypeInterface>
     */
    public function provide(array $contentRecord, ServerRequestInterface $request): iterable;
}
