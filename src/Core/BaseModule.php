<?php

namespace PhoenixPhp\Core;

use PhoenixPhp\Utils\JsonEncoder;

/**
 * Base class for every module
 *
 * Modules are reusable components of logic (like a cart, a login, a navigation, ...) that are used among multiple pages.
 */
abstract class BaseModule
{

    //---- MEMBERS

    protected string $templatePath;
    protected ?string $subTemplatePath = null;
    protected ?array $vueComponents = null;


    //---- ABSTRACT METHODS

    /**
     * This method renders the contents of the module as soon as it's being called as part of a page.
     *
     * @return string rendered module
     */
    abstract public function parseContent(): string;

    /**
     * This method registers vue components that might be part of the module.
     */
    abstract public function parseVueComponents(): void;


    //---- COMMON METHODS

    /**
     * This method generates the path to the actual module.
     *
     * @return array path data
     */
    public function retrieveModuleArray(): array
    {
        $className = get_called_class();
        $pathArray = explode('\\', $className);
        $project = strtolower($pathArray[1]);
        $module = strtolower($pathArray[3]);
        $action = (isset($pathArray[4])) ? strtolower(str_replace('Action', '', $pathArray[4])) : '';

        //global modules are not part of the project
        if ($project === 'modules') {
            $project = '';
            $module = strtolower($pathArray[2]);
            $action = strtolower(str_replace('Action', '', $pathArray[3]));
        } else {
            $project .= '/';
        }

        return [
            'project' => $project,
            'module' => $module,
            'action' => $action
        ];
    }

    /**
     * This method creates the path to the template and saves it inside the object.
     */
    final public function generateTemplate(): void
    {
        $moduleData = $this->retrieveModuleArray();

        //FIXME: recover ability to use templates outside of projects (for global modules)

        $module = ucfirst(strtolower($moduleData['module']));
        $action = ucfirst(strtolower($moduleData['action']));

        //main template
        $templatePath = 'Modules/' . $module . '/' . $action . '.html';
        if (!stream_resolve_include_path($templatePath)) {
            $templatePathGlobal = $moduleData['project'] . 'Modules/' . $module . '/' . $action . '.html';
            if (!stream_resolve_include_path($templatePathGlobal)) {
                $logger = new Logger();
                $logger->warning(
                    'The file ' . $templatePath . ' does not exist. Unfortunately there\'s also no global variant: ' . $templatePathGlobal
                );
                //FIXME: use a global "not found" module here
                $templatePathGlobal = $moduleData['project'] . 'Modules/Notfound/Default.html';
            }
            $templatePath = $templatePathGlobal;
        }
        $this->setTemplatePath($templatePath);

        //sub template
        $templatePath = 'Modules/' . $module . '/' . $action . '_sub.html';
        if (!stream_resolve_include_path($templatePath)) {
            $templatePath = $moduleData['project'] . 'Modules/' . $module . '/' . $action . '_sub.html';
            $templatePath = stream_resolve_include_path($templatePath);
        }

        if ($templatePath) {
            $this->setSubTemplatePath($templatePath);
        }
    }

    /**
     * This method registers a vue component.
     *
     * @param string $componentName name of the component
     * @param bool $childComponent true if component is a child component which is not registered globally
     * @return Component|null vue component or null if not found
     * @throws Exception
     */
    final protected function registerVueComponent(string $componentName, bool $childComponent = false): ?Component
    {
        $moduleArray = $this->retrieveModuleArray();
        $project = $moduleArray['project'];
        $module = ucfirst(strtolower($moduleArray['module']));
        $componentPath = 'Modules/' . $module . '/Components/' . $componentName . '.vue';
        $componentPath2 = $project . 'Modules/' . $module . '/Components/' . $componentName . '.vue';

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
            $components = $this->getVueComponents();
            $components[] = &$component;
            $this->setVueComponents($components);
            return $component;
        }

        $logger = new Logger();
        $logger->warning('Die Vue Komponente "' . $componentPath . '" wurde nicht gefunden.');
        return null;
    }

    /**
     * This method returns the contents of the module.
     *
     * @return array contents of the module itself and its vue components
     */
    public function render(): array
    {
        $this->generateTemplate();
        $this->parseVueComponents();

        return [
            'moduleContent' => $this->parseContent(),
            'vueComponents' => $this->getVueComponents()
        ];
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

    protected function setTemplatePath(string $val): void
    {
        $this->templatePath = $val;
    }

    protected function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    protected function setSubTemplatePath(?string $val): void
    {
        $this->subTemplatePath = $val;
    }

    protected function getSubTemplatePath(): ?string
    {
        return $this->subTemplatePath;
    }

    protected function setVueComponents(?array $val): void
    {
        $this->vueComponents = $val;
    }

    protected function getVueComponents(): ?array
    {
        return $this->vueComponents;
    }
}