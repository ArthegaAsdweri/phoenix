<?php

namespace PhoenixPhp\Database;

use PDO;
use PhoenixPhp\Core\Exception;
use PhoenixPhp\Core\Logger;

/**
 * Das ORM, das die Datenbank-Queries erzeugt
 */
class Query
{

    //---- KONSTANTEN

    const VALID_ORDER_DIRECTIONS = ['ASC', 'DESC'];
    const VALID_JOIN_TYPES = ['INNER', 'LEFT', 'RIGHT'];


    //---- MEMBER VARIABLEN

    private array $selectFields = ['*'];
    private null|string|Query|array $from = null;
    private ?string $fromAlias = null;
    private ?array $joins = null;
    private ?array $where = null;
    private ?array $subQueries = null;
    private ?array $orderFields = null;
    private ?array $groupFields = null;
    private ?int $limit = null;
    private bool $distinct = false;
    private int $whereCounter = 0;
    private bool $isPretty = false;


    //---- SETTER UND GETTER

    //---- SETTER

    public function setSelectFields(array $val): void
    {
        $this->selectFields = $val;
    }

    public function setFrom(null|string|Query|array $val): void
    {
        $this->from = $val;
    }

    public function setFromAlias(?string $val): void
    {
        $this->fromAlias = $val;
    }

    public function setJoins(?array $val): void
    {
        $this->joins = $val;
    }

    public function setWhere(?array $val): void
    {
        $this->where = $val;
    }

    public function setSubqueries(?array $val): void
    {
        $this->subQueries = $val;
    }

    public function setOrderFields(?array $val): void
    {
        $this->orderFields = $val;
    }

    public function setGroupFields(?array $val): void
    {
        $this->groupFields = $val;
    }

    public function setLimit(?int $val): void
    {
        $this->limit = $val;
    }

    public function setDistinct(bool $val): void
    {
        $this->distinct = $val;
    }

    public function setWhereCounter(int $val): void
    {
        $this->whereCounter = $val;
    }

    public function setPretty(bool $val): void
    {
        $this->isPretty = true;
    }

    //---- GETTER

    public function getSelectFields(): array
    {
        return $this->selectFields;
    }

    public function getFrom(): null|string|Query|array
    {
        return $this->from;
    }

    public function getFromAlias(): ?string
    {
        return $this->fromAlias;
    }

    public function getJoins(): ?array
    {
        return $this->joins;
    }

    public function getWhere(): ?array
    {
        return $this->where;
    }

    public function getSubQueries(): ?array
    {
        return $this->subQueries;
    }

    public function getOrderFields(): ?array
    {
        return $this->orderFields;
    }

    public function getGroupFields(): ?array
    {
        return $this->groupFields;
    }

    private function getLimit(): ?int
    {
        return $this->limit;
    }

    private function getDistinct(): bool
    {
        return $this->distinct;
    }

    private function getWhereCounter(): int
    {
        return $this->whereCounter;
    }

    private function isPretty(): bool
    {
        return $this->isPretty;
    }


    //---- ALLGEMEINE FUNKTIONEN

    /**
     * Diese Methode erzeugt den gesamten Query String
     *
     * @return string $preparationQuery    das fertige zusammen gepuzzelte Query
     */
    public function createQueryString(): string
    {
        $p = ' ';
        if ($this->isPretty()) {
            $p = PHP_EOL;
        }
        $queryString = $this->createSelectString();
        $queryString .= $p . $this->createFromString();

        $joinString = $this->createJoinString();
        if ($joinString !== '') {
            $queryString .= $joinString;
        }

        $whereString = $this->createWhereString();
        if ($whereString !== '') {
            $queryString .= $p . $whereString;
        }

        $groupString = $this->createGroupString();
        if ($groupString !== '') {
            $queryString .= $p . $groupString;
        }

        $orderString = $this->createOrderString();
        if ($orderString !== '') {
            $queryString .= $p . $orderString;
        }

        $limitString = $this->createLimitString();
        if ($limitString !== '') {
            $queryString .= $p . $limitString;
        }
        return $queryString . $p;
    }

