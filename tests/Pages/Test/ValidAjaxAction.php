<?php

namespace PhoenixPhp\Pages\Test;

use PhoenixPhp\Core\BaseAjax;

class ValidAjaxAction extends BaseAjax
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