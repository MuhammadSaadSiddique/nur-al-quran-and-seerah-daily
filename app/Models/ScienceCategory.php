<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScienceCategory extends Model
{
    protected $table = 'science_categories';

    protected $fillable = ['name', 'slug', 'emoji', 'mapped_fields'];

    /**
     * Get mapped fields as an array.
     */
    public function getFieldsAttribute()
    {
        return array_map('trim', explode(',', $this->mapped_fields ?? ''));
    }
}
