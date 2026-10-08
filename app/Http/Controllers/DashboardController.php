<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Muestra la vista del panel principal (Dashboard).
     */
    public function index()
    {
        $user = Auth::user();
        return view('dashboard.index', compact('user'));
    }
}
