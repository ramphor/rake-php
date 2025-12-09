<?php

namespace Rake\Tests\Actions;

use PHPUnit\Framework\TestCase;
use Rake\Actions\ContextActionManager;
use Rake\Actions\ContextActionInterface;
use Rake\Actions\ActionContext;
use Rake\Actions\ActionResult;

/**
 * Test ContextActionManager
 * 
 * @group unit
 * @group actions
 */
class ContextActionManagerTest extends TestCase
{
    private function createMockAction(string $id, array $supportedPhases = ['phase1'], int $priority = 10): ContextActionInterface
    {
        return new class($id, $supportedPhases, $priority) implements ContextActionInterface {
            private string $id;
            private array $supportedPhases;
            private int $priority;
            public bool $executed = false;

            public function __construct(string $id, array $supportedPhases, int $priority)
            {
                $this->id = $id;
                $this->supportedPhases = $supportedPhases;
                $this->priority = $priority;
            }

            public function execute(ActionContext $context): ActionResult
            {
                $this->executed = true;
                return ActionResult::success(['action_id' => $this->id]);
            }

            public function getId(): string
            {
                return $this->id;
            }

            public function getLabel(): string
            {
                return "Action {$this->id}";
            }

            public function getDescription(): string
            {
                return "Description for {$this->id}";
            }

            public function getSupportedContexts(): array
            {
                return $this->supportedPhases;
            }

            public function shouldExecute(ActionContext $context): bool
            {
                return in_array($context->getPhase(), $this->supportedPhases);
            }

            public function getPriority(): int
            {
                return $this->priority;
            }
        };
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Clear all actions before each test
        ContextActionManager::clearAll();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up after each test
        ContextActionManager::clearAll();
    }

    public function test_register_action_registers_for_supported_phases()
    {
        $action = $this->createMockAction('test_action', ['phase1', 'phase2']);

        ContextActionManager::register($action);

        $this->assertTrue(ContextActionManager::hasAction('phase1', 'test_action'));
        $this->assertTrue(ContextActionManager::hasAction('phase2', 'test_action'));
        $this->assertFalse(ContextActionManager::hasAction('phase3', 'test_action'));
    }

    public function test_register_action_prevents_duplicate_registration()
    {
        $action1 = $this->createMockAction('duplicate_action', ['phase1']);
        $action2 = $this->createMockAction('duplicate_action', ['phase1']);

        ContextActionManager::register($action1);
        ContextActionManager::register($action2); // Should not register duplicate

        $actions = ContextActionManager::getActions('phase1');
        $this->assertCount(1, $actions);
    }

    public function test_unregister_action_removes_from_specific_phase()
    {
        $action = $this->createMockAction('test_action', ['phase1', 'phase2']);

        ContextActionManager::register($action);
        ContextActionManager::unregister('test_action', 'phase1');

        $this->assertFalse(ContextActionManager::hasAction('phase1', 'test_action'));
        $this->assertTrue(ContextActionManager::hasAction('phase2', 'test_action'));
    }

    public function test_unregister_action_removes_from_all_phases()
    {
        $action = $this->createMockAction('test_action', ['phase1', 'phase2']);

        ContextActionManager::register($action);
        ContextActionManager::unregister('test_action');

        $this->assertFalse(ContextActionManager::hasAction('phase1', 'test_action'));
        $this->assertFalse(ContextActionManager::hasAction('phase2', 'test_action'));
    }

    public function test_execute_runs_all_registered_actions()
    {
        $action1 = $this->createMockAction('action1', ['phase1']);
        $action2 = $this->createMockAction('action2', ['phase1']);

        ContextActionManager::register($action1);
        ContextActionManager::register($action2);

        $context = new ActionContext('phase1', 123);
        $results = ContextActionManager::execute('phase1', $context);

        $this->assertCount(2, $results);
        $this->assertTrue($results['action1']['success']);
        $this->assertTrue($results['action2']['success']);
        $this->assertTrue($action1->executed);
        $this->assertTrue($action2->executed);
    }

