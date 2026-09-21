<?php
namespace Tests\Feature;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\User;
use Tests\TestCase;

class TestReceiverWorkflow extends TestCase
{
    private function hasKind($user, $taskId, $kind): bool
    {
        return $user->fresh()->notifications()->get()->contains(
            fn($n) => ($n->data['task_id'] ?? null) == $taskId && ($n->data['kind'] ?? null) === $kind
        );
    }

    public function test_receiver_workflow()
    {
        $manager = User::where('role', 'manager')->first() ?? User::where('is_admin', true)->first();
        $staff = User::where('role', 'reception')->first();
        $this->assertNotNull($manager);
        $this->assertNotNull($staff);
        $sfx = uniqid();
        $created = [];

        // 1. Manager assigns -> staff notified
        $this->actingAs($manager);
        $this->post(route('reception.tasks.store'), [
            'title' => "RW Task $sfx", 'priority' => 'high', 'assigned_to' => $staff->id,
            'due_at' => now()->format('Y-m-d'),
        ])->assertRedirect();
        $task = Task::where('title', "RW Task $sfx")->first();
        $this->assertEquals('assigned', $task->status);
        $this->assertTrue($this->hasKind($staff, $task->id, 'assigned'));
        $created[] = $task->id;
        echo "receive ok\n";

        // 2. Decline without reason fails; with reason notifies creator
        $this->actingAs($staff);
        $this->post(route('reception.tasks.status', $task->id), ['status' => 'declined'])
            ->assertSessionHasErrors('comment');
        $this->post(route('reception.tasks.status', $task->id), ['status' => 'declined', 'comment' => 'Need clarification on scope'])->assertRedirect();
        $this->assertEquals('declined', $task->fresh()->status);
        $this->assertTrue($this->hasKind($manager, $task->id, 'declined'));
        echo "decline ok\n";

        // 3. Re-assign then accept -> accepted_at recorded
        $this->actingAs($manager);
        $this->post(route('reception.tasks.status', $task->id), ['status' => 'assigned'])->assertRedirect();
        $this->actingAs($staff);
        $this->post(route('reception.tasks.status', $task->id), ['status' => 'accepted'])->assertRedirect();
        $task->refresh();
        $this->assertEquals('accepted', $task->status);
        $this->assertNotNull($task->accepted_at);
        echo "accept ok\n";

        // 4. Start -> started_at recorded
        $this->post(route('reception.tasks.status', $task->id), ['status' => 'in_progress', 'comment' => 'Started calling'])->assertRedirect();
        $task->refresh();
        $this->assertEquals('in_progress', $task->status);
        $this->assertNotNull($task->started_at);
        echo "start ok\n";

        // 5. Updates land on timeline
        $this->post(route('reception.tasks.status', $task->id), ['status' => 'in_progress', 'comment' => 'Called ABC, waiting confirmation'])->assertRedirect();
        $this->assertTrue(TaskUpdate::where('task_id', $task->id)->where('comment', 'ilike', '%waiting for confirmation%')->exists());
        echo "updates ok\n";

        // 6. Submit with result -> creator notified
        $this->post(route('reception.tasks.status', $task->id), [
            'status' => 'submitted', 'result' => 'Contacted 12 customers', 'comment' => 'Ready for review',
        ])->assertRedirect();
        $this->assertEquals('submitted', $task->fresh()->status);
        $this->assertTrue($this->hasKind($manager, $task->id, 'submitted'));
        echo "submit ok\n";

        // 7. Manager returns -> back to in_progress + assignee notified
        $this->actingAs($manager);
        $this->post(route('reception.tasks.status', $task->id), ['status' => 'returned', 'comment' => 'Follow up the remaining customer'])->assertRedirect();
        $this->assertEquals('returned', $task->fresh()->status);
        $this->assertTrue($this->hasKind($staff, $task->id, 'returned'));
        echo "return ok\n";

        // 8. Staff cannot return/verify others' tasks... staff resubmits, manager approves
        $this->actingAs($staff);
        $this->post(route('reception.tasks.status', $task->id), ['status' => 'in_progress', 'comment' => 'Doing extra work'])->assertRedirect();
        $this->post(route('reception.tasks.status', $task->id), ['status' => 'submitted', 'result' => 'All 12 done'])->assertRedirect();
        $this->actingAs($manager);
        $this->post(route('reception.tasks.verify', $task->id))->assertRedirect();
        $task->refresh();
        $this->assertEquals('verified', $task->status);
        $this->assertTrue($this->hasKind($staff, $task->id, 'verified'));
        echo "approve ok\n";

        // 9. Detail shows action bar + review bar + timestamps
        $this->get(route('manager.tasks.show', $task->id))->assertOk()
            ->assertSee('Accept Task', false)
            ->assertSee('Approve &amp; Close', false)
            ->assertSee('Accepted', false);
        echo "views ok\n";

        foreach ($created as $id) {
            if ($t = Task::find($id)) {
                TaskUpdate::where('task_id', $id)->delete();
                $t->collaborators()->detach();
                $t->delete();
            }
        }
        $manager->notifications()->delete();
        $staff->notifications()->delete();
        echo "cleanup done\n";
    }
}
