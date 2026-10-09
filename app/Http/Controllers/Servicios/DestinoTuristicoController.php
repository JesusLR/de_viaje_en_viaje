<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Models\DestinoTuristico;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Exception;

class DestinoTuristicoController extends Controller
{
    public function index()
    {
        return view('servicios.destino.index');
    }

    public function gridData(Request $request)
    {
        try {
            $cData = DestinoTuristico::withCount('servicios')
                ->orderBy('iID', 'desc')
                ->get()
                ->map(function ($oDest) {
                    return [
                        'iID'                => $oDest->iID,
                        'cNombre'            => $oDest->cNombre,
                        'cPais'              => $oDest->cPais ?: 'N/A',
                        'cEstado'            => $oDest->cEstado ?: 'N/A',
                        'cCiudad'            => $oDest->cCiudad ?: 'N/A',
                        'cUbicacionCompleta' => $oDest->ubicacion_completa,
                        'cDescripcion'       => $oDest->cDescripcion ?: 'Sin descripción',
                        'lActivo'            => $oDest->lActivo,
                        'iTotalServicios'    => $oDest->servicios_count,
                        'cCreado'            => $oDest->created_at ? $oDest->created_at->format('d/m/Y H:i') : '',
                    ];
                });

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Destinos turísticos cargados correctamente.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener la lista de destinos: ' . $ex->getMessage(),
                'cData'    => [],
            ], 500);
        }
    }

    public function getData($id)
    {
        try {
            $iID = (int) $id;
            $oDest = DestinoTuristico::find($iID);

            if (!$oDest) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El destino turístico no existe.',
                    'cData'    => null,
                ], 404);
            }

            $cData = [
                'iID'          => $oDest->iID,
                'cNombre'      => $oDest->cNombre,
                'cPais'        => $oDest->cPais,
                'cEstado'      => $oDest->cEstado,
                'cCiudad'      => $oDest->cCiudad,
                'cDescripcion' => $oDest->cDescripcion,
                'lActivo'      => $oDest->lActivo,
            ];

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Destino encontrado.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener los datos del destino: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    public function saveData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID', 0);

            $request->validate([
                'cNombre' => 'required|string|max:150',
                'cPais'   => 'nullable|string|max:100',
                'cEstado' => 'nullable|string|max:100',
                'cCiudad' => 'nullable|string|max:100',
                'cDescripcion' => 'nullable|string',
            ], [
                'cNombre.required' => 'El nombre del destino es obligatorio.',
            ]);

            $cNombre = trim($request->input('cNombre'));
            $cPais = trim($request->input('cPais'));
            $cEstado = trim($request->input('cEstado'));
            $cCiudad = trim($request->input('cCiudad'));
            $cDescripcion = trim($request->input('cDescripcion'));

            // Validar restricción de ubicación única
            $oExiste = DestinoTuristico::where('cNombre', $cNombre)
                ->where('cPais', $cPais)
                ->where('cEstado', $cEstado)
                ->where('cCiudad', $cCiudad)
                ->where('iID', '!=', $iID)
                ->first();

            if ($oExiste) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'Ya existe un destino registrado con el mismo nombre y ubicación geográfica.',
                    'cData'    => null,
                ], 422);
            }

            if ($iID === 0) {
                $oDest = DestinoTuristico::create([
                    'cNombre'      => $cNombre,
                    'cPais'        => $cPais,
                    'cEstado'      => $cEstado,
                    'cCiudad'      => $cCiudad,
                    'cDescripcion' => $cDescripcion,
                    'lActivo'      => 1,
                    'iIDUsuario'   => auth()->id(),
                ]);
                $cMensaje = 'El destino turístico ha sido registrado correctamente.';
            } else {
                $oDest = DestinoTuristico::find($iID);
                if (!$oDest) {
                    return response()->json([
                        'lSuccess' => false,
                        'cMensaje' => 'No se encontró el destino a actualizar.',
                        'cData'    => null,
                    ], 404);
                }

                $oDest->update([
                    'cNombre'      => $cNombre,
                    'cPais'        => $cPais,
                    'cEstado'      => $cEstado,
                    'cCiudad'      => $cCiudad,
                    'cDescripcion' => $cDescripcion,
                ]);
                $cMensaje = 'El destino turístico ha sido actualizado correctamente.';
            }

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => $cMensaje,
                'cData'    => $oDest,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al guardar el destino: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    public function deleteData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID');
            $oDest = DestinoTuristico::withCount('servicios')->find($iID);

            if (!$oDest) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El destino no existe.',
                    'cData'    => null,
                ], 404);
            }

            $oDest->lActivo = 0;
            $oDest->save();

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'El destino turístico ha sido desactivado correctamente.',
                'cData'    => null,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al cambiar estatus del destino: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }
}
