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
     * @param array $data
     */
    public static function parseString($string, &$data)
    {
        // check if the values is provided like a query string
        $pairs = explode('&', $string);
        foreach ($pairs as $pair) {
            list($k, $v) = explode('=', $pair);

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
}
