<?php

namespace Tests\Unit;

use App\Services\MemberXlsx;
use InvalidArgumentException;
use Tests\TestCase;
use ZipArchive;

class MemberXlsxTest extends TestCase
{
    public function test_generated_template_is_a_valid_workbook_with_the_expected_headers(): void
    {
        $xlsx = new MemberXlsx();
        $path = $this->temporaryWorkbook($xlsx->template());

        try {
            $this->assertSame([], $xlsx->read($path));
        } finally {
            unlink($path);
        }
    }

    public function test_reader_maps_xlsx_columns_to_template_headers(): void
    {
        $xlsx = new MemberXlsx();
        $data = [
            'name' => 'Siswa Contoh',
            'email' => 'siswa@example.test',
            'grade' => '11',
            'kelas' => 'RPL 1',
        ];
        $headers = array_reverse(MemberXlsx::HEADERS);
        $values = array_map(fn ($header) => $data[$header], $headers);
        $sharedStrings = [];
        $worksheetRows = [
            1 => $headers,
            4 => $values,
        ];

        foreach ($worksheetRows as $row) {
            foreach ($row as $value) {
                if (!array_key_exists($value, $sharedStrings)) {
                    $sharedStrings[$value] = count($sharedStrings);
                }
            }
        }

        $path = $this->temporaryWorkbook($xlsx->template());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sharedStringWorksheet($worksheetRows, $sharedStrings));
        $zip->addFromString('xl/sharedStrings.xml', $this->sharedStringsXml(array_flip($sharedStrings)));
        $zip->close();

        try {
            $this->assertSame([
                [
                    'row' => 4,
                    'data' => $data,
                ],
            ], $xlsx->read($path));
        } finally {
            unlink($path);
        }
    }

    public function test_reader_rejects_workbooks_with_headers_that_do_not_match_the_template(): void
    {
        $xlsx = new MemberXlsx();
        $path = $this->temporaryWorkbook($xlsx->template());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $zip->addFromString(
            'xl/worksheets/sheet1.xml',
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>nama</t></is></c></row></sheetData>'
                . '</worksheet>'
        );
        $zip->close();

        try {
            $this->expectException(InvalidArgumentException::class);
            $xlsx->read($path);
        } finally {
            unlink($path);
        }
    }

    public function test_reader_accepts_more_than_one_thousand_member_rows(): void
    {
        $xlsx = new MemberXlsx();
        $headers = MemberXlsx::HEADERS;
        $data = ['Siswa Contoh', 'siswa@example.test', '11', 'RPL 1'];
        $worksheetRows = [1 => $headers];

        for ($rowNumber = 2; $rowNumber <= 1002; $rowNumber++) {
            $worksheetRows[$rowNumber] = $data;
        }

        $sharedStrings = [];
        foreach ($worksheetRows as $row) {
            foreach ($row as $value) {
                if (!array_key_exists($value, $sharedStrings)) {
                    $sharedStrings[$value] = count($sharedStrings);
                }
            }
        }

        $path = $this->temporaryWorkbook((new MemberXlsx())->template());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sharedStringWorksheet($worksheetRows, $sharedStrings));
        $zip->addFromString('xl/sharedStrings.xml', $this->sharedStringsXml(array_flip($sharedStrings)));
        $zip->close();

        try {
            $this->assertCount(1001, $xlsx->read($path));
        } finally {
            unlink($path);
        }
    }

    private function temporaryWorkbook(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'member-xlsx-test-');
        $this->assertNotFalse($path);
        $this->assertNotFalse(file_put_contents($path, $contents));

        return $path;
    }

    /**
     * @param array<int, array<int, string>> $rows
     * @param array<string, int> $sharedStrings
     */
    private function sharedStringWorksheet(array $rows, array $sharedStrings): string
    {
        $xmlRows = [];
        foreach ($rows as $rowNumber => $values) {
            $cells = [];
            foreach ($values as $columnIndex => $value) {
                $cells[] = '<c r="' . chr(65 + $columnIndex) . $rowNumber . '" t="s"><v>'
                    . $sharedStrings[$value] . '</v></c>';
            }
            $xmlRows[] = '<row r="' . $rowNumber . '">' . implode('', $cells) . '</row>';
        }

        return '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData></worksheet>';
    }

    /**
     * @param array<int, string> $sharedStrings
     */
    private function sharedStringsXml(array $sharedStrings): string
    {
        $items = [];
        foreach ($sharedStrings as $value) {
            $items[] = '<si><t>' . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></si>';
        }

        return '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . implode('', $items) . '</sst>';
    }
}
