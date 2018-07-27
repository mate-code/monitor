<?php

namespace mateTest\Monitor;

use mate\Monitor\Exception\InvalidArgumentException;
use mate\Monitor\Listener\ProgressEvent;
use mate\Monitor\ProgressManager;
use mate\PhpUnit\TestWithMockTrait;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;

/**
 * @package mateTest\Monitor
 */
class ProgressManagerTest extends \PHPUnit_Framework_TestCase
{
    use TestWithMockTrait;

    /**
     * @var ProgressManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $progressManager;

    /**
     * @var ProgressEvent
     */
    protected $progressEvent;

    public function setUp()
    {
        $this->setTestClass(ProgressManager::class);
        $this->setConstructorArgs(array());
        $this->progressManager = $this->createInstanceToTest(array(
            "trigger",
            "verifyTriggerOrder"
        ));

        $this->progressEvent = new ProgressEvent();
        $this->progressEvent->setParam("unique", true);
        $this->progressManager->setProgressEvent($this->progressEvent);
    }

    public function testProgressManagerExtendsEventManager()
    {
        $this->assertInstanceOf(EventManager::class, $this->progressManager,
            ProgressManager::class . " does not extend " . EventManager::class);
    }

    public function testProgressManagerCreatesProgressEvent()
    {
        $event = new ProgressEvent();
        $progressManager = new ProgressManager();
        $this->assertEquals($event, $progressManager->getProgressEvent(),
            "constructor does not create a default progress event");
    }

    // OVERWRITE TRIGGER

    public function provideTestTrigger()
    {
        return array(
            array(
                [
                    "event"  => new Event("testEvent", null, [
                        "test" => "value"
                    ]),
                    "target" => function () {
                    }
                ],
                [
                    "event"    => "testEvent",
                    "e"        => new ProgressEvent("testEvent", null, [
                        "test"   => "value",
                        "unique" => true
                    ]),
                    "callback" => function () {
                    }
                ]
            ),
            array(
                [
                    "event"  => "testEvent",
                    "target" => new Event(null, null, [
                        "test" => "value"
                    ]),
                    "argv"   => function () {
                    }
                ],
                [
                    "event"    => "testEvent",
                    "e"        => new ProgressEvent("testEvent", null, [
                        "test"   => "value",
                        "unique" => true
                    ]),
                    "callback" => function () {
                    }
                ]
            ),
            array(
                [
                    "event"    => "testEvent",
                    "target"   => __CLASS__,
                    "argv"     => new Event(null, null, [
                        "test" => "value"
                    ]),
                    "callback" => function () {
                    }
                ],
                [
                    "event"    => "testEvent",
                    "e"        => new ProgressEvent("testEvent", __CLASS__, [
                        "test"   => "value",
                        "unique" => true
                    ]),
                    "callback" => function () {
                    }
                ]
            ),
            array(
                [
                    "event"    => "testEvent",
                    "target"   => __CLASS__,
                    "argv"     => [
                        "test" => "value"
                    ],
                    "callback" => function () {
                    }
                ],
                [
                    "event"    => "testEvent",
                    "e"        => new ProgressEvent("testEvent", __CLASS__, [
                        "test"   => "value",
                        "unique" => true
                    ]),
                    "callback" => function () {
                    }
                ]
            ),
        );
    }

    /**
     * @dataProvider provideTestTrigger
     *
     * @param array $triggerParams
     * @param array $triggerListenersParams
     */
    public function testTrigger(array $triggerParams, array $triggerListenersParams)
    {
        $event = $triggerParams["event"];
        $target = isset($triggerParams["target"]) ? $triggerParams["target"] : null;
        $argv = isset($triggerParams["argv"]) ? $triggerParams["argv"] : [];
        $callback = isset($triggerParams["callback"]) ? $triggerParams["callback"] : null;

        $listenerEvent = $triggerListenersParams["event"];
        $listenerE = $triggerListenersParams["e"];
        $listenerCallback = isset($triggerListenersParams["callback"]) ? $triggerListenersParams["callback"] : null;

        /** @var ProgressManager|\PHPUnit_Framework_MockObject_MockObject $progressManager */
        $progressManager = $this->getMockBuilder(ProgressManager::class)
            ->setMethods(["triggerListeners"])
            ->getMock();
        $progressManager->expects($this->once())
            ->method("triggerListeners")
            ->with($listenerEvent, $listenerE, $listenerCallback);

        $progressManager->setProgressEvent($this->progressEvent);
        $progressManager->trigger($event, $target, $argv, $callback);
    }

