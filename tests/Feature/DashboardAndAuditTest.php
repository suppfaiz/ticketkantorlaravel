<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;
    protected User $supervisor;
    protected User $itHead;
    protected User $developer;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'General Affairs', 'code' => 'GA']);

        $this->supervisor = User::factory()->create([
            'name' => 'Supervisor GA',
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $dept->id,
        ]);

        $this->requester = User::factory()->create([
            'name' => 'Staff GA',
            'role' => User::ROLE_REQUESTER,
            'department_id' => $dept->id,
            'supervisor_id' => $this->supervisor->id,
        ]);

        $this->itHead = User::factory()->create([
            'name' => 'Kepala IT',
            'role' => User::ROLE_IT_HEAD,
        ]);

        $this->developer = User::factory()->create([
            'name' => 'Teknisi IT',
            'role' => User::ROLE_IT_DEVELOPER,
        ]);
    }

    public function test_requester_dashboard_displays_correct_metrics(): void
    {
        Ticket::create([
            'ticket_number' => 'TICK-20261028-0060',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Hardware',
            'title' => 'Penggantian Kabel LAN',
            'description' => 'Kabel LAN putus digigit tikus.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $response = $this->actingAs($this->requester)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.requester');
        $response->assertSee('Dashboard Karyawan');
        $response->assertSee('Penggantian Kabel LAN');
    }

    public function test_supervisor_dashboard_displays_subordinate_pending_queue(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0061',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Hardware',
            'title' => 'Perbaikan Printer GA',
            'description' => 'Paper jam terus-menerus.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $response = $this->actingAs($this->supervisor)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.supervisor');
        $response->assertSee('Dashboard Atasan Langsung');
        $response->assertSee('Perbaikan Printer GA');
    }

    public function test_it_head_dashboard_displays_actionable_tickets(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0062',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Pengembangan Sistem Pengarsipan Digital',
            'description' => 'Perlu sistem arsip scan dokumen.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        $response = $this->actingAs($this->itHead)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.it_head');
        $response->assertSee('Dashboard Kepala Bagian IT');
        $response->assertSee('Pengembangan Sistem Pengarsipan Digital');
    }

    public function test_it_head_dashboard_displays_major_ticket_waiting_meeting(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0099',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'title' => 'Pengembangan Sistem Core Banking',
            'description' => 'Upgrade core banking ke versi cloud.',
            'status' => Ticket::STATUS_WAITING_MAJOR_MEETING,
        ]);

        $response = $this->actingAs($this->itHead)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.it_head');
        $response->assertSee('Pengembangan Sistem Core Banking');
        $response->assertSee('Setting Rapat');
    }

    public function test_it_developer_dashboard_displays_open_queue_and_active_work(): void
    {
        Ticket::create([
            'ticket_number' => 'TICK-20261028-0063',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'title' => 'Reset Password Admin GA',
            'description' => 'Lupa password akun lokal.',
            'status' => Ticket::STATUS_READY_FOR_WORK,
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($this->developer)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.it_developer');
        $response->assertSee('Dashboard Tim IT');
        $response->assertSee('Reset Password Admin GA');
        $response->assertSee('Ambil Tiket');
    }

    public function test_it_developer_dashboard_shows_active_work_as_dismissible_notification(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0067',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Perbaikan Aplikasi Internal',
            'description' => 'Tiket sedang dikerjakan teknisi.',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'assigned_to' => $this->developer->id,
        ]);

        $this->actingAs($this->developer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pekerjaan Anda menunggu')
            ->assertSee('Tutup notifikasi pekerjaan')
            ->assertSee(route('tickets.show', $ticket))
            ->assertSee(route('tickets.index', ['developer_queue' => 'active']))
            ->assertDontSee('Lanjutkan Kerja');
    }

    public function test_audit_logs_are_immutable(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0064',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Hardware',
            'title' => 'Test Audit Immutability',
            'description' => 'Testing audit log tampering protection.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $log = $ticket->auditLogs()->create([
            'actor_id' => $this->requester->id,
            'action' => 'SUBMIT_TICKET',
            'to_status' => Ticket::STATUS_WAITING_SUPERVISOR,
            'remarks' => 'Pengajuan awal',
        ]);

        // Attempting to update or delete throws RuntimeException
        $this->expectException(\RuntimeException::class);
        $log->delete();
    }

    public function test_kadiv_it_dashboard_displays_major_loa_queue(): void
    {
        $kadiv = User::factory()->create(['role' => User::ROLE_KADIV_IT]);

        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0065',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'title' => 'Pengembangan Sistem ERP Regional',
            'description' => 'Sistem ERP untuk seluruh cabang.',
            'status' => Ticket::STATUS_WAITING_LOA_KADIV_IT,
        ]);

        $response = $this->actingAs($kadiv)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.kadiv_it');
        $response->assertSee('Dashboard Kepala Divisi IT dan Operasional');
        $response->assertSee('Pengembangan Sistem ERP Regional');
    }

    public function test_direksi_dashboard_displays_major_loa_queue(): void
    {
        $direksi = User::factory()->create(['role' => User::ROLE_DIREKSI]);

        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0066',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'title' => 'Transformasi Digital Korporat',
            'description' => 'Inisiatif strategis direksi.',
            'status' => Ticket::STATUS_WAITING_LOA_DIREKSI,
        ]);

        $response = $this->actingAs($direksi)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.direksi');
        $response->assertSee('Dashboard Dewan Direksi');
        $response->assertSee('Transformasi Digital Korporat');
    }
}
