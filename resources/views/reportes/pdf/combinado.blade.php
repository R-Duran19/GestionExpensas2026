<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero Combinado</title>
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
            font-size: 9px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1565c0;
        }

        .logo {
            max-width: 40mm;
            max-height: 20mm;
            margin-bottom: 5px;
        }

        .titulo {
            font-size: 18px;
            font-weight: bold;
            color: #1565c0;
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
            background: #e3f2fd;
            border-radius: 4px;
        }

        .balance-box {
            margin-bottom: 15px;
            padding: 12px;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 6px;
            text-align: center;
        }

        .balance-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }

        .balance-valor {
            font-size: 20px;
            font-weight: bold;
            color: #1565c0;
        }

        .balance-valor.positivo { color: #2e7d32; }
        .balance-valor.negativo { color: #c62828; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            padding: 6px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
        }

        th.ingresos { background: #2e7d32; color: white; }
        th.egresos { background: #c62828; color: white; }

        td {
            padding: 4px;
            border-bottom: 1px solid #ddd;
            font-size: 8px;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            padding: 6px;
            border-radius: 4px;
        }

        .ingresos-title { background: #e8f5e9; color: #2e7d32; }
        .egresos-title { background: #ffebee; color: #c62828; }

        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .resumen-item {
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }

        .ingresos-box { background: #e8f5e9; border-left: 3px solid #2e7d32; }
        .egresos-box { background: #ffebee; border-left: 3px solid #c62828; }
        .balance-box { background: #e3f2fd; border-left: 3px solid #1565c0; }

        .resumen-label {
            font-size: 8px;
            color: #666;
            margin-bottom: 3px;
        }

        .resumen-valor {
            font-size: 14px;
            font-weight: bold;
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

    <!-- Resumen Financiero -->
    <div class="resumen-grid">
        <div class="resumen-item ingresos-box">
            <div class="resumen-label">TOTAL INGRESOS</div>
            <div class="resumen-valor" style="color: #2e7d32;">
                ${{ number_format($totales['total_ingresos'], 2, ',', '.') }}
            </div>
        </div>
        <div class="resumen-item egresos-box">
            <div class="resumen-label">TOTAL EGRESOS</div>
            <div class="resumen-valor" style="color: #c62828;">
                ${{ number_format($totales['total_egresos'], 2, ',', '.') }}
            </div>
        </div>
        <div class="resumen-item balance-box">
            <div class="resumen-label">BALANCE</div>
            <div class="resumen-valor {{ $totales['balance'] >= 0 ? 'positivo' : 'negativo' }}">
                ${{ number_format($totales['balance'], 2, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Tabla de Ingresos -->
    <div class="section-title ingresos-title">
        <i class="fas fa-arrow-down"></i> INGRESOS ({{ $ingresos->count() }} registros)
    </div>
    <table>
        <thead>
            <tr>
                <th class="ingresos">N° Recibo</th>
                <th class="ingresos">Fecha</th>
                <th class="ingresos">Categoría</th>
                <th class="ingresos">Descripción</th>
                <th class="ingresos">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ingresos as $ingreso)
                <tr>
                    <td>{{ $ingreso->numero_recibo }}</td>
                    <td>{{ $ingreso->fecha->format('d/m/Y') }}</td>
                    <td>{{ $ingreso->categoria ? $ingreso->categoria->nombre : '-' }}</td>
                    <td>{{ $ingreso->descripcion ?? '-' }}</td>
                    <td style="text-align: right; font-weight: bold;">
                        ${{ number_format($ingreso->importe, 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Tabla de Egresos -->
    <div class="section-title egresos-title">
        <i class="fas fa-arrow-up"></i> EGRESOS ({{ $egresos->count() }} registros)
    </div>
    <table>
        <thead>
            <tr>
                <th class="egresos">N° Egreso</th>
                <th class="egresos">Fecha</th>
                <th class="egresos">Categoría</th>
                <th class="egresos">Concepto</th>
                <th class="egresos">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($egresos as $egreso)
                <tr>
                    <td>{{ $egreso->numero_egreso }}</td>
                    <td>{{ $egreso->fecha->format('d/m/Y') }}</td>
                    <td>{{ $egreso->categoria ? $egreso->categoria->nombre : '-' }}</td>
                    <td>{{ $egreso->concepto }}</td>
                    <td style="text-align: right; font-weight: bold;">
                        ${{ number_format($egreso->importe, 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        {{ $configuracion->nombre_edificio }} - {{ $configuracion->direccion ?? '' }} -
        Tel: {{ $configuracion->telefono ?? '' }} - Generado: {{ $fecha_generacion }}
    </div>
</body>
</html>
