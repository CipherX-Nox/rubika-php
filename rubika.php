<?php
require 'encryption.php';
require 'vendor/autoload.php';

class Rubika {
    private $auth;
    private $key;
    private $encryption;
    private $auth_send;
    private $app_version = "3.3.2";
    private $api_version = "6";
    private $url = "https://messengerg2c58.iranlms.ir/";
    private $resultX;

    public function __construct($auth, $private_key) {
        $this->auth = $auth;
        $this->key = $private_key;
        $this->encryption = new Encryption($auth, $private_key);
        $this->auth_send = $this->encryption->changeAuthType($auth);
    }

    public function makeRequests($method, $temp_code, $data) {
        $createData = function ($method, $data, $temp_code) {
            return [
                "input" => $data,
                "client" => [
                    "app_name" => "Main",
                    "app_version" => $this->app_version,
                    "lang_code" => "fa",
                    "package" => "app.rbmain.a",
                    "temp_code" => $temp_code,
                    "platform" => "Android"
                ],
                "method" => $method
            ];
        };
        $data = json_encode($createData($method, $data, $temp_code)); 
        $data_enc = $this->encryption->encrypt($data); 
        $sign = $this->encryption->sign($this->key, $data_enc); 
        $client = new \GuzzleHttp\Client([
            'verify' => false,
        ]);

        $Cipher = null;
        while ($Cipher === null) {
            $requestData = [
                "api_version" => $this->api_version,
                "auth" => $this->auth_send,
                "data_enc" => $data_enc,
                "sign" => $sign
            ];
            $response = $client->post($this->url, [
                'json' => $requestData,
                'headers' => [
                    'Origin' => 'https://web.rubika.ir',
                    'Referer' => 'https://web.rubika.ir/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/113.0'
                ]
            ]);

            $Cipher = $response->getBody()->getContents();

            if (json_decode($Cipher)->data_enc) {
                $Cipher = $this->encryption->decrypt(json_decode($Cipher)->data_enc);
            } else {
                $Cipher = null;
            }
        }

        return $Cipher;
    }
    public function requestSendFile($file) {
        $method = "sendMessage";
        $tempCode = "2";
        $inputData = [
            "is_mute" => false,
            "object_guid" => $chatId,
            "rnd" => mt_rand(100000, 999999),
            "text" => $text
        ];
        $response = $this->makeRequests($method, $tempCode, $inputData);
    
        return $response;
    }
    public function uploadFile($file) {
        if (strpos($file, "http") === 0) {
            return "Invalid file path";
        }
    
        $upload_info = $this->requestSendFile($file)->wait();
        
        if (!isset($upload_info['data'])) {
            return "Upload information is missing";
        }
    
        $byte_data = file_get_contents($file);
        $id = $upload_info['data']['id'];
        $access_hash_send = $upload_info['data']['access_hash_send'];
        $url = $upload_info['data']['upload_url'];
    
        if (strlen($byte_data) <= 131072) {
            $headers = [
                'auth' => $this->auth,
                'file-id' => $id,
                'access-hash-send' => $access_hash_send,
                'chunk-size' => strlen($byte_data),
                'content-length' => strlen($byte_data),
                'part-number' => '1',
                'total-part' => '1'
            ];
    
            try {
                $response = json_decode(file_get_contents($url, false, stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'content' => $byte_data
                    ]
                ])), true);
    
                return [
                    "access_hash_rec" => $response['data']['access_hash_rec'],
                    "file_id" => $upload_info['data']['id'],
                    "dc" => $upload_info['data']['dc_id']
                ];
            } catch (Exception $e) {
                print($e);
                return str($e);
            }
        } else {
            $total_part = (strlen($byte_data) - 1) / 131072 + 1;
    
            $parts = str_split($byte_data, 131072);
    
            foreach ($parts as $part_number => $part_data) {
                $headers = [
                    'auth' => $this->auth,
                    'file-id' => $id,
                    'access-hash-send' => $access_hash_send,
                    'accept-encoding' => 'qzip',
                    'chunk-size' => strlen($part_data),
                    'content-length' => strlen($part_data),
                    'part-number' => str($part_number + 1),
                    'total-part' => str($total_part)
                ];
    
                try {
                    $start_time = microtime(true);
                    $response = json_decode(file_get_contents($url, false, stream_context_create([
                        'http' => [
                            'method' => 'POST',
                            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                            'content' => $part_data
                        ]
                    ])), true);
    
                    $elapsed_time = microtime(true) - $start_time;
                    $remaining_time = ($total_part - $part_number) * $elapsed_time;
                    $upload_speed = strlen($part_data) / $elapsed_time / 1024 / 1024;
    
                    print("Part " . ($part_number + 1) . "/" . $total_part . " uploaded. Remaining time: " . $this->convert_seconds($remaining_time) . ". Upload Speed: " . number_format($upload_speed, 2) . " MBPS");
                } catch (Exception $e) {
                    print($e);
                }
            }
    
            return [
                "access_hash_rec" => $response['data']['access_hash_rec'],
                "file_id" => $upload_info['data']['id'],
                "dc" => $upload_info['data']['dc_id']
            ];
        }
    }
    
    public function sendMessage($chatId, $text) {
        $method = "sendMessage";
        $tempCode = "2";
        $inputData = [
            "is_mute" => false,
            "object_guid" => $chatId,
            "rnd" => mt_rand(100000, 999999),
            "text" => $text
        ];
        $response = $this->makeRequests($method, $tempCode, $inputData);
    
        return $response;
    }
    public function getChats() {
        $method = "getChats";
        $tempCode = "2";
        $inputData = [];
        $response = $this->makeRequests($method, $tempCode, $inputData);
        return $response;
    }
}


?>