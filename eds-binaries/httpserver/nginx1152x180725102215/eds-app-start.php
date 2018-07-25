<?php
/**
 * EasyPHP Devserver: a complete development environment
 * @author   Laurent Abbal <laurent@abbal.com>
 * @link     http://www.easyphp.org
 */

// Stop - just in case...
exec('eds-app-stop.exe -accepteula "eds-httpserver"');
exec('eds-app-stop.exe -accepteula "php-cgi"');
sleep(2);

// Start
include(__DIR__ . '\\eds-app-actions.php');
include('conf_httpserver.php');
exec('eds-app-launch "' . dirname(dirname(__DIR__)) . '\php\\' . $conf_httpserver['php_folder'] . '\php-cgi.exe" -b 127.0.0.1:9000');
exec('cd "..\eds-binaries\httpserver\\' . basename(dirname(__FILE__)) . '\" && "' . $_SERVER["DOCUMENT_ROOT"] . '\\eds-app-launch.exe" eds-httpserver.exe');
sleep(2);
?>