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

    public function itinerarioPdf(ServicioTuristico $servicio)
    {
        if ($servicio->cTipoItinerario === 'PDF') {
            abort_unless($servicio->cItinerarioPdf, 404);
            return response(app(\App\Services\ItinerarioPdfService::class)->contenido($servicio->cItinerarioPdf))
                ->withHeaders(['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="itinerario.pdf"', 'X-Content-Type-Options' => 'nosniff']);
        }
        $dias = $servicio->itinerarioSnapshot();
        abort_if(empty($dias), 422, 'Este servicio no tiene un itinerario asignado con días registrados.');
        $servicio->loadMissing(['itinerario', 'destino']);
        $settings = \App\Models\LandingSetting::current();
        $marca = $settings->values()['brand'];
        $rutaLogo = $settings->logoFilePath();
        $logo = is_file($rutaLogo) ? 'data:'.mime_content_type($rutaLogo).';base64,'.base64_encode(file_get_contents($rutaLogo)) : null;
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('servicios.itinerario.pdf', compact('servicio', 'dias', 'marca', 'logo'))
            ->setPaper('a4')->setOption(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(455, 810, 'Página {PAGE_NUM} / {PAGE_COUNT}', null, 8, [0.45, 0.5, 0.6]);
        return $pdf->download('itinerario-'.(\Illuminate\Support\Str::slug($servicio->cCodigo) ?: $servicio->iID).'.pdf');
    }

    public function itinerarioArchivo(ServicioTuristico $servicio)
    {
        abort_unless($servicio->cTipoItinerario === 'PDF' && $servicio->cItinerarioPdf, 404);
        return response(app(\App\Services\ItinerarioPdfService::class)->contenido($servicio->cItinerarioPdf))
            ->withHeaders(['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="itinerario.pdf"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function index()
    {
        $aCategorias = CategoriaTuristica::where('lActivo', 1)->orderBy('cNombre')->get();
        $aDestinos = DestinoTuristico::where('lActivo', 1)->orderBy('cNombre')->get();
        $aProveedores = Proveedor::where('lActivo', 1)->orderBy('cNombre')->get();

        $aItinerarios = \App\Models\Itinerario::orderBy('nombre')->get(['id', 'nombre']);
        return view('servicios.paquete.index', compact('aCategorias', 'aDestinos', 'aProveedores', 'aItinerarios'));
    }

    public function gridData(Request $request)
    {
        $request->validate([
            'draw' => 'nullable|integer|min:0',
            'start' => 'nullable|integer|min:0',
            'search.value' => 'nullable|string|max:150',
        ]);
        try {
            $query = ServicioTuristico::with(['categoria', 'destino', 'proveedor'])->withCount('itinerarioDias');
            $totalRegistros = ServicioTuristico::count();

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
                    $query->where('lActivo', 1)->where('lUsaVigencia', true)->where('dFechaInicioVigencia', '>', $today);
                } else if ($cEstatus === 'VENCIDO') {
                    $query->where('lActivo', 1)->where('lUsaVigencia', true)->where('dFechaFinVigencia', '<', $today);
                } else if ($cEstatus === 'ACTIVO') {
                    $query->where('lActivo', 1)->where(function ($q) use ($today) {
                        $q->where('lUsaVigencia', false)->orWhere(fn ($vigente) => $vigente->where('lUsaVigencia', true)->where('dFechaInicioVigencia', '<=', $today)->where('dFechaFinVigencia', '>=', $today));
                    });
                }
            }

            if ($request->filled('lConPromocion')) {
                $query->where('lTienePromocion', 1)->where('lPromocionActiva', 1)
                    ->where('dFechaInicioPromocion', '<=', now())
                    ->where('dFechaFinPromocion', '>=', now());
            }

            if ($request->filled('search.value')) {
                $busqueda = '%'.$request->input('search.value').'%';
                $query->where(function ($q) use ($busqueda) {
                    $q->where('cCodigo', 'like', $busqueda)->orWhere('cNombre', 'like', $busqueda)
                        ->orWhereHas('categoria', fn ($rel) => $rel->where('cNombre', 'like', $busqueda))
                        ->orWhereHas('destino', fn ($rel) => $rel->where('cNombre', 'like', $busqueda)->orWhere('cPais', 'like', $busqueda)->orWhere('cCiudad', 'like', $busqueda))
                        ->orWhereHas('proveedor', fn ($rel) => $rel->where('cNombre', 'like', $busqueda));
                });
            }
            $totalFiltrados = (clone $query)->count();
            $cData = $query->orderBy('iID', 'desc')->offset((int) $request->input('start', 0))->limit(10)->get()->map(function ($oServ) use ($request) {
                return [
                    'iID'                    => $oServ->iID,
                    'cCodigo'                => $oServ->cCodigo,
                    'cNombre'                => $oServ->cNombre,
                    'iDiasItinerario'         => $request->user()->hasPermission('itinerarios.ver') ? $oServ->itinerario_dias_count : null,
                    'iIDItinerario'           => $request->user()->hasPermission('itinerarios.ver') ? $oServ->iIDItinerario : null,
                    'lTieneItinerario'        => $request->user()->hasPermission('itinerarios.ver') && $oServ->tiene_itinerario,
                    'cTipoItinerario'         => $oServ->cTipoItinerario,
                    'cCategoria'             => $oServ->categoria ? $oServ->categoria->cNombre : 'N/A',
                    'cDestino'               => $oServ->destino ? $oServ->destino->ubicacion_completa : 'N/A',
                    'cProveedor'             => $oServ->proveedor ? $oServ->proveedor->cNombre : 'Sin Proveedor',
                    'cImagenPrincipal'       => $oServ->cImagenPrincipal ? asset(str_replace('public/', 'storage/', $oServ->cImagenPrincipal)) : null,
                    'dPrecioCompra'          => number_format($oServ->dPrecioCompra, 2),
                    'dPrecioVenta'           => number_format($oServ->dPrecioVenta, 2),
                    'dPrecioFinal'           => number_format($oServ->d_precio_final, 2),
                    'lPrecioMenorActivo'     => $oServ->lPrecioMenorActivo,
                    'dPrecioMenor'           => $oServ->lPrecioMenorActivo ? $oServ->dPrecioMenor : null,
                    'cMoneda'                => $oServ->cMoneda,
                    'dGananciaEfectiva'      => number_format($oServ->d_ganancia_efectiva, 2),
                    'dPorcentajeUtilidad'    => $oServ->d_porcentaje_utilidad,
                    'lTienePromocion'        => $oServ->lTienePromocion,
                    'lPromocionVigente'      => $oServ->l_promocion_vigente,
                    'dFechaInicioVigencia'   => $oServ->dFechaInicioVigencia ? $oServ->dFechaInicioVigencia->format('d/m/Y') : '',
                    'dFechaFinVigencia'      => $oServ->dFechaFinVigencia ? $oServ->dFechaFinVigencia->format('d/m/Y') : '',
                    'lActivo'                => $oServ->lActivo,
                    'lUsaVigencia'           => $oServ->lUsaVigencia,
                    'dFechaInicioViaje' => $oServ->dFechaInicioViaje?->format('d/m/Y') ?? '',
                    'dFechaFinViaje' => $oServ->dFechaFinViaje?->format('d/m/Y') ?? '',
                    'cEstatusDisponibilidad' => $oServ->c_estatus_disponibilidad,
                ];
            });

            return response()->json([
                'lSuccess' => true,
                'cMensaje' => 'Servicios cargados correctamente.',
                'cData'    => $cData,
                'data' => $cData,
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => $totalRegistros,
                'recordsFiltered' => $totalFiltrados,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'lSuccess' => false,
                'cMensaje' => 'Error al obtener la lista de servicios: ' . $ex->getMessage(),
                'cData'    => [],
                'data' => [],
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
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
                'iIDItinerario'         => $oServ->iIDItinerario,
                'cTipoItinerario'       => $oServ->cTipoItinerario,
                'lTienePdfItinerario'   => !empty($oServ->cItinerarioPdf),
                'urlItinerarioPdf'      => $oServ->cTipoItinerario === 'PDF' && $oServ->cItinerarioPdf ? route('servicios.paquetes.itinerarioArchivo', $oServ) : null,
                'lPublicarItinerario'   => $oServ->lPublicarItinerario,
                'cImagenPrincipal'      => $oServ->cImagenPrincipal ? asset(str_replace('public/', 'storage/', $oServ->cImagenPrincipal)) : null,
                'dPrecioCompra'         => $oServ->dPrecioCompra,
                'dPrecioVenta'          => $oServ->dPrecioVenta,
                'lPrecioMenorActivo'    => $oServ->lPrecioMenorActivo,
                'dPrecioMenor'          => $oServ->dPrecioMenor,
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
                'lUsaVigencia'          => $oServ->lUsaVigencia,
                'dFechaInicioViaje' => $oServ->dFechaInicioViaje?->format('Y-m-d') ?? '',
                'dFechaFinViaje' => $oServ->dFechaFinViaje?->format('Y-m-d') ?? '',
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
            $actual = $iID > 0 ? ServicioTuristico::find($iID) : null;
            $request->merge(['cTipoItinerario' => $request->input('cTipoItinerario', $actual?->cTipoItinerario ?? 'CATALOGO')]);
            $request->merge(['lUsaVigencia' => $request->boolean('lUsaVigencia', true)]);

            $request->validate([
                'cNombre'              => 'required|string|max:255',
                'iIDCategoria'         => 'nullable|exists:categoria_turisticas,iID',
                'iIDDestino'           => 'nullable|exists:destino_turisticos,iID',
                'iIDProveedor'         => 'nullable|exists:proveedores,iID',
                'iIDItinerario'        => 'nullable|integer|exists:itinerarios,id',
                'cTipoItinerario'      => 'required|in:CATALOGO,PDF',
                'itinerarioPdfFile'    => 'nullable|file|mimes:pdf|max:10240',
                'lPublicarItinerario'  => 'sometimes|boolean',
                'dPrecioCompra'        => 'required|numeric|min:0',
                'dPrecioVenta'         => 'required|numeric|min:0',
                'lPrecioMenorActivo'   => 'sometimes|boolean',
                'dPrecioMenor'         => 'nullable|numeric|min:0|max:9999999999.99',
                'lUsaVigencia' => 'boolean',
                'lActivo' => 'boolean',
                'dFechaInicioViaje' => 'nullable|required_with:dFechaFinViaje|date',
                'dFechaFinViaje' => 'nullable|required_with:dFechaInicioViaje|date|after_or_equal:dFechaInicioViaje',
                'dFechaInicioVigencia' => 'exclude_if:lUsaVigencia,false|required|date',
                'dFechaFinVigencia'    => 'exclude_if:lUsaVigencia,false|required|date|after_or_equal:dFechaInicioVigencia',
                'cImagenFile'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'cGaleriaFiles' => 'nullable|array',
                'cGaleriaFiles.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
                'imagenesEliminar' => 'nullable|array',
                'imagenesEliminar.*' => ['integer', 'distinct', \Illuminate\Validation\Rule::exists('servicio_imagenes', 'iID')->where('iIDServicio', $iID)],
            ], [
                'cNombre.required'                  => 'El nombre del servicio es obligatorio.',
                'iIDCategoria.required'             => 'Debe seleccionar una categoría turística.',
                'iIDDestino.required'               => 'Debe seleccionar un destino turístico.',
                'dPrecioCompra.required'            => 'El precio de compra es obligatorio.',
                'dPrecioVenta.required'             => 'El precio de venta es obligatorio.',
                'dFechaInicioVigencia.required'     => 'La fecha de inicio de vigencia es obligatoria.',
                'dFechaFinVigencia.required'        => 'La fecha de finalización de vigencia es obligatoria.',
                'dFechaFinVigencia.after_or_equal'  => 'La fecha final de vigencia no puede ser anterior a la fecha inicial.',
                'dFechaInicioViaje.required_with' => 'Ingresa la fecha de salida del viaje.',
                'dFechaFinViaje.required_with' => 'Ingresa la fecha de regreso del viaje.',
                'dFechaFinViaje.after_or_equal' => 'El regreso no puede ser anterior a la salida del viaje.',
                'cImagenFile.max'                   => 'La imagen principal no debe exceder los 5MB.',
            ]);

            $dPrecioCompra = (float) $request->input('dPrecioCompra', 0);
            $esPdf = $request->cTipoItinerario === 'PDF';
            if ($esPdf && !$request->hasFile('itinerarioPdfFile') && !$actual?->cItinerarioPdf) {
                throw \Illuminate\Validation\ValidationException::withMessages(['itinerarioPdfFile' => 'Selecciona el PDF del itinerario.']);
            }
            if ($request->boolean('lPublicarItinerario') && !$esPdf && !$request->filled('iIDItinerario')) {
                throw \Illuminate\Validation\ValidationException::withMessages(['iIDItinerario' => 'Selecciona un itinerario antes de publicarlo.']);
            }
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
                'iIDCategoria'          => $request->filled('iIDCategoria') ? (int) $request->input('iIDCategoria') : null,
                'iIDDestino'            => $request->filled('iIDDestino') ? (int) $request->input('iIDDestino') : null,
                'iIDProveedor'          => $request->input('iIDProveedor') ? (int) $request->input('iIDProveedor') : null,
                'iIDItinerario'         => !$esPdf && $request->filled('iIDItinerario') ? (int) $request->input('iIDItinerario') : null,
                'cTipoItinerario'       => $request->cTipoItinerario,
                'cItinerarioPdf'        => $actual?->cItinerarioPdf,
                'lPublicarItinerario'   => $request->boolean('lPublicarItinerario'),
                'dPrecioCompra'         => $dPrecioCompra,
                'dPrecioVenta'          => $dPrecioVenta,
                'lPrecioMenorActivo'    => $request->boolean('lPrecioMenorActivo'),
                'dPrecioMenor'          => round((float) $request->input('dPrecioMenor', 0), 2),
                'cMoneda'               => $request->input('cMoneda', 'MXN'),
                'lTienePromocion'       => $lTienePromocion ? 1 : 0,
                'cNombrePromocion'      => $request->input('cNombrePromocion'),
                'cDescripcionPromocion' => $request->input('cDescripcionPromocion'),
                'cTipoDescuento'        => $request->input('cTipoDescuento'),
                'dValorDescuento'       => $dValorDescuento,
                'dFechaInicioPromocion' => $request->input('dFechaInicioPromocion') ?: null,
                'dFechaFinPromocion'    => $request->input('dFechaFinPromocion') ?: null,
                'lPromocionActiva'      => $request->boolean('lPromocionActiva') ? 1 : 0,
                'lUsaVigencia'          => $request->boolean('lUsaVigencia'),
                'dFechaInicioViaje'     => $request->input('dFechaInicioViaje') ?: null,
                'dFechaFinViaje'        => $request->input('dFechaFinViaje') ?: null,
                'dFechaInicioVigencia'  => $request->boolean('lUsaVigencia') ? $request->input('dFechaInicioVigencia') : null,
                'dFechaFinVigencia'     => $request->boolean('lUsaVigencia') ? $request->input('dFechaFinVigencia') : null,
                'lActivo'               => $request->boolean('lActivo', true) ? 1 : 0,
            ];

            if ($esPdf && $request->hasFile('itinerarioPdfFile')) {
                $aData['cItinerarioPdf'] = app(\App\Services\ItinerarioPdfService::class)->guardar($request->file('itinerarioPdfFile'));
            }

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

            // Solo elimina imágenes que pertenecen al servicio editado.
            foreach ($oServ->imagenes()->whereIn('iID', $request->input('imagenesEliminar', []))->get() as $imagen) {
                $imagen->delete();
                $this->service->eliminarImagen($imagen->cRutaImagen);
            }

            // Las imágenes existentes no se vuelven a enviar como archivos.
            if ($request->hasFile('cGaleriaFiles')) {
                $orden = (int) $oServ->imagenes()->max('iOrden') + 1;
                foreach ($request->file('cGaleriaFiles') as $key => $fileImg) {
                    $cRutaGaleria = $this->service->guardarImagen($fileImg, 'servicios/galeria');
                    ServicioImagen::create([
                        'iIDServicio' => $oServ->iID,
                        'cRutaImagen' => $cRutaGaleria,
                        'iOrden'      => $orden + $key,
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
