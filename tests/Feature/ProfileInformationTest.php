<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Weekday;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Service\TimezoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_profile_information_succeeds(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get('/user/profile');

        // Assert
        $response->assertSuccessful();
    }

    public function test_profile_information_can_be_updated(): void
    {
        // Arrange
        $user = User::factory()->create();
        $timezone = app(TimezoneService::class)->getTimezones()[0];
        $this->actingAs($user);

        // Act
        $response = $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'timezone' => $timezone,
            'week_start' => Weekday::Sunday->value,
        ]);

        // Assert
        $response->assertValid(errorBag: 'updateProfileInformation');
        $user = $user->fresh();
        $this->assertEquals('Test Name', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals($timezone, $user->timezone);
        $this->assertEquals(Weekday::Sunday, $user->week_start);
    }

    public function test_profile_information_can_be_updated_via_post_with_method_spoofing(): void
    {
        $user = User::factory()->create();
        $timezone = app(TimezoneService::class)->getTimezones()[0];
        $this->actingAs($user);

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/user/profile-information', [
                '_method' => 'PUT',
                'name' => 'Spoofed Put Name',
                'email' => $user->email,
                'timezone' => $timezone,
                'week_start' => Weekday::Sunday->value,
            ]);

        $response->assertValid(errorBag: 'updateProfileInformation');
        $this->assertSame('Spoofed Put Name', $user->fresh()->name);
    }
}
