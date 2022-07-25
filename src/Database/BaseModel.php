<?php

namespace PhoenixPhp\Database;

use DateTime;
use PhoenixPhp\Utils\StringConversion;
use ReflectionClass;

/**
 * Diese Klasse stellt das Grund-Model aller DB-Klassen mit entsprechenden Hilfs- und Validierungsmethoden dar
 */
abstract class BaseModel
{

    //---- ABSTRAKTE FUNKTIONEN

    abstract public function checkFields(): bool;


    //---- CONSTRUCTOR

    /**
     * Der Konstruktor kann das Result eines Queries übernehmen, um daraus ein voll funktionierendes Klassen-Objekt zu erzeugen.
     *
     * @param array|null $values Ein Array mit den entsprechenden Parametern
     */
    public function __construct(?array $values = null)
    {
        if ($values !== null) {
            foreach ($values as $key => $value) {
                $setter = 'set' . ucfirst(StringConversion::toCamelCase($key));
                $this->$setter($value);
            }
        }
    }

    //---- DATENBANK-FUNKTIONEN

    /**
     * Wrapper-Funktion für DB, damit die Return-Objekte von Code-Suggest etc. profitieren.
     *
     * @param array|string $pk Der Key, nach dem gesucht wird, oder ein Array aller Keys
     * @return static|null        Eine Instanz der aufgerufenen Klasse oder null, wenn kein Ergebnis
     */
    final public static function findByPk($pk): ?self
    {
        $dbName = self::retrieveDbName();
        $db = new $dbName();
        return $db->findByPk(self::retrieveTableName(), $pk);
    }

    /**
     * Wrapper-Funktion für DB, damit man das Objekt direkt speichern kann.
     *
     * @return bool    last insert ID, oder null bei Fehlschlag
     */
    final public function save(): ?int
    {
        $dbName = self::retrieveDbName();
        $db = new $dbName();
        return $db->save($this);
    }


    //---- ALLGEMEINE FUNKTIONEN

    /**
     * Diese Methode ermittelt alle Properties der Datenbank-Klasse in Datenbank-Form (z.B. für Inserts)
     *
     * @return array    Die Liste der Properties als numerisches Array
     */
    final public function retrieveProperties(): array
    {
        $reflection = new ReflectionClass($this);
        $vars = $reflection->getProperties();

        $properties = [];
        foreach ($vars as $var) {
            $key = StringConversion::toSnakeCase($var->name);
            $getName = 'get' . $var->name;
            $value = $this->$getName();
            $usedValue = $value;
            if (is_a($value, '\DateTime')) {
                $usedValue = $value->format('Y-m-d H:i:s');
            }
            $properties[$key] = $usedValue;
        }
        return $properties;
    }

    /**
     * Diese Methode ermittelt den korrekten DB-Namespace der Klasse
     *
     * @return string    der Tabellenname
     */
    final public static function retrieveDbName(): string
    {
        $className = get_called_class();
        $tmpClass = explode('\\', $className);
        $length = count($tmpClass);
        unset($tmpClass[$length - 1]);
        $dbName = implode('\\', $tmpClass);
        return $dbName;
    }

    /**
     * Diese Methode ermittelt den korrekten Tabellennamen des Objekts
     *
     * @return string    der Tabellenname
     */
    final public static function retrieveTableName(): string
    {
        $className = get_called_class();
        $tmpClass = explode('\\', $className);
        $length = count($tmpClass);
        $class = $tmpClass[$length - 1];
        return StringConversion::toSnakeCase($class);
    }

    //---- CREATE STATEMENT

