<?php

namespace App\Exports;

use App\Models\PeriodoExpensa;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LecturasPeriodoExport implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected Collection $rows;

    protected PeriodoExpensa $periodo;

    public function __construct(Collection $rows, PeriodoExpensa $periodo)
    {
        $this->rows = $rows;
        $this->periodo = $periodo;
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn ($r) => [
            $r['codigo'],
            $r['propietario'],
            $r['medidor'],
            $r['lectura_anterior'] !== null ? round((float) $r['lectura_anterior'], 2) : null,
            $r['lectura_actual'] !== null ? round((float) $r['lectura_actual'], 2) : null,
            round((float) $r['consumo_m3'], 2),
        ]);
    }

    public function headings(): array
    {
        return [
            'CÓDIGO',
            'PROPIETARIO',
            'MEDIDOR',
            'LECTURA ANTERIOR (M³)',
            'LECTURA ACTUAL (M³)',
            'CONSUMO (M³)',
        ];
    }

    public function title(): string
    {
        return 'Lecturas '.$this->periodo->nombre_periodo;
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
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
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $anchos = [
                    'A' => 28,
                    'B' => 34,
                    'C' => 24,
                    'D' => 22,
                    'E' => 22,
                    'F' => 16,
                ];
                foreach ($anchos as $col => $ancho) {
                    $sheet->getColumnDimension($col)->setWidth($ancho);
                }

                $ultimaFila = $sheet->getHighestDataRow();
                if ($ultimaFila > 1) {
                    $sheet->getStyle("D2:F{$ultimaFila}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $sheet->setAutoFilter('A1:F1');
                $sheet->freezePane('A2');
            },
        ];
    }
}
