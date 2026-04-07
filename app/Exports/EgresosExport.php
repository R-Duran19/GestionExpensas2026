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

class EgresosExport implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        $rows[] = ['REPORTE DE EGRESOS'];
        $rows[] = ['Edificio: ' . $this->datos['configuracion']->nombre_edificio];
        $rows[] = ['Periodo: ' . $this->datos['fecha_inicio'] . ' - ' . $this->datos['fecha_fin']];
        $rows[] = ['Generado: ' . $this->datos['fecha_generacion']];
        $rows[] = []; // Línea vacía

        // Encabezados de la tabla
        $rows[] = [
            'N° Egreso',
            'Fecha',
            'Categoría',
            'Concepto',
            'N° Comprobante',
            'Proveedor',
            'Importe',
            'Descripción',
            'Observaciones'
        ];

        // Datos de egresos
        foreach ($this->datos['egresos'] as $egreso) {
            $rows[] = [
                $egreso->numero_egreso,
                $egreso->fecha->format('d/m/Y'),
                $egreso->categoria ? $egreso->categoria->nombre : '-',
                $egreso->concepto,
                $egreso->numero_comprobante ?? '-',
                $egreso->proveedor ?? '-',
                $egreso->importe,
                $egreso->descripcion ?? '-',
                $egreso->observaciones ?? '-'
            ];
        }

        $rows[] = []; // Línea vacía

        // Totales
        $rows[] = ['RESUMEN'];
        $rows[] = ['Total Egresos:', $this->datos['totales']['cantidad']];
        $rows[] = ['TOTAL GENERAL:', $this->datos['totales']['total_general']];

        // Totales por categoría
        if (!empty($this->datos['totales']['por_categoria'])) {
            $rows[] = [];
            $rows[] = ['POR CATEGORÍA:'];
            foreach ($this->datos['totales']['por_categoria'] as $categoria => $total) {
                $rows[] = [$categoria, $total];
            }
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
            // Encabezados de tabla (fila 7)
            7 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFEBEE']],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ],
            // Totales
            'A' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
        ];
    }

    public function title(): string
    {
        return 'Egresos';
    }
}
