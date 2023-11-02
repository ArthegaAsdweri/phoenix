<?php

namespace PhoenixPhp\Pages\Test;

use PhoenixPhp\Core\BaseAjax;

class ValidAjax extends BaseAjax
{

    public function validateTestKey(string $key)
    {
        return true;
    }

    public function run(): ?string
    {
        return 'AjaxContent';
    }

}