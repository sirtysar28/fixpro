<?php

namespace App\Services;

use Illuminate\Http\Response;
use RuntimeException;
use ZipArchive;

/**
 * Lightweight native .xlsx (Office Open XML) writer — zero dependencies.
 *
 * Uses only PHP's ZipArchive extension + XML string building. Produces files
 * that open cleanly in MS Excel, LibreOffice Calc, Google Sheets, Apple Numbers,
 * and WPS Office (true .xlsx, not the legacy SpreadsheetML .xml-as-.xls hack).
 *
 * Usage:
 *   $w = new XlsxWriter();
 *   $s = $w->sheet('Stok');
 *   $s->widths([150, 100, 80])->headerRow(['Nama', 'Kode', 'Jumlah']);
 *   $s->row(['LCD iPhone 11', 'LCD-IP11', 10]);
 *   $s->row([$s->money(150000, 'rp_red'), $s->money(250000, 'rp_green')]);
 *   $w->download('Stok.xlsx');
 *
 * Style keys: '' (default), 'header', 'bold', 'total',
 *             'rp', 'rp_total', 'rp_green', 'rp_red', 'title', 'sub'
 */
class XlsxWriter
{
    /** @var XlsxSheet[] */
    private array $sheets = [];

    /** Map of style-key => fixed cellXfs index (see stylesXml()). */
    public const STYLE_INDEX = [
        ''         => 0,
        'header'   => 1,
        'bold'     => 2,
        'total'    => 3,
        'rp'       => 4,
        'rp_total' => 5,
        'rp_green' => 6,
        'rp_red'   => 7,
        'title'    => 8,
        'sub'      => 9,
    ];

    /** Create (or reuse) a worksheet. */
    public function sheet(string $name): XlsxSheet
    {
        $name = $this->sanitizeSheetName($name);
        $used = array_map(fn (XlsxSheet $s) => $s->name, $this->sheets);
        $base = $name;
        $i = 1;
        while (in_array($name, $used, true)) {
            $suffix = '_' . $i;
            $name = mb_substr($base, 0, 31 - strlen($suffix)) . $suffix;
            $i++;
        }
        $sheet = new XlsxSheet($name);
        $this->sheets[] = $sheet;
        return $sheet;
    }

    /** Build binary .xlsx content. */
    public function build(): string
    {
        if (empty($this->sheets)) {
            throw new RuntimeException('XlsxWriter: no sheets added.');
        }
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('XlsxWriter: PHP ZipArchive extension is required.');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tmpFile === false) {
            throw new RuntimeException('XlsxWriter: cannot create temp file.');
        }

        $zip = new ZipArchive();
        $res = $zip->open($tmpFile, ZipArchive::OVERWRITE);
        if ($res !== true) {
            @unlink($tmpFile);
            throw new RuntimeException('XlsxWriter: cannot create zip archive (code ' . $res . ').');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        $i = 1;
        foreach ($this->sheets as $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . $i . '.xml', $sheet->toXml());
            $i++;
        }

        $zip->close();
        $content = file_get_contents($tmpFile);
        @unlink($tmpFile);
        if ($content === false) {
            throw new RuntimeException('XlsxWriter: cannot read generated archive.');
        }
        return $content;
    }

    /** Stream as a download response. */
    public function download(string $filename): Response
    {
        return response($this->build(), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
            'Pragma'              => 'public',
        ]);
    }

    // ============================================================
    //  Internal XML builders
    // ============================================================

    private function sanitizeSheetName(string $name): string
    {
        // Excel forbids : \ / ? * [ ] and max 31 chars
        $name = str_replace([':', '\\', '/', '?', '*', '[', ']'], '_', $name);
        $name = trim($name);
        if ($name === '') {
            $name = 'Sheet';
        }
        return mb_substr($name, 0, 31);
    }

    private function contentTypesXml(): string
    {
        $overrides = '';
        $n = count($this->sheets);
        for ($i = 1; $i <= $n; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml"'
                . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        $sheets = '';
        $i = 1;
        foreach ($this->sheets as $sheet) {
            $sheets .= '<sheet name="' . $this->escAttr($sheet->name) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
            $i++;
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        $n = count($this->sheets);
        $rels = '';
        for ($i = 1; $i <= $n; $i++) {
            $rels .= '<Relationship Id="rId' . $i . '"'
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                . ' Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . ($n + 1) . '"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            . ' Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    /**
     * Fixed style table. cellXfs indices MUST match self::STYLE_INDEX.
     * Uses builtin numFmtId=3 (#,##0) for currency — no custom numFmt needed.
     */
    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="8">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'                              // 0 default
            . '<font><b/><color rgb="FFFFFFFF"/><sz val="10"/><name val="Calibri"/></font>'  // 1 header (white bold)
            . '<font><b/><sz val="10"/><name val="Calibri"/></font>'                         // 2 bold
            . '<font><b/><color rgb="FF16A34A"/><sz val="10"/><name val="Calibri"/></font>'  // 3 green bold
            . '<font><color rgb="FFDC2626"/><sz val="10"/><name val="Calibri"/></font>'      // 4 red
            . '<font><b/><color rgb="FF0D9488"/><sz val="14"/><name val="Calibri"/></font>'  // 5 title (teal bold large)
            . '<font><color rgb="FF64748B"/><sz val="10"/><name val="Calibri"/></font>'      // 6 sub (gray)
            . '<font><b/><sz val="10"/><name val="Calibri"/></font>'                         // 7 bold (for total reuse)
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'                               // 0
            . '<fill><patternFill patternType="gray125"/></fill>'                            // 1 (required placeholder)
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0D9488"/></patternFill></fill>' // 2 teal (header bg)
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF0FDFA"/></patternFill></fill>' // 3 light teal (total bg)
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="10">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'                                                                                        // 0  default
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 1  header
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" applyFont="1"/>'                                                                          // 2  bold
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" applyFont="1" applyFill="1"/>'                                                            // 3  total
            . '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/>'                                                                  // 4  rp
            . '<xf numFmtId="3" fontId="2" fillId="3" borderId="0" applyFont="1" applyFill="1" applyNumberFormat="1"/>'                                       // 5  rp_total
            . '<xf numFmtId="3" fontId="3" fillId="0" borderId="0" applyFont="1" applyNumberFormat="1"/>'                                                    // 6  rp_green
            . '<xf numFmtId="3" fontId="4" fillId="0" borderId="0" applyNumberFormat="1"/>'                                                                  // 7  rp_red
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" applyFont="1"/>'                                                                          // 8  title
            . '<xf numFmtId="0" fontId="6" fillId="0" borderId="0" applyFont="1"/>'                                                                          // 9  sub
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function escAttr(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
