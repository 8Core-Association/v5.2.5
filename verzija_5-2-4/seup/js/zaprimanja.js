(function() {
    'use strict';

    function showToast(message, type = 'success') {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.style.cssText = `
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;

        const icon = type === 'success'
            ? '<i class="fas fa-check-circle" style="font-size: 20px;"></i>'
            : '<i class="fas fa-exclamation-circle" style="font-size: 20px;"></i>';

        toast.innerHTML = `${icon}<span>${message}</span>`;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    function initZaprimanja() {
        let posiljateljAutocompleteTimeout;
        let selectedPosiljatelj = null;

        console.log('Zaprimanja.js initializing');

        const zaprimiBtn = document.getElementById('zaprimiDokumentBtn');
        const modal = document.getElementById('zaprimiDokumentModal');
        const closeModalBtn = document.getElementById('closeZaprimanjeModal');
        const cancelBtn = document.getElementById('cancelZaprimanjeBtn');
        const zaprimanjeForm = document.getElementById('zaprimanjeForm');
        const tipDokumentaSelect = document.getElementById('tip_dokumenta');
        const aktZaPrilogWrapper = document.getElementById('akt_za_prilog_wrapper');
        const posiljateljSearchInput = document.getElementById('posiljatelj_search');
        const posiljateljDropdown = document.getElementById('posiljatelj_dropdown');
        const fkPosiljateljInput = document.getElementById('fk_posiljatelj');

        console.log('Elements found:', {
            zaprimiBtn: !!zaprimiBtn,
            modal: !!modal,
            zaprimanjeForm: !!zaprimanjeForm,
            submitBtn: !!document.getElementById('submitZaprimanjeBtn')
        });

        if (zaprimiBtn) {
            zaprimiBtn.addEventListener('click', function() {
                console.log('Button clicked!');
                if (modal) {
                    modal.classList.add('show');
                    if (zaprimanjeForm) zaprimanjeForm.reset();
                    if (fkPosiljateljInput) fkPosiljateljInput.value = '';
                    selectedPosiljatelj = null;
                    if (aktZaPrilogWrapper) aktZaPrilogWrapper.style.display = 'none';
                    if (posiljateljDropdown) posiljateljDropdown.classList.remove('active');
                }
            });
        } else {
            console.error('Button #zaprimiDokumentBtn not found!');
        }

        function closeModal() {
            if (modal) {
                modal.classList.remove('show');
                if (zaprimanjeForm) zaprimanjeForm.reset();
                if (fkPosiljateljInput) fkPosiljateljInput.value = '';
                selectedPosiljatelj = null;
                if (posiljateljDropdown) posiljateljDropdown.classList.remove('active');
            }
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeModal);
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }

        if (tipDokumentaSelect && aktZaPrilogWrapper) {
            tipDokumentaSelect.addEventListener('change', function() {
                if (this.value === 'prilog_postojecem') {
                    aktZaPrilogWrapper.style.display = 'block';
                    const aktSelect = document.getElementById('fk_akt_za_prilog');
                    if (aktSelect) aktSelect.required = true;
                } else {
                    aktZaPrilogWrapper.style.display = 'none';
                    const aktSelect = document.getElementById('fk_akt_za_prilog');
                    if (aktSelect) aktSelect.required = false;
                }
            });
        }

        if (posiljateljSearchInput) {
            posiljateljSearchInput.addEventListener('input', function() {
                const query = this.value.trim();

                clearTimeout(posiljateljAutocompleteTimeout);

                if (query.length < 2) {
                    if (posiljateljDropdown) {
                        posiljateljDropdown.classList.remove('active');
                        posiljateljDropdown.innerHTML = '';
                    }
                    if (fkPosiljateljInput) fkPosiljateljInput.value = '';
                    selectedPosiljatelj = null;
                    return;
                }

                if (posiljateljDropdown) {
                    posiljateljDropdown.innerHTML = '<div class="seup-autocomplete-loading"><i class="fas fa-spinner fa-spin"></i> Pretraživanje...</div>';
                    posiljateljDropdown.classList.add('active');
                }

                posiljateljAutocompleteTimeout = setTimeout(function() {
                    searchPosiljatelji(query);
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (posiljateljSearchInput && posiljateljDropdown) {
                    if (!posiljateljSearchInput.contains(e.target) && !posiljateljDropdown.contains(e.target)) {
                        posiljateljDropdown.classList.remove('active');
                    }
                }
            });
        }

        function searchPosiljatelji(query) {
            if (!posiljateljDropdown) return;

            const formData = new FormData();
            formData.append('action', 'search_posiljatelji');
            formData.append('query', query);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPosiljateljiResults(data.results);
                } else {
                    posiljateljDropdown.innerHTML = '<div class="seup-autocomplete-no-results">Greška pri pretraživanju</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (posiljateljDropdown) {
                    posiljateljDropdown.innerHTML = '<div class="seup-autocomplete-no-results">Greška pri pretraživanju</div>';
                }
            });
        }

        function displayPosiljateljiResults(results) {
            if (!posiljateljDropdown) return;

            if (!results || results.length === 0) {
                posiljateljDropdown.innerHTML = '<div class="seup-autocomplete-no-results">Nema rezultata. Možete unijeti novi naziv.</div>';
                return;
            }

            let html = '';
            results.forEach(function(posiljatelj) {
                let details = [];
                if (posiljatelj.oib) details.push('<span>OIB: ' + posiljatelj.oib + '</span>');
                if (posiljatelj.email) details.push('<span>Email: ' + posiljatelj.email + '</span>');
                if (posiljatelj.telefon) details.push('<span>Tel: ' + posiljatelj.telefon + '</span>');

                html += '<div class="seup-autocomplete-item" data-id="' + posiljatelj.rowid + '" data-naziv="' + posiljatelj.naziv + '">';
                html += '<div class="seup-autocomplete-item-title">' + posiljatelj.naziv + '</div>';
                if (details.length > 0) {
                    html += '<div class="seup-autocomplete-item-details">' + details.join('') + '</div>';
                }
                html += '</div>';
            });

            posiljateljDropdown.innerHTML = html;

            const items = posiljateljDropdown.querySelectorAll('.seup-autocomplete-item');
            items.forEach(function(item) {
                item.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const naziv = this.getAttribute('data-naziv');

                    if (posiljateljSearchInput) posiljateljSearchInput.value = naziv;
                    if (fkPosiljateljInput) fkPosiljateljInput.value = id;
                    selectedPosiljatelj = { id: id, naziv: naziv };

                    if (posiljateljDropdown) posiljateljDropdown.classList.remove('active');
                });
            });
        }

        const submitZaprimanjeBtn = document.getElementById('submitZaprimanjeBtn');
        if (submitZaprimanjeBtn) {
            console.log('Submit button found, attaching click handler');
            submitZaprimanjeBtn.addEventListener('click', function(e) {
                console.log('Submit button clicked');
                e.preventDefault();

                const form = document.getElementById('zaprimanjeForm');
                if (!form) {
                    console.error('Form not found');
                    return;
                }

                const dokumentFile = form.querySelector('input[name="dokument_file"]');
                const potvrdaFile = form.querySelector('input[name="potvrda_file"]');

                const maxFileSize = 8 * 1024 * 1024;

                if (dokumentFile && dokumentFile.files && dokumentFile.files[0]) {
                    const fileSize = dokumentFile.files[0].size;
                    const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);

                    console.log('Dokument file size:', fileSizeMB + ' MB');

                    if (fileSize > maxFileSize) {
                        showToast(`Dokument je prevelik (${fileSizeMB} MB). Maksimalna veličina je 8 MB. Molimo povećajte PHP limite ili smanjite veličinu fajla.`, 'error');
                        return;
                    }
                }

                if (potvrdaFile && potvrdaFile.files && potvrdaFile.files[0]) {
                    const fileSize = potvrdaFile.files[0].size;
                    const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);

                    console.log('Potvrda file size:', fileSizeMB + ' MB');

                    if (fileSize > maxFileSize) {
                        showToast(`Potvrda je prevelika (${fileSizeMB} MB). Maksimalna veličina je 8 MB. Molimo povećajte PHP limite ili smanjite veličinu fajla.`, 'error');
                        return;
                    }
                }

                console.log('Form found, submitting...');

                const originalText = submitZaprimanjeBtn.innerHTML;
                submitZaprimanjeBtn.disabled = true;
                submitZaprimanjeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Zaprimanje...';

                const formData = new FormData(form);
                console.log('FormData created');
                console.log('Action:', formData.get('action'));

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers.get('Content-Type'));
                    return response.text();
                })
                .then(text => {
                    console.log('Response text:', text);
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response was:', text);
                        throw new Error('Server vratio neispravan odgovor. Provjerite veličinu fajla ili PHP limite.');
                    }

                    if (data.success) {
                        showToast('Dokument uspješno zaprimljen!', 'success');
                        closeModal();
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showToast('Greška: ' + (data.error || 'Nepoznata greška'), 'error');
                        submitZaprimanjeBtn.disabled = false;
                        submitZaprimanjeBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Greška pri zaprimanju dokumenta: ' + error.message, 'error');
                    submitZaprimanjeBtn.disabled = false;
                    submitZaprimanjeBtn.innerHTML = originalText;
                });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initZaprimanja);
    } else {
        initZaprimanja();
    }

})();
