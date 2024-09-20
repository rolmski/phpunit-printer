<?php

declare(strict_types=1);

namespace Czim\PHPUnitPrinter\Context;

class TestClassInformation
{
    /**
     * @param string                         $class
     * @param array<string, TestInformation> $tests by test method name
     */
    public function __construct(
        public readonly string $class,
        public array $tests = [],
    ) {
    }
}
