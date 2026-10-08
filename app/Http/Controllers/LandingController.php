<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Muestra la página de inicio (Landing Page) pública de la Agencia de Viajes.
     */
    public function index()
    {
        return view('landing.index');
    }
}
