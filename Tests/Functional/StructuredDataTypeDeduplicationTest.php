<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * A dedicated provider replaces the node the generic provider builds for the same type of a
 * content element, while every node yielded by dedicated providers is kept - including several
 * nodes of the same type, e.g. one ImageObject per image.
 */
final class StructuredDataTypeDeduplicationTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $testExtensionsToLoad = [
        'brotkrueml/schema',
        'web-vision/wv_t3unity',
        'web-vision/unity-schema',
        'acme/site-package-provider',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/structured_data_type_deduplication_setup.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://acme.com/',
            ),
        );
    }

    #[Test]
    public function multipleNodesOfTheSameTypeFromADedicatedProviderAreKept(): void
    {
        $imageObjects = $this->findNodesOfType($this->fetchJsonLdNodes('/gallery-page'), 'ImageObject');

        $this->assertSame(
            [
                'https://acme.com/fileadmin/first.jpg',
                'https://acme.com/fileadmin/second.jpg',
                'https://acme.com/fileadmin/third.jpg',
            ],
            array_column($imageObjects, 'contentUrl'),
        );
    }

    #[Test]
    public function dedicatedProviderReplacesGenericNodeOfTheSameType(): void
    {
        $articles = $this->findNodesOfType($this->fetchJsonLdNodes('/article-page'), 'Article');

        $this->assertSame(['Headline from dedicated provider'], array_column($articles, 'headline'));
    }

    #[Test]
    public function genericNodeIsKeptWithoutDedicatedProviderForTheSameType(): void
    {
        $newsArticles = $this->findNodesOfType($this->fetchJsonLdNodes('/news-article-page'), 'NewsArticle');

        $this->assertSame(['Headline from generic provider'], array_column($newsArticles, 'headline'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchJsonLdNodes(string $path): array
    {
        $internalRequest = new InternalRequest('https://acme.com' . $path . '?type=3210');
        $response = $this->executeFrontendSubRequest($internalRequest);

        $this->assertSame(200, $response->getStatusCode());
        $response->getBody()->rewind();
        $decodedBody = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($decodedBody);
        $this->assertArrayHasKey('jsonLd', $decodedBody);
        $decodedJsonLd = json_decode($decodedBody['jsonLd'], true);
        $this->assertIsArray($decodedJsonLd);

        return $decodedJsonLd['@graph'] ?? [$decodedJsonLd];
    }

    /**
     * @param list<array<string, mixed>> $nodes
     * @return list<array<string, mixed>>
     */
    private function findNodesOfType(array $nodes, string $type): array
    {
        return array_values(array_filter(
            $nodes,
            static fn (array $node): bool => ($node['@type'] ?? '') === $type,
        ));
    }
}
