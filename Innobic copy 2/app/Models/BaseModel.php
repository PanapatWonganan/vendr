<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * Get the database connection for the model.
     */
    public function getConnectionName()
    {
        // ถ้ามี company_connection ใน session ให้ใช้
        if (session('company_connection')) {
            return session('company_connection');
        }

        // ถ้าไม่มี ให้ใช้ default connection
        return config('database.default');
    }

    /**
     * Set the connection for all models in the current request
     */
    public static function setCompanyConnection($connection)
    {
        session(['company_connection' => $connection]);
    }

    /**
     * Clear company connection
     */
    public static function clearCompanyConnection()
    {
        session()->forget('company_connection');
    }

    /**
     * Get current company connection
     */
    public static function getCurrentConnection()
    {
        return session('company_connection', config('database.default'));
    }
} 