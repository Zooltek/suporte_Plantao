@extends('admin.layouts.master')

@section('title', $item ? 'Editar Item RAT' : 'Novo Item RAT')

@section('content')
<div class="max-w-2xl space-y-5" x-data="ratForm()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <span class="text-gray-600 font-semibold">Implantação</span>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.implantacao.rat.index') }}" class="hover:text-indigo-600 transition-colors">Checklist do RAT</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">{{ $item ? 'Editar' : 'Novo' }}</span>
    </nav>

    <form action="{{ $item ? route('admin.implantacao.rat.update', $item->id) : route('admin.implantacao.rat.store') }}"
          method="POST"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf
        @if($item) @method('PUT') @endif

        <h2 class="text-lg font-black text-gray-900">{{ $item ? 'Editar Item do Checklist' : 'Novo Item do Checklist' }}</h2>

        {{-- Módulo --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Módulo <span class="text-red-500">*</span></label>
            <select name="module_id" required
                    class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50">
                <option value="">Selecione o módulo</option>
                @foreach($modules->groupBy('project') as $project => $projectModules)
                    <optgroup label="{{ $project ?: 'Geral' }}">
                        @foreach($projectModules as $module)
                            <option value="{{ $module->id }}"
                                @selected(old('module_id', $item?->module_id) == $module->id || request('module_id') == $module->id)>
                                {{ $module->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('module_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Grupo --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Grupo <span class="text-red-500">*</span></label>
            <select name="group_id" x-model="groupId"
                    class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50">
                <option value="">— Criar novo grupo —</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}" @selected(old('group_id', $item?->group_id) == $group->id)>
                        {{ $group->name }}
                    </option>
                @endforeach
            </select>
            @error('group_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

            <div x-show="!groupId" x-transition class="mt-2">
                <input type="text" name="new_group_name"
                       value="{{ old('new_group_name') }}"
                       placeholder="Nome do novo grupo..."
                       class="w-full text-sm border border-indigo-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-indigo-50/30">
                @error('new_group_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Label --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Rótulo (exibição) <span class="text-red-500">*</span></label>
            <input type="text" name="label" required maxlength="150"
                   value="{{ old('label', $item?->label) }}"
                   @input="autoFillName"
                   placeholder="Ex: Plano de Contas"
                   class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50">
            @error('label') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Name (slug) --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Identificador interno
                <span class="ml-1 text-gray-400 font-normal normal-case">(snake_case único)</span>
                <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" required maxlength="100" x-model="nameField"
                   value="{{ old('name', $item?->name) }}"
                   placeholder="Ex: cont_plano_contas"
                   class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50 font-mono">
            <p class="text-[11px] text-gray-400 mt-1">Somente letras, números, hífens e underscores. Preenchido automaticamente a partir do rótulo.</p>
            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Ações --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
                {{ $item ? 'Salvar Alterações' : 'Criar Item' }}
            </button>
            <a href="{{ route('admin.implantacao.rat.index') }}"
               class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function ratForm() {
    return {
        groupId: '{{ old('group_id', $item?->group_id ?? '') }}',
        nameField: '{{ old('name', $item?->name ?? '') }}',
        autoFillName(e) {
            if (this.nameField === '' || !{{ $item ? 'false' : 'true' }}) {
                this.nameField = e.target.value
                    .toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s_]/g, '')
                    .trim()
                    .replace(/\s+/g, '_');
            }
        },
    };
}
</script>
@endsection
