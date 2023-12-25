<?php
declare(strict_types=1);

namespace nicotine;

use nicotine\Registry;

/**
| Kernel class.
*/
final class Kernel extends Dispatcher {

    /**
    | Class constructor.
    */
    public function __construct()
    {
        $this->checkRequirements();

        if (!$this->isCliRequest()) {
            $this->initPhpHandlers();
        }

        spl_autoload_register([$this, 'registerAutoload']);
    }

    /**
    | Check server/framework requirements.
    */
    public function checkRequirements(): void
    {
        $errors = [];
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $errors[] = 'PHP version should be >= 8.2.0!';
        }

        if (!empty($errors)) {
            trigger_error(implode(PHP_EOL, $errors), E_USER_ERROR);
        }
    }

    /**
    | Initialize PHP handlers.
    */
    public function initPhpHandlers(): void
    {
        session_save_path(realpath(__DIR__ . '/../workspace/sessions/'));
        session_start();

        if (isset($_COOKIE['language']))
        {
            // Prevent navigating up into folders.
            if (strpos($_COOKIE['language'], '..') === false)
            {
                $folder = $this->isAdminRequest() ? 'admin' : 'site';
                $file = __DIR__.'/../workspace/'.$folder.'/langs/'.$_COOKIE['language'].'.php';
                $path = realpath($file);

                if (!empty($path)) {
                    require $path; // Load language file.
                }
            }
        }

        // 1. in this order.
        if (Registry::get('config')->checkCsrfToken == true && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
            if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
                exit('Bad token!');
            }
        }

        // 2. Generate.
        if (Registry::get('config')->checkCsrfToken == true) {
            $this->csrf();
        } else {
            // Default. Avoid notices on Form Builder.
            $_SESSION['csrf'] = '';
        }
    }

    /**
    | [Re]Generate CSRF Token.
    */
    public function csrf()
    {
        $csrf = str_split(md5((string) time()));
        shuffle($csrf);
        $_SESSION['csrf'] = implode('', $csrf);
    }

    /**
    | Register autoload function, for classes. It is for user namespace.
    | @note On Linux, /Foo and /foo are different things.
    */
    public function registerAutoload($class): void
    {
        $file = __DIR__ . '/../' . $class .'.php';
        $path = realpath($file);

        if (!empty($path)) {
            require_once($path);
        } else {
            trigger_error('File '. $this->quote() . $file . $this->quote() .' not found!', E_USER_ERROR);
        }
    }

    public function stats(): string
    {
        $output = '';

        $executionTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 4);
        $memoryPeakUsage = Registry::get('Utils')->formatBytes(memory_get_peak_usage(true));
        $phpVersion = phpversion();

        if ($this->isAjaxRequest() || $this->isCliRequest()) {
            $output .= PHP_EOL . 'Execution Time: '. $executionTime .' seconds'. PHP_EOL;
            $output .= 'Memory Real Peak Usage: '. $memoryPeakUsage . PHP_EOL;
            $output .= 'PHP Version: '. $phpVersion . PHP_EOL;
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

            $output .= <<<"HEREDOC"
                <div style="{$style}">
                    <div style="background-color:#9b28af;color:#fff;padding:1px;">
                        Statistics
                    </div>
                    <div style="padding:1px;">
                        Execution Time: {$executionTime} seconds<br />
                        Memory Real Peak Usage: {$memoryPeakUsage}<br />
                        PHP Version: {$phpVersion}
                    </div>
                </div>
            HEREDOC;
        }
        
        return $output;
    }

    public function __destruct()
    {
        if (!str_starts_with($_SERVER['REQUEST_URI'], '/admin/static/')) {
            if (Registry::get('config')->errorReporting != 'PRODUCTION_MODE') {
                print Registry::get('Error')->display();
            }

            if (Registry::get('config')->errorReporting == 'DEVELOPMENT_MODE') {
                print Registry::get('Database')->display();
            }

            if (Registry::get('config')->logErrors == true) {
                Registry::get('Error')->log();
            }

            if (Registry::get('config')->errorReporting == 'DEVELOPMENT_MODE') {
                print Registry::get('Kernel')->stats();
            }

            Registry::get('Database')->dbh = null;
        }
    }

}
