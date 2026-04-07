<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CombinadoExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function collection()
    {
        $rows = [];

        // Información del encabezado
        $rows[] = ['REPORTE FINANCIERO COMBINADO'];
        $rows[] = ['Edificio: ' . $this->datos['configuracion']->nombre_edificio];
        $rows[] = ['Periodo: ' . $this->datos['fecha_inicio'] . ' - ' . $this->datos['fecha_fin']];
        $rows[] = ['Generado: ' . $this->datos['fecha_generacion']];
        $rows[] = []; // Línea vacía

        // Resumen financiero
        $rows[] = ['RESUMEN FINANCIERO'];
        $rows[] = ['Total Ingresos:', $this->datos['totales']['total_ingresos']];
        $rows[] = ['Total Egresos:', $this->datos['totales']['total_egresos']];
        $rows[] = ['BALANCE:', $this->datos['totales']['balance']];
        $rows[] = []; // Línea vacía

        // INGRESOS
        $rows[] = ['INGRESOS'];
        $rows[] = [
            'N° Recibo',
            'Fecha',
            'Categoría',
            'Descripción',
            'Importe'
        ];

        foreach ($this->datos['ingresos'] as $ingreso) {
            $rows[] = [
                $ingreso->numero_recibo,
                $ingreso->fecha->format('d/m/Y'),
                $ingreso->categoria ? $ingreso->categoria->nombre : '-',
                $ingreso->descripcion ?? '-',
                $ingreso->importe
            ];
        }

        $rows[] = []; // Línea vacía

        // EGRESOS
        $rows[] = ['EGRESOS'];
        $rows[] = [
            'N° Egreso',
            'Fecha',
            'Categoría',
            'Concepto',
            'Importe'
        ];

        foreach ($this->datos['egresos'] as $egreso) {
            $rows[] = [
                $egreso->numero_egreso,
                $egreso->fecha->format('d/m/Y'),
                $egreso->categoria ? $egreso->categoria->nombre : '-',
                $egreso->concepto,
                $egreso->importe
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return []; // Los encabezados se manejan en collection()
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Título principal
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Balance
            8 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E3F2FD']],
            ],
            // Encabezados INGRESOS
            11 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8F5E9']],
            ],
            // Encabezados EGRESOS
            'A' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
        ];
    }

    public function title(): string
    {
        return 'Reporte Financiero';
    }
}
