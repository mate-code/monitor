<?php

namespace mateTest\Monitor\Listener;

use mate\Monitor\Listener\AbstractProgressListener;
use mate\Monitor\Listener\ProgressEvent;
use mate\PhpUnit\Mapper\ListenerTest;
use Zend\Mvc\MvcEvent;

/**
 * @package mateTest\Monitor\Listener
 */
class AbstractProgressListenerTest extends ListenerTest
{
    /**
     * @var AbstractProgressListener|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $listener;

    protected $attachedMethods = array(
        [ProgressEvent::EVENT_START_APPLICATION, "startApplication", 100],
        [MvcEvent::EVENT_BOOTSTRAP, "startApplication", 100],
        [ProgressEvent::EVENT_START, "start", 100],
        [ProgressEvent::EVENT_EXECUTE, "execute", 100],
        [ProgressEvent::EVENT_SKIP_EXECUTION, "skipExecution", 100],
        ["preMapping", "execute", 100],
        [ProgressEvent::EVENT_FINISH, "finish", 100],
        [ProgressEvent::EVENT_FINISH_APPLICATION, "finishApplication", 100],
        [MvcEvent::EVENT_FINISH, "finishApplication", 100],
    );

    public function setUp()
    {
        $this->listener = $this->getMockBuilder(AbstractProgressListener::class)
            ->getMockForAbstractClass();
    }

    public function testGetProgressInPercent()
    {
        $event = new ProgressEvent();
        $event->setDoneCount(20);
        $event->setTotalExecutions(80);

        $expected = 25;
        $this->assertEquals($expected, $this->listener->getProgressInPercent($event),
            "getProgressInPercent does not return the correct percentage");
    }

    public function testCreateEvent()
    {
        $expectedEvent = new ProgressEvent();
        $expectedEvent->setTaskName("test task");
        $expectedEvent->setTotalExecutions(100);
        $expectedEvent->setParam("additional", "value");

        $actualEvent = AbstractProgressListener::createEvent("test task", 100, [
            "additional" => "value",
        ]);
        $this->assertEquals($expectedEvent, $actualEvent,
            "createEvent() does not return the correct event");
    }

}
