<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'phone_e164' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{10,15}$/'],
            'document_type' => ['nullable', 'in:cpf,cnpj'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do cliente é obrigatório.',
            'phone_e164.regex' => 'Formato de telefone inválido (ex: 81999998888 ou +5581999998888).',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }
}
