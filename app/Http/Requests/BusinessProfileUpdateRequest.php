<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $raw = trim((string) $this->input('phone'));
            $hasPlus = str_starts_with($raw, '+');
            $digits = preg_replace('/\D/', '', $raw);
            $this->merge([
                'phone' => $digits !== '' ? ($hasPlus ? '+'.$digits : $digits) : null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'store_name' => ['nullable', 'string', 'max:160'],
            'display_name' => ['required', 'string', 'max:160'],
            'activity_description' => ['nullable', 'string', 'max:1000'],
            'document_type' => ['nullable', 'in:cpf,cnpj'],
            'document_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{11}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}|\d{14})$/',
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{10,11}$|^\+[0-9]{12,15}$/'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:160'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:60'],
            'neighborhood' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'size:2'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'O nome de exibição é obrigatório.',
            'document_number.regex' => 'Informe um CPF (000.000.000-00) ou CNPJ (00.000.000/0000-00) válido.',
            'phone.regex' => 'Informe um celular/telefone válido com DDD (ex: (81) 99999-8888).',
            'state.size' => 'O estado (UF) deve ter 2 letras (ex: PE, SP).',
            'logo.image' => 'O logotipo deve ser um arquivo de imagem válido.',
            'logo.mimes' => 'O logotipo deve estar no formato PNG, JPG, JPEG ou WEBP.',
            'logo.max' => 'O logotipo não pode ultrapassar 2MB de tamanho.',
        ];
    }
}
