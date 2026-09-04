@extends('admin.layouts.master')

@section('content')
<div class="py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Criar Agente</h1>
        <p class="text-gray-500">Adicionar novo agente responsável pelos tickets</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
        <form x-data="agentForm()" @submit.prevent="submit()" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" x-model="form.name" name="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">E-mail</label>
                <input type="email" x-model="form.email" name="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Função</label>
                <input type="text" x-model="form.role" name="role" class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div class="flex justify-end">
                <a href="{{ route('tickets.admin.agent.index') }}" class="px-4 py-2 mr-2 bg-white border rounded">Cancelar</a>
                <button type="submit" :disabled="submitting" class="px-4 py-2 bg-blue-600 text-white rounded">
                    <span x-show="!submitting">Criar Agente</span>
                    <span x-show="submitting">Salvando...</span>
                </button>
            </div>
        </form>
    </div>

    <script>
    function agentForm(){
        return {
            submitting:false,
            form:{ name:'', email:'', role:'' },
            async submit(){
                this.submitting = true;
                try{
                    const res = await fetch('/admin/api/helpdesk/agent', {
                        method:'POST',
                        headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                        body: JSON.stringify(this.form)
                    });
                    if(res.ok){ AppToast.success({ message: 'Agente criado com sucesso' }); window.setTimeout(() => { window.location.href='{{ route('tickets.admin.agent.index') }}'; },800); }
                    else { const j=await res.json().catch(()=>({message:'Erro'})); AppToast.error({ message: j.message||'Erro ao criar agente' }); }
                }catch(e){ console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
                finally{ this.submitting = false; }
            }
        }
    }
    </script>
</div>
@endsection
