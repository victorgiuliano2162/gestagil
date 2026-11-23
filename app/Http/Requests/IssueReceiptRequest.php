<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => [
                'required',
                'integer',
                Rule::exists('transactions', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at'),
            ],
            'payer_name' => ['required', 'string', 'max:150'],
            'payer_document' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{11}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}|\d{14})$/',
            ],
            'description' => ['required', 'string', 'max:500'],
            'payment_method' => ['required', 'string', 'in:pix,cash,card,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_id.required' => 'O lançamento de receita é obrigatório.',
            'payer_name.required' => 'O nome do cliente/pagador é obrigatório.',
            'payer_document.regex' => 'Informe um CPF (000.000.000-00) ou CNPJ (00.000.000/0000-00) válido.',
            'description.required' => 'A descrição do serviço/venda é obrigatória.',
            'payment_method.required' => 'A forma de pagamento é obrigatória.',
        ];
    }
}
