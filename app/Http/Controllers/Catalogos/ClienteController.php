<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Exception;

class ClienteController extends Controller
{
    /**
     * Muestra la vista principal del submódulo de Clientes.
     */
    public function index()
    {
        return view('catalogos.cliente.index');
    }

    /**
     * Retorna la lista de clientes activos (lActivo = 1).
     */
    public function gridData(Request $request)
    {
        try {
            $cData = Cliente::where('lActivo', 1)
                ->orderBy('iID', 'desc')
                ->get()
                ->map(function ($oCliente) {
                    return [
                        'iID'              => $oCliente->iID,
                        'cNombre'          => $oCliente->cNombre,
                        'cPrimerApellido'  => $oCliente->cPrimerApellido,
                        'cSegundoApellido' => $oCliente->cSegundoApellido,
                        'cNombreCompleto'  => $oCliente->nombre_completo,
                        'cTelefono'        => $oCliente->cTelefono ?: 'N/A',
                        'cEmail'           => $oCliente->cEmail ?: 'N/A',
                        'cRFC'             => $oCliente->cRFC ?: 'N/A',
                        'cDireccion'       => $oCliente->cDireccion ?: 'N/A',
                        'lActivo'          => $oCliente->lActivo,
                        'cCreado'          => $oCliente->created_at ? $oCliente->created_at->format('d/m/Y H:i') : '',
                    ];
                });

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Clientes cargados correctamente.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener la lista de clientes: ' . $ex->getMessage(),
                'cData'    => [],
            ], 500);
        }
    }

    /**
     * Retorna la información de un cliente por su ID.
     */
    public function getData($id)
    {
        try {
            $iID = (int) $id;
            $oCliente = Cliente::where('iID', $iID)->where('lActivo', 1)->first();

            if (!$oCliente) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El cliente solicitado no existe o fue deshabilitado.',
                    'cData'    => null,
                ], 404);
            }

            $cData = [
                'iID'              => $oCliente->iID,
                'cNombre'          => $oCliente->cNombre,
                'cPrimerApellido'  => $oCliente->cPrimerApellido,
                'cSegundoApellido' => $oCliente->cSegundoApellido,
                'cTelefono'        => $oCliente->cTelefono,
                'cEmail'           => $oCliente->cEmail,
                'cRFC'             => $oCliente->cRFC,
                'cDireccion'       => $oCliente->cDireccion,
                'lActivo'          => $oCliente->lActivo,
            ];

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Cliente encontrado.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener los datos del cliente: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    /**
     * Guarda o actualiza un cliente (creación con iID == 0, edición con iID > 0).
     */
    public function saveData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID', 0);

            $request->validate([
                'cNombre'          => 'required|string|max:100',
                'cPrimerApellido'  => 'required|string|max:100',
                'cSegundoApellido' => 'nullable|string|max:100',
                'cTelefono'        => 'nullable|string|max:20',
                'cEmail'           => 'nullable|email|max:150',
                'cRFC'             => 'nullable|string|max:20',
                'cDireccion'       => 'nullable|string',
            ], [
                'cNombre.required'         => 'El nombre del cliente es obligatorio.',
                'cPrimerApellido.required' => 'El primer apellido es obligatorio.',
                'cEmail.email'             => 'Ingrese una dirección de correo electrónico válida.',
            ]);

            $cNombre = trim($request->input('cNombre'));
            $cPrimerApellido = trim($request->input('cPrimerApellido'));
            $cSegundoApellido = trim($request->input('cSegundoApellido'));
            $cTelefono = trim($request->input('cTelefono'));
            $cEmail = trim($request->input('cEmail'));
            $cRFC = mb_strtoupper(trim($request->input('cRFC')));
            $cDireccion = trim($request->input('cDireccion'));

            if ($iID === 0) {
                // Alta de Cliente
                $oCliente = Cliente::create([
                    'cNombre'          => $cNombre,
                    'cPrimerApellido'  => $cPrimerApellido,
                    'cSegundoApellido' => $cSegundoApellido,
                    'cTelefono'        => $cTelefono,
                    'cEmail'           => $cEmail,
                    'cRFC'             => $cRFC,
                    'cDireccion'       => $cDireccion,
                    'lActivo'          => 1,
                ]);

                $cMensaje = 'El cliente se ha registrado exitosamente.';
            } else {
                // Edición de Cliente
                $oCliente = Cliente::find($iID);
                if (!$oCliente) {
                    return response()->json([
                        'lSuccess' => false,
                        'cMensaje' => 'No se encontró el registro del cliente a actualizar.',
                        'cData'    => null,
                    ], 404);
                }

                $oCliente->update([
                    'cNombre'          => $cNombre,
                    'cPrimerApellido'  => $cPrimerApellido,
                    'cSegundoApellido' => $cSegundoApellido,
                    'cTelefono'        => $cTelefono,
                    'cEmail'           => $cEmail,
                    'cRFC'             => $cRFC,
                    'cDireccion'       => $cDireccion,
                ]);

                $cMensaje = 'La información del cliente ha sido actualizada correctamente.';
            }

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => $cMensaje,
                'cData'    => [
                    'iID'             => $oCliente->iID,
                    'cNombreCompleto' => $oCliente->nombre_completo,
                    'cEmail'          => $oCliente->cEmail,
                ],
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al guardar la información del cliente: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    /**
     * Desactivación lógica (lActivo = 0) del cliente.
     */
    public function deleteData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID');
            $oCliente = Cliente::find($iID);

            if (!$oCliente) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El cliente no existe.',
                    'cData'    => null,
                ], 404);
            }

            // Baja Lógica
            $oCliente->lActivo = 0;
            $oCliente->save();

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'El cliente ha sido deshabilitado correctamente.',
                'cData'    => null,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al eliminar el cliente: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }
}
