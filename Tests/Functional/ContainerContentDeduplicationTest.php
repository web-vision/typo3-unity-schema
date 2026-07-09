<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContainerContentDeduplicationTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $testExtensionsToLoad = [
        'b13/container',
        'brotkrueml/schema',
        'web-vision/wv_t3unity',
        'web-vision/unity-schema',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'unity_schema' => [
                'containerParentField' => 'tx_container_parent',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/container_setup.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://acme.com/',
            ),
        );
    }

    #[Test]
    public function structuredDataFromAContainerChildIsNotDuplicated(): void
    {
        $internalRequest = new InternalRequest('https://acme.com/container-page?type=3210');
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

        $imageObjects = array_filter(
            $decodedJsonLd['@graph'] ?? [],
            static fn (array $node): bool => ($node['@type'] ?? '') === 'ImageObject',
        );

        $this->assertCount(1, $imageObjects);
    }
}
