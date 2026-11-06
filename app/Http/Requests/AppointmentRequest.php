<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('expected_amount')) {
            $raw = $this->input('expected_amount');
            if (is_string($raw)) {
                $cleaned = str_replace(' ', '', $raw);
                if (str_contains($cleaned, ',') && ! str_contains($cleaned, '.')) {
                    $cleaned = str_replace(',', '.', $cleaned);
                } elseif (str_contains($cleaned, '.') && str_contains($cleaned, ',')) {
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                }
                $this->merge(['expected_amount' => $cleaned]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'service_description' => ['required', 'string', 'max:2000'],
            'service_at' => ['required', 'date'],
            'technical_notes' => ['nullable', 'string', 'max:2000'],
            'expected_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'O cliente é obrigatório.',
            'client_id.exists' => 'O cliente selecionado não foi encontrado.',
            'service_description.required' => 'Informe a descrição do serviço / atendimento.',
            'service_at.required' => 'Informe a data e horário do atendimento.',
            'expected_amount.numeric' => 'O valor previsto deve ser numérico.',
        ];
    }
}
