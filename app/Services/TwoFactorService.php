<?php

namespace App\Services;

use App\Models\User;

class TwoFactorService
{
    /**
     * Generate a new base32 secret for TOTP.
     */
    public function generateSecret(int $length = 20): string
    {
        $random = random_bytes($length);

        return $this->base32Encode($random);
    }

    /**
     * Create the otpauth URL compatible with authenticator apps.
     */
    public function makeOtpauthUrl(User $user, string $secret, string $issuer, int $digits = 6, int $period = 30, string $algorithm = 'SHA1'): string
    {
        $label = rawurlencode($issuer.':'.$user->email);
        $issuer = rawurlencode($issuer);
        $secret = strtoupper($secret);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm={$algorithm}&digits={$digits}&period={$period}";
    }

    /**
     * Verify a provided TOTP code for the given secret.
     */
    public function verifyCode(string $secret, string $code, int $window = 1, int $digits = 6, int $period = 30): bool
    {
        $code = trim($code);
        if (! ctype_digit($code) || strlen($code) !== $digits) {
            return false;
        }

        $time = time();
        for ($i = -$window; $i <= $window; $i++) {
            $calc = $this->totp($secret, $time + ($i * $period), $digits, $period);
            if (hash_equals($calc, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a TOTP code for a given time.
     */
    private function totp(string $secret, int $timestamp, int $digits = 6, int $period = 30, string $algo = 'sha1'): string
    {
        $counter = intdiv($timestamp, $period);
        $binaryKey = $this->base32Decode($secret);
        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac($algo, $binaryCounter, $binaryKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncatedHash = substr($hash, $offset, 4);
        $value = unpack('N', $truncatedHash)[1] & 0x7FFFFFFF;
        $mod = 10 ** $digits;
        $code = str_pad((string) ($value % $mod), $digits, '0', STR_PAD_LEFT);

        return $code;
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binaryString = '';
        foreach (str_split($data) as $c) {
            $binaryString .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $chunks = str_split($binaryString, 5);
        $output = '';
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $output .= $alphabet[bindec($chunk)];
        }

        // Remove padding (not strictly needed by all apps)
        return $output;
    }

    private function base32Decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper($b32);
        $binaryString = '';
        foreach (str_split($b32) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = str_split($binaryString, 8);
        $output = '';
        foreach ($bytes as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }

        return $output;
    }
}
