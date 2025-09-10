<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * Boot the base model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Automatically set company_id when creating records
        static::creating(function ($model) {
            if (in_array('company_id', $model->getFillable()) && !$model->company_id) {
                $model->company_id = session('selected_company_id') ?: session('company_id', 1);
            }
        });
    }

    /**
     * Get the database connection for the model.
     */
    public function getConnectionName()
    {
        // ใช้ default connection เสมอ (single database with company_id filtering)
        return config('database.default');
        
        // Code สำหรับ multi-database (ปิดใช้งานชั่วคราว)
        // if (session('company_connection')) {
        //     return session('company_connection');
        // }
        // return config('database.default');
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