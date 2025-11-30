<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    /**
     * Use default database connection (shared across all companies)
     */
    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'code',
        'database_connection',
        'display_name',
        'logo',
        'description',
        'address',
        'tax_id',
        'phone',
        'email',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    // Static method to get available companies
    public static function getActiveCompanies()
    {
        return self::where('is_active', true)->orderBy('display_name')->get();
    }

    // Helper method to get database connection name
    public function getDatabaseConnection()
    {
        return $this->database_connection;
    }

    // Helper method to check if company is active
    public function isActive()
    {
        return $this->is_active;
    }

    // Method to get company logo URL
    public function getLogoUrl()
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return asset('assets/img/innobic.png'); // default logo
    }
}
