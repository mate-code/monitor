<?php

namespace mate\Monitor\Listener;

use Zend\EventManager\Event;

/**
 * @package mateTest\Monitor\Listener
 */
class ProgressEvent extends Event
{

    // PARAMETERS

    /** unique name of the process */
    const PARAM_PROCESS_NAME = "processName";

    /** human readable description */
    const PARAM_PROCESS_DESCRIPTION = "processDescription";

    /** display or property name of the current set of tasks */
    const PARAM_TASK_NAME = "taskName";

    /** microtime when the application has started */
    const PARAM_APP_START = "appStart";

    /** microtime when the task has started */
    const PARAM_START = "start";

    /** microtime when the application has ended */
    const PARAM_APP_END = "appEnd";

    /** response string of application */
    const PARAM_APP_RESPONSE = "appResponse";

    /** expected total executions */
    const PARAM_TOTAL_EXECUTIONS = "totalExecutions";

    /** count of how many tasks out of the current set have been finished */
    const PARAM_DONE_COUNT = "doneCount";

    /** count of how many tasks out of the current set have been skipped */
    const PARAM_SKIPPED_COUNT = "skippedCount";

    // EVENTS

    /**
     * trigger at the very beginning of the application
     * Available properties:
     * - appStart
     */
    const EVENT_START_APPLICATION = "progress_startApplication";

    /**
     * trigger before executing a task
     * Available properties:
     * - appStart
     * - totalExecutions
     */
    const EVENT_START = "progress_start";

    /**
     * trigger for a single execution
     * Available properties:
     * - appStart
     * - totalExecutions
     * - doneCount
     * - start
     */
    const EVENT_EXECUTE = "progress_execute";

    /**
     * trigger if an execution is skipped
     * improves accuracy of remaining time
     * Available properties:
     * - appStart
     * - totalExecutions
     * - doneCount
     * - start
     */
    const EVENT_SKIP_EXECUTION = "progress_skip_execution";

    /**
     * trigger after finishing a task
     * Available properties:
     * - appStart
     * - totalExecutions
     * - doneCount
     * - start
     */
    const EVENT_FINISH = "progress_finish";

    /**
     * trigger at the very end of the application
     * Available properties:
     * - appStart
     * - totalExecutions
     */
    const EVENT_FINISH_APPLICATION = "progress_finishApplication";

    /**
     * @return string
     */
    public function getProcessName()
    {
        return $this->getParam(self::PARAM_PROCESS_NAME);
    }

    /**
     * @param string $processName
     */
    public function setProcessName($processName)
    {
        $this->setParam(self::PARAM_PROCESS_NAME, $processName);
    }

    /**
     * @return string
     */
    public function getProcessDescription()
    {
        return $this->getParam(self::PARAM_PROCESS_DESCRIPTION);
    }

    /**
     * @param string $processDescription
     */
    public function setProcessDescription($processDescription)
    {
        $this->setParam(self::PARAM_PROCESS_DESCRIPTION, $processDescription);
    }

    /**
     * @return string
     */
    public function getTaskName()
    {
        return $this->getParam(self::PARAM_TASK_NAME);
    }

    /**
     * @param string $taskName
     */
    public function setTaskName($taskName)
    {
        $this->setParam(self::PARAM_TASK_NAME, $taskName);
    }

    /**
     * @return float
     */
    public function getAppStart()
    {
        return $this->getParam(self::PARAM_APP_START);
    }

    /**
     * @param float $appStart
     */
    public function setAppStart($appStart)
    {
        $this->setParam(self::PARAM_APP_START, $appStart);
    }

    /**
     * @return float
     */
    public function getStart()
    {
        return $this->getParam(self::PARAM_START);
    }

    /**
     * @param float $start
     */
    public function setStart($start)
    {
        $this->setParam(self::PARAM_START, $start);
    }

    /**
     * @return float
     */
    public function getAppEnd()
    {
        return $this->getParam(self::PARAM_APP_END);
    }

    /**
     * @param float $appEnd
     */
    public function setAppEnd($appEnd)
    {
        $this->setParam(self::PARAM_APP_END, $appEnd);
    }

    /**
     * @return string
     */
    public function getAppResponse()
    {
        return $this->getParam(self::PARAM_APP_RESPONSE);
    }

    /**
     * @param string $response
     */
    public function setAppResponse($response)
    {
        if(is_scalar($response) || (is_object($response) && method_exists($response, '__toString'))) {
            $this->setParam(self::PARAM_APP_RESPONSE, (string) $response);
        }
    }

    /**
     * @return int
     */
    public function getTotalExecutions()
    {
        return $this->getParam(self::PARAM_TOTAL_EXECUTIONS);
    }

    /**
     * @param int $totalExecutions
     */
    public function setTotalExecutions($totalExecutions)
    {
        $this->setParam(self::PARAM_TOTAL_EXECUTIONS, $totalExecutions);
    }

    /**
     * @return int
     */
    public function getDoneCount()
    {
        return $this->getParam(self::PARAM_DONE_COUNT);
    }

    /**
     * @param int $done
     */
    public function setDoneCount($done)
    {
        $this->setParam(self::PARAM_DONE_COUNT, $done);
    }

    /**
     * @return int
     */
    public function getSkippedCount()
    {
        return $this->getParam(self::PARAM_SKIPPED_COUNT);
    }

    /**
     * @param int $skipped
     */
    public function setSkippedCount($skipped)
    {
        $this->setParam(self::PARAM_SKIPPED_COUNT, $skipped);
    }

}