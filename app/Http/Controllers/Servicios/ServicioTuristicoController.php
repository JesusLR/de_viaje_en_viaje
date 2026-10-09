<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Models\ServicioTuristico;
use App\Models\ServicioImagen;
use App\Models\CategoriaTuristica;
use App\Models\DestinoTuristico;
use App\Models\Proveedor;
use App\Services\ServicioTuristicoService;
use Illuminate\Http\Request;
use Exception;

class ServicioTuristicoController extends Controller
{
    protected ServicioTuristicoService $service;

    public function __construct(ServicioTuristicoService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $aCategorias = CategoriaTuristica::where('lActivo', 1)->orderBy('cNombre')->get();
        $aDestinos = DestinoTuristico::where('lActivo', 1)->orderBy('cNombre')->get();
        $aProveedores = Proveedor::where('lActivo', 1)->orderBy('cNombre')->get();

        return view('servicios.paquete.index', compact('aCategorias', 'aDestinos', 'aProveedores'));
    }

    public function gridData(Request $request)
    {
        try {
            $query = ServicioTuristico::with(['categoria', 'destino', 'proveedor']);

            // Filtros dinámicos por solicitud AJAX
            if ($request->filled('iIDCategoria')) {
                $query->where('iIDCategoria', $request->input('iIDCategoria'));
            }

            if ($request->filled('iIDDestino')) {
                $query->where('iIDDestino', $request->input('iIDDestino'));
            }

            if ($request->filled('iIDProveedor')) {
                $query->where('iIDProveedor', $request->input('iIDProveedor'));
            }

            if ($request->filled('cEstatusFiltro')) {
                $cEstatus = $request->input('cEstatusFiltro');
                $today = date('Y-m-d');

                if ($cEstatus === 'INACTIVO') {
                    $query->where('lActivo', 0);
                } else if ($cEstatus === 'PROGRAMADO') {
                    $query->where('lActivo', 1)->where('dFechaInicioVigencia', '>', $today);
                } else if ($cEstatus === 'VENCIDO') {
                    $query->where('lActivo', 1)->where('dFechaFinVigencia', '<', $today);
                } else if ($cEstatus === 'ACTIVO') {
                    $query->where('lActivo', 1)
                          ->where('dFechaInicioVigencia', '<=', $today)
                          ->where('dFechaFinVigencia', '>=', $today);
                }
            }

            if ($request->filled('lConPromocion')) {
                $query->where('lTienePromocion', 1);
            }

            $cData = $query->orderBy('iID', 'desc')->get()->map(function ($oServ) {
                return [
                    'iID'                    => $oServ->iID,
                    'cCodigo'                => $oServ->cCodigo,
                    'cNombre'                => $oServ->cNombre,
                    'cCategoria'             => $oServ->categoria ? $oServ->categoria->cNombre : 'N/A',
                    'cDestino'               => $oServ->destino ? $oServ->destino->ubicacion_completa : 'N/A',
                    'cProveedor'             => $oServ->proveedor ? $oServ->proveedor->cNombre : 'Sin Proveedor',
                    'cImagenPrincipal'       => $oServ->cImagenPrincipal ? asset(str_replace('public/', 'storage/', $oServ->cImagenPrincipal)) : null,
                    'dPrecioCompra'          => number_format($oServ->dPrecioCompra, 2),
                    'dPrecioVenta'           => number_format($oServ->dPrecioVenta, 2),
                    'dPrecioFinal'           => number_format($oServ->d_precio_final, 2),
                    'dGananciaEfectiva'      => number_format($oServ->d_ganancia_efectiva, 2),
                    'dPorcentajeUtilidad'    => $oServ->d_porcentaje_utilidad,
                    'lTienePromocion'        => $oServ->lTienePromocion,
                    'lPromocionVigente'      => $oServ->l_promocion_vigente,
                    'dFechaInicioVigencia'   => $oServ->dFechaInicioVigencia ? $oServ->dFechaInicioVigencia->format('d/m/Y') : '',
                    'dFechaFinVigencia'      => $oServ->dFechaFinVigencia ? $oServ->dFechaFinVigencia->format('d/m/Y') : '',
                    'lActivo'                => $oServ->lActivo,
                    'cEstatusDisponibilidad' => $oServ->c_estatus_disponibilidad,
                ];
            });

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Servicios cargados correctamente.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener la lista de servicios: ' . $ex->getMessage(),
                'cData'    => [],
            ], 500);
        }
    }

    public function getData($id)
    {
        try {
            $iID = (int) $id;
            $oServ = ServicioTuristico::with(['imagenes'])->find($iID);

            if (!$oServ) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El servicio turístico no existe.',
                    'cData'    => null,
                ], 404);
            }

            $cData = [
                'iID'                   => $oServ->iID,
                'cCodigo'               => $oServ->cCodigo,
                'cNombre'               => $oServ->cNombre,
                'cDescripcionCorta'     => $oServ->cDescripcionCorta,
                'cDescripcionCompleta'  => $oServ->cDescripcionCompleta,
                'iIDCategoria'          => $oServ->iIDCategoria,
                'iIDDestino'            => $oServ->iIDDestino,
                'iIDProveedor'          => $oServ->iIDProveedor,
                'cImagenPrincipal'      => $oServ->cImagenPrincipal ? asset(str_replace('public/', 'storage/', $oServ->cImagenPrincipal)) : null,
                'dPrecioCompra'         => $oServ->dPrecioCompra,
                'dPrecioVenta'          => $oServ->dPrecioVenta,
                'cMoneda'               => $oServ->cMoneda,
                'lTienePromocion'       => $oServ->lTienePromocion,
                'cNombrePromocion'      => $oServ->cNombrePromocion,
                'cDescripcionPromocion' => $oServ->cDescripcionPromocion,
                'cTipoDescuento'        => $oServ->cTipoDescuento,
                'dValorDescuento'       => $oServ->dValorDescuento,
                'dFechaInicioPromocion' => $oServ->dFechaInicioPromocion ? $oServ->dFechaInicioPromocion->format('Y-m-d') : '',
                'dFechaFinPromocion'    => $oServ->dFechaFinPromocion ? $oServ->dFechaFinPromocion->format('Y-m-d') : '',
                'lPromocionActiva'      => $oServ->lPromocionActiva,
                'dFechaInicioVigencia'  => $oServ->dFechaInicioVigencia ? $oServ->dFechaInicioVigencia->format('Y-m-d') : '',
                'dFechaFinVigencia'     => $oServ->dFechaFinVigencia ? $oServ->dFechaFinVigencia->format('Y-m-d') : '',
                'lActivo'               => $oServ->lActivo,
                'aGaleria'              => $oServ->imagenes->map(function ($img) {
                    return [
                        'iID'   => $img->iID,
                        'cRuta' => asset(str_replace('public/', 'storage/', $img->cRutaImagen)),
                    ];
                }),
            ];

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Servicio encontrado.',
                'cData'    => $cData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener datos del servicio: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    public function saveData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID', 0);

            $request->validate([
                'cNombre'              => 'required|string|max:255',
                'iIDCategoria'         => 'required|exists:categoria_turisticas,iID',
                'iIDDestino'           => 'required|exists:destino_turisticos,iID',
                'iIDProveedor'         => 'nullable|exists:proveedores,iID',
                'dPrecioCompra'        => 'required|numeric|min:0',
                'dPrecioVenta'         => 'required|numeric|min:0',
                'dFechaInicioVigencia' => 'required|date',
                'dFechaFinVigencia'    => 'required|date|after_or_equal:dFechaInicioVigencia',
                'cImagenFile'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            ], [
                'cNombre.required'                  => 'El nombre del servicio es obligatorio.',
                'iIDCategoria.required'             => 'Debe seleccionar una categoría turística.',
                'iIDDestino.required'               => 'Debe seleccionar un destino turístico.',
                'dPrecioCompra.required'            => 'El precio de compra es obligatorio.',
                'dPrecioVenta.required'             => 'El precio de venta es obligatorio.',
                'dFechaInicioVigencia.required'     => 'La fecha de inicio de vigencia es obligatoria.',
                'dFechaFinVigencia.required'        => 'La fecha de finalización de vigencia es obligatoria.',
                'dFechaFinVigencia.after_or_equal'  => 'La fecha final de vigencia no puede ser anterior a la fecha inicial.',
                'cImagenFile.max'                   => 'La imagen principal no debe exceder los 5MB.',
            ]);

            $dPrecioCompra = (float) $request->input('dPrecioCompra', 0);
            $dPrecioVenta = (float) $request->input('dPrecioVenta', 0);
            $lTienePromocion = $request->boolean('lTienePromocion');
            $dValorDescuento = (float) $request->input('dValorDescuento', 0);

            // Validar que el descuento no sea mayor al precio de venta
            if ($lTienePromocion) {
                $cTipo = $request->input('cTipoDescuento');
                if ($cTipo === 'IMPORTE' && $dValorDescuento > $dPrecioVenta) {
                    return response()->json([
                        'lSuccess' => false,
                        'cMensaje' => 'El importe del descuento no puede ser mayor al precio de venta.',
                        'cData'    => null,
                    ], 422);
                }
                if ($cTipo === 'PORCENTAJE' && ($dValorDescuento < 0 || $dValorDescuento > 100)) {
                    return response()->json([
                        'lSuccess' => false,
                        'cMensaje' => 'El porcentaje de descuento debe estar entre 0 y 100%.',
                        'cData'    => null,
                    ], 422);
                }
            }

            $aData = [
                'cNombre'               => trim($request->input('cNombre')),
                'cDescripcionCorta'     => trim($request->input('cDescripcionCorta')),
                'cDescripcionCompleta'  => trim($request->input('cDescripcionCompleta')),
                'iIDCategoria'          => (int) $request->input('iIDCategoria'),
                'iIDDestino'            => (int) $request->input('iIDDestino'),
                'iIDProveedor'          => $request->input('iIDProveedor') ? (int) $request->input('iIDProveedor') : null,
                'dPrecioCompra'         => $dPrecioCompra,
                'dPrecioVenta'          => $dPrecioVenta,
                'cMoneda'               => $request->input('cMoneda', 'MXN'),
                'lTienePromocion'       => $lTienePromocion ? 1 : 0,
                'cNombrePromocion'      => $request->input('cNombrePromocion'),
                'cDescripcionPromocion' => $request->input('cDescripcionPromocion'),
                'cTipoDescuento'        => $request->input('cTipoDescuento'),
                'dValorDescuento'       => $dValorDescuento,
                'dFechaInicioPromocion' => $request->input('dFechaInicioPromocion') ?: null,
                'dFechaFinPromocion'    => $request->input('dFechaFinPromocion') ?: null,
                'lPromocionActiva'      => $request->boolean('lPromocionActiva') ? 1 : 0,
                'dFechaInicioVigencia'  => $request->input('dFechaInicioVigencia'),
                'dFechaFinVigencia'     => $request->input('dFechaFinVigencia'),
                'lActivo'               => $request->boolean('lActivo', true) ? 1 : 0,
            ];

            if ($iID === 0) {
                $aData['cCodigo'] = $this->service->generarCodigoUnico();
                $aData['iIDUsuario'] = auth()->id();

                if ($request->hasFile('cImagenFile')) {
                    $aData['cImagenPrincipal'] = $this->service->guardarImagen($request->file('cImagenFile'));
                }

                $oServ = ServicioTuristico::create($aData);
                $cMensaje = "Servicio turístico {$oServ->cCodigo} registrado correctamente.";
            } else {
                $oServ = ServicioTuristico::find($iID);
                if (!$oServ) {
                    return response()->json([
                        'lSuccess' => false,
                        'cMensaje' => 'No se encontró el servicio a actualizar.',
                        'cData'    => null,
                    ], 404);
                }

                if ($request->hasFile('cImagenFile')) {
                    $this->service->eliminarImagen($oServ->cImagenPrincipal);
                    $aData['cImagenPrincipal'] = $this->service->guardarImagen($request->file('cImagenFile'));
                }

                $oServ->update($aData);
                $cMensaje = "Servicio turístico {$oServ->cCodigo} actualizado correctamente.";
            }

            // Carga de Galería Adicional de Imágenes si existen
            if ($request->hasFile('cGaleriaFiles')) {
                foreach ($request->file('cGaleriaFiles') as $key => $fileImg) {
                    $cRutaGaleria = $this->service->guardarImagen($fileImg, 'servicios/galeria');
                    ServicioImagen::create([
                        'iIDServicio' => $oServ->iID,
                        'cRutaImagen' => $cRutaGaleria,
                        'iOrden'      => $key,
                    ]);
                }
            }

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => $cMensaje,
                'cData'    => $oServ,
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
                'cMensaje' => 'Error al guardar el servicio: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    public function deleteData(Request $request)
    {
        try {
            $iID = (int) $request->input('iID');
            $oServ = ServicioTuristico::find($iID);

            if (!$oServ) {
                return response()->json([
                    'lSuccess' => false,
                    'cMensaje' => 'El servicio turístico no existe.',
                    'cData'    => null,
                ], 404);
            }

            // Desactivación Lógica
            $oServ->lActivo = 0;
            $oServ->save();

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => "El servicio {$oServ->cCodigo} ha sido desactivado del catálogo activo.",
                'cData'    => null,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al cambiar estatus del servicio: ' . $ex->getMessage(),
                'cData'    => null,
            ], 500);
        }
    }

    /**
     * Endpoint API para simulación en tiempo real de precios, utilidades y descuentos.
     */
    public function simularPrecios(Request $request)
    {
        try {
            $dCompra = (float) $request->input('dPrecioCompra', 0);
            $dVenta = (float) $request->input('dPrecioVenta', 0);
            $lPromo = $request->boolean('lTienePromocion');
            $cTipo = $request->input('cTipoDescuento');
            $dValor = (float) $request->input('dValorDescuento', 0);
            $dIni = $request->input('dFechaInicioPromocion');
            $dFin = $request->input('dFechaFinPromocion');

            $cSimulacion = $this->service->calcularSimulacionPrecios(
                $dCompra, $dVenta, $lPromo, $cTipo, $dValor, $dIni, $dFin
            );

            return response()->json([
                'lSuccess' => true,
                'cData'    => $cSimulacion,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => $ex->getMessage(),
            ], 500);
        }
    }
}
