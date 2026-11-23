<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone_e164')) {
            $raw = trim((string) $this->input('phone_e164'));
            $hasPlus = str_starts_with($raw, '+');
            $digits = preg_replace('/\D/', '', $raw);
            $this->merge([
                'phone_e164' => $digits !== '' ? ($hasPlus ? '+'.$digits : $digits) : null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'phone_e164' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{10,11}$|^\+[0-9]{12,15}$/'],
            'document_type' => ['nullable', 'in:cpf,cnpj'],
            'document_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{11}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}|\d{14})$/',
            ],
            'email' => ['nullable', 'email', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do cliente é obrigatório.',
            'phone_e164.regex' => 'Informe um celular/telefone válido com DDD (ex: (81) 99999-8888).',
            'document_number.regex' => 'Informe um CPF (000.000.000-00) ou CNPJ (00.000.000/0000-00) válido.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }
}
