<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sustainability_dashboard_loads(): void
    {
        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertSee('EcoCycle Smart')
            ->assertSee('Recycle electronics safely. Earn rewards.');
    }

    public function test_customer_pages_load(): void
    {
        foreach (['/facilities', '/pickup', '/learn', '/rewards', '/about', '/contact', '/login', '/signup'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_user_can_register_and_logout(): void
    {
        $response = $this->post('/signup', [
            'name' => 'Eco User',
            'email' => 'eco@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_device_analysis_returns_materials_and_awareness_data(): void
    {
        $response = $this->postJson('/api/devices/analyze', [
            'model_name' => 'iPhone 11',
            'condition' => 'working',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('analysis.category', 'smartphone')
            ->assertJsonStructure([
                'analysis' => [
                    'category_code',
                    'estimated_recycling_value_inr',
                    'india_compliance',
                    'prep_checklist',
                    'materials',
                    'hazards',
                    'environmental_effects',
                    'health_effects',
                    'impact',
                    'recommendation',
                    'quiz',
                ],
            ]);
    }

    public function test_india_facility_endpoint_returns_ranked_centers(): void
    {
        $response = $this->getJson('/api/facilities/nearest?lat=19.0760&lng=72.8777&limit=3');

        $response
            ->assertOk()
            ->assertJsonPath('india_mode', true)
            ->assertJsonStructure([
                'recommended' => ['city', 'state', 'distance_km', 'match_score'],
                'facilities' => [
                    '*' => ['name', 'city', 'state', 'pickup_available', 'certificate_supported'],
                ],
                'coverage' => ['centers', 'states', 'pickup_enabled', 'certificate_enabled'],
            ]);
    }

    public function test_pickup_planner_returns_booking_preview(): void
    {
        $response = $this->postJson('/api/pickups/schedule', [
            'model_name' => 'Old power bank',
            'city' => 'Mumbai',
            'pincode' => '400001',
            'preferred_window' => 'Tomorrow morning',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'booking_id',
                'facility' => ['name', 'city', 'state'],
                'points_preview',
                'prep_checklist',
            ]);
    }

    public function test_recycling_completion_creates_certificate(): void
    {
        $response = $this->postJson('/api/recycling/complete', [
            'model_name' => 'Dell Inspiron Laptop',
            'condition' => 'not working',
            'holder_name' => 'Test Recycler',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'activity',
                'certificate' => ['number', 'download_url', 'verify_url', 'share_text'],
                'wallet' => ['points_added'],
            ]);

        $this->assertDatabaseCount('recycling_activities', 1);
        $this->assertDatabaseCount('recycling_certificates', 1);

        $this->get($response->json('certificate.verify_url'))->assertOk();
        $this->get($response->json('certificate.download_url'))->assertOk();
    }
}
