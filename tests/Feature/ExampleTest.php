<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
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
            ->assertSee('Scan. Pickup. Reward.');
    }

    public function test_customer_pages_load(): void
    {
        foreach (['/facilities', '/pickup', '/learn', '/rewards', '/about', '/contact', '/terms', '/login', '/signup', '/forgot-password'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_demo_login_opens_workspace(): void
    {
        config(['services.demo_login.enabled' => true]);

        $this->post('/demo-login')->assertRedirect('/admin');

        $this->assertAuthenticated();
        $this->assertAuthenticatedAs(User::where('email', 'demo@ecocycle.test')->first());
    }

    public function test_demo_login_is_disabled_by_default(): void
    {
        $this->post('/demo-login')->assertNotFound();

        $this->assertGuest();
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        Role::create(['name' => 'admin', 'label' => 'Admin']);
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'Password123',
            'role' => 'admin',
        ]);
        $user->assignRole('admin');

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123',
        ])->assertRedirect('/admin');
    }

    public function test_admin_dashboard_uses_admin_only_shell(): void
    {
        Role::create(['name' => 'admin', 'label' => 'Admin']);
        $user = User::factory()->create(['role' => 'admin']);
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Admin Console')
            ->assertSee('Control platform access and approvals.')
            ->assertDontSee('E-waste made simple')
            ->assertDontSee('Responsible recycling platform.');

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Admin Console')
            ->assertDontSee('E-waste made simple');

        $this->actingAs($user)
            ->get('/shop')
            ->assertForbidden();
    }

    public function test_user_can_register_verify_and_logout(): void
    {
        $response = $this->post('/signup', [
            'name' => 'Eco User',
            'email' => 'eco@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('user_settings', ['user_id' => auth()->id()]);
        $this->assertDatabaseHas('email_verification_codes', ['user_id' => auth()->id()]);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_protected_dashboard_requires_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_role_updates_replace_previous_role_permissions(): void
    {
        $admin = Role::create(['name' => 'admin', 'label' => 'Admin']);
        $customer = Role::create(['name' => 'customer', 'label' => 'Customer']);
        $user = User::factory()->create(['role' => 'customer']);

        $user->assignRole('admin');
        $this->assertTrue($user->hasRole('admin'));

        $user->assignRole('customer');

        $this->assertFalse($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('customer'));
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $user->id,
            'role_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $customer->id,
        ]);
    }

    public function test_customer_request_creates_visible_notification(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)
            ->post('/customer/recycling-requests', [
                'category' => 'Laptop',
                'brand' => 'Dell',
                'model' => 'Inspiron 15',
                'condition' => 'working',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'request',
            'title' => 'Device submitted',
        ]);
        $this->assertDatabaseCount('notifications', 0);
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

        $this->assertDatabaseCount('pickup_requests', 1);
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

    public function test_global_search_returns_facilities_devices_and_actions(): void
    {
        $response = $this->getJson('/api/search?q=laptop');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['type', 'title', 'subtitle', 'url', 'icon'],
                ],
            ]);
    }

    public function test_jwt_login_flow_issues_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123',
            'device_name' => 'Feature test',
        ]);

        $token = $response
            ->assertOk()
            ->assertJsonStructure(['data' => ['tokens' => ['access_token', 'refresh_token']]])
            ->json('data.tokens.access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'api@example.com');
    }
}
