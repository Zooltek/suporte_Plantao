<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        return view('admin.companies.index');
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function store(Request $request)
    {
        // Este método está vazio temporariamente (sonar)
    }

    public function edit(string $id)
    {
        return view('admin.companies.edit');
    }

    public function update(Request $request, string $id)
    {
        // Este método está vazio temporariamente (sonar)
    }

    public function vincular(Request $request)
    {
        // Este método está vazio temporariamente (sonar)
    }

    public function cities(Request $request)
    {
        // Este método está vazio temporariamente (sonar)
    }
}
