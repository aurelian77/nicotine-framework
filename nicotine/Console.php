<?php
declare(strict_types=1);

namespace nicotine;

class Console {
    public array $argv = [];
    public int $argc = 0;

    public string $group = '';
    public string $command = '';

    public string $argument = '';
    public string $option = '';

    public function __construct()
    {
        $this->argv = $_SERVER['argv'];
        $this->argc = $_SERVER['argc'];

        if ($this->checkCommand() == true) {
            $this->dispatchCommand();
        }
    }

    public function checkCommand(): bool
    {
        if ($this->argc == 1) {
            return $this->showHelp();
        }

        if (preg_match('/^[^\s]+\:[^\s]+$/i', $this->argv[1])) {
            $do = explode(':', trim($this->argv[1]));

            $this->group = trim($do[0]);
            $this->command = trim($do[1]);
        } else {
            return $this->showHelp();
        }

        if (!empty($this->argv[2])) {
            if (preg_match('/^\-[^\s]+$/i', $this->argv[2])) {
                $this->option = trim($this->argv[2]);
            } else {
                $this->argument = trim($this->argv[2]);
            }
        } else {
            return true;
        }

        if (!empty($this->argv[3])) {
            if (preg_match('/^\-[^\s]+$/i', $this->argv[3])) {
                $this->option = trim($this->argv[3]);
                return true;
            } else {
                return $this->showHelp();
            }
        } else {
            return true;
        }
    }

    public function showHelp(): false
    {
        print <<<'NOWDOC'
        Unknown command. Please check documentation! Synopsis:
        php console <grup>:<command> [argument] [-option]
        NOWDOC;

        return false;
    }

    public function dispatchCommand()
    {
        if ($this->group == 'cron') {
            $file = __DIR__ . '/../workspace/cli/crons/'. $this->command .'.php';
            $path = realpath($file);

            if (!empty($path)) {
                require $file;
                exit;
            } else {
                trigger_error('Cron "'. $file .'" not found!', E_USER_ERROR);
            }
        }

        if ($this->group == 'script') {
            $file = __DIR__ . '/../workspace/cli/scripts/'. $this->command .'.php';
            $path = realpath($file);

            if (!empty($path)) {
                require $file;
                exit;
            } else {
                trigger_error('Script "'. $file .'" not found!', E_USER_ERROR);
            }
        }

        if ($this->group == 'db' && $this->command == 'fresh') {
            $db = namespace\Registry::get('Database');
            $db->set(file_get_contents(realpath(__DIR__.'/../workspace/db/db.sql')));
        }

        if ($this->group == 'clear' && $this->command == 'log') {

            $dir = '';

            switch ($this->argument) {
                case 'admin':
                    $dir = realpath(__DIR__.'/../workspace/admin/logs');
                break;

                case 'site':
                    $dir = realpath(__DIR__.'/../workspace/site/logs');
                break;

                case 'api':
                    $dir = realpath(__DIR__.'/../workspace/api/logs');
                break;

                case 'crons':
                    $dir = realpath(__DIR__.'/../workspace/cli/crons/logs');
                break;

                case 'scripts':
                    $dir = realpath(__DIR__.'/../workspace/cli/scripts/logs');
                break;
            }

            if (empty($dir)) {
                trigger_error("Unknown argument '{$this->argument}'. Please check documentation!", E_USER_ERROR);
            }

            empty_directory($dir);
        }
    }
}