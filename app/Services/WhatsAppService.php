<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use App\Models\Sale;

/**
 * Construit les messages et liens WhatsApp pour l'envoi des factures de vente
 * et des bons d'échange, en utilisant le numéro WhatsApp officiel de
 * l'entreprise (config('company.whatsapp_number')) comme référence de contact.
 */
class WhatsAppService
{
    public function buildMessage(Sale $sale, string $documentLabel, string $documentNumber, string $documentUrl): string
    {
        $customerName = $sale->customer?->full_name ?? 'cher client';
        $companyPhone = config('company.phone');

        return implode("\n", [
            "Bonjour {$customerName},",
            '',
            "Veuillez trouver votre {$documentLabel}.",
            '',
            "Référence : {$documentNumber}",
            $documentUrl,
            '',
            'Merci de votre confiance.',
            '',
            'Pour toute information complémentaire :',
            $companyPhone,
        ]);
    }

    /**
     * Construit le lien wa.me vers le numéro du client. Retourne null si le
     * numéro du client n'est pas un mobile sénégalais valide.
     */
    public function buildLink(?string $customerPhone, string $message): ?string
    {
        $normalized = PhoneHelper::normalizeSenegalNumber($customerPhone);

        if ($normalized === null) {
            return null;
        }

        return "https://wa.me/{$normalized}?text=" . rawurlencode($message);
    }
}
