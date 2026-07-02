<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Issued = 'issued';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'Non payé',
            self::Partial => 'Partiellement payée',
            self::Paid => 'Payée',
            self::Cancelled => 'Annulée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Issued => 'bg-secondary',
            self::Partial => 'bg-warning text-dark',
            self::Paid => 'bg-success',
            self::Cancelled => 'bg-danger',
        };
    }
}
