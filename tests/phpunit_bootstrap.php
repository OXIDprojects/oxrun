<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 2019-05-04
 * Time: 12:24
 */

register_shutdown_function(function() {
    $error = error_get_last();
    if( $error !== NULL) {
        // only log fatal-type errors; all other errors will be handled by set_error_handler() above.
        $handledErrorTypes = [E_ERROR, E_WARNING, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR, E_USER_ERROR];
        if (!in_array($error['type'], $handledErrorTypes)) {
                return;
        }

        echo "\nFatal PHP error encountered: \n";
        echo "{$error['message']} on {$error['file']}:{$error['line']}\n";
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        echo "memory_limit = " . ini_get("memory_limit") . "\n";
        exit(2);
    }
});
