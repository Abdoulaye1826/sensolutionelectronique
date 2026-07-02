<?php

namespace App\Enums;

use Carbon\Carbon;

enum WarrantyDuration: string
{
    case None = 'none';
    case Days30 = '30d';
    case Months3 = '3m';
    case Months6 = '6m';
    case Year1 = '1y';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Aucune garantie',
            self::Days30 => '30 jours',
            self::Months3 => '3 mois',
            self::Months6 => '6 mois',
            self::Year1 => '1 an',
        };
    }

    /**
     * Calcule la date de fin de garantie à partir de la date de vente.
     * Retourne null pour "Aucune garantie" (rien à suivre).
     */
    public function endDateFrom(Carbon $saleDate): ?Carbon
    {
        return match ($this) {
            self::None => null,
            self::Days30 => $saleDate->copy()->addDays(30),
            self::Months3 => $saleDate->copy()->addMonths(3),
            self::Months6 => $saleDate->copy()->addMonths(6),
            self::Year1 => $saleDate->copy()->addYear(),
        };
    }
}
