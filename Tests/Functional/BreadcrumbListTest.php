<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The generated breadcrumb trail follows EXT:schema's AddBreadcrumbList: it starts at the root
 * (position 1) and skips the site root, sysfolders, spacers, hidden pages and pages hidden in
 * menus. Pages with an excluded backend layout get no breadcrumb at all.
 */
final class BreadcrumbListTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $testExtensionsToLoad = [
        'brotkrueml/schema',
        'web-vision/wv_t3unity',
        'web-vision/unity-schema',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'schema' => [
                'automaticWebPageSchemaGeneration' => '1',
                'automaticBreadcrumbSchemaGeneration' => '1',
                'allowOnlyOneBreadcrumbList' => '1',
            ],
            'unity_schema' => [
                'automaticBreadcrumbExcludeAdditionalBackendLayouts' => 'pagets__Category',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/breadcrumb_setup.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://acme.com/',
            ),
        );
    }

    #[Test]
    public function breadcrumbTrailStartsAtTheRootAndSkipsPagesNotShownInMenus(): void
    {
        $webPage = $this->fetchJsonLd('/folder/products/hidden-section/hidden-in-menu/current-page');

        $this->assertArrayHasKey('breadcrumb', $webPage);
        $this->assertSame(
            [
                [1, 'Our products', 'https://acme.com/folder/products'],
                [2, 'Current page', 'https://acme.com/folder/products/hidden-section/hidden-in-menu/current-page'],
            ],
            array_map(
                static fn (array $item): array => [(int)$item['position'], $item['name'], $item['item']['@id']],
                $webPage['breadcrumb']['itemListElement'],
            ),
        );
    }

    #[Test]
    public function breadcrumbIsNotRenderedForExcludedBackendLayout(): void
    {
        $webPage = $this->fetchJsonLd('/category');

        $this->assertSame('WebPage', $webPage['@type']);
        $this->assertArrayNotHasKey('breadcrumb', $webPage);
    }

    #[Test]
    public function noBreadcrumbIsRenderedWhenTheTrailIsEmpty(): void
    {
        // The parent is access restricted, the current page hidden in menus: the trail is empty,
        // and EXT:schema's own breadcrumb (with CMS URLs) must not step in instead.
        $webPage = $this->fetchJsonLd('/members/news');

        $this->assertSame('WebPage', $webPage['@type']);
        $this->assertArrayNotHasKey('breadcrumb', $webPage);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchJsonLd(string $path): array
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://acme.com' . $path . '?type=3210'));

        $this->assertSame(200, $response->getStatusCode());
        $response->getBody()->rewind();
        $decodedBody = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($decodedBody);
        $this->assertArrayHasKey('jsonLd', $decodedBody);
        $decodedJsonLd = json_decode($decodedBody['jsonLd'], true);
        $this->assertIsArray($decodedJsonLd);

        return $decodedJsonLd;
    }
}
