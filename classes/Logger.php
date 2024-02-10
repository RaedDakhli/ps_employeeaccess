<?php

// logging/Logger.php

class Logger
{
    /**
     * Logs a message to a file.
     *
     * @param string $message The message to log.
     */
    public static function log($message)
    {
        // Define the log file path
        $logFilePath = _PS_MODULE_DIR_ . 'ps_employeeaccess/logs/ps_employeeaccess_' . date('dmY') . '.log';

        // Format the log message with timestamp
        $formattedMessage = date('Y-m-d H:i:s') . ': ' . $message . PHP_EOL;

        // Check if the log directory exists, create it if not
        if (!is_dir(_PS_MODULE_DIR_ . 'ps_employeeaccess/logs')) {
            mkdir(_PS_MODULE_DIR_ . 'ps_employeeaccess/logs', 0777, true);
        }

        // Write the log message to the file
        if (error_log($formattedMessage, 3, $logFilePath)) {
            // Log write successful
            error_log($formattedMessage, 3, $logFilePath);
        } else {
            // Log write failed
            error_log("error on log function ", 3, $logFilePath);
        }
    }
}
