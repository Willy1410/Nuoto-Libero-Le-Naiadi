(function () {
    'use strict';

    if (window.GliSqualettiUI) {
        return;
    }

    const state = {
        resolver: null,
        mode: 'alert',
        validator: null,
        required: false,
    };

    let modalRoot = null;
    let toastRoot = null;

    function ensureUi() {
        if (modalRoot) {
            return;
        }

        const style = document.createElement('style');
        style.textContent = [
            '.gsui-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.52);display:none;align-items:center;justify-content:center;padding:16px;z-index:10050;}',
            '.gsui-backdrop.open{display:flex;}',
            '.gsui-modal{width:min(520px,100%);background:#fff;border-radius:16px;box-shadow:0 20px 38px rgba(2,6,23,.35);overflow:hidden;}',
            '.gsui-head{padding:16px 18px;background:linear-gradient(135deg,#0077b6,#00a8e8);color:#fff;}',
            '.gsui-title{margin:0;font:600 19px/1.2 Poppins,sans-serif;}',
            '.gsui-body{padding:16px 18px;color:#1f2937;font:400 14px/1.6 Poppins,sans-serif;}',
            '.gsui-message{margin:0;white-space:pre-wrap;}',
            '.gsui-input{margin-top:14px;width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font:500 14px/1.4 Poppins,sans-serif;}',
            '.gsui-input:focus{outline:none;border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.18);}',
            '.gsui-error{display:none;margin-top:8px;color:#b91c1c;font-size:12px;}',
            '.gsui-error.open{display:block;}',
            '.gsui-actions{display:flex;gap:8px;justify-content:flex-end;padding:14px 18px 18px;background:#f8fafc;}',
            '.gsui-btn{border:0;border-radius:999px;padding:10px 16px;font:700 13px/1 Poppins,sans-serif;cursor:pointer;}',
            '.gsui-btn.primary{background:linear-gradient(135deg,#0ea5e9,#0284c7);color:#fff;}',
            '.gsui-btn.secondary{background:#fff;border:2px solid #0ea5e9;color:#0369a1;}',
            '.gsui-toast-wrap{position:fixed;top:20px;right:16px;display:grid;gap:10px;z-index:10060;pointer-events:none;}',
            '.gsui-toast{min-width:240px;max-width:340px;padding:11px 14px;border-radius:12px;color:#fff;font:600 13px/1.45 Poppins,sans-serif;box-shadow:0 14px 26px rgba(2,6,23,.24);opacity:0;transform:translateY(-8px);transition:all .22s ease;}',
            '.gsui-toast.open{opacity:1;transform:translateY(0);}',
            '.gsui-toast.info{background:#0284c7;}',
            '.gsui-toast.success{background:#16a34a;}',
            '.gsui-toast.error{background:#dc2626;}',
            '@media (max-width:640px){.gsui-actions{flex-direction:column-reverse}.gsui-btn{width:100%}.gsui-toast-wrap{left:12px;right:12px;}}'
        ].join('');
        document.head.appendChild(style);

        modalRoot = document.createElement('div');
        modalRoot.className = 'gsui-backdrop';
        modalRoot.innerHTML = '' +
            '<div class="gsui-modal" role="dialog" aria-modal="true" aria-labelledby="gsuiTitle">' +
            '  <div class="gsui-head"><h2 id="gsuiTitle" class="gsui-title">Messaggio</h2></div>' +
            '  <div class="gsui-body">' +
            '    <p class="gsui-message" id="gsuiMessage"></p>' +
            '    <input id="gsuiInput" class="gsui-input" type="text" />' +
            '    <div id="gsuiError" class="gsui-error"></div>' +
            '  </div>' +
            '  <div class="gsui-actions">' +
            '    <button id="gsuiCancel" class="gsui-btn secondary" type="button">Annulla</button>' +
            '    <button id="gsuiConfirm" class="gsui-btn primary" type="button">Conferma</button>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(modalRoot);

        toastRoot = document.createElement('div');
        toastRoot.className = 'gsui-toast-wrap';
        document.body.appendChild(toastRoot);

        modalRoot.addEventListener('click', function (event) {
            if (event.target === modalRoot) {
                closeModal(false, true);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (!modalRoot.classList.contains('open')) {
                return;
            }
            if (event.key === 'Escape') {
                closeModal(false, true);
            }
        });

        document.getElementById('gsuiCancel').addEventListener('click', function () {
            closeModal(false, true);
        });
        document.getElementById('gsuiConfirm').addEventListener('click', function () {
            handleConfirm();
        });
        document.getElementById('gsuiInput').addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                handleConfirm();
            }
        });
    }

    function handleConfirm() {
        const input = document.getElementById('gsuiInput');
        const errorNode = document.getElementById('gsuiError');

        if (state.mode === 'prompt') {
            const value = input.value;
            if (state.required && !value.trim()) {
                errorNode.textContent = 'Campo obbligatorio';
                errorNode.classList.add('open');
                return;
            }
            if (typeof state.validator === 'function') {
                const msg = state.validator(value);
                if (msg) {
                    errorNode.textContent = msg;
                    errorNode.classList.add('open');
                    return;
                }
            }
            closeModal(value, false);
            return;
        }

        closeModal(true, false);
    }

    function closeModal(value, cancelled) {
        if (!modalRoot) {
            return;
        }

        const resolver = state.resolver;
        const mode = state.mode;
        state.resolver = null;
        state.validator = null;
        state.required = false;

        modalRoot.classList.remove('open');
        const errorNode = document.getElementById('gsuiError');
        errorNode.textContent = '';
        errorNode.classList.remove('open');

        if (typeof resolver !== 'function') {
            return;
        }

        if (cancelled) {
            if (mode === 'confirm') {
                resolver(false);
            } else if (mode === 'prompt') {
                resolver(null);
            } else {
                resolver(true);
            }
            return;
        }

        resolver(value);
    }

    function openModal(options) {
        ensureUi();

        const titleNode = document.getElementById('gsuiTitle');
        const messageNode = document.getElementById('gsuiMessage');
        const input = document.getElementById('gsuiInput');
        const cancel = document.getElementById('gsuiCancel');
        const confirm = document.getElementById('gsuiConfirm');
        const errorNode = document.getElementById('gsuiError');

        state.mode = options.mode || 'alert';
        state.validator = options.validator || null;
        state.required = !!options.required;

        titleNode.textContent = options.title || 'Messaggio';
        messageNode.textContent = options.message || '';
        errorNode.textContent = '';
        errorNode.classList.remove('open');

        cancel.style.display = state.mode === 'alert' ? 'none' : '';
        cancel.textContent = options.cancelText || 'Annulla';
        confirm.textContent = options.confirmText || (state.mode === 'alert' ? 'Chiudi' : 'Conferma');

        if (state.mode === 'prompt') {
            input.style.display = '';
            input.type = options.inputType || 'text';
            input.placeholder = options.placeholder || '';
            input.value = options.defaultValue || '';
        } else {
            input.style.display = 'none';
            input.value = '';
            input.placeholder = '';
        }

        modalRoot.classList.add('open');

        return new Promise(function (resolve) {
            state.resolver = resolve;
            setTimeout(function () {
                if (state.mode === 'prompt') {
                    input.focus();
                } else {
                    confirm.focus();
                }
            }, 0);
        });
    }

    function alertDialog(message, options) {
        const settings = options || {};
        return openModal({
            mode: 'alert',
            title: settings.title || 'Avviso',
            message: message,
            confirmText: settings.confirmText || 'OK',
        });
    }

    function confirmDialog(message, options) {
        const settings = options || {};
        return openModal({
            mode: 'confirm',
            title: settings.title || 'Conferma',
            message: message,
            confirmText: settings.confirmText || 'Conferma',
            cancelText: settings.cancelText || 'Annulla',
        });
    }

    function promptDialog(message, options) {
        const settings = options || {};
        return openModal({
            mode: 'prompt',
            title: settings.title || 'Inserisci valore',
            message: message,
            confirmText: settings.confirmText || 'Conferma',
            cancelText: settings.cancelText || 'Annulla',
            placeholder: settings.placeholder || '',
            defaultValue: settings.defaultValue || '',
            inputType: settings.inputType || 'text',
            required: !!settings.required,
            validator: settings.validator || null,
        });
    }

    function toast(message, type, timeout) {
        ensureUi();

        const node = document.createElement('div');
        node.className = 'gsui-toast ' + (type || 'info');
        node.textContent = message || '';

        toastRoot.appendChild(node);
        requestAnimationFrame(function () {
            node.classList.add('open');
        });

        const ttl = Number(timeout || 4200);
        setTimeout(function () {
            node.classList.remove('open');
            setTimeout(function () {
                if (node.parentNode) {
                    node.parentNode.removeChild(node);
                }
            }, 220);
        }, ttl);
    }

    window.GliSqualettiUI = {
        alert: alertDialog,
        confirm: confirmDialog,
        prompt: promptDialog,
        toast: toast,
    };
})();
