<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmploymentType extends Model
{
    use HasFactory;

    protected $table = 'employment_types';

    protected $fillable = [
        'type_name',
        'display_name',
        'is_active',
        'is_default'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get all active employment types
     */
    public static function getActive()
    {
        return self::where('is_active', true)->orderBy('display_name')->get();
    }

    /**
     * Get all employment types (including inactive)
     */
    public static function getAllTypes()
    {
        return self::orderBy('display_name')->get();
    }

    /**
     * Get type names as array (for form validation)
     */
    public static function getTypeNames()
    {
        return self::where('is_active', true)->pluck('type_name')->toArray();
    }

    /**
     * Get type names with display names (for dropdowns)
     */
    public static function getOptionsForSelect()
    {
        return self::where('is_active', true)
            ->pluck('display_name', 'type_name')
            ->toArray();
    }

    /**
     * Get display name by type_name
     */
    public static function getDisplayName($typeName)
    {
        return self::where('type_name', $typeName)->value('display_name') ?? $typeName;
    }

    /**
     * Relationships
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'employment_type', 'type_name');
    }
}
