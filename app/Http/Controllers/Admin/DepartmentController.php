<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\Admin\DepartmentService;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService
    ) {}

    /**
     * Exibe a listagem de departamentos
     */
    public function index(): View
    {
        $departments = $this->departmentService->getAllDepartments();
        $stats = $this->departmentService->getDepartmentStats();

        return view('admin.departments.index', compact('departments', 'stats'));
    }
}
