<?php

namespace WebVision\UnitySchema\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DummyTest extends UnitTestCase
{
    #[Test]
    public function dummy(): void
    {
        $this->assertTrue(true);
    }
}
