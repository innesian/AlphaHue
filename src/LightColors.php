<?php namespace AlphaHue;

trait LightColors
{
    /**
     * Get XY from Red, Blue, Green value.
     *
     * @param int $color Color value, number between 0 and 255.
     *
     * @return number 
     */
    function convertColorToPoint($color)
    {
        $color = $color < 0   ? 0   : $color;
        $color = $color > 255 ? 255 : $color;
        return ($color > 0.04045) ? pow(($color + 1.055), 2.4) : ($color / 12.92);
    }

    /**
     * Converts Hex to RGB.
     * 
     * @param string $hex Hex string.
     *
     * @return array Array of color values.
     */
    function hexToRGB($hex)
    {
        $hex = ltrim($hex, '#');

        list($rgb['red'], $rgb['green'], $rgb['blue']) = str_split($hex, 2);

        $rgb = array_map('hexdec', $rgb);

        return $rgb;
    }

    /**
     * Get XY Point from Hex.
     *
     * @param string $hex Hex string.
     *
     * @return array XY point.
     */
    function getXYPointFromHex($hex)
    {
        $rgb = hexToRGB($hex);
        return getXYPointFromRGB($rgb);
    }

    /**
     * Get XY Point from RGB.
     *
     * @param int $red   Integer between 0 and 255.
     * @param int $green Integer between 0 and 255.
     * @param int $blue  Integer between 0 and 255.
     *
     * @return array Array of xy coordinates.
     */
    function getXYPointFromRGB($rgb)
    {

        $rgb['red'] = convertColorToPoint($rgb['red']);
        $rgb['green'] = convertColorToPoint($rgb['green']);
        $rgb['blue'] = convertColorToPoint($rgb['blue']);

        $x = $rgb['red'] * 0.4360747 + $rgb['green'] * 0.3850649 + $rgb['blue'] * 0.0930804;
        $y = $rgb['red'] * 0.2225045 + $rgb['green'] * 0.7168786 + $rgb['blue'] * 0.0406169;
        $z = $rgb['red'] * 0.0139322 + $rgb['green'] * 0.0971045 + $rgb['blue'] * 0.7141733;

        if (0 == ($x + $y + $z)) {
            $cx = $cy = 0;
        } else {
            $cx = $x / ($x + $y + $z);
            $cy = $y / ($x + $y + $z);
        }

        return array($cx, $cy);
    }
}
