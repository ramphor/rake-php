<?php

namespace Rake\Tests\Actions;

use PHPUnit\Framework\TestCase;
use Rake\Actions\ActionContext;

/**
 * Test ActionContext
 * 
 * @group unit
 * @group actions
 */
class ActionContextTest extends TestCase
{
    public function test_creates_context_with_required_parameters()
    {
        $context = new ActionContext('phase1', 123);

        $this->assertEquals('phase1', $context->getPhase());
        $this->assertEquals(123, $context->getProjectId());
        $this->assertEquals([], $context->getResults());
        $this->assertEquals([], $context->getFlowConfig());
    }

    public function test_creates_context_with_all_parameters()
    {
        $results = ['items_saved' => 10, 'errors' => []];
        $flowConfig = ['nodes' => [], 'edges' => []];
        $data = ['custom_key' => 'custom_value'];

        $context = new ActionContext('phase2', 456, $results, $flowConfig, $data);

        $this->assertEquals('phase2', $context->getPhase());
        $this->assertEquals(456, $context->getProjectId());
        $this->assertEquals($results, $context->getResults());
        $this->assertEquals($flowConfig, $context->getFlowConfig());
    }

    public function test_get_data_returns_all_data_when_no_key_provided()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $context = new ActionContext('phase1', 123, [], [], $data);

        $this->assertEquals($data, $context->getData());
    }

    public function test_get_data_returns_specific_value_when_key_provided()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $context = new ActionContext('phase1', 123, [], [], $data);

        $this->assertEquals('value1', $context->getData('key1'));
        $this->assertEquals('value2', $context->getData('key2'));
    }

    public function test_get_data_returns_default_when_key_not_found()
    {
        $context = new ActionContext('phase1', 123);

        $this->assertNull($context->getData('nonexistent'));
        $this->assertEquals('default', $context->getData('nonexistent', 'default'));
    }

    public function test_set_data_adds_new_key_value()
    {
        $context = new ActionContext('phase1', 123);

        $context->setData('new_key', 'new_value');

        $this->assertEquals('new_value', $context->getData('new_key'));
    }

    public function test_set_data_overwrites_existing_key()
    {
        $context = new ActionContext('phase1', 123, [], [], ['key' => 'old_value']);

        $context->setData('key', 'new_value');

        $this->assertEquals('new_value', $context->getData('key'));
    }

    public function test_supports_all_phase_types()
    {
        $phases = ['phase1', 'phase2', 'phase3'];

        foreach ($phases as $phase) {
            $context = new ActionContext($phase, 123);
            $this->assertEquals($phase, $context->getPhase());
        }
    }

    public function test_get_results_returns_empty_array_by_default()
    {
        $context = new ActionContext('phase1', 123);

        $this->assertEquals([], $context->getResults());
    }

    public function test_get_flow_config_returns_empty_array_by_default()
    {
        $context = new ActionContext('phase1', 123);

        $this->assertEquals([], $context->getFlowConfig());
    }
}

