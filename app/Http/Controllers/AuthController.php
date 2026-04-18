<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, RateLimiter, Hash};
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends Controller {
    public function showLogin() {
        return view('auth.login');
    }

    public function login(Request $request) {
        $request->validate([
            'email'    => ['required','email','max:255'],
            'password' => ['required','string','min:6'],
        ], [
            'email.required'    => 'O e-mail é obrigatório.',
            'email.email'       => 'Digite um e-mail válido.',
            'password.required' => 'A senha é obrigatória.',
        ]);

        // Rate limiting: máximo 5 tentativas por IP+email em 15 minutos
        $key = Str::lower($request->email) . '|' . $request->ip();
        $maxAttempts = (int) config('app.max_login_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
            ]);
        }

        if (!Auth::attempt($request->only('email','password'), $request->boolean('remember'))) {
            RateLimiter::hit($key, 900); // 15 minutos
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha incorretos.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        // Registrar login
        Auth::user()->recordLogin($request->ip());

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
