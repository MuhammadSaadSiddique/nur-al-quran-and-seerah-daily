<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranResearchContribution extends Model
{
    use SoftDeletes;

    protected $table = 'quran_research_contributions';

    protected $fillable = [
        'user_id',
        'submitter_name',
        'submitter_email',
        'surah_number',
        'end_surah_number',
        'surah_numbers',
        'verse_number',
        'end_verse_number',
        'is_full_surah',
        'category',
        'title',
        'description',
        'video_url',
        'video_platform',
        'video_id',
        'status',
        'approvals_count',
        'downvotes_count',
        'published_at',
    ];

    protected $casts = [
        'surah_number' => 'integer',
        'end_surah_number' => 'integer',
        'surah_numbers' => 'array',
        'verse_number' => 'integer',
        'end_verse_number' => 'integer',
        'is_full_surah' => 'boolean',
        'approvals_count' => 'integer',
        'downvotes_count' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'scope_label',
        'embed_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(QuranResearchReview::class, 'contribution_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVideos($query)
    {
        return $query->where('category', 'video');
    }

    /**
     * Scope query to match a specific Surah (including Surah ranges and multi-Surah selections).
     */
    public function scopeForSurah($query, int $surahNumber)
    {
        return $query->where(function ($q) use ($surahNumber) {
            $q->where('surah_number', $surahNumber)
              ->orWhere(function ($rangeQ) use ($surahNumber) {
                  $rangeQ->whereNotNull('end_surah_number')
                         ->where('surah_number', '<=', $surahNumber)
                         ->where('end_surah_number', '>=', $surahNumber);
              })
              ->orWhereJsonContains('surah_numbers', $surahNumber)
              ->orWhereJsonContains('surah_numbers', (string) $surahNumber);
        });
    }

    /**
     * Human-readable Surah / Verse scope label (Single verse, Verse range, Full Surah, Surah range, or Multiple Surahs).
     */
    public function scopeLabel(): string
    {
        if (is_array($this->surah_numbers) && count($this->surah_numbers) > 1) {
            $sorted = array_unique(array_map('intval', $this->surah_numbers));
            sort($sorted);
            return 'Surahs ' . implode(', ', $sorted);
        }

        if ($this->end_surah_number && $this->end_surah_number > $this->surah_number) {
            if ($this->is_full_surah) {
                return "Surahs {$this->surah_number}–{$this->end_surah_number} (Full)";
            }
            $startV = $this->verse_number ?: 1;
            $endV = $this->end_verse_number;
            if ($endV) {
                return "Surah {$this->surah_number}:{$startV} – {$this->end_surah_number}:{$endV}";
            }
            return "Surahs {$this->surah_number}–{$this->end_surah_number}";
        }

        if ($this->is_full_surah) {
            if ($this->end_verse_number && $this->end_verse_number > 1) {
                return "Surah {$this->surah_number} (1–{$this->end_verse_number} Full)";
            }
            return "Surah {$this->surah_number} (Full Surah)";
        }

        if ($this->end_verse_number && $this->end_verse_number > ($this->verse_number ?: 1)) {
            return "Surah {$this->surah_number}:{$this->verse_number}–{$this->end_verse_number}";
        }

        return "Surah {$this->surah_number}:{$this->verse_number}";
    }

    public function getScopeLabelAttribute(): string
    {
        return $this->scopeLabel();
    }

    /**
     * Recalculate approvals and downvotes counts and update publication status.
     * Rule: 5 approvals will publish it (status = 'published').
     * If downvotes outnumber approvals or net approvals < 5, reverts back to 'pending'.
     */
    public function recalculateStatus(): void
    {
        $approvals = $this->reviews()->where('vote', 'approve')->count();
        $downvotes = $this->reviews()->where('vote', 'downvote')->count();

        $this->approvals_count = $approvals;
        $this->downvotes_count = $downvotes;

        // If >= 5 approvals and downvotes do not exceed approvals
        if ($approvals >= 5 && $downvotes < $approvals) {
            if ($this->status !== 'published') {
                $this->status = 'published';
                $this->published_at = now();
            }
        } else {
            // Revert to pending if downvotes bring it below threshold or downvotes >= approvals
            $this->status = 'pending';
        }

        $this->save();
    }

    /**
     * Parse video URL from ANY video platform (YouTube, Shorts, Live, Vimeo, Dailymotion, Facebook,
     * Instagram, TikTok, X/Twitter, Twitch, Rumble, Streamable, Archive.org, Google Drive, Direct MP4/WebM, or any external URL).
     */
    public static function parseVideoUrl(?string $url): array
    {
        if (empty($url)) {
            return ['platform' => null, 'id' => null, 'embed_url' => null];
        }

        $url = trim($url);

        // 1. YouTube (watch, youtu.be, shorts, live, embed)
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts|live)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $matches)) {
            $id = $matches[1];
            return [
                'platform' => 'youtube',
                'id' => $id,
                'embed_url' => "https://www.youtube.com/embed/{$id}"
            ];
        }

        // 2. Vimeo
        if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)/i', $url, $matches)) {
            $id = $matches[1];
            return [
                'platform' => 'vimeo',
                'id' => $id,
                'embed_url' => "https://player.vimeo.com/video/{$id}"
            ];
        }

        // 3. Dailymotion (dailymotion.com/video/{id} or dai.ly/{id})
        if (preg_match('/(?:dailymotion\.com\/(?:embed\/)?video\/|dai\.ly\/)([a-zA-Z0-9]+)/i', $url, $matches)) {
            $id = $matches[1];
            return [
                'platform' => 'dailymotion',
                'id' => $id,
                'embed_url' => "https://www.dailymotion.com/embed/video/{$id}"
            ];
        }

        // 4. Facebook Video / Watch / Reel
        if (preg_match('/(?:facebook\.com|fb\.watch)/i', $url)) {
            return [
                'platform' => 'facebook',
                'id' => substr(md5($url), 0, 16),
                'embed_url' => 'https://www.facebook.com/plugins/video.php?href=' . urlencode($url) . '&show_text=false'
            ];
        }

        // 5. Instagram Reel / Post / IGTV
        if (preg_match('/instagram\.com\/(reel|p|tv)\/([a-zA-Z0-9_-]+)/i', $url, $matches)) {
            $type = $matches[1];
            $id = $matches[2];
            return [
                'platform' => 'instagram',
                'id' => $id,
                'embed_url' => "https://www.instagram.com/{$type}/{$id}/embed"
            ];
        }

        // 6. TikTok
        if (preg_match('/tiktok\.com\/.*\/video\/(\d+)/i', $url, $matches)) {
            $id = $matches[1];
            return [
                'platform' => 'tiktok',
                'id' => $id,
                'embed_url' => "https://www.tiktok.com/embed/v2/{$id}"
            ];
        }

        // 7. X / Twitter video status
        if (preg_match('/(?:twitter\.com|x\.com)\/[^\/]+\/status\/(\d+)/i', $url, $matches)) {
            $id = $matches[1];
            return [
                'platform' => 'twitter',
                'id' => $id,
                'embed_url' => "https://platform.twitter.com/embed/Tweet.html?id={$id}"
            ];
        }

        // 8. Streamable
        if (preg_match('/streamable\.com\/(?:e\/)?([a-zA-Z0-9]+)/i', $url, $matches)) {
            $id = $matches[1];
            return [
                'platform' => 'streamable',
                'id' => $id,
                'embed_url' => "https://streamable.com/e/{$id}"
            ];
        }

        // 9. Archive.org
        if (preg_match('/archive\.org\/(?:details|embed)\/([a-zA-Z0-9_.\-]+)/i', $url, $matches)) {
            $id = $matches[1];
            return [
                'platform' => 'archive',
                'id' => $id,
                'embed_url' => "https://archive.org/embed/{$id}"
            ];
        }

        // 10. Google Drive video
        if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/i', $url, $matches)) {
            $id = $matches[1];
            return [
                'platform' => 'gdrive',
                'id' => $id,
                'embed_url' => "https://drive.google.com/file/d/{$id}/preview"
            ];
        }

        // 11. Rumble
        if (preg_match('/rumble\.com\/embed\/([a-zA-Z0-9]+)/i', $url, $matches)) {
            $id = $matches[1];
            return [
                'platform' => 'rumble',
                'id' => $id,
                'embed_url' => "https://rumble.com/embed/{$id}/"
            ];
        }

        // 12. Direct video file (.mp4, .webm, .ogg, .mov, .m3u8)
        if (preg_match('/\.(mp4|webm|ogg|mov|m3u8)(\?.*)?$/i', $url)) {
            return [
                'platform' => 'direct_file',
                'id' => null,
                'embed_url' => $url
            ];
        }

        // 13. Any other video platform URL
        $host = parse_url($url, PHP_URL_HOST);
        $platformName = $host ? preg_replace('/^www\./i', '', strtolower($host)) : 'external';

        return [
            'platform' => substr($platformName, 0, 50),
            'id' => null,
            'embed_url' => $url
        ];
    }

    public function getEmbedUrlAttribute(): ?string
    {
        $parsed = self::parseVideoUrl($this->video_url);
        return $parsed['embed_url'] ?? null;
    }

    public function embedUrl(): ?string
    {
        return $this->getEmbedUrlAttribute();
    }
}
