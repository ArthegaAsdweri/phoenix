<?php

namespace PhoenixPhp\Pages\Test;

use PhoenixPhp\Core\BasePage;
use PhoenixPhp\Core\Parser;

class VueGlobalMixinAction extends BasePage
{

    public function parseContent(): string
    {
        $tplPage = new Parser($this->getTemplatePath());
        return $tplPage->retrieveTemplate();
    }

    public function parseVueComponents(): void
    {
        $this->registerVueMixin('test-mixin', true);
    }

}