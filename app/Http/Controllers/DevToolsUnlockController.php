<?php

namespace App\Http\Controllers;

use App\Support\DevToolsGrant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DevToolsUnlockController extends Controller
{
    private const ALLOWED_PATHS = ['/terminal', '/db-console', '/env-editor', '/logs'];

    public function show(Request $request): View
    {
        if (! config('auth.otp.fallback_enabled')) {
            abort(403, 'This interface is disabled when OTP_FALLBACK_ENABLED is not true.');
        }

        $path = $this->normalizeIntended($request->query('intended', '/terminal'));

        return view('dev-tools.dev-tools-unlock', [
            'intended' => $path,
        ]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        if (! config('auth.otp.fallback_enabled')) {
            abort(403);
        }

        $validated = $request->validate([
            'pin' => ['required', 'digits:6'],
            'intended' => ['required', 'string', 'max:512'],
        ]);

        $code = (string) config('auth.otp.fallback_code', '083078');

        if (! hash_equals($code, (string) $validated['pin'])) {
            return redirect()
                ->route('dev-tools.unlock.show', ['intended' => $this->normalizeIntended($validated['intended'])])
                ->withErrors(['pin' => 'Invalid PIN.'])
                ->withInput($request->only('intended'));
        }

        $path = $this->normalizeIntended($validated['intended']);
        $token = DevToolsGrant::create();
        $url = url($path);
        $sep = str_contains($url, '?') ? '&' : '?';

        return redirect()->to($url.$sep.'dt='.urlencode($token));
    }

    private function normalizeIntended(string $raw): string
    {
        $path = parse_url($raw, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $raw;
        }

        $path = '/'.trim($path, '/');

        if (str_starts_with($path, '/backend/')) {
            $path = substr($path, strlen('/backend'));
        } elseif ($path === '/backend') {
            $path = '/terminal';
        }

        return in_array($path, self::ALLOWED_PATHS, true) ? $path : '/terminal';
    }
}
