<?php

return [
    'name' => env('COMPANY_NAME', 'SEN SOLUTION ELECTRONIQUE'),

    'email' => env('COMPANY_EMAIL', 'Rbasse62@gmail.com'),

    // Numéro affiché (avec indicatif visible) et numéro WhatsApp officiel de l'entreprise.
    // Les deux représentent la même ligne ; whatsapp_number est au format international
    // sans "+" ni espaces, tel qu'attendu par les liens wa.me.
    'phone' => env('COMPANY_PHONE', '+221 78 186 89 36'),
    'whatsapp_number' => env('COMPANY_WHATSAPP_NUMBER', '221781868936'),

    'address_line1' => env('COMPANY_ADDRESS_LINE1', 'Médina Rue 29 x Blaise Diagne'),
    'address_line2' => env('COMPANY_ADDRESS_LINE2', 'Dakar, Sénégal'),
];
