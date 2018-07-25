<?php
/**
 * EasyPHP Devserver: a complete development environment
 * @author   Laurent Abbal <laurent@abbal.com>
 * @link     http://www.easyphp.org
 */

exec('eds-app-stop.exe -accepteula "eds-httpserver"');
exec('eds-app-stop.exe -accepteula "php-cgi"');
sleep(2);
?>