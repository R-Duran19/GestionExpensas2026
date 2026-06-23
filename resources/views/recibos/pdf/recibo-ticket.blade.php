<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $ingreso->numero_recibo }}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 216mm;
            margin: 0 auto;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-left {
            flex: 1;
        }

        .logo {
            max-width: 80px;
            max-height: 50px;
            margin-bottom: 10px;
        }

        .empresa-nombre {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .empresa-datos {
            font-size: 10px;
            color: #666;
        }

        .header-right {
            text-align: right;
        }

        .titulo-recibo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            letter-spacing: 2px;
        }

        .numero-recibo {
            font-size: 16px;
            font-weight: bold;
            color: #27ae60;
            margin-top: 5px;
        }

        .fecha-recibo {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }

        /* Secciones */
        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #2c3e50;
            background: #ecf0f1;
            padding: 8px 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-left: 4px solid #3498db;
            margin-bottom: 12px;
        }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 8px 20px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        /* Propietario */
        .propietario-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
        }

        .propietario-nombre {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .propietario-datos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 10px;
        }

        /* Tabla de detalles */
        .detalles-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .detalles-table thead {
            background: #34495e;
            color: #fff;
        }

        .detalles-table th {
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .detalles-table th.text-right {
            text-align: right;
            padding-right: 30px;
        }

        .detalles-table th.text-center {
            text-align: center;
        }

        .detalles-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 11px;
        }

        .detalles-table td.text-center {
            text-align: center;
        }

        .detalles-table td.text-right {
            text-align: right;
            padding-right: 30px;
        }

        .detalles-table tbody tr:last-child td {
            border-bottom: none;
        }

        .detalles-table tbody tr:hover {
            background: #f8f9fa;
        }

        .periodo-header {
            background: #e8f4f8;
            font-weight: bold;
            color: #2980b9;
        }

        .concepto-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .concepto-expensa {
            background: #3498db;
            color: #fff;
        }

        .concepto-agua {
            background: #1abc9c;
            color: #fff;
        }

        .concepto-ambos {
            background: #9b59b6;
            color: #fff;
        }

        /* Totales */
        .totales-box {
            background: #fdfefe;
            border: 2px solid #3498db;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 12px;
        }

        .total-row.final {
            border-top: 2px solid #2c3e50;
            padding-top: 12px;
            margin-top: 8px;
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }

        .total-amount {
            font-weight: bold;
            color: #27ae60;
        }

        .total-amount.large {
            font-size: 18px;
        }

        /* Saldos a favor */
        .saldos-box {
            background: #f0fff4;
            border: 2px solid #27ae60;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .saldos-title {
            font-size: 13px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .saldo-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 11px;
            border-bottom: 1px dashed #27ae60;
        }

        .saldo-item:last-child {
            border-bottom: none;
        }

        /* Método de pago */
        .metodo-badge {
            display: inline-block;
            padding: 5px 12px;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Observaciones */
        .observaciones-box {
            background: #fff9e6;
            border-left: 4px solid #f39c12;
            padding: 12px;
            font-size: 10px;
            font-style: italic;
            color: #666;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
        }

        .footer-gracias {
            font-size: 14px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 5px;
        }

        .footer-info {
            font-size: 9px;
            color: #999;
        }

        /* Tabla alternativa para ingresos ordinarios */
        .simple-table {
            width: 100%;
            margin-top: 10px;
        }

        .simple-table td {
            padding: 8px 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .simple-table tr:last-child td {
            border-bottom: none;
        }

        .simple-table .label {
            font-weight: bold;
            color: #555;
            width: 120px;
        }

        .empty-row {
            height: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if($configuracion->logo_url)
                    <img src="{{ $configuracion->logo_url }}" alt="Logo" class="logo">
                @endif

                <div class="empresa-nombre">{{ $configuracion->nombre_edificio }}</div>

                @if($configuracion->direccion)
                    <div class="empresa-datos">{{ $configuracion->direccion }}</div>
                @endif

                @if($configuracion->telefono || $configuracion->email)
                    <div class="empresa-datos">
                        @if($configuracion->telefono) Tel: {{ $configuracion->telefono }} @endif
                        @if($configuracion->telefono && $configuracion->email) | @endif
                        @if($configuracion->email) {{ $configuracion->email }} @endif
                    </div>
                @endif
            </div>

            <div class="header-right">
                <div class="titulo-recibo">RECIBO DE PAGO</div>
                <div class="numero-recibo">{{ $ingreso->numero_recibo }}</div>
                <div class="fecha-recibo">{{ $ingreso->fecha->format('d/m/Y') }}</div>
            </div>
        </div>

        {{-- Información del Propietario --}}
        @if(isset($propietario) && $propietario)
        <div class="section">
            <div class="section-title">PROPIETARIO</div>
            <div class="propietario-box">
                <div class="propietario-nombre">{{ $propietario->nombre_completo ?? ($propietario->nombre . ' ' . $propietario->apellido) }}</div>
                <div class="propietario-datos">
                    @if($propietario->ci)
                        <div>
                            <span class="info-label">CI:</span>
                            <span class="info-value">{{ $propietario->ci }}</span>
                        </div>
                    @endif
                    @if($propietario->telefono)
                        <div>
                            <span class="info-label">Teléfono:</span>
                            <span class="info-value">{{ $propietario->telefono }}</span>
                        </div>
                    @endif
                    @if($propietario->email)
                        <div style="grid-column: 1 / -1;">
                            <span class="info-label">Email:</span>
                            <span class="info-value">{{ $propietario->email }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Detalle del Pago --}}
        <div class="section">
            <div class="section-title">DETALLE DEL PAGO</div>

            @if(isset($tiene_detalles) && $tiene_detalles && isset($detalles_por_periodo))
                {{-- Tabla de detalles agrupados por período --}}
                @php
                    $totalDetalles = 0;
                @endphp

                <table class="detalles-table">
                    <thead>
                        <tr>
                            <th>Período</th>
                            <th>Unidad</th>
                            <th class="text-center">Concepto</th>
                            <th class="text-right">Expensa</th>
                            <th class="text-right">Agua</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detalles_por_periodo as $periodoId => $detalles)
                            @php
                                $periodo = $detalles->first()->expensa->periodo;
                                $subtotalPeriodo = $detalles->sum('monto_aplicado');
                                $totalDetalles += $subtotalPeriodo;
                                $rowCount = $detalles->count();
                            @endphp

                            @foreach($detalles as $index => $detalle)
                                <tr @if($index === 0) class="periodo-header" @endif>
                                    @if($index === 0)
                                        <td rowspan="{{ $rowCount }}" style="vertical-align: middle;">
                                            {{ $periodo->nombre_periodo }}
                                        </td>
                                    @endif
                                    <td>{{ $detalle->expensa->unidad->codigo }}</td>
                                    <td class="text-center">
                                        @if($detalle->concepto === 'expensa')
                                            <span class="concepto-badge concepto-expensa">Expensa</span>
                                        @elseif($detalle->concepto === 'agua')
                                            <span class="concepto-badge concepto-agua">Agua</span>
                                        @elseif($detalle->concepto === 'ambos')
                                            <span class="concepto-badge concepto-ambos">Ambos</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($detalle->monto_expensa_aplicado > 0)
                                            ${{ number_format($detalle->monto_expensa_aplicado, 2, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($detalle->monto_agua_aplicado > 0)
                                            ${{ number_format($detalle->monto_agua_aplicado, 2, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right" style="font-weight: bold;">
                                        ${{ number_format($detalle->monto_aplicado, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Fila de subtotal por período --}}
                            <tr style="background: #f8f9fa; font-weight: bold;">
                                <td colspan="5" class="text-right" style="padding-right: 30px;">
                                    Subtotal {{ $periodo->nombre_periodo }}:
                                </td>
                                <td class="text-right" style="padding-right: 30px;">
                                    ${{ number_format($subtotalPeriodo, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach

                        {{-- Fila del total general --}}
                        <tr style="background: #e8f4f8; font-size: 12px; border-top: 2px solid #2c3e50;">
                            <td colspan="5" class="text-right" style="padding-right: 30px;">
                                <strong>TOTAL GENERAL:</strong>
                            </td>
                            <td class="text-right" style="padding-right: 30px;">
                                <strong>${{ number_format($totalDetalles, 2, ',', '.') }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- Totales --}}
                <div class="totales-box">
                    <div class="total-row">
                        <span>Total Expensas Pagadas:</span>
                        <span>{{ $ingreso->detalles->count() }} expensa(s)</span>
                    </div>
                    <div class="total-row">
                        <span>Período(s):</span>
                        <span>{{ $detalles_por_periodo->count() }} período(s)</span>
                    </div>
                    @if($ingreso->referencia_bancaria)
                        <div class="total-row">
                            <span>Referencia Bancaria:</span>
                            <span>{{ $ingreso->referencia_bancaria }}</span>
                        </div>
                    @endif
                    <div class="total-row">
                        <span>Método de Pago:</span>
                        <span class="metodo-badge">{{ $metodos_pago[$ingreso->metodo_pago] ?? ucfirst($ingreso->metodo_pago) }}</span>
                    </div>
                    <div class="total-row final">
                        <span>TOTAL PAGADO:</span>
                        <span class="total-amount large">${{ number_format($ingreso->importe, 2, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Saldos a favor --}}
                @if(isset($saldos_favor) && $saldos_favor->isNotEmpty())
                    <div class="saldos-box">
                        <div class="saldos-title">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3.5a.5.5 0 0 1-.5-.5v-3A.5.5 0 0 1 8 4z"/>
                            </svg>
                            Saldos a Favor Generados
                        </div>
                        @foreach($saldos_favor as $saldo)
                            <div class="saldo-item">
                                <span>Unidad {{ $saldo->unidad->codigo }}:</span>
                                <span style="font-weight: bold; color: #27ae60;">
                                    ${{ number_format($saldo->monto_saldo, 2, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                        <div style="margin-top: 10px; font-size: 9px; color: #666; font-style: italic;">
                            Estos saldos pueden ser aplicados en futuros pagos de expensas.
                        </div>
                    </div>
                @endif
            @else
                {{-- Ingreso ordinario --}}
                <table class="simple-table">
                    @if($ingreso->descripcion)
                        <tr>
                            <td class="label">Concepto:</td>
                            <td>{{ $ingreso->descripcion }}</td>
                        </tr>
                    @endif
                    @if($ingreso->categoria)
                        <tr>
                            <td class="label">Categoría:</td>
                            <td>{{ $ingreso->categoria->nombre }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Método de Pago:</td>
                        <td><span class="metodo-badge">{{ $metodos_pago[$ingreso->metodo_pago] ?? ucfirst($ingreso->metodo_pago) }}</span></td>
                    </tr>
                    @if($ingreso->referencia_bancaria)
                        <tr>
                            <td class="label">Referencia:</td>
                            <td>{{ $ingreso->referencia_bancaria }}</td>
                        </tr>
                    @endif
                    @if($ingreso->unidad)
                        <tr>
                            <td class="label">Unidad:</td>
                            <td>{{ $ingreso->unidad->codigo }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="2"><div class="empty-row"></div></td>
                    </tr>
                    <tr style="font-size: 14px; font-weight: bold;">
                        <td class="label">TOTAL PAGADO:</td>
                        <td class="total-amount">${{ number_format($ingreso->importe, 2, ',', '.') }}</td>
                    </tr>
                </table>
            @endif
        </div>

        {{-- Observaciones --}}
        @if($ingreso->observaciones)
        <div class="section">
            <div class="section-title">OBSERVACIONES</div>
            <div class="observaciones-box">{{ $ingreso->observaciones }}</div>
        </div>
        @endif

        {{-- Información Adicional --}}
        @if($configuracion->informacion_adicional)
        <div class="section">
            <div class="section-title">INFORMACIÓN ADICIONAL</div>
            <div style="font-size: 10px; color: #666;">{{ $configuracion->informacion_adicional }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="footer-gracias">¡Gracias por su pago!</div>
            @if($configuracion->cuit)
                <div class="footer-info">CUIT: {{ $configuracion->cuit }}</div>
            @endif
            <div class="footer-info">Generado: {{ $fecha_generacion }}</div>
        </div>
    </div>
</body>
</html>