    /**
     * Diese Methode erzeugt den SELECT-String für das Query
     *
     * @return string $selectString    Der fertige SELECT-String für das Query
     */
    private function createSelectString(): string
    {
        $originalFields = $this->getSelectFields();
        $selectFields = [];

        foreach ($originalFields as $selectField) {
            $selectField = preg_replace_callback('/\(([^()]*)\)/', function ($matches) {
                if (stristr($matches[1], '.')) {
                    $tmpArray = explode('.', $matches[1]);
                    $matches[1] = '`' . implode('`.`', $tmpArray) . '`';
                }
                $innerField = trim($matches[1], '`');
                return '(`' . $innerField . '`)';
            }, $selectField);

            $aliasString = '';
            if (stristr($selectField, ' AS ')) {
                $tmpArray = explode(' AS ', $selectField);
                $selectField = $tmpArray[0];
                $aliasString = ' AS `' . $tmpArray[1] . '`';
            } else {
                if (stristr($selectField, '_') || stristr($selectField, '(')) {
                    $aliasString = ' AS `' . $this->createSelectAlias($selectField) . '`';
                }
            }
            $selectFields[] = $selectField . $aliasString;
        }

        $finalFields = implode(', ', $selectFields);

        $distinctAdd = $this->getDistinct() ? 'DISTINCT ' : '';
        return 'SELECT ' . $distinctAdd . $finalFields;
    }

    /**
     * Diese Methode erzeugt den FROM-String für das Query
     *
     * @return string Der fertige FROM-String für das Query
     */
    private function createFromString(): string
    {
        $p = ' ';
        if ($this->isPretty()) {
            $p = PHP_EOL;
        }

        $from = $this->getFrom();
        if (is_a($from, 'PhoenixPhp\Database\Query')) {
            if ($this->isPretty()) {
                $from->setPretty(true);
            }
            $fromString = $from->createQueryString();
        } elseif (is_array($from)) {
            $subString = '';
            foreach ($from as $clause) {
                if (is_a($clause, 'PhoenixPhp\Database\Query')) {
                    if ($this->isPretty()) {
                        $clause->setPretty(true);
                    }
                    $subString .= $clause->createQueryString() . ' ';
                } else {
                    $subString .= $clause . ' ';
                }
            }
            if ($this->getFromAlias() !== null) {
                $fromString = '(' . $p . $subString . ') AS ' . $this->getFromAlias();
            }
        } else {
            $fromString = $this->createSelectField($this->getFrom());
        }
        return 'FROM ' . $fromString;
    }

    /**
     * Diese Methode wandelt die selektierten Parameter in "quotierte" Parameter um.
     *
     * @param string $selectField Der Parameter im Format "table_name.field_name"
     *
     * @return string                Der quotierte Parameter im Format "`table_name`.`field_name`"
     */
    private function createSelectField(string $selectField): string
    {
        $selectParts = explode('.', $selectField);

        $quotedFields = [];
        foreach ($selectParts as $selectPart) {
            if (!stristr($selectPart, 'SubQuery')) {
                $quotedFields[] = '`' . $selectPart . '`';
            } else {
                $quotedFields[] = $selectPart;
            }
        }
        return implode('.', $quotedFields);
    }

    /**
     * Diese Methode generiert den Alias für das selektierte Feld
     *
     * @param string $selectField das selektierte Feld
     *
     * @return string                der neu generierte Alias
     */
    private function createSelectAlias(string $selectField): string
    {
        $selectField = str_replace('DISTINCT', '', $selectField);

        $matches = [];
        $match = preg_match('/(.*?)\(.*`(.*)`/', $selectField, $matches);

        if ($match) {
            $selectField = strtolower($matches[1]) . ' ' . $matches[2];
        }

        $aliasString = str_replace(['.', '_'], ' ', $selectField);
        $aliasString = ucwords($aliasString);
        $aliasString = str_replace(' ', '', $aliasString);
        return lcfirst($aliasString);
    }

