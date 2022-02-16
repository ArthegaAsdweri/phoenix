<?php

namespace PhoenixPhp\Pages\Oops;

use PhoenixPhp\Core\BasePage;

class DefaultAction extends BasePage
{

    public function parseContent(): string
    {
        return '404 - not found';
    }

    public function parseVueComponents(): void
    {
    }

}