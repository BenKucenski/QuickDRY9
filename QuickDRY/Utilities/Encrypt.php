<?php
declare(strict_types=1);
// https://stackoverflow.com/questions/10916284/how-to-encrypt-decrypt-data-in-php
namespace QuickDRY\Utilities;

/**
 * Class Encrypt
 */
class Encrypt extends strongType
{
    public static int $KeySize = 32; // 256-bit
    public static int $PublicKeySize = 16; // 128-bit
    public static string $EncryptionMethod = 'AES-256-CBC';

    /**
     * @return array
     */
    public static function GetEncryptionMethods(): array
    {
        return openssl_get_cipher_methods();
    }

    /**
     * @return string
     */
    public static function GetPrivateKey(): string
    {
        $strong = true;
        $encryption_key = openssl_random_pseudo_bytes(self::$KeySize, $strong);

        return base64_encode($encryption_key);
    }

    /**
     * @param $data
     * @return string
     */
    public static function pkcs7_pad($data): string
    {
        $length = self::$KeySize - strlen($data) % self::$KeySize;
        return $data . str_repeat(chr($length), $length);
    }

    /**
     * @param $data
     * @return string
     */
    public static function pkcs7_unpad($data): string
    {
        return substr($data, 0, -ord($data[strlen($data) - 1]));
    }

    /**
     * @return string
     */
    public static function GetPublicKey(): string
    {
        $strong = true;
        $encryption_key = openssl_random_pseudo_bytes(self::$PublicKeySize, $strong);

        return base64_encode($encryption_key);
    }

    /**
     * @param $data
     * @param $private_key
     * @param $public_key
     * @return string
     */
    public static function EncryptData($data, $private_key, $public_key): string
    {
        return base64_encode(openssl_encrypt(
            self::pkcs7_pad($data), // padded data
            self::$EncryptionMethod,        // cipher and mode
            base64_decode($private_key),      // secret key
            0,                    // options (not used)
            base64_decode($public_key)                   // initialisation vector
        ));

    }

    /**
     * @param $data
     * @param $private_key
     * @param $public_key
     * @return string
     */
    public static function DecryptData($data, $private_key, $public_key): string
    {
        return self::pkcs7_unpad(openssl_decrypt(
            base64_decode($data),
            self::$EncryptionMethod,
            base64_decode($private_key),
            0,
            base64_decode($public_key)
        ));
    }
}