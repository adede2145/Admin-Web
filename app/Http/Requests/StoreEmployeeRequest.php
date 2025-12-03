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
            'emp_id' => 'required|string|max:50|unique:employees,employee_code',
            'department_id' => 'required|exists:departments,department_id',
            'employment_type' => 'required|string|in:full_time,cos,admin,faculty with designation',
            // RFID is optional but must be unique when provided
            // Be permissive on content; length mainly to protect DB index sizes
            'rfid_uid' => 'nullable|string|max:191|unique:employees,rfid_code',
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
            'employment_type.required' => 'Please select an employment type.',
            'employment_type.in' => 'The selected employment type is invalid.',
            'rfid_uid.unique' => 'This RFID card is already registered.',
            'rfid_uid.max' => 'RFID value is too long.',
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
        // Sanitize inputs prior to running validation rules
        $this->prepareForValidation();

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

    /**
     * Normalize and sanitize incoming data before validation.
     */
    protected function prepareForValidation(): void
    {
        $rfid = (string) ($this->input('rfid_uid', ''));
        // Remove non-printable characters (incl. stray reader control bytes) and trim spaces
        $rfid = preg_replace('/[^\x20-\x7E]/', '', $rfid ?? '');
        // Collapse internal whitespace to single spaces and trim
        $rfid = trim(preg_replace('/\s+/', ' ', $rfid));

        // Convert empty string to null so nullable validation works properly
        $rfid = $rfid === '' ? null : $rfid;

        $this->merge([
            'rfid_uid' => $rfid,
        ]);
    }
}
