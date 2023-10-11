<?php

namespace PhoenixPhp\Utils;

class JsonEncoder
{

    //---- GENERAL METHODS

    /**
     * This method encodes an array to be used as HTML property for vue components.
     *
     * @param array $dataArray
     * @return string encoded HTML json
     */
    public static function encodeForHtml(array $dataArray): string
    {
        return htmlentities(json_encode($dataArray, JSON_HEX_QUOT), ENT_QUOTES);
    }
}