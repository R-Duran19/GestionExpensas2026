<?php

namespace App\Exports;

use App\Models\Unidad;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnidadesProrrateoExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithEvents
{
    protected Collection $unidades;

    protected float $factorDomiciliario;

    protected float $factorComercial;

    public function __construct(Collection $unidades, float $factorDomiciliario, float $factorComercial)
    {
        $this->unidades = $unidades;
        $this->factorDomiciliario = $factorDomiciliario;
        $this->factorComercial = $factorComercial;
    }

    public function collection(): Collection
    {
        $filas = $this->unidades->map(function (Unidad $unidad, int $index) {
            $fila = $index + 2;
            $propietario = $unidad->propietarioActual
                ? $unidad->propietarioActual->nombre_completo
                : 'Sin asignar';

            $formulaTotal = "=ROUND(C{$fila}*D{$fila},0)";

            return [
                $unidad->codigo,
                $propietario,
                (float) $unidad->metros_cuadrados,
                (float) $unidad->factor_prorrateo,
                $formulaTotal,
            ];
        });

        $ultimaFilaDatos = $this->unidades->count() + 1;
        $filas->push([
            'TOTAL',
            '',
            '',
            '',
            "=SUM(E2:E{$ultimaFilaDatos})",
        ]);

        return $filas;
    }

    public function headings(): array
    {
        return [
            'UNIDAD',
            'PROPIETARIO',
            'm²',
            'FACTOR',
            'TOTAL (Bs)',
        ];
    }

    public function title(): string
    {
        return 'Prorrateo de Unidades';
    }

    public function styles(Worksheet $sheet): array
    {
        $ultimaFila = $this->unidades->count() + 2;

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
            $ultimaFila => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA'],
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '4472C4'],
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getColumnDimension('A')->setWidth(18);
                $sheet->getColumnDimension('B')->setWidth(42);
                $sheet->getColumnDimension('C')->setWidth(10);
                $sheet->getColumnDimension('D')->setWidth(10);
                $sheet->getColumnDimension('E')->setWidth(14);

                $ultimaFila = $this->unidades->count() + 2;
                $sheet->getStyle("C2:E{$ultimaFila}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setAutoFilter('A1:E1');
                $sheet->freezePane('A2');
            },
        ];
    }
}
