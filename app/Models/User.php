<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Descomentar al instalar spatie/laravel-permission:
// use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    protected $attributes = ['lActivo' => true];
    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        $this->loadMissing('roles');
        return $this->lActivo && $this->roles->contains(fn ($role) => $role->slug === 'administrador' || in_array($permission, $role->permissions ?? [], true));
    }

    public function permissionKeys(): array
    {
        return array_values(array_filter(Role::permissionKeys(), fn ($key) => $this->hasPermission($key)));
    }
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    
    // Descomentar al instalar spatie/laravel-permission:
    // use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'cPrimerApellido',
        'cSegundoApellido',
        'cTelefono',
        'email',
        'password',
        'lActivo',
        'lCambiarPassword',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'lActivo' => 'boolean',
            'lCambiarPassword' => 'boolean',
        ];
    }

    /**
     * Obtener el nombre completo del usuario.
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->name} {$this->cPrimerApellido} {$this->cSegundoApellido}");
    }
}
