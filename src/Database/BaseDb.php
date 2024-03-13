<?php

namespace PhoenixPhp\Database;

use Error;
use PhoenixPhp\Core\Exception;
use PhoenixPhp\Core\Logger;
use PhoenixPhp\Core\Session;
use PhoenixPhp\Utils\StringConversion;
use PDO;
use PDOException;

/**
 * Diese Klasse stellt eine Abstraktionsebene dar, über die mit der Datenbank kommuniziert wird.
 */
class BaseDb
{

    //---- KONSTANTEN

    //---- MEMBER VARIABLEN

    private PDO $connection;
    private string $className;
    private bool $ignoreMock = false;
    private bool $debugMode = false;
    private bool $transaction = false;


    //---- KONSTRUKTOR

    /**
     * Erzeugt eine Datenbankverbindung via PDO
     *
     * @param string $db Der Name der Datenbank, zu der die Verbindung aufgebaut wird
     *
     * @throws Exception    Falls keine Verbindung zur Datenbank aufgebaut werden kann
     */
    protected function __construct(string $db)
    {
        $dsn = 'mysql:host=' . PHPHP_DB['HOST'] . ';dbname=' . $db . ';charset=' . PHPHP_DB['CHARSET'];
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, PHPHP_DB['USER'], PHPHP_DB['PASS'], $options);
            $this->setConnection($pdo);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

        $session = Session::getInstance();
        if ($session->retrieve('PHOENIX', 'DEBUGGER_ACTIVE') === true) {
            $this->setDebugMode(true);
        }

