<?php
/**
* Copied from Sessions handler which stores session data in HMAC-signed and encrypted cookies https://github.com/Snawoot/php-storageless-sessions
*/

namespace URLSafeTimedSerializer;

const JSON_DEPTH = 4;
const METADATA_SIZE = 4;
const UINT32_LE_PACK_CODE = 'V';

if (!function_exists('hash_equals')) {
    function hash_equals($a, $b)
    {
        $ret = _strlen($a) ^ _strlen($b);
        $ret |= array_sum(unpack('C*', $a ^ $b));
        return !$ret;
    }
}

class BadAlgoException extends \Exception
{
}

class BadNumericParamsException extends \Exception
{
}

class BadSecretException extends \Exception
{
}

class OpenSSLError extends \Exception
{
}

class SerializeError extends \Exception
{
}

class URLSafeTimedSerializer
{
    public function __construct($secret, $expire = 2592000, $digest_algo = 'sha256', $cipher_algo = 'aes-256-ctr', $cipher_keylen = 32)
    {
        if (!$secret) {
            throw new BadSecretException();
        }
        $this->secret = $secret;

        if (!in_array($digest_algo, hash_algos())) {
            throw new BadAlgoException();
        }
        $this->digest_algo = $digest_algo;

        if (!in_array($cipher_algo, openssl_get_cipher_methods(true))) {
            throw new BadAlgoException();
        }
        $this->cipher_algo = $cipher_algo;

        if (!(is_int($cipher_keylen) && is_int($expire) && $expire > 0 && $cipher_keylen > 0)) {
            throw new BadNumericParamsException();
        }
        $this->cipher_keylen = $cipher_keylen;
        $this->expire = $expire;

        $this->digest_len = _strlen(hash($this->digest_algo, '', true));
        $this->cipher_ivlen = openssl_cipher_iv_length($this->cipher_algo);

        if ($this->digest_len === false or $this->cipher_ivlen === false) {
            throw new BadAlgoException();
        }
    }

    protected static function base64_urlsafe_decode($v)
    {
        $tr = strtr($v, [
            '-' => '+',
            '_' => '/',
        ]);
        return base64_decode(str_pad($tr, ((int) ((_strlen($v) + 3) / 4)) * 4, '=', STR_PAD_RIGHT));
    }

    protected static function base64_urlsafe_encode($v)
    {
        return strtr(base64_encode($v), [
            '+' => '-',
            '/' => '_',
            '=' => '',
        ]);
    }

    public function generate($v, $serialize = true)
    {
        if ($serialize) {
            $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            if ($v === false) {
                throw new SerializeError();
            }
        }
        $expires = time() + $this->expire;
        $valid_till_bin = pack(UINT32_LE_PACK_CODE, $expires);

        $iv = openssl_random_pseudo_bytes($this->cipher_ivlen);
        $key = static::pbkdf2($this->digest_algo, $this->secret, $valid_till_bin, 1, $this->cipher_keylen, true);

        $ciphertext = openssl_encrypt($v, $this->cipher_algo, $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new OpenSSLError();
        }
        $meta = $valid_till_bin;
        $message = $meta . $iv . $ciphertext;

        $digest = hash_hmac($this->digest_algo, $message, $this->secret, true);

        return static::base64_urlsafe_encode($digest . $message);
    }

    public function load($v, $serialize = true)
    {
        $v = static::base64_urlsafe_decode($v);
        if ($v === false) {
            return '';
        }

        $digest = _substr($v, 0, $this->digest_len);
        if ($digest === false) {
            return '';
        }

        $message = _substr($v, $this->digest_len);
        if ($message === false) {
            return '';
        }

        if (!hash_equals(hash_hmac($this->digest_algo, $message, $this->secret, true), $digest)) {
            return '';
        }

        $valid_till_bin = _substr($message, 0, METADATA_SIZE);
        $valid_till = unpack(UINT32_LE_PACK_CODE, $valid_till_bin)[1];

        if (time() > $valid_till) {
            return '';
        }

        $iv = _substr($message, METADATA_SIZE, $this->cipher_ivlen);
        $ciphertext = _substr($message, METADATA_SIZE + $this->cipher_ivlen);

        $key = static::pbkdf2($this->digest_algo, $this->secret, $valid_till_bin, 1, $this->cipher_keylen, true);

        $rv = openssl_decrypt($ciphertext, $this->cipher_algo, $key, OPENSSL_RAW_DATA, $iv);

        if ($rv === false) {
            throw new OpenSSLError();
        }

        return $serialize ? json_decode($rv ?: '', true, JSON_DEPTH) : $rv;
    }

    /*
     * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
     * $algorithm - The hash algorithm to use. Recommended: SHA256
     * $password - The password.
     * $salt - A salt that is unique to the password.
     * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
     * $key_length - The length of the derived key in bytes.
     * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
     * Returns: A $key_length-byte key derived from the password and salt.
     *
     * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
     *
     * This implementation of PBKDF2 was originally created by https://defuse.ca
     * With improvements by http://www.variations-of-shadow.com
     */
    protected static function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
    {
        $algorithm = strtolower($algorithm);

        if (!in_array($algorithm, hash_algos(), true)) {
            trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
        }
        if ($count <= 0 || $key_length <= 0) {
            trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);
        }
        if (function_exists('hash_pbkdf2')) {
            // The output length is in NIBBLES (4-bits) if $raw_output is false!
            if (!$raw_output) {
                $key_length = $key_length * 2;
            }
            return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output);
        }

        $hash_length = _strlen(hash($algorithm, '', true));
        $block_count = ceil($key_length / $hash_length);
        $output = '';

        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack('N', $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }
        return ($raw_output ? _substr($output, 0, $key_length) : bin2hex(_substr($output, 0, $key_length)));
    }
}

if (!function_exists('URLSafeTimedSerializer\\_strlen')) {
    /**
     * Count the number of bytes in a string
     *
     * We cannot simply use strlen() for this, because it might be overwritten by the mbstring extension.
     * In this case, strlen() will count the number of *characters* based on the internal encoding. A
     * sequence of bytes might be regarded as a single multibyte character.
     */
    function _strlen($v)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($v, '8bit');
        }
        return strlen($v);
    }
}

if (!function_exists('URLSafeTimedSerializer\\_substr')) {
    /**
     * Get a substring based on byte limits
     * @see _strlen()
     */
    function _substr($v, $start, $length = null)
    {
        if ($length === null) {
            $length = _strlen($v) - $start;
        }
        if (function_exists('mb_substr')) {
            return mb_substr($v, $start, $length, '8bit');
        }
        return substr($v, $start, $length);
    }
}
