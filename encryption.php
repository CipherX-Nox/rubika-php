<?php
use phpseclib\Crypt\AES;
use phpseclib\Crypt\RSA;
use phpseclib\Crypt\Hash;
use phpseclib\Crypt\RSA\Signature;

require('vendor/autoload.php');

class Encryption
{
    private $auth;
    private $key;
    private $iv;
    private $keypair;

    public function __construct($auth, $private_key = null)
    {
        $this->auth = $auth;
        $this->key = $this->secret($auth);
        $this->iv = str_repeat("\x00", 32);
        if ($private_key) {
            $rsa = new RSA();
            $rsa->loadKey($private_key);
            $this->keypair = $rsa;
        }
    }

    public static function replaceCharAt($str, $index, $replacement)
    {
        return substr_replace($str, $replacement, $index, strlen($replacement));
    }

    public static function changeAuthType($auth_enc)
    {
        $result = '';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = strtoupper($lowercase);
        $digits = '0123456789';

        for ($i = 0; $i < strlen($auth_enc); $i++) {
            $char = $auth_enc[$i];
            if (strpos($lowercase, $char) !== false) {
                $result .= chr(((32 - (ord($char) - 97)) % 26) + 97);
            } elseif (strpos($uppercase, $char) !== false) {
                $result .= chr(((29 - (ord($char) - 65)) % 26) + 65);
            } elseif (strpos($digits, $char) !== false) {
                $result .= chr(((13 - (ord($char) - 48)) % 10) + 48);
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    public function secret($auth)
    {
        $t = substr($auth, 0, 8);
        $i = substr($auth, 8, 8);
        $n = substr($auth, 16, 8) . $t . substr($auth, 24, 8) . $i;

        for ($s = 0; $s < strlen($n); $s++) {
            $char = $n[$s];
            if (ctype_digit($char)) {
                $t = chr((ord($char) - ord('0') + 5) % 10 + ord('0'));
                $n = $this->replaceCharAt($n, $s, $t);
            } else {
                $t = chr((ord($char) - ord('a') + 9) % 26 + ord('a'));
                $n = $this->replaceCharAt($n, $s, $t);
            }
        }

        return $n;
    }
    public function encrypt($data_enc)
    {
        $cipher = "aes-256-cbc";
        $encrypted = openssl_encrypt($data_enc, $cipher, $this->key, OPENSSL_RAW_DATA, $this->iv);
        return base64_encode($encrypted);
    }
    
    public function decrypt($data_enc)
    {
        $aes = new AES(AES::MODE_CBC);
        $aes->setKey($this->key);
        $aes->setIV($this->iv);
        $decoded_data = base64_decode($data_enc);
        $decrypted = $aes->decrypt($decoded_data);
        return $decrypted;
    }
    
    public function sign($private_key, $data) {
        $key = openssl_pkey_get_private($private_key);
        if (!$key) {
            return "key error loads";
        }
        
        $signature = null;
        $success = openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);
        
        openssl_free_key($key);
    
        if ($success) {
            return base64_encode($signature);
        } else {
            return "Sign fild error";
        }
    }

}
?>
