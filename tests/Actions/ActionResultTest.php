<?php

namespace Rake\Tests\Actions;

use PHPUnit\Framework\TestCase;
use Rake\Actions\ActionResult;

/**
 * Test ActionResult
 * 
 * @group unit
 * @group actions
 */
class ActionResultTest extends TestCase
{
    public function test_creates_success_result_with_static_method()
    {
        $result = ActionResult::success();

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getError());
        $this->assertEquals([], $result->getData());
        $this->assertEquals([], $result->getMetadata());
    }

    public function test_creates_success_result_with_data()
    {
        $data = ['items' => 10, 'processed' => 5];
        $metadata = ['execution_time' => 100];

        $result = ActionResult::success($data, $metadata);

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getError());
        $this->assertEquals($data, $result->getData());
        $this->assertEquals($metadata, $result->getMetadata());
    }

    public function test_creates_error_result_with_static_method()
    {
        $result = ActionResult::error('Something went wrong');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Something went wrong', $result->getError());
        $this->assertEquals([], $result->getData());
        $this->assertEquals([], $result->getMetadata());
    }

    public function test_creates_error_result_with_data()
    {
        $error = 'Validation failed';
        $data = ['field' => 'email', 'value' => 'invalid'];
        $metadata = ['code' => 400];

        $result = ActionResult::error($error, $data, $metadata);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals($error, $result->getError());
        $this->assertEquals($data, $result->getData());
        $this->assertEquals($metadata, $result->getMetadata());
    }

    public function test_get_returns_specific_data_value()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $result = ActionResult::success($data);

        $this->assertEquals('value1', $result->get('key1'));
        $this->assertEquals('value2', $result->get('key2'));
    }

    public function test_get_returns_default_when_key_not_found()
    {
        $result = ActionResult::success();

        $this->assertNull($result->get('nonexistent'));
        $this->assertEquals('default', $result->get('nonexistent', 'default'));
    }

    public function test_constructor_creates_success_result()
    {
        $result = new ActionResult(true, null, ['data' => 'value']);

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getError());
        $this->assertEquals(['data' => 'value'], $result->getData());
    }

    public function test_constructor_creates_error_result()
    {
        $result = new ActionResult(false, 'Error message', [], ['code' => 500]);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Error message', $result->getError());
        $this->assertEquals(['code' => 500], $result->getMetadata());
    }

    public function test_handles_complex_data_structures()
    {
        $data = [
            'nested' => [
                'array' => [1, 2, 3],
                'object' => (object)['key' => 'value'],
            ],
            'string' => 'test',
            'number' => 42,
        ];

        $result = ActionResult::success($data);

        $this->assertEquals($data, $result->getData());
        $this->assertEquals([1, 2, 3], $result->get('nested')['array']);
    }
}

