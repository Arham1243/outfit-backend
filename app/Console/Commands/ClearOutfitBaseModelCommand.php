<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearOutfitBaseModelCommand extends Command
{
    protected $signature = 'outfits:clear-base-model {uuid? : User UUID to clear; omit to clear all users}';

    protected $description = 'Clear cached generic base model so the next outfit generation rebuilds it';

    public function handle(): int
    {
        $uuid = $this->argument('uuid');

        $query = User::query();

        if (is_string($uuid) && $uuid !== '') {
            $query->where('uuid', $uuid);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->error('No matching users found.');

            return self::FAILURE;
        }

        foreach ($users as $user) {
            if ($user->base_model_image) {
                Storage::disk('public')->delete($user->base_model_image);
            }

            $user->clearBaseModelCache();
            $user->save();

            $this->line(sprintf('Cleared base model cache for %s (%s).', $user->email ?? 'user', $user->uuid));
        }

        $this->info('Done. Restart the queue worker if it is running, then generate outfits again.');

        return self::SUCCESS;
    }
}
