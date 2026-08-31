<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

final class FlexFieldEncryption
{
    public static function encrypt(mixed $value): string
    {
        return Crypt::encryptString(is_scalar($value) ? (string) $value : json_encode($value));
    }

    public static function decrypt(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        try {
            $decrypted = Crypt::decryptString($value);

            $json = json_decode($decrypted, true);

            return json_last_error() === JSON_ERROR_NONE ? $json : $decrypted;
        } catch (DecryptException) {
            return $value;
        }
    }

    public static function isEncryptedPayload(mixed $value): bool
    {
        if (! is_string($value) || strlen($value) < 20) {
            return false;
        }

        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
