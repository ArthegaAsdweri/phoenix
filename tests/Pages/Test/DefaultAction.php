<?php

namespace PhoenixPhp\Pages\Test;

use PhoenixPhp\Core\BasePage;
use PhoenixPhp\Core\Parser;

class DefaultAction extends BasePage
{

    /**
     * @throws \PhoenixPhp\Core\Exception
     */
    public function parseContent(): string
    {
        $this->registerCss('_Specimen/Resources/Css/Test.css');
        $this->registerJs('_Specimen/Resources/Js/Test.js');
        $tplPage = new Parser($this->getTemplatePath());
        return $tplPage->retrieveTemplate();
    }

    public function parseVueComponents(): void
    {
    }

}