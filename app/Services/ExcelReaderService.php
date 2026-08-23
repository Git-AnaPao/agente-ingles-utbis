<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ExcelReaderService
{
    /**
     * Lee un archivo Excel y retorna los datos como array.
     *
     * @param string $filePath Ruta del archivo Excel
     * @return array Datos del Excel organizados por hojas
     */
    public function readExcel(string $filePath): array
    {
        if (!file_exists($filePath)) {
            Log::error('Excel file not found', ['path' => $filePath]);
            return [];
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            Log::error('Unsupported file format', ['extension' => $extension]);
            return [];
        }

        if ($extension === 'csv') {
            return $this->readCsv($filePath);
        }

        return $this->readXlsx($filePath);
    }

    /**
     * Lee un archivo CSV.
     */
    private function readCsv(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return [];
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }

        fclose($handle);
        return $data;
    }

    /**
     * Lee un archivo XLSX usando PhpSpreadsheet.
     * Si PhpSpreadsheet no está instalado, retorna array vacío.
     */
    private function readXlsx(string $filePath): array
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            Log::error('PhpSpreadsheet not installed. Run: composer require phpoffice/phpspreadsheet');
            return [];
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $data = [];

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheetName = $sheet->getTitle();
                $rows = [];

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                Log::info('Processing sheet', [
                    'sheet' => $sheetName,
                    'rows' => $highestRow,
                    'columns' => $highestColumnIndex,
                ]);

                if ($highestRow < 2) {
                    Log::info('Sheet skipped: fewer than 2 rows', ['sheet' => $sheetName]);
                    continue;
                }

                $headers = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $headers[] = $sheet->getCell("{$colLetter}1")->getValue();
                }

                for ($row = 2; $row <= $highestRow; $row++) {
                    $rowData = [];
                    $isEmpty = true;

                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $value = $sheet->getCell("{$colLetter}{$row}")->getValue();
                        $rowData[$headers[$col - 1]] = $value;
                        if ($value !== null && $value !== '') {
                            $isEmpty = false;
                        }
                    }

                    if (!$isEmpty) {
                        $rows[] = $rowData;
                    }
                }

                Log::info('Sheet data read', [
                    'sheet' => $sheetName,
                    'data_rows' => count($rows),
                ]);

                $data[$sheetName] = $rows;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error reading Excel file', [
                'error' => $e->getMessage(),
                'file' => $filePath,
            ]);
            return [];
        }
    }

    /**
     * Lee un archivo Excel desde contenido binario.
     *
     * @param string $content Contenido del archivo
     * @param string $extension Extensión del archivo (xlsx, xls, csv)
     * @return array Datos del Excel
     */
    public function readFromContent(string $content, string $extension = 'xlsx'): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        file_put_contents($tempFile . '.' . $extension, $content);

        $data = $this->readExcel($tempFile . '.' . $extension);

        @unlink($tempFile . '.' . $extension);

        return $data;
    }
}
