<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Egresos</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c62828;
        }

        .logo {
            max-width: 40mm;
            max-height: 20mm;
            margin-bottom: 5px;
        }

        .titulo {
            font-size: 18px;
            font-weight: bold;
            color: #c62828;
            margin-bottom: 3px;
        }

        .subtitulo {
            font-size: 12px;
            color: #666;
        }

        .info-periodo {
            text-align: center;
            margin-bottom: 15px;
            padding: 8px;
            background: #ffebee;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background: #c62828;
            color: white;
            padding: 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }

        td {
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .totales {
            margin-top: 20px;
            padding: 15px;
            background: #ffebee;
            border-radius: 4px;
        }

        .totales-titulo {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #c62828;
        }

        .totales-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }

        .total-item {
            padding: 8px;
            background: white;
            border-radius: 4px;
            border-left: 3px solid #c62828;
        }

        .total-label {
            font-size: 8px;
            color: #666;
        }

        .total-valor {
            font-size: 14px;
            font-weight: bold;
            color: #c62828;
        }

        .categoria-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 8px;
            background: white;
            margin-bottom: 3px;
            border-radius: 3px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 10px;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        @if($configuracion->logo_url)
            <img src="{{ $configuracion->logo_url }}" alt="Logo" class="logo">
        @endif

        <div class="titulo">{{ $configuracion->nombre_edificio }}</div>
        <div class="subtitulo">{{ $titulo }}</div>
    </div>

    <!-- Información del Periodo -->
    <div class="info-periodo">
        <strong>Período:</strong> {{ $fecha_inicio }} - {{ $fecha_fin }}
        <strong style="margin-left: 20px;">Generado:</strong> {{ $fecha_generacion }}
    </div>

    <!-- Tabla de Egresos -->
    <table>
        <thead>
            <tr>
                <th>N° Egreso</th>
                <th>Fecha</th>
                <th>Categoría</th>
                <th>Concepto</th>
                <th>N° Comprobante</th>
                <th>Proveedor</th>
                <th>Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($egresos as $egreso)
                <tr>
                    <td>{{ $egreso->numero_egreso }}</td>
                    <td>{{ $egreso->fecha->format('d/m/Y') }}</td>
                    <td>{{ $egreso->categoria ? $egreso->categoria->nombre : '-' }}</td>
                    <td>{{ $egreso->concepto }}</td>
                    <td>{{ $egreso->numero_comprobante ?? '-' }}</td>
                    <td>{{ $egreso->proveedor ?? '-' }}</td>
                    <td style="text-align: right; font-weight: bold; color: #c62828;">
                        ${{ number_format($egreso->importe, 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="totales">
        <div class="totales-titulo">RESUMEN DE EGRESOS</div>
        <div class="totales-grid">
            <div class="total-item">
                <div class="total-label">Total Registros</div>
                <div class="total-valor">{{ $totales['cantidad'] }}</div>
            </div>
            <div class="total-item" style="background: #c62828; color: white;">
                <div class="total-label" style="color: white;">TOTAL GENERAL</div>
                <div class="total-valor">${{ number_format($totales['total_general'], 2, ',', '.') }}</div>
            </div>
        </div>

        @if(!empty($totales['por_categoria']))
            <div style="margin-top: 15px;">
                <div style="font-weight: bold; margin-bottom: 8px; color: #c62828;">POR CATEGORÍA:</div>
                @foreach($totales['por_categoria'] as $categoria => $total)
                    <div class="categoria-item">
                        <span>{{ $categoria }}</span>
                        <strong>${{ number_format($total, 2, ',', '.') }}</strong>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        {{ $configuracion->nombre_edificio }} - {{ $configuracion->direccion ?? '' }} -
        Tel: {{ $configuracion->telefono ?? '' }} - Generado: {{ $fecha_generacion }}
    </div>
</body>
</html>
