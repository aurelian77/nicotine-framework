<?php
declare(strict_types=1);

namespace nicotine;

/**
| Dispatcher for console commands.
*/
class Console {
    /**
    | Console $argc and $argv filled values.
    */
    public array $argv = [];
    public int $argc = 0;

    /**
    | Console <group>:<command>
    */
    public string $group = '';
    public string $command = '';

    /**
    | Console ...[argument] [-option]
    */
    public string $argument = '';
    public string $option = '';

    /**
    | Class constructor.
    */
    public function __construct()
    {
        $this->argv = $_SERVER['argv'];
        $this->argc = $_SERVER['argc'];

        if ($this->checkCommand() == true) {
            $this->dispatchCommand();
        }
    }

    /**
    | Fill this properties, check them and return bool.
    */
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

    /**
    | Show help on failure and return always false.
    */
    public function showHelp(): false
    {
        print <<<'NOWDOC'
        Unknown command. Please check documentation! Synopsis:
        php console <grup>:<command> [argument] [-option]
        NOWDOC;

        return false;
    }

    /**
    | Dispatch and execute console command.
    */
    public function dispatchCommand()
    {
        // Cron.
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

        // Script.
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

        // Fresh database.
        if ($this->group == 'db' && $this->command == 'fresh') {
            $db = namespace\Registry::get('Database');
            $db->set(file_get_contents(realpath(__DIR__.'/../workspace/db/db.sql')));
        }

        // Clear different types of logs.
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

        // Clear all logs.
        if ($this->group == 'clear' && $this->command == 'logs') {
            $this->command = 'log';

            foreach (['admin', 'site', 'api', 'crons', 'scripts'] as $argument) {
                $this->argument = $argument;
                $this->dispatchCommand();
            }
        }

        // Clear sessions.
        if ($this->group == 'clear' && $this->command == 'sessions') {
            empty_directory(realpath(__DIR__.'/../workspace/sessions'));
        }

        // Generate controller.
        if ($this->group == 'make' && $this->command == 'controller') {
            if (empty($this->argument)) {
                trigger_error('Please specify the controller name!', E_USER_ERROR);
            }

            if (!in_array($this->option, ['-admin', '-site'])) {
                trigger_error('Unknown option. Please check documentation!', E_USER_ERROR);
            }

            $file = __DIR__.'/../workspace/'.str_replace('-', '', $this->option).'/controllers/'.$this->argument.'.php';
            $path = realpath($file);

            if (!empty($path)) {
                trigger_error("Controller '{$path}' already exists. Please choose another name!", E_USER_ERROR);
            }

            // Generate Admin controller.
            if ($this->option == '-admin') {
                $data = '<?php
declare(strict_types=1);

namespace workspace\admin\controllers;

use nicotine\Controller;
use nicotine\RequestMethod;
use nicotine\AdminRoles;
use nicotine\Registry;

class '.$this->argument.' extends Controller {

    public function __construct()
    {
        parent::__construct();
        $this->proxy->layout = \'\';
    }

    #[RequestMethod(\'get\')]
    #[AdminRoles(\'super_admin\')]
    public function index()
    {
        d($this->proxy);
        d($this->db);
    }
}
';
            }

            // Generate Site controller.
            if ($this->option == '-site') {
                $data = '<?php
declare(strict_types=1);

namespace workspace\site\controllers;

use nicotine\Controller;
use nicotine\RequestMethod;
use nicotine\Registry;

class '.$this->argument.' extends Controller 
{
    public function __construct()
    {
        parent::__construct();
        $this->proxy->layout = \'\';
    }

    #[RequestMethod(\'get\')]
    public function index()
    {
        d($this->proxy);
        d($this->db);
    }
}
';
            }

            file_put_contents($file, $data);
        }

        // Generate model.
        if ($this->group == 'make' && $this->command == 'model') {
            if (empty($this->argument)) {
                trigger_error('Please specify the model name!', E_USER_ERROR);
            }

            if (!in_array($this->option, ['-admin', '-site'])) {
                trigger_error('Unknown option. Please check documentation!', E_USER_ERROR);
            }

            $option = str_replace('-', '', $this->option);

            $file = __DIR__.'/../workspace/'.$option.'/models/'.$this->argument.'.php';
            $path = realpath($file);

            if (!empty($path)) {
                trigger_error("Model '{$path}' already exists. Please choose another name!", E_USER_ERROR);
            }

            $data = '<?php
declare(strict_types=1);

namespace workspace\\'.$option.'\\models;

use nicotine\Model;

class '.$this->argument.' extends Model {

}
';
            file_put_contents($file, $data);
        }

        // Generate Api || Cron || Script.
        if ($this->group == 'make' && in_array($this->command, ['api', 'cron', 'script'])) {
            if (empty($this->argument)) {
                trigger_error('Please specify the '.$this->command.' name!', E_USER_ERROR);
            }

            switch ($this->command) {
                case 'api':
                    $dir = 'api';
                break;

                case 'cron':
                    $dir = 'cli/crons';
                break;

                case 'script':
                    $dir = 'cli/scripts';
                break;
            }

            $file = __DIR__.'/../workspace/'.$dir.'/'.$this->argument.'.php';
            $path = realpath($file);

            if (!empty($path)) {
                trigger_error(ucfirst($this->command)." '{$path}' already exists. Please choose another name!", E_USER_ERROR);
            }

            $data = '<?php
declare(strict_types=1);

namespace workspace\\'.str_replace('/', '\\', $dir).';

use nicotine\Registry;

$db = Registry::get(\'Database\');
$proxy = Registry::get(\'Proxy\');
';
            file_put_contents($file, $data);
        }

    }
}