<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ingresos</title>
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
            border-bottom: 2px solid #2e7d32;
        }

        .logo {
            max-width: 40mm;
            max-height: 20mm;
            margin-bottom: 5px;
        }

        .titulo {
            font-size: 18px;
            font-weight: bold;
            color: #2e7d32;
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
            background: #e8f5e9;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background: #2e7d32;
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
            background: #e8f5e9;
            border-radius: 4px;
        }

        .totales-titulo {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2e7d32;
        }

        .totales-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .total-item {
            padding: 8px;
            background: white;
            border-radius: 4px;
            border-left: 3px solid #2e7d32;
        }

        .total-label {
            font-size: 8px;
            color: #666;
        }

        .total-valor {
            font-size: 14px;
            font-weight: bold;
            color: #2e7d32;
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

        .metodo-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .efectivo { background: #c8e6c9; color: #2e7d32; }
        .transferencia { background: #bbdefb; color: #1565c0; }
        .qr { background: #e1bee7; color: #7b1fa2; }
        .otro { background: #ffe0b2; color: #e65100; }
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

    <!-- Tabla de Ingresos -->
    <table>
        <thead>
            <tr>
                <th>N° Recibo</th>
                <th>Fecha</th>
                <th>Categoría</th>
                <th>Descripción</th>
                <th>Método</th>
                <th>Referencia</th>
                <th>Importe</th>
                <th>Propietario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ingresos as $ingreso)
                <tr>
                    <td>{{ $ingreso->numero_recibo }}</td>
                    <td>{{ $ingreso->fecha->format('d/m/Y') }}</td>
                    <td>{{ $ingreso->categoria ? $ingreso->categoria->nombre : '-' }}</td>
                    <td>{{ $ingreso->descripcion ?? '-' }}</td>
                    <td>
                        <span class="metodo-badge {{ $ingreso->metodo_pago }}">
                            {{ ucfirst($ingreso->metodo_pago) }}
                        </span>
                    </td>
                    <td>{{ $ingreso->referencia_bancaria ?? '-' }}</td>
                    <td style="text-align: right; font-weight: bold;">
                        ${{ number_format($ingreso->importe, 2, ',', '.') }}
                    </td>
                    <td>{{ $ingreso->propietario ? ($ingreso->propietario->nombre . ' ' . $ingreso->propietario->apellido) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="totales">
        <div class="totales-titulo">RESUMEN DE INGRESOS</div>
        <div class="totales-grid">
            <div class="total-item">
                <div class="total-label">Total Registros</div>
                <div class="total-valor">{{ $totales['cantidad'] }}</div>
            </div>
            <div class="total-item">
                <div class="total-label">Efectivo</div>
                <div class="total-valor">${{ number_format($totales['total_efectivo'], 2, ',', '.') }}</div>
            </div>
            <div class="total-item">
                <div class="total-label">Transferencias</div>
                <div class="total-valor">${{ number_format($totales['total_transferencia'], 2, ',', '.') }}</div>
            </div>
            <div class="total-item">
                <div class="total-label">QR</div>
                <div class="total-valor">${{ number_format($totales['total_qr'], 2, ',', '.') }}</div>
            </div>
            <div class="total-item">
                <div class="total-label">Otros</div>
                <div class="total-valor">${{ number_format($totales['total_otro'], 2, ',', '.') }}</div>
            </div>
            <div class="total-item" style="background: #2e7d32; color: white;">
                <div class="total-label" style="color: white;">TOTAL GENERAL</div>
                <div class="total-valor">${{ number_format($totales['total_general'], 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        {{ $configuracion->nombre_edificio }} - {{ $configuracion->direccion ?? '' }} -
        Tel: {{ $configuracion->telefono ?? '' }} - Generado: {{ $fecha_generacion }}
    </div>
</body>
</html>
