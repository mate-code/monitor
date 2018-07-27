<?php


namespace mateTest\Monitor\Listener;

use mate\Monitor\Listener\AbstractProgressListener;
use mate\Monitor\Listener\ConsoleProgressBarListener;
use mate\Monitor\Listener\ProgressEvent;


/**
 * @package mateTest\Monitor\Listener
 */
class ConsoleProgressBarListenerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ConsoleProgressBarListener
     */
    protected $listener;

    public function setUp()
    {
        $this->listener = new ConsoleProgressBarListener();
    }

    public function testExtendsAbstractProgressListener()
    {
        $this->assertInstanceOf(AbstractProgressListener::class, $this->listener,
            ConsoleProgressBarListener::class . " does not extend " . AbstractProgressListener::class);
    }

    public function testStartApplication()
    {
        $startMessage = sprintf(ConsoleProgressBarListener::HIGHLIGHT_NEUTRAL, "Starting the application") . "\n\n";
        $this->expectOutputString($startMessage);

        $event = new ProgressEvent();
        $this->listener->startApplication($event);
    }

    public function testStart()
    {
        $event = new ProgressEvent();
        $event->setTaskName("test task");
        $event->setTotalExecutions(100);

        $this->expectOutputString("Start test task with 100 executions\n");

        $this->listener->start($event);
    }

    /**
     * @depends testStart
     */
    public function testStartUsesDefaultTaskName()
    {
        $event = new ProgressEvent();
        $event->setTotalExecutions(100);

        $this->expectOutputString("Start task with 100 executions\n");
        $this->listener->start($event);
    }

    public function testExecute()
    {
        /** @var ConsoleProgressBarListener|\PHPUnit_Framework_MockObject_MockObject $listener */
        $listener = $this->getMockBuilder(ConsoleProgressBarListener::class)
            ->setMethods(["showProgress"])
            ->getMockForAbstractClass();

        $event = new ProgressEvent();
        $event->setDoneCount(10);
        $event->setTotalExecutions(100);

        $listener->expects($this->once())
            ->method("showProgress")
            ->with($event);

        $listener->execute($event);

        $this->assertEquals(time(), $event->getParam(ConsoleProgressBarListener::PARAM_LAST_TRIGGER),
            "execute() does not set the param " . ConsoleProgressBarListener::PARAM_LAST_TRIGGER);
    }

    /**
     * @depends testExecute
     */
    public function testExecuteRefreshEverySecond()
    {
        /** @var ConsoleProgressBarListener|\PHPUnit_Framework_MockObject_MockObject $listener */
        $listener = $this->getMockBuilder(ConsoleProgressBarListener::class)
            ->setMethods(["showProgress"])
            ->getMockForAbstractClass();

        $event = new ProgressEvent();
        $event->setDoneCount(10);
        $event->setTotalExecutions(100);
        $event->setParam(ConsoleProgressBarListener::PARAM_LAST_TRIGGER, time());

        $listener->expects($this->never())
            ->method("showProgress");

        $listener->execute($event);
    }

    public function testFinish()
    {
        $event = new ProgressEvent();
        $event->setDoneCount(100);
        $event->setTotalExecutions(100);

        $finishMessage = "Total execution time: 00:01:40\n\n";
        $this->expectOutputString($finishMessage);
        $event->setStart(time() - 100);
        $this->listener->finish($event);
    }

    public function testFinishWithSkipped()
    {
        $event = new ProgressEvent();
        $event->setDoneCount(100);
        $event->setSkippedCount(50);
        $event->setTotalExecutions(100);

        $finishMessage = "Total execution time: 00:01:40\n";
        $finishMessage .= "Skipped 50/150 executions\n\n";
        $this->expectOutputString($finishMessage);

        $event->setStart(time() - 100);

        $this->listener->finish($event);
    }

    public function testFinishApplication()
    {
        $line1 = sprintf(ConsoleProgressBarListener::HIGHLIGHT_SUCCESS, "All tasks finished") . "\n";
        $line2 = sprintf(ConsoleProgressBarListener::HIGHLIGHT_SUCCESS, "Total execution time: 00:01:40") . "\n";

        $this->expectOutputString($line1 . $line2);

        $event = new ProgressEvent();
        $event->setAppStart(time() - 100);

        $this->listener->finishApplication($event);
    }

}
