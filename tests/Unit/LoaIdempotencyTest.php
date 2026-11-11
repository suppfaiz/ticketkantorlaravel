<?php

namespace Tests\Unit;

use Tests\TestCase;

class LoaIdempotencyTest extends TestCase
{
    public function test_idempotency_key_generation_is_deterministic(): void
    {
        $ticketId = 101;
        $approvalLevel = 'supervisor';
        $ticketNumber = 'TICK-20261028-0001';

        $key1 = hash('sha256', "LOA_{$ticketId}_{$approvalLevel}_{$ticketNumber}");
        $key2 = hash('sha256', "LOA_{$ticketId}_{$approvalLevel}_{$ticketNumber}");

        $this->assertEquals($key1, $key2);
        $this->assertEquals(64, strlen($key1));
    }

    public function test_different_approval_levels_produce_different_idempotency_keys(): void
    {
        $ticketId = 101;
        $ticketNumber = 'TICK-20261028-0001';

        $keySupervisor = hash('sha256', "LOA_{$ticketId}_supervisor_{$ticketNumber}");
        $keyKadiv = hash('sha256', "LOA_{$ticketId}_kadiv_it_ops_{$ticketNumber}");
        $keyDireksi = hash('sha256', "LOA_{$ticketId}_direksi_{$ticketNumber}");

        $this->assertNotEquals($keySupervisor, $keyKadiv);
        $this->assertNotEquals($keyKadiv, $keyDireksi);
    }
}
