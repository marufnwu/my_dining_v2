<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test country
        Country::create([
            'name' => 'Test Country',
            'dial_code' => '+1',
            'code' => 'TC'
        ]);
    }

    /** @test */
    public function user_can_get_their_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'city',
                        'gender'
                    ],
                    'profile_completion',
                    'last_updated'
                ]
            ]);
    }

    /** @test */
    public function user_can_update_their_profile()
    {
        $user = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'city' => 'Updated City',
            'gender' => 'male'
        ];

        $response = $this->actingAs($user)
            ->putJson('/api/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => 'Updated Name',
                        'city' => 'Updated City',
                        'gender' => 'male'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'city' => 'Updated City',
            'gender' => 'male'
        ]);
    }

    /** @test */
    public function user_cannot_access_profile_without_authentication()
    {
        $response = $this->getJson('/api/profile');
        $response->assertStatus(401);
    }

    /** @test */
    public function profile_update_validation_works()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson('/api/profile', [
                'gender' => 'invalid_gender'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    }

    /** @test */
    public function user_can_upload_avatar()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $response = $this->actingAs($user)
            ->postJson('/api/profile/avatar', [
                'avatar' => $file
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['photo_url']
            ]);

        // Check that user's photo_url was updated
        $user->refresh();
        $this->assertNotNull($user->photo_url);
    }

    /** @test */
    public function user_can_remove_avatar()
    {
        $user = User::factory()->create([
            'photo_url' => '/storage/avatars/test.jpg'
        ]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/profile/avatar');

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNull($user->photo_url);
    }

    /** @test */
    public function avatar_upload_validation_works()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/profile/avatar', [
                'avatar' => 'not_a_file'
            ]);

        $response->assertStatus(422);
    }
}
