<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        if (config('app.Email_Required')) {
            return [
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ];
        } else {
            return [
                'email' => ['required', 'string'],
                'password' => ['required', 'string'],
            ];
        }
    }

            
    function handleAuthAttempt($credentials) {
        if (!Auth::attempt($credentials, $this->filled('remember'))) {
            RateLimiter::hit($this->throttleKey());
    
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
    
        if (!Auth::user()->hasVerifiedEmail() && Auth::user()->guid && Auth::user()->domain) {
            Auth::user()->markEmailAsVerified();
        }
    }
    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();
        $this->email = strtolower($this->email);

        // Check if the optional file exists
        $optionalFile = __DIR__ . '/LoginRequestOverride.php';
        if (file_exists($optionalFile)) {
            include_once $optionalFile;
            $overrideClass = new LoginRequestOverride();
            $result = $overrideClass->auth($this->email, $this->password, $this->filled('remember'));
            if ($result) {
                RateLimiter::clear($this->throttleKey());
            } else {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'email' => __('auth.failed'),
                ]);
            }
        } else {
            if (env('LDAP_CONNECTION', '') != '') {
                $credentials = [
                    'mail' => $this->email,
                    'password' => $this->password,
                    'fallback' => [
                        'email' => $this->email,
                        'password' => $this->password,
                    ],
                ];
            } else {
                $credentials = [
                    'email' => $this->email,
                    'password' => $this->password,
                ];
            }

            try {
                if (!Auth::attempt($credentials, $this->filled('remember'))) {
                    RateLimiter::hit($this->throttleKey());

                    throw ValidationException::withMessages([
                        'email' => __('auth.failed'),
                    ]);
                }
                if (!Auth::user()->hasVerifiedEmail() && Auth::user()->guid && Auth::user()->domain) {
                    Auth::user()->markEmailAsVerified();
                }
                $user = Auth::user();
                if (preg_match('/^\$2a\$/', $user->password)) {
                    //rehash
                    $user->password = Hash::make($this->password);
                    $user->save();
                }
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // This means the user is already in the database record, but LDAP also has the same user.
                // Here we decide to override the Kuwa DB's record.
                User::where('email', $this->email)->delete();
                $this->handleAuthAttempt($credentials);
            } catch (\Throwable $e) {
                // This usually means the LDAP server is unreachable.
                $credentials = [
                    'email' => $this->email,
                    'password' => $this->password,
                ];
                $this->handleAuthAttempt($credentials);
            }
            RateLimiter::clear($this->throttleKey());
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')) . '|' . $this->ip());
    }
}
