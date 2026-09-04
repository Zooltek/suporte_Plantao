@extends('admin.layouts.master')

@section('content')
<div class="py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Editar Configuração</h1>
        <p class="text-gray-500">Editar chave de configuração existente</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
        <form x-data="configurationEditForm({ id: {{ $configuration->id }}, key: '{{ addslashes($configuration->key) }}', value: `{{ addslashes($configuration->value ?? '') }}`, description: `{{ addslashes($configuration->description ?? '') }}` })" @submit.prevent="submit()" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700">Chave</label>
                <input type="text" x-model="form.key" name="key" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Valor</label>
                <input type="text" x-model="form.value" name="value" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea x-model="form.description" name="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" rows="3"></textarea>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('tickets.admin.configuration.index') }}" class="px-4 py-2 mr-2 bg-white border rounded">Cancelar</a>
                <button type="submit" :disabled="submitting" class="px-4 py-2 bg-blue-600 text-white rounded">
                    <span x-show="!submitting">Salvar Alterações</span>
                    <span x-show="submitting">Salvando...</span>
                </button>
            </div>
        </form>
    </div>

    <script>
    function configurationEditForm(initial){
        return {
            submitting:false,
            form:{ id: initial.id, key: initial.key, value: initial.value, description: initial.description },
            async submit(){
                this.submitting = true;
                try{
                    const res = await fetch(`/admin/api/helpdesk/configuration/${this.form.id}`, {
                        method:'PUT',
                        headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                        body: JSON.stringify({ key:this.form.key, value:this.form.value, description:this.form.description })
                    });
                    if(res.ok){ AppToast.success({ message: 'Configuração atualizada com sucesso' }); window.setTimeout(()=>{ window.location.href='{{ route('tickets.admin.configuration.index') }}'; },800); }
                    else { const j=await res.json().catch(()=>({message:'Erro'})); AppToast.error({ message: j.message||'Erro ao atualizar configuração' }); }
                }catch(e){ console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
                finally{ this.submitting = false; }
            }
        }
    }
    </script>
</div>
@endsection
