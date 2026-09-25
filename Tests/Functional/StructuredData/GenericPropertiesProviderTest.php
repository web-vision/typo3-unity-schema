<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Tests\Functional\StructuredData;

use Brotkrueml\Schema\Type\TypeFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\AbstractLogger;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WebVision\UnitySchema\StructuredData\Provider\GenericPropertiesProvider;

/**
 * The editor provided properties are never trusted: only properties known by the type with a
 * non-empty scalar or a non-empty list of non-empty scalars are applied, everything else is
 * skipped and logged instead of throwing. Functional, as the TypeFactory needs the registered
 * schema.org types of the loaded packages.
 */
final class GenericPropertiesProviderTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'brotkrueml/schema',
        'web-vision/wv_t3unity',
        'web-vision/unity-schema',
    ];

    private GenericPropertiesProvider $subject;

    /**
     * @var AbstractLogger&object{messages: list<string>, contexts: list<array<string, mixed>>}
     */
    private AbstractLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new class() extends AbstractLogger {
            /**
             * @var list<string>
             */
            public array $messages = [];

            /**
             * @var list<array<string, mixed>>
             */
            public array $contexts = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = (string)$message;
                $this->contexts[] = ['level' => $level, ...$context];
            }
        };
        $this->subject = new GenericPropertiesProvider(new TypeFactory(), $this->logger);
    }

    /**
     * @param array<string, mixed> $expectedProperties
     */
    #[Test]
    #[DataProvider('editorPropertiesDataProvider')]
    public function provideOnlyAppliesKnownPropertiesWithScalarValues(
        string $json,
        array $expectedProperties,
        int $expectedLogEntries,
    ): void {
        $types = [];
        $contentRecord = ['uid' => 42, 'tx_schema_type' => 'Article', 'tx_schema_properties' => $json];
        foreach ($this->subject->provide($contentRecord, new ServerRequest('https://example.com/')) as $type) {
            $types[] = $type;
        }

        $this->assertCount(1, $types);
        foreach ($expectedProperties as $name => $value) {
            $this->assertSame($value, $types[0]->getProperty($name), $name);
        }
        foreach (['headline', 'keywords', 'author', 'isAccessibleForFree', 'wordCount'] as $name) {
            if (!array_key_exists($name, $expectedProperties)) {
                $this->assertNull($types[0]->getProperty($name), $name);
            }
        }
        $this->assertCount($expectedLogEntries, $this->logger->messages);
    }

    /**
     * @return \Generator<string, array{json: string, expectedProperties: array<string, mixed>, expectedLogEntries: int}>
     */
    public static function editorPropertiesDataProvider(): \Generator
    {
        yield 'no properties' => [
            'json' => '',
            'expectedProperties' => [],
            'expectedLogEntries' => 0,
        ];
        yield 'known scalar and list of scalars are applied' => [
            'json' => '{"headline":"Headline","keywords":["a","b"]}',
            'expectedProperties' => ['headline' => 'Headline', 'keywords' => ['a', 'b']],
            'expectedLogEntries' => 0,
        ];
        yield 'false and zero are applied' => [
            'json' => '{"isAccessibleForFree":false,"wordCount":0}',
            'expectedProperties' => ['isAccessibleForFree' => false, 'wordCount' => '0'],
            'expectedLogEntries' => 0,
        ];
        yield 'unknown property is skipped instead of throwing' => [
            'json' => '{"keyword":"typo","headline":"Headline"}',
            'expectedProperties' => ['headline' => 'Headline'],
            'expectedLogEntries' => 1,
        ];
        yield 'json-ld keywords are skipped' => [
            'json' => '{"@type":"Person","@id":"https://example.com/#x"}',
            'expectedProperties' => [],
            'expectedLogEntries' => 2,
        ];
        yield 'nested object is skipped' => [
            'json' => '{"author":{"@type":"Person","name":"Jane Doe"}}',
            'expectedProperties' => [],
            'expectedLogEntries' => 1,
        ];
        yield 'list containing an object or empty string is skipped' => [
            'json' => '{"keywords":["a",{"b":1}],"headline":["","x"]}',
            'expectedProperties' => [],
            'expectedLogEntries' => 2,
        ];
        yield 'empty values are skipped' => [
            'json' => '{"headline":"","keywords":[],"author":{},"inLanguage":null}',
            'expectedProperties' => [],
            'expectedLogEntries' => 4,
        ];
        yield 'json list with numeric keys is skipped' => [
            'json' => '["a","b"]',
            'expectedProperties' => [],
            'expectedLogEntries' => 2,
        ];
        yield 'invalid json is skipped' => [
            'json' => '{"headline":',
            'expectedProperties' => [],
            'expectedLogEntries' => 1,
        ];
        yield 'scalar json is skipped' => [
            'json' => '"headline"',
            'expectedProperties' => [],
            'expectedLogEntries' => 1,
        ];
    }

    #[Test]
    public function skippedPropertyIsLoggedAsWarningWithPropertyAndUid(): void
    {
        foreach ($this->subject->provide(
            ['uid' => 42, 'tx_schema_type' => 'Article', 'tx_schema_properties' => '{"keyword":"typo"}'],
            new ServerRequest('https://example.com/'),
        ) as $type) {
            $this->assertNull($type->getProperty('keywords'));
        }

        $this->assertSame([['level' => 'warning', 'property' => 'keyword', 'uid' => 42]], $this->logger->contexts);
    }
}
