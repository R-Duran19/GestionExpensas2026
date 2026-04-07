<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo {{ $ingreso->numero_recibo }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            width: 80mm;
            margin: 0 auto;
        }

        .container {
            padding: 5mm;
            border: 1px solid #000;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }

        .logo {
            max-width: 30mm;
            max-height: 15mm;
            margin: 0 auto 2mm;
            display: block;
        }

        .edificio-nombre {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1mm;
        }

        .edificio-direccion {
            font-size: 9px;
            margin-bottom: 1mm;
        }

        .edificio-contacto {
            font-size: 9px;
        }

        .titulo-recibo {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 2mm 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .numero-recibo {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .fecha-recibo {
            text-align: center;
            font-size: 10px;
            margin-bottom: 3mm;
        }

        .section {
            margin-bottom: 3mm;
            border-bottom: 1px dashed #000;
            padding-bottom: 2mm;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1mm;
            background: #f0f0f0;
            padding: 1mm 2mm;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
            font-size: 10px;
        }

        .label {
            font-weight: bold;
        }

        .value {
            text-align: right;
        }

        .propetario-info {
            font-size: 11px;
        }

        .propetario-nombre {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 1mm;
        }

        .totals {
            margin-top: 2mm;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
            font-size: 11px;
        }

        .total-final {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 2mm 0;
            font-weight: bold;
            font-size: 14px;
        }

        .estado-expensa {
            background: #f9f9f9;
            padding: 2mm;
            font-size: 10px;
        }

        .estado-badge {
            display: inline-block;
            padding: 1mm 3mm;
            border-radius: 2mm;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }

        .estado-pagado {
            background: #d4edda;
            color: #155724;
        }

        .estado-parcial {
            background: #fff3cd;
            color: #856404;
        }

        .estado-pendiente {
            background: #f8d7da;
            color: #721c24;
        }

        .observaciones {
            font-size: 9px;
            font-style: italic;
            margin-top: 2mm;
            padding: 2mm;
            background: #f5f5f5;
        }

        .footer {
            text-align: center;
            margin-top: 3mm;
            padding-top: 2mm;
            border-top: 2px solid #000;
            font-size: 9px;
        }

        .gracias {
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .fecha-generacion {
            color: #666;
            font-size: 8px;
        }

        .method-badge {
            display: inline-block;
            padding: 1mm 2mm;
            background: #e9ecef;
            border-radius: 1mm;
            font-size: 9px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            @if($configuracion->logo_url)
                <img src="{{ $configuracion->logo_url }}" alt="Logo" class="logo">
            @endif

            <div class="edificio-nombre">{{ $configuracion->nombre_edificio }}</div>

            @if($configuracion->direccion)
                <div class="edificio-direccion">{{ $configuracion->direccion }}</div>
            @endif

            @if($configuracion->telefono || $configuracion->email)
                <div class="edificio-contacto">
                    @if($configuracion->telefono)
                        Tel: {{ $configuracion->telefono }}
                    @endif

                    @if($configuracion->telefono && $configuracion->email)
                        |
                    @endif

                    @if($configuracion->email)
                        {{ $configuracion->email }}
                    @endif
                </div>
            @endif
        </div>

        <!-- Título del Recibo -->
        <div class="titulo-recibo">RECIBO DE PAGO</div>
        <div class="numero-recibo">{{ $ingreso->numero_recibo }}</div>
        <div class="fecha-recibo">Fecha: {{ $ingreso->fecha->format('d/m/Y') }}</div>

        {{-- Información del Propietario (solo para pagos de expensas) --}}
        @if(isset($propietario) && $propietario)
        <div class="section">
            <div class="section-title">PROPIETARIO</div>
            <div class="propetario-info">
                <div class="propetario-nombre">{{ $propietario->nombre }} {{ $propietario->apellido }}</div>

                @if($propietario->ci)
                    <div class="row">
                        <span class="label">CI:</span>
                        <span class="value">{{ $propietario->ci }}</span>
                    </div>
                @endif

                @if($propietario->telefono)
                    <div class="row">
                        <span class="label">Teléfono:</span>
                        <span class="value">{{ $propietario->telefono }}</span>
                    </div>
                @endif

                @if($propietario->email)
                    <div class="row">
                        <span class="label">Email:</span>
                        <span class="value">{{ $propietario->email }}</span>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Detalle del Pago -->
        <div class="section">
            <div class="section-title">DETALLE DEL PAGO</div>

            <div class="row">
                <span class="label">Concepto:</span>
                <span class="value">{{ $ingreso->descripcion ?? 'Ingreso variado' }}</span>
            </div>

            @if(isset($periodo) && $periodo)
            <div class="row">
                <span class="label">Período:</span>
                <span class="value">{{ $periodo->nombre_periodo }}</span>
            </div>
            @endif

            @if($ingreso->categoria)
            <div class="row">
                <span class="label">Categoría:</span>
                <span class="value">{{ $ingreso->categoria->nombre }}</span>
            </div>
            @endif

            <div class="row">
                <span class="label">Método:</span>
                <span class="value">
                    <span class="method-badge">{{ $metodos_pago[$ingreso->metodo_pago] ?? ucfirst($ingreso->metodo_pago) }}</span>
                </span>
            </div>

            @if($ingreso->referencia_bancaria)
                <div class="row">
                    <span class="label">Referencia:</span>
                    <span class="value">{{ $ingreso->referencia_bancaria }}</span>
                </div>
            @endif

            <div class="totals">
                <div class="total-row total-final">
                    <span class="label">TOTAL PAGADO:</span>
                    <span class="value">${{ number_format($ingreso->importe, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Estado de Expensa (solo para pagos de expensas) --}}
        @if(isset($expensa_total) && $expensa_total)
        <div class="section">
            <div class="section-title">ESTADO DE EXPENSA</div>

            <div class="estado-expensa">
                <div class="row">
                    <span class="label">Período:</span>
                    <span class="value">{{ $periodo->nombre_periodo }}</span>
                </div>

                <div class="row">
                    <span class="label">Total Deuda:</span>
                    <span class="value">${{ number_format($expensa_total->monto_total, 2, ',', '.') }}</span>
                </div>

                <div class="row">
                    <span class="label">Pagado:</span>
                    <span class="value">${{ number_format($expensa_total->monto_pagado, 2, ',', '.') }}</span>
                </div>

                <div class="row">
                    <span class="label">Pendiente:</span>
                    <span class="value">${{ number_format($expensa_total->monto_pendiente, 2, ',', '.') }}</span>
                </div>

                @if($expensa_total->saldo_a_favor > 0)
                    <div class="row">
                        <span class="label">Saldo a Favor:</span>
                        <span class="value">${{ number_format($expensa_total->saldo_a_favor, 2, ',', '.') }}</span>
                    </div>
                @endif

                <div style="text-align: center; margin-top: 2mm;">
                    <span class="estado-badge estado-{{ $expensa_total->estado }}">
                        {{ $expensa_total->estado_label }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        <!-- Observaciones -->
        @if($ingreso->observaciones)
            <div class="section">
                <div class="section-title">OBSERVACIONES</div>
                <div class="observaciones">{{ $ingreso->observaciones }}</div>
            </div>
        @endif

        <!-- Información Adicional del Edificio -->
        @if($configuracion->informacion_adicional)
            <div class="section">
                <div class="section-title">INFORMACIÓN ADICIONAL</div>
                <div style="font-size: 9px;">{{ $configuracion->informacion_adicional }}</div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="gracias">¡Gracias por su pago!</div>
            @if($configuracion->cuit)
                <div style="font-size: 8px; margin-bottom: 1mm;">CUIT: {{ $configuracion->cuit }}</div>
            @endif
            <div class="fecha-generacion">Generado: {{ $fecha_generacion }}</div>
        </div>
    </div>
</body>
</html>
