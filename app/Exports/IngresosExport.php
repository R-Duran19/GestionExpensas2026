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

class IngresosExport implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        $rows[] = ['REPORTE DE INGRESOS'];
        $rows[] = ['Edificio: ' . $this->datos['configuracion']->nombre_edificio];
        $rows[] = ['Periodo: ' . $this->datos['fecha_inicio'] . ' - ' . $this->datos['fecha_fin']];
        $rows[] = ['Generado: ' . $this->datos['fecha_generacion']];
        $rows[] = []; // Línea vacía

        // Encabezados de la tabla
        $rows[] = [
            'N° Recibo',
            'Fecha',
            'Categoría',
            'Descripción',
            'Método Pago',
            'Referencia',
            'Importe',
            'Propietario',
            'Observaciones'
        ];

        // Datos de ingresos
        foreach ($this->datos['ingresos'] as $ingreso) {
            $rows[] = [
                $ingreso->numero_recibo,
                $ingreso->fecha->format('d/m/Y'),
                $ingreso->categoria ? $ingreso->categoria->nombre : '-',
                $ingreso->descripcion ?? '-',
                $this->getMetodoPago($ingreso->metodo_pago),
                $ingreso->referencia_bancaria ?? '-',
                $ingreso->importe,
                $ingreso->propietario ? ($ingreso->propietario->nombre . ' ' . $ingreso->propietario->apellido) : '-',
                $ingreso->observaciones ?? '-'
            ];
        }

        $rows[] = []; // Línea vacía

        // Totales
        $rows[] = ['RESUMEN'];
        $rows[] = ['Total Ingresos:', $this->datos['totales']['cantidad']];
        $rows[] = ['Total Efectivo:', $this->datos['totales']['total_efectivo']];
        $rows[] = ['Total Transferencias:', $this->datos['totales']['total_transferencia']];
        $rows[] = ['Total QR:', $this->datos['totales']['total_qr']];
        $rows[] = ['Total Otros:', $this->datos['totales']['total_otro']];
        $rows[] = ['TOTAL GENERAL:', $this->datos['totales']['total_general']];

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
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8F5E9']],
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
        return 'Ingresos';
    }

    protected function getMetodoPago($metodo): string
    {
        return match($metodo) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'qr' => 'QR',
            'otro' => 'Otro',
            default => ucfirst($metodo),
        };
    }
}
