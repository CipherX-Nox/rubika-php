<?php

require 'rubika.php';


$auth = '';
$key = '';

$privateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
$key
-----END RSA PRIVATE KEY-----
EOD;


$bot = new rubika($auth, $privateKey);

echo $bot->getChats();
?>
