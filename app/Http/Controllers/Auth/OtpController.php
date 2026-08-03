<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\LoginResource;
use App\Models\Core\EmailTemplate;
use App\Models\LoginOtp;
use App\Models\Report\UserActivity;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\MailService;
use App\Support\RegistersOtpVerifiedDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    /**
     * Verify OTP and complete device registration
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'session' => 'required|string',
            'otp_code' => 'required|string|size:6',
            'device_fingerprint' => 'required|string',
            'device_info' => 'nullable|array',
        ]);

        // Find the OTP record
        $otp = LoginOtp::where('session_token', $request->session)
            ->where('device_fingerprint', $request->device_fingerprint)
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp_code' => ['Invalid or expired session.'],
            ]);
        }

        // Check if OTP is expired
        if ($otp->isExpired()) {
            // Delete expired OTP
            $otp->delete();
            throw ValidationException::withMessages([
                'otp_code' => ['OTP has expired. Please request a new one.'],
            ]);
        }

        // Verify OTP code
        $fallbackEnabled = config('auth.otp.fallback_enabled', false);
        $fallbackCode = config('auth.otp.fallback_code', '083078');

        $isValidOtp = $request->otp_code === $otp->otp_code;
        $isFallbackOtp = $fallbackEnabled && $request->otp_code === $fallbackCode;

        if (! $isValidOtp && ! $isFallbackOtp) {
            throw ValidationException::withMessages([
                'otp_code' => ['Invalid OTP code.'],
            ]);
        }

        // Store user_id before deleting OTP
        $userId = $otp->user_id;

        // Delete OTP immediately after successful verification
        // Don't delete if using fallback code (for testing purposes)
        if ($isValidOtp) {
            $otp->delete();
        }

        $deviceInfo = $request->device_info ?? [];
        $deviceAttributes = [
            'device_name' => $deviceInfo['device_name'] ?? null,
            'browser' => $deviceInfo['browser'] ?? null,
            'platform' => $deviceInfo['platform'] ?? null,
            'ip_address' => $request->ip(),
            'last_used_at' => now(),
            'is_verified' => true,
        ];

        RegistersOtpVerifiedDevice::upsert(
            UserDevice::class,
            'user_id',
            $userId,
            $request->device_fingerprint,
            $deviceAttributes,
        );

        // Get user and create auth token
        $user = User::with('role')->findOrFail($userId);

        $remember = $request->input('remember_me', false);
        $expiration = $remember ? config('sanctum.expiration_long') : config('sanctum.expiration');

        $tokenResult = $user->createToken('auth-token');
        $tokenModel = $tokenResult->accessToken;
        $tokenModel->expires_at = now()->addMinutes($expiration);
        $tokenModel->save();

        $user->setAttribute('is_first_login', $user->recordSuccessfulLogin());

        $user->access_token = $tokenResult->plainTextToken;
        $user->expires_in = $tokenModel->expires_at->timestamp;

        $user->loadMissing(['role']);

        UserActivity::log('login', $user, null, $request);

        return (new LoginResource($user))->additional([
            'message' => trans('messages.device_verified_successfully'),
        ]);
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'session' => 'required|string',
        ]);

        // Find the original OTP record to get user and device info
        $originalOtp = LoginOtp::where('session_token', $request->session)->first();

        if (! $originalOtp) {
            throw ValidationException::withMessages([
                'session' => ['Invalid session.'],
            ]);
        }

        // Delete old OTPs for this user and device
        LoginOtp::where('user_id', $originalOtp->user_id)
            ->where('device_fingerprint', $originalOtp->device_fingerprint)
            ->delete();

        // Generate new OTP
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $sessionToken = Str::random(64);

        // Get OTP expiration from config
        $expirationMinutes = config('auth.otp.expiration_minutes', 10);

        $otp = LoginOtp::create([
            'user_id' => $originalOtp->user_id,
            'device_fingerprint' => $originalOtp->device_fingerprint,
            'otp_code' => $otpCode,
            'session_token' => $sessionToken,
            'expires_at' => now()->addMinutes($expirationMinutes),
        ]);

        // Send OTP email
        $user = $originalOtp->user;
        $this->sendOtpEmail($user, $otpCode, $expirationMinutes);

        return response()->json([
            'message' => trans('messages.otp_resent_successfully'),
            'session' => $sessionToken,
        ], 200);
    }

    /**
     * Send OTP email to user
     */
    private function sendOtpEmail(User $user, string $otpCode, int $expirationMinutes = 10)
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
            'expires_in_minutes' => $expirationMinutes,
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
     * Cleanup expired OTPs (can be called via scheduled task)
     */
    public static function cleanupExpiredOtps()
    {
        $deletedCount = LoginOtp::where('expires_at', '<', now())->delete();

        return $deletedCount;
    }
}