    /**
     * Diese Methode erzeugt den JOIN-String für das Query
     *
     * @return string $joinString    Der fertige JOIN-String für das Query
     */
    private function createJoinString(): string
    {
        $joinArray = $this->getJoins();
        $joinString = '';

        if ($joinArray !== null) {
            foreach ($joinArray as $joinTable => $joinProperties) {
                $joinType = $joinProperties['TYPE'];
                $joinConditions = $joinProperties['CONDITIONS'];
                $joinQuery = $joinProperties['SUBQUERY'];
                $joinTableJoin = str_replace(['_sub_query'], '', $joinTable);

                $joinDb = '';
                if ($joinProperties['DB'] !== null) {
                    $joinDb = '`' . $joinProperties['DB'] . '`.';
                }

                $p = ' ';
                if ($this->isPretty()) {
                    $p = PHP_EOL;
                }

                $queryString = '';
                if (isset($joinQuery)) {
                    $joinTableJoin = $this->createSelectAlias($joinTable);
                    $queryString = '(' . $joinQuery->createQueryString() . ')';
                }

                $joinString .= $p . $joinType . ' JOIN ' . $queryString . ' ' . $joinDb . '`' . $joinTableJoin . '` ON (';
                foreach ($joinConditions as $joinParameters) {
                    $joinTable = $joinParameters['joinTable'];
                    $joinConnector = ($joinParameters['joinConnector'] !== null) ? ' ' . $joinParameters['joinConnector'] . ' ' : '';
                    $joinKeys = $joinParameters['joinKeys'];

                    if ($joinKeys[0] === 'none') {
                        $joinString .= $joinDb . $joinConnector . $joinKeys[1];
                    } else {
                        if (is_array($joinKeys[1])) {
                            $arguments = $joinKeys[1];
                            if ($arguments[0] === 'BETWEEN') {
                                $joinString .= $joinDb . $joinConnector . '`' . $joinTable . '`.`' . $joinKeys[0] . '` BETWEEN ' . $arguments[1] . ' AND ' . $arguments[2].'';
                            }
                        } else {
                            $joinData = $this->createSelectField($joinKeys[1]);
                            $joinString .= $joinDb . $joinConnector . '`' . $joinTable . '`.`' . $joinKeys[0] . '` = ' . $joinData;
                        }
                    }
                }
                $joinString .= ') ';
            }
        }
        return $joinString;
    }

    /**
     * Diese Methode erzeugt den WHERE-String für das Query
     *
     * @return string $whereString    Der fertige WHERE-String für das Query
     */
    public function createWhereString(): string
    {
        $keys = $this->getWhere();
        $whereString = '';
        
        if ($keys !== null) {
            $whereArray = [];
            foreach ($keys as $key => $value) {
                if (is_a($value, '\PhoenixPhp\Database\Where')) {
                    $where = $value->createWhere();
                    $whereArray[] = $where;
                } else {
                    $where = $this->addWhere($key, $value);
                    $whereArray[] = $where->createWhere();
                }
            }
            $whereString = 'WHERE ' . implode(' AND ', $whereArray);
        }
        return $whereString;
    }

    /**
     * Diese Methode erzeugt den ORDER BY-String für das Query
     *
     * @return string $orderString    Der fertige ORDER BY-String für das Query
     */
    private function createOrderString(): string
    {
        $orderFields = $this->getOrderFields();
        $orderString = '';
        if ($orderFields !== null) {
            $orderArray = [];
            foreach ($orderFields as $orderEntry) {
                $orderField = $orderEntry['FIELD'];
                $orderDirection = $orderEntry['DIRECTION'];
                $orderArray[] = $orderField . ' ' . $orderDirection;
            }
            $orderString = 'ORDER BY ' . implode(', ', $orderArray);
        }
        return $orderString;
    }

    /**
     * Diese Methode erzeugt den GROUP BY-String für das Query
     *
     * @return string $groupString    Der fertige GROUP BY-String für das Query
     */
    private function createGroupString(): string
    {
        $groupFields = $this->getGroupFields();
        $groupString = '';
        if ($groupFields !== null) {
            $groupString = 'GROUP BY ' . implode(', ', $groupFields) . ' ';
        }
        return $groupString;
    }

    /**
     * Diese Methode erzeugt den LIMIT-String für das Query
     *
     * @return string $limitString    der fertige LIMIT-String für das Query
     */
    private function createLimitString(): string
    {
        $limitString = '';
        if ($this->getLimit() !== null) {
            $limitString = 'LIMIT ' . $this->getLimit();
        }
        return $limitString;
    }


    //---- KOMPLEXE QUERY ERWEITERUNGEN

    /**
     * Diese Methode legt fest, welche Parameter abgefragt werden sollen
     *
     * @param array $keys Ein Array aus Feldern, die selektiert werden sollen
     */
    public function select(array $keys): void
    {
        if (count($keys) === 0) {
            $error = 'Das Array ' . print_r(
                    $keys,
                    true
                ) . ' ist ungültig. Es muss mindestens ein Feld selektiert werden. Ich verwende jetzt `*`.';
            $logger = new Logger();
            $logger->warning($error);
            $keys = ['*'];
        }
        $this->setSelectFields($keys);
    }

