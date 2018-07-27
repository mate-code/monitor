<?php

namespace mate\Monitor;

use mate\Monitor\Exception\InvalidArgumentException;
use mate\Monitor\Listener\ProgressEvent;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\Exception\InvalidCallbackException;
use Zend\EventManager\ResponseCollection;

/**
 * @package mate\Monitor
 */
class ProgressManager extends EventManager
{

    const EXCEPTION_INVALID_PARAMETER = "Parameter %s was expected to be %s, %s given";

    const EXCEPTION_INVALID_TRIGGER_ORDER = "Method %s is not allowed to be called after %s";

    const TRIGGER_ORDER = array(
        "taskStarts"            => ["applicationStarts" => true, "taskIsFinished" => true],
        "execution"             => ["taskStarts" => true, "execution" => true, "executionIsSkipped" => true],
        "executionIsSkipped"    => ["taskStarts" => true, "execution" => true, "executionIsSkipped" => true],
        "taskIsFinished"        => ["taskStarts" => true, "execution" => true, "executionIsSkipped" => true],
        "applicationIsFinished" => ["applicationStarts" => true, "taskIsFinished" => true],
    );

    /**
     * @var ProgressEvent
     */
    protected $progressEvent;

    /**
     * @var string
     */
    protected $lastMethod;

    /**
     * @var TriggerBuffer
     */
    protected $executeBuffer;

    /**
     * {@inheritdoc}
     */
    public function __construct($identifiers = null)
    {
        parent::__construct($identifiers);

        $this->executeBuffer = new TriggerBuffer();
        $this->setProgressEvent(new ProgressEvent());
    }

    /**
     * Trigger all listeners for a given event
     *
     * @param  string|EventInterface $event
     * @param  string|object $target Object calling emit, or symbol describing target (such as static method name)
     * @param  array|\ArrayAccess $argv Array of arguments; typically, should be associative
     * @param  null|callable $callback Trigger listeners until return value of this callback evaluate to true
     * @return ResponseCollection All listener return values
     * @throws InvalidCallbackException
     */
    public function trigger($event, $target = null, $argv = [], $callback = null)
    {
        if($event instanceof EventInterface) {
            $e = $this->mergeEvent($event);
            $event = $e->getName();
            $callback = $target;
        } elseif($target instanceof EventInterface) {
            $e = $this->mergeEvent($target);
            $e->setName($event);
            $callback = $argv;
        } elseif($argv instanceof EventInterface) {
            $e = $this->mergeEvent($argv);
            $e->setName($event);
            $e->setTarget($target);
        } else {
            $e = new ProgressEvent();
            $e->setName($event);
            $e->setTarget($target);
            $e->setParams($argv);
            $e = $this->mergeEvent($e);
        }

        if($callback && !is_callable($callback)) {
            throw new InvalidCallbackException('Invalid callback provided');
        }

        // Initial value of stop propagation flag should be false
        $e->stopPropagation(false);

        return $this->triggerListeners($event, $e, $callback);
    }

    /**
     * @param EventInterface $event
     * @return ProgressEvent
     */
    protected function mergeEvent(EventInterface $event)
    {
        $progressEvent = $this->getProgressEvent();

        $name = $event->getName();
        if($name) {
            $progressEvent->setName($name);
        }

        $target = $event->getTarget();
        if($target) {
            $progressEvent->setTarget($target);
        }

        $params = $event->getParams();
        foreach ($params as $name => $param) {
            $progressEvent->setParam($name, $param);
        }
        return $progressEvent;
    }

    /**
     * call at the start of the application
     */
    public function applicationStarts()
    {
        $this->trigger(ProgressEvent::EVENT_START_APPLICATION);
    }

    /**
     * call before starting a task
     *
     * @param int $totalExecutions
     * @param null|string $taskName
     */
    public function taskStarts($totalExecutions, $taskName = null)
    {
        if(!is_int($totalExecutions) || $totalExecutions < 0) {
            throw new InvalidArgumentException(sprintf(self::EXCEPTION_INVALID_PARAMETER, "totalExecutions", "positive int", gettype($totalExecutions)));
        }
        $progressEvent = $this->getProgressEvent();
        $progressEvent->setTaskName($taskName);
        $progressEvent->setTotalExecutions($totalExecutions);

        $this->trigger(
            ProgressEvent::EVENT_START,
            $progressEvent
        );
    }

    /**
     * call before every execution of a task
     */
    public function execution()
    {
        $triggers = $this->executeBuffer->collectTrigger();
        $this->executions($triggers);
    }

    /**
     * call if multiple executions have been processed at once
     * @param int $executions
     */
    public function executions($executions)
    {
        if($executions > 0) {
            $progressEvent = $this->getProgressEvent();
            $doneCount = $progressEvent->getDoneCount();
            $newDoneCount = $doneCount + $executions - 1;
            $progressEvent->setDoneCount($newDoneCount);

            $this->trigger(ProgressEvent::EVENT_EXECUTE);
        }
    }

    /**
     * call before every execution of a task
     */
    public function executionIsSkipped()
    {
        $this->trigger(ProgressEvent::EVENT_SKIP_EXECUTION);
    }

    /**
     * call after all executions of a task are done
     */
    public function taskIsFinished()
    {
        $restCollected = $this->executeBuffer->getCollectedTriggers();
        $this->executions($restCollected);
        $this->trigger(ProgressEvent::EVENT_FINISH);
    }

    /**
     * call at the end of the application
     * @param mixed $response
     */
    public function applicationIsFinished($response = null)
    {
        $this->getProgressEvent()->setAppResponse($response);
        $this->trigger(ProgressEvent::EVENT_FINISH_APPLICATION);
    }

    /**
     * @return ProgressEvent
     */
    public function getProgressEvent()
    {
        return $this->progressEvent;
    }

    /**
     * @param ProgressEvent $progressEvent
     */
    public function setProgressEvent($progressEvent)
    {
        $this->progressEvent = $progressEvent;
    }

    /**
     * @return string
     */
    public function getLastMethod()
    {
        return $this->lastMethod;
    }

}