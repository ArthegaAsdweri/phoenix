<?php

namespace PhoenixPhp\Utils;

class StringConversion
{

    /**
     * converts a string from snake_case to camelCase
     * @param string $string snake_cased string
     * @return string $string camelCased string
     */
    public static function toCamelCase(string $string): string
    {
        if (stristr($string, '_')) {
            $tmpString = '';
            $tmpParts = explode('_', $string);
            foreach ($tmpParts as $part) {
                $tmpString .= ucfirst($part);
            }
            $string = lcfirst($tmpString);
        } elseif (stristr($string, '-')) {
            $tmpString = '';
            $tmpParts = explode('-', $string);
            foreach ($tmpParts as $part) {
                $tmpString .= ucfirst($part);
            }
            $string = lcfirst($tmpString);
        }
        return $string;
    }

    /**
     * converts a string from camelCase to snake_case
     * @param string $string camelCased string
     * @return string $string snake_cased string
     */
    public
    static function toSnakeCase(
        string $string
    ): string {
        if (!stristr($string, '_')) {
            $string = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
        }
        return $string;
    }
}