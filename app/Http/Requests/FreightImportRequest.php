<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class FreightImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorização pode ser implementada conforme necessário
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:50000', // 50MB máximo
            ],
            'client_name' => [
                'required',
                'string',
                'max:255',
            ],
            'client_document' => [
                'required',
                'string',
                'max:50',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'O arquivo CSV é obrigatório.',
            'csv_file.file' => 'O arquivo deve ser um arquivo válido.',
            'csv_file.mimes' => 'O arquivo deve ser do tipo CSV ou TXT.',
            'csv_file.max' => 'O arquivo não pode ser maior que 50MB.',

            'client_name.required' => 'O nome do cliente é obrigatório.',
            'client_name.string' => 'O nome do cliente deve ser um texto.',
            'client_name.max' => 'O nome do cliente não pode ter mais de 255 caracteres.',

            'client_document.required' => 'O documento do cliente é obrigatório.',
            'client_document.string' => 'O documento do cliente deve ser um texto.',
            'client_document.max' => 'O documento do cliente não pode ter mais de 50 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'csv_file' => 'arquivo CSV',
            'client_name' => 'nome do cliente',
            'client_document' => 'documento do cliente',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->all();

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Dados de entrada inválidos',
                'errors' => $errors,
                'details' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Limpar e normalizar dados antes da validação
        $this->merge([
            'client_name' => trim($this->client_name ?? ''),
            'client_document' => trim($this->client_document ?? ''),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validações customizadas adicionais

            if ($this->hasFile('csv_file')) {
                $file = $this->file('csv_file');

                // Verificar se o arquivo tem conteúdo
                if ($file->getSize() === 0) {
                    $validator->errors()->add('csv_file', 'O arquivo CSV não pode estar vazio.');
                }

                // Verificar se é possível ler o arquivo
                try {
                    $handle = fopen($file->getPathname(), 'r');
                    if ($handle === false) {
                        $validator->errors()->add('csv_file', 'Não foi possível ler o arquivo CSV.');
                    } else {
                        // Verificar se tem pelo menos uma linha (header)
                        $firstLine = fgets($handle);
                        if ($firstLine === false) {
                            $validator->errors()->add('csv_file', 'O arquivo CSV parece estar corrompido.');
                        }
                        fclose($handle);
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('csv_file', 'Erro ao processar o arquivo CSV: ' . $e->getMessage());
                }
            }

            // Validar formato do documento (exemplo para CNPJ/CPF brasileiro)
            if ($this->filled('client_document')) {
                $document = preg_replace('/[^\d]/', '', $this->client_document);

                if (strlen($document) !== 11 && strlen($document) !== 14) {
                    $validator->errors()->add('client_document', 'O documento deve ser um CPF (11 dígitos) ou CNPJ (14 dígitos).');
                }
            }
        });
    }
}
