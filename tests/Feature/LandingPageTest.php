<?php

namespace Tests\Feature;

use App\Models\DemoRequest;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_landing_page_with_portal_login_button(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('DenFin');
        $response->assertSee('ERP');
        $response->assertSee('Login to Portal');
        $response->assertSee(route('login'));
        $response->assertSee('Purchase');
        $response->assertSee('Sales');
        $response->assertSee('POS');
        $response->assertSee('Inventory');
        $response->assertSee('HR');
        $response->assertSee('Auto Lube Station');
        $response->assertSee('Drum Decanting');
        $response->assertSee('Pit Inspection');
        $response->assertDontSee('Flexible Pricing for Every Business Size');
    }

    public function test_authenticated_user_sees_go_to_dashboard_link(): void
    {
        $shop = Shop::first();
        $user = User::factory()->create([
            'role' => 'super_admin',
            'shop_id' => $shop?->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Go to Dashboard');
        $response->assertSee(route('dashboard'));
    }

    public function test_guest_can_submit_demo_request_form(): void
    {
        $payload = [
            'name' => 'Muhammad Ahmed',
            'business_name' => 'Ahmed Auto Care & Lube',
            'business_type' => 'Auto Lube Station',
            'phone' => '03009876543',
            'email' => 'ahmed@autocare.pk',
            'city' => 'Lahore',
            'preferred_plan' => 'Auto Lube Pro',
            'message' => 'Need POS with drum decanting and oil stickers.',
        ];

        $response = $this->post(route('demo.request'), $payload);

        $response->assertRedirect(url('/#demo-section'));
        $response->assertSessionHas('demo_success');

        $this->assertDatabaseHas('demo_requests', [
            'name' => 'Muhammad Ahmed',
            'business_name' => 'Ahmed Auto Care & Lube',
            'business_type' => 'Auto Lube Station',
            'phone' => '03009876543',
            'email' => 'ahmed@autocare.pk',
            'city' => 'Lahore',
            'preferred_plan' => 'Auto Lube Pro',
            'status' => 'pending',
        ]);
    }

    public function test_demo_request_ajax_submission_returns_json(): void
    {
        $payload = [
            'name' => 'Zubair Khan',
            'business_name' => 'Khan Oil Traders',
            'business_type' => 'Retail Spare Parts & POS',
            'phone' => '03215551234',
            'preferred_plan' => 'Starter Retail POS',
        ];

        $response = $this->postJson(route('demo.request'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'whatsapp_url',
            'inquiry_id',
        ]);

        $this->assertDatabaseHas('demo_requests', [
            'name' => 'Zubair Khan',
            'business_name' => 'Khan Oil Traders',
            'phone' => '03215551234',
        ]);
    }

    public function test_demo_request_validation_fails_on_missing_required_fields(): void
    {
        $response = $this->post(route('demo.request'), [
            'name' => '',
            'business_name' => '',
            'phone' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'business_name', 'phone']);
    }
}
