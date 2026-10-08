<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Exception;

class UsuarioController extends Controller
{
    /**
     * Muestra la vista principal del submódulo de Usuarios.
     */
    public function index()
    {
        return view('admin.usuario.index');
    }

    /**
     * Retorna la lista de usuarios activos (lActivo = 1).
     */
    public function gridData(Request $request)
    {
        try {
            $cData = User::where('lActivo', 1)
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($oUser) {
                    return [
                        'iID'                => $oUser->id,
                        'cNombre'            => $oUser->name,
                        'cPrimerApellido'    => $oUser->cPrimerApellido,
                        'cSegundoApellido'   => $oUser->cSegundoApellido,
                        'cNombreCompleto'    => $oUser->nombre_completo ?: $oUser->name,
                        'cTelefono'          => $oUser->cTelefono ?: 'N/A',
                        'cEmail'             => $oUser->email,
                        'lActivo'            => $oUser->lActivo,
                        'cCreado'            => $oUser->created_at ? $oUser->created_at->format('d/m/Y H:i') : '',
                    ];
                });

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Usuarios cargados correctamente.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener la lista de usuarios: ' . $ex->getMessage(),
                'cData'    => [],
            ], 500);
        }
    }

    /**
     * Retorna los datos de un usuario por su ID.
     */
    public function getData($id)
    {
        try {
            $iID = (int) $id;
            $oUser = User::where('id', $iID)->first();

            if (!$oUser) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El usuario no existe.',
                    'cData'    => null,
                ], 404);
            }

            $cData = [
                'iID'              => $oUser->id,
                'cNombre'          => $oUser->name,
                'cPrimerApellido'  => $oUser->cPrimerApellido,
                'cSegundoApellido' => $oUser->cSegundoApellido,
                'cTelefono'        => $oUser->cTelefono,
                'cEmail'           => $oUser->email,
                'lActivo'          => $oUser->lActivo,
            ];

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Usuario encontrado.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener datos del usuario: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    /**
     * Crea (iID == 0) o actualiza (iID > 0) un usuario.
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
                'cEmail'           => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($iID),
                ],
                'cPassword' => 'nullable|string|min:6',
            ], [
                'cNombre.required'          => 'El nombre es obligatorio.',
                'cPrimerApellido.required'  => 'El primer apellido es obligatorio.',
                'cEmail.required'           => 'El correo electrónico es obligatorio.',
                'cEmail.email'              => 'Ingrese un correo electrónico válido.',
                'cEmail.unique'             => 'Este correo electrónico ya se encuentra registrado.',
            ]);

            $cNombre = trim($request->input('cNombre'));
            $cPrimerApellido = trim($request->input('cPrimerApellido'));
            $cSegundoApellido = trim($request->input('cSegundoApellido'));
            $cTelefono = trim($request->input('cTelefono'));
            $cEmail = trim($request->input('cEmail'));
            $cPassword = $request->input('cPassword');

            if ($iID === 0) {
                // Nuevo Usuario: Contraseña inicial por defecto igual a su correo de acceso ($cEmail)
                $cPasswordFinal = !empty($cPassword) ? $cPassword : $cEmail;

                $oUser = User::create([
                    'name'             => $cNombre,
                    'cPrimerApellido'  => $cPrimerApellido,
                    'cSegundoApellido' => $cSegundoApellido,
                    'cTelefono'        => $cTelefono,
                    'email'            => $cEmail,
                    'password'         => Hash::make($cPasswordFinal),
                    'lActivo'          => 1,
                    'lCambiarPassword' => 1, // Requiere cambio obligatorio al ingresar
                ]);

                $cMensaje = "Usuario creado exitosamente. Contraseña inicial asignada: {$cPasswordFinal}. Requerirá cambio de contraseña al ingresar.";
            } else {
                // Edición de Usuario existente
                $oUser = User::find($iID);
                if (!$oUser) {
                    return response()->json([
                        'lSuccess' => false,
                        'cMensaje' => 'No se encontró el usuario a actualizar.',
                        'cData'    => null,
                    ], 404);
                }

                $oUser->name = $cNombre;
                $oUser->cPrimerApellido = $cPrimerApellido;
                $oUser->cSegundoApellido = $cSegundoApellido;
                $oUser->cTelefono = $cTelefono;
                $oUser->email = $cEmail;

                // Actualizar contraseña solo si se proporciona una nueva
                if (!empty($cPassword)) {
                    $oUser->password = Hash::make($cPassword);
                }

                $oUser->save();
                $cMensaje = 'El usuario ha sido actualizado correctamente.';
            }

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => $cMensaje,
                'cData'    => [
                    'iID'              => $oUser->id,
                    'cNombreCompleto'  => $oUser->nombre_completo,
                    'cEmail'           => $oUser->email,
                ],
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al guardar el usuario: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    /**
     * Desactivación lógica (lActivo = 0) del usuario.
     */
    public function deleteData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID');
            $oUser = User::find($iID);

            if (!$oUser) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El usuario solicitado no existe.',
                    'cData'    => null,
                ], 404);
            }

            // Evitar que un usuario se desactive a sí mismo
            if (auth()->id() === $oUser->id) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'No puedes desactivar tu propia cuenta activa.',
                    'cData'    => null,
                ], 422);
            }

            $oUser->lActivo = 0;
            $oUser->save();

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'El usuario ha sido deshabilitado correctamente.',
                'cData'    => null,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al eliminar el usuario: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }
}
