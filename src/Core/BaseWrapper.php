<?php

namespace PhoenixPhp\Core;

use PhoenixPhp\Utils\StringConversion;

/**
 * wrapper for a whole set of pages
 */
abstract class BaseWrapper extends BasePage
{

    //---- MEMBERS

    private string $pageContent;
    protected string $title;


    //---- ABSTRACT METHODS

    abstract function parseContent(): string;

    abstract function parseVueComponents(): void;


    //---- GENERAL METHODS

    /**
     * generates the path to the template and stores it inside the object
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

    /**
     * calls the page contents and converts them into the object for later use
     * @param BasePage $pageContent
     */
    final public function renderPage($pageContent)
    {
        $this->setPageContent($pageContent->render());
        $this->setTitle($pageContent->getTitle());
        $this->setDescription($pageContent->getDescription());
        $this->setVueComponents($pageContent->getVueComponents());
        $this->setCssFiles($pageContent->getCssFiles());
        $this->setJsFiles($pageContent->getJsFiles());
        $this->setExternalJsFiles($pageContent->getExternalJsFiles());
        $this->setInlineJs($pageContent->getInlineJs());
        $this->setCalledPage($pageContent->getCalledPage());
        $this->setCalledAction($pageContent->getCalledAction());
    }

    /**
     * returns the page contents inside the wrapper
     * @return string
     */
    public function render(): string
    {
        $tplIndex = new Parser(__DIR__ . '/../Index.html');
        $this->generateTemplate();
        $wrapperContent = $this->parseContent();

        //loop through the vue components
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

        /*developer console
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
        */

        if (count($globalMixins) > 0) {
            $tplIndex->parse('VUE_MIXIN', PHP_EOL . implode(PHP_EOL, $globalMixins));
        }

        if (count($mainMixins) > 0) {
            $tplIndex->parse('MAIN_MIXINS', ',' . PHP_EOL . 'mixins: [' . implode(PHP_EOL, $mainMixins) . ']');
        }

        //render includes
        $cssString = $this->renderCss();
        $jsString = $this->renderJs();
        $jsExString = $this->renderExternalJs();
        $jsInline = $this->getInlineJs();

        $tplIndex->parse('CSS_INCLUDES', $cssString);
        $tplIndex->parse('JS_INCLUDES', $jsString);
        $tplIndex->parse('VUE_COMPONENTS', PHP_EOL . $vueString);
        $tplIndex->parse('EXTERNAL_JS', $jsExString);
        $tplIndex->parse('JS_INLINE', $jsInline);

        if (getenv('DEVELOPER')) {
            $tplIndex->parse('VUE_DEVELOPMENT_URL', '/dist/vue.js');
            $tplIndex->parse('VUE_DEVELOPMENT', 'Vue.config.devtools = true;');
        }

        if (defined('PHPHP_VUETIFY') && PHPHP_VUETIFY === true) {
            $vuetifyJs = new Parser(__DIR__ . '/../IndexSub.html', 'VUETIFY_JS');
            $vuetifyCss = new Parser(__DIR__ . '/../IndexSub.html', 'VUETIFY_CSS');
            $tplIndex->parse('VUETIFY_JS', $vuetifyJs->retrieveTemplate());
            $tplIndex->parse('VUETIFY_CSS', $vuetifyCss->retrieveTemplate());

            $vuetifyTheme = '';
            if (defined('PHPHP_VUETIFY_THEME')) {
                $vuetifyTheme = '
                    theme: {
                        ' . PHPHP_VUETIFY_THEME . ':true
                    }
                ';
            }

            $tplIndex->parse(
                'VUETIFY_INIT',
                '
                , vuetify: new Vuetify({' . $vuetifyTheme . '})
            '
            );
        }

        if (defined('PHPHP_AXIOS') && PHPHP_AXIOS === true) {
            $axiosJs = new Parser(__DIR__ . '/../IndexSub.html', 'AXIOS_JS');
            $tplIndex->parse('AXIOS_JS', $axiosJs->retrieveTemplate());
        }

        $language = 'en-EN';
        if (defined('PHPHP_LANGUAGE')) {
            $language = PHPHP_LANGUAGE;
        }

        $tplIndex->parse('LANGUAGE', $language);
        $tplIndex->parse('TITLE', $this->getTitle());
        $tplIndex->parse('DESCRIPTION', $this->getDescription());

        if ($this->getAdditionalMeta() !== null) {
            $tplIndex->parse('ADDITIONAL_META', $this->getAdditionalMeta());
        }

        if (defined('PHPHP_GOOGLE')) {
            $google = PHPHP_GOOGLE;
            if (isset($google['ANALYTICS']) && $google['ANALYTICS'] === true) {
                if (isset($google['ANALYTICS_ID'])) {
                    $googleAnalytics = new Parser(__DIR__ . '/../IndexSub.html', 'GOOGLE_ANALYTICS');
                    $googleAnalytics->parse('GOOGLE_ANALYTICS_ID', $google['ANALYTICS_ID']);
                    $tplIndex->parse('GOOGLE_ANALYTICS', $googleAnalytics->retrieveTemplate());
                }
            }
        }

        $canonical = new Parser(__DIR__ . '/../IndexSub.html', 'CANONICAL');
        if (defined('PHPHP_DOMAIN')) {
            $domain = PHPHP_DOMAIN;
            if($this->getCalledPage() !== null && $this->getCalledPage() !== 'home') {
                $domain .= '/'.$this->getCalledPage();
            }
            if($this->getCalledAction() !== null && $this->getCalledAction() !== 'default') {
                $domain .= '/'.$this->getCalledAction();
            }
            $canonical->parse('CANONICAL_URL', 'https://www.' .$domain);
            $tplIndex->parse('CANONICAL', $canonical->retrieveTemplate());
        }

        $tplIndex->parse('WRAPPER_CONTENT', $wrapperContent);
        $tplIndex->parse('ADDITIONAL_BODY_SCRIPTS', $this->getAdditionalBodyScripts());

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