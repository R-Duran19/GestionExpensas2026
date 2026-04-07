<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egreso {{ $egreso->numero_egreso }}</title>
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

        .titulo-egreso {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 2mm 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #dc2626;
        }

        .numero-egreso {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .fecha-egreso {
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
            background: #fef2f2;
            padding: 1mm 2mm;
            color: #dc2626;
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
            border-top: 2px solid #dc2626;
            border-bottom: 2px solid #dc2626;
            padding: 2mm 0;
            font-weight: bold;
            font-size: 14px;
            color: #dc2626;
        }

        .descripcion {
            font-size: 9px;
            margin-top: 2mm;
            padding: 2mm;
            background: #fef2f2;
            border-left: 2px solid #dc2626;
        }

        .observaciones {
            font-size: 9px;
            font-style: italic;
            margin-top: 2mm;
            padding: 2mm;
            background: #fef2f2;
        }

        .footer {
            text-align: center;
            margin-top: 3mm;
            padding-top: 2mm;
            border-top: 2px solid #000;
            font-size: 9px;
        }

        .fecha-generacion {
            color: #666;
            font-size: 8px;
        }

        .category-badge {
            display: inline-block;
            padding: 1mm 2mm;
            background: #fef2f2;
            border: 1px solid #dc2626;
            border-radius: 1mm;
            font-size: 9px;
            font-weight: bold;
            color: #dc2626;
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

        <!-- Título del Egreso -->
        <div class="titulo-egreso">COMPROBANTE DE EGRESO</div>
        <div class="numero-egreso">{{ $egreso->numero_egreso }}</div>
        <div class="fecha-egreso">Fecha: {{ $egreso->fecha->format('d/m/Y') }}</div>

        <!-- Detalle del Egreso -->
        <div class="section">
            <div class="section-title">DETALLE DEL PAGO</div>

            @if($egreso->categoria)
                <div class="row">
                    <span class="label">Categoría:</span>
                    <span class="value">
                        <span class="category-badge">{{ $egreso->categoria->nombre }}</span>
                    </span>
                </div>
            @endif

            @if($egreso->concepto)
                <div class="row">
                    <span class="label">Concepto:</span>
                    <span class="value">{{ $egreso->concepto }}</span>
                </div>
            @endif

            @if($egreso->numero_comprobante)
                <div class="row">
                    <span class="label">N° Comprobante:</span>
                    <span class="value">{{ $egreso->numero_comprobante }}</span>
                </div>
            @endif

            @if($egreso->proveedor)
                <div class="row">
                    <span class="label">Proveedor:</span>
                    <span class="value">{{ $egreso->proveedor }}</span>
                </div>
            @endif

            <div class="totals">
                <div class="total-row total-final">
                    <span class="label">TOTAL PAGADO:</span>
                    <span class="value">${{ number_format($egreso->importe, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Descripción -->
        @if($egreso->descripcion)
            <div class="section">
                <div class="section-title">DESCRIPCIÓN</div>
                <div class="descripcion">{{ $egreso->descripcion }}</div>
            </div>
        @endif

        <!-- Observaciones -->
        @if($egreso->observaciones)
            <div class="section">
                <div class="section-title">OBSERVACIONES</div>
                <div class="observaciones">{{ $egreso->observaciones }}</div>
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
            @if($configuracion->cuit)
                <div style="font-size: 8px; margin-bottom: 1mm;">CUIT: {{ $configuracion->cuit }}</div>
            @endif
            <div class="fecha-generacion">Generado: {{ $fecha_generacion }}</div>
        </div>
    </div>
</body>
</html>
