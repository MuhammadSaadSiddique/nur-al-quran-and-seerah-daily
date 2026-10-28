<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogLink;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use App\Services\BlogThumbnailService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogDraftSeeder extends Seeder
{
    public function run(): void
    {
        $quran = BlogCategory::firstOrCreate(
            ['slug' => 'quran'],
            ['name' => 'Quran', 'description' => 'Structure, wording, and how to read the mushaf.']
        );
        $human = BlogCategory::firstOrCreate(
            ['slug' => 'the-human-being'],
            ['name' => 'The Human Being', 'description' => 'The soul, worship, and the purpose of life.']
        );

        $paths = [
            'patterns' => $this->path('patterns.index'),
            'kursiPattern' => $this->path('patterns.show', ['chapter' => 2, 'verse' => 255]),
            'kursiVerse' => $this->path('lens.verse', ['chapter' => 2, 'verse' => 255]),
            'baqarah' => $this->path('lens.surah', ['chapter' => 2]),
            'reader' => $this->path('welcome'),
            'duas' => $this->path('duas.index'),
            'seerah' => $this->path('seerah'),
            'v51' => $this->path('lens.verse', ['chapter' => 51, 'verse' => 56]),
            'v67' => $this->path('lens.verse', ['chapter' => 67, 'verse' => 2]),
            'v2_30' => $this->path('lens.verse', ['chapter' => 2, 'verse' => 30]),
            'v13' => $this->path('lens.verse', ['chapter' => 13, 'verse' => 28]),
            'v12' => $this->path('lens.verse', ['chapter' => 12, 'verse' => 53]),
            'v75' => $this->path('lens.verse', ['chapter' => 75, 'verse' => 2]),
            'v89' => $this->path('lens.verse', ['chapter' => 89, 'verse' => 27]),
            'v286' => $this->path('lens.verse', ['chapter' => 2, 'verse' => 286]),
            'v94' => $this->path('lens.verse', ['chapter' => 94, 'verse' => 5]),
        ];

        $authorId = User::where('is_admin', true)->value('id');
        $thumbnails = app(BlogThumbnailService::class);

        foreach ($this->posts($quran->id, $human->id, $paths) as $draft) {
            if (BlogPost::where('slug', $draft['slug'])->exists()) {
                continue;
            }

            $post = BlogPost::create([
                'blog_category_id' => $draft['blog_category_id'],
                'author_id' => $authorId,
                'title' => $draft['title'],
                'slug' => $draft['slug'],
                'excerpt' => $draft['excerpt'],
                'body' => $draft['body'],
                'seo_title' => $draft['seo_title'],
                'meta_description' => $draft['meta_description'],
                'status' => BlogPost::STATUS_DRAFT,
            ]);

            $tagIds = [];
            foreach ($draft['tags'] as $name) {
                $tag = BlogTag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                );
                $tagIds[] = $tag->id;
            }
            $post->tags()->sync($tagIds);

            foreach ($draft['links'] as $order => $link) {
                $post->links()->create([
                    'label' => $link['label'],
                    'url' => $link['url'],
                    'kind' => str_starts_with($link['url'], '/') ? BlogLink::KIND_INTERNAL : BlogLink::KIND_EXTERNAL,
                    'sort_order' => $order,
                ]);
            }

            $post->load('category');
            $path = $thumbnails->generate($post);
            if ($path) {
                $post->thumbnail_path = $path;
                $post->thumbnail_is_generated = true;
                $post->save();
            }
        }
    }

    /**
     * @param  array<string, string>  $paths
     * @return array<int, array<string, mixed>>
     */
    private function posts(int $quranId, int $humanId, array $paths): array
    {
        return [
            [
                'blog_category_id' => $quranId,
                'title' => 'Ring Composition in the Quran',
                'slug' => 'ring-composition-in-the-quran',
                'excerpt' => 'A ring opens and closes on the same note, with matching parts around a center. Ayat al-Kursi is a short place to see it.',
                'seo_title' => 'Ring Composition in the Quran',
                'meta_description' => 'How ring structure works in the Quran, using the nine-part reading of Ayat al-Kursi and a careful way to test longer surahs.',
                'tags' => ['Ring composition', 'Structure', 'Ayat al-Kursi', 'Literary form'],
                'links' => [
                    ['label' => 'Open the Ayat al-Kursi ring in Patterns', 'url' => $paths['kursiPattern']],
                    ['label' => 'Read 2:255 in the Quran reader', 'url' => $paths['kursiVerse']],
                    ['label' => 'Read Surah al-Baqarah', 'url' => $paths['baqarah']],
                    ['label' => 'Ayat al-Kursi on Quran.com', 'url' => 'https://quran.com/2/255'],
                    ['label' => 'Surah al-Baqarah on Quran.com', 'url' => 'https://quran.com/2'],
                ],
                'body' => $this->ringBody($paths),
            ],
            [
                'blog_category_id' => $quranId,
                'title' => 'Mathematical Patterns in the Quran',
                'slug' => 'mathematical-patterns-in-the-quran',
                'excerpt' => 'A Quranic count is only as clear as its rule. Here is what the pattern pages measure, and what a number does not prove by itself.',
                'seo_title' => 'Mathematical Patterns in the Quran',
                'meta_description' => 'How to study word counts and surah totals in the Quran without treating an unchecked number as proof.',
                'tags' => ['Numbers', 'Word counts', 'Study method', 'Patterns'],
                'links' => [
                    ['label' => 'Open Patterns and count from the stored text', 'url' => $paths['patterns']],
                    ['label' => 'Read the Quran in the app', 'url' => $paths['reader']],
                    ['label' => 'Check the wording on Quran.com', 'url' => 'https://quran.com/'],
                ],
                'body' => $this->mathBody($paths),
            ],
            [
                'blog_category_id' => $humanId,
                'title' => 'The Psychology of the Quran',
                'slug' => 'the-psychology-of-the-quran',
                'excerpt' => 'The Quran names the soul that pushes, the soul that blames itself, and the soul at rest, and it pairs those states with remembrance and prayer.',
                'seo_title' => 'The Psychology of the Quran',
                'meta_description' => 'What the Quran says about the restless, self-reproaching, and tranquil soul, and where prayer ends and medical care begins.',
                'tags' => ['Nafs', 'Heart', 'Patience', 'Remembrance'],
                'links' => [
                    ['label' => 'Hearts find rest in remembrance (13:28)', 'url' => $paths['v13']],
                    ['label' => 'No soul is burdened beyond its capacity (2:286)', 'url' => $paths['v286']],
                    ['label' => 'Duas for the state you are in', 'url' => $paths['duas']],
                    ['label' => 'Seerah: worship under pressure', 'url' => $paths['seerah']],
                    ['label' => '13:28 on Quran.com', 'url' => 'https://quran.com/13/28'],
                    ['label' => 'Seek treatment — Sunan Abi Dawud 3855', 'url' => 'https://sunnah.com/abudawud:3855'],
                ],
                'body' => $this->psychologyBody($paths),
            ],
            [
                'blog_category_id' => $humanId,
                'title' => 'The Purpose of the Human Being',
                'slug' => 'the-purpose-of-the-human-being',
                'excerpt' => 'The Quran gives worship as the reason we were created, and it ties that worship to a trust on earth and a return to Allah.',
                'seo_title' => 'The Purpose of the Human Being',
                'meta_description' => 'Worship, the trust of living on earth, and the test of life and death, read from Quran 51:56, 67:2, and 2:30.',
                'tags' => ['Worship', 'Khilafah', 'Purpose', 'Trial'],
                'links' => [
                    ['label' => 'Created to worship (51:56)', 'url' => $paths['v51']],
                    ['label' => 'Life and death as a test (67:2)', 'url' => $paths['v67']],
                    ['label' => 'A successor on the earth (2:30)', 'url' => $paths['v2_30']],
                    ['label' => 'Words for that purpose, in Duas', 'url' => $paths['duas']],
                    ['label' => 'The life that embodied it, in Seerah', 'url' => $paths['seerah']],
                    ['label' => '51:56 on Quran.com', 'url' => 'https://quran.com/51/56'],
                    ['label' => '67:2 on Quran.com', 'url' => 'https://quran.com/67/2'],
                ],
                'body' => $this->purposeBody($paths),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function ringBody(array $paths): string
    {
        return <<<HTML
<p>A ring composition is a way of arranging a passage so that the end answers the beginning. The first part pairs with the last, the next part pairs with the second to last, and the middle holds the idea the passage turns on. Readers meet this shape in classical literature and in scripture. In Quran study it is one literary reading. It does not replace tafsir, and it is only as strong as the pairs you can point to in the text.</p>
<h2>What to look for</h2>
<p>Read the passage once for the meaning. Then mark the opening theme and the closing theme. If they answer each other, look inward for a center. Keep the Arabic order. Moving a sentence to force a match is a sign the ring is not there.</p>
<h2>A short ring you can check</h2>
<p><a href="{$paths['kursiVerse']}">Ayat al-Kursi, Quran 2:255</a>, is short enough to hold in one reading. The pattern page lays it out in nine parts:</p>
<ol>
<li>Allah — there is no god except Him, the Ever-Living, the Self-Sustaining. This pairs with the close: He is the Most High, the Most Great.</li>
<li>Neither drowsiness nor sleep overtakes Him. This pairs with: their preservation does not tire Him.</li>
<li>To Him belongs whatever is in the heavens and the earth. This pairs with: His Throne extends over the heavens and the earth.</li>
<li>Who can intercede except by His permission? This pairs with: they encompass nothing of His knowledge except what He wills.</li>
<li>The center: He knows what is before them and what will be after them.</li>
</ol>
<p>The center is knowledge. Around it, the pairs move through permission, ownership of the heavens and the earth, freedom from weakness, and the names of Allah. You walk in toward the middle, then back out through matching ideas. That is the ring. <a href="{$paths['kursiPattern']}">Open the same breakdown beside the verse</a> and compare it with the wording, rather than with a diagram alone.</p>
<h2>Longer surahs</h2>
<p>Readers have tried the same kind of map on whole surahs. Raymond Farrin argues that <a href="{$paths['baqarah']}">Surah al-Baqarah</a> returns at the end to the faith and prayer of its opening, with the change of qiblah and the community’s covenant near the middle. Michel Cuypers has read many shorter surahs as concentric passages. Scholars do not all divide a surah in the same places. Where a proposed pair is forced, say so. Where the opening and the close clearly answer each other, the reading has earned a closer look.</p>
<blockquote>A ring is a proposal you can check against the mushaf. It is not a hidden code, and it does not need one.</blockquote>
<p>Start with 2:255. If the nine parts hold, you have a method you can take to a longer passage without having to believe every ring that has ever been drawn.</p>
HTML;
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function mathBody(array $paths): string
    {
        return <<<HTML
<p>Lists of Quranic word counts travel quickly. A number can be real and still not mean what the caption claims. The useful question is smaller: what was counted, in which text, and by which rule?</p>
<h2>A count needs a rule</h2>
<p>Before a total can teach anything, four things have to be stated. Which mushaf was used. Whether the count is an exact word or a whole Arabic root. Which forms were included. Who can repeat the count and reach the same total. Change any one of those and the number can change. A count without a rule cannot be checked, so it cannot be trusted.</p>
<p>That is why matching pairs you may have seen online — day and night, angels and devils, and others — are not proofs by themselves. Some of those totals depend on a private list of words. Until the list is published with the text, the total is a claim, not a finding.</p>
<h2>What the pattern pages measure</h2>
<p>On <a href="{$paths['patterns']}">The Eternal Echo pattern pages</a>, a card counts from the Arabic stored for that verse and its surah. You can see how many words are in the verse, how many ayat the surah has, and how often the focus word appears in that surah. The note beside the count is a study observation. The page is there so you can look at the wording and the total together.</p>
<p>Three things on that page are easy to recheck:</p>
<ul>
<li>The surah’s ayat total, which belongs to the mushaf itself.</li>
<li>A repeated word inside one surah, once you name the word.</li>
<li>A shape you can see without arithmetic, such as an opening and a close that share a theme.</li>
</ul>
<h2>What a number does not decide</h2>
<p>Classical tafsir asks what the verse says, who was being addressed, and how the Prophet, peace be upon him, and the early Muslims lived it. A repeated word can draw your eye. It does not answer those questions for you, and it does not turn a surah into a puzzle whose only point is the puzzle.</p>
<blockquote>If your total and the card’s total differ, compare the rule before you compare the conclusion.</blockquote>
<p>Try one verse in <a href="{$paths['patterns']}">Patterns</a>, then open the same ayah in the <a href="{$paths['reader']}">reader</a> and on Quran.com. Stay with counts you can repeat. Leave the ones that only work if nobody is allowed to ask how they were made.</p>
HTML;
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function psychologyBody(array $paths): string
    {
        return <<<HTML
<p>The Quran speaks to the inner life in plain names. It is not a modern clinic, and it should not be used to delay care from a qualified person. It does describe what a human being feels, and it gives practices that steady the heart.</p>
<h2>More than one state of the soul</h2>
<p>These are not three kinds of people. They are states a person can move through. In Yusuf’s words, the soul pushes toward wrong unless Allah has mercy (<a href="{$paths['v12']}">12:53</a>). The Quran swears by the self-reproaching soul, the one that blames itself after a slip (<a href="{$paths['v75']}">75:2</a>). It also addresses the tranquil soul, the one invited back to its Lord (<a href="{$paths['v89']}">89:27</a>). Urge, regret, and rest can visit the same person.</p>
<h2>What the text pairs with those states</h2>
<p>Hearts find rest in the remembrance of Allah (<a href="{$paths['v13']}">13:28</a>). No soul is given a burden beyond its capacity (<a href="{$paths['v286']}">2:286</a>). With hardship comes ease, and the sentence is said twice (<a href="{$paths['v94']}">94:5–6</a>). The <a href="{$paths['seerah']}">Seerah</a> shows the same pairing in a life: grief is real, and the next act of worship is real too.</p>
<p>The practices are specific. Remembrance is words, prayer, and Quran, said aloud and in the heart, not a mood you wait to feel. Patience is staying inside the limit when you are angry, afraid, or tired. Gratitude names the gift and not only the lack. The Quran also names the social habits that disturb the self: mockery, suspicion, and speaking about people in their absence (49:11–12).</p>
<h2>Where worship does not replace care</h2>
<p>If anxiety, despair, or harm is present, seek a qualified person as well as prayer. <a href="https://sunnah.com/abudawud:3855">Sunan Abi Dawud 3855</a> records that the Prophet, peace be upon him, told people to make use of medical treatment, because Allah has not placed a disease without a remedy, except old age. Dua and treatment sit side by side. The <a href="{$paths['duas']}">duas library</a> is for the words. It is not a reason to stay away from help.</p>
<blockquote>When the heart is tight, start with 13:28 and the remembrance you already know. When you have slipped, let the self-reproaching soul speak, then ask forgiveness. When the load feels unfair, read 2:286 before you decide what the load is.</blockquote>
HTML;
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function purposeBody(array $paths): string
    {
        return <<<HTML
<p>The Quran states a purpose in one sentence. Allah says He did not create jinn and mankind except to worship Him (<a href="{$paths['v51']}">51:56</a>). Worship here is not a spare activity after the real business of life. It is what the life is for.</p>
<h2>The sentence next to it</h2>
<p>That verse is easy to shrink until it means only a private feeling. The surahs around the same theme keep it from shrinking. Allah created death and life to test which of you is best in deed (<a href="{$paths['v67']}">67:2</a>). He tells the angels that He is placing a successor on the earth (<a href="{$paths['v2_30']}">2:30</a>). He asks whether people thought they were created for nothing and would not be returned (23:115). Read together, the purpose is worship that includes a trust on earth and a return in which deeds matter.</p>
<h2>Worship has a spine</h2>
<p>Prayer, Quran, fasting, zakat, and <a href="{$paths['duas']}">dua</a> are the spine, not a decoration around a feeling. Around that spine, the same purpose shows up in honesty, family, knowledge, and restraint. A person who prays and then cheats has not stepped outside the topic of worship. The Quran treats the prayer and the cheat as one moral life.</p>
<p>The successor on earth is not permission to use the world however you like. The trust is answered by 51:56, not set against it. Learning, refusing cruelty, and caring for what you were given are ways of serving Allah. They do not replace the prayer that names Him. The <a href="{$paths['seerah']}">Seerah</a> is the record of that purpose lived in hunger, in leadership, and in loss.</p>
<blockquote>A test means the result is not already finished in public. Comfort is not always a reward, and pain is not always a punishment. Life and death are the setting. The return is to Him.</blockquote>
<p>Start with <a href="{$paths['v51']}">51:56</a>, then <a href="{$paths['v67']}">67:2</a>, then the human story at <a href="{$paths['v2_30']}">2:30</a>. The words in the duas are what someone says after accepting that purpose.</p>
HTML;
    }

    private function path(string $name, array $params = []): string
    {
        return parse_url(route($name, $params), PHP_URL_PATH) ?: '/';
    }
}
