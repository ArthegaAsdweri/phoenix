<?php

namespace PhoenixPhp\Pages\Test;

use PhoenixPhp\Core\BasePage;

class DoubleIncludeAction extends BasePage
{

    public function parseContent(): string
    {
        $this->registerCss('_Specimen/Resources/Css/Test.css');
        $this->registerCss('_Specimen/Resources/Css/Test.css');
        $this->registerJs('_Specimen/Resources/Js/Test.js');
        $this->registerJs('_Specimen/Resources/Js/Test.js');
        $tplPage = new \PhoenixPhp\Core\Parser($this->getTemplatePath());
        return $tplPage->retrieveTemplate();
    }

    public function parseVueComponents(): void
    {
    }

}