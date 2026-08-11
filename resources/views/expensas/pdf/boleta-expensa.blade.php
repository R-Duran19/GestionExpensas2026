<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta de Expensa - {{ $propietario->nombre }} {{ $propietario->apellido }}</title>
    <style>
        @page {
            margin: 0;
            size: Letter portrait;
        }

        @page:first {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #000;
            background: #fff;
            padding: 0;
            margin: 0;
            width: 216mm;
        }

        .page-wrapper {
            width: 216mm;
            padding: 10mm 15mm 0 15mm;
            margin: 0;
            box-sizing: border-box;
        }

        .container {
            width: 186mm;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .content-section {
            margin-top: 0;
            width: 186mm;
        }

        .content-section + .content-section {
            margin-top: 10mm;
        }

        .header {
            text-align: center;
            margin-bottom: 4mm;
            padding: 10mm 2mm 2mm 2mm;
            width: 186mm;
        }

        .edificio-nombre {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2mm;
        }

        .titulo-boleta {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin: 2mm 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .propietario-info {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3mm;
            padding: 1.5mm;
            background: #f9f9f9;
            border: 1px solid #ddd;
            width: 186mm;
        }

        table {
            width: 186mm;
            border-collapse: collapse;
            margin-bottom: 2mm;
            font-size: 8px;
            border: 2px solid #000;
            table-layout: fixed;
        }

        th, td {
            padding: 1.5mm;
            text-align: left;
            border: 1px solid #000;
            word-wrap: break-word;
        }

        tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        th {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .total-row {
            background: #e8e8e8;
            font-weight: bold;
        }

        .gran-total {
            font-size: 10px;
            font-weight: bold;
            background: #000;
            color: #fff;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .agua-row {
            background: #f0f8ff;
        }

        .agua-subrow {
            background: #e6f3ff;
            font-size: 8px;
        }

        .factor-box {
            display: inline-block;
            padding: 0.3mm 0.8mm;
            background: #fff;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 7px;
            margin: 0 0.5mm;
        }

        .multiplication {
            font-weight: bold;
            font-size: 9px;
            margin: 0 0.5mm;
        }

        .notas {
            margin-top: 3mm;
            padding: 2mm;
            background: #fff;
            border: 2px solid #000;
            font-size: 7px;
            line-height: 1.5;
            page-break-inside: avoid;
            width: 186mm;
        }

        .notas-title {
            font-weight: bold;
            margin-bottom: 1.5mm;
            text-transform: uppercase;
            font-size: 8px;
            border-bottom: 1px solid #000;
            padding-bottom: 1mm;
        }

        .aviso {
            margin-top: 2mm;
            padding: 2mm;
            background: #fff3cd;
            border: 2px solid #ffc107;
            font-size: 8px;
            text-align: center;
            font-weight: bold;
            page-break-inside: avoid;
            width: 186mm;
        }

        .footer {
            margin-top: 3mm;
            margin-bottom: 10mm;
            padding: 2mm;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #ddd;
            width: 186mm;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="edificio-nombre">{{ $configuracion->nombre_edificio }}</div>
            <div class="titulo-boleta">EXPENSAS DEL MES DE {{ strtoupper($periodo->nombre_periodo) }}</div>
            <div class="propietario-info">
                {{ $etiquetaResponsable }}: {{ strtoupper($propietario->nombre) }} {{ strtoupper($propietario->apellido) }}
            </div>
        </div>

        @if($expensas->isNotEmpty())
            <!-- Tabla Principal de Expensas -->
            <div class="content-section">
            <table>
                <thead>
                    <tr>
                        <th style="width: 64mm;">CONCEPTO</th>
                        <th style="width: 38mm;" class="text-center">Nº</th>
                        <th style="width: 23mm;" class="text-right">M2</th>
                        <th style="width: 25mm;" class="text-right">FACTOR</th>
                        <th style="width: 36mm;" class="text-right">TOTAL Bs.-</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expensas as $expensa)
                        @php
                            $tieneAgua = $expensa->unidad->tiene_medidor && $expensa->monto_agua > 0;
                            $lectura = null;
                            $factorAgua = 0;

                            if ($tieneAgua) {
                                $lectura = $expensa->unidad->lecturasMedidor->first() ?? null;
                                $factorAgua = $expensa->unidad->categoria_unidad === \App\Enums\CategoriaUnidad::DOMICILIARIA
                                    ? $periodo->factor_domiciliario
                                    : $periodo->factor_comercial;
                            }
                        @endphp

                        <!-- Fila principal de la unidad -->
                        <tr>
                            <td><strong>EXPENSA {{ strtoupper($expensa->unidad->tipo_unidad->getLabel()) }}.</strong></td>
                            <td class="text-center">{{ $expensa->unidad->codigo }}</td>
                            <td class="text-right">{{ number_format($expensa->unidad->metros_cuadrados, 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($expensa->unidad->factor_prorrateo, 1, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($expensa->monto_expensa, 0, ',', '.') }}</td>
                        </tr>

                        @if($tieneAgua && $lectura)
                            <!-- Fila de consumo de agua en una sola línea -->
                            <tr class="agua-row">
                                <td style="padding-left: 3mm;">├─ <strong>CONSUMO DE AGUA</strong></td>
                                <td colspan="3" class="text-right">
                                    Lec. Ant.: {{ number_format($lectura->lectura_anterior, 2, ',', '.') }} m³ |
                                    Lec. Act.: {{ number_format($lectura->lectura_actual, 2, ',', '.') }} m³ |
                                    <strong>{{ number_format($lectura->consumo_m3, 2, ',', '.') }} m³</strong>
                                    <span class="multiplication">×</span>
                                    <span class="factor-box">{{ number_format($factorAgua, 5, ',', '.') }}</span>
                                </td>
                                <td class="text-right">{{ number_format($expensa->monto_agua, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endforeach

                    <!-- Subtotal Expensas -->
                    <tr class="total-row">
                        <td colspan="4"><strong>SUBTOTAL EXPENSAS</strong></td>
                        <td class="text-right">{{ number_format($totales['subtotal_expensas'], 0, ',', '.') }}</td>
                    </tr>

                    <!-- Subtotal Agua -->
                    @if($totales['subtotal_agua'] > 0)
                        <tr class="total-row">
                            <td colspan="4"><strong>SUBTOTAL AGUA</strong></td>
                            <td class="text-right">{{ number_format($totales['subtotal_agua'], 0, ',', '.') }}</td>
                        </tr>
                    @endif

                    <!-- Total sin deuda -->
                    <tr class="total-row">
                        <td colspan="4"><strong>TOTAL EXPENSA</strong></td>
                        <td class="text-right">
                            <strong>{{ number_format($totales['subtotal_expensas'] + $totales['subtotal_agua'], 0, ',', '.') }}</strong>
                        </td>
                    </tr>

                    <!-- Deuda anterior -->
                    @if($totales['deuda_anterior'] > 0)
                        <tr>
                            <td colspan="4"><strong>DEUDAS</strong></td>
                            <td class="text-right">{{ number_format($totales['deuda_anterior'], 0, ',', '.') }}</td>
                        </tr>
                    @endif

                    <!-- Gran Total -->
                    <tr class="gran-total">
                        <td colspan="4">TOTAL A PAGAR BS</td>
                        <td class="text-right">{{ number_format($totales['total_a_pagar'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
            </div>

            <!-- Notas -->
            <div class="content-section">
            @php
                $mensajeNotas = trim($configuracion->informacion_adicional ?? '');
            @endphp
            <div class="notas">
                <div class="notas-title">Notas:</div>
                @if($mensajeNotas !== '')
                    <div>{!! nl2br(e($mensajeNotas)) !!}</div>
                @else
                    <div>
                        ESTIMADOS SEÑORES COPROPIETARIOS E INQUILINOS, LES RECORDAMOS QUE PUEDEN HACER SUS PAGOS EN EL BANCO
                        <strong>BANCO BISA N° DE CUENTA 727505-001-1</strong>
                        A NOMBRE DE <strong>LA ADMINISTRACIÓN</strong>
                        TRANSFERENCIA BANCARIA Y DEJAR O ENVIAR SU PAPELETA DE DEPÓSITO EN RECEPCIÓN O EN OFICINAS DE ADMINISTRACIÓN
                        PARA EL CANJE DE RECIBO CORRESPONDIENTE.
                        @if($configuracion->telefono)
                            CUALQUIER CONSULTA COMUNICARSE A LOS TELÉFONOS {{ $configuracion->telefono }}
                        @endif
                    </div>
                @endif
            </div>
            </div>
        @else
            <!-- Sin expensas -->
            <div style="text-align: center; padding: 5mm; background: #fff3cd; border: 2px solid #ffc107; margin: 5mm 0;">
                <div style="font-weight: bold; margin-bottom: 2mm; font-size: 10px;">EXPENSAS NO CALCULADAS</div>
                <div style="font-size: 9px;">Las expensas para este período aún no han sido generadas.</div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            Generado: {{ $fecha_generacion }}
            @if($configuracion->cuit)
                | CUIT: {{ $configuracion->cuit }}
            @endif
        </div>
        </div>
    </div>
</body>
</html>
