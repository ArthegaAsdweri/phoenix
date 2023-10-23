<?php

namespace PhoenixPhp\Pages\Test;

use PhoenixPhp\Core\BasePage;

class JsonAction extends BasePage
{
    protected bool $noTemplate = true;

    /**
     * @throws \PhoenixPhp\Core\Exception
     */
    public function parseContent(): string
    {
        return $this->returnJsonForHtml(['key' => 'value']);
    }

    public function parseVueComponents(): void
    {
    }

}