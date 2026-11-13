<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperTicketQueuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_queue_menus_separate_unassigned_active_and_finished_tickets(): void
    {
        $requester = User::factory()->create(['role' => User::ROLE_REQUESTER]);
        $developer = User::factory()->create(['role' => User::ROLE_IT_DEVELOPER]);
        $otherDeveloper = User::factory()->create(['role' => User::ROLE_IT_DEVELOPER]);

        $pool = $this->ticket('TICK-QUEUE-0001', Ticket::STATUS_READY_FOR_WORK, $requester);
        $assignedPool = $this->ticket('TICK-QUEUE-0002', Ticket::STATUS_READY_FOR_WORK, $requester, $otherDeveloper);
        $active = $this->ticket('TICK-QUEUE-0003', Ticket::STATUS_IN_PROGRESS, $requester, $developer);
        $revision = $this->ticket('TICK-QUEUE-0004', Ticket::STATUS_REVISION_WORK, $requester, $developer);
        $othersWork = $this->ticket('TICK-QUEUE-0005', Ticket::STATUS_IN_PROGRESS, $requester, $otherDeveloper);
        $waitingReview = $this->ticket('TICK-QUEUE-0006', Ticket::STATUS_WAITING_USER_REVIEW, $requester, $developer);
        $completed = $this->ticket('TICK-QUEUE-0007', Ticket::STATUS_COMPLETED, $requester, $developer);
        $othersCompleted = $this->ticket('TICK-QUEUE-0008', Ticket::STATUS_COMPLETED, $requester, $otherDeveloper);

        $this->actingAs($developer)
            ->get(route('tickets.index', ['developer_queue' => 'unassigned']))
            ->assertOk()
            ->assertSee('Tiket Belum Di-assign')
            ->assertSee($pool->ticket_number)
            ->assertSee('Ambil Tiket')
            ->assertDontSee($assignedPool->ticket_number)
            ->assertDontSee($active->ticket_number);

        $this->actingAs($developer)
            ->get(route('tickets.index', ['developer_queue' => 'active']))
            ->assertOk()
            ->assertSee('Tiket Sedang Saya Kerjakan')
            ->assertSee($active->ticket_number)
            ->assertSee($revision->ticket_number)
            ->assertDontSee($pool->ticket_number)
            ->assertDontSee($othersWork->ticket_number)
            ->assertDontSee($waitingReview->ticket_number);

        $this->actingAs($developer)
            ->get(route('tickets.index', ['developer_queue' => 'completed']))
            ->assertOk()
            ->assertSee('Tiket Selesai Dikerjakan')
            ->assertSee($waitingReview->ticket_number)
            ->assertSee($completed->ticket_number)
            ->assertDontSee($active->ticket_number)
            ->assertDontSee($othersCompleted->ticket_number);
    }

    private function ticket(string $number, string $status, User $requester, ?User $developer = null): Ticket
    {
        return Ticket::create([
            'ticket_number' => $number,
            'requester_id' => $requester->id,
            'category' => 'Software',
            'title' => 'Pengujian antrean '.$number,
            'description' => 'Memastikan menu teknisi menampilkan tiket yang sesuai.',
            'status' => $status,
            'assigned_to' => $developer?->id,
        ]);
    }
}
