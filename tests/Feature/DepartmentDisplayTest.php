<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_assignment_is_visible_in_user_and_ticket_lists(): void
    {
        $department = Department::create(['code' => 'ALT', 'name' => 'Alternate Distribution']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $requester = User::factory()->create(['name' => 'Amanda E', 'role' => User::ROLE_REQUESTER]);

        Ticket::create([
            'ticket_number' => 'TICK-DEPT-0001',
            'requester_id' => $requester->id,
            'category' => 'Software',
            'title' => 'Permintaan Akses Aplikasi',
            'description' => 'Permintaan akses untuk pekerjaan operasional.',
            'status' => Ticket::STATUS_DRAFT,
        ]);

        $this->actingAs($admin)->patch(route('admin.users.update', $requester), [
            'name' => 'Amanda E',
            'email' => $requester->email,
            'role' => User::ROLE_REQUESTER,
            'department_id' => $department->id,
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame($department->id, $requester->fresh()->department_id);
        $this->assertSame('Alternate Distribution', $requester->fresh()->department);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Alternate Distribution');

        $this->actingAs($requester)->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Amanda E')
            ->assertSee('Alternate Distribution')
            ->assertDontSee('Tanpa Departemen');
    }
}
