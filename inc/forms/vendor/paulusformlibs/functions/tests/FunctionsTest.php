<?php

use PHPUnit\Framework\TestCase;
use function PaulusFormLibs\Functions\{strip_whitespaces};

final class FunctionsTest extends TestCase
{
    public function testStripWhitespaces(): void
    {
        $input = "	　　test\xff  	";

        $this->assertTrue("test�" === strip_whitespaces($input));
    }
}
