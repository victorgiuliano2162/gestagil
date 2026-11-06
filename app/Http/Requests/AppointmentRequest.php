<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
