<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = ['name', 'type', 'is_active'];

    public function questions()
    {
        return $this->hasMany(GeneratedQuestion::class, 'theme_id');
    }
}
