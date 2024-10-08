<?php

namespace JDeniau\BehatReviewdogFormatter;

use FriendsOfBehat\SymfonyExtension\Context\Environment\InitializedSymfonyExtensionEnvironment;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Exception\DriverException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Mockery;

class ReviewdogFormatter implements Formatter
{
    private ?MinkContext $minkContext = null;

    private bool $removeOldFile = true;

    public function __construct(
        private readonly string $pathsBase,
        private readonly ReviewdogOutputPrinter $outputPrinter
    ) {
    }

    public function setParameter($name, $value): void
    {
        switch ($name) {
            case 'file_name':
                if (!is_string($value)) {
                    throw new \RuntimeException('file_name must be a string');
                }

                $this->outputPrinter->setFileName($value);
                break;

            case 'remove_old_file':
                if (!is_bool($value)) {
                    throw new \RuntimeException(
                        'remove_old_file must be a boolean'
                    );
                }

                $this->removeOldFile = $value;
                break;

            default:
                throw new \Exception('Unknown parameter ' . $name);
        }
    }

    public function getParameter($name)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            StepTested::AFTER => 'onAfterStepTested',
            ScenarioTested::BEFORE => 'gatherBaseContexts',
            BeforeExerciseCompleted::BEFORE => 'onBeforeExercise',
        ];
    }

    public function getName(): string
    {
        return 'reviewdog';
    }

    public function getDescription(): string
    {
        return 'Reviewdog formatter';
    }

    public function getOutputPrinter(): OutputPrinter
    {
        return $this->outputPrinter;
    }

    /**
     * @BeforeScenario
     */
    final public function gatherBaseContexts(BeforeScenarioTested $event): void
    {
        $environment = $event->getEnvironment();

        if (
            !$environment instanceof InitializedContextEnvironment &&
            !$environment instanceof InitializedSymfonyExtensionEnvironment
        ) {
            return;
        }

        if (!$environment->hasContextClass(MinkContext::class)) {
            return;
        }

        $this->minkContext = $environment->getContext(MinkContext::class);
    }

    public function onBeforeExercise(BeforeExerciseCompleted $event): void
    {
        if ($this->removeOldFile !== false) {
            $this->outputPrinter->removeOldFile();
        }
    }

    public function onAfterStepTested(AfterStepTested $event): void
    {
        $testResult = $event->getTestResult();

        if (
            $testResult->isPassed() ||
            !$testResult instanceof ExecutedStepResult
        ) {
            return;
        }

        $path = str_replace(
            $this->pathsBase . '/',
            '',
            $event->getFeature()->getFile() ?? ''
        );

        $message = $testResult->getException()?->getMessage() ?? 'Failed step';

        $step = $event->getStep();

        $line = [
            'message' => $message,
            'location' => [
                'path' => $path,
                'range' => [
                    'start' => [
                        'line' => $step->getLine(),
                        'column' => 0,
                    ],
                ],
            ],
            'severity' => 'ERROR',
            'source' => [
                'name' => 'behat',
            ],
        ];

        $originalOutput = $this->getOriginalOutput();

        if ($originalOutput) {
            $line['original_output'] = $originalOutput;
        }

        $json = json_encode($line, \JSON_THROW_ON_ERROR);

        $this->getOutputPrinter()->writeln($json);
    }

    private function getOriginalOutput(): ?string
    {
        if (!$this->minkContext) {
            return null;
        }

        try {
        $originalOutput = $this->minkContext
            ->getSession()
            ->getPage()
            ->getContent();
        } catch (DriverException $e) {
            // See https://github.com/FriendsOfBehat/MinkBrowserKitDriver/blob/4f7d58037f8aa5f3aa17308cb6341b029859ea65/src/BrowserKitDriver.php#L541
            // we don't have an output there, so let's return null
            return null;
        }

        if (!$originalOutput) {
            return null;
        }

        try {
            $json = json_decode(
                $originalOutput,
                true,
                512,
                \JSON_THROW_ON_ERROR
            );

            return json_encode(
                $json,
                \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT
            );
        } catch (\JsonException $e) {
            // split content every 80 chars
            $originalOutput = wordwrap($originalOutput, 80, "\n", true);

            return $originalOutput;
        }
    }
}
