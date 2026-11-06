<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IssueReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
            'payer_name' => ['required', 'string', 'max:150'],
            'payer_document' => ['nullable', 'string', 'max:20'],
            'description' => ['required', 'string', 'max:500'],
            'payment_method' => ['required', 'string', 'in:pix,cash,card,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_id.required' => 'O lançamento de receita é obrigatório.',
            'payer_name.required' => 'O nome do cliente/pagador é obrigatório.',
            'description.required' => 'A descrição do serviço/venda é obrigatória.',
            'payment_method.required' => 'A forma de pagamento é obrigatória.',
        ];
    }
}
