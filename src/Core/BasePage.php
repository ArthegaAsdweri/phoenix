<?php

namespace PhoenixPhp\Core;

/**
 * handles the contents of a page
 */
abstract class BasePage
{

    //---- MEMBERS

    protected string $page;
    protected string $calledPage;
    protected string $calledAction;
    protected string $templatePath;
    protected ?string $subTemplatePath = null;
    protected array $cssFiles = [];
    protected array $jsFiles = [];
    protected array $externalJsFiles = [];
    protected string $inlineJs = '';
    protected array $vueComponents = [];
    protected bool $debugMode = false;


    //---- ABSTRACT METHODS

    /**
     * Diese Methode rendert den Inhalt der Seite
     *
     * @return string    Der Inhalt der Seite
     */
    abstract public function parseContent(): string;

    /**
     * Diese Methode rendert den Inhalt der Vue-Komponenten
     */
    abstract public function parseVueComponents(): void;


    //---- GENERAL METHODS

    /**
     * Diese Methode generiert ein Array mit den Daten zum aktuellen Pfad der Seite
     *
     * @return array    Array mit den Pfad-Daten
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
     * Diese Methode generiert den Pfad zum Template und speichert ihn abrufbar im Objekt
     */
    public function generateTemplate(): void
    {
        $pageData = $this->retrievePageArray();

        //Haupt-Template
        $templatePath = 'Pages/' . $pageData['page'] . '/' . $pageData['action'] . '.html';
        if (!stream_resolve_include_path($templatePath)) {
            throw new Exception('Die Datei ' . $templatePath . ' existiert nicht.');
        }
        $this->setTemplatePath($templatePath);

        //SubTemplate
        $templatePath = 'Pages/' . $pageData['page'] . '/' . $pageData['action'] . 'Sub.html';
        if (!stream_resolve_include_path($templatePath)) {
            $templatePath = 'Pages/' . $pageData['page'] . '/' . $pageData['action'] . 'Sub.html';
            $templatePath = stream_resolve_include_path($templatePath);
        }
        $this->setSubTemplatePath($templatePath);
    }

    /**
     * Diese Methode registriert eine Vue-Komponente
     *
     * @param string $componentName Der Name der Komponente
     * @param bool $childComponent true: es handelt sich um eine Kindkomponente, die nicht global regisrtiert wird
     * @param null|string $mixin LOCAL = lokales Mixin, GLOBAL = globales Mixin (für alle Components), VUE = nur für die Haupt-Instanz     *
     * @return Component|null       Die Komponente oder null, falls sie nicht gefunden wurde
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
            $componentPath = 'mixins/' . $componentName . '.vue';
            $componentPath2 = $project . 'mixins/' . $componentName . '.vue';
        } else {
            $componentPath = 'pages/' . $page . '/components/' . $componentName . '.vue';
            $componentPath2 = $project . 'pages/' . $page . '/components/' . $componentName . '.vue';
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
        $logger->warning('Die Vue Komponente "' . $componentPath . '" wurde nicht gefunden.');
        return null;
    }

    /**
     * registers a vue mixin
     *
     * @param string $componentName name of the component
     * @param bool $global true: mixin will be accessable for all components false: mixin must be included manually
     * @return Component|null component or null if not valid
     */
    final protected function registerVueMixin(string $componentName, bool $global = false): ?Component
    {
        $mixin = ($global) ? 'GLOBAL' : 'LOCAL';
        return $this->registerVueComponent($componentName, true, $mixin);
    }

    /**
     * Diese Methode registriert ein Mixin, das nur für die Haupt-Vue-Instanz geladen wird.
     *
     * @param string $componentName der Name der Komponente
     * @return VueComponent|null       Die Komponente oder null, falls sie nicht gefunden wurde
     */
    final protected function registerMainMixin(string $componentName): ?VueComponent
    {
        return $this->registerVueComponent($componentName, true, 'VUE');
    }


    /**
     * registers a css file
     * @param string $path path to the file
     */
    final protected function registerCss(string $path): void
    {
        $cssFiles = $this->getCssFiles();

        //disable duplicate includes
        foreach ($cssFiles as $value) {
            if ($value === $path) {
                return;
            }
        }

        $path = 'Resources/Css/' . $path;
        if (!stream_resolve_include_path($path)) {
            throw new Exception('Die Datei ' . $path . ' existiert nicht.');
        }

        $cssFiles[] = $path;
        $this->setCssFiles($cssFiles);
    }

    /**
     * Diese Funktion registriert eine JS-Datei, die geladen werden soll
     *
     * @param string $path Pfad zur JS-Datei
     */
    final protected function registerJs(string $path): void
    {
        $jsFiles = $this->getJsFiles();

        //Wurde die Datei bereits registriert?
        foreach ($jsFiles as $value) {
            if ($value === $path) {
                return;
            }
        }

        if (!stream_resolve_include_path($path)) {
            throw new Exception('Die Datei ' . $path . ' existiert nicht.');
        }

        $jsFiles[] = $path;
        $this->setJsFiles($jsFiles);
    }

    /**
     * Diese Funktion registriert eine externe JS-Datei, die geladen werden soll
     *
     * @param string $path Pfad zur JS-Datei
     */
    final protected function registerExternalJs(string $path): void
    {
        $jsFiles = $this->getExternalJsFiles();

        //Wurde die Datei bereits registriert?
        foreach ($jsFiles as $value) {
            if ($value === $path) {
                return;
            }
        }

        $jsFiles[] = $path;
        $this->setExternalJsFiles($jsFiles);
    }

