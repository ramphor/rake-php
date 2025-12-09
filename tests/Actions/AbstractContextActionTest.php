<?php

namespace Rake\Tests\Actions;

use PHPUnit\Framework\TestCase;
use Rake\Actions\AbstractContextAction;
use Rake\Actions\ActionContext;
use Rake\Actions\ActionResult;

/**
 * Test AbstractContextAction
 * 
 * @group unit
 * @group actions
 */
class AbstractContextActionTest extends TestCase
{
    private function createConcreteAction(): AbstractContextAction
    {
        return new class('test_action', 'Test Action', 'Test description', ['phase1'], 10) extends AbstractContextAction {
            public function execute(ActionContext $context): ActionResult
            {
                return ActionResult::success(['executed' => true]);
            }
        };
    }

    public function test_constructor_sets_properties()
    {
        $action = $this->createConcreteAction();

        $this->assertEquals('test_action', $action->getId());
        $this->assertEquals('Test Action', $action->getLabel());
        $this->assertEquals('Test description', $action->getDescription());
        $this->assertEquals(['phase1'], $action->getSupportedContexts());
        $this->assertEquals(10, $action->getPriority());
    }

    public function test_should_execute_returns_true_for_supported_phase()
    {
        $action = $this->createConcreteAction();
        $context = new ActionContext('phase1', 123);

        $this->assertTrue($action->shouldExecute($context));
    }

    public function test_should_execute_returns_false_for_unsupported_phase()
    {
        $action = $this->createConcreteAction();
        $context = new ActionContext('phase2', 123);

        $this->assertFalse($action->shouldExecute($context));
    }

    public function test_supports_multiple_phases()
    {
        $action = new class('multi_phase', 'Multi Phase', '', ['phase1', 'phase2'], 5) extends AbstractContextAction {
            public function execute(ActionContext $context): ActionResult
            {
                return ActionResult::success();
            }
        };

        $this->assertTrue($action->shouldExecute(new ActionContext('phase1', 123)));
        $this->assertTrue($action->shouldExecute(new ActionContext('phase2', 123)));
        $this->assertFalse($action->shouldExecute(new ActionContext('phase3', 123)));
    }

    public function test_get_priority_returns_set_priority()
    {
        $action = new class('priority_test', 'Priority Test', '', ['phase1'], 25) extends AbstractContextAction {
            public function execute(ActionContext $context): ActionResult
            {
                return ActionResult::success();
            }
        };

        $this->assertEquals(25, $action->getPriority());
    }

    public function test_default_priority_is_10()
    {
        $action = new class('default_priority', 'Default Priority') extends AbstractContextAction {
            public function execute(ActionContext $context): ActionResult
            {
                return ActionResult::success();
            }
        };

        $this->assertEquals(10, $action->getPriority());
    }

    public function test_default_supported_contexts_is_phase1()
    {
        $action = new class('default_context', 'Default Context') extends AbstractContextAction {
            public function execute(ActionContext $context): ActionResult
            {
                return ActionResult::success();
            }
        };

        $this->assertEquals(['phase1'], $action->getSupportedContexts());
    }
}

