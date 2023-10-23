<?php

namespace PhoenixPhp\Core;

/**
 * separates templates from logic. parses php contents into html templates.
 */
class Parser
{

    //---- MEMBERS

    private string $parsed;
    private string $original;
    private string $fileName;


    //---- CONSTRUCTOR

    /**
     * creates a parser instance out of a file or a subtemplate
     *
     * @param string $fileName path to the file
     * @param ?string $subTemplate specific subtemplate '{[{SUBTEMPLATE}]}'
     * @throws Exception File not found or Subtemplate not found
     */
    public function __construct(string $fileName, ?string $subTemplate = null)
    {
        $fileInclude = stream_resolve_include_path($fileName);
        if (is_file($fileInclude)) {
            $fileContent = file_get_contents($fileInclude);

            if ($subTemplate !== null) {
                $tag = '{[{' . $subTemplate . '}]}';
                if (!str_contains($fileContent, $tag)) {
                    throw new Exception('subtemplate "' . $tag . '" does not exist in "' . $fileName . '".');
                }

                $closingTag = str_replace("{[{", "{[{/", $tag);
                $pos1 = strpos($fileContent, $tag);
                $pos2 = strpos($fileContent, $closingTag);
                $startPos = $pos1 + strlen($tag);
                $endPos = $pos2 - $startPos;
                $fileContent = trim(substr($fileContent, $startPos, $endPos));
            }

            $this->setOriginal($fileContent);
            $this->setParsed($fileContent);
            $this->setFileName($fileInclude);
        } else {
            throw new Exception('file "' . $fileName . '" does not exist.');
        }
    }


    //---- GENERAL METHODS

    /**
     * fills a placeholder with content
     *
     * @param string $placeholder placeholder after {{PLACEHOLDER}}
     * @param string $content content for replacement
     * @throws Exception placeholder not found
     */
    public function parse(string $placeholder, string $content): void
    {
        if (!str_contains($this->getParsed(), $placeholder)) {
            throw new Exception(
                'placeholder "{{' . $placeholder . '}}" does not exist in "' . $this->getFileName() . '".'
            );
        }
        $this->setParsed(str_replace('{{' . $placeholder . '}}', $content, $this->getParsed()));
    }

    /**
     * This method renders the template, deletes placeholders that haven't been used and reverts the parser to its
     * original state.
     *
     * @return string $cleanTemplate rendered template
     */
    public function retrieveTemplate(): string
    {
        $cleanTemplate = preg_replace('/[{]{2}(?!(\s)(this))(.*)[^\s][}]{2}/', '', $this->parsed);
        $this->parsed = $this->getOriginal();
        return $cleanTemplate;
    }


    //---- SETTERS AND GETTERS

    /**
     * @return string
     */
    public function getParsed(): string
    {
        return $this->parsed;
    }

    /**
     * @param string $parsed
     */
    public function setParsed(string $parsed): void
    {
        $this->parsed = $parsed;
    }

    /**
     * @return string
     */
    public function getOriginal(): string
    {
        return $this->original;
    }

    /**
     * @param string $original
     */
    public function setOriginal(string $original): void
    {
        $this->original = $original;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

}