        $this->query = new Query();
    }

    //---- SETTER UND GETTER

    //---- SETTER

    private function setConnection(PDO $val): void
    {
        $this->connection = $val;
    }

    private function setClassName(string $val): void
    {
        $this->className = $val;
    }

    public function setIgnoreMock(bool $val): void
    {
        $this->ignoreMock = $val;
    }

    private function setDebugMode(bool $val): void
    {
        $this->debugMode = $val;
    }

    private function setTransaction(bool $val): void
    {
        $this->transaction = $val;
    }

    //---- GETTER

    private function getConnection(): PDO
    {
        return $this->connection;
    }

    private function getClassName(): string
    {
        return $this->className;
    }

    private function getIgnoreMock(): bool
    {
        return $this->ignoreMock;
    }

    public function getDebugMode(): bool
    {
        return $this->debugMode;
    }

    public function getTransaction(): bool
    {
        return $this->transaction;
    }


    //---- SELEKTOREN

    /**
     * Diese Methode findet einen Eintrag anhand seines Primary Keys.
     *
     * @param string $tableName die Tabelle, auf die zugegriffen wird
     * @param mixed $value Der Wert des Primary Keys, nach dem gesucht wird
     * @param array $keys Ein Array aus Feldern, die selektiert werden sollen
     *
     * @return ?object             Ein Objekt der entsprechenden Klasse oder null, wenn kein Eintrag gefunden wurde
     */
    public function findByPk(string $tableName, $value, array $keys = ['*']): ?object
    {
        $className = $this->generateTargetClassName($tableName);
        $class = new $className();
        $values = $class::KEYS;
        $pks = $values['PK'];

        if ($pks === '') {
            $logger = new Logger();
            $logger->error($className . ' enthält keinen PK.');
            return null;
        }

        if (is_array($value)) {
            $where = $this->createPkArray($pks, $value);
        } else {
            $where = [$pks => $value];
        }

        //TODO: Mehrere Parameter / mehrere PKS
        $this->query->select($keys);
        $this->query->from($tableName);
        $this->query->where($where);
        return $this->findUnique();
    }

    /**
     * @param array $pks Ein Array der Primärschlüssel der Datenbank-Tabelle
     * @param array $values Die übergebenen Primary-Keys
     *
     * @return array           das korrekte Array für die Where Klausel
     */
    private function createPkArray(array $pks, array $values): array
    {
        $where = [];
        foreach ($values as $key => $value) {
            if (!in_array($key, array_values($pks))) {
                $pkString = implode(', ', $pks);
                throw new Exception(
                    'Der Key "' . $key . '" ist nicht in den Primary Keys [' . $pkString . '] enthalten.'
                );
            }
            $where[$key] = $value;
        }
        return $where;
    }

    /**
     * Diese Methode liefert das eine erwartete Ergebnis
     *
     * FIXME - Exception, wenn mehr als ein Ergebnis gefunden wird - Falsche Funktion verwendet!
     *
     * @return object|array|null $result      Das Ergebnis des Queries oder null, wenn kein Treffer
     * @throws Exception
     */
    private function findUnique(): object|array|null
    {
        if ($this->query->getJoins() !== null) {
            throw new Exception('findUnique kann nicht mit Join verwendet werden. (Datensatz uneindeutig)');
        }

        //Zwischewnspeichern der Tabelle - wird nach Query resettet
        $tableName = $this->query->getFrom();

        $result = $this->callQuery($this->query);
        if ($result !== null) {
            $resultArray = $result[0];
            $returnObject = $this->putProperties($resultArray, $tableName);
            return $returnObject;
        }
        return null;
    }


    //---- ALLGEMEINE FUNKTIONEN

    /**
     * Diese Methode gibt alle Properties als Array zurück
     *
     * @return array $properties    Alle (DB-relevanten) Klasseneigenschaften zur Weiterverarbeitung
     */
    public function retrieveProperties(): array
    {
        $classMethods = get_class_methods($this);
        $propertyArray = [];
        foreach ($classMethods as $method) {
            if (strpos($method, 'get') === 0) {
                $propertyName = str_replace('get', '', $method);
                $propertyName = (!in_array($propertyName, ['ID', 'shopId'])) ? lcfirst($propertyName) : $propertyName;
                if (!in_array($propertyName, ['ID', 'shopId'])) {
                    $propertyName = StringConversion::toSnakeCase($propertyName);
                }
                $propertyName = str_replace('shopId', 'shopID', $propertyName);

                try {
                    $propertyArray[$propertyName] = $this->$method();
                } catch (Error $e) {
                    $error = 'Die Eigenschaft ' . $propertyName . ' ist nicht im Mock enthalten. Bitte das Mock aktualisieren.';
                    throw new Exception($error);
                }
            }
        }

        return $propertyArray;
    }

    /**
     * Diese Methode ermittelt alle Einträge, die auf die übergebenen Suchparameter zutreffen
     *
     * @return ?array $result    Das Ergebnis des Queries oder null, wenn kein Treffer
     */
    public function callQuery(Query $query): ?array
    {
        if ($query->getFrom() === null) {
            $error = 'Es wurde keine Ursprungstabelle definiert. Bitte $db->from() verwenden.';
            throw new Exception($error);
        }

        $queryString = $query->createQueryString();

        if ($this->getDebugMode() === true) {
            Debugger::putDebugQuery($queryString);
        }

        // In Funktion auslagern
        try {
            $statement = $this->getConnection()->prepare($queryString);
        } catch (\Exception $e) {
            $logger = new Logger();
            $logger->critical($e->getMessage() . PHP_EOL . $queryString);
            return null;
        }
        $execArray = $query->createExecutionParameters();
        
        //Datenbank hat das Query vorbereitet - nun die Parameter setzen
        if ($statement !== false) {
            foreach ($execArray as $parameter) {
                if ($this->getDebugMode() === true) {
                    $value = $parameter['value'];
                    if ($parameter['type'] === 2) {
                        if (is_array($value)) {
                            $tmp = implode(',', $value);
                            $value = $tmp;
                        } else {
                            if (!$parameter['usedQuery'] === true) {
                                $value = '"' . $value . '"';
                            }
                        }
                    } else {
                        $value = var_export($value, true);
                    }
                    $queryString = str_replace(':' . $parameter['placeholder'], $value, $queryString);
                }
                if ($parameter['value'] === 'NOT NULL') {
                    $parameter['value'] = '';
                } else {
                    if (!is_array($parameter['value'])) {
                        try {
                            $statement->bindValue(
                                ':' . $parameter['placeholder'],
                                $parameter['value'],
                                $parameter['type']
                            );
                        } catch (PDOException $e) {
                            $message = str_replace(
                                'parameter was ',
                                'parameter (' . $parameter['placeholder'] . ') was ',
                                $e->getMessage()
                            );
                            throw new Exception($message);
                        }
                    }
                }
            }
        }

        //... und Query ausführen
        if ($statement->execute()) {
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);

            if ($this->getDebugMode() === true) {
                Debugger::removeLastQuery();
                Debugger::putDebugQuery($queryString);
            }

            if (count($result) > 0) {
                $resultArray = [];
                foreach ($result as $resultObject) {
                    $resultArray[] = $resultObject;
                }

                if ($this->getDebugMode() === true) {
                    $queryArray = $resultArray;
                    if (count($resultArray) > 200) {
                        $queryArray = array_slice($resultArray, 0, 200);
                        $queryArray[] = '... Der Rest des Arrays wurde aus Performance-Gründen abgeschnitten';
                    }
                    Debugger::putDebugQuery(print_r($queryArray, true), true);
                }

                return $resultArray;
            }
        }
        return null;
    }

    /**
     * Diese Methode kümmert sich um das Insert / Update
     *
     * @return int|null    die lastInsertId oder null, bei Fehlschlag
     */
    public function save(BaseModel $object): ?int
    {
        $properties = $this->createExecutionParametersForInsert($object);
        $queryString = $this->createInsertString($object);

        if ($this->getDebugMode() === true) {
            Debugger::putDebugQuery($queryString);
        }

        //Vorbereiten
        try {
            $statement = $this->getConnection()->prepare($queryString);
        } catch (\Exception $e) {
            $logger = new Logger();
            $logger->critical($e->getMessage() . PHP_EOL . $queryString);
            return null;
        }

        //Datenbank hat das Query vorbereitet - nun die Parameter setzen
        if ($statement !== false) {
            $paramCount = 0;
            foreach ($properties as $parameter) {
                if ($this->getDebugMode() === true) {
                    $paramCount++;
                    $value = $parameter['value'];
                    if ($parameter['type'] === 2) {
                        if (is_array($value)) {
                            $tmp = implode(',', $value);
                            $value = $tmp;
                        } else {
                            $value = '"' . $value . '"';
                        }
                    } else {
                        $value = var_export($value, true);
                    }

                    $addCommas = '';
                    if (count($properties) !== $paramCount) {
                        $addCommas = ',';
                    }
                    $queryString = str_replace(
                        ':' . $parameter['placeholder'] . $addCommas,
                        $value . $addCommas,
                        $queryString
                    );
                    $queryString = str_replace(
                        ':up' . ucfirst($parameter['placeholder']) . $addCommas,
                        $value . $addCommas,
                        $queryString
                    );
                }
                $statement->bindValue(':' . $parameter['placeholder'], $parameter['value'], $parameter['type']);
                $statement->bindValue(
                    ':up' . ucfirst($parameter['placeholder']),
                    $parameter['value'],
                    $parameter['type']
                );
            }

            if ($this->getDebugMode() === true) {
                Debugger::removeLastQuery();
                Debugger::putDebugQuery($queryString);
            }
        }

        try {
            //FIXME - Updates, bei denen keine Werte verändert werden, "schlagen fehl"
            if ($statement->execute()) {
                return $this->getConnection()->lastInsertId();
            } else {
                $this->rollBack();
            }
        } catch (PDOException $e) {
            $logger = new Logger();
            $logger->critical($e->getMessage() . PHP_EOL . $queryString);
            $this->rollBack();
        }
        return null;
    }

    /**
     * FIXME
     */
    public function delete(Query $query): bool
    {
        $whereString = $query->createWhereString();
        $queryString = 'DELETE FROM ' . $query->getFrom() . ' ' . $whereString;

        //Vorbereiten
        try {
            $statement = $this->getConnection()->prepare($queryString);
        } catch (\Exception $e) {
            $logger = new Logger();
            $logger->critical($e->getMessage() . PHP_EOL . $queryString);
            return false;
        }

        $execArray = $query->createExecutionParameters();

        //Datenbank hat das Query vorbereitet - nun die Parameter setzen
        if ($statement !== false) {
            foreach ($execArray as $parameter) {
                if ($this->getDebugMode() === true) {
                    $value = $parameter['value'];
                    if ($parameter['type'] === 2) {
                        $value = '"' . $value . '"';
                    } else {
                        $value = var_export($value, true);
                    }
                    $queryString = str_replace(':' . $parameter['placeholder'] . ' ', $value . ' ', $queryString);
                }
                if ($parameter['value'] === 'NOT NULL') {
                    $parameter['value'] = '';
                } else {
                    $statement->bindValue(':' . $parameter['placeholder'], $parameter['value'], $parameter['type']);
                }
            }
        }

        try {
            if ($statement->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            $logger = new Logger();
            $logger->critical($e->getMessage() . PHP_EOL . $queryString);
            $this->rollBack();
        }
        return false;
    }

    /**
     * Diese Methode löscht alle Daten einer bestimmten Tabelle
     *
     * @param string $table Der Name der Tabelle
     * @return bool            true: Erfolgreich gelöscht, false: Ein Fehler ist aufgetreten
     */
    public function truncateTable(string $table): bool
    {
        $queryString = 'TRUNCATE TABLE ' . $table;

        //Vorbereiten
        try {
            $statement = $this->getConnection()->prepare($queryString);
        } catch (\Exception $e) {
            $logger = new Logger();
            $logger->critical($e->getMessage() . PHP_EOL . $queryString);
            return false;
        }

        try {
            if ($statement->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            $logger = new Logger();
            $logger->critical($e->getMessage() . PHP_EOL . $queryString);
            $this->rollBack();
        }
        return false;
    }

    /**
     * Diese Methode generiert die PDO-Parameter für das Insert
     *
     * @param BaseModel $object Ein Datenbank-Objekt
     *
     * @return array $executionArray    Das Array der Parameter mit entsprechendem PDO Type
     */
    private function createExecutionParametersForInsert(BaseModel $object): array
    {
        $properties = $object->retrieveProperties();
        $executionArray = [];

        foreach ($properties as $key => $value) {
            $fix = str_replace('.', '_', $key);
            $valuePdoType = $this->query->retrieveParameterType($value);
            $executionArray[] = ['placeholder' => $fix, 'value' => $value, 'type' => $valuePdoType];
        }

        return $executionArray;
    }


    /**
     * FIXME
     */
    protected function createInsertString(BaseModel $object): string
    {
        $properties = $object->retrieveProperties();
        $tableName = $object->retrieveTableName();
        $insertArray = [];

        foreach ($properties as $key => $value) {
            $propertyArray[] = '`' . $key . '`';
            $insertArray[] = ':' . $key;
            $updateArray[] = '`' . $key . '` = :up' . ucfirst($key);
        }

        $keys = implode(', ', $propertyArray);
        $values = implode(', ', $insertArray);
        $updates = implode(', ', $updateArray);

        $insertString = 'INSERT INTO ' . $tableName . ' (' . $keys . ')
                         VALUES (' . $values . ')
                         ON DUPLICATE KEY UPDATE ' . $updates;

        return $insertString;
    }


    //---- TRANSAKTIONEN

    /**
     * Diese Methode startet eine Transaktion
     */
    public function startTransaction(): void
    {
        $this->getConnection()->beginTransaction();
        $this->setTransaction(true);
        if ($this->getDebugMode() === true) {
            Debugger::putDebugQuery('START TRANSACTION');
        }
    }

    /**
     * Diese Methode sendet die Transaktion ab
     */
    public function commit(): void
    {
        if ($this->getTransaction() === true) {
            $this->getConnection()->commit();
            if ($this->getDebugMode() === true) {
                Debugger::putDebugQuery('COMMIT');
            }
        }
    }

    /**
     * Diese Methode rollt die Transaktion zurück
     */
    public function rollBack(): void
    {
        if ($this->getTransaction()) {
            $this->getConnection()->rollBack();
            $this->setTransaction(false);
            if ($this->getDebugMode() === true) {
                Debugger::putDebugQuery('ROLLBACK');
            }
        }
    }


    //---- HILFSFUNKTIONEN

    /**
     * Diese Methode wandelt das Array aus der Datenbank in eine Instanz der Klasse um.
     *
     * @param array $array Das Ergebnis aus der Datenbank-Abfrage
     * @param string $tableName Der Name der Tabelle, damit die Klasse generiert werden kann
     *
     * @return object              Das Objekt
     */
    public function putProperties(array $array, string $tableName): object
    {
        $className = $this->generateTargetClassName($tableName);
        $class = new $className();

        foreach ($array as $key => $property) {
            $keyParts = explode('_', $key);
            $methodParts = [];
            foreach ($keyParts as $part) {
                $methodParts[] = ucfirst($part);
            }

            $methodName = implode('', $methodParts);
            $methodName = 'set' . $methodName;
            $class->$methodName($property);
        }

        return $class;
    }

    /**
     * Diese Methode generiert den Klassen-Namen der DB Klasse
     *
     * @param string $tableName Der Name der Tabelle, aus dem die Klasse generiert wird
     *
     * @return string              Der komplette Klassen-String
     */
    private function generateTargetClassName(string $tableName): string
    {
        $className = get_called_class() . '\\' . ucfirst(StringConversion::toCamelCase($tableName));
        return $className;
    }

    /**
     * Hilft beim Debugging
     */
    public function printQuery($query): void
    {
        $query->setPretty(true);
        $queryString = $query->createQueryString(true);
        $execArray = $query->createExecutionParameters();
        foreach ($execArray as $parameter) {
            $value = $parameter['value'];
            if ($parameter['type'] === 2) {
                if (is_array($value)) {
                    $tmp = implode(',', $value);
                    $value = $tmp;
                } else {
                    if (!$parameter['usedQuery'] === true) {
                        $value = '"' . $value . '"';
                    }
                }
            } else {
                $value = var_export($value, true);
            }
            $queryString = str_replace(':' . $parameter['placeholder'] . ' ', $value . ' ', $queryString);
            $queryString = str_replace(':' . $parameter['placeholder'] . ')', $value . ')', $queryString);
        }
        die($queryString);
    }

}