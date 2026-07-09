<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Tests\Functional\Type;

use Brotkrueml\Schema\Type\TypeFactory;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WebVision\UnitySchema\Type\ImageObjectFactory;

/**
 * Functional (not unit) test: constructing any EXT:schema type model requires the "tx_schema" cache
 * to be configured, which only happens once EXT:schema's ext_localconf.php has run.
 */
final class ImageObjectFactoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'brotkrueml/schema',
        'web-vision/wv_t3unity',
        'web-vision/unity-schema',
    ];

    private ImageObjectFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ImageObjectFactory(new TypeFactory());
    }

    #[Test]
    public function fromFileKeepsAlreadyAbsoluteContentUrl(): void
    {
        $file = $this->createFile(['getPublicUrl' => 'https://example.org/image.jpg']);
        $request = $this->createRequest();

        $imageObject = $this->subject->fromFile($file, $request);

        $this->assertSame('https://example.org/image.jpg', $imageObject->getProperty('contentUrl'));
    }

    #[Test]
    public function fromFilePrefixesRelativeContentUrlWithSiteUrl(): void
    {
        $file = $this->createFile(['getPublicUrl' => 'fileadmin/image.jpg']);
        $request = $this->createRequest('https://example.org/');

        $imageObject = $this->subject->fromFile($file, $request);

        $this->assertSame('https://example.org/fileadmin/image.jpg', $imageObject->getProperty('contentUrl'));
    }

    #[Test]
    public function fromFileUsesCaptionFallbackChain(): void
    {
        $file = $this->createFile(
            ['getPublicUrl' => 'https://example.org/image.jpg'],
            ['description' => 'A description', 'alternative' => 'An alternative'],
        );
        $request = $this->createRequest();

        $imageObject = $this->subject->fromFile($file, $request);

        $this->assertSame('A description', $imageObject->getProperty('caption'));
    }

    #[Test]
    public function fromFileOmitsPropertiesThatAreNotAvailable(): void
    {
        $file = $this->createFile(['getPublicUrl' => 'https://example.org/image.jpg']);
        $request = $this->createRequest();

        $imageObject = $this->subject->fromFile($file, $request);

        $this->assertNull($imageObject->getProperty('name'));
        $this->assertNull($imageObject->getProperty('copyrightHolder'));
    }

    /**
     * @param array<string, mixed> $methodReturnValues
     * @param array<string, mixed> $properties
     */
    private function createFile(array $methodReturnValues, array $properties = []): FileInterface
    {
        $file = $this->createMock(FileInterface::class);
        $file->method('getPublicUrl')->willReturn($methodReturnValues['getPublicUrl']);
        $file->method('hasProperty')->willReturnCallback(
            static fn (string $key): bool => array_key_exists($key, $properties),
        );
        $file->method('getProperty')->willReturnCallback(
            static fn (string $key): mixed => $properties[$key] ?? null,
        );

        return $file;
    }

    private function createRequest(?string $siteUrl = null): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        if ($siteUrl === null) {
            $request->method('getAttribute')->with('normalizedParams')->willReturn(null);

            return $request;
        }

        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSiteUrl')->willReturn($siteUrl);
        $request->method('getAttribute')->with('normalizedParams')->willReturn($normalizedParams);

        return $request;
    }
}
