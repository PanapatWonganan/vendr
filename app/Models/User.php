<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'email_po_approved',
        'email_po_rejected',
        'email_po_notifications',
        'email_pr_notifications',
        'email_pr_approved',
        'email_pr_rejected',
        'email_pr_created',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_po_approved' => 'boolean',
            'email_po_rejected' => 'boolean',
            'email_po_notifications' => 'boolean',
            'email_pr_notifications' => 'boolean',
            'email_pr_approved' => 'boolean',
            'email_pr_rejected' => 'boolean',
            'email_pr_created' => 'boolean',
        ];
    }

    /**
     * Get the department that owns the user.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('department_id', 'additional_data', 'assigned_at', 'expires_at', 'is_active')
            ->withTimestamps();
    }

    /**
     * Get the active roles that belong to the user.
     */
    public function activeRoles(): BelongsToMany
    {
        return $this->roles()
            ->wherePivot('is_active', true)
            ->where('roles.is_active', true)
            ->whereRaw('role_user.expires_at IS NULL OR role_user.expires_at > NOW()');
    }

    /**
     * Check if the user has the given role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->activeRoles()->where('name', $roleName)->exists();
    }

    /**
     * Check if the user has any of the given roles.
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->activeRoles()->whereIn('name', $roleNames)->exists();
    }

    /**
     * Check if the user has all of the given roles.
     */
    public function hasAllRoles(array $roleNames): bool
    {
        return $this->activeRoles()->whereIn('name', $roleNames)->count() === count($roleNames);
    }

    /**
     * Check if the user has the given permission through any of their roles.
     */
    public function hasPermission(string $permissionName): bool
    {
        // Get all active roles of the user
        $roles = $this->activeRoles()->get();
        
        // Check if any of the roles has the permission
        foreach ($roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if the user is a system administrator.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Allow all authenticated users to access the panel
        return true;
    }
}
