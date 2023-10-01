<?php

namespace nicotine;

/**
| Error class.
*/
final class Error extends Dispatcher {
    
    /**
    | Errors stack.
    | @var array<int, array>
    */
    public array $stack = [];

    /**
    | Class constructor.
    */
    public function __construct() {
        // All errors.
        error_reporting(-1);

        if (Registry::get('config')->errorReporting == 'PRODUCTION_MODE') {
            ini_set('display_errors', false);
            ini_set('log_errors', false);
        } else {
            ini_set('display_errors', true);
            ini_set('log_errors', true);
        }

        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
    | Get Exception and Error context. Useful for logs. Useless for Cli scripts.
    */
    public function getContext(): array
    {
        if (!$this->isCliRequest()) {
            return [
                'page' => $_SERVER['REQUEST_URI'],
                'method' => $_SERVER['REQUEST_METHOD'],
                'referer' => $_SERVER['HTTP_REFERER'] ?? 'No Referer',
                'ip' => $_SERVER['REMOTE_ADDR'],
                'browser' => $_SERVER['HTTP_USER_AGENT']
            ];
        }
        return [];
    }

    /**
    | Handler for errors.
    */
    public function errorHandler(int $level, string $message, string $file, int $line): true
    {
        $error = [
            'type' => 'Error',
            'level' => $level,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'time' => date('H:i:s', time()),
        ];

        $context = $this->getContext();

        if (!empty($context)) {
            $error['context'] = $context;
        }

        $this->stack[] = $error;

        if (in_array($level, [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
        ])) {
            exit;
        }

        return true;
    }

    /**
    | Handler for exceptions.
    */
    public function exceptionHandler($exception): void
    {
        $exception = [
            'type' => 'Exception',
            'level' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'time' => date('H:i:s', time()),
        ];

        $context = $this->getContext();

        if (!empty($context)) {
            $exception['context'] = $context;
        }

        $this->stack[] = $exception;
    }

    /**
    | Get info about error type.
    */
    public function getType(array $error): array
    {
        switch ($error['type']) {
            case 'Error':
                switch ($error['level']) {
                    case E_ERROR:
                    case E_PARSE:
                    case E_CORE_ERROR:
                    case E_COMPILE_ERROR:
                    case E_USER_ERROR:
                        $bgColor = '#e00';
                        $level = '(Error)';
                    break;
                    
                    case E_WARNING:
                    case E_CORE_WARNING:
                    case E_COMPILE_WARNING:
                    case E_USER_WARNING:
                    case E_RECOVERABLE_ERROR:
                        $bgColor = '#f90'; 
                        $level = '(Warning)';
                    break;

                    case E_NOTICE:
                    case E_USER_NOTICE:
                    case E_STRICT:
                        $bgColor = '#069';
                        $level = '(Notice)';
                    break;

                    case E_DEPRECATED:
                    case E_USER_DEPRECATED:
                        $bgColor = '#ff0'; 
                        $level = '(Deprecated)';
                    break;

                    default:
                        $bgColor = '#777';
                        $level = '(Unknown)';
                    break;
                }
            break;

            case 'Exception':
                $bgColor = '#e00';
                $level = ($error['level'] == 0) ? '(Standard)' : '(Custom)';
            break;
        }
        return compact('bgColor', 'level');
    }

    /**
    | Show formatted error.
    | @param bool $returnAsLog for logging purposes
    */
    public function display(bool $returnAsLog = false): string
    {
        $output = '';

        if ($this->isAjaxRequest() || $this->isCliRequest() || $returnAsLog == true) {
            foreach ($this->stack as $error) {
                $info = $this->getType($error);

                $output .= "{$error['type']}! Level: {$error['level']} {$info['level']}, Time: {$error['time']}".PHP_EOL;
                $output .= "Message: \"". str_replace('&quot;', '"', $error['message']) ."\"".PHP_EOL;
                $output .= "File: \"{$error['file']}\", Line: {$error['line']}".PHP_EOL;

                $_context = $this->getContext();
                $context = [];

                foreach ($_context as $key => $value) {
                    $context[] = ucfirst($key) .": ". $value;
                }

                $output .= implode(PHP_EOL, $context) . str_repeat(PHP_EOL, 2);
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

            foreach ($this->stack as $error) {
                $info = $this->getType($error);

                if ($info['level'] == '(Deprecated)') {
                    $color = '#222';
                } else {
                    $color = '#fff';
                }

                $output .= <<<"HEREDOC"
                    <div style="{$style}">
                        <div style="background-color:{$info['bgColor']};color:{$color};padding:1px;">
                            {$error['type']}! Level: {$error['level']} {$info['level']}, Time: {$error['time']}
                        </div>
                        <div style="padding:1px;">
                            {$error['message']}
                        </div>
                        <div style="padding:1px;">
                            File: &quot;{$error['file']}&quot;, Line: {$error['line']}
                        </div>
                    </div>
                HEREDOC;
            }
        }

        return $output;
    }

    /**
    | Log errors and exceptions.
    | @uses {$this->stack}
    */
    public function log(): void
    {
        $errors = $this->display(returnAsLog: true);

        if (empty($errors)) {
            return;
        }

        $date = date('Y-m-d', time());

        if ($this->isSiteRequest()) {
            if ($this->isAjaxRequest()) {
                $file = __DIR__ . '/../workspace/site/logs/' . $date . '_ajax.log';
            } else {
                $file = __DIR__ . '/../workspace/site/logs/' . $date . '_web.log';
            }
        }

        if ($this->isAdminRequest()) {
            if ($this->isAjaxRequest()) {
                $file = __DIR__ . '/../workspace/admin/logs/' . $date . '_ajax.log';
            } else {
                $file = __DIR__ . '/../workspace/admin/logs/' . $date . '_web.log';
            }
        }

        if ($this->isApiRequest()) {
            $file = __DIR__ . '/../workspace/api/logs/' . $date . '.log';
        }

        if ($this->isCronRequest() != false) {
            $file = __DIR__ . '/../workspace/cli/crons/logs/' . $date . '.log';
        }

        if ($this->isScriptRequest() != false) {
            $file = __DIR__ . '/../workspace/cli/scripts/logs/' . $date . '.log';
        }

        if (!empty($file)) {
            // If it works on Windows, wihout realpath(), then it works on all systems :)
            if (file_put_contents($file, $errors, FILE_APPEND) === false) {
                trigger_error('Cannot write to file '. $this->quote() . $file . $this->quote() .'! Please check the path or permissions!', E_USER_ERROR);
            }
        }
    }
}
