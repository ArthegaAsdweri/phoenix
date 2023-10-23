<?php

namespace PhoenixPhp\Pages\Test;

use PhoenixPhp\Core\BasePage;
use PhoenixPhp\Core\Parser;

class ModuleAction extends BasePage
{

    /**
     * @throws \PhoenixPhp\Core\Exception
     */
    public function parseContent(): string
    {
        $module = $this->callModule('test');
        return $this->renderModule($module);
    }

    public function parseVueComponents(): void
    {
    }

}