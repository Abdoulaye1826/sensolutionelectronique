/**
 * Sen Solution Electronique SI — UI des formulaires (ajout/modification)
 *
 * Fournit, sans dépendance externe :
 *  - un système de toasts de confirmation,
 *  - une validation HTML5 en temps réel (au blur + à la soumission)
 *    avec retours visuels (icônes, classes is-valid/is-invalid, aria-invalid),
 *  - un uploader d'image avec glisser-déposer + aperçu,
 *  - un accordéon léger pour les sections de formulaire (form-section),
 *  - un état "chargement" sur le bouton de soumission.
 *
 * N'importe quoi touchant à .top-navbar / .sidebar n'est pas inclus ici :
 * ce script ne cible que les pages de contenu (formulaires).
 */
(function () {
    'use strict';

    /* ── Toasts ────────────────────────────────────────────────── */
    function getToastStack() {
        let stack = document.querySelector('.ui-toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'ui-toast-stack';
            stack.setAttribute('role', 'status');
            stack.setAttribute('aria-live', 'polite');
            document.body.appendChild(stack);
        }
        return stack;
    }

    function showToast(message, type = 'info', duration = 4000) {
        const stack = getToastStack();
        const toast = document.createElement('div');
        const icon = type === 'success' ? 'bi-check-circle-fill' : type === 'error' ? 'bi-x-circle-fill' : 'bi-info-circle-fill';

        toast.className = 'ui-toast' + (type === 'success' ? ' ui-toast--success' : type === 'error' ? ' ui-toast--error' : '');
        toast.innerHTML = '<i class="bi ' + icon + '"></i><span></span>';
        toast.querySelector('span').textContent = message;
        stack.appendChild(toast);

        const remove = () => {
            toast.classList.add('is-leaving');
            setTimeout(() => toast.remove(), 200);
        };

        setTimeout(remove, duration);
        toast.addEventListener('click', remove);
    }

    window.UiToast = { show: showToast };

    /* Affiche automatiquement les messages flash de session (data-flash-*) */
    document.addEventListener('DOMContentLoaded', function () {
        const flash = document.getElementById('ui-flash-data');
        if (flash) {
            if (flash.dataset.success) showToast(flash.dataset.success, 'success');
            if (flash.dataset.error) showToast(flash.dataset.error, 'error');
        }
    });

    /* ── Validation temps réel ────────────────────────────────── */
    function validateField(field) {
        if (!(field instanceof HTMLInputElement) && !(field instanceof HTMLSelectElement) && !(field instanceof HTMLTextAreaElement)) {
            return true;
        }
        if (field.disabled || field.type === 'file' || field.type === 'hidden') return true;

        const isValid = field.checkValidity();
        field.classList.toggle('is-invalid', !isValid);
        field.classList.toggle('is-valid', isValid && field.value.trim() !== '');
        field.setAttribute('aria-invalid', isValid ? 'false' : 'true');

        let feedback = field.parentElement.querySelector('.invalid-feedback[data-js-feedback]');
        if (!isValid) {
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block';
                feedback.setAttribute('data-js-feedback', '1');
                field.insertAdjacentElement('afterend', feedback);
            }
            feedback.textContent = field.validationMessage;
        } else if (feedback) {
            feedback.remove();
        }

        return isValid;
    }

    function attachLiveValidation(form) {
        form.querySelectorAll('.form-control, .form-select').forEach((field) => {
            field.addEventListener('blur', () => validateField(field));
            field.addEventListener('input', () => {
                if (field.classList.contains('is-invalid') || field.classList.contains('is-valid')) {
                    validateField(field);
                }
            });
        });

        form.addEventListener('submit', function (event) {
            let firstInvalid = null;
            form.querySelectorAll('.form-control, .form-select').forEach((field) => {
                const ok = validateField(field);
                if (!ok && !firstInvalid) firstInvalid = field;
            });

            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
                showToast('Merci de corriger les champs en rouge avant de continuer.', 'error');
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.classList.contains('is-loading')) {
                submitBtn.classList.add('is-loading');
                submitBtn.disabled = true;
                submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="btn-spinner"></span>Enregistrement...';
            }
        });
    }

    /* ── Uploader d'image (drag & drop + aperçu) ─────────────── */
    function initImageDropzone(dropzone) {
        const input = dropzone.querySelector('input[type="file"]');
        const previewWrap = dropzone.parentElement.querySelector('.image-preview');
        if (!input) return;

        function renderPreview(file) {
            if (!previewWrap) return;
            if (!file || !file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                let img = previewWrap.querySelector('img');
                if (!img) {
                    img = document.createElement('img');
                    img.loading = 'lazy';
                    img.alt = 'Aperçu';
                    previewWrap.prepend(img);
                }
                img.src = e.target.result;
                previewWrap.classList.add('d-flex');
                previewWrap.style.display = '';
            };
            reader.readAsDataURL(file);
        }

        input.addEventListener('change', function () {
            if (input.files && input.files[0]) {
                renderPreview(input.files[0]);
            }
        });

        ['dragenter', 'dragover'].forEach((evt) => {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((evt) => {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', function (e) {
            const files = e.dataTransfer && e.dataTransfer.files;
            if (files && files[0]) {
                input.files = files;
                renderPreview(files[0]);
            }
        });
    }

    /* ── Select "recherchable" (sans dépendance externe) ─────── */
    function initSearchableSelect(wrapper) {
        const select = wrapper.querySelector('select');
        const input = wrapper.querySelector('input[data-select-filter]');
        if (!select || !input) return;

        const options = Array.from(select.options);
        const expandedSize = Math.min(8, Math.max(options.length, 2));

        input.addEventListener('input', function () {
            const term = input.value.trim().toLowerCase();
            options.forEach((opt) => {
                if (!opt.value) { opt.hidden = false; return; }
                opt.hidden = !opt.textContent.toLowerCase().includes(term);
            });
        });

        input.addEventListener('focus', function () {
            select.size = expandedSize;
            wrapper.classList.add('is-expanded');
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                select.focus();
            } else if (e.key === 'Escape') {
                select.size = 1;
                wrapper.classList.remove('is-expanded');
                input.blur();
            }
        });

        function collapse() {
            select.size = 1;
            wrapper.classList.remove('is-expanded');
        }

        input.addEventListener('blur', function () {
            setTimeout(collapse, 150);
        });

        select.addEventListener('blur', function () {
            setTimeout(collapse, 150);
        });

        select.addEventListener('change', function () {
            const selected = select.options[select.selectedIndex];
            input.value = selected ? selected.text : '';
            collapse();
            select.dispatchEvent(new Event('ui:selected', { bubbles: true }));
        });

        // Valeur initiale (édition) : on affiche le libellé sélectionné dans le filtre.
        const initiallySelected = select.options[select.selectedIndex];
        if (initiallySelected && initiallySelected.value) {
            input.value = initiallySelected.text;
        }
    }

    /* ── Sections repliables ─────────────────────────────────── */
    function initFormSections(root) {
        root.querySelectorAll('.form-section__header[data-toggle-section]').forEach((header) => {
            header.addEventListener('click', function () {
                const body = document.getElementById(header.getAttribute('aria-controls'));
                const expanded = header.getAttribute('aria-expanded') === 'true';
                header.setAttribute('aria-expanded', String(!expanded));
                if (body) {
                    body.style.display = expanded ? 'none' : '';
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-ui-form]').forEach(attachLiveValidation);
        document.querySelectorAll('.image-dropzone').forEach(initImageDropzone);
        document.querySelectorAll('[data-form-sections]').forEach(initFormSections);
        document.querySelectorAll('[data-searchable-select]').forEach(initSearchableSelect);

        document.querySelectorAll('.image-preview__remove').forEach((btn) => {
            btn.addEventListener('click', function () {
                const wrap = btn.closest('.image-preview');
                const checkbox = document.getElementById(btn.dataset.removeTarget);
                if (checkbox) checkbox.checked = true;
                if (wrap) wrap.style.display = 'none';
            });
        });
    });
})();
