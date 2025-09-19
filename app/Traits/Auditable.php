<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::updating(function ($model) {
            if (!auth()->check()) return;

            $oldValues = $model->getOriginal();
            $newValues = $model->getAttributes();
            
            // Only store changed attributes
            $changedAttributes = array_diff_assoc($newValues, $oldValues);
            if (empty($changedAttributes)) return;

            $oldValuesToStore = array_intersect_key($oldValues, $changedAttributes);
            $newValuesToStore = array_intersect_key($newValues, $changedAttributes);

            AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'edit',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'old_values' => $oldValuesToStore,
                'new_values' => $newValuesToStore,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });

        static::created(function ($model) {
            if (!auth()->check()) return;

            AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'create',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'new_values' => $model->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });

        static::deleted(function ($model) {
            if (!auth()->check()) return;

            AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'delete',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'old_values' => $model->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });
    }
}