    /**
     * Diese Methode legt fest, welche Parameter abgefragt werden sollen
     *
     * @param array $keys Ein Array aus Feldern, die selektiert werden sollen
     */
    public function selectDistinct(array $keys): void
    {
        $this->setDistinct(true);
        $this->select($keys);
    }

    /**
     * @param string|Query|array $value name of the table or Query object, or array of multiple queries
     * @param null|string $as the alias of the result in case of complex queries
     */
    public function from(string|Query|array $value, ?string $as = null): void
    {
        $this->setFrom($value);
        $this->setFromAlias($as);
    }

    /**
     * FIXME - ein Join sollte ein eigenes Objekt sein, um die ganze Array Kacke hier zu vermeiden
     *
     * Diese Methode erzeugt den Array Eintrag für den Join
     *
     * @param string $joinType Der Join-Typ
     * @param string $joinTable Die Tabelle, die verknüpft wird
     * @param string $joinField Das Feld der "gejointen" Tabelle, über das verknüpft wird
     * @param string $linkField Das Feld der Tabelle, das mit der zu joinenden Tabelle verknüpft wird
     * @param ?string $joinConnector Der Connector, der mehrere Bedingungen eines Joins verknüpft
     * @param ?self $subQuery Ein Sub-Query
     * @param ?string $joinDb Die DB, über die die Tabelle verknüpft wird
     */
    private function addJoin(
        string $joinType,
        string $joinTable,
        string $joinField,
        string|array $linkField,
        ?string $joinConnector = null,
        ?self $subQuery = null,
        ?string $joinDb = null
    ): void {
        $joins = $this->getJoins();

        //neu anlegen
        if (!isset($joins[$joinTable]) && $joinConnector === null) {
            $joins[$joinTable] = [
                'TYPE' => $joinType,
                'DB' => $joinDb,
                'CONDITIONS' => [
                    [
                        'joinDb' => $joinDb,
                        'joinTable' => $joinTable,
                        'joinKeys' => [$joinField, $linkField],
                        'joinConnector' => $joinConnector,
                    ]
                ],
                'SUBQUERY' => $subQuery
            ];
        } //Bedingungen hinzufügen
        else {
            $arrayKeys = array_keys($joins);
            $lastKey = end($arrayKeys);
            $lastJoin = end($joins);
            $lastJoin['CONDITIONS'][] = [
                'joinDb' => $joinDb,
                'joinTable' => $joinTable,
                'joinKeys' => [$joinField, $linkField],
                'joinConnector' => $joinConnector
            ];
            $joins[$lastKey] = $lastJoin;
        }
        $this->setJoins($joins);
    }

    /**
     * Diese Methode speichert einen Join für das Query
     *
     * @param string $joinField Das Feld der "zu joinenden" Tabelle, über das verknüpft wird
     * @param string $linkField Das Feld der Tabelle, das mit der zu joinenden Tabelle verknüpft wird
     * @param string $joinType Der Typ des Joins
     */
    public function join(string $joinField, string $linkField, $joinType = 'INNER'): void
    {
        $dotCount = substr_count($joinField, '.');

        if ($linkField !== '1=1') {
            $tmpTable = explode('.', $joinField);
            $tableName = $tmpTable[0];
            $joinField = $tmpTable[1];
        } else {
            $tableName = $joinField;
            $joinField = 'none';
        }
        $joinDbName = null;
        if ($dotCount === 2) {
            $joinDbName = $tmpTable[0];
            $tableName = $tmpTable[1];
            $joinField = $tmpTable[2];
        }

        if ($tableName === $this->getFrom()) {
            $error = 'Die Parameter $joinField und $linkField müssen vertauscht werden - so funktioniert es nicht. Ich ignoriere die Anweisung.';
            $logger = new Logger();
            $logger->warning($error);
            return;
        }

        $this->addJoin($joinType, $tableName, $joinField, $linkField, null, null, $joinDbName);
    }