    /**
     * Diese Methode erzeugt das CREATE Statement für die Datenbank, damit die Tabelle programmatisch angelegt werden kann.
     *
     * @return string    das CREATE TABLE Statement
     */
    final public function createStatement(): string
    {
        $reflection = new ReflectionClass($this);
        $classDoc = $reflection->getDocComment();
        $properties = $reflection->getProperties();
        $defaultProps = $reflection->getDefaultProperties();
        $constants = $reflection->getConstants();

        $fieldString = '';
        $propCounter = 1;
        foreach ($properties as $property) {
            $propName = $property->getName();
            $snakeName = StringConversion::toSnakeCase($property->getName());
            $propType = $property->getType();
            $typeName = $propType->getName();

            $isEnum = false;
            $enumValues = [];
            foreach ($constants as $constKey => $constValues) {
                if ($constKey !== 'KEYS') {
                    $workKey = substr($constKey, 6, -1);
                    $workKey = strtolower($workKey);
                    if ($snakeName === $workKey) {
                        $isEnum = true;
                        $enumValues = $constValues;
                    }
                }
            }

            $sqlType = $this->retrievePropertyType($typeName, $isEnum);
            $description = $property->getDocComment();

            if ($isEnum) {
                $sqlType .= '(\'' . implode('\',\'', $enumValues) . '\')';
            }

            $commentAdd = '';
            if ($description !== false) {
                $description = preg_replace('/(@(.*?) (.*?) )/', '', $description);
                $description = str_replace(['*', '/', PHP_EOL], '', $description);
                $commentAdd = ' COMMENT \'' . trim($description) . '\'';
            }

            $defaultAdd = '';
            if (in_array($propName, array_keys($defaultProps))) {
                $propDefault = $defaultProps[$propName];
                $defaultVal = $this->retrieveDefaultPropertyValue($propDefault, $typeName);
                $defaultAdd = ' DEFAULT ' . $defaultVal;
            }

            $nullAdd = ' NOT NULL';
            if ($propType->allowsNull()) {
                $nullAdd = ' NULL';
                if ($defaultVal === 'NULL') {
                    $nullAdd = '';
                }
            }

            $commaAdd = ',';
            if ($propCounter === count($properties)) {
                $commaAdd = '';
            }

            $fieldString .= '`' . $snakeName . '` ' . $sqlType . $nullAdd . $defaultAdd . $commentAdd . $commaAdd . PHP_EOL;

            $propCounter++;
        }

        /*
        CREATE TABLE `ph_menu` (
          `id` int NOT NULL,
          `parent_id` int DEFAULT NULL COMMENT 'id des Eltern-Menüpunkts',
          `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
          `position` int NOT NULL COMMENT 'Position für die Sortierung',
          `visible` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'true: Der Menüpunkt ist sichtbar. false: Der Menüpunkt ist nicht sichtbar.',
          `maintainable` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'true: Der Menüpunkt kann über die Kasse verwaltet werden. false: Der Menüpunkt kann nicht über die Kasse verwaltet werden.',
          `layout` enum('DEFAULT','CELL') CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'DEFAULT' COMMENT 'Das Prouktlayout, das für den Menüpunkt verwendet werden soll.',
          `submenu_display` enum('GROUPED','SEPERATED') CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'GROUPED' COMMENT 'Die Darstellung des Menüs. Alle Menüpunkte gruppiert untereinander oder getrennt aufrufbar',
          `advertisment` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'true: Produkte aus diesem Menüpunkt werden beworben. false: Produkte aus diesem Menüpunkt werden nicht beworben.',
          `show_multiple_sizes` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'true: Es werden alle Größen für alle Produkte angezeigt. false: Es wird nur ein Button mit "ab-Preis" pro Produkt angezeigt.'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Die Menüpunkte der Speisekarte';

        ALTER TABLE `ph_menu`
          ADD PRIMARY KEY (`id`),
          ADD KEY `parent_id` (`parent_id`);

        ALTER TABLE `ph_menu`
          MODIFY `id` int NOT NULL AUTO_INCREMENT;

        ALTER TABLE `ph_menu`
          ADD CONSTRAINT `fk_ph_menu` FOREIGN KEY (`parent_id`) REFERENCES `ph_menu` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
        */

        $classDoc = str_replace(['*', '/', PHP_EOL], '', $classDoc);

        $statement = 'CREATE TABLE IF NOT EXISTS `' . self::retrieveTableName() . '` (' . PHP_EOL .
            $fieldString . ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT=\'' . trim($classDoc) . '\'';

        $statement .= 'ALTER TABLE `' . self::retrieveTableName() . '`';

        die($statement);
    }

    /**
     * Diese Hilfsfunktion ermittelt den DB-konformen Datentyp der Klassen-Eigenschaft anhand ihres Typen
     *
     * @param string $type Der Datentyp
     * @param bool $isEnum true: Der String ist ein ENUM und muss den entsprechenden Datentypen ausgeben
     *
     * @return string           Der valide Datentyp für die DB (aus string mach varchar(255) usw.)
     */
    private function retrievePropertyType(string $type, bool $isEnum): string
    {
        $returnVal = '';
        if ($type === 'string') {
            $returnVal = 'varchar(255) CHARACTER SET utf8 COLLATE utf8_bin';
            if ($isEnum) {
                $returnVal = 'enum';
            }
        } else {
            if ($type === 'bool') {
                $returnVal = 'tinyint(1)';
            } else {
                $returnVal = $type;
            }
        }
        return $returnVal;
    }

    /**
     * Diese Hilfsfunktion ermittelt den DB-konformen Standard-Wert der Klassen-Eigenschaft anhand ihres Typen
     *
     * @param mixed $value Der Default-Wert aus der DB-Klasse
     * @param string $type Der Datentyp
     *
     * @return string           Der valide Default-Wert für den entsprechenden Datentypen (aus bool:true mach 1 usw.)
     */
    private function retrieveDefaultPropertyValue($value, string $type): string
    {
        echo $type . PHP_EOL;
        $returnVal = '';
        if ($value === null) {
            $returnVal = 'NULL';
        } else {
            if ($type === 'bool') {
                $boolVal = '\'0\'';
                if ($value === true) {
                    $boolVal = '\'1\'';
                }
                $returnVal = $boolVal;
            } else {
                if ($type === 'string') {
                    $returnVal = '\'' . $value . '\'';
                }
            }
        }
        return $returnVal;
    }



