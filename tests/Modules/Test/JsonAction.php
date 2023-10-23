<?php

namespace PhoenixPhp\Modules\Test;

use PhoenixPhp\Core\BaseModule;
use PhoenixPhp\Core\Parser;

class JsonAction extends BaseModule
{

    public function parseContent(): string
    {
        return $this->returnJsonForHtml(['key' => 'value']);
    }

    public function parseVueComponents(): void
    {
    }

}