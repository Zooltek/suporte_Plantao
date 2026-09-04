@extends('admin.layouts.master')

@section('content')
<div x-data="agentManager()" class="py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Agentes - Painel de Suporte</h1>
        <p class="text-gray-500">Gerencie os agentes responsáveis pelos tickets</p>
    </div>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('tickets.admin.agent.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded shadow-sm hover:bg-green-700">Novo Agente</a>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Função</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($agents as $agent)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $agent->id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-800">{{ $agent->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $agent->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $agent->role ?? '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('tickets.admin.agent.edit', $agent->id) }}" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-600 rounded mr-2">Editar</a>
                                <button @click="deleteAgent({{ $agent->id }})" class="inline-flex items-center px-3 py-1 bg-red-100 text-red-600 rounded">Remover</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function agentManager(){
    return {
        async deleteAgent(id){
            if(!confirm('Tem certeza que deseja remover este agente?')) return;
            try{
                const res = await fetch(`/admin/api/helpdesk/agent/${id}`,{ method:'DELETE', headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } });
                if(res.ok){ AppToast.success({ message: 'Agente removido com sucesso' }); setTimeout(()=>location.reload(),700); }
                else { const json=await res.json().catch(()=>({message:'Erro'})); AppToast.error({ message: json.message||'Erro ao remover agente' }); }
            }catch(e){ console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
        }
    }
}
</script>
@endsection
