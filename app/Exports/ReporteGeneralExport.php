<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReporteGeneralExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, WithTitle
{
    protected $data;
    protected $tipoVista;
    protected $totales;

    /**
     * Constructor
     *
     * @param array $data - Datos del reporte
     * @param string $tipoVista - 'resumido' o 'detallado'
     * @param array $totales - Totales para fila final
     */
    public function __construct(array $data, string $tipoVista, array $totales = [])
    {
        $this->data = collect($data);
        $this->tipoVista = $tipoVista;
        $this->totales = $totales;
    }

    /**
     * Obtener colección de datos
     */
    public function collection()
    {
        $rows = $this->data->map(function ($row) {
            if ($this->tipoVista === 'resumido') {
                return [
                    $row['tipo_unidad'],
                    $row['periodo'],
                    $row['unidades'],
                    $row['generado'],
                    $row['cobrado'],
                    $row['deuda'],
                    $row['porcentaje_cobranza'] . '%',
                ];
            } else {
                // Vista detallada
                return [
                    $row['unidad'],
                    $row['tipo'],
                    $row['propietario'],
                    $row['piso'],
                    $row['periodo'],
                    $row['generado'],
                    $row['cobrado'],
                    $row['deuda'],
                    $row['estado'],
                    $row['porcentaje_cobranza'] . '%',
                ];
            }
        });

        // Agregar fila de totales al final
        if (!empty($this->totales)) {
            if ($this->tipoVista === 'resumido') {
                $totalRow = [
                    'TOTALES',
                    'TODOS',
                    $this->totales['unidades'],
                    $this->totales['total_generado'],
                    $this->totales['total_cobrado'],
                    $this->totales['total_deuda'],
                    $this->totales['porcentaje_cobranza'] . '%',
                ];
            } else {
                $totalRow = [
                    'TOTALES',
                    '—',
                    '—',
                    '—',
                    '—',
                    $this->totales['total_generado'],
                    $this->totales['total_cobrado'],
                    $this->totales['total_deuda'],
                    '—',
                    $this->totales['porcentaje_cobranza'] . '%',
                ];
            }
            $rows->push($totalRow);
        }

        return $rows;
    }

    /**
     * Definir encabezados
     */
    public function headings(): array
    {
        if ($this->tipoVista === 'resumido') {
            return [
                'TIPO UNIDAD',
                'PERÍODO',
                'CANTIDAD',
                'GENERADO Bs.',
                'COBRADO Bs.',
                'DEUDA Bs.',
                '% COBRANZA',
            ];
        } else {
            return [
                'UNIDAD',
                'TIPO',
                'PROPIETARIO',
                'PISO',
                'PERÍODO',
                'GENERADO Bs.',
                'COBRADO Bs.',
                'DEUDA Bs.',
                'ESTADO',
                '% COBRANZA',
            ];
        }
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return $this->tipoVista === 'resumido'
            ? 'REPORTE RESUMIDO'
            : 'REPORTE DETALLADO';
    }

    /**
     * Formato de columnas
     */
    public function columnFormats(): array
    {
        if ($this->tipoVista === 'resumido') {
            return [
                'C' => NumberFormat::FORMAT_NUMBER,
                'D' => NumberFormat::FORMAT_NUMBER_00,
                'E' => NumberFormat::FORMAT_NUMBER_00,
                'F' => NumberFormat::FORMAT_NUMBER_00,
            ];
        } else {
            return [
                'F' => NumberFormat::FORMAT_NUMBER_00,
                'G' => NumberFormat::FORMAT_NUMBER_00,
                'H' => NumberFormat::FORMAT_NUMBER_00,
            ];
        }
    }

    /**
     * Estilos de las celdas
     */
    public function styles(Worksheet $sheet)
    {
        $totalRows = $this->data->count();
        $lastRow = $totalRows + 2; // +1 por encabezados, +1 por fila de totales

        return [
            // Estilo para encabezados (fila 1)
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],

            // Estilo para fila de totales
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA'],
                ],
            ],

            // Ajustar ancho de columnas
            'A' => ['width' => 25],
            'B' => ['width' => 15],
            'C' => ['width' => 10],
            'D' => ['width' => 15],
            'E' => ['width' => 15],
            'F' => ['width' => 15],
        ];
    }
}