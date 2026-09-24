<?php

declare(strict_types=1);

namespace Acme\SitePackageProvider\StructuredData\Provider;

use Brotkrueml\Schema\Core\Model\TypeInterface;
use Brotkrueml\Schema\Type\TypeFactory;
use Psr\Http\Message\ServerRequestInterface;
use WebVision\UnitySchema\StructuredData\StructuredDataProviderInterface;

/**
 * Fixture provider building a dedicated node for the editor selected "Article" type, which has
 * to replace the node the generic provider builds for the same content element.
 */
final class SiteArticleProvider implements StructuredDataProviderInterface
{
    public function __construct(
        private readonly TypeFactory $typeFactory,
    ) {
    }

    public function supports(array $contentRecord): bool
    {
        return ($contentRecord['tx_schema_type'] ?? '') === 'Article';
    }

    /**
     * @return iterable<TypeInterface>
     */
    public function provide(array $contentRecord, ServerRequestInterface $request): iterable
    {
        $article = $this->typeFactory->create('Article');
        $article->setProperties(['headline' => 'Headline from dedicated provider']);

        yield $article;
    }
}
