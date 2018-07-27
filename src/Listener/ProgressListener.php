<?php

namespace mate\Monitor\Listener;

use mate\Monitor\Exception\InvalidArgumentException;
use Zend\EventManager\EventManagerInterface;

/**
 * @package mate\Monitor\Listener
 */
class ProgressListener extends AbstractProgressListener
{

    public function attach(EventManagerInterface $events)
    {
        parent::attach($events);

        $this->listeners[] = $events->attach(ProgressEvent::EVENT_START_APPLICATION, [$this, 'initStartApplication'], 1000);
        $this->listeners[] = $events->attach(ProgressEvent::EVENT_START, [$this, 'initStart'], 1000);
        $this->listeners[] = $events->attach(ProgressEvent::EVENT_EXECUTE, [$this, 'initExecute'], 1000);
        $this->listeners[] = $events->attach(ProgressEvent::EVENT_SKIP_EXECUTION, [$this, 'initSkipExecution'], 1000);
        $this->listeners[] = $events->attach("preMapping", [$this, 'initExecute'], 1000);
        $this->listeners[] = $events->attach(ProgressEvent::EVENT_FINISH_APPLICATION, [$this, 'initFinishApplication'], 1000);
    }

    /**
     * Set starting time of application
     *
     * @param ProgressEvent $event
     */
    public function initStartApplication(ProgressEvent $event)
    {
        $event->setParam(ProgressEvent::PARAM_APP_START, microtime(true));
    }

    /**
     * - Make sure the total count of executions is given
     * - Set starting time of task stack
     * - set counter to zero
     *
     * @param ProgressEvent $event
     */
    public function initStart(ProgressEvent $event)
    {
        if($event->getTotalExecutions() === null) {
            throw new InvalidArgumentException(sprintf(self::EXCEPTION_REQUIRED_PARAMETER, "totalExecutions", __FUNCTION__));
        }
        $event->setStart(microtime(true));
        $event->setDoneCount(0);
        $event->setSkippedCount(0);
    }

    /**
     * Increment counter
     *
     * @param ProgressEvent $event
     */
    public function initExecute(ProgressEvent $event)
    {
        $doneCount = $event->getDoneCount();
        $event->setDoneCount($doneCount + 1);
    }

    /**
     * Decrement total executions
     * Don't increment counter
     *
     * @param ProgressEvent $event
     */
    public function initSkipExecution(ProgressEvent $event)
    {
        $totalExecutions = $event->getTotalExecutions();
        $skippedExecutions = $event->getSkippedCount();

        $event->setTotalExecutions($totalExecutions - 1);
        $event->setSkippedCount($skippedExecutions + 1);
    }

    /**
     * Set starting time of application
     *
     * @param ProgressEvent $event
     */
    public function initFinishApplication(ProgressEvent $event)
    {
        $event->setParam(ProgressEvent::PARAM_APP_END, microtime(true));
    }

}