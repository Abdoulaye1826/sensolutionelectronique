<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>{{ ucfirst($documentLabel) }} {{ $documentNumber }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1a1a2e; font-size: 14px; line-height: 1.6;">
  <p>Bonjour {{ $sale->customer?->full_name ?? 'cher client' }},</p>

  <p>Veuillez trouver ci-joint votre {{ $documentLabel }} <strong>{{ $documentNumber }}</strong>.</p>

  @if($invoice && !$invoice->isFullyPaid())
    <p>
      Montant total : <strong>{{ number_format($invoice->total_ttc, 0, ',', ' ') }} FCFA</strong><br>
      Montant payé : <strong>{{ number_format($invoice->amount_paid, 0, ',', ' ') }} FCFA</strong><br>
      Reste à payer : <strong>{{ number_format($invoice->remaining_amount, 0, ',', ' ') }} FCFA</strong>
    </p>
  @endif

  @if($sale->warranty_duration && $sale->warranty_duration->value !== 'none')
    <p>
      Garantie : <strong>{{ $sale->warranty_duration->label() }}</strong>
      @if($sale->warranty_end_date)
        — valable jusqu'au {{ $sale->warranty_end_date->format('d/m/Y') }}
      @endif
    </p>
  @endif

  <p>Merci de votre confiance.</p>

  <p>
    Pour toute information complémentaire :<br>
    {{ config('company.phone') }}<br>
    {{ config('company.email') }}
  </p>
</body>
</html>
