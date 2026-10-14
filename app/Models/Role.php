<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'permissions'];
    protected $casts = ['permissions' => 'array'];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public static function permissionKeys(): array
    {
        return collect(config('permissions'))->flatMap(fn ($group) => array_keys($group['actions']))->values()->all();
    }
}
