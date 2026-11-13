<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserReviewClosureTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;

    protected User $supervisor;

    protected User $developer;

    protected User $otherRequester;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'Accounting', 'code' => 'ACC']);

        $this->supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $dept->id,
        ]);

        $this->requester = User::factory()->create([
            'role' => User::ROLE_REQUESTER,
            'department_id' => $dept->id,
            'supervisor_id' => $this->supervisor->id,
        ]);

        $this->otherRequester = User::factory()->create([
            'role' => User::ROLE_REQUESTER,
            'department_id' => $dept->id,
        ]);

        $this->developer = User::factory()->create([
            'name' => 'Developer Penanggung Jawab',
            'role' => User::ROLE_IT_DEVELOPER,
        ]);
    }

    public function test_review_endpoints_reject_ticket_before_user_review_without_saving_feedback(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0049',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'title' => 'Tiket belum selesai dikerjakan',
            'description' => 'Pemohon belum boleh memberikan ulasan.',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'assigned_to' => $this->developer->id,
        ]);

        $this->actingAs($this->requester)
            ->post(route('tickets.user-review', $ticket), ['action' => 'accept', 'rating' => 5])
            ->assertForbidden();
        $this->post(route('tickets.reviews.accept', $ticket), ['rating' => 5])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_reviews', 0);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
    }

    public function test_requester_can_accept_and_close_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0050',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'title' => 'Perbaikan Format Cetak Faktur',
            'description' => 'Garis pemisah tabel tidak rata saat print.',
            'status' => Ticket::STATUS_WAITING_USER_REVIEW,
            'assigned_to' => $this->developer->id,
            'work_completed_at' => now(),
        ]);

        $response = $this->actingAs($this->requester)->post(route('tickets.reviews.accept', $ticket), [
            'rating' => 5,
            'feedback' => 'Hasil cetak sudah sangat rapi dan sesuai kebutuhan.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $fresh = $ticket->fresh();

        $this->assertSame(Ticket::STATUS_COMPLETED, $fresh->status);
        $this->assertNotNull($fresh->closed_at);

        $this->assertDatabaseHas('ticket_reviews', [
            'ticket_id' => $ticket->id,
            'reviewer_id' => $this->requester->id,
            'decision' => TicketReview::DECISION_ACCEPT,
            'rating' => 5,
        ]);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->requester->id,
            'action' => 'USER_REVIEW_ACCEPT',
            'to_status' => Ticket::STATUS_COMPLETED,
        ]);
    }

    public function test_requester_can_request_revision_and_developer_remains_assigned(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0051',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'title' => 'Penambahan Filter Tanggal Transaksi',
            'description' => 'Filter tanggal awal dan akhir.',
            'status' => Ticket::STATUS_WAITING_USER_REVIEW,
            'assigned_to' => $this->developer->id,
            'work_completed_at' => now(),
        ]);

        $response = $this->actingAs($this->requester)->post(route('tickets.reviews.revise', $ticket), [
            'feedback' => 'Filter tanggal akhir masih belum mengikutsertakan jam 23:59:59.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $fresh = $ticket->fresh();

        $this->assertSame(Ticket::STATUS_REVISION_WORK, $fresh->status);

        // INVARIANT FR-020: Developer assignment remains intact!
        $this->assertSame($this->developer->id, $fresh->assigned_to);

        $this->assertDatabaseHas('ticket_reviews', [
            'ticket_id' => $ticket->id,
            'reviewer_id' => $this->requester->id,
            'decision' => TicketReview::DECISION_REVISE,
        ]);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->requester->id,
            'action' => 'USER_REVIEW_REQUEST_REVISION',
            'to_status' => Ticket::STATUS_REVISION_WORK,
        ]);
    }

    public function test_non_requester_cannot_review_or_close_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0052',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'title' => 'Update Modul Budgeting',
            'description' => 'Update rumus alokasi.',
            'status' => Ticket::STATUS_WAITING_USER_REVIEW,
            'assigned_to' => $this->developer->id,
        ]);

        // Developer tries to approve own work
        $responseDev = $this->actingAs($this->developer)->post(route('tickets.reviews.accept', $ticket), [
            'rating' => 5,
        ]);
        $responseDev->assertForbidden();

        // Other requester tries to review
        $responseOther = $this->actingAs($this->otherRequester)->post(route('tickets.reviews.accept', $ticket), [
            'rating' => 5,
        ]);
        $responseOther->assertForbidden();

        // Supervisor tries to review
        $responseSupervisor = $this->actingAs($this->supervisor)->post(route('tickets.reviews.accept', $ticket), [
            'rating' => 5,
        ]);
        $responseSupervisor->assertForbidden();

        $this->assertSame(Ticket::STATUS_WAITING_USER_REVIEW, $ticket->fresh()->status);
    }
}
