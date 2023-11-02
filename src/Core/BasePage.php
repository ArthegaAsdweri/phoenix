<?php

namespace PhoenixPhp\Core;

use PhoenixPhp\Utils\JsonEncoder;

/**
 * base class for every page
 *
 * A page is a route in your project that has its own URI. Like /newsletter, /imprint
 */
abstract class BasePage
{

    //---- MEMBERS

    protected string $page;
    protected string $calledPage;
    protected string $calledAction;
    protected ?string $calledArgument = null;
    protected string $templatePath;
    protected ?string $subTemplatePath = null;
    protected array $vueComponents = [];
    protected bool $debugMode = false;
    protected bool $noTemplate = false;

    protected string $title = 'Page Title';
    protected string $description = 'Page Description';
    protected ?string $additionalMeta = null;
    protected ?string $additionalBodyScripts = null;


    //---- ABSTRACT METHODS

    /**
     * This method renders the contents of the page.
     *
     * @return string rendered page
     */
    abstract public function parseContent(): string;

    /**
     * This method registers vue components that might be part of the page.
     */
    abstract public function parseVueComponents(): void;


    //---- GENERAL METHODS

    /**
     * This method generates the path to the actual page.
     *
     * @return array path data
     */
    public function retrievePageArray(): array
    {
        $className = get_called_class();
        $pathArray = explode('\\', $className);
        $pathCount = count($pathArray);
        $project = strtolower($pathArray[0]);
        $page = $pathArray[$pathCount - 2];
        $fileName = str_replace('Action', '', $pathArray[$pathCount - 1]);
        $action = $fileName;

        return [
            'project' => $project,
            'page' => $page,
            'action' => $action
        ];
    }

    /**
     * This method creates the path to the template and saves it inside the object.
     */
    private function generateTemplate(): void
    {
        $pageData = $this->retrievePageArray();

        if (!$this->noTemplate) {
            //main template
            $templatePath = 'Pages/' . $pageData['page'] . '/' . $pageData['action'] . '.html';
            if (!stream_resolve_include_path($templatePath)) {
                throw new Exception('The file ' . $templatePath . ' does not exist.');
            }
            $this->setTemplatePath($templatePath);

            //sub template
            $templatePath = 'Pages/' . $pageData['page'] . '/' . $pageData['action'] . 'Sub.html';
            $this->setSubTemplatePath($templatePath);
        }
    }

    /**
     * This method registers a vue component.
     *
     * @param string $componentName
     * @param bool $childComponent true if component is a child component which is not registered globally
     * @param null|string $mixin LOCAL = local Mixin, GLOBAL = global Mixin (for all components), VUE = just for the main instance
     * @return Component|null vue component or null if not found
     * @throws Exception
     */
    final protected function registerVueComponent(
        string $componentName,
        bool $childComponent = false,
        ?string $mixin = null
    ): ?Component {
        $pageArray = $this->retrievePageArray();
        $project = $pageArray['project'];
        $page = $pageArray['page'];

        if ($mixin !== null) {
            $componentPath = 'Mixins/' . $componentName . '.vue';
            $componentPath2 = __DIR__ . '/../Mixins/' . $componentName . '.vue';
        } else {
            $componentPath = 'Pages/' . $page . '/Components/' . $componentName . '.vue';
            $componentPath2 = $project . '/Pages/' . $page . '/Components/' . $componentName . '.vue';
        }

        $found = stream_resolve_include_path($componentPath);
        $found2 = stream_resolve_include_path($componentPath2);
        if (!$found) {
            if ($found2) {
                $componentPath = $componentPath2;
            }
        }

        if ($found || $found2) {
            $component = new Component($componentPath);
            $component->setName($componentName);
            $component->setIsChild($childComponent);

            if ($mixin !== null) {
                $component->setIsMixin(true);
                $component->setMixinType($mixin);
            }
            $components = $this->getVueComponents();
            $components[] = &$component;
            $this->setVueComponents($components);
            return $component;
        }

        $logger = new Logger();
        $logger->warning('The vue component "' . $componentPath . '" was not found.');
        return null;
    }

    /**
     * This method registers a vue mixin.
     *
     * @param string $componentName
     * @param bool $global true: mixin will be accessible for all components, false: mixin must be included manually
     * @return Component|null component or null if not valid
     * @throws Exception
     */
    final protected function registerVueMixin(string $componentName, bool $global = false): ?Component
    {
        $mixin = ($global) ? 'GLOBAL' : 'LOCAL';
        return $this->registerVueComponent($componentName, true, $mixin);
    }

    /**
     * This method registers a mixin which is only used for the main instance.
     *
     * @param string $componentName
     * @return Component|null component or null if not valid
     * @throws Exception
     */
    final protected function registerMainMixin(string $componentName): ?Component
    {
        return $this->registerVueComponent($componentName, true, 'VUE');
    }

    /**
     * This method registers a CSS file.
     *
     * @param string $path path to the file
     * @param bool $prepend true: prepend the file to the list, false: append it to the list
     * @throws Exception
     */
    final protected function registerCss(string $path, bool $prepend = false): void
    {
        $assets = AssetHandler::getInstance();
        $assets->registerCss($path, $prepend);
    }

    /**
     * This method registers a JS file.
     *
     * @param string $path path to the file
     * @throws Exception
     */
    final protected function registerJs(string $path): void
    {
        $assets = AssetHandler::getInstance();
        $assets->registerJs($path);
    }

