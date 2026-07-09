<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Reproduces DEVELOPER.md's documented scenario: a consuming site package registers its own
 * {@see \WebVision\UnitySchema\StructuredData\StructuredDataProviderInterface} implementation via
 * plain autowiring, relying on unity-schema's Services.yaml `_instanceof` rule to tag it for the
 * `unity_schema.structured_data_provider` tagged_iterator - without unity-schema knowing anything
 * about that provider's package.
 */
final class CrossPackageStructuredDataProviderTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/cross_package_provider_setup.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://acme.com/',
            ),
        );
    }

    #[Test]
    public function providerFromAConsumingExtensionIsTaggedAndCalled(): void
    {
        $internalRequest = new InternalRequest('https://acme.com/carousel-page?type=3210');
        $response = $this->executeFrontendSubRequest($internalRequest);

        $this->assertSame(200, $response->getStatusCode());
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        $this->assertJson($body);
        $decodedBody = json_decode($body, true);
        $this->assertIsArray($decodedBody);
        $this->assertArrayHasKey('jsonLd', $decodedBody);
        $this->assertJson($decodedBody['jsonLd']);
        $decodedJsonLd = json_decode($decodedBody['jsonLd'], true);

        $itemLists = array_filter(
            $decodedJsonLd['@graph'] ?? [],
            static fn (array $node): bool => ($node['@type'] ?? '') === 'ItemList',
        );

        $this->assertCount(1, $itemLists);
    }
}
