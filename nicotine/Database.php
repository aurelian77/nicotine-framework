<?php
declare(strict_types=1);

namespace nicotine;

use \PDO;
use nicotine\Registry;

/**
| Database class.
*/
class Database extends Dispatcher {

    /**
    | Database handler.
    */
    public ?PDO $dbh;

    /**
    | All database queries.
    */
    public array $queries = [];

    /**
    | Registry::get('map');
    */
    public array $map = [];

    /**
    | Last table used by de builder.
    */
    public string $lastTable;

    /**
    | Query building in progress.
    */
    public string $selectQuery = "SELECT".PHP_EOL;

    /**
    | Class constructor.
    */
    public function __construct()
    {
        $config = Registry::get('config');
        
        switch ($config->errorReporting) {
            case 'DEVELOPMENT_MODE':
                $errMode = PDO::ERRMODE_EXCEPTION;
            break;

            case 'STAGING_MODE':
                $errMode = PDO::ERRMODE_WARNING;
            break;

            case 'PRODUCTION_MODE':
                $errMode = PDO::ERRMODE_SILENT;
            break;
        }

        try {
            $this->dbh = new PDO(
                "mysql:host={$config->dbHost};port={$config->dbPort};dbname={$config->dbName};charset={$config->dbCharset}", 
                $config->dbUser,
                $config->dbPassword, 
                [
                    PDO::ATTR_CASE => PDO::CASE_NATURAL,
                    PDO::ATTR_ERRMODE => $errMode,
                    PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config->dbCharset} COLLATE {$config->dbCollation}"
                ]
            );
        } catch (\PDOException $exception) {
            trigger_error('Could not connect to database!', E_USER_ERROR);
        }

