<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Core\RoleResource;
use App\Http\Resources\Core\UserResource;
use App\Http\Resources\Auth\LoginResource;
use App\Models\Core\EmailTemplate;
use App\Models\LoginOtp;
use App\Models\Report\UserActivity;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\MailService;
use App\Support\LoginAttemptLockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->input('remember_me', false);

        if ($lockedResponse = LoginAttemptLockout::lockedResponseIfNeeded(
            $credentials['email'],
            LoginAttemptLockout::CONTEXT_APP
        )) {
            return $lockedResponse;
        }

        // Authenticate user
        $user = $this->authenticateUser($credentials);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        // Handle device verification if required
        $deviceFingerprint = $request->input('device_fingerprint');
        if ($deviceFingerprint) {
            $deviceResponse = $this->handleDeviceVerification($user, $deviceFingerprint);
            if ($deviceResponse) {
                return $deviceResponse;
            }
        }

        // Create and return access token
        return $this->createAuthToken($user, $remember);
    }

    /**
     * Authenticate user with credentials.
     *
     * Non-admins are blocked immediately if inactive - no token is issued.
     */
    private function authenticateUser(array $credentials)
    {
        if (! Auth::attempt($credentials)) {
            $failedUser = User::where('email', $credentials['email'])->first();
            UserActivity::log('wrong_password', $failedUser, $credentials['email'], request());

            $lockoutResult = LoginAttemptLockout::recordFailure(
                $credentials['email'],
                LoginAttemptLockout::CONTEXT_APP
            );

            if (LoginAttemptLockout::isLockoutResult($lockoutResult)) {
                return LoginAttemptLockout::lockedJsonResponse();
            }

            return response()->json([
                'message' => trans('messages.invalid_credentials'),
                'errors' => ['email' => [trans('messages.invalid_credentials')]],
            ], 422);
        }

        LoginAttemptLockout::clear($credentials['email'], LoginAttemptLockout::CONTEXT_APP);

        $user = Auth::user();
        $user->loadMissing('role');

        return $user;
    }

    /**
     * Handle device verification and OTP flow
     */
    private function handleDeviceVerification(User $user, string $deviceFingerprint)
    {
        $device = UserDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $deviceFingerprint)
            ->where('is_verified', true)
            ->first();

        if (! $device) {
            return $this->sendOtpForDeviceVerification($user, $deviceFingerprint);
        }

        // Update device last used timestamp
        $device->update(['last_used_at' => now()]);

        return null;
    }

    /**
     * Generate and send OTP for device verification
     */
    private function sendOtpForDeviceVerification(User $user, string $deviceFingerprint)
    {
        // Delete any existing OTPs for this user and device
        LoginOtp::where('user_id', $user->id)
            ->where('device_fingerprint', $deviceFingerprint)
            ->delete();

        // Generate OTP and session token
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $sessionToken = Str::random(64);

        // Create OTP record (expires in 10 minutes)
        LoginOtp::create([
            'user_id' => $user->id,
            'device_fingerprint' => $deviceFingerprint,
            'otp_code' => $otpCode,
            'session_token' => $sessionToken,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Send OTP email
        $this->sendOtpEmail($user, $otpCode);

        return response()->json([
            'message' => trans('messages.otp_verification_required'),
            'challenge' => 'OTP_REQUIRED',
            'session' => $sessionToken,
        ], 200);
    }

    /**
     * Send OTP email to user
     */
    private function sendOtpEmail(User $user, string $otpCode)
    {
        $template_data = EmailTemplate::where('slug', 'login-otp')
            ->first();

        if (! $template_data || ! $template_data->status) {
            return;
        }

        $data = [
            'name' => $user->name,
            'template_data' => $template_data,
            'otp_code' => $otpCode,
            'expires_in_minutes' => 10,
            'logo' => asset('assets/images/logo.png'),
        ];

        try {
            $subject = render_template($template_data->subject);

            // Render the email template
            $htmlContent = view('emails.login-otp', ['data' => $data])->render();

            // Send email using MailService
            $result = MailService::sendEmail([
                'to' => $user->email,
                'subject' => $subject,
                'html' => $htmlContent,
                'template' => $template_data,
            ]);

            if (! $result['success']) {
                Log::error('Login OTP email failed for user '.$user->id.': '.$result['message']);
            }

        } catch (\Throwable $e) {
            Log::error('Login OTP email failed for user '.$user->id.': '.$e->getMessage());
        }
    }

    /**
     * Create authentication token for user
     */
    private function createAuthToken(User $user, bool $remember)
    {
        $expiration = $remember ? config('sanctum.expiration_long') : config('sanctum.expiration');
        $tokenResult = $user->createToken('auth-token');
        $tokenModel = $tokenResult->accessToken;
        $tokenModel->expires_at = now()->addMinutes($expiration);
        $tokenModel->save();

        $user->setAttribute('is_first_login', $user->recordSuccessfulLogin());

        $user->access_token = $tokenResult->plainTextToken;
        $user->expires_in = $tokenModel->expires_at->timestamp;

        $user->loadMissing(['preferredLanguage', 'role']);

        UserActivity::log('login', $user, null, request());

        return new LoginResource($user);
    }

    public function updateUiPreferences(Request $request)
    {
        $validated = $request->validate([
            'dark_mode' => ['sometimes', 'boolean'],
            'sidebar_open' => ['sometimes', 'boolean'],
        ]);

        if ($validated === []) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'preferences' => ['At least one preference is required.'],
                ],
            ], 422);
        }

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'data' => [
                'dark_mode' => (bool) $user->dark_mode,
                'sidebar_open' => (bool) ($user->sidebar_open ?? true),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            UserActivity::log('logout', $user, null, $request);
            $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
        }

        return response()->json([
            'status' => true,
            'message' => trans('messages.logged_out_successfully'),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->loadMissing(['preferredLanguage', 'role.permissions']);
        $permNames = $user->rolePermissionNames();

        return response()->json([
            'data' => new UserResource($user),
            'role' => $user->role ? new RoleResource($user->role) : null,
            'permissions' => $permNames,
        ]);
    }
}
