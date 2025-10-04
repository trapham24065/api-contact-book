<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'name'        => ['required', 'string', 'max:150'],
            'email'       => ['required', 'email', 'unique:users,email'],
            'password'    => ['required', Password::min(8)],
            'role'        => ['required', Rule::in([0, 1])], // Chỉ chấp nhận role 0 hoặc 1
            'daily_quota' => ['sometimes', 'integer', 'min:0'],
        ];
    }

}
