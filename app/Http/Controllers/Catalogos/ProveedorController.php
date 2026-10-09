<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Exception;

class ProveedorController extends Controller
{
    public function index()
    {
        return view('catalogos.proveedor.index');
    }

    public function gridData(Request $request)
    {
        try {
            $cData = Proveedor::withCount('servicios')
                ->orderBy('iID', 'desc')
                ->get()
                ->map(function ($oProv) {
                    return [
                        'iID'             => $oProv->iID,
                        'cNombre'         => $oProv->cNombre,
                        'cDescripcion'    => $oProv->cDescripcion ?: 'Sin descripción',
                        'lActivo'         => $oProv->lActivo,
                        'iTotalServicios' => $oProv->servicios_count,
                        'cCreado'         => $oProv->created_at ? $oProv->created_at->format('d/m/Y H:i') : '',
                    ];
                });

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Proveedores cargados correctamente.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener la lista de proveedores: ' . $ex->getMessage(),
                'cData'    => [],
            ], 500);
        }
    }

    public function getData($id)
    {
        try {
            $iID = (int) $id;
            $oProv = Proveedor::find($iID);

            if (!$oProv) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El proveedor no existe.',
                    'cData'    => null,
                ], 404);
            }

            $cData = [
                'iID'          => $oProv->iID,
                'cNombre'      => $oProv->cNombre,
                'cDescripcion' => $oProv->cDescripcion,
                'lActivo'      => $oProv->lActivo,
            ];

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Proveedor encontrado.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener los datos del proveedor: ' . $ex->getMessage(),
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
                    Rule::unique('proveedores', 'cNombre')->ignore($iID, 'iID'),
                ],
                'cDescripcion' => 'nullable|string',
            ], [
                'cNombre.required' => 'El nombre del proveedor es obligatorio.',
                'cNombre.unique'   => 'Ya existe un proveedor registrado con este nombre.',
            ]);

            $cNombre = trim($request->input('cNombre'));
            $cDescripcion = trim($request->input('cDescripcion'));

            if ($iID === 0) {
                $oProv = Proveedor::create([
                    'cNombre'      => $cNombre,
                    'cDescripcion' => $cDescripcion,
                    'lActivo'      => 1,
                    'iIDUsuario'   => auth()->id(),
                ]);
                $cMensaje = 'El proveedor ha sido registrado correctamente.';
            } else {
                $oProv = Proveedor::find($iID);
                if (!$oProv) {
                    return response()->json([
                        'lSuccess' => false,
                        'cMensaje' => 'No se encontró el proveedor a actualizar.',
                        'cData'    => null,
                    ], 404);
                }

                $oProv->update([
                    'cNombre'      => $cNombre,
                    'cDescripcion' => $cDescripcion,
                ]);
                $cMensaje = 'El proveedor ha sido actualizado correctamente.';
            }

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => $cMensaje,
                'cData'    => $oProv,
            ]);
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => collect($ex->errors())->flatten()->first() ?? 'Error de validación.',
                'cData'    => null,
            ], 422);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al guardar el proveedor: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    public function deleteData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID');
            $oProv = Proveedor::withCount('servicios')->find($iID);

            if (!$oProv) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El proveedor no existe.',
                    'cData'    => null,
                ], 404);
            }

            $oProv->lActivo = 0;
            $oProv->save();

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'El proveedor ha sido desactivado correctamente.',
                'cData'    => null,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al cambiar estatus del proveedor: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }
}
