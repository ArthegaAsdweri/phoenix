<?php

namespace PhoenixPhp\Database;

use PDO;
use PhoenixPhp\Core\Exception;

/**
 * Die Wrapper-Klasse für alle Where-Clauses
 */
class Where
{

    //---- MEMBER VARIABLEN

    private int $index = 0;
    private array $clauses = [];
    private array $keycount = [];


    //---- SETTER

    private function setIndex(int $val): void
    {
        $this->index = $val;
    }

    private function setClauses(array $val): void
    {
        $this->clauses = $val;
    }

    private function setKeyCount(array $val): void
    {
        $this->keycount = $val;
    }


    //---- GETTER

    private function getIndex(): int
    {
        return $this->index;
    }

    public function getClauses(): array
    {
        return $this->clauses;
    }

    public function getKeycount(): array
    {
        return $this->keycount;
    }


    //---- ALLGEMEINE FUNKTIONEN

    /**
     * Diese Funktion erzeugt eine Where-Klausel
     *
     * @param int $index der Index der Bedingung
     * @param string $key Der Key
     * @param $val
     */
    public function addClause(int $index, string $key, $val): void
    {
        $this->addKeyCount($key);
        $this->setIndex($index);
        $clauses = $this->getClauses();
        $clauses[] = [
            'connector' => null,
            'key' => $key,
            'value' => $val,
            'keyCount' => $this->getKeycount()[$key]
        ];
        $this->setClauses($clauses);
    }


    public function or(string $key, $value): void
    {
        $this->addKeyCount($key);
        $clauses = $this->getClauses();
        $clauses[] = [
            'connector' => 'OR',
            'key' => $key,
            'value' => $value,
            'keyCount' => $this->getKeycount()[$key]
        ];
        $this->setClauses($clauses);
    }

    public function and(string $key, $value): void
    {
        $this->addKeyCount($key);
        $clauses = $this->getClauses();
        $clauses[] = [
            'connector' => 'AND',
            'key' => $key,
            'value' => $value,
            'keyCount' => $this->getKeycount()[$key]
        ];
        $this->setClauses($clauses);
    }


    //FIXME - Das definieren der Key Values sollte auf alle Queries ausgerollt werden, damit die Types definiert sind
    public function createWhere(): string
    {
        $clauses = $this->getClauses();

        $returnString = '';
        foreach ($clauses as $clause) {
            if ($clause['connector'] !== null) {
                $returnString .= ' ' . $clause['connector'] . ' ';
            }

            $key = $clause['key'];
            $value = $clause['value'];
            $keyCount = (isset($clause['keyCount']) && $clause['keyCount'] !== 1) ? '_' . $clause['keyCount'] : '';
            $fix = $key;
            $usedKey = $key . $keyCount;
            $connector = ' =';
            $usedQuery = false;

            $usedValue = $value;
            if (is_array($value)) {
                $usedValue = $value[1];
                $givenConnector = $value[0];
            }

            if (is_a($usedValue, '\PhoenixPhp\Database\Query')) {
                $usedValue = $usedValue->createQueryString();
                $usedQuery = true;
            } else {
                if (!is_array($usedValue)) {
                    $fix = str_replace('.', '_', $fix);
                    $usedKey = '`' . str_replace('.', '`.`', $key) . '`';
                    $valuePdoType = $this->retrieveParameterType($usedValue);
                    $connector = ($valuePdoType !== 0) ? '=' : '<=>';
                } else {
                    $valuePdoType = $this->retrieveParameterType($usedValue);
                    if ($givenConnector === 'IN' || $givenConnector === 'NOT IN') {
                        if ($valuePdoType === 2) {
                            $fix = '\'' . implode('\',\'', $usedValue) . '\'';
                        } else {
                            $fix = implode(',', $usedValue);
                        }
                    } else {
                        if ($givenConnector === 'BETWEEN') {
                            if (!$this->isDate($usedKey)) {
                                $fix = '`' . implode('` AND `', $usedValue) . '`';
                            } else {
                                $fix = '\'' . implode('\' AND \'', $usedValue) . '\'';
                            }
                        }
                    }
                }
            }

            if (is_array($value)) {
                $connector = $givenConnector;
            }

            //Kein Parameter-Binding mit NOT NULL
            if ($value === 'NOT NULL') {
                $returnString .= $usedKey . ' IS NOT NULL AND ' . $usedKey . ' != ""';
            } //... und mit IN und NOT IN
            else {
                if ($connector === 'IN' || $connector === 'NOT IN') {
                    if ($usedQuery) {
                        $returnString .= $usedKey . ' ' . $connector . '('.$usedValue.')';
                    } else {
                        $returnString .= $usedKey . ' ' . $connector . '(' . $fix . ')';
                    }
                } //... und mit BETWEEN
                else {
                    if ($connector === 'BETWEEN') {
                        if ($this->isDate($usedKey)) {
                            $usedKey = preg_replace('/(_.*)/', '', $usedKey);
                            $usedKey = '"' . $usedKey . '"';
                        }
                        $returnString .= $usedKey . ' ' . $connector . ' ' . $fix;
                    } else {
                        if ($connector === 'LIKE') {
                            $returnString .= $usedKey . ' LIKE CONCAT("%", :' . $fix . ', "%")';
                        } else {
                            if ($this->isDate($fix)) {
                                $checkKey = str_replace('`', '', $usedKey);
                                if ($this->isDate($checkKey)) {
                                    $usedKey = '"' . $checkKey . '"';
                                }
                                if ($this->isDate($usedValue)) {
                                    $usedValue = '"' . $usedValue . '"';
                                } else {
                                    $usedValue = '`' . $usedValue . '`';
                                }
                                $returnString .= $usedKey . ' ' . $connector . ' ' . $usedValue . '';
                            } else {
                                $fix .= $keyCount;
                                $returnString .= $usedKey . ' ' . $connector . ' :' . $fix;
                            }
                        }
                    }
                }
            }
        }

        return '(' . $returnString . ')';
    }

