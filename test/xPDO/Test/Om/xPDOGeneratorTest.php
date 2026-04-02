<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Om;

use PHPUnit\Framework\TestCase;
use xPDO\Om\xPDOGenerator;

/**
 * Tests related to xPDOGenerator utility methods.
 *
 * @package xPDO\Test\Om
 */
class xPDOGeneratorTest extends TestCase
{
    /**
     * Regression test for PHP 8.1 deprecation: str_repeat() received a float
     * when $spaces is odd because $spaces / 2 produces a float.
     *
     * var_export() of a nested array emits 2-space-indented lines. The space
     * counting loop in varExport() uses next() which skips index 0, so a line
     * with 2 leading spaces yields $spaces = 1, and 1 / 2 = 0.5 (float).
     * The fix replaces $spaces / 2 with intdiv($spaces, 2).
     *
     * @see xPDOGenerator::varExport()
     */
    public function testVarExportNestedArrayOddSpacesProducesNoDeprecation(): void
    {
        // A nested array guarantees var_export() will emit lines with 2 leading
        // spaces (1 level of PHP's native 2-space indentation), which causes
        // $spaces = 1 (odd) inside varExport() — the float trigger.
        $input = ['key' => ['nested' => 'value']];

        $deprecationEmitted = false;
        set_error_handler(function (int $errno, string $errstr) use (&$deprecationEmitted): bool {
            if ($errno === E_DEPRECATED && str_contains($errstr, 'float')) {
                $deprecationEmitted = true;
            }
            return true;
        }, E_DEPRECATED);

        $result = xPDOGenerator::varExport($input, 1);

        restore_error_handler();

        $this->assertFalse(
            $deprecationEmitted,
            'xPDOGenerator::varExport() emitted a float-to-int deprecation notice — str_repeat() received a float from $spaces / 2.'
        );

        // Also assert the output is a non-empty string to confirm the method ran.
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Verify varExport() produces correctly indented output for a nested array
     * when $spaces is odd (the same code path as the float bug).
     */
    public function testVarExportNestedArrayReturnsString(): void
    {
        $input = ['a' => 1, 'b' => ['c' => 2]];
        $result = xPDOGenerator::varExport($input, 1);

        $this->assertIsString($result);
        // The result must start with the array opening token from var_export.
        $this->assertStringStartsWith('array (', $result);
    }
}
