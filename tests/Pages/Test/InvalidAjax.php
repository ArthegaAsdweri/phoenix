<?php

namespace PhoenixPhp\Pages\Test;

use PhoenixPhp\Core\BaseAjax;

class InvalidAjax extends BaseAjax
{

    public function validateKey(): bool
    {
        return false;
    }

    public function run(): ?string
    {
        return null;
    }

}