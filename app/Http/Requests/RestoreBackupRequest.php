<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'backup_file' => ['required', 'file', 'max:20480'], // 20 MB max
            'mode' => ['required', 'string', 'in:merge,replace'],
            'confirmed_overwrite' => ['nullable', 'boolean'],
            'confirm_text' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'backup_file.required' => 'O arquivo de backup é obrigatório.',
            'backup_file.file' => 'O arquivo enviado é inválido.',
            'backup_file.max' => 'O arquivo de backup não pode exceder 20 MB.',
            'mode.required' => 'O modo de restauração é obrigatório.',
            'mode.in' => 'O modo deve ser mesclar (merge) ou sobrescrever (replace).',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('mode') === 'replace') {
                if (! $this->boolean('confirmed_overwrite')) {
                    $validator->errors()->add('confirmed_overwrite', 'A confirmação explícita (double check) é obrigatória para sobrescrever dados.');
                }

                if (strtoupper(trim((string) $this->input('confirm_text'))) !== 'SOBRESCREVER') {
                    $validator->errors()->add('confirm_text', 'Digite SOBRESCREVER para confirmar a substituição dos dados.');
                }
            }
        });
    }
}
