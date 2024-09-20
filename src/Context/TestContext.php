<?php

declare(strict_types=1);

namespace Czim\PHPUnitPrinter\Context;

use Czim\PHPUnitPrinter\Enums\TestStatus;
use Czim\PHPUnitPrinter\Output\FormatterInterface;
use Czim\PHPUnitPrinter\Output\PrinterInterface;
use PHPUnit\Event\Telemetry\Duration;

class TestContext
{
    protected int $totalTests;
    protected int $currentTestNumber = 0;

    /**
     * @var array<string, TestClassInformation>
     */
    protected array $testClasses = [];

    protected ?string $currentTestClass = null;
    protected ?string $currentTestMethod = null;


    public function __construct(
        protected readonly string $basePath,
        protected readonly FormatterInterface $formatter,
        protected readonly PrinterInterface $printer,
    ) {
    }


    public function setTotalTests(int $total): void
    {
        $this->totalTests = $total;
    }

    public function markTestStarted(string $className, string $method): void
    {
        $this->incrementTestNumber();

        $this->currentTestMethod = $method;

        $this->handleFirstTestForClass($className);

        $this->testClasses[$className]->tests[$method] = new TestInformation(
            $method,
            $this->currentTestNumber,
            TestStatus::RUNNING
        );
    }

    public function markTestFinished(Duration $duration): void
    {
        $this->currentTestInformation()->duration = $duration;

        $this->printer->line(
            $this->formatter->testFinish(
                $this->currentTestInformation()
            )
        );
    }

    public function markRunnerFinished(): void
    {
        $this->printer->finalize();
    }

    public function markTestPassed(): void
    {
        $this->currentTestInformation()->status = TestStatus::PASSED;
    }

    public function markTestErrored(): void
    {
        $this->currentTestInformation()->status = TestStatus::ERRORED;
    }

    public function markTestWarning(): void
    {
        $this->currentTestInformation()->status = TestStatus::WARNING;
    }

    public function markTestFailed(): void
    {
        $this->currentTestInformation()->status = TestStatus::FAILED;
    }

    public function markTestIncomplete(): void
    {
        $this->currentTestInformation()->status = TestStatus::INCOMPLETE;
    }

    /**
     * A test passes or fails first, then it may be considered risky.
     * Only set the status to risky if we passed the test.
     */
    public function markTestRisky(): void
    {
        if ($this->currentTestInformation()->status !== TestStatus::PASSED) {
            return;
        }

        $this->currentTestInformation()->status = TestStatus::RISKY;
    }

    public function markPhpDeprecation(string $file, int $line, string $message): void
    {
        $this->printer->line(
            $this->formatter->deprecation($file, $line, $message)
        );
    }

    public function markUnexpectedOutput(string $output): void
    {
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            $this->printer->line(
                $this->formatter->unexpectedOutput($line)
            );
        }
    }


    protected function handleFirstTestForClass(string $className): void
    {
        if (array_key_exists($className, $this->testClasses)) {
            return;
        }

        $this->currentTestClass = $className;

        $this->testClasses[$className] = new TestClassInformation($className);

        $this->printer->line(
            $this->formatter->testClassStart(
                $this->testClasses[$className],
                $this->currentTestNumber,
                $this->totalTests
            )
        );
    }

    protected function currentTestInformation(): TestInformation
    {
        return $this->testClasses[$this->currentTestClass]->tests[$this->currentTestMethod];
    }

    protected function incrementTestNumber(): void
    {
        $this->currentTestNumber++;
    }
}
