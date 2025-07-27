<?php
define("DB_HOST","localhost");
define("DB_NAME","sistemacultivos");
define("DB_USERNAME","root");
define("DB_PASSWORD","admin");
define("DB_ENCODE","utf8");
define("PRO_NOMBRE","");

// Configuración de errores para desarrollo
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../php_error.log');

// Configurar nivel de error reporting
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// Función para logging personalizado
function logError($message, $file = '', $line = '') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ERROR: $message";
    if ($file) $logMessage .= " in $file";
    if ($line) $logMessage .= " on line $line";
    $logMessage .= PHP_EOL;
    
    error_log($logMessage, 3, dirname(__FILE__) . '/../php_error.log');
}

// Función para logging de AJAX y operaciones críticas
function logOperation($operation, $user_id = null, $details = '') {
    $timestamp = date('Y-m-d H:i:s');
    $user_info = $user_id ? "User ID: $user_id" : "No user";
    $logMessage = "[$timestamp] OPERATION: $operation | $user_info";
    if ($details) $logMessage .= " | Details: $details";
    $logMessage .= PHP_EOL;
    
    error_log($logMessage, 3, dirname(__FILE__) . '/../php_error.log');
}

// Función para logging de errores de AJAX
function logAjaxError($ajax_action, $error_message, $user_id = null) {
    $timestamp = date('Y-m-d H:i:s');
    $user_info = $user_id ? "User ID: $user_id" : "No user";
    $logMessage = "[$timestamp] AJAX ERROR: $ajax_action | $user_info | Error: $error_message" . PHP_EOL;
    
    error_log($logMessage, 3, dirname(__FILE__) . '/../php_error.log');
}
?>