<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\VariableSistema;
use Illuminate\Http\Request;

class VariablesEntornoController extends Controller
{
    public function edit()
    {
        return view('configuracion.variables-entorno', ['dias' => VariableSistema::diasSinAbonos(), 'whatsappVentaAutomatica' => VariableSistema::envioAutomaticoVentaWhatsApp()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'dias_sin_abonos' => 'required_without:whatsapp_venta_automatica|integer|min:1|max:3650',
            'whatsapp_venta_automatica' => 'required_without:dias_sin_abonos|boolean',
        ]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request) {
            foreach ($data as $clave => $valor) {
                VariableSistema::updateOrCreate(['clave' => $clave], ['valor' => (int) $valor, 'actualizada_por' => $request->user()->id]);
            }
        });
        return redirect()->route('configuracion.variables.edit')->with('success', 'Variables de entorno actualizadas correctamente.');
    }
}
