<?php

namespace PhoenixPhp\Pages\Test;

use PhoenixPhp\Core\BasePage;
use PhoenixPhp\Core\Parser;

class VueMainMixinAction extends BasePage
{

    /**
     * @throws \PhoenixPhp\Core\Exception
     */
    public function parseContent(): string
    {
        $tplPage = new Parser($this->getTemplatePath());
        return $tplPage->retrieveTemplate();
    }

    public function parseVueComponents(): void
    {
        $this->registerMainMixin('test-mixin');
    }

}