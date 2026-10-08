<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Exception;

class CategoriaController extends Controller
{
    /**
     * Muestra la vista principal del módulo.
     */
    public function index()
    {
        return view('categoria.index');
    }

    /**
     * Retorna la lista de registros activos (lActivo = 1) para la tabla AJAX.
     */
    public function gridData(Request $request)
    {
        try {
            $cData = Categoria::where('lActivo', 1)
                ->orderBy('iID', 'desc')
                ->get();

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Datos obtenidos correctamente.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener los registros: ' . $ex->getMessage(),
                'cData'    => [],
            ], 500);
        }
    }

    /**
     * Retorna la información de un registro específico por su ID.
     */
    public function getData($id)
    {
        try {
            $iID = (int)$id;
            $oCategoria = Categoria::where('iID', $iID)
                ->where('lActivo', 1)
                ->first();

            if (!$oCategoria) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El registro solicitado no existe o fue desactivado.',
                    'cData'    => null,
                ], 404);
            }

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Registro encontrado.',
                'cData'    => $oCategoria,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener la información: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    /**
     * Guarda o actualiza un registro (creación con iID == 0, actualización con iID > 0).
     */
    public function saveData(Request $request)
    {
        try {
            $request->validate([
                'cNombre' => 'required|string|max:150',
                'cDescripcion' => 'nullable|string',
            ]);

            $iID = (int) $request->input('iID', 0);
            $cNombre = $request->input('cNombre');
            $cDescripcion = $request->input('cDescripcion');

            if ($iID === 0) {
                $oCategoria = Categoria::create([
                    'cNombre'      => $cNombre,
                    'cDescripcion' => $cDescripcion,
                    'lActivo'      => 1,
                ]);
                $cMensaje = 'El registro se ha creado exitosamente.';
            } else {
                $oCategoria = Categoria::find($iID);
                if (!$oCategoria) {
                    return response()->json([
                        'lSuccess' => false,
                        'cMensaje' => 'No se encontró el registro para actualizar.',
                        'cData'    => null,
                    ], 404);
                }

                $oCategoria->update([
                    'cNombre'      => $cNombre,
                    'cDescripcion' => $cDescripcion,
                ]);
                $cMensaje = 'El registro se ha actualizado exitosamente.';
            }

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => $cMensaje,
                'cData'    => $oCategoria,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al guardar los datos: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    /**
     * Desactivación lógica (lActivo = 0) del registro.
     */
    public function deleteData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID');
            $oCategoria = Categoria::find($iID);

            if (!$oCategoria) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El registro no existe.',
                    'cData'    => null,
                ], 404);
            }

            $oCategoria->lActivo = 0;
            $oCategoria->save();

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'El registro ha sido deshabilitado exitosamente.',
                'cData'    => null,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al eliminar el registro: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }
}
