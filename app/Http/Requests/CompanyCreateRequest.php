<?php

namespace App\Http\Requests;

use App\Rules\CheckCnpj;
use App\Rules\CheckCep;
use Illuminate\Foundation\Http\FormRequest;

class CompanyCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'cnpj'        => ['required', 'string', new CheckCnpj],
            'responsible' => ['required', 'integer', 'exists:users,id'],
            'zipcode'     => ['required', 'string', new CheckCep],
            'address'     => ['required', 'string', 'max:255'],
            'state'       => ['required', 'integer', 'exists:states,id'],
            'city'        => ['required', 'integer', 'exists:cities,id'],
            'obs'         => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Mensagens de erro personalizadas.
     */
    public function messages(): array
    {
        return [
            'name.required'        => 'O nome da empresa é obrigatório.',
            'cnpj.required'        => 'O CNPJ é obrigatório.',
            'responsible.required' => 'Você deve selecionar um responsável.',
            'responsible.exists'   => 'O responsável selecionado não existe no sistema.',
            'zipcode.required'     => 'O CEP é necessário para o endereço.',
            'state.required'       => 'Selecione o estado.',
            'city.required'        => 'Selecione a cidade.',
            'city.exists'          => 'A cidade selecionada não é válida para este estado.',
        ];
    }

    /**
     * Tradução dos nomes dos atributos para as mensagens.
     */
    public function attributes(): array
    {
        return [
            'name'    => 'Nome da Empresa',
            'cnpj'    => 'CNPJ',
            'zipcode' => 'CEP',
            'address' => 'Endereço',
        ];
    }
}
