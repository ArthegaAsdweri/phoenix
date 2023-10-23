<?php

namespace PhoenixPhp\Core;

use PhoenixPhp\Utils\StringConversion;

/**
 * Main Page Wrapper that all pages share. (For example header, footer, sidebar, ...)
 */
abstract class BaseWrapper extends BasePage
{

    //---- MEMBERS

    private string $pageContent;


    //---- GENERAL METHODS

    /**
     * This method generates the path to the template and stores it inside the object.
     */
    private function generateTemplate(): void
    {
        //main template
        $templatePath = 'PageWrapper/PageWrapper.html';
        if (!stream_resolve_include_path($templatePath)) {
            //@codeCoverageIgnoreStart
            throw new Exception('file "' . $templatePath . '" does not exist.');
            //@codeCoverageIgnoreEnd
        }
        $this->setTemplatePath($templatePath);

        //sub template
        $templatePath = 'PageWrapper/PageWrapperSub.html';
        $this->setSubTemplatePath($templatePath);
    }

    /**
     * This method calls the page contents and converts them into the object for later use.
     *
     * @param BasePage $pageContent
     * @throws Exception
     */
    final public function renderPage($pageContent): void
    {
        $this->setPageContent($pageContent->render());
        $this->setTitle($pageContent->getTitle());
        $this->setDescription($pageContent->getDescription());
        $this->setVueComponents($pageContent->getVueComponents());
        $this->setCalledPage($pageContent->getCalledPage());
        $this->setCalledAction($pageContent->getCalledAction());
    }

    /**
     * This method renders the pages content and puts it into the wrapper.
     *
     * @return string
     * @throws Exception
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
            } elseif ($component->getMixinType() === 'VUE') {
                $mainMixins[] = StringConversion::toCamelCase($component->getName());
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

            //FIXME - do this properly
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
            $tplIndex->parse(
                'VUE_MIXIN',
                '
            ' . implode(PHP_EOL, $globalMixins)
            );
        }

        if (count($mainMixins) > 0) {
            $tplIndex->parse(
                'MAIN_MIXINS',
                '
            mixins: [' . implode(PHP_EOL, $mainMixins) . '],'
            );
        }

        //render includes
        $assetHandler = AssetHandler::getInstance();
        $cssString = $assetHandler->renderCss($components);

        $jsExString = $assetHandler->renderExternalJs();
        $jsInline = $assetHandler->getInlineJs();

        $tplIndex->parse('CSS_INCLUDES', $cssString);
        $tplIndex->parse(
            'VUE_COMPONENTS',
            '
        ' . $vueString
        );
        $tplIndex->parse('EXTERNAL_JS', $jsExString);
        $tplIndex->parse(
            'JS_INLINE',
            '
        ' . $jsInline
        );

        //render JS
        if (count($assetHandler->getJsFiles()) > 0) {
            $jsString = $assetHandler->renderJs();
            $tplIndex->parse('INTERNAL_JS', '<script src="/cache/' . $jsString . '.js"></script>');
        }

        //render vue dev mode
        if (getenv('DEVELOPER')) {
            //@codeCoverageIgnoreStart
            $tplIndex->parse('VUE_DEVELOPMENT_URL', '/dist/vue.js');
            $tplIndex->parse('VUE_DEVELOPMENT', PHP_EOL . 'Vue.config.devtools = true;');
            //@codeCoverageIgnoreEnd
        }

        //render vuetify
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

        if (defined('PHPHP_GOOGLE') && !getenv('DEVELOPER')) {
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
            if ($this->getCalledPage() !== null && $this->getCalledPage() !== 'home') {
                $domain .= '/' . $this->getCalledPage();
            }
            if ($this->getCalledAction() !== null && $this->getCalledAction() !== 'default') {
                $domain .= '/' . $this->getCalledAction();
            }
            $canonical->parse('CANONICAL_URL', 'https://www.' . $domain);
            $tplIndex->parse('CANONICAL', $canonical->retrieveTemplate());
        }

        if (defined('PHPHP_COOKIEBOT_ID') && !getenv('DEVELOPER')) {
            $cookieBot = new Parser(__DIR__ . '/../IndexSub.html', 'COOKIEBOT');
            $cookieBot->parse('COOKIEBOT_ID', PHPHP_COOKIEBOT_ID);
            $tplIndex->parse('COOKIEBOT', $cookieBot->retrieveTemplate());
        }

        if (defined('PHPHP_META')) {
            $meta = PHPHP_META;
            $author = '';
            $copyright = '';
            $publisher = '';
            if (isset($meta['AUTHOR'])) {
                $author = $meta['AUTHOR'];
            }
            if (isset($meta['COPYRIGHT'])) {
                $copyright = $meta['COPYRIGHT'];
            }
            if (isset($meta['PUBLISHER'])) {
                $publisher = $meta['PUBLISHER'];
            }
            $tplIndex->parse('AUTHOR', $author);
            $tplIndex->parse('COPYRIGHT', $copyright);
            $tplIndex->parse('PUBLISHER', $publisher);
        }

        $tplIndex->parse('WRAPPER_CONTENT', $wrapperContent);

        if ($this->getAdditionalBodyScripts() !== null) {
            $tplIndex->parse('ADDITIONAL_BODY_SCRIPTS', $this->getAdditionalBodyScripts());
        }

        if (defined('PHPHP_VERSION')) {
            $tplIndex->parse('VERSION', '?' . PHPHP_VERSION);
        }

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