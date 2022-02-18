<?php

namespace PhoenixPhp\Core;

/**
 * wrapper for a whole set of pages
 */
abstract class BaseWrapper extends BasePage
{

    //---- MEMBERS

    private string $pageContent;


    //---- ABSTRACT METHODS

    abstract function parseContent(): string;

    abstract function parseVueComponents(): void;


    //---- GENERAL METHODS

    /**
     * Diese Methode generiert den Pfad zum Template und speichert ihn abrufbar im Objekt
     */
    public function generateTemplate(): void
    {
        //main-template
        $templatePath = 'PageWrapper/PageWrapper.html';
        if (!stream_resolve_include_path($templatePath)) {
            throw new Exception('file ' . $templatePath . ' does not exist.');
        }
        $this->setTemplatePath($templatePath);

        //sub-template
        $templatePath = 'PageWrapper/PageWrapperSub.html';
        $this->setSubTemplatePath($templatePath);
    }

    final public function renderPage($pageContent)
    {
        $this->setPageContent($pageContent->render());
        $this->setVueComponents($pageContent->getVueComponents());
        $this->setCssFiles($pageContent->getCssFiles());
        $this->setJsFiles($pageContent->getJsFiles());
        $this->setExternalJsFiles($pageContent->getExternalJsFiles());
        $this->setInlineJs($pageContent->getInlineJs());
    }

    /**
     * Diese Methode gibt den Inhalt der Aktion zurück.
     *
     * @return string    Der Inhalt der Seite
     */
    public function render(): string
    {
        $tplIndex = new Parser(__DIR__ . '/../Index.html');
        $this->generateTemplate();
        $wrapperContent = $this->parseContent();

        //Jetzt die Vue-Components durchlaufen
        $this->parseVueComponents();
        $components = $this->getVueComponents();

        $globalMixins = [];
        $mainMixins = [];

        $vueString = '';
        foreach ($components as $component) {
            $component->renderComponent();
            if ($component->getMixinType() === 'GLOBAL') {
                $camelMixin = StringConversion::toCamelCase($component->getName());
                $globalMixins[] = 'Vue.mixin(' . $camelMixin . ');';
            } else {
                if ($component->getMixinType() === 'VUE') {
                    $mainMixins[] = StringConversion::toCamelCase($component->getName());
                }
            }
            $vueString .= $component->getVScript();
        }

        $componentCount = count($components);

        //Developer Konsole
        if (getenv('DEVELOPER')) {
            $debugConsole = $this->callGlobalModule('debugger');
            $debugConsole->setDebugMode($this->getDebugMode());
            $moduleContent = $this->renderModule($debugConsole);
            $tplIndex->parse('DEBUG_CONTENT', $moduleContent);

            //Fixme - Das hier mal besser machen
            $components = $this->getVueComponents();
            $count = 0;
            foreach ($components as $component) {
                $count++;
                if ($count <= $componentCount) {
                    continue;
                }
                $component->renderComponent();
                $vueString .= $component->getVScript();
            }
        }

        if (count($globalMixins) > 0) {
            $tplIndex->parse('VUE_MIXIN', implode(PHP_EOL, $globalMixins));
        }

        if (count($mainMixins) > 0) {
            $tplIndex->parse('MAIN_MIXINS', ',' . PHP_EOL . 'mixins: [' . implode(PHP_EOL, $mainMixins) . ']');
        }

        //dann Includes rendern
        $cssString = $this->renderCss();
        $jsString = $this->renderJs();
        $jsExString = $this->renderExternalJs();
        $jsInline = $this->getInlineJs();

        $tplIndex->parse('CSS_INCLUDES', $cssString);
        $tplIndex->parse('JS_INCLUDES', $jsString);
        $tplIndex->parse('VUE_COMPONENTS', $vueString);
        $tplIndex->parse('EXTERNAL_JS', $jsExString);
        $tplIndex->parse('JS_INLINE', $jsInline);

        if (getenv('DEVELOPER')) {
            $tplIndex->parse('VUE_DEVELOPMENT_URL', '/dist/vue.js');
            $tplIndex->parse('VUE_DEVELOPMENT', 'Vue.config.devtools = true;');
        }

        if (defined('VUETIFY') && VUETIFY === true) {
            $vuetifyJs = new Parser('tpl_index_sub.html', 'VUETIFY_JS');
            $vuetifyCss = new Parser('tpl_index_sub.html', 'VUETIFY_CSS');
            $tplIndex->parse('VUETIFY_JS', $vuetifyJs->retrieveTemplate());
            $tplIndex->parse('VUETIFY_CSS', $vuetifyCss->retrieveTemplate());

            $vuetifyTheme = '';
            if (defined('VUETIFY_THEME')) {
                $vuetifyTheme = '
                    theme: {
                    ' . VUETIFY_THEME . ':true
                    }
                ';
            }

            $tplIndex->parse(
                'VUETIFY_INIT',
                ',
                vuetify: new Vuetify({' . $vuetifyTheme . '})
            '
            );
        }

        if (defined('AXIOS') && AXIOS === true) {
            $axiosJs = new Parser('tpl_index_sub.html', 'AXIOS_JS');
            $tplIndex->parse('AXIOS_JS', $axiosJs->retrieveTemplate());
        }

        $tplIndex->parse('WRAPPER_CONTENT', $wrapperContent);

        return $tplIndex->retrieveTemplate();
    }


    //---- SETTERS AND GETTERS

    private function setPageContent(string $val): void
    {
        $this->pageContent = $val;
    }

    protected function getPageContent(): string
    {
        return $this->pageContent;
    }
}