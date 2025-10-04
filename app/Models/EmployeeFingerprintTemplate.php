<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeFingerprintTemplate extends Model
{
    use HasFactory;

    protected $primaryKey = 'template_id';

    protected $fillable = [
        'employee_id',
        'template_index',
        'template_data',
        'template_quality',
        'finger_position'
    ];

    protected $casts = [
        'template_quality' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    // Helper method to get template data as base64
    public function getTemplateDataBase64Attribute()
    {
        return base64_encode($this->template_data);
    }

    // Helper method to set template data from base64
    public function setTemplateDataFromBase64($base64Data)
    {
        $this->template_data = base64_decode($base64Data);
    }
}
