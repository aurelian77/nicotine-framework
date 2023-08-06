<?php
declare(strict_types=1);

namespace nicotine;

use \PDO;

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

}
