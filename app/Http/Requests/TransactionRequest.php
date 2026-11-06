<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $raw = $this->input('amount');
            if (is_string($raw)) {
                // Remove separador de milhar e converte vírgula para ponto se necessário
                $cleaned = str_replace(' ', '', $raw);
                if (str_contains($cleaned, ',') && ! str_contains($cleaned, '.')) {
                    $cleaned = str_replace(',', '.', $cleaned);
                } elseif (str_contains($cleaned, '.') && str_contains($cleaned, ',')) {
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                }
                $this->merge(['amount' => $cleaned]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:transaction_categories,id'],
            'payment_method' => ['required', 'in:pix,cash,card,other'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Informe o tipo do lançamento (receita ou despesa).',
            'amount.required' => 'O valor do lançamento é obrigatório.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'occurred_on.required' => 'A data do lançamento é obrigatória.',
            'description.required' => 'Informe uma descrição para o lançamento.',
        ];
    }
}
