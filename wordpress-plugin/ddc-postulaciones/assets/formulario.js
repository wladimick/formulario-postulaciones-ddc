(() => {
    'use strict';

    const MAX_FILE_SIZE = 8 * 1024 * 1024;
    const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('ddc-postulaciones-form');
        if (!form) return;

        const nationality = document.getElementById('ddc-nacionalidad');
        const foreignFields = Array.from(form.querySelectorAll('.ddc-foreign-field'));
        const country = document.getElementById('ddc-pais');
        const passport = document.getElementById('ddc-pasaporte');
        const rut = document.getElementById('ddc-rut');
        const rutRequired = document.getElementById('ddc-rut-required');
        const fileInput = document.getElementById('ddc-cv');
        const submitButton = document.getElementById('ddc-submit-button');
        const status = document.getElementById('ddc-form-status');
        const summary = document.getElementById('ddc-error-summary');

        const updateNationalityFields = () => {
            const isForeign = nationality.value === 'Extranjera';
            foreignFields.forEach((field) => { field.hidden = !isForeign; });
            country.required = isForeign;
            passport.required = isForeign;
            rut.required = !isForeign;
            rutRequired.hidden = isForeign;

            if (!isForeign) {
                country.value = '';
                passport.value = '';
            }
        };

        nationality.addEventListener('change', updateNationalityFields);
        updateNationalityFields();

        const clearErrors = () => {
            summary.hidden = true;
            summary.innerHTML = '';
            form.querySelectorAll('[aria-invalid="true"]').forEach((field) => field.removeAttribute('aria-invalid'));
            status.textContent = '';
            status.className = 'ddc-status';
        };

        const showErrors = (errors = {}) => {
            const entries = Object.entries(errors);
            if (!entries.length) return;

            const title = document.createElement('h2');
            title.textContent = 'Revisa los siguientes campos:';
            const list = document.createElement('ul');
            let firstField = null;

            entries.forEach(([fieldName, message]) => {
                const item = document.createElement('li');
                item.textContent = String(message);
                list.appendChild(item);

                const field = form.elements.namedItem(fieldName);
                if (field instanceof HTMLElement) {
                    field.setAttribute('aria-invalid', 'true');
                    if (!firstField) firstField = field;
                }
            });

            summary.replaceChildren(title, list);
            summary.hidden = false;
            summary.focus();
            if (firstField) setTimeout(() => firstField.focus(), 80);
        };

        const validateFile = () => {
            const file = fileInput.files[0];
            if (!file) return 'Debes adjuntar tu currículum.';
            const maxBytes = Number(window.DDCPostulaciones?.maxFileBytes || MAX_FILE_SIZE);
            if (file.size > maxBytes) return 'El currículum no puede superar los 8 MB.';
            const extension = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';
            if (!ALLOWED_EXTENSIONS.includes(extension)) return 'El currículum debe ser PDF, DOC o DOCX.';
            return null;
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors();

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const fileError = validateFile();
            if (fileError) {
                showErrors({ curriculum: fileError });
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Enviando…';
            status.textContent = 'Enviando tu postulación…';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });

                let payload;
                try {
                    payload = await response.json();
                } catch (_) {
                    throw new Error('El servidor devolvió una respuesta inválida.');
                }

                const data = payload?.data || {};
                if (!response.ok || !payload?.success) {
                    if (data.errors) showErrors(data.errors);
                    throw new Error(data.message || 'No fue posible enviar la postulación.');
                }

                form.reset();
                updateNationalityFields();
                summary.hidden = true;
                status.textContent = data.message || 'Postulación enviada correctamente.';
                status.className = 'ddc-status is-success';
            } catch (error) {
                status.textContent = error?.message || 'Ocurrió un error al enviar. Intenta nuevamente.';
                status.className = 'ddc-status is-error';
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Enviar postulación';
            }
        });
    });
})();
