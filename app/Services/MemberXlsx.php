<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class MemberXlsx
{
    public const HEADERS = [
        'name',
        'email',
        'grade',
        'kelas',
    ];

    private const MAX_XML_SIZE = 10 * 1024 * 1024;
    private const RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    public function template(): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi ZIP PHP diperlukan untuk membuat template XLSX.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'member-template-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Gagal menyiapkan file template XLSX.');
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($temporaryPath, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Gagal membuat file template XLSX.');
            }

            $files = [
                '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                    . '<Default Extension="xml" ContentType="application/xml"/>'
                    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                    . '</Types>',
                '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                    . '</Relationships>',
                'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
                    . ' xmlns:r="' . self::RELATIONSHIP_NAMESPACE . '">'
                    . '<sheets><sheet name="Anggota" sheetId="1" r:id="rId1"/></sheets></workbook>',
                'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                    . '</Relationships>',
                'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                    . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
                    . '<fills count="2"><fill><patternFill patternType="none"/></fill>'
                    . '<fill><patternFill patternType="gray125"/></fill></fills>'
                    . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                    . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                    . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
                    . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
                    . '</styleSheet>',
                'xl/worksheets/sheet1.xml' => $this->templateWorksheet(),
            ];

            foreach ($files as $name => $contents) {
                if (!$zip->addFromString($name, $contents)) {
                    $zip->close();
                    throw new RuntimeException('Gagal menulis file template XLSX.');
                }
            }

            if (!$zip->close()) {
                throw new RuntimeException('Gagal menyelesaikan file template XLSX.');
            }

            $contents = file_get_contents($temporaryPath);
            if ($contents === false) {
                throw new RuntimeException('Gagal membaca file template XLSX yang dibuat.');
            }

            return $contents;
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    /**
     * @return array<int, array{row: int, data: array<string, string>}>
     */
    public function read(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi ZIP PHP diperlukan untuk membaca XLSX.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('File bukan dokumen XLSX yang valid.');
        }

        try {
            $workbook = $this->readXml($zip, 'xl/workbook.xml');
            $workbookRelationships = $this->readXml($zip, 'xl/_rels/workbook.xml.rels');
            $worksheetPath = $this->worksheetPath($workbook, $workbookRelationships);
            $worksheet = $this->parseXml($this->readXml($zip, $worksheetPath));
            $sharedStrings = $this->readSharedStrings($zip);
            $rawRows = $this->readRows($worksheet, $sharedStrings);

            if (!isset($rawRows[1])) {
                throw new InvalidArgumentException('Baris pertama XLSX harus berisi nama kolom template.');
            }

            $headerMap = $this->headerMap($rawRows[1]);
            $rows = [];

            foreach ($rawRows as $rowNumber => $cells) {
                if ($rowNumber === 1 || $this->isBlankRow($cells)) {
                    continue;
                }

                $data = [];
                foreach (self::HEADERS as $header) {
                    $data[$header] = (string) ($cells[$headerMap[$header]] ?? '');
                }

                $rows[] = ['row' => $rowNumber, 'data' => $data];
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function templateWorksheet(): string
    {
        $cells = [];
        foreach (self::HEADERS as $index => $header) {
            $column = $this->columnName($index + 1);
            $value = htmlspecialchars($header, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $cells[] = '<c r="' . $column . '1" t="inlineStr"><is><t>' . $value . '</t></is></c>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:D1"/><sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols><col min="1" max="2" width="24" style="1"/></cols>'
            . '<sheetData><row r="1">' . implode('', $cells)
            . '</row></sheetData></worksheet>';
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)) . $name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function readXml(ZipArchive $zip, string $path): string
    {
        $stat = $zip->statName($path);
        if ($stat === false || $stat['size'] > self::MAX_XML_SIZE) {
            throw new InvalidArgumentException('Struktur XLSX tidak lengkap atau ukurannya terlalu besar.');
        }

        $contents = $zip->getFromName($path);
        if ($contents === false) {
            throw new InvalidArgumentException('Struktur XLSX tidak lengkap atau rusak.');
        }

        return $contents;
    }

    private function parseXml(string $contents): DOMDocument
    {
        $document = new DOMDocument();
        if (!$document->loadXML($contents, LIBXML_NONET | LIBXML_NOBLANKS)) {
            throw new InvalidArgumentException('Struktur XML di dalam XLSX tidak valid.');
        }

        return $document;
    }

    private function worksheetPath(string $workbook, string $relationships): string
    {
        $workbookDocument = $this->parseXml($workbook);
        $workbookXPath = new DOMXPath($workbookDocument);
        $sheet = $workbookXPath->query('//*[local-name()="sheets"]/*[local-name()="sheet"]')->item(0);
        if (!$sheet instanceof DOMElement) {
            throw new InvalidArgumentException('Dokumen XLSX tidak memiliki worksheet.');
        }

        $relationshipId = $sheet->getAttributeNS(self::RELATIONSHIP_NAMESPACE, 'id');
        $relationshipDocument = $this->parseXml($relationships);
        $relationshipXPath = new DOMXPath($relationshipDocument);
        $target = null;
        foreach ($relationshipXPath->query('//*[local-name()="Relationship"]') as $relationship) {
            if ($relationship instanceof DOMElement && $relationship->getAttribute('Id') === $relationshipId) {
                $target = $relationship->getAttribute('Target');
                break;
            }
        }

        if (!$target) {
            throw new InvalidArgumentException('Worksheet XLSX tidak dapat ditemukan.');
        }

        $target = str_replace('\\', '/', $target);
        $segments = str_starts_with($target, '/') ? [] : ['xl'];
        foreach (explode('/', ltrim($target, '/')) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($segments === []) {
                    throw new InvalidArgumentException('Path worksheet XLSX tidak valid.');
                }
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $document = $this->parseXml($this->readXml($zip, 'xl/sharedStrings.xml'));
        $xpath = new DOMXPath($document);
        $strings = [];

        foreach ($xpath->query('//*[local-name()="sst"]/*[local-name()="si"]') as $item) {
            $text = '';
            foreach ($xpath->query('.//*[local-name()="t"]', $item) as $textNode) {
                $text .= $textNode->textContent;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    /**
     * @param array<int, string> $sharedStrings
     * @return array<int, array<int, string>>
     */
    private function readRows(DOMDocument $worksheet, array $sharedStrings): array
    {
        $xpath = new DOMXPath($worksheet);
        $rows = [];

        foreach ($xpath->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
            if (!$row instanceof DOMElement) {
                continue;
            }

            $rowNumber = (int) $row->getAttribute('r');
            if ($rowNumber < 1) {
                continue;
            }

            $cells = [];
            foreach ($xpath->query('./*[local-name()="c"]', $row) as $cell) {
                if (!$cell instanceof DOMElement || !preg_match('/^([A-Z]+)\d+$/i', $cell->getAttribute('r'), $match)) {
                    continue;
                }

                $column = 0;
                foreach (str_split(strtoupper($match[1])) as $letter) {
                    $column = ($column * 26) + ord($letter) - 64;
                }
                $column--;

                if ($cell->getAttribute('t') === 'inlineStr') {
                    $value = '';
                    foreach ($xpath->query('.//*[local-name()="is"]//*[local-name()="t"]', $cell) as $textNode) {
                        $value .= $textNode->textContent;
                    }
                } else {
                    $valueNode = $xpath->query('./*[local-name()="v"]', $cell)->item(0);
                    $value = $valueNode?->textContent ?? '';
                    if ($cell->getAttribute('t') === 's') {
                        $sharedStringIndex = (int) $value;
                        if (!array_key_exists($sharedStringIndex, $sharedStrings)) {
                            throw new InvalidArgumentException('Indeks teks pada XLSX tidak valid.');
                        }
                        $value = $sharedStrings[$sharedStringIndex];
                    }
                }

                $cells[$column] = $value;
            }

            $rows[$rowNumber] = $cells;
        }

        ksort($rows);

        return $rows;
    }

    /**
     * @param array<int, string> $cells
     * @return array<string, int>
     */
    private function headerMap(array $cells): array
    {
        $headers = [];
        foreach ($cells as $column => $value) {
            $header = strtolower(trim($value));
            if ($header === '') {
                continue;
            }
            if (isset($headers[$header])) {
                throw new InvalidArgumentException('Nama kolom XLSX tidak boleh duplikat.');
            }
            $headers[$header] = $column;
        }

        $missing = array_values(array_diff(self::HEADERS, array_keys($headers)));
        $extra = array_values(array_diff(array_keys($headers), self::HEADERS));
        if ($missing !== [] || $extra !== []) {
            throw new InvalidArgumentException(
                'Header XLSX tidak sesuai template. Gunakan kolom: ' . implode(', ', self::HEADERS) . '.'
            );
        }

        return $headers;
    }

    /**
     * @param array<int, string> $cells
     */
    private function isBlankRow(array $cells): bool
    {
        foreach ($cells as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
