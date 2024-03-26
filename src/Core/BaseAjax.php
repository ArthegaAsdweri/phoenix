<?php

namespace PhoenixPhp\Core;

use PhoenixPhp\Utils\StringConversion;

/**
 * Abstract base class for every ajax request
 */
abstract class BaseAjax
{

    //---- ABSTRACT METHODS

    /**
     * This method runs the ajax call.
     *
     * @return string|null the rendered response or null if none is necessary
     */
    abstract public function run(): ?string;


    //---- COMMON METHODS

    /**
     * This method loops through all the committed parameters and calls the validation method.
     *
     * @return bool
     */
    private function validateParameters(): bool
    {
        $request = Request::getInstance();
        $parameters = $request->retrieveParameters();
        $valid = true;

        if ($parameters !== null) {
            foreach ($parameters as $parameter => $value) {
                if ($parameter === 'phoenix') {
                    continue;
                }
                $camelParam = StringConversion::toCamelCase($parameter);
                $methodName = 'validate' . ucfirst($camelParam);
                if (getenv('DEVELOPER')) {
                    if (!method_exists($this, $methodName)) {
                        $logger = new Logger();
                        $logger->debug(
                            get_called_class(
                            ) . ': The method "' . $methodName . '()" for "' . $parameter . '" does not exist.'
                        );
                        break;
                    }
                }
                if (method_exists($this, $methodName)) {
                    $valid = $this->$methodName($value);
                    if ($valid === false) {
                        $logger = new Logger();
                        $logger->debug(
                            $methodName . '(): The value "' . $value . '" for "' . $parameter . '" is invalid.'
                        );
                        break;
                    }
                }
            }
        }

        return $valid;
    }

    /**
     * This method returns the result of the ajax call.
     * Returning SUCCESS in case of error is a way of misleading attackers.
     *
     * @return string 'OK' if everything went well, 'SUCCESS' on failure or rendered response
     */
    final public function render(): string
    {
        if ($this->validateParameters() === true) {
            $runResult = $this->run();
            if ($runResult === null) {
                return 'OK';
            } else {
                return $runResult;
            }
        }
        return 'SUCCESS';
    }
}