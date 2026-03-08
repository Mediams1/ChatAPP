<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // Página principal del chat
    public function index()
    {
        $currentUser = Auth::user();
        $currentUser->markAsOnline();

        // Obtener todas las conversaciones del usuario
        $conversations = Conversation::where('user_one', $currentUser->id)
            ->orWhere('user_two', $currentUser->id)
            ->with(['userOne', 'userTwo', 'lastMessage'])
            ->get()
            ->map(function ($conv) use ($currentUser) {
                $other = $conv->getOtherUser($currentUser->id);
                return [
                    'id'           => $conv->id,
                    'other_user'   => $other,
                    'last_message' => $conv->lastMessage,
                    'unread_count' => $conv->unreadCount($currentUser->id),
                    'updated_at'   => $conv->updated_at,
                ];
            })
            ->sortByDesc(function ($conv) {
                return optional($conv['last_message'])->created_at ?? $conv['updated_at'];
            })
            ->values();

        // Lista de usuarios para iniciar nueva conversación
        $users = User::where('id', '!=', $currentUser->id)
            ->whereNotNull('email_verified_at')
            ->orderBy('name')
            ->get();

        return view('chat.index', compact('conversations', 'users', 'currentUser'));
    }

    // Abrir una conversación
    public function show(int $userId)
    {
        $currentUser = Auth::user();
        $otherUser = User::findOrFail($userId);

        if ($otherUser->id === $currentUser->id) {
            return redirect()->route('chat.index');
        }

        $conversation = Conversation::getOrCreate($currentUser->id, $otherUser->id);

        // Marcar mensajes como leídos
        $conversation->messages()
            ->where('sender_id', $otherUser->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = $conversation->messages()
            ->with('sender')
            ->get();

        $conversations = Conversation::where('user_one', $currentUser->id)
            ->orWhere('user_two', $currentUser->id)
            ->with(['userOne', 'userTwo', 'lastMessage'])
            ->get()
            ->map(function ($conv) use ($currentUser) {
                $other = $conv->getOtherUser($currentUser->id);
                return [
                    'id'           => $conv->id,
                    'other_user'   => $other,
                    'last_message' => $conv->lastMessage,
                    'unread_count' => $conv->unreadCount($currentUser->id),
                    'updated_at'   => $conv->updated_at,
                ];
            })
            ->sortByDesc(function ($conv) {
                return optional($conv['last_message'])->created_at ?? $conv['updated_at'];
            })
            ->values();

        $users = User::where('id', '!=', $currentUser->id)
            ->whereNotNull('email_verified_at')
            ->orderBy('name')
            ->get();

        return view('chat.index', compact('conversations', 'users', 'currentUser', 'otherUser', 'messages', 'conversation'));
    }

    // Enviar mensaje
    public function send(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'body'            => 'required|string|max:5000',
        ]);

        $currentUser = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Verificar que el usuario pertenece a esta conversación
        if ($conversation->user_one !== $currentUser->id && $conversation->user_two !== $currentUser->id) {
            abort(403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $currentUser->id,
            'body'            => $request->body,
        ]);

        $conversation->touch();

        return response()->json([
            'success' => true,
            'message' => [
                'id'         => $message->id,
                'body'       => $message->body,
                'sender_id'  => $message->sender_id,
                'is_read'    => $message->is_read,
                'created_at' => $message->created_at->format('H:i'),
                'sender'     => [
                    'id'         => $currentUser->id,
                    'name'       => $currentUser->name,
                    'avatar_url' => $currentUser->avatar_url,
                ],
            ],
        ]);
    }

    // Obtener mensajes nuevos (polling)
    public function getMessages(Request $request, int $conversationId)
    {
        $currentUser = Auth::user();
        $conversation = Conversation::findOrFail($conversationId);

        if ($conversation->user_one !== $currentUser->id && $conversation->user_two !== $currentUser->id) {
            abort(403);
        }

        $lastId = $request->input('last_id', 0);

        $messages = $conversation->messages()
            ->with('sender')
            ->where('id', '>', $lastId)
            ->get()
            ->map(function ($msg) use ($currentUser) {
                return [
                    'id'         => $msg->id,
                    'body'       => $msg->body,
                    'sender_id'  => $msg->sender_id,
                    'is_mine'    => $msg->sender_id === $currentUser->id,
                    'created_at' => $msg->created_at->format('H:i'),
                    'sender'     => [
                        'id'         => $msg->sender->id,
                        'name'       => $msg->sender->name,
                        'avatar_url' => $msg->sender->avatar_url,
                    ],
                ];
            });

        // Marcar como leídos
        $conversation->messages()
            ->where('sender_id', '!=', $currentUser->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['messages' => $messages]);
    }

    // Obtener estado de usuarios (online/offline)
    public function getUserStatus(int $userId)
    {
        $user = User::findOrFail($userId);
        return response()->json([
            'is_online'     => $user->is_online,
            'last_seen_text' => $user->last_seen_text,
        ]);
    }
}
