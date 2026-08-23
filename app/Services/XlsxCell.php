<?php

namespace App\Services;

/**
 * Value object describing a single cell's content + style.
 * Created via XlsxSheet helpers (text/num/money/cell/blank).
 */
class XlsxCell
{
    /** Raw value (scalar|null). */
    public $value;

    /** Style key — see XlsxWriter::STYLE_INDEX. */
    public string $style;

    /** 'str' | 'num' | 'empty'. */
    public string $type;

    public function __construct($value, string $style = '', ?string $type = null)
    {
        $this->value = $value;
        $this->style = $style;

        if ($type !== null) {
            $this->type = $type;
            return;
        }
        // Auto-detect type from value when not specified.
        if ($value === null || $value === '') {
            $this->type = 'empty';
        } elseif (is_int($value) || is_float($value)) {
            $this->type = 'num';
        } elseif (is_string($value) && preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            $this->type = 'num';
        } else {
            $this->type = 'str';
        }
    }
}