    /**
     * Diese Methode erweitert den bestehenden Inner Join um eine AND Bedingung
     *
     * @param string $joinField Die Tabelle, zu der der Join aufgebaut wird
     * @param string $linkField Das Feld der Tabelle, das mit der zu joinenden Tabelle verknüpft wird
     * @param string $joinType Der Typ des Joins
     */
    public function andJoin(string $joinField, string|array $linkField, $joinType = 'INNER'): void
    {
        $dotCount = substr_count($joinField, '.');
        $tmpTable = explode('.', $joinField);
        $tableName = $tmpTable[0];
        $joinField = $tmpTable[1];
        $joinDbName = null;
        if ($dotCount === 2) {
            $joinDbName = $tmpTable[0];
            $tableName = $tmpTable[1];
            $joinField = $tmpTable[2];
        }

        $joins = $this->getJoins();

        /*
        if (!isset($joins[$tableName])) {
            $error = 'Es kann keine zusätzliche Bedingung für ' . $tableName . ' generiert werden. Erste Bedingung fehlt (join). Ich ignoriere die Anweisung.';
            $logger = new Logger();
            $logger->warning($error);
            return;
        }
        */

        $this->addJoin($joinType, $tableName, $joinField, $linkField, 'AND', null, $joinDbName);
    }

    /**
     * Diese Methode speichert einen Left (Outer) Join für das Query
     *
     * @param string $joinField Das Feld der "zu joinenden" Tabelle, über das verknüpft wird
     * @param string $linkField Das Feld der Tabelle, das mit der zu joinenden Tabelle verknüpft wird
     */
    public function leftJoin(string $joinField, string $linkField): void
    {
        $this->join($joinField, $linkField, 'LEFT');
    }

    /**
     * TODO
     */
    public function forceLeftJoin(string $joinField): void
    {
        $this->join($joinField, '1=1', 'LEFT');
    }

    /**
     * Diese Methode erweitert den bestehenden Left (Outer) Join um eine AND Bedingung
     *
     * @param string $joinField Die Tabelle, zu der der Join aufgebaut wird
     * @param string $linkField Das Feld der Tabelle, das mit der zu joinenden Tabelle verknüpft wird
     */
    public function andLeftJoin(string $joinField, string|array $linkField): void
    {
        $this->andJoin($joinField, $linkField, 'LEFT');
    }

    /**
     * Diese Methode nutzt ein bestehendes SubQuery als Join
     *
     * @param Query $subQuery Das vorbereitete SubQuery
     * @param string $joinField Das Feld, über das das SubQuery verknüpft werden soll
     * @param string $linkField Das Feld der Tabelle, das mit der zu joinenden Tabelle verknüpft wird
     */
    public function leftJoinSubQuery(self $subQuery, string $joinField, string $linkField, $aliasConcat = ''): void
    {
        $tmpTable = explode('.', $joinField);
        $joinField = $tmpTable[1];
        $this->addJoin(
            'LEFT',
            $subQuery->getFrom() . '_sub_query' . $aliasConcat,
            $joinField,
            $linkField,
            null,
            $subQuery
        );
    }

    /**
     * Diese Methode nutzt ein bestehendes SubQuery als Join
     *
     * @param Query $subQuery Das vorbereitete SubQuery
     * @param string $joinField Das Feld, über das das SubQuery verknüpft werden soll
     * @param string $linkField Das Feld der Tabelle, das mit der zu joinenden Tabelle verknüpft wird
     */
    public function andLeftJoinSubQuery(self $subQuery, string $joinField, string $linkField): void
    {
        $tmpTable = explode('.', $joinField);
        $joinField = $tmpTable[1];
        $this->addJoin('LEFT', $subQuery->getFrom() . '_sub_query', $joinField, $linkField, 'AND', $subQuery);
    }

    /**
     * Diese Methode generiert ein SubQuery für die Abfrage
     *
     * @param string $identifier Die Tabelle, auf die das Select abzielt und gleichzeitig Schlüssel im Array
     */
    public function &subQuery(string $tableName): self
    {
        $subQueries = $this->getSubQueries();
        $subQuery = new self();
        $subQuery->setFrom($tableName);
        if (isset($subQueries[$tableName])) {
            $subQueries[$tableName] = $subQuery;
        } else {
            $subQueries[$tableName . '2'] = $subQuery;
        }
        $this->setSubQueries($subQueries);
        return $subQuery;
    }

