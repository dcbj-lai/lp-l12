<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_card_has_drag_flip_and_qr_side(): void
    {
        User::factory()->create([
            'name' => 'Jane Employee',
            'preferred_name' => 'Jane Employee',
            'email' => 'jane@example.com',
            'position' => 'Teacher',
        ]);

        $this->get(route('card.show', 'jane-employee'))
            ->assertOk()
            ->assertDontSee('Show QR')
            ->assertDontSee('Show Card')
            ->assertSee('data:image/svg+xml;base64')
            ->assertSee('https://lp.life.edu.ph/card/jane-employee')
            ->assertSee('pointerdown')
            ->assertSee('pointermove')
            ->assertSee('Learn and Live Fully.');
    }
}
