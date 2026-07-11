/**
 * Sen Solution Electronique SI — Maintien de session (keep-alive)
 *
 * Tant que l'onglet reste ouvert et visible, un ping léger est envoyé
 * périodiquement pour prolonger la session côté serveur (StartSession
 * renouvelle la durée de vie à chaque requête authentifiée). Ceci évite
 * les erreurs "Page Expired" (419) lors d'une utilisation continue sur
 * une journée de travail.
 *
 * Si le ping détecte que la session est réellement expirée (401/419),
 * l'utilisateur est prévenu puis redirigé proprement vers la connexion.
 */
(function () {
    'use strict';

    var config = window.SessionKeepAliveConfig;
    if (!config || !config.pingUrl || !config.loginUrl) return;

    var PING_INTERVAL_MS = 10 * 60 * 1000; // 10 minutes
    var expired = false;

    function handleExpiredSession() {
        if (expired) return;
        expired = true;

        if (window.UiToast) {
            window.UiToast.show('Votre session a expiré. Veuillez vous reconnecter.', 'error', 3500);
        }

        setTimeout(function () {
            window.location.href = config.loginUrl + '?expired=1';
        }, 1800);
    }

    function ping() {
        if (expired || document.hidden) return;

        fetch(config.pingUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(function (response) {
            if (response.status === 401 || response.status === 419) {
                handleExpiredSession();
            }
        }).catch(function () {
            // Erreur réseau (offline, etc.) : on ne considère pas la session comme expirée.
        });
    }

    setInterval(ping, PING_INTERVAL_MS);

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) ping();
    });
})();