    /**
     * Diese Funktion registriert Inline-Code, der ausgeführt werden soll
     *
     * @param string $code Der JS Code-Abschnitt
     */
    final protected function registerInlineJs(string $code): void
    {
        $jsString = $this->getInlineJs();
        $jsString .= $code;
        $this->setInlineJs($jsString);
    }

    /**
     * determines the project path
     * @return string
     */
    private function retrieveDirectoryPath()
    {
        $includedFiles = get_included_files();
        $firstFile = $includedFiles[0];
        $partArray = explode('/', $firstFile);
        $length = count($partArray);
        unset($partArray[$length - 1]);
        return implode('/', $partArray);
    }

    /**
     * Diese Methode rendert die CSS-Dateien in eine einzelne Datei und gibt ihren Namen zurück, damit sie als Pfad
     * geparst werden kann.
     *
     * @return string    Der Name der CSS-Datei
     */
    final protected function renderCss(): string
    {
        $cssFiles = $this->getCssFiles();
        $cssFileString = '';
        $cssContent = '';
        foreach ($cssFiles as $cssFile) {
            $cssFileString .= $cssFile;
            $cssContent .= file_get_contents(stream_resolve_include_path($cssFile));
        }

        $components = $this->getVueComponents();
        foreach ($components as $component) {
            $component->renderComponent();
            $cssContent .= $component->getVStyle();
        }

        $md5String = md5($cssFileString);

        $projectPath = $this->retrieveDirectoryPath();
        $fp = fopen($projectPath . '/src/Resources/Cache/' . $md5String . '.css', 'w');
        fwrite($fp, $cssContent);
        fclose($fp);
        return $md5String;
    }

    /**
     * Diese Methode rendert die JS-Dateien in eine einzelne Datei und gibt ihren Namen zurück, damit sie als Pfad
     * geparst werden kann.
     *
     * @return string    Der Name der JS-Datei
     */
    final protected function renderJs(): string
    {
        $jsFiles = $this->getJsFiles();
        $jsFileString = '';
        $jsContent = '';
        foreach ($jsFiles as $jsFile) {
            //Lokal immer die Entwickler-JS-Dateien laden
            if (getenv('DEVELOPER')) {
                $jsFile = str_replace('/js/', '/devjs/', $jsFile);
            }
            $jsFileString .= $jsFile;
            $jsContent .= file_get_contents(stream_resolve_include_path($jsFile));
        }
        $md5String = md5($jsFileString);

        #$fp = fopen('/srv/www/media/cache/'.$md5String.'.js', 'w');
        #fwrite($fp, $jsContent);
        #fclose($fp);

        return $md5String;
    }

    /**
     * Diese Methode rendert die script-Tags für die externen Java-Script Dateien
     *
     * @return string    die JS-Dateien als <script>tags
     */
    final protected function renderExternalJs(): string
    {
        $jsFiles = $this->getExternalJsFiles();
        $jsFileString = '';
        foreach ($jsFiles as $jsFile) {
            $jsFileString .= '<script src="' . $jsFile . '"></script>';
        }
        return $jsFileString;
    }

    /**
     * Diese Methode gibt den Inhalt der Aktion zurück.
     *
     * @return string    Der Inhalt der Seite
     */
    public function render(): string
    {
        $this->generateTemplate();
        $pageContent = $this->parseContent();
        $this->parseVueComponents();
        return $pageContent;
    }

    /**
     * Diese Methode ruft ein Modul der Seite auf.
     *
     * @param string $moduleName Der Name des Moduls, das aufgerufen wird
     * @param string $action Die Aktion innerhalb des Moduls, die aufgerufen wird
     * @param bool $global true, wenn das Modul ein globales Modul ist, das geladen werden soll
     *
     * @return BaseModule           Das Modul, das aufgerufen wird
     */
    final protected function callModule(
        string $moduleName,
        string $action = 'default',
        bool $global = false
    ): BaseModule {
        $ucAction = ucfirst($action);
        $className = 'PhoenixPhp\Modules\\' . strtoupper($moduleName) . '\\' . $ucAction . 'Action';

        //FIXME - REGISTRIEREN VON CSS UND JS ÜBER MODUL MÖGLICH MACHEN

        return new $className();
    }

    /**
     * Wrapper Funktion für callModule
     */
    final protected function callGlobalModule(string $moduleName, string $action = "default"): BaseModule
    {
        return $this->callModule($moduleName, $action, true);
    }

    /**
     * Diese Methode rendert das Modul - speichert als Zwischenschritt alle Vue-Components und CSS Dateien in die Seite,
     * damit diese vom Controller gerendert werden können
     *
     * @param BaseModule $module Das Modul, das gerendert werden soll
     *
     * @return string               Der Inhalt des Moduls
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
    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    /**
     * @param array $cssFiles
     */
    public function setCssFiles(array $cssFiles): void
    {
        $this->cssFiles = $cssFiles;
    }

    /**
     * @return array
     */
    public function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    /**
     * @param array $jsFiles
     */
    public function setJsFiles(array $jsFiles): void
    {
        $this->jsFiles = $jsFiles;
    }

    /**
     * @return array
     */
    public function getExternalJsFiles(): array
    {
        return $this->externalJsFiles;
    }

    /**
     * @param array $externalJsFiles
     */
    public function setExternalJsFiles(array $externalJsFiles): void
    {
        $this->externalJsFiles = $externalJsFiles;
    }

    /**
     * @return string
     */
    public function getInlineJs(): string
    {
        return $this->inlineJs;
    }

    /**
     * @param string $inlineJs
     */
    public function setInlineJs(string $inlineJs): void
    {
        $this->inlineJs = $inlineJs;
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


}