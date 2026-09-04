<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    // app/Http/Controllers/HomeController.php

    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        return redirect()->route('dashboard');
    }
}
