<?php

namespace App\Http\Controllers\Core;

use App\Http\Requests\Core\UserRequest;
use App\Http\Resources\Core\UserResource;
use App\Models\Core\EmailTemplate;
use App\Models\User;
use App\Support\UserUploadPath;
use App\Services\MailService;
use App\Traits\HandlesBase64Uploads;
use App\Traits\HandlesFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request as OrionRequest;

class UserController extends Controller
{
    use DisableAuthorization, HandlesBase64Uploads, HandlesFiles;

    protected $model = User::class;

    protected $request = UserRequest::class;

    protected $resource = UserResource::class;

    protected function keyName(): string
    {
        return 'uuid';
    }

    public function searchableBy(): array
    {
        return [
            'uuid',
            'name',
            'email',
            'status',
            'role.name',
        ];
    }

    public function sortableBy(): array
    {
        return [
            'name',
            'email',
            'status',
            'gender',
            'date_of_birth',
            'role.name',
            'created_at',
        ];
    }

    public function includes(): array
    {
        return ['role', 'preferredLanguage'];
    }

    public function filterableBy(): array
    {
        return [
            'id',
            'status',
            'role.name',
            'uuid',
            'name',
            'email',
            'gender',
            'date_of_birth',
            'preferred_language.name',
        ];
    }

    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        return $query;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return void
     */
    protected function beforeSave(OrionRequest $request, Model $entity)
    {
        if ($entity instanceof User) {
            $userUuid = UserUploadPath::ensureUuid($entity);
            $this->handleFile($request, $entity, 'profile_image', UserUploadPath::profileDir($userUuid));
            $this->handleFile($request, $entity, 'face_image', UserUploadPath::faceDir($userUuid));
        }

        $this->invalidateBaseModelIfProfileChanged($request, $entity);
        if ($request->filled('role_id')) {
            $role = \App\Models\Core\Role::where('id', $request->role_id)->first();
            if ($role) {
                $entity->role_id = $role->id;
            } else {
                $entity->role_id = null;
            }
        }
    }

    protected function beforeUpdate(OrionRequest $request, Model $entity)
    {
        if ($this->isProfileUpdateRequest($request)) {
            $this->assertProfileSelfUpdate($request, $entity);
            $request->replace(collect($request->all())->except(['email'])->all());
        }

        if ($entity instanceof User) {
            $userUuid = UserUploadPath::ensureUuid($entity);
            $this->handleFile($request, $entity, 'profile_image', UserUploadPath::profileDir($userUuid));
            $this->handleFile($request, $entity, 'face_image', UserUploadPath::faceDir($userUuid));
        }

        $this->invalidateBaseModelIfProfileChanged($request, $entity);

        if ($request->filled('role_id')) {
            $role = \App\Models\Core\Role::where('id', $request->role_id)->first();
            if ($role) {
                $entity->role_id = $role->id;
            }
        }
    }

    protected function isProfileUpdateRequest(Request $request): bool
    {
        return $request->is('api/profiles/*');
    }

    protected function assertProfileSelfUpdate(Request $request, Model $entity): void
    {
        $authUser = $request->user();

        if (! $authUser instanceof User || $authUser->uuid !== $entity->uuid) {
            abort(403);
        }
    }

    protected function afterSave(OrionRequest $request, Model $entity)
    {
        $this->syncUserSpatieRole($entity);

        if ($entity->wasRecentlyCreated) {
            $this->sendSetupPasswordEmail($entity);
        }
    }

    protected function afterUpdate(OrionRequest $request, Model $entity)
    {
        $this->syncUserSpatieRole($entity);
    }

    protected function syncUserSpatieRole(Model $entity): void
    {
        if (! $entity instanceof User) {
            return;
        }

        if (! $entity->role_id) {
            $entity->syncRoles([]);

            return;
        }

        $role = \App\Models\Core\Role::query()
            ->whereKey($entity->role_id)
            ->first();

        if ($role) {
            $entity->syncRoles([$role]);
        }
    }

    /**
     * Resend the welcome / setup-password email (pending users only).
     */
    public function resendWelcomeEmail(User $user, Request $request)
    {
        if ($user->status !== 'pending') {
            throw ValidationException::withMessages([
                'email' => ['Welcome email can only be resent for users with pending status.'],
            ]);
        }

        $this->sendSetupPasswordEmail($user);

        return new UserResource($user->fresh());
    }

    protected function sendSetupPasswordEmail(User $entity): void
    {
        $user = $entity->getKey()
            ? User::query()->whereKey($entity->getKey())->first()
            : User::where('email', $entity->email)->first();

        if (! $user) {
            Log::warning("sendSetupPasswordEmail: user not found for email {$entity->email}");

            return;
        }

        if ($user->status !== 'pending') {
            Log::info("sendSetupPasswordEmail skipped: user {$user->id} is not pending (status: {$user->status})");

            return;
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $token,
                'created_at' => now(),
            ]
        );

        $template_data = EmailTemplate::where('slug', 'setup-password')
            ->first();

        if (! $template_data || ! $template_data->status) {
            Log::warning("Cannot send setup password email: Template not found or inactive for user {$user->id}");

            return;
        }

        $data = [
            'name' => $user->name,
            'template_data' => $template_data,
            'verify_link' => config('app.frontend_url').'/auth/password/set?token='.$token,
            'logo' => asset('assets/images/logo.png'),
        ];

        try {
            $subject = render_template($template_data->subject);
            try {
                $htmlContent = view('emails.setup-password', ['data' => $data])->render();

                $result = MailService::sendEmail([
                    'to' => $user->email,
                    'subject' => $subject,
                    'html' => $htmlContent,
                    'template' => $template_data,
                    'user_id' => $user->id,
                ]);

                if (! $result['success']) {
                    Log::error("Password setup email failed for user {$user->id}: ".$result['message']);
                }
            } catch (\Throwable $e) {
                Log::error("Password setup email failed for user {$user->id}: ".$e->getMessage());
            }
        } catch (\Throwable $e) {
            Log::error('Password setup email failed for user '.$user->id.': '.$e->getMessage());
        }
    }

    public function changeStatus(User $user, Request $request)
    {
        $newStatus = $request->status;

        $user->update(['status' => $newStatus]);

        return new UserResource($user->fresh());
    }

    public function getPermissions(User $user)
    {
        return response()->json($user->rolePermissionNames());
    }

    protected function afterDestroy($request, $user)
    {
        $this->deleteFile($user->profile_image);
        $this->deleteFile($user->face_image);
    }

    protected function invalidateBaseModelIfProfileChanged(OrionRequest $request, Model $entity): void
    {
        if (! $entity instanceof User) {
            return;
        }

        $heightChanged = $request->has('height')
            && (int) $request->input('height') !== (int) $entity->getOriginal('height');
        $genderChanged = $request->has('gender')
            && $request->input('gender') !== $entity->getOriginal('gender');
        $useFaceChanged = $request->has('face_mode')
            && $request->input('face_mode') !== $entity->getOriginal('face_mode');

        $faceImageChanged = false;
        if ($request->has('face_image')) {
            $newFace = $request->input('face_image');

            if ($newFace === null) {
                $faceImageChanged = ! empty($entity->getOriginal('face_image'));
            } elseif (is_string($newFace) && str_starts_with($newFace, 'data:')) {
                $faceImageChanged = true;
            }
        }

        if (! $heightChanged && ! $genderChanged && ! $useFaceChanged && ! $faceImageChanged) {
            return;
        }

        if ($entity->base_model_image) {
            $this->deleteFile($entity->base_model_image);
        }

        $entity->clearBaseModelCache();
    }
}
