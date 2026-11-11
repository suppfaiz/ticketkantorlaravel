<?php

namespace Tests\Feature;

use App\Models\ApprovalDelegation;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationAndDelegationTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    private User $supervisor;

    private User $otherSupervisor;

    private User $delegate;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $department = Department::create(['name' => 'Operasional', 'code' => 'OPS']);
        $this->supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $department->id,
            'is_active' => true,
        ]);
        $this->otherSupervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $department->id,
            'is_active' => true,
        ]);
        $this->delegate = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $department->id,
            'is_active' => true,
        ]);
        $this->requester = User::factory()->create([
            'role' => User::ROLE_REQUESTER,
            'department_id' => $department->id,
            'supervisor_id' => $this->supervisor->id,
            'is_active' => true,
        ]);
    }

    public function test_new_ticket_notifies_only_the_configured_supervisor(): void
    {
        $ticket = $this->submitTicket();

        $this->assertSame(1, $this->supervisor->unreadNotifications()->count());
        $this->assertSame(0, $this->otherSupervisor->unreadNotifications()->count());
        $this->assertSame($ticket->id, $this->supervisor->unreadNotifications()->first()->data['ticket_id']);
    }

    public function test_notification_can_be_marked_as_read_when_opened(): void
    {
        $this->submitTicket();
        $notification = $this->supervisor->unreadNotifications()->firstOrFail();

        $response = $this->actingAs($this->supervisor)->post(route('notifications.read', $notification));

        $response->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $this->submitTicket();
        $notification = $this->supervisor->unreadNotifications()->firstOrFail();

        $this->actingAs($this->otherSupervisor)
            ->post(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_active_delegate_receives_notification_and_can_approve_for_supervisor(): void
    {
        ApprovalDelegation::create([
            'delegator_id' => $this->supervisor->id,
            'delegate_id' => $this->delegate->id,
            'created_by' => $this->supervisor->id,
            'scope' => ApprovalDelegation::SCOPE_SUPERVISOR,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'reason' => 'Cuti tahunan',
            'is_active' => true,
        ]);

        User::factory()->create(['role' => User::ROLE_IT_HEAD, 'is_active' => true]);
        $ticket = $this->submitTicket();

        $this->assertSame(0, $this->supervisor->unreadNotifications()->count());
        $this->assertSame(1, $this->delegate->unreadNotifications()->count());

        $response = $this->actingAs($this->delegate)->post(route('tickets.supervisor-approve', $ticket), [
            'notes' => 'Disetujui atas delegasi atasan.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame(Ticket::STATUS_WAITING_IT_HEAD, $ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->delegate->id,
            'action' => 'SUPERVISOR_APPROVE',
        ]);
    }

    public function test_expired_delegate_cannot_approve(): void
    {
        ApprovalDelegation::create([
            'delegator_id' => $this->supervisor->id,
            'delegate_id' => $this->delegate->id,
            'created_by' => $this->supervisor->id,
            'scope' => ApprovalDelegation::SCOPE_SUPERVISOR,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'reason' => 'Delegasi lama',
            'is_active' => true,
        ]);

        $ticket = $this->submitTicket();

        $this->actingAs($this->delegate)
            ->post(route('tickets.supervisor-approve', $ticket))
            ->assertForbidden();
        $this->assertSame(Ticket::STATUS_WAITING_SUPERVISOR, $ticket->fresh()->status);
    }

    public function test_kadiv_delegate_can_process_executive_approval(): void
    {
        $kadiv = User::factory()->create(['role' => User::ROLE_KADIV_IT, 'is_active' => true]);
        User::factory()->create(['role' => User::ROLE_DIREKSI, 'is_active' => true]);
        ApprovalDelegation::create([
            'delegator_id' => $kadiv->id,
            'delegate_id' => $this->delegate->id,
            'created_by' => $kadiv->id,
            'scope' => ApprovalDelegation::SCOPE_KADIV_IT,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'reason' => 'Perjalanan dinas',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261111-9999',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Perubahan Sistem',
            'title' => 'Perubahan sistem major',
            'description' => 'Pengujian delegasi persetujuan eksekutif.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_LOA_KADIV_IT,
        ]);

        $this->actingAs($this->delegate)
            ->post(route('tickets.kadiv-approve', $ticket), ['notes' => 'Disetujui atas delegasi.'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame(Ticket::STATUS_WAITING_LOA_DIREKSI, $ticket->fresh()->status);
    }

    public function test_direct_assignment_notifies_developer_and_requester(): void
    {
        $itHead = User::factory()->create(['role' => User::ROLE_IT_HEAD, 'is_active' => true]);
        $developer = User::factory()->create(['role' => User::ROLE_IT_DEVELOPER, 'is_active' => true]);
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261111-9998',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Penugasan langsung developer',
            'description' => 'Pengujian notifikasi penugasan langsung kepada developer.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        $this->actingAs($itHead)->post(route('tickets.it-head-classify-approve', $ticket), [
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'assigned_to' => $developer->id,
        ])->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertSame(1, $developer->unreadNotifications()->count());
        $this->assertSame(1, $this->requester->unreadNotifications()->count());
    }

    public function test_progress_note_notifies_requester(): void
    {
        $developer = User::factory()->create(['role' => User::ROLE_IT_DEVELOPER, 'is_active' => true]);
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261111-9997',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Catatan progres',
            'description' => 'Pengujian notifikasi ketika progres pekerjaan ditambahkan.',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'assigned_to' => $developer->id,
        ]);

        $this->actingAs($developer)->post(route('tickets.activities.store', $ticket), [
            'content' => 'Pengerjaan telah mencapai tahap pengujian.',
            'activity_type' => 'progress_note',
        ])->assertRedirect(route('tickets.show', $ticket));

        $notification = $this->requester->unreadNotifications()->firstOrFail();
        $this->assertSame('progress_added', $notification->data['event']);
    }

    public function test_scheduled_delegation_notifies_delegate_when_it_becomes_active(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261111-9996',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Hardware',
            'title' => 'Approval tertunda',
            'description' => 'Tiket menunggu saat delegasi terjadwal mulai berlaku.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);
        $delegation = ApprovalDelegation::create([
            'delegator_id' => $this->supervisor->id,
            'delegate_id' => $this->delegate->id,
            'created_by' => $this->supervisor->id,
            'scope' => ApprovalDelegation::SCOPE_SUPERVISOR,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
            'reason' => 'Cuti terjadwal',
            'is_active' => true,
        ]);

        $this->assertSame(0, $this->delegate->unreadNotifications()->count());
        $this->assertSame(0, Artisan::call('delegations:activate'));

        $notification = $this->delegate->unreadNotifications()->firstOrFail();
        $this->assertSame($ticket->id, $notification->data['ticket_id']);
        $this->assertNotNull($delegation->fresh()->notified_at);
    }

    private function submitTicket(): Ticket
    {
        $this->actingAs($this->requester)->post(route('tickets.store'), [
            'title' => 'Permintaan perangkat kerja',
            'category' => 'Hardware',
            'priority' => 'Medium',
            'description' => 'Memerlukan perangkat tambahan untuk pekerjaan operasional harian.',
        ])->assertRedirect();

        return Ticket::where('requester_id', $this->requester->id)->latest('id')->firstOrFail();
    }
}
