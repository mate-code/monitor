<?php

namespace mateTest\Monitor;

use mate\Monitor\TriggerBuffer;

/**
 * @package mateTest\Monitor
 */
class TriggerBufferTest extends \PHPUnit_Framework_TestCase
{

    public function testFirstTriggerReturnsOne()
    {
        $buffer = new TriggerBuffer(0.1);
        $this->assertEquals(1, $buffer->collectTrigger());
    }

    /**
     * @depends testFirstTriggerReturnsOne
     */
    public function testTriggerReturnsCollectedAmount()
    {
        $buffer = new TriggerBuffer(0.001);
        $buffer->collectTrigger();
        $buffer->collectTrigger();
        usleep(1000);
        $this->assertEquals(2 , $buffer->collectTrigger());
    }

    public function testTriggerReturnsFalse()
    {
        $buffer = new TriggerBuffer(0.1);
        $buffer->collectTrigger();
        $this->assertFalse($buffer->collectTrigger());
    }

}
