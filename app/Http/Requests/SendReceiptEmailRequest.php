<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendReceiptEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe o endereço de e-mail do destinatário.',
            'email.email' => 'Informe um endereço de e-mail válido.',
        ];
    }
}
