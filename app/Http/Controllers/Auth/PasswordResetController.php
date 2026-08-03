<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\LoginResource;
use App\Models\Core\EmailTemplate;
use App\Models\Report\UserActivity;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => trans('messages.email_invalid'),
            ], 422);
        }

        UserActivity::log('forgot_password_request', $user, $email, $request);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $token,
                'created_at' => now(),
            ]
        );

        $template_data = EmailTemplate::where('slug', 'reset-password')
            ->first();

        $data = [
            'name' => $user->name,
            'template_data' => $template_data,
            'verify_link' => config('app.frontend_url').'/auth/password/reset?token='.$token,
            'logo' => asset('assets/images/logo.png'),
        ];

        if (! $template_data || ! $template_data->status) {
            return response()->json([
                'status' => true,
                'message' => trans('messages.password_reset_request_received'),
            ]);
        }

        // Render the Blade template into raw HTML
        $htmlContent = view('emails.reset-password', ['data' => $data])->render();
        $subject = render_template($template_data->subject);

        // Send email using MailService
        $result = MailService::sendEmail([
            'to' => $email,
            'subject' => $subject,
            'html' => $htmlContent,
            'template' => $template_data,
        ]);

        if (! $result['success']) {
            $statusCode = $result['status_code'] ?? 500;

            return response()->json([
                'status' => false,
                'message' => $result['message'],
            ], $statusCode);
        }

        return response()->json([
            'status' => true,
            'message' => trans('messages.password_reset_link_sent'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data = $request->only('token', 'password');

        $record = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if (! $record) {
            return response()->json([
                'message' => trans('messages.session_expired'),
                'errors' => ['email' => [trans('messages.session_expired')]],
            ], 422);
        }

        $user = User::where('email', $record->email)->first();

        if (! $user) {
            return response()->json([
                'message' => trans('messages.user_not_found'),
                'errors' => ['email' => [trans('messages.user_not_found')]],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        UserActivity::log('password_updated', $user, null, $request);

        return response()->json(['status' => true, 'message' => trans('messages.password_reset_successful')]);
    }

    public function setupPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if (! $record) {
            return response()->json([
                'message' => trans('messages.session_expired'),
                'errors' => ['email' => [trans('messages.session_expired')]],
            ], 422);
        }

        $user = User::where('email', $record->email)->first();

        if (! $user) {
            return response()->json([
                'message' => trans('messages.user_not_found'),
                'errors' => ['email' => [trans('messages.user_not_found')]],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        Cache::put('password_setup_consumed:'.$request->token, true, now()->addDays(90));

        DB::table('password_reset_tokens')->where('email', $record->email)->delete();

        UserActivity::log('password_setup', $user, null, $request);

        $expiration = config('sanctum.expiration');
        $tokenResult = $user->createToken('auth-token');
        $tokenModel = $tokenResult->accessToken;
        $tokenModel->expires_at = now()->addMinutes($expiration);
        $tokenModel->save();

        $user->setAttribute('is_first_login', $user->recordSuccessfulLogin());

        $user->access_token = $tokenResult->plainTextToken;
        $user->expires_in = $tokenModel->expires_at->timestamp;

        return new LoginResource($user);
    }

    /**
     * Check whether a password-setup token is still valid or was already used.
     */
    public function setupPasswordTokenStatus(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = $request->query('token');
        $record = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->first();

        if ($record) {
            return response()->json(['status' => 'valid']);
        }

        if (Cache::has('password_setup_consumed:'.$token)) {
            return response()->json(['status' => 'already_set']);
        }

        return response()->json(['status' => 'invalid'], 422);
    }
}
