<?php

namespace App\Services;

/**
 * A single worksheet inside an XlsxWriter document.
 *
 * Collect rows, then render to OOXML sheet XML via toXml().
 */
class XlsxSheet
{
    public string $name;

    /** @var array<int, float> column widths in character units (1-indexed position => width) */
    private array $columnWidths = [];

    /** @var list<list<XlsxCell>> */
    private array $rows = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /** Set column widths. Accepts [w1, w2, ...] mapped to columns A, B, C, ... */
    public function widths(array $widths): self
    {
        $this->columnWidths = array_values($widths);
        return $this;
    }

    /** Append a header row (styled bold/white/teal, centered). */
    public function headerRow(array $labels): self
    {
        $cells = [];
        foreach ($labels as $l) {
            $cells[] = new XlsxCell((string) $l, 'header', 'str');
        }
        $this->rows[] = $cells;
        return $this;
    }

    /**
     * Append a data row. Each item may be:
     *  - scalar (auto-typed, default style)
     *  - XlsxCell (explicit type/style) — use helpers cell()/money()/num()/text()/blank()
     */
    public function row(array $cells): self
    {
        $processed = [];
        foreach ($cells as $cell) {
            $processed[] = $this->normalize($cell);
        }
        $this->rows[] = $processed;
        return $this;
    }

    /** Append a fully empty row (spacer). */
    public function blankRow(): self
    {
        $this->rows[] = [];
        return $this;
    }

    // ---- Cell helpers (return XlsxCell for use inside row()) ----

    /** String cell with optional style. */
    public function text($value, string $style = ''): XlsxCell
    {
        return new XlsxCell($value, $style, 'str');
    }

    /** Number cell with optional style. */
    public function num($value, string $style = ''): XlsxCell
    {
        return new XlsxCell($value, $style, 'num');
    }

    /** Currency (Rp) cell. Defaults to 'rp' style (#,##0). */
    public function money($value, string $style = 'rp'): XlsxCell
    {
        return new XlsxCell($value, $style, 'num');
    }

    /** Explicit cell with value + style. Type auto-detected unless given. */
    public function cell($value, string $style = '', ?string $type = null): XlsxCell
    {
        return new XlsxCell($value, $style, $type);
    }

    /** Empty cell (optionally with a style to carry fill, e.g. 'total'). */
    public function blank(string $style = ''): XlsxCell
    {
        return new XlsxCell(null, $style, 'empty');
    }

    // ---- Rendering ----

    public function toXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Column widths
        if (!empty($this->columnWidths)) {
            $xml .= '<cols>';
            foreach ($this->columnWidths as $idx => $w) {
                $col = $idx + 1;
                $xml .= '<col min="' . $col . '" max="' . $col . '" width="' . $this->fmtWidth($w) . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        $rowNum = 1;
        foreach ($this->rows as $cells) {
            $xml .= '<row r="' . $rowNum . '">';
            $colNum = 1;
            foreach ($cells as $cell) {
                $xml .= $this->renderCell($cell, $colNum, $rowNum);
                $colNum++;
            }
            $xml .= '</row>';
            $rowNum++;
        }
        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    private function renderCell(XlsxCell $cell, int $colNum, int $rowNum): string
    {
        $ref = $this->colLetter($colNum) . $rowNum;
        $sIdx = XlsxWriter::STYLE_INDEX[$cell->style] ?? 0;
        $sAttr = $sIdx > 0 ? ' s="' . $sIdx . '"' : '';

        // Empty cell — still emit reference so style/fill renders (e.g. total row fill)
        if ($cell->type === 'empty') {
            return '<c r="' . $ref . '"' . $sAttr . '/>';
        }

        if ($cell->type === 'num') {
            return '<c r="' . $ref . '"' . $sAttr . '><v>' . $this->fmtNum($cell->value) . '</v></c>';
        }

        // string (inline)
        return '<c r="' . $ref . '"' . $sAttr . ' t="inlineStr"><is><t xml:space="preserve">'
            . $this->escText((string) $cell->value)
            . '</t></is></c>';
    }

    /** Normalize a raw cell value into an XlsxCell. */
    private function normalize($cell): XlsxCell
    {
        if ($cell instanceof XlsxCell) {
            return $cell;
        }
        if ($cell === null || $cell === '') {
            return new XlsxCell(null, '', 'empty');
        }
        if (is_int($cell) || is_float($cell)) {
            return new XlsxCell($cell, '', 'num');
        }
        // numeric string → number (keeps "150000" as number, not text)
        if (is_string($cell) && preg_match('/^-?\d+(\.\d+)?$/', $cell)) {
            return new XlsxCell($cell, '', 'num');
        }
        return new XlsxCell($cell, '', 'str');
    }

    /** Convert 1-based column number to letter (1→A, 27→AA). */
    private function colLetter(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = intdiv($n - 1, 26);
        }
        return $s;
    }

    private function fmtNum($v): string
    {
        if (!is_numeric($v)) {
            $v = 0;
        }
        $n = (float) $v;
        if (floor($n) == $n) {
            return (string) (int) $n;
        }
        // Trim insignificant trailing zeros from floats
        return rtrim(rtrim(number_format($n, 10, '.', ''), '0'), '.');
    }

    private function fmtWidth(float $w): string
    {
        return number_format($w, 2, '.', '');
    }

    private function escText(string $s): string
    {
        // Strip control chars illegal in XML (except tab/newline which we also drop for cells)
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '';
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
