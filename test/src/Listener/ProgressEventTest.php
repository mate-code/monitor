<?php

namespace mateTest\Monitor\Listener;

use mate\Monitor\Listener\ProgressEvent;

/**
 * @package mateTest\Monitor\Listener
 */
class ProgressEventTest extends \PHPUnit_Framework_TestCase
{

    public function provideTestSetAndGet()
    {
        return array(
            [ProgressEvent::PARAM_PROCESS_NAME, "task-name"],
            [ProgressEvent::PARAM_PROCESS_DESCRIPTION, "test task description"],
            [ProgressEvent::PARAM_TASK_NAME, "task name"],
            [ProgressEvent::PARAM_APP_START, 132445.12342],
            [ProgressEvent::PARAM_START, 136545.1234563],
            [ProgressEvent::PARAM_TOTAL_EXECUTIONS, 32],
            [ProgressEvent::PARAM_DONE_COUNT, 13],
            [ProgressEvent::PARAM_SKIPPED_COUNT, 11],
            [ProgressEvent::PARAM_APP_END, 132445.23351],
            [ProgressEvent::PARAM_APP_RESPONSE, '{"status": 200}'],
        );
    }

    /**
     * @dataProvider provideTestSetAndGet
     *
     * @param string $param
     * @param mixed $value
     */
    public function testSetParam($param, $value)
    {
        $event = new ProgressEvent();
        $setter = "set" . ucfirst($param);
        if(method_exists($event, $setter)) {
            $event->$setter($value);
        }
        $this->assertEquals($value, $event->getParam($param),
            "Method $setter does not set a value to the event param $param or does not exist");
    }

    /**
     * @dataProvider provideTestSetAndGet
     *
     * @param string $param
     * @param mixed $value
     */
    public function testGetParam($param, $value)
    {
        $event = new ProgressEvent();
        $getter = "get" . ucfirst($param);
        $event->setParam($param, $value);
        if(method_exists($event, $getter)) {
            $return = $event->$getter();
        } else {
            $return = null;
        }
        $this->assertEquals($value, $return,
            "Method $getter does not return the value set to event param $param or does not exist");
    }

}