    /**
     * Diese Methode speichert die Bedingungen für das Query
     *
     * @param array $keys Key-Value Paare [feld => wert, feld2 => ['>',  wert2]], nach denen gefiltert wird
     */
    public function where(array $keys): void
    {
        foreach ($keys as $key => $value) {
            if (is_a($value, '\PhoenixPhp\DataBase\Where')) {
                $whereArray = $this->getWhere();
                $whereArray[] = $value;
                $this->setWhere($whereArray);
            } else {
                $this->addWhere($key, $value);
            }
        }
    }

    public function addWhere(string $key, $value): Where
    {
        $clauses = $this->getWhere();

        $index = $this->getWhereCounter();
        $index++;
        $this->setWhereCounter($index);

        $where = new Where();
        $where->addClause($index, $key, $value);
        $clauses[] = $where;
        $this->setWhere($clauses);
        return $where;
    }

    /**
     * Diese Methode speichert das Sortierfeld und die Sortierreihenfolge
     *
     * @param string $field Das Feld, nach dem sortiert werden soll
     * @param string $direction Die Sortierrichtung
     */
    public function orderBy(string $field, string $direction = 'ASC'): void
    {
        if (!in_array($direction, self::VALID_ORDER_DIRECTIONS)) {
            $valid = implode(' oder ', self::VALID_ORDER_DIRECTIONS);
            $error = 'Die Sortierrichtung ' . $direction . ' ist ungültig. Muss ' . $valid . ' sein.';
            $logger = new Logger();
            $logger->warning($error);
            $direction = 'ASC';
        }

        $fields = $this->getOrderFields();
        if ($this->getJoins() !== null) {
            $aliasString = str_replace(['.', '_'], ' ', $field);
            $aliasString = ucwords($aliasString);
            $aliasString = str_replace(' ', '', $aliasString);
            $field = lcfirst($aliasString);
        }

        $fields[] = ['FIELD' => $field, 'DIRECTION' => $direction];
        $this->setOrderFields($fields);
    }

    /**
     * Diese Methode speichert das Sortierfeld und die Sortierreihenfolge
     *
     * @param array $groupFields Die Felder, nach denen gruppiert wird
     */
    public function groupBy(array $groupFields): void
    {
        $this->setGroupFields($groupFields);
    }

    /**
     * Diese Methode speichert die Limitierung für das Query
     *
     * @param int $limit Die Anzahl an Datensätzen, die zurück geliefert werden soll
     */
    public function limit(int $limit): void
    {
        $this->setLimit($limit);
    }

    /**
     * Diese Methode generiert die PDO-Parameter für das Query
     *
     * @return array $executionArray    Das Array der Parameter mit entsprechendem PDO Type
     */
    public function createExecutionParameters(): array
    {
        $keys = $this->getWhere();
        $executionArray = [];
        if ($keys !== null) {
            foreach ($keys as $value) {
                $parameters = $value->retrieveParameters();
                $executionArray = array_merge($executionArray, $parameters);
            }
        }

        $subQueries = $this->getSubQueries();
        if ($subQueries !== null) {
            foreach ($subQueries as $subQuery) {
                $keys = $subQuery->getWhere();
                if ($keys !== null) {
                    foreach ($keys as $value) {
                        $parameters = $value->retrieveParameters();
                        $executionArray = array_merge($executionArray, $parameters);
                    }
                    /*
                    foreach($keys as $key => $value) {
                        $clauses = $value->getClauses();
                        print_r($clauses); die('123');

                        $usedValue = $value;
                        if(is_array($value)) {
                            $usedValue = $value[1];
                        }

                        $fix              = str_replace('.', '_', $key);
                        $valuePdoType     = $this->retrieveParameterType($usedValue);
                        $executionArray[] = ['placeholder' => $fix, 'value' => $usedValue, 'type' => $valuePdoType];
                    }
                    */
                }
            }
        }

        return $executionArray;
    }

    /**
     * Diese Methode gibt den PDO-Datentyp eines übergebenen Parameters zurück, damit PDO ihn korrekt kodieren kann
     *
     * @param mixed $value Der Wert, der in die DB gespeichert werden soll
     *
     * @return int            Die id des Datentyps für PDO
     */
    public function retrieveParameterType($value): int
    {
        $usedValue = $value;
        if (is_array($value)) {
            if (!isset($value[1])) {
                $usedValue = $value[0];
            } else {
                $usedValue = $value[1];
            }
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
}