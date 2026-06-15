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

class ReporteGeneralHorizontalExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, WithTitle
{
    protected $datosHorizontales;
    protected $tipoVista;

    /**
     * Constructor
     *
     * @param array $datosHorizontales - Datos transformados a formato horizontal (IGUAL que frontend)
     * @param string $tipoVista - 'resumido' o 'detallado'
     */
    public function __construct(array $datosHorizontales, string $tipoVista)
    {
        $this->datosHorizontales = $datosHorizontales;
        $this->tipoVista = $tipoVista;
    }

    /**
     * Obtener colección de datos
     */
    public function collection()
    {
        if (empty($this->datosHorizontales) || empty($this->datosHorizontales['filas'])) {
            return collect();
        }

        $filas = $this->datosHorizontales['filas'];

        $rows = collect($filas)->map(function ($fila) {
            if ($this->tipoVista === 'resumido') {
                // Vista Resumida: Tipo Unidad | Período 1 (GEN) | Período 2 (GEN) | ... | TOTAL GENERADO | TOTAL PAGADO | TOTAL DEUDA
                $row = [
                    $fila['tipo_unidad'],
                ];

                // Agregar datos de cada período (solo generado)
                foreach ($fila['periodos'] as $periodo) {
                    $row[] = $periodo['generado'];
                }

                // Agregar totales
                $row[] = $fila['totales']['generado'];
                $row[] = $fila['totales']['cobrado'];
                $row[] = $fila['totales']['deuda'];

                return $row;

            } else {
                // Vista Detallada: Unidad | Propietario | Período 1 (GEN) | Período 2 (GEN) | ... | TOTAL GENERADO | TOTAL PAGADO | TOTAL DEUDA | % COBRANZA
                $row = [
                    $fila['unidad'],
                    $fila['propietario'],
                ];

                // Agregar datos de cada período (solo generado)
                foreach ($fila['periodos'] as $periodo) {
                    $row[] = $periodo['generado'];
                }

                // Agregar totales
                $row[] = $fila['totales']['generado'];
                $row[] = $fila['totales']['cobrado'];
                $row[] = $fila['totales']['deuda'];
                $row[] = $fila['totales']['porcentaje_cobranza'] . '%';

                return $row;
            }
        });

        return $rows;
    }

    /**
     * Definir encabezados
     */
    public function headings(): array
    {
        if (empty($this->datosHorizontales) || empty($this->datosHorizontales['periodos'])) {
            return [];
        }

        $periodos = $this->datosHorizontales['periodos'];

        if ($this->tipoVista === 'resumido') {
            // Vista Resumida: Tipo Unidad | Período 1 (GEN) | Período 2 (GEN) | ... | TOTAL GENERADO | TOTAL PAGADO | TOTAL DEUDA
            $headings = ['TIPO UNIDAD'];

            foreach ($periodos as $periodo) {
                $headings[] = $periodo['nombre'] . ' (GEN)';
            }

            $headings[] = 'TOTAL GENERADO Bs.';
            $headings[] = 'TOTAL PAGADO Bs.';
            $headings[] = 'TOTAL DEUDA Bs.';

            return $headings;

        } else {
            // Vista Detallada: Unidad | Propietario | Período 1 (GEN) | Período 2 (GEN) | ... | TOTAL GENERADO | TOTAL PAGADO | TOTAL DEUDA | % COBRANZA
            $headings = ['UNIDAD', 'PROPIETARIO'];

            foreach ($periodos as $periodo) {
                $headings[] = $periodo['nombre'] . ' (GEN)';
            }

            $headings[] = 'TOTAL GENERADO Bs.';
            $headings[] = 'TOTAL PAGADO Bs.';
            $headings[] = 'TOTAL DEUDA Bs.';
            $headings[] = '% COBRANZA';

            return $headings;
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
        $formats = [];

        if ($this->tipoVista === 'resumido') {
            // Vista Resumida: col A = texto, col B+ = números
            $startCol = 2; // Empezar desde la columna B
        } else {
            // Vista Detallada: col A-B = texto, col C+ = números
            $startCol = 3; // Empezar desde la columna C
        }

        // Aplicar formato numérico a todas las columnas numéricas
        foreach (range($startCol, 100) as $colIndex) {
            $letter = $this->numberToLetter($colIndex);
            $formats[$letter] = NumberFormat::FORMAT_NUMBER_00;
        }

        return $formats;
    }

    /**
     * Convertir número de columna a letra (1 = A, 2 = B, etc.)
     */
    protected function numberToLetter($number)
    {
        $letter = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $number = floor(($number - 1) / 26);
        }
        return $letter;
    }

    /**
     * Estilos de las celdas
     */
    public function styles(Worksheet $sheet)
    {
        // Calcular columnas dinámicamente
        $numColumnas = 0;
        if (!empty($this->datosHorizontales) && !empty($this->datosHorizontales['periodos'])) {
            $numPeriodos = count($this->datosHorizontales['periodos']);

            if ($this->tipoVista === 'resumido') {
                // Tipo Unidad + N períodos + 3 totales
                $numColumnas = 1 + $numPeriodos + 3;
            } else {
                // Unidad + Propietario + N períodos + 3 totales + % cobranza
                $numColumnas = 2 + $numPeriodos + 4;
            }
        }

        $styles = [
            // Estilo para encabezados (fila 1)
            1 => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 9,
                ],
            ],
        ];

        // Ajustar ancho de columnas
        if ($this->tipoVista === 'resumido') {
            $sheet->getColumnDimension('A')->setWidth(20);
        } else {
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(30);
        }

        // Ancho para columnas de períodos y totales
        $lastColumn = $this->numberToLetter($numColumnas);
        foreach (range('B', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setWidth(14);
        }

        return $styles;
    }
}