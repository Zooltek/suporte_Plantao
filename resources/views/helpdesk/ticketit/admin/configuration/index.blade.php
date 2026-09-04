@extends('admin.layouts.master')

@section('content')
<div x-data="configurationManager()" class="py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Configurações - Painel de Suporte</h1>
        <p class="text-gray-500">Gerencie chaves de configuração do helpdesk</p>
    </div>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('tickets.admin.configuration.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded shadow-sm hover:bg-green-700">Nova Configuração</a>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chave</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($configurations as $conf)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $conf->id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-800">{{ $conf->key }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ Str::limit($conf->value ?? '-', 60) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ Str::limit($conf->description ?? '-', 80) }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('tickets.admin.configuration.edit', $conf->id) }}" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-600 rounded mr-2">Editar</a>
                                <button @click="deleteConfiguration({{ $conf->id }})" class="inline-flex items-center px-3 py-1 bg-red-100 text-red-600 rounded">Remover</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function configurationManager(){
    return {
        async deleteConfiguration(id){
            if(!confirm('Tem certeza que deseja remover esta configuração?')) return;
            try{
                const res = await fetch(`/admin/api/helpdesk/configuration/${id}`,{ method:'DELETE', headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } });
                if(res.ok){ AppToast.success({ message: 'Configuração removida com sucesso' }); setTimeout(()=>location.reload(),700); }
                else { const json=await res.json().catch(()=>({message:'Erro'})); AppToast.error({ message: json.message||'Erro ao remover configuração' }); }
            }catch(e){ console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
        }
    }
}
</script>
@endsection
