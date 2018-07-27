<?php

namespace mate\Monitor;

/**
 * Collect execution() triggers to improve performance
 * @package mate\Monitor
 */
class TriggerBuffer
{
    /**
     * last execution timestamp
     * @var int
     */
    protected $last;
    /**
     * number of collected executions
     * @var int
     */
    protected $collectedTriggers;
    /**
     * collection time in seconds
     * @var float
     */
    protected $collectTime;

    /**
     * TriggerBuffer constructor.
     * @param float $collectTime
     */
    public function __construct($collectTime = 0.1)
    {
        $this->last = microtime(true) - $collectTime;
        $this->collectedTriggers = 0;
        $this->collectTime = $collectTime;
    }

    /**
     * represents an event trigger
     * returns false and increments the collected triggers if buffer is active
     * returns amount of collected triggers and sets collected=0 if buffer time is exceeded
     * @return false|int
     */
    public function collectTrigger()
    {
        $current = microtime(true);
        if($current - $this->last > $this->collectTime) {
            $collectedTriggers = $this->collectedTriggers + 1;
            $this->collectedTriggers = 0;
            $this->last = $current;
            return $collectedTriggers;
        } else {
            $this->collectedTriggers++;
            return false;
        }
    }

    /**
     * @return int
     */
    public function getCollectedTriggers()
    {
        return $this->collectedTriggers;
    }

    /**
     * @return int
     */
    public function getLast()
    {
        return $this->last;
    }

    /**
     * @return float
     */
    public function getCollectTime()
    {
        return $this->collectTime;
    }

    /**
     * @param float $collectTime
     */
    public function setCollectTime($collectTime)
    {
        $this->collectTime = $collectTime;
    }

}