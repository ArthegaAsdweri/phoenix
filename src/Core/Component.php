<?php

namespace PhoenixPhp\Core;

use PhoenixPhp\Utils\StringConversion;

/**
 * This class handles conversion of Vue Single File Components to usable includes for the framework.
 */
class Component extends Parser
{

    //---- MEMBERS

    private string $vScript;
    private string $vStyle;
    private string $name;
    private bool $isChild = false;
    private bool $isMixin = false;
    private string $mixinType = 'LOCAL';


    //---- GENERAL METHODS

    /**
     * This method renders the component and fills the style and script tags for separation.
     */
    public function renderComponent(): void
    {
        $content = $this->getParsed();
        $templateArr = $scriptArr = $styleArr = [];
        preg_match('/<template>([\s\S]*)<\/template>/', $content, $templateArr);
        $template = $templateArr[1];
        preg_match('/<script>([\s\S]*)<\/script>/', $content, $scriptArr);
        $script = str_replace(['<script>', '</script>'], '', $scriptArr[1]);
        preg_match('/<style( scoped)?>([\s\S][^<]*)<\/style>/', $content, $styleArr);
        $style = trim(str_replace(['<style>', '</style>'], '', $styleArr[2]));

        $camelName = StringConversion::toCamelCase($this->getName());
        $bracket = ')';
        if ($this->isChild()) {
            $script = preg_replace('/^[\s\S]*export default {/', 'var ' . $camelName . ' = {', $script);
            $script = preg_replace('/export default {/', 'var ' . $camelName . ' = {', $script);
            $bracket = '';
        } else {
            $script = preg_replace('/^^[\s\S]*export default {\s*name:[\s]*(.*)"(,)+([\s\S]*)/', 'Vue.component($1", {$3', $script);
        }

        if (!$this->isMixin()) {
            $script = preg_replace(
                '/([\s\S]*)}/',
                '$1, template:`' . $template . '`}'.$bracket.';',
                $script
            );
        }

        $this->setVScript($script);
        $this->setVStyle($style);
    }


    //---- SETTERS AND GETTERS

    /**
     * @return string
     */
    public function getVScript(): string
    {
        return $this->vScript;
    }

    /**
     * @param string $vScript
     */
    public function setVScript(string $vScript): void
    {
        $this->vScript = $vScript;
    }

    /**
     * @return string
     */
    public function getVStyle(): string
    {
        return $this->vStyle;
    }

    /**
     * @param string $vStyle
     */
    public function setVStyle(string $vStyle): void
    {
        $this->vStyle = $vStyle;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isChild(): bool
    {
        return $this->isChild;
    }

    /**
     * @param bool $isChild
     */
    public function setIsChild(bool $isChild): void
    {
        $this->isChild = $isChild;
    }

    /**
     * @return bool
     */
    public function isMixin(): bool
    {
        return $this->isMixin;
    }

    /**
     * @param bool $isMixin
     */
    public function setIsMixin(bool $isMixin): void
    {
        $this->isMixin = $isMixin;
    }

    /**
     * @return string
     */
    public function getMixinType(): string
    {
        return $this->mixinType;
    }

    /**
     * @param string $mixinType
     */
    public function setMixinType(string $mixinType): void
    {
        $this->mixinType = $mixinType;
    }

}