    //---- VALIDIERUNGS-FUNKTIONEN

    /**
     * Diese Methode validiert Strings
     *
     * @param string|null $val der übergebene String
     * @param bool $nullable true: wenn der Wert null sein darf
     * @param int $maxLength die maximale String-Länger, gegen die validiert wird
     *
     * @return bool                     true: wenn der String gültig ist
     *                                  false: wenn nicht
     */
    protected function validateString(?string $val, bool $nullable = false, int $maxLength = 255): bool
    {
        if ($val === null && $nullable === true) {
            return true;
        } else {
            $string = filter_var($val, FILTER_SANITIZE_STRING);
            if (strlen($string) < $maxLength) {
                return true;
            }
        }
        return false;
    }

    /**
     * Diese Methode validiert Zahlen
     *
     * @param int|null $val die übergebene Zahl
     * @param bool $nullable true: wenn der Wert null sein darf
     * @param int|null $maxValue der maximale / minimale Wert der Zahl oder null, falls unbegrenzt groß / klein
     * @param bool $lessThanZero true: wenn der Wert negativ sein darf
     *
     * @return bool                     true: wenn die Zahl gültig ist
     *                                  false: wenn nicht
     */
    protected function validateInt(
        ?int $val,
        bool $nullable = false,
        ?int $maxValue = null,
        $lessThanZero = false
    ): bool {
        if ($val === null && $nullable === true) {
            return true;
        } else {
            $int = filter_var($val, FILTER_SANITIZE_NUMBER_INT);
            if (filter_var($int, FILTER_VALIDATE_INT) === false) {
                return false;
            }

            if (isset($maxValue)) {
                if ($lessThanZero) {
                    if ($int >= $maxValue) {
                        return true;
                    }
                } else {
                    if ($int <= $maxValue) {
                        return true;
                    }
                }
                return false;
            }

            if ($lessThanZero) {
                if ($int < 0) {
                    return true;
                }
            } else {
                if ($int > 0) {
                    return true;
                }
            }
            return true;
        }
    }

    /**
     * Diese Methode validiert Datums-Objekte
     *
     * @param DateTime|null $val das übergebene Datum
     * @param bool $nullable true: wenn das Datum null sein darf
     * @param DateTime|null $minDate das Referenzdatum, das das übergebene Datum überschreiten muss
     * @param DateTime|null $maxDate das Referenzdatum, das das übergebene Datum noch nicht erreicht haben darf
     *
     * @return bool                      true: wenn das Datum gültig ist
     *                                   false: wenn nicht
     */
    protected function validateDateTime(
        ?DateTime $val,
        bool $nullable = false,
        ?DateTime $minDate = null,
        ?DateTime $maxDate = null
    ): bool {
        if ($val === null && $nullable === true) {
            return true;
        } else {
            //FIXME - Prüfung zwischen den beiden Zeiträumen
            if (is_a($val, '\DateTime')) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param float|null $val die Gleitkommazahl
     * @param bool $nullable true: wenn der Wert null sein darf
     * @param int $preDigits Anzahl der Stellen vor dem Komma
     * @param int $postDigits Anzahl der Nachkommastellen
     * @param bool $lessThanZero true: wenn der Wert negativ sein darf
     *
     * @return bool                       true: wenn der Wert eine gültige Gleitkommazahl ist
     *                                    false: wenn nicht
     */
    protected function validateFloat(
        ?float $val,
        bool $nullable = false,
        int $preDigits = 4,
        int $postDigits = 2,
        $lessThanZero = false
    ): bool {
        if ($val === null && $nullable === true) {
            return true;
        } else {
            if (filter_var($val, FILTER_VALIDATE_FLOAT) === false) {
                return false;
            }
            $tmpDigits = explode('.', $val);
            $preComma = strlen($tmpDigits[0]);
            $postComma = strlen($tmpDigits[1]);
            if ($preComma <= $preDigits && $postComma <= $postDigits) {
                if ($lessThanZero) {
                    if ($val < 0) {
                        return true;
                    }
                } else {
                    if ($val > 0) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param bool $val der übergebene Wert
     *
     * @return bool         true: wenn der Wert ein Boolean ist
     *                      false: wenn nicht
     */
    protected function validateBool(bool $val): bool
    {
        $bool = filter_var($val, FILTER_VALIDATE_BOOLEAN);
        if ($bool) {
            return true;
        }
        return false;
    }

}