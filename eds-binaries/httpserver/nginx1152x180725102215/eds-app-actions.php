<?php
/**
 * EasyPHP Devserver: a complete development environment
 * @author   Laurent Abbal <laurent@abbal.com>
 * @link     http://www.easyphp.org
 */


 // Variables
$server_port = '';
$php_folder = '';

if (isset($_POST['action']['variable']['server_port']) AND isset($_POST['action']['variable']['php_folder'])) {	
	$server_port = $_POST['action']['variable']['server_port'];
	$php_folder = $_POST['action']['variable']['php_folder'];		
} else {
	if (isset($conf_httpserver['httpserver_folder']) AND strstr($conf_httpserver['httpserver_folder'],'apache')) {
		
		$php_folder = $conf_httpserver['php_folder'];
		
		// Check if port is available
		function check_port($port) {
			$conn = @fsockopen("127.0.0.1", $port, $errno, $errstr, 0.2);
			if ($conn) {
				fclose($conn);
				return true;
			}
		}

		// Array of available ports
		$ports = array(80,8080,8000,8888,8008, 8118, 8181, 8111);
		$ports_available = array();
		foreach ($ports as $port){
			if (!check_port($port)) $ports_available[] = $port;
		}
		
		if (!in_array($conf_httpserver['httpserver_port'],$ports_available)) {
			$server_port = $ports_available[0];
		} else {
			$server_port = $conf_httpserver['httpserver_port'];
		}
	
	}
}
 

if (($server_port !== '') AND ($php_folder !== '')) {

	// HTTP CONFIGURATION FILES

	// Update nginx.conf
	$serverconffile = file_get_contents(__DIR__ . '\conf\nginx.conf');

		// Listen - port
		$replacement = '${1}' . $server_port . '$3';
		$serverconffile = preg_replace('/^([\s|\t]*listen[\s|\t]*)(.*)(;[\s|\t]*)$/m', $replacement, $serverconffile);
		
		// Root - eds-www
		$replacement = '${1}' . str_replace('\\', '/', dirname(dirname(dirname(__DIR__)))) . '$3';
		$serverconffile = preg_replace('/^(.*root[\s|\t]*\")(.*)(\/eds-www.*)$/m', $replacement, $serverconffile);	

		// Alias - eds-modules
		$replacement = '${1}' . str_replace('\\', '/', dirname(dirname(dirname(__DIR__)))) . '$3';
		$serverconffile = preg_replace('/^([\s|\t]*alias[\s|\t]*\")(.*)(\/eds-modules.*)$/m', $replacement, $serverconffile);
		
	file_put_contents (__DIR__ . '\conf\nginx.conf', $serverconffile);


	// Update nginx-alias.conf
	$alias_serialized = file_get_contents('store_alias.php');
	$store_alias = '';
	if ($alias_serialized != '') {
		foreach (unserialize($alias_serialized) as $key => $alias) {
			$alias_link = str_replace("\\","/", urldecode($alias['alias_path']));
			$alias_link = str_replace("//","/", $alias_link);
			if (substr($alias_link, -1) == "/"){$alias_link = substr($alias_link,0,strlen($alias_link)-1);}
			$store_alias .= "location \"/" . $alias['alias_name'] . "\" {\r\n";
			$store_alias .= "\talias \"" . $alias_link . "\";\r\n";
			$store_alias .= "\tindex  index.php index.html index.htm;\r\n";
			$store_alias .= "\tautoindex on;\r\n";
			$store_alias .= "\tlocation ~ \"/" . $alias['alias_name'] . "(.*\.php)$\" {\r\n";
			$store_alias .= "\t\tfastcgi_pass   127.0.0.1:9000;\r\n";
			$store_alias .= "\t\tfastcgi_index  index.php;\r\n";
			$store_alias .= "\t\tfastcgi_param  SCRIPT_FILENAME".' $document_root$1'.";\r\n";
			$store_alias .= "\t\tinclude fastcgi_params;\r\n";
			$store_alias .= "\t}\r\n";
			//$store_alias .= "\tlocation ~ /\.ht {";
			//$store_alias .= "\t\tdeny  all;";
			//$store_alias .= "\t};";
			$store_alias .= "}\r\n";
		}
	}
	file_put_contents(__DIR__ . '\conf\nginx-alias.conf', $store_alias);
		

	// Update nginx-vhosts.conf	
	$vhosts_serialized = file_get_contents('store_vhosts.php');
	$store_vhosts = '';	
	if ($vhosts_serialized != '') {
		foreach (unserialize($vhosts_serialized) as $key => $vhost) {
			$store_vhosts .= "server\r\n";
			$store_vhosts .= "{\r\n";
			$store_vhosts .= "\tlisten " .  $server_port . ";\r\n";
			$store_vhosts .= "\tserver_name " .  $vhost['vhost_name'] . ";\r\n";
			$store_vhosts .= "\t#access_log /var/log/nginx/example.com.access.log;\r\n";
			$store_vhosts .= "\t#error_log /var/log/nginx/example.com.error.log;\r\n";
			$store_vhosts .= "\tlocation / {\r\n";
			$store_vhosts .= "\t\troot \"" .  urldecode($vhost['vhost_link']) . "/\";\r\n";
			$store_vhosts .= "\t\tindex index.php index.html index.htm;\r\n";
			$store_vhosts .= "\t\tautoindex on;\r\n";
			$store_vhosts .= "\t}\r\n";
			$store_vhosts .= "\t# use fastcgi for all php files\r\n";
			$store_vhosts .= "\tlocation ~ \.php$\r\n";
			$store_vhosts .= "\t{\r\n";
			$store_vhosts .= "\t\tfastcgi_pass 127.0.0.1:9000;\r\n";
			$store_vhosts .= "\t\tfastcgi_index index.php;\r\n";
			$store_vhosts .= "\t\t" . 'fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;' . "\r\n";
			$store_vhosts .= "\t\tinclude fastcgi_params;\r\n";
			$store_vhosts .= "\t}\r\n";
			//$store_vhosts .= "\t# deny access to apache .htaccess files\r\n";
			//$store_vhosts .= "\tlocation ~ /\.ht\r\n";
			//$store_vhosts .= "\t{\r\n";
			//$store_vhosts .= "\t\tdeny all;\r\n";
			//$store_vhosts .= "\t}\r\n";
			$store_vhosts .= "}\r\n";
		}
	}
	file_put_contents(__DIR__ . '\conf\nginx-vhosts.conf', $store_vhosts);	
	

	// PHP CONFIGURATION
	include('..\eds-binaries\php\\' . $php_folder . '\eds-app-actions.php');


	// CONF_HTTPSERVER.PHP
	$conf_httpserver_content = '<?php' . "\r\n";
	$conf_httpserver_content .= '$conf_httpserver = array();' . "\r\n";
	$conf_httpserver_content .= '$conf_httpserver = array(' . "\r\n";
	$conf_httpserver_content .= "\t" . '"httpserver_folder" => "' . basename(__DIR__) . '",' . "\r\n";
	$conf_httpserver_content .= "\t" . '"httpserver_port" => "' . $server_port . '",' . "\r\n";
	$conf_httpserver_content .= "\t" . '"php_folder" => "' . $php_folder . '",' . "\r\n";
	$conf_httpserver_content .= ');' . "\r\n";
	$conf_httpserver_content .= '?>';
	file_put_contents ('conf_httpserver.php', $conf_httpserver_content);
}
?>