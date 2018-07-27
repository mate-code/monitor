<?php


namespace mateTest\Monitor\Listener;

use mate\Monitor\Exception\InvalidArgumentException;
use mate\Monitor\Listener\AbstractProgressListener;
use mate\Monitor\Listener\ProgressEvent;
use mate\Monitor\Listener\RedisProgressListener;
use mate\PhpUnit\TestWithMockTrait;
use Zend\Cache\Storage\Adapter\Redis;
use Zend\Cache\Storage\Adapter\RedisOptions;


/**
 * @package mateTest\Monitor\Listener
 */
class RedisProgressListenerTest extends \PHPUnit_Framework_TestCase
{

    use TestWithMockTrait;
    /**
     * @var RedisProgressListener|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $listener;
    /**
     * @var Redis|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $redisAdapterMock;
    /**
     * @var string
     */
    protected $processName = "test";

    public function setUp()
    {
        $this->redisAdapterMock = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()->getMock();

        $this->setTestClass(RedisProgressListener::class);
        $this->setConstructorArgs(array(
            $this->redisAdapterMock
        ));
        $this->listener = $this->createInstanceToTest(array(
            "updateCache"
        ));
    }

    public function testExtendsAbstractProgressListener()
    {
        $this->assertInstanceOf(AbstractProgressListener::class, $this->listener,
            RedisProgressListener::class . " does not extend " . AbstractProgressListener::class);
    }

    public function testSetAndGetRedisAdapter()
    {
        /** @var Redis|\PHPUnit_Framework_MockObject_MockObject $adapter */
        $adapter = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()->getMock();
        $this->listener->setRedisAdapter($adapter);
        $returnedAdapter = $this->listener->getRedisAdapter();
        $this->assertSame($adapter, $returnedAdapter,
            "setRedisAdapter does not set or gerRedisAdapter does not get the adapter instance");
    }

    // CONSTRUCTOR TEST

    /**
     * @depends testSetAndGetRedisAdapter
     */
    public function testConstructSetsAdapter()
    {
        $this->assertSame($this->redisAdapterMock, $this->listener->getRedisAdapter(),
            "__construct() does not set the given redis adapter instance");
    }

    /**
     * @depends testSetAndGetRedisAdapter
     */
    public function testConstructCreatesAdapter()
    {
        $options = array(
            'server' => array(
                'host' => '192.168.100.11',
                'port' => 6379,
            )
        );
        $listener = new RedisProgressListener($options);
        $expectedAdapter = new Redis($options);
        $this->assertEquals($expectedAdapter, $listener->getRedisAdapter(),
            "__construct() does not create the correct redis adapter instance");
    }

    /**
     * @depends testSetAndGetRedisAdapter
     */
    public function testConstructUsesDefaultOptions()
    {
        $options = RedisProgressListener::REDIS_DEFAULT_OPTIONS;
        $listener = new RedisProgressListener();
        $expectedAdapter = new Redis($options);
        $this->assertEquals($expectedAdapter, $listener->getRedisAdapter(),
            "__construct() does not create a default redis adapter");
    }

    /**
     * @depends testSetAndGetRedisAdapter
     */
    public function testConstructThrowsException()
    {
        $message = RedisProgressListener::EXCEPTION_INVALID_ADAPTER;
        $this->setExpectedException(InvalidArgumentException::class, $message);
        new RedisProgressListener("invalid");
    }

    // UPDATE STORAGE

    public function testGetCacheKey()
    {
        $event = new ProgressEvent();
        $event->setProcessName($this->processName);

        $expectedCacheKey = $this->processName . "_" . getmypid();
        $actualCacheKey = $this->listener->getCacheKey($event);
        $this->assertEquals($expectedCacheKey, $actualCacheKey,
            "getCacheKey() does not create the correct key");
    }