    // EVENT TRIGGERS

    public function testApplicationStarts()
    {
        $this->progressManager->expects($this->once())
            ->method("trigger")
            ->with(
                ProgressEvent::EVENT_START_APPLICATION
            );

        $this->progressManager->applicationStarts();
    }

    public function testTaskStarts()
    {
        $taskName = "test task";
        $totalExecutions = 100;

        $this->progressEvent->setTaskName($taskName);
        $this->progressEvent->setTotalExecutions($totalExecutions);

        $this->progressManager->expects($this->once())
            ->method("trigger")
            ->with(
                ProgressEvent::EVENT_START
            );

        $this->progressManager->taskStarts($totalExecutions, $taskName);
    }

    public function testTaskStartsThrowsException()
    {
        $message = sprintf(ProgressManager::EXCEPTION_INVALID_PARAMETER, "totalExecutions", "positive int", "string");
        $this->setExpectedException(InvalidArgumentException::class, $message);
        $this->progressManager->applicationStarts();
        $this->progressManager->taskStarts("test task", 100);
    }

    public function testExecution()
    {
        $this->progressManager->expects($this->once())
            ->method("trigger")
            ->with(
                ProgressEvent::EVENT_EXECUTE
            );

        $this->progressManager->execution();
    }

    /**
     * @depends testExecution
     */
    public function testExecutionIsBuffered()
    {
        $this->progressManager->expects($this->once())
            ->method("trigger")
            ->with(ProgressEvent::EVENT_EXECUTE);

        $this->progressManager->execution();
        $this->progressManager->execution();
        $this->progressManager->execution();
    }

    public function testExecutions()
    {
        $executions = 50;
        $doneCount = 10;
        $this->progressEvent->setDoneCount($doneCount);
        $expectedDoneCount = $doneCount + $executions - 1;

        $this->progressManager->expects($this->once())
            ->method("trigger")
            ->with(
                ProgressEvent::EVENT_EXECUTE
            );

        $this->progressManager->executions($executions);

        $this->assertEquals($expectedDoneCount, $this->progressEvent->getDoneCount(),
            "executions() does not count up the done count");
    }

    public function testExecutionIsSkipped()
    {
        $this->progressManager->expects($this->once())
            ->method("trigger")
            ->with(
                ProgressEvent::EVENT_SKIP_EXECUTION
            );

        $this->progressManager->executionIsSkipped();
    }

    public function testTaskIsFinished()
    {
        $this->progressManager = $this->createInstanceToTest(array(
            "trigger",
            "verifyTriggerOrder",
            "executions"
        ));
        $this->progressManager->setProgressEvent($this->progressEvent);

        $this->progressManager->expects($this->once())
            ->method("executions")
            ->with(0);

        $this->progressManager->expects($this->once())
            ->method("trigger")
            ->with(
                ProgressEvent::EVENT_FINISH
            );

        $this->progressManager->taskIsFinished();
    }

    public function testApplicationIsFinished()
    {
        $this->progressManager->expects($this->once())
            ->method("trigger")
            ->with(
                ProgressEvent::EVENT_FINISH_APPLICATION
            );

        $responseString = '{"status": 200}';
        $this->progressManager->applicationIsFinished($responseString);

        $this->assertEquals($responseString, $this->progressEvent->getAppResponse(),
            "applicationIsFinished() does not set the apps response");
    }

}