        $this->map = Registry::get('map');
    }

    /**
    | Get a single value.
    */
    public function getValue(string $query, array $params = [])
    {
        return $this->exec('value', $query, $params);
    }

    /**
    | Get a single row.
    */
    public function getRow(string $query, array $params = [])
    {
        return $this->exec('row', $query, $params);
    }

    /**
    | Get a column.
    */
    public function getColumn(string $query, array $params = [])
    {
        return $this->exec('column', $query, $params);
    }

    /**
    | Get unique values.
    */
    public function getUnique(string $query, array $params = [])
    {
        return $this->exec('unique', $query, $params);
    }

    /**
    | Get key - pair.
    */
    public function getPair(string $query, array $params = [])
    {
        return $this->exec('pair', $query, $params);
    }

    /**
    | Get group values.
    */
    public function getGroup(string $query, array $params = [])
    {
        return $this->exec('group', $query, $params);
    }

    /**
    | Get all.
    */
    public function getAll(string $query, array $params = [])
    {
        return $this->exec('all', $query, $params);
    }

    /**
    | Get custom.
    */
    public function getCustom(string $query, array $params = [], int $fetchMode = PDO::FETCH_ASSOC, bool $fetchAll = true)
    {
        return $this->exec('custom', $query, $params, $fetchMode, $fetchAll);
    }

    /**
    | Send e.g. queries like insert, update or delete.
    */
    public function set(string $query, array $params = [])
    {
        return $this->exec('set', $query, $params);
    }

    /**
    | Get last insert id.
    */
    public function getLastInsertId()
    {
        return $this->dbh->lastInsertId();
    }

    /**
    | Execute any query type.
    */
    public function exec(string $fetchType, string $query, array $params = [], int $fetchMode = PDO::FETCH_ASSOC, bool $fetchAll = true)
    {
        $startTime = microtime(true);

        $sth = $this->dbh->prepare($query);
        $sth->execute($params);

        switch($fetchType)
        {
            case 'value':
                $result = $sth->fetchColumn() ?: null;
            break;

            case 'row':
                $result = $sth->fetch() ?: [];
            break;

            case 'column':
                $result = $sth->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
            break;

            case 'unique':
                $result = $sth->fetchAll(PDO::FETCH_UNIQUE) ?: [];
            break;

            case 'pair':
                $result = $sth->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            break;

            case 'group':
                $result = $sth->fetchAll(PDO::FETCH_GROUP) ?: [];
            break;

            case 'all':
                $result = $sth->fetchAll() ?: [];
            break;

            case 'custom':
                if ($fetchAll == true) {
                    $result = $sth->fetchAll($fetchMode);
                } else {
                    $result = $sth->fetch($fetchMode);
                }
            break;

            case 'set':
                $result = $sth->rowCount();
            break;
        }

        $endTime = microtime(true);

        ob_start();
        $oldBuffer = ob_get_clean();

        ob_start();
        $sth->debugDumpParams();
        $newBuffer = ob_get_clean();
        
        print $oldBuffer;

        $string = $query;

        if (!empty($newBuffer)) {
            $emulate = false;
            
            preg_match('/Sent\sSQL\:\s\[[0-9]+\]\s([\s\S]*)\r?\nParams\:\s/m', $newBuffer, $matches);
            
            if (isset($matches) && isset($matches[1])) {
                $string = $matches[1];
                $emulate = true;
            }
            
            if ($emulate == false) {
                preg_match('/SQL\:\s\[[0-9]+\]\s([\s\S]*)\r?\nParams\:\s/m', $newBuffer, $matches);

                if (isset($matches) && isset($matches[1])) {
                    $string = $matches[1];
                }
            }
        }

        $trace = debug_backtrace();

        $this->queries[] = [
            'query' => $string,
            'time' => round(($endTime - $startTime), 4),
            'file' => $trace[1]['file'], 
            'line' => $trace[1]['line']
        ];

        $sth = null;

        return $result;
    }

    /**
    | Display queries.
    */
    public function display()
    {
        $output = '';
        $countQueries = 1;

        if ($this->isAjaxRequest() || $this->isCliRequest()) {
            foreach ($this->queries as $queyData) {
                $output .= PHP_EOL . 'Query number: ' . $countQueries . ', Time took: ' . $queyData['time'] . ' seconds' . PHP_EOL;
                $output .= $queyData['query'] . PHP_EOL;
                $output .= 'File: "' . $queyData['file'] . '", Line: ' . $queyData['line'] . PHP_EOL;

                $countQueries++;
            }
        } else {
            $_style = [
                'font-family' => 'monospace',
                'font-size' => '14px',
                'line-height' => '16px',
                'text-align' => 'left',
                'background-color' => '#eee',
                'border' => '1px solid #ccc',
                'padding' => '0',
                'margin' => '1px',
                'color' => '#222'
            ];

            $style = '';

            foreach ($_style as $key => $value) {
                $style .= "{$key}:{$value};";
            }

            foreach ($this->queries as $queyData) {
                $output .= <<<"HEREDOC"
                    <div style="{$style}">
                        <div style="background-color:#090;color:#fff;padding:1px;">
                            Query number: {$countQueries}, Time took: {$queyData['time']} seconds
                        </div>
                        <div style="padding:1px;">
                            <pre style="margin:0;padding:0;color:#b00;">{$queyData['query']}</pre>
                        </div>
                        <div style="padding:1px;">
                            File: &quot;{$queyData['file']}&quot;, Line: {$queyData['line']}
                        </div>
                    </div>
                HEREDOC;

                $countQueries++;
            }
        }
        return $output;
    }

    public function getPreparedNull($value, $params)
    {
        $value = (is_string($value) && (!isset($params['trim_strings']) || $params['trim_strings'] == true)) ? trim($value) : $value;
        $value = ((!isset($params['force_null']) || $params['force_null'] == true) && empty($value)) ? null : $value;
        return $value;
    }

    /**
    | // Usually return value is the number of affected rows.
    | $this->db->insert([
    |     // Backticks are automatically added.
    |     // Be careful, table name aren't escaped!
    |     'table' => 'users',
    |
    |     // Don't set 'data' => $this->proxy->post()! Don't trust post! Users can inject e.g. "is_admin" => 1, if you send whole post to database.
    |     // Note that empty data is not allowed. You must specify a column. If you want to insert a blank row, send 'id' => null (primary key).
    |     'data' => [
    |         // Typecast is mandatory for all values. The framework must know how to deal with these vaues and proper escape them.
    |         // Backticks are automatically added.
    |
    |         'name' => (string) $this->proxy->post('name', 'unknown'),
    |
    |         // This will be set to null into database, if 'force_null' is set to true (default), in case of an empty value.
    |         'nickname' => (string) $this->proxy->post('nickname'),
    |
    |         'weight' => (int) $this->proxy->post('weight', 10),
    |
    |         // Note that boolean false values are converted to null whereas true it is converted to 1.
    |         'is_owner' => (bool) $this->proxy->post('is_owner'),
    |
    |         'price' => (float) $this->proxy->post('price'),
    |
    |         // You can use null as second array value for specific fields.
    |         // Even if 'force_null' is set to false, the database field will be set to null, in case of an empty value.
    |         'size' => [(int) $this->proxy->post('size'), null],
    |     ],
    |
    |     // Default is true. If 'force_null' is true and 'trim_strings' is true, values like "   " are set to null into database.
    |     'trim_strings' => true,
    |
    |     // Default is true. Force null empty values, e.g. "", "0", 0, null, false - are set to null into database.
    |     'force_null' => true,
    | ]);
    */
    public function buildShortcut(array $params)
    {
        if (empty($params['table'])) {
            trigger_error('You must provide the table name!', E_USER_ERROR);
        }

        if (!is_array($params['data'])) {
            trigger_error('Table data should be an array!', E_USER_ERROR);
        }

        if (empty($params['data'])) {
            trigger_error('Table data should not be empty! You can send one field e.g. `id` = null if you want to insert a blank row!', E_USER_ERROR);
        }

        $build = [];
        $data = [];

        foreach ($params['data'] as $key => $value) {
            if (is_array($value)) {
                if (sizeof($value) == 2) {
                    if (empty($this->getPreparedNull($value[0], $params))) {
                        $value = $value[1];
                    } else {
                        $value = $value[0];
                    }
                } else {
                    trigger_error('Size of array value should be exact 2!', E_USER_ERROR);
                }
            }

            $value = $this->getPreparedNull($value, $params);

            if (is_string($value)) {
                $build[$key] = ":{$key}";
                $data[":{$key}"] = $value;
            }
            elseif (is_int($value) || is_float($value)) {
                $build[$key] = $value;
            }
            elseif (is_bool($value)) {
                switch ($value) {
                    case true:
                        $build[$key] = 1;
                    break;
                    case false:
                        $build[$key] = 'NULL';
                    break;
                }
            }
            elseif (is_null($value)) {
                $build[$key] = 'NULL';
            }
            else {
                trigger_error('Unknown value type, should be scalar!', E_USER_ERROR);
            }
        }

        // Rebuild.
        $array = [];

        foreach ($build as $k => $v) {
            $array[] = "`{$k}` = {$v}";
        }

        return [
            'build' => implode(','.PHP_EOL, $array),
            'data' => $data,
        ];
    }

    /**
    | Insert.
    */
    public function insert($params)
    {
        $shortcut = $this->buildShortcut($params);
        return $this->set("INSERT INTO `{$params['table']}` SET ".PHP_EOL.$shortcut['build'], $shortcut['data']);
    }

    /**
    | "where" isn't mandatory. But you will update all table rows if isn't present!
    | For other $params attribs see insert() method, they are the same.
    | 'trim_strings' and 'force_null' settings has the same behavior as in insert() method.
    | Note that where[0] (field name) isn't escaped.
    | Usually return value is the number of affected rows.
    | 
    | In this case, Nicotine will assume that are you referring to primary key, namelly `id`:
    | 'where' => (int) $this->proxy->get('id') means where `id` = 123
    |
    | 'where' => ['id', (int) "123"] same as above.
    |
    | 'where' => ['name', (string) $this->proxy->post('name')] produces: where `name` = 'John' (automatically escaped)
    | 'where' => ['price', (float) $this->proxy->post('price')] produces: where `price` = 12.34
    |
    | In this example, 10 is default, in case of empty value for size:
    | 'where' => ['size', (int) $this->proxy->post('size', '10')] produces: where `size` = 10 
    |
    | 'where' => ['is_admin', (bool) $this->proxy->post('is_admin', false)] produces: where `is_admin` is null, on empty post 'is_admin' value.
    | 'where' => ['has_category', (bool) $this->proxy->post('has_category')] produces: where `has_category` is not null, if 'has_category' post value is not empty.
    */
    public function buildWhere($params, $shortcut)
    {
        if (empty($params['where'])) {
            return [
                '',
                $shortcut['data']
            ];
        }

        if (is_int($params['where'])) {
            return [
                PHP_EOL."WHERE `id` = ".$params['where'],
                $shortcut['data'],
            ];
        }

        if (is_array($params['where']))
        {
            if (sizeof($params['where']) != 2) {
                trigger_error('Size of array value should be exact 2!', E_USER_ERROR);
            }

            if (is_bool($params['where'][1])) {
                return [
                    PHP_EOL."WHERE `{$params['where'][0]}` IS ".($params['where'][1] == true ? 'NOT ' : '')."NULL",
                    $shortcut['data'],
                ];
            }

            if (is_int($params['where'][1]) || is_float($params['where'][1])) {
                return [
                    PHP_EOL."WHERE `{$params['where'][0]}` = ".$params['where'][1],
                    $shortcut['data'],
                ];
            }

            if (is_string($params['where'][1])) {
                $placeholder = ":_{$params['where'][0]}";
                $shortcut['data'][$placeholder] = $params['where'][1];

                return [
                    PHP_EOL."WHERE `{$params['where'][0]}` = {$placeholder}",
                    $shortcut['data'],
                ];
            }

            trigger_error("Unknown 'where' value type!", E_USER_ERROR);
        }

        trigger_error("Unknown 'where' type!", E_USER_ERROR);
    }

    /**
    | Update.
    */
    public function update($params)
    {
        $shortcut = $this->buildShortcut($params);
        $buildWhere = $this->buildWhere($params, $shortcut);

        return $this->set("UPDATE `{$params['table']}` SET ".PHP_EOL.$shortcut['build'].$buildWhere[0], $buildWhere[1]);
    }

    /**
    | Delete.
    */
    public function delete($params)
    {
        $params['data'] = ['id' => null]; // Dummy fill.

        $shortcut = $this->buildShortcut($params);
        $buildWhere = $this->buildWhere($params, $shortcut);

        return $this->set("DELETE FROM `{$params['table']}`".$buildWhere[0], $buildWhere[1]);
    }

    /**
    | Select.
    */
    public function select(string $select): object
    {
        $this->selectQuery .= $select.PHP_EOL;
        return $this;
    }

    /**
    | Select ... from.
    */
    public function from(string $table): object
    {
        $this->selectQuery .= "FROM `".$table."`".PHP_EOL;
        $this->lastTable = $table;
        return $this;
    }

    /**
    | Get join table.
    */
    public function getJoinTable(string $tableSearchFor): array
    {
        if (!empty($this->map[$tableSearchFor][$this->lastTable])) {
            $return = $this->map[$tableSearchFor][$this->lastTable];
            $return['type'] = 'child-to-parent';
            return $return;
        }
        elseif (!empty($this->map[$this->lastTable][$tableSearchFor])) {
            $return = $this->map[$this->lastTable][$tableSearchFor];
            $return['type'] = 'parent-to-child';
            return $return;
        }
        trigger_error("Table '{$tableSearchFor}' not found in database map, or is not related to joined tables, "
            ."or you should change the join order, or specify the viaTable as the second argument!", 
            E_USER_ERROR
        );
    }

    /**
    | Join.
    */
    public function join(string $table, string $joinType, string $viaTable): void
    {
        if (!empty($viaTable)) { 
            $this->lastTable = $viaTable;
        }

        $found = $this->getJoinTable($table);

        $this->selectQuery .= "{$joinType} JOIN `{$table}`".PHP_EOL;

        if ($found['type'] == 'child-to-parent') {
            $this->selectQuery .=  "ON (`{$this->lastTable}`.`".$found['link']."` = `{$table}`.`id`)".PHP_EOL;
        }
        elseif ($found['type'] == 'parent-to-child') {
            $this->selectQuery .= "ON (`{$table}`.`".$found['link']."` = `{$this->lastTable}`.`id`)".PHP_EOL;
        }

        $this->lastTable = $table;
    }

    /**
    | Join Assoc.
    */
    public function joinAssoc(string $table, string $joinType, string $viaTable): void
    {
        if (!empty($viaTable)) { 
            $this->lastTable = $viaTable;
        }

        $found = $this->getJoinTable($table);

        $this->selectQuery .= "{$joinType} JOIN `{$found['pivot']}`".PHP_EOL;

        if ($found['type'] == 'child-to-parent')
        {
            $this->selectQuery .= "ON (`{$found['pivot']}`.`".$found['parentLink']."` = `{$this->lastTable}`.`id`)".PHP_EOL;

            $this->selectQuery .= "{$joinType} JOIN `{$table}`".PHP_EOL;

            $this->selectQuery .= "ON (`{$found['pivot']}`.`".$found['link']."` = `{$table}`.`id`)".PHP_EOL;
        }
        elseif ($found['type'] == 'parent-to-child')
        {
            $this->selectQuery .= "ON (`{$found['pivot']}`.`".$found['link']."` = `{$this->lastTable}`.`id`)".PHP_EOL;

            $this->selectQuery .= "{$joinType} JOIN `{$table}`".PHP_EOL;

            $this->selectQuery .= "ON (`{$found['pivot']}`.`".$found['parentLink']."` = `{$table}`.`id`)".PHP_EOL;
        }

        $this->lastTable = $table;
    }

    /**
    | Inner join.
    */
    public function innerJoin(string $table, string $viaTable = ''): object
    {
        $this->join($table, 'INNER', $viaTable);
        return $this;
    }

    /**
    | Left join.
    */
    public function leftJoin(string $table, string $viaTable = ''): object
    {
        $this->join($table, 'LEFT', $viaTable);
        return $this;
    }

    /**
    | Inner assoc.
    */
    public function innerAssoc(string $table, string $viaTable = ''): object
    {
        $this->joinAssoc($table, 'INNER', $viaTable);
        return $this;
    }

    /**
    | Left assoc.
    */
    public function leftAssoc(string $table, string $viaTable = ''): object
    {
        $this->joinAssoc($table, 'LEFT', $viaTable);
        return $this;
    }

    /**
    | Where ...
    */
    public function where(string $where): object
    {
        $this->selectQuery .= "WHERE {$where}".PHP_EOL;
        return $this;
    }

    /**
    | Query.
    */
    public function query(): string
    {
        $return = $this->selectQuery;

        // Reset query for the next select.
        $this->selectQuery = "SELECT".PHP_EOL;

        return $return;
    }

    /**
    | Group by.
    */
    public function groupBy(string $groupBy): object
    {
        $this->selectQuery .= "GROUP BY {$groupBy}".PHP_EOL;
        return $this;
    }

    /**
    | Having.
    */
    public function having(string $having): object
    {
        $this->selectQuery .= "HAVING {$having}".PHP_EOL;
        return $this;
    }

    /**
    | Order by.
    */
    public function orderBy(string $orderBy): object
    {
        $this->selectQuery .= "ORDER BY {$orderBy}".PHP_EOL;
        return $this;
    }

    /**
    | Limit.
    */
    public function limit(string $limit): object
    {
        $this->selectQuery .= "LIMIT {$limit}".PHP_EOL;
        return $this;
    }

    /**
    | Custom.
    */
    public function custom(string $custom): object
    {
        $this->selectQuery .= "{$custom}".PHP_EOL;
        return $this;
    }

}
