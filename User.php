<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'verification_token',
        'email_verified_at',
        'avatar',
        'is_online',
        'last_seen',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen' => 'datetime',
        'is_online' => 'boolean',
    ];

    // Paleta de colores vibrantes para avatares
    private static $avatarColors = [
        'e8384f', // rojo
        'f7634f', // naranja rojo
        'f59e0b', // amarillo
        '10b981', // verde
        '3b82f6', // azul
        '8b5cf6', // morado
        'ec4899', // rosa
        '06b6d4', // cyan
        'f97316', // naranja
        '6366f1', // indigo
        '14b8a6', // teal
        'a855f7', // violeta
    ];

    public static function generatePassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
        $password = '';
        for ($i = 0; $i < 12; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public static function generateVerificationToken(): string
    {
        return Str::random(64);
    }

    public function conversations()
    {
        return Conversation::where('user_one', $this->id)
            ->orWhere('user_two', $this->id);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        // Color único por usuario basado en su ID
        $color = self::$avatarColors[($this->id - 1) % count(self::$avatarColors)];
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=' . $color . '&color=fff&size=128&bold=true';
    }

    public function markAsOnline()
    {
        $this->update(['is_online' => true, 'last_seen' => now()]);
    }

    public function markAsOffline()
    {
        $this->update(['is_online' => false, 'last_seen' => now()]);
    }

    public function getLastSeenTextAttribute()
    {
        if ($this->is_online) return 'En línea';
        if (!$this->last_seen) return 'Nunca';
        return 'Último: ' . $this->last_seen->diffForHumans();
    }
}