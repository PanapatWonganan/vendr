<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $user = $this->record;

        // Auto-assign 'requester' role if no roles were selected
        if ($user->roles()->count() === 0) {
            $requesterRole = Role::where('name', 'requester')->first();

            if ($requesterRole) {
                $user->roles()->attach($requesterRole->id, [
                    'is_active' => true,
                    'assigned_at' => now(),
                ]);
            }
        }
    }
}
