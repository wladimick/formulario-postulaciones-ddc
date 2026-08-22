(() => {
    'use strict';

    const MAX_FILE_SIZE = 8 * 1024 * 1024;
    const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('formulario');
        if (!form) return;

        const nationality = document.getElementById('nacionalidad');
        const foreignFields = document.getElementById('foreign-fields');
        const country = document.getElementById('paisDeOrigen');
        const passport = document.getElementById('numeroDePasaporte');
        const rut = document.getElementById('rut');
        const rutRequiredMark = document.getElementById('rut-required-mark');
        const rutHelpLabel = document.getElementById('rut-help-label');
        const fileInput = document.getElementById('curriculum');
        const submitButton = document.getElementById('submit-button');
        const status = document.getElementById('form-status');
        const summary = document.getElementById('error-summary');
        const csrfInput = document.getElementById('csrf_token');

        const updateNationalityFields = () => {
            const isForeign = nationality.value === 'Extranjera';
            foreignFields.hidden = !isForeign;
            country.required = isForeign;
            passport.required = isForeign;
            rut.required = !isForeign;
            rutRequiredMark.hidden = isForeign;
            rutHelpLabel.textContent = isForeign ? '(si tienes)' : '';

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
            form.querySelectorAll('[aria-invalid="true"]').forEach((field) => {
                field.removeAttribute('aria-invalid');
            });
            status.textContent = '';
            status.className = 'status';
        };

        const showErrors = (errors) => {
            const entries = Object.entries(errors || {});
            if (!entries.length) return;

            const list = document.createElement('ul');
            let firstField = null;

            entries.forEach(([fieldName, message]) => {
                const item = document.createElement('li');
                item.textContent = message;
                list.appendChild(item);

                const field = form.elements.namedItem(fieldName);
                if (field && field instanceof HTMLElement) {
                    field.setAttribute('aria-invalid', 'true');
                    if (!firstField) firstField = field;
                }
            });

            const title = document.createElement('h2');
            title.textContent = 'Revisa los siguientes campos:';
            summary.replaceChildren(title, list);
            summary.hidden = false;
            summary.focus();

            if (firstField) {
                setTimeout(() => firstField.focus(), 80);
            }
        };

        const validateFile = () => {
            const file = fileInput.files[0];
            if (!file) return 'Debes adjuntar tu currículum.';
            if (file.size > MAX_FILE_SIZE) return 'El currículum no puede superar los 8 MB.';

            const extension = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';
            if (!ALLOWED_EXTENSIONS.includes(extension)) {
                return 'El currículum debe ser PDF, DOC o DOCX.';
            }
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
            status.textContent = 'Enviando tu postulación de forma segura…';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });

                let payload = {};
                try {
                    payload = await response.json();
                } catch (_) {
                    throw new Error('Respuesta inválida del servidor.');
                }

                if (!response.ok || !payload.ok) {
                    if (response.status === 422 && payload.errors) {
                        showErrors(payload.errors);
                    }
                    throw new Error(payload.message || 'No fue posible enviar la postulación.');
                }

                form.reset();
                if (payload.csrf_token) csrfInput.value = payload.csrf_token;
                updateNationalityFields();
                summary.hidden = true;
                status.textContent = payload.message || 'Postulación enviada correctamente.';
                status.className = 'status success';
                status.focus?.();
            } catch (error) {
                if (!summary.hidden) {
                    status.textContent = 'Corrige los campos indicados e intenta nuevamente.';
                } else {
                    status.textContent = error.message || 'Ocurrió un error al enviar. Intenta nuevamente.';
                }
                status.className = 'status error';
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Enviar postulación';
            }
        });
    });
})();
