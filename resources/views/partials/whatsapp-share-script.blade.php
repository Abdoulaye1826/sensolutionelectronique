@once
<script>
  /**
   * Partage une facture/un bon d'échange via WhatsApp.
   * Tente d'abord un partage natif du fichier PDF (Web Share API, niveau fichiers)
   * pour joindre réellement le document. Si le navigateur ne le permet pas
   * (la plupart des navigateurs de bureau), on retombe sur un lien wa.me
   * pointant directement vers le PDF téléchargeable.
   */
  async function shareDocumentViaWhatsApp(button) {
    const payloadUrl = button.dataset.payloadUrl;
    const icon = button.querySelector('i');
    const originalIconClass = icon ? icon.className : null;

    if (icon) {
      icon.className = 'bi bi-hourglass-split';
    }
    button.disabled = true;

    try {
      const response = await fetch(payloadUrl, { headers: { Accept: 'application/json' } });
      const data = await response.json();

      if (!response.ok) {
        alert(data.error || "Impossible de préparer l'envoi WhatsApp.");
        return;
      }

      if (navigator.canShare) {
        try {
          const pdfResponse = await fetch(data.pdfUrl);
          const blob = await pdfResponse.blob();
          const file = new File([blob], data.fileName || 'document.pdf', { type: 'application/pdf' });

          if (navigator.canShare({ files: [file] })) {
            await navigator.share({ files: [file], text: data.message });
            return;
          }
        } catch (shareError) {
          // Partage natif indisponible ou annulé : on retombe sur le lien wa.me.
        }
      }

      window.open(data.waUrl, '_blank');
    } catch (error) {
      alert("Erreur lors de la préparation de l'envoi WhatsApp.");
    } finally {
      button.disabled = false;
      if (icon && originalIconClass) {
        icon.className = originalIconClass;
      }
    }
  }

  document.addEventListener('click', function (event) {
    const button = event.target.closest('.js-whatsapp-share');
    if (button) {
      shareDocumentViaWhatsApp(button);
    }
  });
</script>
@endonce
