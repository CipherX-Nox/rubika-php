<?php

require 'rubika.php';


$auth = $_SERVER['HTTP_AUTH'];
$key = $_SERVER['HTTP_KEY'];
$method = $_SERVER['HTTP_METHOD'];

$privateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
$private
-----END RSA PRIVATE KEY-----
EOD;


$bot = new rubika($auth, $private_key);

$inputData = file_get_contents("php://input");
$jsonData = json_decode($inputData, true);

if ($method === 'getChats') {
    echo $bot->getChats();
}
else {
    echo "Invalid method";
}

?>