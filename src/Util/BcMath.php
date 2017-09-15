<?php

namespace Shopware\Plugins\MoptAvalara\Util;

use Shopware\Components\Model\ModelManager;

/**
 * This class may emulate bcmath if it is not installed
 *
 * @author bubnov
 */
class BcMath
{
    /**
     * @var string bcmath extension name
     */
    const BCMATH_EXTENTION = 'bcmath';

    /**
     * @var int Scale to be used in all bcmath calls
     */
    const BCMATH_SCALE = 8;

    /**
     * @var int Scale to be used to calculate netto from brutto
     */
    const NETTO_SCALE = 2;

    /**
     * @param mixed $left
     * @param mixed $right
     * @return float
     */
    public function bcadd($left, $right)
    {
        // Check if bcmath extention is avalible
        if (!extension_loaded(self::BCMATH_EXTENTION)) {
            return (float)round($left + $right, self::BCMATH_SCALE);
        }

        return (float)\bcadd($left, $right, self::BCMATH_SCALE);
    }

    /**
     * @param mixed $left
     * @param mixed $right
     * @return float
     */
    public function bcsub($left, $right)
    {
        // Check if bcmath extention is avalible
        if (!extension_loaded(self::BCMATH_EXTENTION)) {
            return (float)round($left - $right, self::BCMATH_SCALE);
        }

        return (float)\bcsub($left, $right, self::BCMATH_SCALE);
    }

    /**
     *
     * @param mixed $left
     * @param mixed $right
     * @return float
     */
    public function bcmul($left, $right)
    {
        // Check if bcmath extention is avalible
        if (!extension_loaded(self::BCMATH_EXTENTION)) {
            return (float)round($left * $right, self::BCMATH_SCALE);
        }

        return (float)\bcmul($left, $right, self::BCMATH_SCALE);
    }

    /**
     *
     * @param mixed $left
     * @param mixed $right
     * @param int $scale
     * @return float
     */
    public function bcdiv($left, $right, $scale = self::BCMATH_SCALE)
    {
        // Check if bcmath extention is avalible
        if (!extension_loaded(self::BCMATH_EXTENTION)) {
            return round($left / $right, $scale);
        }

        return (float)\bcdiv($left, $right, $scale);
    }

    /**
     * @param mixed $brutto
     * @param float $tax
     * @return float
     */
    public function calculateNetto($brutto, $tax)
    {
        $left = $this->bcmul($brutto, 100);
        $right = $this->bcadd($tax, 100);

        return (float)$this->bcdiv($left, $right, self::NETTO_SCALE);
    }

    /**
     * @param mixed $value
     * @return float
     */
    public function convertToFloat($value)
    {
        if (is_string($value)) {
            return (float)str_replace(',', '.', $value);
        }

        return $value;
    }
}
