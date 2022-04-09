<?php

namespace PhoenixPhp\Core;

use PhoenixPhp\Utils\StringConversion;

/**
 * handles the sfc vue components
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
     * renders the component and fills the style and script tags for separation
     */
    public function renderComponent()
    {
        $content = $this->getParsed();
        $templateArr = [];
        preg_match('/<template>([\s\S]*)<\/template>([\s\S]*)/', $content, $templateArr);
        $template = $templateArr[1];
        $scriptArr = explode('</script>', $templateArr[2]);
        $script = str_replace('<script>', '', $scriptArr[0] . '</script>');

        $camelName = StringConversion::toCamelCase($this->getName());
        if ($this->isChild()) {
            $script = preg_replace('/^[\s\S]*export default {\s*name: "(.*)",/', 'var ' . $camelName . ' = {', $script);
            $script = preg_replace('/export default {\s*name: "(.*)",/', 'var ' . $camelName . ' = {', $script);
            $bracket = '';
        } else {
            $snakeName = str_replace('_', '-', StringConversion::toSnakeCase($this->getName()));
            $script = preg_replace('/^[\s\S]*export default {\s*name: /', 'Vue.component(', $script);
            $script = preg_replace('/export default {\s*name: /', 'Vue.component(', $script);
            $script = str_replace(
                'Vue.component("' . $camelName . '",',
                'Vue.component("' . $camelName . '", {',
                $script
            );
            $bracket = ')';
        }

        if (!$this->isMixin()) {
            $script = preg_replace(
                '/(\s*)};*(\s*)<\/script>/',
                ',' . PHP_EOL . "\t" . 'template:`' . $template . '`' . PHP_EOL . '}' . $bracket . ';',
                $script
            );
        } else {
            $script = preg_replace('/(\s*)};*(\s*)<\/script>/', PHP_EOL . "\t" . '}' . $bracket . ';', $script);
        }

        $style = str_replace(['<style>', '<style scoped>', '</style>'], '', $scriptArr[1]);

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