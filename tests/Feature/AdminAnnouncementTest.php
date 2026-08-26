<?php

namespace Tests\Feature;

use App\Models\User;
use App\Mail\AdminAnnouncementMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function non_admin_cannot_access_announcements_composer()
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.announcements.index'));
        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function guest_cannot_access_announcements_composer()
    {
        $response = $this->get(route('admin.announcements.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_access_announcements_composer()
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.announcements.index'));
        $response->assertStatus(200);
        $response->assertSee('Compose Announcement');
        $response->assertSee('Send Announcement to All Users');
    }

    /** @test */
    public function admin_can_send_announcement_to_all_registered_users()
    {
        Mail::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'email' => 'admin@example.com',
        ]);

        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.announcements.send'), [
            'subject' => 'Important Maintenance Notice',
            'content' => 'The server will undergo maintenance for 2 hours tonight.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Announcement has been successfully sent to 3 users.');

        Mail::assertSent(AdminAnnouncementMail::class, 3);
        Mail::assertSent(AdminAnnouncementMail::class, function ($mail) use ($user1) {
            return $mail->hasTo($user1->email) &&
                   $mail->announcementSubject === 'Important Maintenance Notice' &&
                   $mail->announcementContent === 'The server will undergo maintenance for 2 hours tonight.';
        });
    }

    /** @test */
    public function announcement_validation_fails_for_empty_data()
    {
        Mail::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.announcements.send'), [
            'subject' => '',
            'content' => 'Short',
        ]);

        $response->assertSessionHasErrors(['subject', 'content']);
        Mail::assertNothingSent();
    }
}