    public function testTriggerUpdatesStorage()
    {
        $this->listener = $this->createInstanceToTest(array(
            "getCacheKey"
        ));

        $eventArray = array(
            ProgressEvent::PARAM_PROCESS_NAME        => $this->processName,
            ProgressEvent::PARAM_PROCESS_DESCRIPTION => "php public/index.php order modotex forward",
            ProgressEvent::PARAM_APP_START           => 192844.28472,
            ProgressEvent::PARAM_TASK_NAME           => "modotex-forward",
            ProgressEvent::PARAM_START               => 192845.28472,
            ProgressEvent::PARAM_APP_END             => 192845.32412,
            ProgressEvent::PARAM_APP_RESPONSE        => '{"status": 200}',
            ProgressEvent::PARAM_TOTAL_EXECUTIONS    => 10,
            ProgressEvent::PARAM_DONE_COUNT          => 3,
            ProgressEvent::PARAM_SKIPPED_COUNT       => 1,
        );

        $event = new ProgressEvent(null, null, $eventArray);

        $cacheKey = "cache-key";
        $this->listener->method("getCacheKey")
            ->will($this->returnValue($cacheKey));

        $this->redisAdapterMock->expects($this->at(0))
            ->method("setItem")
            ->with($cacheKey, serialize($eventArray));

        $runningProcesses = array(
            "task_1" => true,
            "task_2" => false,
            "task_3" => false,
        );
        $this->redisAdapterMock->method("getItem")
            ->will($this->returnValue(serialize($runningProcesses)));
        $this->redisAdapterMock->method("hasItem")
            ->will($this->returnValueMap(array(
                ["task_1", true],
                ["task_2", true],
                ["task_3", false],
            )));
        $updatedRunningProcesses = array(
            "task_1" => true,
            "task_2" => false,
        );
        $this->redisAdapterMock->expects($this->at(5))
            ->method("setItem")
            ->with(
                RedisProgressListener::CACHE_KEY_RUNNING_PROCESSES,
                serialize($updatedRunningProcesses)
            );

        $this->listener->updateCache($event);
    }

    public function provideTestMethodUpdatesCache()
    {
        return array(
            ["start"],
            ["execute"],
            ["finish"],
        );
    }

    /**
     * @dataProvider provideTestMethodUpdatesCache
     *
     * @param $method
     */
    public function testMethodUpdatesCache($method)
    {
        $event = new ProgressEvent();

        $this->listener->expects($this->once())
            ->method("updateCache")
            ->with($event);

        $this->listener->$method($event);
    }

    // RUNNING PROCESSES

    /**
     * @depends testGetCacheKey
     */
    public function testStartApplicationAddsRunningProcess()
    {
        $event = new ProgressEvent();
        $event->setProcessName($this->processName);

        $this->redisAdapterMock->expects($this->once())
            ->method("getItem")
            ->with(RedisProgressListener::CACHE_KEY_RUNNING_PROCESSES)
            ->will($this->returnValue(serialize(array("another-process" => true))));

        $processName = $this->listener->getCacheKey($event);
        $expectedRunningProcesses = serialize(["another-process" => true, $processName => true]);

        $this->redisAdapterMock->expects($this->once())
            ->method("setItem")
            ->with(RedisProgressListener::CACHE_KEY_RUNNING_PROCESSES, $expectedRunningProcesses);

        $this->listener->expects($this->once())
            ->method("updateCache")
            ->with($event);

        $this->listener->startApplication($event);
    }

    /**
     * @depends testGetCacheKey
     */
    public function testStartApplicationSetsRunningProcesses()
    {
        $event = new ProgressEvent();
        $event->setProcessName($this->processName);

        $this->redisAdapterMock->expects($this->once())
            ->method("getItem")
            ->with(RedisProgressListener::CACHE_KEY_RUNNING_PROCESSES)
            ->will($this->returnValue(null));

        $processName = $this->listener->getCacheKey($event);
        $expectedRunningProcesses = serialize([$processName => true]);

        $this->redisAdapterMock->expects($this->once())
            ->method("setItem")
            ->with(RedisProgressListener::CACHE_KEY_RUNNING_PROCESSES, $expectedRunningProcesses);

        $this->listener->startApplication($event);
    }

    /**
     * @depends testGetCacheKey
     */
    public function testEndApplication()
    {
        $this->listener = $this->createInstanceToTest(array(
            "updateCache"
        ));

        $event = new ProgressEvent();
        $event->setProcessName($this->processName);

        $processName = $this->listener->getCacheKey($event);
        $this->redisAdapterMock->expects($this->once())
            ->method("getItem")
            ->with(RedisProgressListener::CACHE_KEY_RUNNING_PROCESSES)
            ->will($this->returnValue(serialize(array($processName => true))));

        $expectedRunningProcesses = serialize(array($processName => false));

        $oldTtl = 0;
        $newTtl = 5;
        $this->listener->setLifetimeAfterAppEnd($newTtl);

        $redisOptions = $this->getMockBuilder(RedisOptions::class)
            ->disableOriginalConstructor()->getMock();
        $redisOptions->expects($this->at(0))
            ->method("getTtl")
            ->will($this->returnValue($oldTtl));
        $redisOptions->expects($this->at(1))
            ->method("setTtl")
            ->with($newTtl);
        $redisOptions->expects($this->at(2))
            ->method("setTtl")
            ->with($oldTtl);

        $this->redisAdapterMock->method("getOptions")
            ->will($this->returnValue($redisOptions));
        $this->listener->expects($this->once())
            ->method("updateCache")
            ->with($event);

        $this->redisAdapterMock->expects($this->once())
            ->method("setItem")
            ->with(RedisProgressListener::CACHE_KEY_RUNNING_PROCESSES, $expectedRunningProcesses);

        $this->listener->finishApplication($event);
    }

}
