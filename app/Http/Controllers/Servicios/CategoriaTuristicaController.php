<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Models\CategoriaTuristica;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Exception;

class CategoriaTuristicaController extends Controller
{
    public function index()
    {
        return view('servicios.categoria.index');
    }

    public function gridData(Request $request)
    {
        try {
            $cData = CategoriaTuristica::withCount('servicios')
                ->orderBy('iID', 'desc')
                ->get()
                ->map(function ($oCat) {
                    return [
                        'iID'          => $oCat->iID,
                        'cNombre'      => $oCat->cNombre,
                        'cDescripcion' => $oCat->cDescripcion ?: 'Sin descripción',
                        'cImagen'      => $oCat->cImagen ? asset(str_replace('public/', 'storage/', $oCat->cImagen)) : null,
                        'lActivo'      => $oCat->lActivo,
                        'iTotalServicios' => $oCat->servicios_count,
                        'cCreado'      => $oCat->created_at ? $oCat->created_at->format('d/m/Y H:i') : '',
                    ];
                });

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Categorías turísticas cargadas correctamente.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener la lista de categorías: ' . $ex->getMessage(),
                'cData'    => [],
            ], 500);
        }
    }

    public function getData($id)
    {
        try {
            $iID = (int) $id;
            $oCat = CategoriaTuristica::find($iID);

            if (!$oCat) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'La categoría turística no existe.',
                    'cData'    => null,
                ], 404);
            }

            $cData = [
                'iID'          => $oCat->iID,
                'cNombre'      => $oCat->cNombre,
                'cDescripcion' => $oCat->cDescripcion,
                'lActivo'      => $oCat->lActivo,
            ];

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Categoría encontrada.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener los datos de la categoría: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    public function saveData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID', 0);

            $request->validate([
                'cNombre' => [
                    'required',
                    'string',
                    'max:150',
                    Rule::unique('categoria_turisticas', 'cNombre')->ignore($iID, 'iID'),
                ],
                'cDescripcion' => 'nullable|string',
            ], [
                'cNombre.required' => 'El nombre de la categoría es obligatorio.',
                'cNombre.unique'   => 'Ya existe una categoría turística registrada con este nombre.',
            ]);

            $cNombre = trim($request->input('cNombre'));
            $cDescripcion = trim($request->input('cDescripcion'));

            if ($iID === 0) {
                $oCat = CategoriaTuristica::create([
                    'cNombre'      => $cNombre,
                    'cDescripcion' => $cDescripcion,
                    'lActivo'      => 1,
                    'iIDUsuario'   => auth()->id(),
                ]);
                $cMensaje = 'La categoría turística ha sido registrada correctamente.';
            } else {
                $oCat = CategoriaTuristica::find($iID);
                if (!$oCat) {
                    return response()->json([
                        'lSuccess' => false,
                        'cMensaje' => 'No se encontró la categoría a actualizar.',
                        'cData'    => null,
                    ], 404);
                }

                $oCat->update([
                    'cNombre'      => $cNombre,
                    'cDescripcion' => $cDescripcion,
                ]);
                $cMensaje = 'La categoría turística ha sido actualizada correctamente.';
            }

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => $cMensaje,
                'cData'    => $oCat,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al guardar la categoría: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    public function deleteData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID');
            $oCat = CategoriaTuristica::withCount('servicios')->find($iID);

            if (!$oCat) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'La categoría no existe.',
                    'cData'    => null,
                ], 404);
            }

            // Regla: Evitar eliminación o advertir si tiene servicios asociados
            if ($oCat->servicios_count > 0) {
                // Desactivación lógica únicamente
                $oCat->lActivo = 0;
                $oCat->save();
                return response()->json([
                    'lSuccess' => true,
                    'cMensaje' => "La categoría se ha desactivado. Conserva sus {$oCat->servicios_count} servicios asociados en el historial.",
                    'cData'    => null,
                ]);
            }

            $oCat->lActivo = 0;
            $oCat->save();

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'La categoría turística ha sido desactivada correctamente.',
                'cData'    => null,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al cambiar estatus de la categoría: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }
}
