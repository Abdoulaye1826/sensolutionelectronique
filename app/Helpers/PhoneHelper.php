<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Normalise un numéro sénégalais vers le format international sans "+"
     * ni espaces (ex: "221781868936"), tel qu'attendu par les liens wa.me.
     * Retourne null si le numéro ne correspond pas à un mobile sénégalais valide.
     */
    public static function normalizeSenegalNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '221')) {
            $digits = substr($digits, 3);
        }

        $digits = ltrim($digits, '0');

        // Un mobile sénégalais comporte 9 chiffres (ex: 77 123 45 67).
        if (strlen($digits) !== 9) {
            return null;
        }

        return '221' . $digits;
    }
}
