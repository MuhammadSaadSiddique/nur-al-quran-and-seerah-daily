<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\QuranicLensAnalysis;
use App\Mail\WeeklyResearchPromoMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WeeklyResearchPromoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function command_skips_sending_emails_if_no_new_approved_research_exists()
    {
        Mail::fake();

        // Create a user
        User::factory()->create([
            'email' => 'user@example.com',
        ]);

        // Run the console command
        $this->artisan('send:weekly-research-promo')
            ->expectsOutput('No new research content has been approved in the last 7 days. Promotional email skipped.')
            ->assertExitCode(0);

        Mail::assertNotSent(WeeklyResearchPromoMail::class);
    }

    /** @test */
    public function command_sends_weekly_promo_email_to_registered_users_when_new_research_exists()
    {
        Mail::fake();

        // Create a user
        $user = User::factory()->create([
            'email' => 'subscriber@example.com',
            'display_name' => 'Subscriber User',
        ]);

        // Create an approved research item within the last 7 days
        $analysis = QuranicLensAnalysis::create([
            'user_id' => $user->id,
            'chapter_number' => 1,
            'verse_number' => 1,
            'lens_type' => 'science',
            'title' => 'Quantum Fluctuations',
            'content' => 'Deep analysis of quantum parallel mappings.',
            'status' => 'approved',
            'created_at' => now(),
        ]);

        // Run the console command
        $this->artisan('send:weekly-research-promo')
            ->expectsOutput("Sending weekly promotional email to 1 users containing 1 new research items...")
            ->expectsOutput("Weekly promotional email sending completed.")
            ->assertExitCode(0);

        // Assert mail was sent to the user
        Mail::assertSent(WeeklyResearchPromoMail::class, function ($mail) use ($user, $analysis) {
            return $mail->hasTo($user->email) &&
                   $mail->analyses->contains($analysis) &&
                   $mail->user->id === $user->id;
        });
    }

    /** @test */
    public function mailable_renders_correct_subject_and_view_content()
    {
        // Create user and analysis
        $user = User::factory()->make([
            'email' => 'recipient@example.com',
            'display_name' => 'Recipient Name',
        ]);

        $analysis = new QuranicLensAnalysis([
            'chapter_number' => 2,
            'verse_number' => 255,
            'lens_type' => 'history',
            'title' => 'Historical Significance',
            'content' => 'Full text describing the historical context of the verse.',
        ]);

        $analyses = collect([$analysis]);
        $mailable = new WeeklyResearchPromoMail($analyses, $user);

        // Assert subject
        $mailable->assertHasSubject('Weekly Research Highlights - The Eternal Echo');

        // Render HTML content
        $html = $mailable->render();

        // Assert content details are present in the HTML template
        $this->assertStringContainsString('Assalamu Alaikum Recipient Name', $html);
        $this->assertStringContainsString('HISTORY LENS', $html);
        $this->assertStringContainsString('Historical Significance', $html);
        $this->assertStringContainsString('Full text describing the historical', $html);
        $this->assertStringContainsString('/2/255', $html);
    }
}
