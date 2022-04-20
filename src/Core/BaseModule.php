<?php

namespace PhoenixPhp\Core;

/**
 * Base class for every module
 */
abstract class BaseModule
{

    //---- MEMBERS

    protected string $templatePath;
    protected ?string $subTemplatePath = null;
    protected ?array $vueComponents = null;


    //---- ABSTRAKTE FUNKTIONEN

    /**
     * Diese Methode liefert das gesamte Modul als Inhalt zurück, sobald das Modul als Bestandteil der Seite aufgerufen wird
     *
     * @return string    das Modul als gerendertes Template
     */
    abstract public function parseContent(): string;

    /**
     * Diese Methode kann genutzt werden, um Vue-Komponenten zu parsen
     */
    abstract public function parseVueComponents(): void;


    //---- ALLGEMEINE FUNKTIONEN

    /**
     * Diese Methode generiert ein Array mit den Daten zum aktuellen Pfad der Seite
     *
     * @return array    Array mit den Pfad-Daten
     */
    public function retrieveModuleArray(): array
    {
        $className = get_called_class();
        $pathArray = explode('\\', $className);
        $project = strtolower($pathArray[1]);
        $module = strtolower($pathArray[3]);
        $action = (isset($pathArray[4])) ? strtolower(str_replace('Action', '', $pathArray[4])) : '';

        //globales Modul liegt nicht in einem Projekt
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
     * Diese Methode generiert den Pfad zum Template und speichert ihn abrufbar im Objekt
     */
    final public function generateTemplate(): void
    {
        $moduleData = $this->retrieveModuleArray();

        //FIXME: KANN NUR TEMPLATES INNERHALB VON PROJEKTEN :(

        $module = ucfirst(strtolower($moduleData['module']));
        $action = ucfirst(strtolower($moduleData['action']));

        //Haupt-Template
        $templatePath = 'Modules/' . $module . '/' . $action . '.html';
        if (!stream_resolve_include_path($templatePath)) {
            $templatePathGlobal = $moduleData['project'] . 'Modules/' . $module . '/' . $action . '.html';
            if (!stream_resolve_include_path($templatePathGlobal)) {
                $logger = new Logger();
                $logger->warning(
                    'Die Datei ' . $templatePath . ' existiert nicht. Global existiert ebenfalls keine Variante: ' . $templatePathGlobal
                );
                //FIXME: Hier ein globales "NOT FOUND" Modul erstellen
                $templatePathGlobal = $moduleData['project'] . 'Modules/Notfound/Default.html';
            }
            $templatePath = $templatePathGlobal;
        }
        $this->setTemplatePath($templatePath);

        //SubTemplate
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
     * Diese Methode registriert eine Vue-Komponente
     *
     * @param string $componentName Der Name der Komponente
     * @param bool $childComponent true: es handelt sich um eine Kindkomponente, die nicht global regisrtiert wird
     *
     * @return Component|null       Die Komponente oder null, falls sie nicht gefunden wurde
     */
    final protected function registerVueComponent(string $componentName, bool $childComponent = false): Component
    {
        $moduleArray = $this->retrieveModuleArray();
        $project = $moduleArray['project'];
        $module = $moduleArray['module'];
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
    }

    /**
     * Diese Methode gibt den Inhalt des Moduls zurück.
     *
     * @return array    Der Inhalt des Moduls und der Vue-Components
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
     * Diese Methode formatiert ein Array in nutzbares JSON-Format für HTML Tags (z.B. als Vue Prop)
     *
     * @param array $dataArray Das Array, das als JSON benötigt wird
     *
     * @return string             Der Json-String
     */
    final protected function returnJsonForHtml(array $dataArray)
    {
        return htmlentities(json_encode($dataArray, JSON_HEX_QUOT), ENT_QUOTES);
    }


    //---- SETTERS AND GETTERS

    protected function setTemplatePath(string $val): void
    {
        $this->templatePath = $val;
    }

    protected function setSubTemplatePath(?string $val): void
    {
        $this->subTemplatePath = $val;
    }

    protected function setVueComponents(?array $val): void
    {
        $this->vueComponents = $val;
    }

    //---- GETTER

    protected function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    protected function getSubTemplatePath(): ?string
    {
        return $this->subTemplatePath;
    }

    protected function getVueComponents(): ?array
    {
        return $this->vueComponents;
    }
}