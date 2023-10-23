<?php

namespace PhoenixPhp\PageWrapper;

use PhoenixPhp\Core\BaseWrapper;
use PhoenixPhp\Core\Parser;

class PageWrapper extends BaseWrapper
{

    public function parseContent(): string
    {
        $template = new Parser($this->getTemplatePath());
        $pageContent = $this->getPageContent();
        $template->parse('PAGE_CONTENT', $pageContent);
        return $template->retrieveTemplate();
    }

    public function parseVueComponents(): void
    {

    }

}