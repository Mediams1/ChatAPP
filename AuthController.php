<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\WelcomeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('chat.index');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100|min:2',
            'email' => 'required|email|unique:users,email',
        ], [
            'name.required'  => 'El nombre es obligatorio.',
            'name.min'       => 'El nombre debe tener al menos 2 caracteres.',
            'email.required' => 'El correo es obligatorio.',
            'email.email'    => 'Ingresa un correo válido.',
            'email.unique'   => 'Este correo ya está registrado.',
        ]);

        $plainPassword = User::generatePassword();
        $token = User::generateVerificationToken();

        $user = User::create([
            'name'               => $request->name,
            'email'              => $request->email,
            'password'           => Hash::make($plainPassword),
            'verification_token' => $token,
        ]);

        try {
            Mail::to($user->email)->send(new WelcomeMail($user, $plainPassword, $token));
        } catch (\Exception $e) {
            $user->delete();
            return back()->withErrors(['email' => 'No se pudo enviar el correo. Verifica tu dirección.'])->withInput();
        }

        return redirect()->route('verification.notice')
            ->with('email', $user->email)
            ->with('success', '¡Registro exitoso! Revisa tu correo.');
    }

    public function verificationNotice()
    {
        return view('auth.verify-notice');
    }

    public function verifyEmail(string $token)
    {
        $user = User::where('verification_token', $token)
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Enlace inválido o ya utilizado.']);
        }

        $user->update([
            'email_verified_at'  => now(),
            'verification_token' => null,
        ]);

        Auth::login($user);
        $user->markAsOnline();

        return redirect()->route('chat.index')
            ->with('success', '¡Email verificado! Bienvenido.');
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('chat.index');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => 'El correo es obligatorio.',
            'email.email'       => 'Ingresa un correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Correo o contraseña incorrectos.',
            ]);
        }

        if (!$user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => 'Debes verificar tu correo antes de iniciar sesión.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $user->markAsOnline();
        $request->session()->regenerate();

        return redirect()->intended(route('chat.index'));
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::user()->markAsOffline();
        }
        Auth::logout();
        $request->session()->flush();

        return redirect()->route('login');
    }
}