<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_send_and_fetch_persistent_global_chat_messages(): void
    {
        $requester = User::factory()->create(['role' => 'requester', 'name' => 'Ahmad Pemohon']);
        $itDev = User::factory()->create(['role' => 'it_developer', 'name' => 'Dedi Developer']);

        $postResponse = $this->actingAs($requester)->postJson(route('chat.store'), [
            'message' => 'Halo tim IT Helpdesk, mohon bantuan terkait VPN.',
        ]);

        $postResponse->assertCreated()
            ->assertJsonPath('message.message', 'Halo tim IT Helpdesk, mohon bantuan terkait VPN.')
            ->assertJsonPath('message.sender_id', $requester->id)
            ->assertJsonPath('message.receiver_id', null);

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $requester->id,
            'receiver_id' => null,
            'message' => 'Halo tim IT Helpdesk, mohon bantuan terkait VPN.',
        ]);

        $getResponse = $this->actingAs($itDev)->getJson(route('chat.index'));
        $getResponse->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.message', 'Halo tim IT Helpdesk, mohon bantuan terkait VPN.');
    }

    public function test_authenticated_users_can_exchange_persistent_direct_messages_and_track_unread_counts(): void
    {
        $requester = User::factory()->create(['role' => 'requester', 'name' => 'Ahmad Pemohon']);
        $itHead = User::factory()->create(['role' => 'it_head', 'name' => 'Budi Kabag IT']);

        $this->actingAs($requester)->postJson(route('chat.store'), [
            'message' => 'Selamat siang Pak Budi, izin konfirmasi jadwal rapat Major.',
            'receiver_id' => $itHead->id,
        ])->assertCreated();

        // Check unread count for IT Head before opening direct chat
        $initialFetch = $this->actingAs($itHead)->getJson(route('chat.index'));
        $initialFetch->assertOk()
            ->assertJsonPath('unread_total', 1);

        // Open direct chat with requester -> marks message as read and returns the conversation
        $directFetch = $this->actingAs($itHead)->getJson(route('chat.index', ['contact_id' => $requester->id]));
        $directFetch->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.message', 'Selamat siang Pak Budi, izin konfirmasi jadwal rapat Major.')
            ->assertJsonPath('unread_total', 0);

        $this->assertNotNull(ChatMessage::first()->read_at);
    }

    public function test_chat_messages_are_scoped_to_specific_ticket(): void
    {
        $requester = User::factory()->create(['role' => 'requester', 'name' => 'Ahmad Pemohon']);
        $supervisor = User::factory()->create(['role' => 'supervisor', 'name' => 'Siti Atasan']);
        $itDev = User::factory()->create(['role' => 'it_developer', 'name' => 'Rian Developer']);

        $ticketA = \App\Models\Ticket::create([
            'ticket_number' => 'TICK-20261105-0001',
            'requester_id' => $requester->id,
            'supervisor_id' => $supervisor->id,
            'category' => 'Software',
            'title' => 'Perbaikan Modul Laporan',
            'description' => 'Laporan bulanan gagal diunduh.',
            'status' => \App\Models\Ticket::STATUS_IN_PROGRESS,
            'assigned_to' => $itDev->id,
        ]);

        $ticketB = \App\Models\Ticket::create([
            'ticket_number' => 'TICK-20261105-0002',
            'requester_id' => $requester->id,
            'supervisor_id' => $supervisor->id,
            'category' => 'Hardware',
            'title' => 'Penggantian Mouse',
            'description' => 'Mouse rusak.',
            'status' => \App\Models\Ticket::STATUS_IN_PROGRESS,
            'assigned_to' => $itDev->id,
        ]);

        // Send message on Ticket A
        $this->actingAs($requester)->postJson(route('chat.store'), [
            'ticket_id' => $ticketA->id,
            'message' => 'Mohon update progres modul laporan untuk Tiket A.',
        ])->assertCreated()
          ->assertJsonPath('message.ticket_id', $ticketA->id)
          ->assertJsonPath('message.sender_role', 'Pemohon');

        // Send message on Ticket B
        $this->actingAs($itDev)->postJson(route('chat.store'), [
            'ticket_id' => $ticketB->id,
            'message' => 'Mouse baru untuk Tiket B sudah siap diambil.',
        ])->assertCreated()
          ->assertJsonPath('message.ticket_id', $ticketB->id)
          ->assertJsonPath('message.sender_role', 'IT Developer');

        // Fetch Ticket A chat -> only returns Ticket A's message
        $resA = $this->actingAs($itDev)->getJson(route('chat.index', ['ticket_id' => $ticketA->id]));
        $resA->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.message', 'Mohon update progres modul laporan untuk Tiket A.');

        // Fetch Ticket B chat -> only returns Ticket B's message
        $resB = $this->actingAs($requester)->getJson(route('chat.index', ['ticket_id' => $ticketB->id]));
        $resB->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.message', 'Mouse baru untuk Tiket B sudah siap diambil.');
    }

    public function test_user_can_post_forum_discussion_inside_ticket(): void
    {
        $requester = User::factory()->create(['role' => 'requester', 'name' => 'Ahmad Pemohon']);
        $supervisor = User::factory()->create(['role' => 'supervisor', 'name' => 'Siti Atasan']);
        $itDev = User::factory()->create(['role' => 'it_developer', 'name' => 'Rian Developer']);

        $ticket = \App\Models\Ticket::create([
            'ticket_number' => 'TICK-20261105-0099',
            'requester_id' => $requester->id,
            'supervisor_id' => $supervisor->id,
            'category' => 'Software',
            'title' => 'Integrasi Modul Absensi',
            'description' => 'Sinkronisasi data mesin absensi.',
            'status' => \App\Models\Ticket::STATUS_IN_PROGRESS,
            'assigned_to' => $itDev->id,
        ]);

        $response = $this->actingAs($requester)->post(route('tickets.discussions.store', $ticket), [
            'message' => 'Apakah integrasi sudah bisa diuji coba hari ini?',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket) . '#discussion');

        $this->assertDatabaseHas('chat_messages', [
            'ticket_id' => $ticket->id,
            'sender_id' => $requester->id,
            'message' => 'Apakah integrasi sudah bisa diuji coba hari ini?',
        ]);

        $showResponse = $this->actingAs($itDev)->get(route('tickets.show', $ticket));
        $showResponse->assertOk()
            ->assertSee('Forum Diskusi Tiket')
            ->assertSee('Apakah integrasi sudah bisa diuji coba hari ini?');
    }
}
