<?php

namespace mate\Monitor\Listener;

use mate\Monitor\Exception\InvalidArgumentException;
use Zend\Cache\Storage\Adapter\Redis;

/**
 * @package mate\Monitor\Listener
 */
class RedisProgressListener extends AbstractProgressListener
{

    const EXCEPTION_INVALID_ADAPTER = "Invalid redis adapter - expected options array or instance of" . Redis::class;

    const CACHE_KEY_RUNNING_PROCESSES = "running-processes";

    const REDIS_DEFAULT_OPTIONS = array(
        'server' => array(
            'host' => '127.0.0.1',
            'port' => 6379,
        )
    );

    /**
     * @var Redis
     */
    protected $redisAdapter;

    /**
     * @var string
     */
    protected $processName;

    /**
     * @var int
     */
    protected $lifetimeAfterAppEnd;

    /**
     * Will set the given redis adapter or create it by given or default options array
     *
     * @param null|array|Redis $redisAdapter
     * @param int $lifetimeAfterAppEnd
     * @throws InvalidArgumentException
     */
    public function __construct($redisAdapter = null, $lifetimeAfterAppEnd = 3)
    {
        if($redisAdapter === null) {
            $redisAdapter = self::REDIS_DEFAULT_OPTIONS;
        }

        if(is_array($redisAdapter)) {
            $redisAdapter = new Redis($redisAdapter);
        }

        if(!$redisAdapter instanceof Redis) {
            throw new InvalidArgumentException(self::EXCEPTION_INVALID_ADAPTER);
        }

        $this->setRedisAdapter($redisAdapter);
        $this->setLifetimeAfterAppEnd($lifetimeAfterAppEnd);
    }

    /**
     * will add the currents process cache key to the list of running processes
     *
     * @param ProgressEvent $event
     */
    public function startApplication(ProgressEvent $event)
    {
        $adapter = $this->getRedisAdapter();
        $runningProcesses = $adapter->getItem(self::CACHE_KEY_RUNNING_PROCESSES);
        $runningProcesses = unserialize($runningProcesses);

        $processName = $this->getCacheKey($event);
        $runningProcesses[$processName] = true;

        $runningProcesses = serialize($runningProcesses);
        $adapter->setItem(self::CACHE_KEY_RUNNING_PROCESSES, $runningProcesses);
        $this->updateCache($event);
    }

    /**
     * @param ProgressEvent $event
     */
    public function start(ProgressEvent $event)
    {
        $this->updateCache($event);
    }

    /**
     * @param ProgressEvent $event
     */
    public function execute(ProgressEvent $event)
    {
        $this->updateCache($event);
    }

    /**
     * @param ProgressEvent $event
     */
    public function finish(ProgressEvent $event)
    {
        $this->updateCache($event);
    }

    /**
     * will remove the currents process cache key from the list of running processes
     *
     * @param ProgressEvent $event
     */
    public function finishApplication(ProgressEvent $event)
    {
        $adapter = $this->getRedisAdapter();
        $runningProcesses = $adapter->getItem(self::CACHE_KEY_RUNNING_PROCESSES);
        $runningProcesses = unserialize($runningProcesses);

        $processName = $this->getCacheKey($event);

        // touch process event with final lifetime set so it will expire soon
        $options = $adapter->getOptions();
        $defaultTtl = $options->getTtl();
        $options->setTtl($this->getLifetimeAfterAppEnd());
        $this->updateCache($event);
        $options->setTtl($defaultTtl);

        // set that process is no longer running
        $runningProcesses[$processName] = false;
        $runningProcesses = serialize($runningProcesses);
        $adapter->setItem(self::CACHE_KEY_RUNNING_PROCESSES, $runningProcesses);
    }

    /**
     * @param ProgressEvent $event
     */
    public function updateCache(ProgressEvent $event)
    {
        $redis = $this->getRedisAdapter();
        // update event
        $cacheKey = $this->getCacheKey($event);
        $redis->setItem($cacheKey, serialize(array(
            ProgressEvent::PARAM_PROCESS_NAME        => $event->getProcessName(),
            ProgressEvent::PARAM_PROCESS_DESCRIPTION => $event->getProcessDescription(),
            ProgressEvent::PARAM_APP_START           => $event->getAppStart(),
            ProgressEvent::PARAM_TASK_NAME           => $event->getTaskName(),
            ProgressEvent::PARAM_START               => $event->getStart(),
            ProgressEvent::PARAM_APP_END             => $event->getAppEnd(),
            ProgressEvent::PARAM_APP_RESPONSE        => $event->getAppResponse(),
            ProgressEvent::PARAM_TOTAL_EXECUTIONS    => $event->getTotalExecutions(),
            ProgressEvent::PARAM_DONE_COUNT          => $event->getDoneCount(),
            ProgressEvent::PARAM_SKIPPED_COUNT       => $event->getSkippedCount(),
        )));

        // remove expired events from running list, not taking their status in account
        $runningProcesses = $redis->getItem(self::CACHE_KEY_RUNNING_PROCESSES);
        $runningProcesses = unserialize($runningProcesses);
        $updatedProcesses = $runningProcesses;
        foreach ($runningProcesses as $processName => $isRunning) {
            if(!$redis->hasItem($processName)) {
                unset($updatedProcesses[$processName]);
            }
        }
        if($runningProcesses != $updatedProcesses) {
            $redis->setItem(self::CACHE_KEY_RUNNING_PROCESSES, serialize($updatedProcesses));
        }
    }

    /**
     * @param ProgressEvent $event
     * @return string
     */
    public function getCacheKey(ProgressEvent $event)
    {
        $processName = $event->getProcessName();
        return ($processName ? $processName . "_" : "") . getmypid();
    }

    /**
     * @return Redis
     */
    public function getRedisAdapter()
    {
        return $this->redisAdapter;
    }

    /**
     * @param Redis $redisAdapter
     */
    public function setRedisAdapter(Redis $redisAdapter)
    {
        $this->redisAdapter = $redisAdapter;
    }

    /**
     * @return int
     */
    public function getLifetimeAfterAppEnd()
    {
        return $this->lifetimeAfterAppEnd;
    }

    /**
     * @param int $lifetimeAfterAppEnd
     */
    public function setLifetimeAfterAppEnd($lifetimeAfterAppEnd)
    {
        $this->lifetimeAfterAppEnd = $lifetimeAfterAppEnd;
    }

    /**
     * @return string
     */
    public function getProcessName()
    {
        return $this->processName;
    }

    /**
     * @param string $processName
     */
    public function setProcessName($processName)
    {
        $this->processName = $processName;
    }


}