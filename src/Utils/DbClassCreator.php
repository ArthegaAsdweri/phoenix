<?php

namespace PhoenixPhp\Utils;

class DbClassCreator
{
    public static function create($db, $tableName)
    {
        $table = '`'.$tableName.'`';
        set_time_limit(5);
        $dbLink = mysqli_connect(PHPHP_DB['HOST'], PHPHP_DB['USER'], PHPHP_DB['PASS'], $db);

        $query = 'SHOW FULL COLUMNS FROM ' . $table;
        $ressource = mysqli_query($dbLink, $query);

        $descriptions = [];
        while ($row = mysqli_fetch_assoc($ressource)) {
            $descriptions[$row['Field']] = $row['Comment'];
        }

        $query = 'EXPLAIN ' . $table;
        $ressource = mysqli_query($dbLink, $query);

        $fields = [];
        while ($row = mysqli_fetch_assoc($ressource)) {
            $fields[] = $row;
        }

//---- HIER WIRD NUR GEZAEHLT

        $maxTypeLen = 3;
        $maxCamelLen = 0;
        foreach ($fields as $entry) {
            $field = $entry['Field'];
            $type = $entry['Type'];
            $nullable = $entry['Null'];

            $returnType = self::returnType($type, $nullable);
            $camelField = StringConversion::toCamelCase($field);

            if (strlen($returnType) > $maxTypeLen) {
                $maxTypeLen = strlen($returnType);
            }
            if (strlen($camelField) > $maxCamelLen) {
                $maxCamelLen = strlen($camelField);
            }
        }

//---- HIER WIRD RICHTIG GEARBEITET

        $primaryKeys = [];
        $foreignKeys = [];
        $constString = '';
        $memberString = '';
        $setterString = '';
        $getterString = '';
        $maxFkLen = 0;
        $hasDateTime = false;
        $hasException = false;
        $checkFields = '';
        foreach ($fields as $entry) {
            $field = $entry['Field'];
            $type = $entry['Type'];
            $nullable = $entry['Null'];
            $key = $entry['Key'];
            $default = $entry['Default'];
            $description = $descriptions[$field];

            if ($field === 'ID') {
                $field = 'id';
            } else {
                if (stristr($field, 'ID')) {
                    $field = str_replace('ID', 'Id', $field);
                }
            }

            $camelField = StringConversion::toCamelCase($field);
            $returnType = self::returnType($type, $nullable);
            $isEnum = false;
            $spaces = str_repeat(' ', ($maxTypeLen - strlen($returnType)));
            $camelSpaces = str_repeat(' ', ($maxCamelLen - strlen($camelField)));

            $dateTimeSetAdd = '';
            if (stristr($returnType, 'DateTime')) {
                $hasDateTime = true;
                $nullAdd = '';
                if ($nullable === 'YES') {
                    $nullAdd = '$val !== null && ';
                }

                $dateTimeSetAdd .= '        if(' . $nullAdd . '!is_a($val, \'DateTime\')) {' . PHP_EOL;
                $dateTimeSetAdd .= '            $val = new DateTime($val);' . PHP_EOL;
                $dateTimeSetAdd .= '        }' . PHP_EOL;
            }

            if ($key === 'PRI') {
                $primaryKeys[] = $field;
            } else {
                if ($key === 'MUL') {
                    $foreignKeys[] = $field;
                    if ($maxFkLen < strlen($field)) {
                        $maxFkLen = strlen($field);
                    }
                }
            }

            $constName = '';
            if (stristr($type, 'enum')) {
                $constName = 'VALID_' . strtoupper($field) . 'S';
                $enumString = str_replace(['enum(', ')'], '', $type);
                $constString .= '    const ' . $constName . ' = [' . $enumString . '];' . PHP_EOL;
                $isEnum = true;
            }

            $defaultValue = '';
            if ($nullable === 'YES') {
                $defaultValue = ' = null';
            } else {
                if ($isEnum) {
                    if ($default == 0) {
                        $enumVals = explode(',', $enumString);
                        $defaultValue = ' = ' . $enumVals[0];
                    } else {
                        $defaultValue = ' = ' . $default;
                    }
                } else {
                    if ($default === null) {
                        $camelSpaces = '';
                    } else {
                        if ($default == 0 || $default == 1) {
                            $defaultReturn = self::getDefault($returnType, $default);
                            if ($defaultReturn !== null) {
                                $defaultValue = ' = ' . self::getDefault($returnType, $default);
                            }
                        } else {
                            $camelSpaces = '';
                        }
                    }
                }
            }

            if ($description) {
                $memberString .= '    /**' . PHP_EOL;
                $memberString .= '     * ' . $description . PHP_EOL;
                $memberString .= '     */' . PHP_EOL;
            }
            $memberString .= '    private ' . $returnType . $spaces . ' $' . $camelField . $camelSpaces . $defaultValue . ';' . PHP_EOL;

            $validationString = '';
            if ($isEnum) {
                $notNullAdd = '';
                if ($nullable) {
                    $notNullAdd .= '$val !== null && ';
                }
                $validationString .= '        if(' . $notNullAdd . '!in_array($val, self::' . $constName . ')) {' . PHP_EOL;
                $validationString .= '            $validKeys = implode(\',\', self::' . $constName . ');' . PHP_EOL;
                $validationString .= '            throw new Exception($val.\' ist ungültig. Gültige Werte: [\'.$validKeys.\']\');' . PHP_EOL;
                $validationString .= '        }' . PHP_EOL;
                $hasException = true;
            }

            $returnTypeSetter = $returnType;
            if ($returnType === 'DateTime' || $returnType === '?DateTime') {
                $returnTypeSetter = '';
            }

            $setterString .= PHP_EOL;
            $setterString .= '    public function set' . ucFirst(
                    $camelField . '(' . $returnTypeSetter . ' $val) : void {' . PHP_EOL
                );
            $setterString .= $validationString;
            $setterString .= $dateTimeSetAdd;
            $setterString .= '        $this->' . $camelField . ' = $val;' . PHP_EOL;
            $setterString .= '    }' . PHP_EOL;

            $getterString .= PHP_EOL;
            $getterString .= '    public function get' . ucFirst($camelField) . '() : ' . $returnType . ' {' . PHP_EOL;
            $getterString .= '        return $this->' . $camelField . ';' . PHP_EOL;
            $getterString .= '    }' . PHP_EOL;

            $checkFields .= PHP_EOL;
            $checkFields .= self::checkType($camelField, $type, $nullable, $constName);
        }

//---- NAMESPACE

        $dbNameSpace = 'ArthegaAsdweri\LisaHahn\Db';

//---- IMPORTE

        $importString = '';
        if ($hasDateTime === true) {
            $importString .= 'use DateTime;' . PHP_EOL;
        }

        if ($hasException === true) {
            $importString .= 'use PhoenixPhp\Core\Exception;' . PHP_EOL;
        }


//---- FKS

        $fkCount = 1;
        $fkString = '';
        $pkComma = '';
        if (count($foreignKeys) > 0) {
            $fkString .= '        \'FK\' => [' . PHP_EOL;
            foreach ($foreignKeys as $fk) {
                $spaces = '';
                for ($i = 0; $i < ($maxFkLen - strlen($fk)); $i++) {
                    $spaces .= ' ';
                }
                $comma = ($fkCount < count($foreignKeys)) ? ',' : '';
                $fkString .= '            \'' . $fk . '\'' . $spaces . ' => [\'[FIXME-TABLE]\' , \'[FIXME-FIELD]\']' . $comma . PHP_EOL;
                $fkCount++;
            }
            $fkString .= '        ]' . PHP_EOL;
            $pkComma = ',';
        }

//FIXME: FK-Prüfungen in die Check-Funktionen mit rein?

        echo '<?php' . PHP_EOL;
        echo 'namespace ' . $dbNameSpace . ';' . PHP_EOL;
        echo PHP_EOL;
        echo 'use PhoenixPhp\Database\BaseModel;' . PHP_EOL;
        echo 'use PhoenixPhp\Core\ErrorCollector;' . PHP_EOL;
        echo $importString;
        echo PHP_EOL;
        echo 'class ' . ucFirst(StringConversion::toCamelCase($tableName)) . ' extends BaseModel {' . PHP_EOL;
        echo PHP_EOL;
        echo '    //---- KONSTANTEN' . PHP_EOL;
        echo PHP_EOL;
        echo '    const KEYS = [' . PHP_EOL;
        echo '        \'PK\' => ';
        echo (count($primaryKeys) > 0) ? "'".$primaryKeys[0]."'" : '' . '\'';
        echo $pkComma . PHP_EOL;
        echo $fkString;
        echo '    ];' . PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;
        echo $constString;
        echo PHP_EOL;
        echo '    //---- MEMBER VARIABLEN' . PHP_EOL;
        echo PHP_EOL;
        echo $memberString;
        echo PHP_EOL;
        echo PHP_EOL;
        echo '    //---- SETTER' . PHP_EOL;
        echo str_replace('( $val)', '($val)', $setterString);
        echo PHP_EOL;
        echo PHP_EOL;
        echo '    //---- GETTER' . PHP_EOL;
        echo $getterString;
        echo PHP_EOL;
        echo '    //---- VALIDIERUNG' . PHP_EOL;
        echo PHP_EOL;
        echo '    public function checkFields() : bool {' . PHP_EOL;
        echo PHP_EOL;
        echo '        $return = true;' . PHP_EOL;
        echo $checkFields . PHP_EOL;
        echo '        return $return;' . PHP_EOL;
        echo '    }' . PHP_EOL;
        echo PHP_EOL;
        echo '}' . PHP_EOL;

        die();
    }

