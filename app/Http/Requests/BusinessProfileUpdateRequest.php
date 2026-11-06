<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['nullable', 'string', 'max:160'],
            'display_name' => ['required', 'string', 'max:160'],
            'activity_description' => ['nullable', 'string', 'max:1000'],
            'document_type' => ['nullable', 'in:cpf,cnpj'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:160'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:60'],
            'neighborhood' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'size:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'O nome de exibição é obrigatório.',
            'state.size' => 'O estado (UF) deve ter 2 letras (ex: PE, SP).',
        ];
    }
}
