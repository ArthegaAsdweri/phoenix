<?php

namespace PhoenixPhp\Pages\Test;

use PhoenixPhp\Core\BasePage;
use PhoenixPhp\Core\Parser;

class InvalidJsAction extends BasePage
{

    /**
     * @throws \PhoenixPhp\Core\Exception
     */
    public function parseContent(): string
    {
        $this->registerJs('Invalid.js');
        $tplPage = new Parser($this->getTemplatePath());
        return $tplPage->retrieveTemplate();
    }

    public function parseVueComponents(): void
    {
    }

}