    private function addKeyCount(string $key): void
    {
        $keys = $this->getKeycount();
        if (!isset($keys[$key])) {
            $keys[$key] = 0;
        }
        $keys[$key]++;
        $this->setKeyCount($keys);
    }

    public function retrieveParameters(): array
    {
        $clauses = $this->getClauses();

        $parameters = [];
        foreach ($clauses as $clause) {
            $key = $clause['key'];
            $value = $clause['value'];
            $keyCount = (isset($clause['keyCount']) && $clause['keyCount'] !== 1) ? '_' . $clause['keyCount'] : '';
            $usedQuery = false;

            if ($value === 'IS NULL') {
                continue;
            }

            $usedValue = $value;
            if (is_array($value)) {
                $usedValue = $value[1];
            }

            if (is_a($usedValue, '\PhoenixPhp\Database\Query')) {
                $usedValue = $usedValue->createQueryString();
                $valuePdoType = PDO::PARAM_STR;
                $usedQuery = true;
                return [];
            } else {
                $key = str_replace('.', '_', $key);
                $valuePdoType = $this->retrieveParameterType($usedValue);
            }

            if (!$this->isDate($key)) {
                $parameters[] = ['placeholder' => $key . $keyCount, 'value' => $usedValue, 'type' => $valuePdoType, 'usedQuery' => $usedQuery];
            }
        }
        return $parameters;
    }

    /**
     * Diese Methode gibt den PDO-Datentyp eines übergebenen Parameters zurück, damit PDO ihn korrekt kodieren kann
     *
     * @param mixed $value Der Wert, der in die DB gespeichert werden soll
     *
     * @return int            Die id des Datentyps für PDO
     */
    final public function retrieveParameterType($value): int
    {
        $usedValue = $value;
        if (is_array($value)) {
            $usedValue = $value[0];
        }

        $type = gettype($usedValue);
        if ($type === 'boolean') {
            return PDO::PARAM_BOOL;
        } elseif ($type === 'integer') {
            return PDO::PARAM_INT;
        } elseif ($type === 'double') {
            return PDO::PARAM_STR;
        } elseif ($type === 'string') {
            return PDO::PARAM_STR;
        } elseif ($type === 'NULL') {
            return PDO::PARAM_NULL;
        } else {
            $error = 'Der Parameter ' . print_r(
                    $usedValue,
                    true
                ) . ' hat einen ungültigen Typ: ' . $type;
            throw new Exception($error);
        }
    }

    /**
     * Diese Funktion prüft, ob es sich bei dem übergebenen String um ein Datum handelt
     *
     * @param string $string der String, der geprüft wird
     * @return bool             true: Es ist ein Datum, false: Es ist kein Datum
     */
    private function isDate(string $string): bool
    {
        if (strtotime($string)) {
            return true;
        }
        return false;
    }
}