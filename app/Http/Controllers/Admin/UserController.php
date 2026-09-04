<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Exibe a listagem de usuários
     */
    public function index(): View
    {
        $users = User::with('department')->get();
        $departments = Department::all();
        $crmDepartmentId = Department::crmDepartmentId();

        return view('admin.users.index', compact('users', 'departments', 'crmDepartmentId'));
    }
}
