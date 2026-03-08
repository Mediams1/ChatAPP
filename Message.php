<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Cifrar mensaje al guardar
    public function setBodyAttribute($value)
    {
        $this->attributes['body'] = $this->encryptMessage($value);
    }

    // Descifrar mensaje al leer
    public function getBodyAttribute($value)
    {
        return $this->decryptMessage($value);
    }

    // Cifrado AES-256-CBC
    private function encryptMessage(string $message): string
    {
        $key = substr(hash('sha256', env('MESSAGE_ENCRYPTION_KEY', 'default_key_change_me_32chars!!')), 0, 32);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($message, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    // Descifrado AES-256-CBC
    private function decryptMessage(string $encrypted): string
    {
        try {
            $key = substr(hash('sha256', env('MESSAGE_ENCRYPTION_KEY', 'default_key_change_me_32chars!!')), 0, 32);
            $data = base64_decode($encrypted);
            $iv = substr($data, 0, 16);
            $ciphertext = substr($data, 16);
            $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
            return $decrypted !== false ? $decrypted : '[Mensaje no disponible]';
        } catch (\Exception $e) {
            return '[Mensaje no disponible]';
        }
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
