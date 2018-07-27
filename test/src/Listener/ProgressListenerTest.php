<?php

namespace mateTest\Monitor\Listener;

use mate\Monitor\Exception\InvalidArgumentException;
use mate\Monitor\Listener\AbstractProgressListener;
use mate\Monitor\Listener\ProgressEvent;
use mate\Monitor\Listener\ProgressListener;
use mate\PhpUnit\Mapper\ListenerTest;

/**
 * @package mateTest\Monitor\Listener
 */
class ProgressListenerTest extends ListenerTest
{

    /**
     * @var ProgressListener
     */
    protected $listener;

    protected $attachedMethods = array(
        [ProgressEvent::EVENT_START_APPLICATION, "initStartApplication", 1000],
        [ProgressEvent::EVENT_START, "initStart", 1000],
        [ProgressEvent::EVENT_EXECUTE, "initExecute", 1000],
        ["preMapping", "initExecute", 1000],
        [ProgressEvent::EVENT_SKIP_EXECUTION, "initSkipExecution", 1000],
        [ProgressEvent::EVENT_FINISH_APPLICATION, "initFinishApplication", 1000],
    );

    public function setUp()
    {
        $this->listener = new ProgressListener();
    }

    public function testExtendsAbstractProgressListener()
    {
        $this->assertInstanceOf(AbstractProgressListener::class, $this->listener,
            ProgressListener::class . " does not extend " . AbstractProgressListener::class);
    }

    public function testInitStartApplication()
    {
        $event = new ProgressEvent();
        $microtime = microtime(true);
        $this->listener->initStartApplication($event);
        $appStart = $event->getAppStart();
        $this->assertTrue(
            !empty($appStart) && $appStart >= $microtime && $appStart < ($microtime + 1),
            "initStartApplication does not set the property appStart correctly, " .
            "expected microtime, got " . ($appStart === null ? "null" : $appStart)
        );
    }

    public function testInitStart()
    {
        $event = new ProgressEvent();
        $event->setTotalExecutions(10);
        $microtime = microtime(true);
        $this->listener->initStart($event);
        $this->assertEquals(0, $event->getDoneCount(),
            "initStart() does not initialize the done count");
        $this->assertEquals(0, $event->getSkippedCount(),
            "initStart() does not initialize the skipped count");
        $start = $event->getStart();
        $this->assertTrue(
            !empty($start) && $start >= $microtime && $start < ($microtime + 1),
            "initStart does not set the property start correctly, " .
            "expected current microtime, got " . ($start === null ? "null" : $start)
        );
    }

    public function testInitStartThrowsException()
    {
        $message = sprintf(AbstractProgressListener::EXCEPTION_REQUIRED_PARAMETER, ProgressEvent::PARAM_TOTAL_EXECUTIONS, 'initStart');
        $this->setExpectedException(InvalidArgumentException::class, $message);
        $event = new ProgressEvent();
        $this->listener->initStart($event);
    }

    public function testInitExecute()
    {
        $event = new ProgressEvent();
        $event->setDoneCount(1);
        $this->listener->initExecute($event);
        $this->assertEquals(2, $event->getDoneCount(),
            "initExecute() does not count up the done count");
    }

    public function testInitSkipExecution()
    {
        $event = new ProgressEvent();
        $event->setDoneCount(1);
        $event->setTotalExecutions(3);
        $event->setSkippedCount(3);

        $this->listener->initSkipExecution($event);
        $this->assertEquals(1, $event->getDoneCount(),
            "initSkipExecution() should not increment the done count");
        $this->assertEquals(2, $event->getTotalExecutions(),
            "initSkipExecution() should decrement the total execution count");
        $this->assertEquals(4, $event->getSkippedCount(),
            "initSkipExecution() should increment the skipped count");
    }

    public function testInitFinishApplication()
    {
        $event = new ProgressEvent();
        $microtime = microtime(true);
        $this->listener->initFinishApplication($event);
        $appStart = $event->getAppEnd();
        $this->assertTrue(
            !empty($appStart) && $appStart >= $microtime && $appStart < ($microtime + 1),
            "initFinishApplication does not set the property appEnd correctly, " .
            "expected microtime, got " . ($appStart === null ? "null" : $appStart)
        );
    }

}
