<?php

namespace Tests\Unit;

use App\Http\Controllers\RequestController;
use App\Models\User;
use Tests\TestCase;

class RequestControllerCalendarNameTest extends TestCase
{
    public function test_calendar_display_name_prefers_preferred_name(): void
    {
        $controller = $this->controller();
        $user = new User([
            'name' => 'Donald Balbieran',
            'preferred_name' => 'Don',
        ]);

        $this->assertSame('Don', $controller->displayName($user));
    }

    public function test_calendar_display_name_falls_back_to_full_name_then_name(): void
    {
        $controller = $this->controller();

        $fullNameUser = new User(['name' => 'Fallback Name']);
        $fullNameUser->setAttribute('full_name', 'Donald Balbieran');

        $nameOnlyUser = new User(['name' => 'Fallback Name']);

        $this->assertSame('Donald Balbieran', $controller->displayName($fullNameUser));
        $this->assertSame('Fallback Name', $controller->displayName($nameOnlyUser));
    }

    private function controller(): object
    {
        return new class extends RequestController {
            public function displayName(?User $user): string
            {
                return $this->calendarDisplayName($user);
            }
        };
    }
}
