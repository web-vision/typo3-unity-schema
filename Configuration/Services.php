<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebVision\UnitySchema\StructuredData\StructuredDataProviderInterface;

/**
 * A YAML `_instanceof` rule only auto-tags classes discovered by the *same* Services.yaml's own
 * `resource:` glob - TYPO3 loads every package's Services.yaml through its own fresh
 * YamlFileLoader instance, and `_instanceof` is scoped to that loader
 * ({@see \Symfony\Component\DependencyInjection\Loader\FileLoader::setDefinition()}). It can never
 * reach a provider class from a consuming extension's own package.
 * `ContainerBuilder::registerForAutoconfiguration()` is the container-wide equivalent - it is only
 * reachable from a Services.php closure that type-hints the raw ContainerBuilder, not from
 * ContainerConfigurator's `->instanceof()` (which has the same per-loader scoping as YAML).
 */
return static function (ContainerBuilder $container): void {
    $container->registerForAutoconfiguration(StructuredDataProviderInterface::class)
        ->addTag('unity_schema.structured_data_provider');
};
