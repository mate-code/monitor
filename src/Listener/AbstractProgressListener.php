<?php

namespace mate\Monitor\Listener;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Mvc\MvcEvent;

/**
 * Is used to follow the progress of an application.
 * Vocabulary:
 * - Application: Covers all executions of an application call
 * - Task: Can be a single task or a task with n executions
 * - Execution: A single execution to be tracked in a task
 *
 * @package mate\Monitor\Listener
 */
abstract class AbstractProgressListener implements ListenerAggregateInterface
{


    const EXCEPTION_REQUIRED_PARAMETER = "Parameter '%s' not found in event '%s'";


    use ListenerAggregateTrait;


    public function attach(EventManagerInterface $events)
    {
        // initialize
        $this->listeners[] = $events->attach(
            ProgressEvent::EVENT_START_APPLICATION,
            array($this, 'startApplication')
            , 100
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_BOOTSTRAP,
            array($this, 'startApplication'),
            100
        );
        $this->listeners[] = $events->attach(
            ProgressEvent::EVENT_START,
            array($this, 'start'),
            100
        );
        $this->listeners[] = $events->attach(
            ProgressEvent::EVENT_EXECUTE,
            array($this, 'execute'),
            100
        );
        $this->listeners[] = $events->attach(
            ProgressEvent::EVENT_SKIP_EXECUTION,
            array($this, 'skipExecution'),
            100
        );
        $this->listeners[] = $events->attach(
            "preMapping",
            array($this, 'execute'),
            100
        );
        $this->listeners[] = $events->attach(
            ProgressEvent::EVENT_FINISH,
            array($this, 'finish'),
            100
        );
        $this->listeners[] = $events->attach(
            ProgressEvent::EVENT_FINISH_APPLICATION,
            array($this, 'finishApplication'),
            100
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_FINISH,
            array($this, 'finishApplication'),
            100
        );
    }

    /**
     * @param null|string $taskName
     * @param null|int $totalExecutions
     * @param array $params
     * @return ProgressEvent
     */
    public static function createEvent($taskName = null, $totalExecutions = null, $params = [])
    {
        $params = array_merge([
            ProgressEvent::PARAM_TASK_NAME        => $taskName,
            ProgressEvent::PARAM_TOTAL_EXECUTIONS => $totalExecutions,
        ], $params);
        return new ProgressEvent(null, null, $params);
    }

    /**
     * @param ProgressEvent $event
     * @return float
     */
    public function getProgressInPercent(ProgressEvent $event)
    {
        $total = $event->getTotalExecutions();
        $done = $event->getDoneCount();
        return $done / $total * 100;
    }

    /**
     * trigger at the very beginning of the application
     * Available properties:
     * - appStart
     *
     * @param ProgressEvent $event
     */
    public function startApplication(ProgressEvent $event)
    {
    }

    /**
     * trigger before executing a task
     * Available properties:
     * - appStart
     * - totalExecutions
     *
     * @param ProgressEvent $event
     */
    public function start(ProgressEvent $event)
    {
    }

    /**
     * trigger for a single execution
     * Available properties:
     * - appStart
     * - totalExecutions
     * - doneCount
     * - start
     *
     * @param ProgressEvent $event
     */
    public function execute(ProgressEvent $event)
    {
    }

    /**
     * trigger if an execution is skipped
     * Available properties:
     * - appStart
     * - totalExecutions
     * - doneCount
     * - start
     *
     * @param ProgressEvent $event
     */
    public function skipExecution(ProgressEvent $event)
    {
    }

    /**
     * trigger after finishing a task
     * Available properties:
     * - appStart
     * - totalExecutions
     * - doneCount
     * - start
     *
     * @param ProgressEvent $event
     */
    public function finish(ProgressEvent $event)
    {
    }

    /**
     * trigger at the very end of the application
     * Available properties:
     * - appStart
     * - totalExecutions
     *
     * @param ProgressEvent $event
     */
    public function finishApplication(ProgressEvent $event)
    {
    }


}