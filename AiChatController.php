<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiConversation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use LucianoTonet\GroqLaravel\Facades\Groq;

class AiChatController extends Controller
{
    public function index()
    {
        $sessionId = session()->get('ai_session_id', (string) Str::uuid());
        session()->put('ai_session_id', $sessionId);

        $messages = AiConversation::where('user_id', Auth::id())
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('chat.ai-chat', compact('messages', 'sessionId'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'message'    => 'required|string|max:2000',
            'session_id' => 'required|string',
        ]);

        $userId    = Auth::id();
        $sessionId = $request->session_id;

        // Guardar mensaje del usuario
        AiConversation::create([
            'user_id'    => $userId,
            'role'       => 'user',
            'content'    => $request->message,
            'session_id' => $sessionId,
        ]);

        // Historial para contexto
        $history = AiConversation::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($msg) => ['role' => $msg->role, 'content' => $msg->content])
            ->toArray();

        // Llamar a Groq (Llama 3.3)
        try {
            $response = Groq::chat()->completions()->create([
                'model'    => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                'messages' => array_merge(
                    [[
                        'role'    => 'system',
                        'content' => 'Eres un asistente útil integrado en una aplicación de chat. Responde siempre en el idioma del usuario. Sé amigable y conciso.',
                    ]],
                    $history
                ),
                'max_tokens'  => 1000,
                'temperature' => 0.7,
            ]);

            $aiMessage = $response['choices'][0]['message']['content'];

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al conectar con la IA: ' . $e->getMessage()
            ], 500);
        }

        // Guardar respuesta de la IA
        AiConversation::create([
            'user_id'    => $userId,
            'role'       => 'assistant',
            'content'    => $aiMessage,
            'session_id' => $sessionId,
        ]);

        return response()->json(['message' => $aiMessage]);
    }

    public function newConversation()
    {
        session()->forget('ai_session_id');
        return redirect()->route('ai-chat.index');
    }
}