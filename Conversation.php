<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['user_one', 'user_two'];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function unreadCount(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function getOtherUser(int $currentUserId): User
    {
        return $this->user_one === $currentUserId
            ? $this->userTwo
            : $this->userOne;
    }

    // Buscar o crear conversación sin duplicados
    public static function getOrCreate(int $userOneId, int $userTwoId): self
    {
        $small = min($userOneId, $userTwoId);
        $large = max($userOneId, $userTwoId);

        $existing = self::where(function ($q) use ($small, $large) {
            $q->where('user_one', $small)->where('user_two', $large);
        })->orWhere(function ($q) use ($small, $large) {
            $q->where('user_one', $large)->where('user_two', $small);
        })->first();

        if ($existing) {
            return $existing;
        }

        return self::create([
            'user_one' => $small,
            'user_two' => $large,
        ]);
    }
}
