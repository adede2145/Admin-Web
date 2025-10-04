<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // User must be admin or super_admin
        $user = auth()->user();
        return $user && $user->role && in_array($user->role->role_name, ['admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'emp_name' => 'required|string|max:100',
            'emp_id' => 'required|string|max:50|unique:employees,employee_id',
            'department_id' => 'required|exists:departments,department_id',
            'rfid_uid' => 'required|string|max:100|unique:employees,rfid_code',
            'primary_template' => 'required|string',
            'backup_template' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'emp_name.required' => 'Employee name is required.',
            'emp_id.required' => 'Employee ID is required.',
            'emp_id.unique' => 'This Employee ID is already in use.',
            'department_id.required' => 'Please select a department.',
            'department_id.exists' => 'Selected department does not exist.',
            'rfid_uid.required' => 'RFID card scan is required.',
            'rfid_uid.unique' => 'This RFID card is already registered.',
            'primary_template.required' => 'Primary fingerprint template is required.',
            'profile_image.image' => 'Profile image must be a valid image file.',
            'profile_image.max' => 'Profile image must not exceed 5MB.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional validation: department restriction for non-super admins
            $user = auth()->user();
            if (
                $user->role->role_name !== 'super_admin' &&
                $user->department_id != $this->department_id
            ) {
                $validator->errors()->add('department_id', 'You can only register employees to your department.');
            }
        });
    }
}
