<?php

declare(strict_types=1);

namespace JDeniau\BehatReviewdogFormatter\Tests;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Session;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use JDeniau\BehatReviewdogFormatter\ReviewdogFormatter;
use JDeniau\BehatReviewdogFormatter\ReviewdogOutputPrinter;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @covers \JDeniau\BehatReviewdogFormatter\ReviewdogFormatter
 */
class ReviewdogFormatterTest extends MockeryTestCase
{
    public function testOnBeforeExcercise(): void
    {
        // $outputPrinter = Mockery::mock(ReviewdogOutputPrinter::class);

        $outputPrinter = Mockery::mock(ReviewdogOutputPrinter::class);
        $outputPrinter
            ->expects()
            ->removeOldFile()
            ->once();

        $formatter = new ReviewdogFormatter('/tmp', $outputPrinter);

        $formatter->onBeforeExercise(
            Mockery::mock('overload:' . BeforeExerciseCompleted::class)
        );
    }

    public function testOnAfterStepTestedSkipOutput(): void
    {
        $outputPrinter = Mockery::mock(ReviewdogOutputPrinter::class);
        $formatter = new ReviewdogFormatter('/tmp', $outputPrinter);

        $outputPrinter
            ->expects()
            ->write()
            ->never();

        // step is failing (should output), but the step result is not "Executed"
        $stepResult = Mockery::mock(StepResult::class);
        $stepResult
            ->expects()
            ->isPassed()
            ->once()
            ->andReturn(false);

        $afterStepEvent = Mockery::mock('overload:' . AfterStepTested::class);
        $afterStepEvent
            ->expects()
            ->getTestResult()
            ->once()
            ->andReturn($stepResult);

        $formatter->onAfterStepTested($afterStepEvent);

        // step is "Executed" but is passing (and thus should not output)
        $stepResult = Mockery::mock('overload:' . ExecutedStepResult::class);
        $stepResult
            ->expects()
            ->isPassed()
            ->andReturn(true);

        $afterStepEvent = Mockery::mock('overload:' . AfterStepTested::class);
        $afterStepEvent
            ->expects()
            ->getTestResult()
            ->once()
            ->andReturn($stepResult);

        $formatter->onAfterStepTested($afterStepEvent);
    }

    public function testOnAfterFailingStep(): void
    {
        $outputPrinter = Mockery::mock(ReviewdogOutputPrinter::class);
        $formatter = new ReviewdogFormatter('/tmp', $outputPrinter);

        $outputPrinter
            ->expects()
            ->writeln()
            ->once()
            ->with(
                '{"message":"some exception","location":{"path":"feature.feature","range":{"start":{"line":20,"column":0}}},"severity":"ERROR","source":{"name":"behat"}}'
            );

        $step = Mockery::mock(StepNode::class);
        $step
            ->expects()
            ->getLine()
            ->once()
            ->andReturn(20);

        $stepResult = Mockery::mock('overload:' . ExecutedStepResult::class);
        $stepResult
            ->expects()
            ->isPassed()
            ->andReturn(false);
        $stepResult
            ->expects()
            ->getException()
            ->andReturn(new \Exception('some exception'));

        $afterStepEvent = Mockery::mock('overload:' . AfterStepTested::class);
        $afterStepEvent
            ->expects()
            ->getTestResult()
            ->once()
            ->andReturn($stepResult);
        $afterStepEvent
            ->expects()
            ->getStep()
            ->andReturn($step);

        $featureNode = Mockery::mock(FeatureNode::class);
        $featureNode
            ->expects()
            ->getFile()
            ->once()
            ->andReturn('/tmp/feature.feature');
        $afterStepEvent
            ->expects()
            ->getFeature()
            ->once()
            ->andReturn($featureNode);

        $formatter->onAfterStepTested($afterStepEvent);
    }

    public function testOnAfterFailingStepWithMink(): void
    {
        $outputPrinter = Mockery::mock(ReviewdogOutputPrinter::class);
        $formatter = new ReviewdogFormatter('/tmp', $outputPrinter);

        $content = 'mink page content !';
        $page = Mockery::mock(DocumentElement::class);
        $page
            ->expects()
            ->getContent()
            ->once()
            ->andReturn($content);

        $session = Mockery::mock(Session::class);
        $session
            ->expects()
            ->getPage()
            ->once()
            ->andReturn($page);

        $minkContext = Mockery::mock(MinkContext::class);
        $minkContext
            ->expects()
            ->getSession()
            ->once()
            ->andReturn($session);

        $env = Mockery::mock(
            'overload:' . InitializedContextEnvironment::class
        );
        $env->expects()
            ->hasContextClass()
            ->once()
            ->with(MinkContext::class)
            ->andReturn(true);
        $env->expects()
            ->getContext()
            ->once()
            ->with(MinkContext::class)
            ->andReturn($minkContext);

        $beforeScenarioTestedEvent = Mockery::mock(
            'overload:' . BeforeScenarioTested::class
        );
        $beforeScenarioTestedEvent
            ->expects()
            ->getEnvironment()
            ->once()
            ->andReturn($env);
        $formatter->gatherBaseContexts($beforeScenarioTestedEvent);

        $outputPrinter
            ->expects()
            ->writeln()
            ->once()
            ->with(
                '{"message":"some exception","location":{"path":"feature.feature","range":{"start":{"line":20,"column":0}}},"severity":"ERROR","source":{"name":"behat"},"original_output":"mink page content !"}'
            );

        $step = Mockery::mock(StepNode::class);
        $step
            ->expects()
            ->getLine()
            ->once()
            ->andReturn(20);

        $stepResult = Mockery::mock('overload:' . ExecutedStepResult::class);
        $stepResult
            ->expects()
            ->isPassed()
            ->andReturn(false);
        $stepResult
            ->expects()
            ->getException()
            ->andReturn(new \Exception('some exception'));

        $afterStepEvent = Mockery::mock('overload:' . AfterStepTested::class);
        $afterStepEvent
            ->expects()
            ->getTestResult()
            ->once()
            ->andReturn($stepResult);
        $afterStepEvent
            ->expects()
            ->getStep()
            ->andReturn($step);

        $featureNode = Mockery::mock(FeatureNode::class);
        $featureNode
            ->expects()
            ->getFile()
            ->once()
            ->andReturn('/tmp/feature.feature');
        $afterStepEvent
            ->expects()
            ->getFeature()
            ->once()
            ->andReturn($featureNode);

        $formatter->onAfterStepTested($afterStepEvent);
    }
}
