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
                // ใช้ key เดียวคือ 'company_id' เพื่อความสม่ำเสมอ
                $companyId = session('company_id');

                if (!$companyId) {
                    // ไม่มี company context → ห้ามสร้าง record เพื่อป้องกัน multi-tenancy leak
                    // (เช่น ใน queue/artisan ต้อง set session หรือ pass company_id ก่อน)
                    throw new \RuntimeException(
                        'Cannot create ' . static::class . ' without company context. '
                        . 'Ensure session has company_id or pass company_id explicitly.'
                    );
                }

                $model->company_id = $companyId;
            }
        });
    }

    /**
     * Get the database connection for the model.
     */
    public function getConnectionName()
    {
        // Use company-specific database connection from session
        if (session('company_connection')) {
            return session('company_connection');
        }

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