<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Dua extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'description',
        'arabic_text',
        'transliteration',
        'translation_en',
        'translation_ur',
        'word_by_word',
        'meaning_explanation',
        'benefits_and_virtues',
        'when_to_recite',
        'repeat_count',
        'source_type',
        'quran_reference',
        'surah_number',
        'verse_number',
        'verse_id',
        'hadith_reference',
        'hadith_book',
        'hadith_number',
        'hadith_grading',
        'hadith_id',
        'audio_url',
        'is_featured',
        'order',
    ];

    protected $casts = [
        'word_by_word' => 'array',
        'is_featured' => 'boolean',
        'repeat_count' => 'integer',
        'surah_number' => 'integer',
        'verse_number' => 'integer',
        'order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dua) {
            if (empty($dua->slug)) {
                $dua->slug = Str::slug($dua->title);
            }
        });
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeCategory($query, $category)
    {
        if (!empty($category) && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    /**
     * Scope: Filter by source type (quran, hadith, both).
     */
    public function scopeSourceType($query, $source)
    {
        if (!empty($source) && in_array($source, ['quran', 'hadith', 'both'])) {
            if ($source === 'quran') {
                return $query->whereIn('source_type', ['quran', 'both']);
            } elseif ($source === 'hadith') {
                return $query->whereIn('source_type', ['hadith', 'both']);
            }
            return $query->where('source_type', $source);
        }
        return $query;
    }

    /**
     * Scope: Search across title, description, Arabic, English/Urdu meanings, explanation, and references.
     */
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%")
              ->orWhere('arabic_text', 'LIKE', "%{$term}%")
              ->orWhere('transliteration', 'LIKE', "%{$term}%")
              ->orWhere('translation_en', 'LIKE', "%{$term}%")
              ->orWhere('translation_ur', 'LIKE', "%{$term}%")
              ->orWhere('meaning_explanation', 'LIKE', "%{$term}%")
              ->orWhere('benefits_and_virtues', 'LIKE', "%{$term}%")
              ->orWhere('quran_reference', 'LIKE', "%{$term}%")
              ->orWhere('hadith_reference', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Accessor: Direct URL to Quranic Lens verse page if Quran reference numbers exist.
     */
    public function getQuranLensUrlAttribute(): ?string
    {
        if ($this->surah_number && $this->verse_number) {
            return route('lens.verse', [
                'chapter' => $this->surah_number,
                'verse' => $this->verse_number,
            ]);
        }
        return null;
    }
}
