<?php

namespace HttpExchange\Common\Helpers;

/**
 * Class MessageHelper.
 * @package HttpExchange\Common\Helpers
 */
trait MessageHelper
{
    /**
     * Normalize headers names.
     *
     * @param string $name
     * @return mixed|string
     */
    private function normalizeHeaderName($name)
    {
        $name = str_replace(['_', '-'], ' ', strtolower($name));
        $name = ucwords($name);
        $name = str_replace(' ', '-', $name);
        return $name;
    }

    /**
     * Test that an array contains only strings.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param array $array
     * @return bool
     */
    private function arrayContainsOnlyStrings(array $array)
    {
        return array_reduce(
            $array,
            // Test if a value is a string
            function($carry, $item)
            {
                if (! is_string($item)) {
                    return false;
                }
                return $carry;
            },
            true
        );
    }
}
