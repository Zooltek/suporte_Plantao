@extends('admin.layouts.master')

@section('content')
<div x-data="statusManager()" class="py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Status - Painel de Suporte</h1>
        <p class="text-gray-500">Gerencie os status utilizados nos tickets</p>
    </div>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('tickets.admin.status.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded shadow-sm hover:bg-green-700">Novo Status</a>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cor</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($statuses as $status)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $status->id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-800">{{ $status->name }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-block w-6 h-6 rounded" style="background: {{ $status->color }}"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('tickets.admin.status.edit', $status->id) }}" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-600 rounded mr-2">Editar</a>
                                <button @click="deleteStatus({{ $status->id }})" class="inline-flex items-center px-3 py-1 bg-red-100 text-red-600 rounded">Remover</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function statusManager() {
    return {
        async deleteStatus(id) {
            if (!confirm('Tem certeza que deseja remover este status?')) return;
            try {
                const res = await fetch(`/admin/api/helpdesk/status/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
                });
                if (res.ok) {
                    AppToast.success({ message: 'Status removido com sucesso' });
                    setTimeout(() => location.reload(), 800);
                } else {
                    const json = await res.json().catch(()=>({message:'Erro'}));
                    AppToast.error({ message: json.message || 'Erro ao remover status' });
                }
            } catch (e) {
                console.error(e);
                AppToast.error({ message: 'Erro ao comunicar com servidor' });
            }
        },
    }
    }
}
</script>
@endsection
