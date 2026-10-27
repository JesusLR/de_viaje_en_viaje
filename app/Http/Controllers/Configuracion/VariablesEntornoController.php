<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\VariableSistema;
use Illuminate\Http\Request;

class VariablesEntornoController extends Controller
{
    public function edit()
    {
        return view('configuracion.variables-entorno', ['dias' => VariableSistema::diasSinAbonos()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['dias_sin_abonos' => 'required|integer|min:1|max:3650']);
        VariableSistema::updateOrCreate(['clave' => 'dias_sin_abonos'], ['valor' => $data['dias_sin_abonos'], 'actualizada_por' => $request->user()->id]);
        return redirect()->route('configuracion.variables.edit')->with('success', 'Días sin abonos actualizados correctamente.');
    }
}
