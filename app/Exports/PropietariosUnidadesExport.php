<?php

namespace App\Exports;

use App\Models\Propietario;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PropietariosUnidadesExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithEvents
{
    protected Collection $propietarios;

    public function __construct(Collection $propietarios)
    {
        $this->propietarios = $propietarios;
    }

    public function collection(): Collection
    {
        $filas = collect();

        foreach ($this->propietarios as $propietario) {
            $unidades = $propietario->unidadesActuales;

            if ($unidades->isEmpty()) {
                continue;
            }

            $esPrimeraFila = true;

            foreach ($unidades as $unidad) {
                $inquilino = $unidad->inquilinoActual;

                $filas->push([
                    $esPrimeraFila ? $propietario->nombre_completo : '',
                    $unidad->codigo,
                    $inquilino ? $inquilino->nombre_completo : '-',
                ]);

                $esPrimeraFila = false;
            }
        }

        return $filas;
    }

    public function headings(): array
    {
        return [
            'PROPIETARIO',
            'UNIDAD',
            'INQUILINO',
        ];
    }

    public function title(): string
    {
        return 'Propietarios y Unidades';
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

                $sheet->getColumnDimension('A')->setWidth(42);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(42);

                $ultimaFila = $sheet->getHighestDataRow();
                if ($ultimaFila > 1) {
                    $sheet->getStyle("B2:C{$ultimaFila}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->setAutoFilter('A1:C1');
                $sheet->freezePane('A2');
            },
        ];
    }
}
