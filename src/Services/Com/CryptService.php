<?php

namespace WeSoonNet\LaravelPlus\Services\Com;

class CryptService
{
    /**
     * Encrypt any value
     *
     * @param  string  $plain_text  明文
     * @param  string  $passphrase  秘钥
     *
     * @return string
     */
    public static function encrypt(string $plain_text, string $passphrase)
    {
        $salt           = openssl_random_pseudo_bytes(256);
        $iv             = openssl_random_pseudo_bytes(16);
        $iterations     = 999;
        $raw_output     = null;
        $key            = hash_pbkdf2("sha512", $passphrase, $salt, $iterations, 64, $raw_output);
        $encrypted_data = openssl_encrypt($plain_text, 'aes-256-cbc', hex2bin($key), OPENSSL_RAW_DATA, $iv);
        $data           = ["ciphertext" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "salt" => bin2hex($salt)];
        return json_encode($data);
    }

    /**
     * Decrypt a previously encrypted value
     *
     * @param  string  $jsonString  密文
     * @param  string  $passphrase  秘钥
     *
     * @return mixed
     */
    public static function decrypt(string $jsonString, string $passphrase)
    {
        $jsondata = json_decode($jsonString, true);

        try
        {
            $salt = hex2bin($jsondata["salt"]);
            $iv   = hex2bin($jsondata["iv"]);
        }
        catch (\Exception $e)
        {
            return null;
        }

        $ciphertext = base64_decode($jsondata["ciphertext"]);
        $iterations = 999;
        $raw_output = null;
        $key        = hash_pbkdf2("sha512", $passphrase, $salt, $iterations, 64, $raw_output);
        return openssl_decrypt($ciphertext, 'aes-256-cbc', hex2bin($key), OPENSSL_RAW_DATA, $iv);
    }
}
