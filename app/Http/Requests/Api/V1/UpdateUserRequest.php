<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'required', 'string', 'max:150'],
            'role'        => ['sometimes', 'required', Rule::in([0, 1])],
            'status'      => ['sometimes', 'required', Rule::in(['active', 'inactive', 'suspended'])],
            'daily_quota' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }

}
