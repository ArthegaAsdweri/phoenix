<?php

namespace PhoenixPhp\phoenixphp\Modules\Test;

use PhoenixPhp\Core\BaseModule;
use PhoenixPhp\Core\Parser;

class DefaultAction extends BaseModule
{

    public function parseContent(): string
    {
        $template = new Parser($this->getTemplatePath());
        $subTemplate = new Parser($this->getSubTemplatePath(), 'TEST');
        $template->parse('SUB', $subTemplate->retrieveTemplate());
        return $template->retrieveTemplate();
    }

    public function parseVueComponents(): void
    {
        $this->registerVueComponent('module-test2');
    }

}