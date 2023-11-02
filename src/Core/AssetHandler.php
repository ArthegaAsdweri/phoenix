<?php

namespace PhoenixPhp\Core;

/**
 * This class handles assets like CSS and JS that are being registered in runtime by pages, modules and components
 */
class AssetHandler
{

    //---- MEMBERS

    private static self $instance;

    private array $cssFiles = [];
    private array $jsFiles = [];
    private array $externalJsFiles = [];
    private string $inlineJs = '';


    //---- CONSTRUCTOR

    private function __construct()
    {
    }

    /**
     * returns the instance
     * @return self
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //---- GENERAL METHODS

    /**
     * This method registers a CSS file.
     *
     * @param string $path path to the file
     * @param bool $prepend true: prepend the file to the list, false: append it to the list
     * @throws Exception
     */
    final public function registerCss(string $path, bool $prepend = false): void
    {
        $cssFiles = $this->getCssFiles();

        //disable duplicate includes
        if (in_array($path, $cssFiles, true)) {
            return;
        }

        $filePath = 'Resources/Css/' . $path;
        if (!stream_resolve_include_path($filePath)) {
            if (!stream_resolve_include_path($path)) {
                throw new Exception('The file "' . $filePath . '" does not exist. Same applies to "' . $path . '".');
            }
            $filePath = $path;
        }
        
        if ($prepend === true) {
            array_unshift($cssFiles, $filePath);
        } else {
            $cssFiles[] = $filePath;
        }
        $this->setCssFiles($cssFiles);
    }

    /**
     * This method registers a JS file.
     *
     * @param string $path path to the file
     * @throws Exception
     */
    final public function registerJs(string $path): void
    {
        $jsFiles = $this->getJsFiles();

        //disable duplicate includes
        if (in_array($path, $jsFiles, true)) {
            return;
        }

        if (!stream_resolve_include_path($path)) {
            throw new Exception('The file "' . $path . '" does not exist.');
        }

        $jsFiles[] = $path;
        $this->setJsFiles($jsFiles);
    }

    /**
     * This method registers an external JS file.
     *
     * @param string $path path to the file
     */
    final public function registerExternalJs(string $path): void
    {
        $jsFiles = $this->getExternalJsFiles();

        //disable duplicate includes
        if (in_array($path, $jsFiles, true)) {
            return;
        }

        $jsFiles[] = $path;
        $this->setExternalJsFiles($jsFiles);
    }

    /**
     * This method registers inline JS code.
     *
     * @param string $code Der JS Code-Abschnitt
     */
    final public function registerInlineJs(string $code): void
    {
        $jsString = $this->getInlineJs();
        $jsString .= $code;
        $this->setInlineJs($jsString);
    }

    /**
     * This method determines the project path.
     * @return string the directory path
     */
    private function retrieveDirectoryPath(): string
    {
        $includedFiles = get_included_files();
        $firstFile = $includedFiles[0];
        $partArray = explode('/', $firstFile);
        $length = count($partArray);
        unset($partArray[$length - 1]);
        return implode('/', $partArray);
    }

    /**
     * This method renders all the CSS files into one single file and returns its name for being used in the path.
     *
     * @return string name of the CSS file
     */
    final public function renderCss(array $components): string
    {
        //render CSS files
        $cssFiles = $this->getCssFiles();
        $cssFileString = '';
        $cssContent = '';
        foreach ($cssFiles as $cssFile) {
            $cssFileString .= $cssFile;
            //always use the uncompressed version for development
            if (!getenv("DEVELOPER")) {
                $minFile = str_replace('.css', '.min.css', $cssFile);
                if (stream_resolve_include_path($minFile)) {
                    $cssFile = $minFile;
                }
            }
            $cssContent .= file_get_contents(stream_resolve_include_path($cssFile));
        }

        //render component styles
        foreach ($components as $component) {
            $component->renderComponent();
            $cssContent .= $component->getVStyle();
        }

        $md5String = md5($cssFileString);

        $projectPath = $this->retrieveDirectoryPath();
        $projectPath = str_replace('/app/vendor/phpunit/phpunit', '', $projectPath);
        $fp = fopen($projectPath . '/src/Resources/Cache/' . $md5String . '.css', 'w');
        if ($fp) {
            //@codeCoverageIgnoreStart
            fwrite($fp, $cssContent);
            fclose($fp);
            //@codeCoverageIgnoreEnd
        }
        return $md5String;
    }

    /**
     * This method renders all the JS files into one single file and returns its name for being used in the path.
     *
     * @return string name of the JS file
     */
    final public function renderJs(): string
    {
        $jsFiles = $this->getJsFiles();
        $jsFileString = '';
        $jsContent = '';
        foreach ($jsFiles as $jsFile) {
            $jsFileString .= $jsFile;
            //always use the uncompressed version for development
            if (!getenv("DEVELOPER")) {
                $minFile = str_replace('.js', '.min.js', $jsFile);
                if (stream_resolve_include_path($minFile)) {
                    $jsFile = $minFile;
                }
            }
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
    final public function renderExternalJs(): string
    {
        $jsFiles = $this->getExternalJsFiles();
        $jsFileString = '';
        foreach ($jsFiles as $jsFile) {
            $jsFileString .= '<script src="' . $jsFile . '"></script>';
        }
        return $jsFileString;
    }

    //---- SETTERS AND GETTERS

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

}