    /**
     * This method registers an external JS file.
     *
     * @param string $path path to the file
     */
    final protected function registerExternalJs(string $path): void
    {
        $assets = AssetHandler::getInstance();
        $assets->registerExternalJs($path);
    }

    /**
     * This method registers inline JS code.
     *
     * @param string $code the code that needs to run
     */
    final protected function registerInlineJs(string $code): void
    {
        $assets = AssetHandler::getInstance();
        $assets->registerInlineJs($code);
    }

    /**
     * This method returns the contents of the page and its corresponding action.
     *
     * @return string rendered contents of the page
     * @throws Exception
     */
    public function render(): string
    {
        $this->generateTemplate();
        $pageContent = $this->parseContent();
        $this->parseVueComponents();
        return $pageContent;
    }

    /**
     * This method chains a module to the page.
     *
     * @param string $moduleName
     * @param string $action action that will be called inside the module
     * @param bool $global true: global module, false: local module inside the project
     * @return BaseModule called module
     */
    final protected function callModule(
        string $moduleName,
        string $action = 'default',
        bool $global = false
    ): BaseModule {
        $moduleName = ucfirst(strtolower($moduleName));
        $ucAction = ucfirst($action);
        $className = 'PhoenixPhp\Modules\\' . $moduleName . '\\' . $ucAction . 'Action';

        if ($global === false) {
            $className = PHPHP_PSR_NAMESPACE . '\Modules\\' . $moduleName . '\\' . $ucAction . 'Action';
        }
        //FIXME - registering CSS in modules via asset handler

        return new $className();
    }

    /**
     * wrapper method for global modules
     */
    final protected function callGlobalModule(string $moduleName, string $action = "default"): BaseModule
    {
        return $this->callModule($moduleName, $action, true);
    }

    /**
     * This method renders the module and saves all properties needed for the page - like components and assets, so they
     * can be rendered by the controller.
     *
     * @param BaseModule $module the module instance that needs to be rendered
     * @return string the rendered module content
     */
    final protected function renderModule(BaseModule $module): string
    {
        $moduleArray = $module->render();

        if ($moduleArray['vueComponents'] !== null) {
            $components = $moduleArray['vueComponents'];
            $pageComponents = $this->getVueComponents();
            $mergedComponents = array_merge($pageComponents, $components);
            $this->setVueComponents($mergedComponents);
        }

        return $moduleArray['moduleContent'];
    }

    /**
     * This method converts a string into a usable encoded format for HTML tags. For example to pass data to vue components.
     *
     * @param array $dataArray
     * @return string
     */
    final protected function returnJsonForHtml(array $dataArray): string
    {
        return JsonEncoder::encodeForHtml($dataArray);
    }


    //---- SETTERS AND GETTERS

    /**
     * @return string
     */
    public function getPage(): string
    {
        return $this->page;
    }

    /**
     * @param string $page
     */
    public function setPage(string $page): void
    {
        $this->page = $page;
    }

    /**
     * @return string
     */
    public function getCalledPage(): string
    {
        return $this->calledPage;
    }

    /**
     * @param string $calledPage
     */
    public function setCalledPage(string $calledPage): void
    {
        $this->calledPage = $calledPage;
    }

    /**
     * @return string
     */
    public function getCalledAction(): string
    {
        return $this->calledAction;
    }

    /**
     * @param string $calledAction
     */
    public function setCalledAction(string $calledAction): void
    {
        $this->calledAction = $calledAction;
    }

    /**
     * @return string|null
     */
    public function getCalledArgument(): ?string
    {
        return $this->calledArgument;
    }

    /**
     * @param string|null $calledArgument
     */
    public function setCalledArgument(?string $calledArgument): void
    {
        $this->calledArgument = $calledArgument;
    }

    /**
     * @return string
     */
    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * @param string $templatePath
     */
    public function setTemplatePath(string $templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    /**
     * @return string|null
     */
    public function getSubTemplatePath(): ?string
    {
        return $this->subTemplatePath;
    }

    /**
     * @param string|null $subTemplatePath
     */
    public function setSubTemplatePath(?string $subTemplatePath): void
    {
        $this->subTemplatePath = $subTemplatePath;
    }

    /**
     * @return array
     */
    public function getVueComponents(): array
    {
        return $this->vueComponents;
    }

    /**
     * @param array $vueComponents
     */
    public function setVueComponents(array $vueComponents): void
    {
        $this->vueComponents = $vueComponents;
    }

    /**
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * @param bool $debugMode
     */
    public function setDebugMode(bool $debugMode): void
    {
        $this->debugMode = $debugMode;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getAdditionalMeta(): ?string
    {
        return $this->additionalMeta;
    }

    /**
     * @param string|null $additionalMeta
     */
    public function setAdditionalMeta(?string $additionalMeta): void
    {
        $this->additionalMeta = $additionalMeta;
    }

    /**
     * @return string|null
     */
    public function getAdditionalBodyScripts(): ?string
    {
        return $this->additionalBodyScripts;
    }

    /**
     * @param string|null $additionalBodyScripts
     */
    public function setAdditionalBodyScripts(?string $additionalBodyScripts): void
    {
        $this->additionalBodyScripts = $additionalBodyScripts;
    }

}