    private static function returnType($val, $nullable)
    {
        if (in_array($val, ['tinyint(1)'])) {
            $val = 'bool';
        } elseif (stristr($val, 'int') || $val === 'smallint') {
            $val = 'Int';
        } elseif (stristr($val, 'varchar') || $val === 'text') {
            $val = 'string';
        } elseif (in_array($val, ['datetime', 'timestamp', 'date', 'time'])) {
            $val = 'DateTime';
        } elseif (stristr($val, 'decimal')) {
            $val = 'float';
        } elseif ($val === 'float' || stristr($val, 'double')) {
            $val = 'float';
        } elseif (stristr($val, 'enum')) {
            $val = 'string';
        } else {
            die('returnType: unbekannter Datentyp: ' . $val);
        }

        if ($nullable === 'YES') {
            $val = '?' . $val;
        }

        return $val;
    }

    private static function checkType($field, $val, $nullable, $constName)
    {
        $paramArray = [];
        if (stristr($val, 'varchar')) {
            $type = 'String';
            $maxLength = str_replace(['varchar(', ')'], '', $val);
            if ($maxLength !== 255) {
                $paramArray[] = $maxLength;
            }
        } elseif (in_array($val, ['tinyint(1)'])) {
            $type = 'Bool';
        } elseif (stristr($val, 'int')) {
            $type = 'Int';
        } elseif (in_array($val, ['datetime', 'date', 'timestamp'])) {
            $type = 'DateTime';
        } elseif ($val === 'time') {
            $type = 'Time';
        } elseif (stristr($val, 'decimal')) {
            $type = 'Float';
            $tmpDigits = str_replace(['decimal(', ')'], '', $val);
            $digits = explode(',', $tmpDigits);
            $paramArray[] = $digits[0];
            $paramArray[] = $digits[1];
        } elseif ($val === 'float' || stristr($val, 'double')) {
            $type = 'Float';
        } elseif ($val === 'text') {
            $type = 'String';
        } //Wird im Setter behandelt - keine Validierung notwendig
        elseif (stristr($val, 'enum')) {
            return '';
        } else {
            die('checkType: unbekannter Datentyp: ' . $val);
        }

        $nullVal = '';
        if ($type !== 'Bool') {
            $nullVal = ($nullable === 'YES') ? 'true' : 'false';
        }

        $paramString = '';
        if (count($paramArray) > 0) {
            $nullArray = [];
            $nullArray[] = $nullVal;
            $params = array_merge($nullArray, $paramArray);
            $paramString = ', ' . implode(', ', $params);
        } elseif ($nullVal === 'true') {
            $paramString = ', true';
        }

        $returnString = '        if($this->validate' . $type . '($this->get' . ucfirst(
                $field
            ) . '()' . $paramString . ') === false) {' . PHP_EOL;
        $returnString .= '            ErrorCollector::putErrorMessage(\'' . $field . '\');' . PHP_EOL;
        $returnString .= '            $return = false;' . PHP_EOL;
        $returnString .= '        }' . PHP_EOL;

        return $returnString;
    }

    private static function getDefault($type, $default)
    {
        if ($type === 'bool') {
            if ($default == 1) {
                $defaultValue = 'true';
            } else {
                $defaultValue = 'false';
            }
        } elseif ($type === 'string') {
            $defaultValue = '\'' . $default . '\'';
        } elseif (stristr($type, 'int')) {
            $defaultValue = intval($default);
        } elseif ($type === 'float') {
            $defaultValue = floatval($default);
        } elseif ($type === 'DateTime') {
            if ($default === 'CURRENT_TIMESTAMP') {
                $defaultValue = null;
            }
        } else {
            die('getDefault: unbekannter Datentyp: ' . $type);
        }
        return $defaultValue;
    }
}