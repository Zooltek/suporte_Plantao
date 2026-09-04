@extends('admin.layouts.master')

@section('content')
<div class="py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Criar Categoria</h1>
        <p class="text-gray-500">Adicionar nova categoria/departamento</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
        <form x-data="ticketitCategoryForm()" @submit.prevent="submit()" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" x-model="form.name" name="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Cor</label>
                <input type="color" x-model="form.color" name="color" class="mt-1 h-10 w-24 rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div class="flex justify-end">
                <a href="{{ route('v2.tasks.tickets.admin.category.index') }}" class="px-4 py-2 mr-2 bg-white border rounded">Cancelar</a>
                <button type="submit" :disabled="submitting" class="px-4 py-2 bg-blue-600 text-white rounded">
                    <span x-show="!submitting">Criar Categoria</span>
                    <span x-show="submitting">Salvando...</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
