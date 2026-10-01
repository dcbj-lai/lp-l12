<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreapprovedVisitorTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_download_preapproved_visitor_csv_template(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('visitors.preapproved.template'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();

        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));
        $this->assertSame([
            ['name', 'email'],
            ['Juan Dela Cruz', 'juan.delacruz@example.com'],
            ['Maria Santos', 'maria.santos@example.com'],
        ], $rows);
    }
}
