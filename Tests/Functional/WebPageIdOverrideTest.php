<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The WebPage @id must default to the CMS URL, yet stay overridable from an external extension so a
 * decoupled storefront (e.g. Magento) URL can replace it. The override is expressed purely in
 * TypoScript - a typolink `userFunc` on UnityHead.10.schema.10.id.typolink - and must survive the
 * listener's own WebPage generation, which previously clobbered it.
 */
final class WebPageIdOverrideTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $testExtensionsToLoad = [
        'brotkrueml/schema',
        'web-vision/wv_t3unity',
        'web-vision/unity-schema',
        'acme/schema-id-override',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://acme.com/',
            ),
        );
    }

    #[Test]
    public function webPageIdDefaultsToTheCmsUrl(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/basic_setup.csv');

        $webPage = $this->resolveWebPage('https://acme.com/about-us?type=3210');

        $this->assertSame('https://acme.com/about-us', $webPage['@id'] ?? null);
    }

    #[Test]
    public function webPageIdCanBeOverriddenByAnExternalTypolinkUserFunc(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/webpage_id_override_setup.csv');

        $webPage = $this->resolveWebPage('https://acme.com/about-us?type=3210');

        $this->assertSame('https://shop.example/about-us', $webPage['@id'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveWebPage(string $url): array
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest($url));

        $this->assertSame(200, $response->getStatusCode());
        $response->getBody()->rewind();
        $decodedBody = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($decodedBody);
        $this->assertArrayHasKey('jsonLd', $decodedBody);

        $webPage = json_decode($decodedBody['jsonLd'], true);
        $this->assertIsArray($webPage);
        $this->assertSame('AboutPage', $webPage['@type'] ?? null);

        return $webPage;
    }
}
