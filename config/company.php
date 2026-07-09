<?php

return [
    'name' => env('COMPANY_NAME', 'Mboup Gaming'),

    'email' => env('COMPANY_EMAIL', 'sallamboup2710@gmail.com'),

    // Numéro affiché (avec indicatif visible) et numéro WhatsApp officiel de l'entreprise.
    // Les deux représentent la même ligne ; whatsapp_number est au format international
    // sans "+" ni espaces, tel qu'attendu par les liens wa.me.
    'phone' => env('COMPANY_PHONE', '+221 78 192 85 88'),
    'whatsapp_number' => env('COMPANY_WHATSAPP_NUMBER', '221781928588'),

    'address_line1' => env('COMPANY_ADDRESS_LINE1', 'Médina Rue 29 x Blaise Diagne'),
    'address_line2' => env('COMPANY_ADDRESS_LINE2', 'Dakar, Sénégal'),

    'ninea' => env('COMPANY_NINEA', '012188547'),
    'rc' => env('COMPANY_RC', 'SN.DKR.2025.A.21351'),
];
