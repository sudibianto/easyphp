<?php
$php_command = 'cd "..\eds-binaries\php\\' . $_POST['action']['variable']['php_folder'] . '\" && eds-app-launch php-cgi.exe -b 127.0.0.1:9000';
exec($php_command);
?>