    public function test_execute_skips_actions_that_should_not_execute()
    {
        // Override shouldExecute to return false
        $action = new class('test_action', ['phase1'], 10) implements ContextActionInterface {
            public function execute(ActionContext $context): ActionResult
            {
                return ActionResult::success();
            }

            public function getId(): string { return 'test_action'; }
            public function getLabel(): string { return 'Test'; }
            public function getDescription(): string { return ''; }
            public function getSupportedContexts(): array { return ['phase1']; }
            public function shouldExecute(ActionContext $context): bool { return false; }
        };

        ContextActionManager::register($action);

        $context = new ActionContext('phase1', 123);
        $results = ContextActionManager::execute('phase1', $context);

        $this->assertEmpty($results);
    }

    public function test_execute_sorts_actions_by_priority()
    {
        $action1 = $this->createMockAction('action1', ['phase1'], 30);
        $action2 = $this->createMockAction('action2', ['phase1'], 10);
        $action3 = $this->createMockAction('action3', ['phase1'], 20);

        ContextActionManager::register($action1);
        ContextActionManager::register($action2);
        ContextActionManager::register($action3);

        $context = new ActionContext('phase1', 123);
        $results = ContextActionManager::execute('phase1', $context);

        $resultKeys = array_keys($results);
        // Should be sorted by priority: 10, 20, 30
        $this->assertEquals('action2', $resultKeys[0]);
        $this->assertEquals('action3', $resultKeys[1]);
        $this->assertEquals('action1', $resultKeys[2]);
    }

    public function test_execute_handles_action_exceptions()
    {
        $action = new class('error_action', ['phase1'], 10) implements ContextActionInterface {
            public function execute(ActionContext $context): ActionResult
            {
                throw new \RuntimeException('Action failed');
            }

            public function getId(): string { return 'error_action'; }
            public function getLabel(): string { return 'Error Action'; }
            public function getDescription(): string { return ''; }
            public function getSupportedContexts(): array { return ['phase1']; }
            public function shouldExecute(ActionContext $context): bool { return true; }
        };

        ContextActionManager::register($action);

        $context = new ActionContext('phase1', 123);
        $results = ContextActionManager::execute('phase1', $context);

        $this->assertCount(1, $results);
        $this->assertFalse($results['error_action']['success']);
        $this->assertStringContainsString('Action failed', $results['error_action']['error']);
    }

    public function test_get_actions_returns_registered_actions()
    {
        $action1 = $this->createMockAction('action1', ['phase1']);
        $action2 = $this->createMockAction('action2', ['phase1']);

        ContextActionManager::register($action1);
        ContextActionManager::register($action2);

        $actions = ContextActionManager::getActions('phase1');

        $this->assertCount(2, $actions);
        $this->assertArrayHasKey('action1', $actions);
        $this->assertArrayHasKey('action2', $actions);
    }

    public function test_get_action_returns_specific_action()
    {
        $action = $this->createMockAction('test_action', ['phase1']);

        ContextActionManager::register($action);

        $retrieved = ContextActionManager::getAction('phase1', 'test_action');

        $this->assertNotNull($retrieved);
        $this->assertEquals('test_action', $retrieved->getId());
    }

    public function test_get_action_returns_null_for_nonexistent_action()
    {
        $retrieved = ContextActionManager::getAction('phase1', 'nonexistent');

        $this->assertNull($retrieved);
    }

    public function test_clear_removes_all_actions_for_phase()
    {
        $action1 = $this->createMockAction('action1', ['phase1']);
        $action2 = $this->createMockAction('action2', ['phase2']);

        ContextActionManager::register($action1);
        ContextActionManager::register($action2);

        ContextActionManager::clear('phase1');

        $this->assertEmpty(ContextActionManager::getActions('phase1'));
        $this->assertCount(1, ContextActionManager::getActions('phase2'));
    }

    public function test_clear_all_removes_all_actions()
    {
        $action1 = $this->createMockAction('action1', ['phase1']);
        $action2 = $this->createMockAction('action2', ['phase2']);
        $action3 = $this->createMockAction('action3', ['phase3']);

        ContextActionManager::register($action1);
        ContextActionManager::register($action2);
        ContextActionManager::register($action3);

        ContextActionManager::clearAll();

        $this->assertEmpty(ContextActionManager::getActions('phase1'));
        $this->assertEmpty(ContextActionManager::getActions('phase2'));
        $this->assertEmpty(ContextActionManager::getActions('phase3'));
    }

    public function test_execute_returns_empty_array_for_invalid_phase()
    {
        $context = new ActionContext('invalid_phase', 123);
        $results = ContextActionManager::execute('invalid_phase', $context);

        $this->assertEmpty($results);
    }
}

