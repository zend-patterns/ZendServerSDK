<?php

namespace Client;

use Zend\Stdlib\ArrayUtils;

abstract class Utils
{
    /**
     * Replacement of the parse_str built-in function.
     * This one does not replace dots and spaces in key name with underscores.
     *
     * @param string $string
     * @param array  $data
     */
    public static function parseString($string, &$data, $delimiter = '&')
    {
        // check if the values are provided like a query string
        $string = str_replace('&amp;', chr(0x7f) . chr(0xff) . chr(0x7f), $string); // escaped ampersand
        $pairs = explode($delimiter, $string);
        foreach ($pairs as $pair) {
            list($k, $v) = explode('=', $pair);
            $v = str_replace(chr(0x7f) . chr(0xff) . chr(0x7f), '&', $v); // replace escaped ampersand
            if (preg_match("/^(.*?)((\[(.*?)\])+)$/m", $k, $m)) {
                $parts = explode('][', rtrim(ltrim($m[2], '['), ']'));
                $json = '{"'.implode('":{"', $parts).'": '.json_encode($v).str_pad('', count($parts), '}');
                if (!isset($data[$m[1]])) {
                    $data[$m[1]] = json_decode($json, true);
                } else {
                    $data[$m[1]] = ArrayUtils::merge($data[$m[1]], json_decode($json, true));
                }
            } else {
                $data[$k] = $v;
            }
        }
    }

    /**
     * Represents an array as key value pairs.
     *
     * @param array  $items
     * @param string $nested
     * @param string $prefix
     *
     * @return string
     */
    public static function array2KV(array $items, $nested = false, $prefix = '')
    {
        $output = '';
        foreach ($items as $k => $v) {
            if ($nested) {
                $k = "[{$k}]";
            }
            $k = $prefix.$k;
            if (is_scalar($v)) {
                $output .= "$k=$v\n";
            } elseif (is_array($v)) {
                foreach ($v as $k1 => $v1) {
                    if (is_scalar($v1)) {
                        $output .= $k.'['.$k1."]=$v1\n";
                    } elseif (is_array($v1)) {
                        $output .= self::array2KV($v1, true, $k.'['.$k1.']');
                    }
                }
            }
        }

        return $output;
    }
}
