<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Database\Seeders\BlogDraftSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BlogPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_drafts_are_hidden_until_an_admin_publishes_them(): void
    {
        $this->seed(BlogDraftSeeder::class);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('Essays are being prepared')
            ->assertDontSee('Ring Composition in the Quran');

        $this->get(route('blog.show', 'ring-composition-in-the-quran'))->assertNotFound();
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('blog.index'))
            ->assertDontSee('ring-composition-in-the-quran');

        $this->assertSame(4, BlogPost::draft()->count());

        $ring = BlogPost::where('slug', 'ring-composition-in-the-quran')->firstOrFail();
        $this->assertTrue($ring->links->contains('url', '/patterns/2/255'));
        $this->assertTrue($ring->links->contains('url', 'https://quran.com/2/255'));
        $this->assertSame('quran', $ring->category->slug);
        $this->assertNotNull($ring->thumbnail_path);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->post(route('admin.blog.publish', $ring))
            ->assertRedirect();

        $this->get(route('blog.show', $ring))
            ->assertOk()
            ->assertSee('Ayat al-Kursi')
            ->assertSee('https://quran.com/2/255')
            ->assertSee('rel="noopener noreferrer"', false);

        $this->get(route('blog.category', 'quran'))
            ->assertOk()
            ->assertSee('Ring Composition in the Quran');

        $this->get(route('sitemap'))->assertSee('ring-composition-in-the-quran');
    }

    public function test_admin_can_save_a_draft_and_a_member_cannot_open_the_editor(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.blog.store'), [
            'title' => 'A study note',
            'excerpt' => 'A short note kept private until it is ready.',
            'body' => "First paragraph.\n\nSecond paragraph.",
            'status' => 'draft',
            'new_category' => 'Quran',
            'tags' => 'Study method, Numbers',
            'links' => "Patterns | /patterns\nQuran.com | https://quran.com/2/255",
        ])->assertRedirect(route('admin.blog.index'));

        $post = BlogPost::where('slug', 'a-study-note')->firstOrFail();
        $this->assertSame(BlogPost::STATUS_DRAFT, $post->status);
        $this->assertSame('Quran', $post->category->name);
        $this->assertTrue($post->links->contains('kind', 'external'));
        $this->assertStringContainsString('<p>First paragraph.</p>', $post->body);

        $this->actingAs($admin)
            ->get(route('admin.blog.preview', $post))
            ->assertOk()
            ->assertSee('This is a preview')
            ->assertSee('First paragraph');

        $member = User::factory()->create(['is_admin' => false]);
        $this->actingAs($member)
            ->get(route('admin.blog.index'))
            ->assertForbidden();
    }

    public function test_admin_generates_private_drafts_from_topics(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $payload = $request->data();
            $prompt = $payload['contents'][0]['parts'][0]['text'] ?? '';
            preg_match('/Topic: (.+)/', $prompt, $matches);
            $topic = trim($matches[1] ?? 'Topic');

            return Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'title' => $topic,
                                'excerpt' => 'A short study note on '.$topic.'.',
                                'body' => '<p>Read this in <a href="/patterns">Patterns</a>.</p><script>alert(1)</script><p><a href="https://evil.example/x">Leave</a></p>',
                                'category' => 'Quran',
                                'tags' => ['Study', 'Quran'],
                                'links' => [
                                    ['label' => 'Patterns', 'url' => '/patterns'],
                                    ['label' => 'Ayat al-Kursi', 'url' => 'https://quran.com/2/255'],
                                    ['label' => 'Elsewhere', 'url' => 'https://evil.example/x'],
                                ],
                                'seo_title' => $topic,
                                'meta_description' => 'A study note.',
                            ]),
                        ]],
                    ],
                ]],
            ]);
        });

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.blog.index'))
            ->assertOk()
            ->assertSee('Generate drafts from topics');

        $this->actingAs($admin)
            ->post(route('admin.blog.generate'), [
                'topics' => "Patience in the Quran\nThe Night of Power",
            ])
            ->assertRedirect(route('admin.blog.index', ['status' => 'draft']));

        $this->assertSame(2, BlogPost::draft()->count());

        $post = BlogPost::where('slug', 'patience-in-the-quran')->firstOrFail();
        $this->assertStringNotContainsString('<script>', $post->body);
        $this->assertStringContainsString('href="/patterns"', $post->body);
        $this->assertStringContainsString('href="#"', $post->body);
        $this->assertTrue($post->links->contains('url', '/patterns'));
        $this->assertTrue($post->links->contains('url', 'https://quran.com/2/255'));
        $this->assertFalse($post->links->contains('url', 'https://evil.example/x'));
        $this->get(route('blog.show', $post))->assertNotFound();

        $this->actingAs($admin)
            ->post(route('admin.blog.generate'), [
                'topics' => 'Patience in the Quran',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $member = User::factory()->create(['is_admin' => false]);
        $this->actingAs($member)
            ->post(route('admin.blog.generate'), ['topics' => 'Another topic'])
            ->assertForbidden();
    }
}
