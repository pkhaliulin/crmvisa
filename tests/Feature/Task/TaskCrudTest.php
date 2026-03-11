<?php

namespace Tests\Feature\Task;

use App\Modules\Task\Models\AgencyTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    private function taskUrl(string $suffix = ''): string
    {
        return '/api/v1/tasks' . ($suffix ? "/{$suffix}" : '');
    }

    private function createTask($agency, $owner, array $attrs = []): AgencyTask
    {
        return AgencyTask::create(array_merge([
            'title'      => 'Тестовая задача',
            'agency_id'  => $agency->id,
            'created_by' => $owner->id,
            'priority'   => 'medium',
            'status'     => 'new',
        ], $attrs));
    }

    // -- CRUD ───────────────────────────────────────────────

    public function test_owner_can_create_task(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);

        $response = $this->postJson($this->taskUrl(), [
            'title'    => 'Позвонить клиенту',
            'priority' => 'high',
        ], $headers);

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertEquals('Позвонить клиенту', $data['title']);
        $this->assertEquals('high', $data['priority']);
        $this->assertEquals('new', $data['status']);
        $this->assertEquals($owner->id, $data['created_by']);
    }

    public function test_manager_can_create_task(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $headers = $this->authHeaders($manager);

        $response = $this->postJson($this->taskUrl(), [
            'title' => 'Проверить документы',
        ], $headers);

        $response->assertStatus(201);
        $this->assertEquals($manager->id, $response->json('data.created_by'));
    }

    public function test_create_task_with_all_fields(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $client = $this->createClient($agency);
        $headers = $this->authHeaders($owner);

        $case = \App\Modules\Case\Models\VisaCase::factory()->create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
        ]);

        $response = $this->postJson($this->taskUrl(), [
            'title'           => 'Подготовить перевод',
            'description'     => 'Нотариальный перевод паспорта',
            'priority'        => 'urgent',
            'assigned_to'     => $manager->id,
            'case_id'         => $case->id,
            'due_date'        => now()->addDays(3)->format('Y-m-d'),
            'recurrence_rule' => 'weekly',
        ], $headers);

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertEquals('urgent', $data['priority']);
        $this->assertEquals($manager->id, $data['assigned_to']);
        $this->assertEquals($case->id, $data['case_id']);
        $this->assertEquals('weekly', $data['recurrence_rule']);
    }

    public function test_list_tasks(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);

        $this->createTask($agency, $owner, ['title' => 'Задача 1']);
        $this->createTask($agency, $owner, ['title' => 'Задача 2', 'priority' => 'high']);

        $response = $this->getJson($this->taskUrl(), $headers);
        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data.data')));
    }

    public function test_update_task(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);
        $task = $this->createTask($agency, $owner, ['title' => 'Старое', 'priority' => 'low']);

        $response = $this->patchJson($this->taskUrl($task->id), [
            'title'    => 'Новое',
            'priority' => 'urgent',
            'status'   => 'accepted',
        ], $headers);

        $response->assertOk();
        $this->assertEquals('Новое', $response->json('data.title'));
        $this->assertEquals('urgent', $response->json('data.priority'));
        $this->assertEquals('accepted', $response->json('data.status'));
    }

    public function test_delete_task(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);
        $task = $this->createTask($agency, $owner);

        $response = $this->deleteJson($this->taskUrl($task->id), [], $headers);
        $response->assertOk();
        $this->assertSoftDeleted('agency_tasks', ['id' => $task->id]);
    }

    // -- Status transitions ─────────────────────────────────

    public function test_transition_new_to_accepted(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);
        $task = $this->createTask($agency, $owner, ['status' => 'new']);

        $response = $this->postJson($this->taskUrl("{$task->id}/transition"), [], $headers);
        $response->assertOk();
        $this->assertEquals('accepted', $response->json('data.status'));
    }

    public function test_transition_accepted_to_completed(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);
        $task = $this->createTask($agency, $owner, ['status' => 'accepted']);

        $response = $this->postJson($this->taskUrl("{$task->id}/transition"), [], $headers);
        $response->assertOk();
        $this->assertEquals('completed', $response->json('data.status'));
        $this->assertNotNull($response->json('data.completed_at'));
        $this->assertEquals($owner->id, $response->json('data.completed_by'));
    }

    public function test_transition_completed_to_verified_by_owner(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);
        $task = $this->createTask($agency, $owner, [
            'status'       => 'completed',
            'completed_at' => now(),
            'completed_by' => $owner->id,
        ]);

        $response = $this->postJson($this->taskUrl("{$task->id}/transition"), [], $headers);
        $response->assertOk();
        $this->assertEquals('verified', $response->json('data.status'));
        $this->assertNotNull($response->json('data.verified_at'));
    }

    public function test_defer_and_reopen_task(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);
        $task = $this->createTask($agency, $owner, ['status' => 'accepted']);

        // Defer
        $res = $this->postJson($this->taskUrl("{$task->id}/set-status"), ['status' => 'deferred'], $headers);
        $res->assertOk();
        $this->assertEquals('deferred', $res->json('data.status'));

        // Reopen
        $res2 = $this->postJson($this->taskUrl("{$task->id}/set-status"), ['status' => 'new'], $headers);
        $res2->assertOk();
        $this->assertEquals('new', $res2->json('data.status'));
    }

    // -- Tenant isolation ───────────────────────────────────

    public function test_tasks_are_tenant_isolated(): void
    {
        [$agency1, $owner1] = $this->createAgencyWithOwner();
        [$agency2, $owner2] = $this->createAgencyWithOwner();

        $this->createTask($agency1, $owner1, ['title' => 'Agency1 task']);
        $this->createTask($agency2, $owner2, ['title' => 'Agency2 task']);

        $headers1 = $this->authHeaders($owner1);
        $res = $this->getJson($this->taskUrl(), $headers1);
        $res->assertOk();

        $titles = collect($res->json('data.data'))->pluck('title')->all();
        $this->assertContains('Agency1 task', $titles);
        $this->assertNotContains('Agency2 task', $titles);
    }

    // -- Role-based access ─────────────────────────────────

    public function test_manager_sees_only_own_tasks(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);

        $this->createTask($agency, $owner, ['title' => 'Owner task', 'assigned_to' => $owner->id]);
        $this->createTask($agency, $owner, ['title' => 'Assigned to manager', 'assigned_to' => $manager->id]);
        $this->createTask($agency, $manager, ['title' => 'Created by manager']);

        $headers = $this->authHeaders($manager);
        $res = $this->getJson($this->taskUrl(), $headers);
        $res->assertOk();

        $titles = collect($res->json('data.data'))->pluck('title')->all();
        $this->assertContains('Assigned to manager', $titles);
        $this->assertContains('Created by manager', $titles);
        $this->assertNotContains('Owner task', $titles);
    }

    public function test_owner_sees_all_tasks(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);

        $this->createTask($agency, $owner, ['title' => 'Owner task']);
        $this->createTask($agency, $manager, ['title' => 'Manager task']);

        $headers = $this->authHeaders($owner);
        $res = $this->getJson($this->taskUrl(), $headers);
        $res->assertOk();

        $titles = collect($res->json('data.data'))->pluck('title')->all();
        $this->assertContains('Owner task', $titles);
        $this->assertContains('Manager task', $titles);
    }

    public function test_manager_cannot_update_owner_task(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);

        $task = $this->createTask($agency, $owner, ['title' => 'Owner task', 'assigned_to' => $manager->id]);

        $headers = $this->authHeaders($manager);
        $res = $this->patchJson($this->taskUrl($task->id), ['title' => 'Changed'], $headers);
        $res->assertStatus(403);
    }

    public function test_manager_can_transition_assigned_task(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);

        $task = $this->createTask($agency, $owner, ['status' => 'new', 'assigned_to' => $manager->id]);

        $headers = $this->authHeaders($manager);
        $res = $this->postJson($this->taskUrl("{$task->id}/transition"), [], $headers);
        $res->assertOk();
        $this->assertEquals('accepted', $res->json('data.status'));
    }

    public function test_manager_cannot_delete_owner_task(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);

        $task = $this->createTask($agency, $owner, ['assigned_to' => $manager->id]);

        $headers = $this->authHeaders($manager);
        $res = $this->deleteJson($this->taskUrl($task->id), [], $headers);
        $res->assertStatus(403);
    }

    public function test_manager_can_update_own_task(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);

        $task = $this->createTask($agency, $manager, ['title' => 'My task']);

        $headers = $this->authHeaders($manager);
        $res = $this->patchJson($this->taskUrl($task->id), ['title' => 'Updated'], $headers);
        $res->assertOk();
        $this->assertEquals('Updated', $res->json('data.title'));
    }

    // -- Counters ───────────────────────────────────────────

    public function test_counters(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $headers = $this->authHeaders($owner);

        $this->createTask($agency, $owner, ['assigned_to' => $owner->id, 'status' => 'new']);
        $this->createTask($agency, $owner, ['assigned_to' => $manager->id, 'status' => 'accepted']);
        $this->createTask($agency, $owner, ['status' => 'closed']);
        $this->createTask($agency, $owner, ['assigned_to' => $owner->id, 'status' => 'new', 'due_date' => now()->subDays(2)]);

        $response = $this->getJson('/api/v1/tasks/counters', $headers);
        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals(3, $data['total_active']);  // new + accepted + overdue new
        $this->assertEquals(2, $data['my_tasks']);      // owner assigned
        $this->assertEquals(1, $data['overdue']);
    }

    // -- Recurring tasks ────────────────────────────────────

    public function test_recurring_task_creates_next_on_close(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);

        $task = $this->createTask($agency, $owner, [
            'title'           => 'Еженедельная проверка',
            'recurrence_rule' => 'weekly',
            'due_date'        => now()->format('Y-m-d'),
            'status'          => 'verified',
            'completed_at'    => now(),
            'completed_by'    => $owner->id,
            'verified_at'     => now(),
            'verified_by'     => $owner->id,
        ]);

        // Transition verified → closed
        $response = $this->postJson($this->taskUrl("{$task->id}/transition"), [], $headers);
        $response->assertOk();
        $this->assertEquals('closed', $response->json('data.status'));

        // Check that a child task was created
        $child = AgencyTask::where('recurrence_parent_id', $task->id)->first();
        $this->assertNotNull($child, 'Должна быть создана следующая повторяющаяся задача');
        $this->assertEquals('Еженедельная проверка', $child->title);
        $this->assertEquals('new', $child->status);
        $this->assertEquals($task->id, $child->recurrence_parent_id);
    }

    public function test_recurring_task_daily_calculates_correct_next_date(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();

        $task = $this->createTask($agency, $owner, [
            'recurrence_rule' => 'daily',
            'due_date'        => '2026-03-11',
        ]);

        $next = $task->createNextRecurrence();
        $this->assertNotNull($next);
        $this->assertEquals('2026-03-12', $next->due_date->format('Y-m-d'));
    }

    public function test_recurring_task_monthly(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();

        $task = $this->createTask($agency, $owner, [
            'recurrence_rule' => 'monthly',
            'due_date'        => '2026-03-15',
        ]);

        $next = $task->createNextRecurrence();
        $this->assertNotNull($next);
        $this->assertEquals('2026-04-15', $next->due_date->format('Y-m-d'));
    }

    // -- Validation ─────────────────────────────────────────

    public function test_create_requires_title(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);

        $response = $this->postJson($this->taskUrl(), ['priority' => 'high'], $headers);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    public function test_rejects_invalid_priority(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);

        $response = $this->postJson($this->taskUrl(), ['title' => 'X', 'priority' => 'critical'], $headers);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('priority');
    }

    public function test_rejects_invalid_recurrence_rule(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);

        $response = $this->postJson($this->taskUrl(), ['title' => 'X', 'recurrence_rule' => 'biweekly'], $headers);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('recurrence_rule');
    }
}
