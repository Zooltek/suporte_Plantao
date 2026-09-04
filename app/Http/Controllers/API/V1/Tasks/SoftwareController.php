<?php

namespace App\Http\Controllers\API\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Models\Software;
use Illuminate\Http\JsonResponse;

class SoftwareController extends Controller
{
    /**
     * Lista todos os sistemas/softwares disponíveis para associação em tarefas.
     */
    public function index(): JsonResponse
    {
        $softwares = Software::orderBy('name')
            ->get(['id', 'name', 'version']);

        return response()->json(['data' => $softwares]);
    }
}
