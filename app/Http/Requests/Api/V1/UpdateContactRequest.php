<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
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
            'name'  => ['sometimes', 'required', 'string', 'max:150'],
            'phone' => ['sometimes', 'required', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'note'  => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

}
