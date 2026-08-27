<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class JsonLdRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $testExtensionsToLoad = [
        'brotkrueml/schema',
        'web-vision/wv_t3unity',
        'web-vision/unity-schema',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/basic_setup.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://acme.com/',
            ),
        );
    }

    #[Test]
    public function jsonLdIsAddedForWebPage(): void
    {
        $internalRequest = new InternalRequest('https://acme.com/?type=3210');
        $response = $this->executeFrontendSubRequest($internalRequest);

        $this->assertSame(200, $response->getStatusCode());
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        $this->assertJson($body);
        $decodedBody = json_decode($body, true);
        $this->assertIsArray($decodedBody);
        $this->assertArrayHasKey('jsonLd', $decodedBody);
        $this->assertJson($decodedBody['jsonLd']);
        $this->assertJsonStringEqualsJsonString(
            '{"@context":"https://schema.org/","@type":"WebPage","@id":"https://acme.com/","dateModified":"2026-03-23T18:02:24+00:00","datePublished":"2026-01-23T18:02:08+00:00","name":"ACME Test"}',
            $decodedBody['jsonLd']
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function webPageTypeIsAddedCorrect(): void
    {
        $internalRequest = new InternalRequest('https://acme.com/about-us?type=3210');
        $response = $this->executeFrontendSubRequest($internalRequest);

        $this->assertSame(200, $response->getStatusCode());
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        $this->assertJson($body);
        $decodedBody = json_decode($body, true);
        $this->assertIsArray($decodedBody);
        $this->assertArrayHasKey('jsonLd', $decodedBody);
        $this->assertJson($decodedBody['jsonLd']);
        $this->assertJsonStringEqualsJsonString(
            '{"@context":"https://schema.org/","@type":"AboutPage","@id":"https://acme.com/about-us","dateModified":"2026-03-23T18:02:24+00:00","datePublished":"2026-01-23T18:02:08+00:00","name":"About us"}',
            $decodedBody['jsonLd']
        );
    }
}
