<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Tests\Unit\StructuredData;

use Brotkrueml\Schema\Type\TypeFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\UnitySchema\StructuredData\Provider\GenericPropertiesProvider;

final class GenericPropertiesProviderTest extends UnitTestCase
{
    private GenericPropertiesProvider $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new GenericPropertiesProvider(new TypeFactory());
    }

    /**
     * @param array<string, mixed> $contentRecord
     */
    #[Test]
    #[DataProvider('contentRecordDataProvider')]
    public function supportsMatchesExpectation(array $contentRecord, bool $expected): void
    {
        $this->assertSame($expected, $this->subject->supports($contentRecord));
    }

    /**
     * @return \Generator<string, array{contentRecord: array<string, mixed>, expected: bool}>
     */
    public static function contentRecordDataProvider(): \Generator
    {
        yield 'schema type is set' => [
            'contentRecord' => ['tx_schema_type' => 'Article'],
            'expected' => true,
        ];
        yield 'schema type key is missing' => [
            'contentRecord' => [],
            'expected' => false,
        ];
        yield 'schema type is an empty string' => [
            'contentRecord' => ['tx_schema_type' => ''],
            'expected' => false,
        ];
    }
}
