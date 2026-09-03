<?php

declare(strict_types=1);

namespace App\Support;

use League\Csv\EscapeFormula;

/**
 * CSV export helpers. `row()` neutralises spreadsheet formula injection: a cell
 * beginning with = + - @ (or a control char) is prefixed with a single quote so
 * Excel / Google Sheets treats it as text, not a formula.
 */
final class Csv
{
    private static ?EscapeFormula $escaper = null;

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    public static function row(array $row): array
    {
        self::$escaper ??= new EscapeFormula;

        return self::$escaper->escapeRecord($row);
    }
}
