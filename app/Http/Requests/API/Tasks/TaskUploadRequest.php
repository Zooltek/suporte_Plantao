<?php

namespace App\Http\Requests\API\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class TaskUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:51200', // Limite de 50MB (50 * 1024)
                'mimes:jpg,jpeg,bmp,png,txt,xml,rar,zip,7z,ret,rem,ini,pdf,dat,docx,xlsx,tar,avi,mov,wmv,mp4,3gp,flv,mkv,rm,3g2'
            ],
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
        ];
    }

    /**
     * Customização das mensagens de erro.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Nenhum arquivo foi selecionado.',
            'file.mimes'    => 'A extensão do arquivo selecionado não é permitida.',
            'file.max'      => 'O arquivo não pode ser maior que 50MB.',
        ];
    }
}
