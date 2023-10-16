<?php

namespace PhoenixPhp\Modules\Test;

use PhoenixPhp\Core\BaseModule;
use PhoenixPhp\Core\Parser;

class DefaultAction extends BaseModule
{

    public function parseContent(): string
    {
        $template = new Parser($this->getTemplatePath());
        return $template->retrieveTemplate();
    }

    public function parseVueComponents(): void
    {
        $this->registerVueComponent('module-test');
    }

}