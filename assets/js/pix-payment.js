window.PixPayment = (() => {
    const copyText = async (text) => {
        if (!text) return false;

        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (_) {
                // usa fallback abaixo
            }
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        textarea.style.pointerEvents = 'none';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);

        let copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (_) {
            copied = false;
        }

        textarea.remove();
        return copied;
    };

    const setCopiedState = (button, successLabel, originalHtml) => {
        button.innerHTML = successLabel;
        window.setTimeout(() => {
            button.innerHTML = originalHtml;
        }, 2200);
    };

    const bindCopy = (selector, value, successLabel) => {
        const button = document.querySelector(selector);
        if (!button) return;

        const originalHtml = button.innerHTML;
        button.addEventListener('click', async () => {
            const copied = await copyText(value);
            if (copied) {
                setCopiedState(button, successLabel, originalHtml);
                return;
            }

            const fallback = document.querySelector('[data-pix-code]');
            if (fallback) {
                fallback.focus();
                fallback.select();
            }
            alert('Não foi possível copiar automaticamente. Selecione o código exibido e copie manualmente.');
        });
    };

    const init = ({payload, key, link = ''}) => {
        const qr = document.querySelector('[data-qr-code]');

        if (qr && payload) {
            qr.innerHTML = '';
            if (typeof QRCode === 'function') {
                try {
                    new QRCode(qr, {
                        text: payload,
                        width: 240,
                        height: 240,
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } catch (error) {
                    qr.innerHTML = '<div class="text-danger small p-3">Não foi possível gerar o QR Code. Use o Pix Copia e Cola abaixo.</div>';
                }
            } else {
                qr.innerHTML = '<div class="text-danger small p-3">Gerador de QR Code indisponível. Use o Pix Copia e Cola abaixo.</div>';
            }
        }

        bindCopy('[data-copy-pix]', payload, '<i class="bi bi-check2 me-2"></i>Pix copiado');
        bindCopy('[data-copy-key]', key, '<i class="bi bi-check2 me-2"></i>Chave copiada');

        if (link) {
            bindCopy('[data-copy-link]', link, '<i class="bi bi-check2 me-1"></i>Link copiado');
        }

        const code = document.querySelector('[data-pix-code]');
        if (code) {
            code.addEventListener('click', () => code.select());
        }
    };

    return {init, copyText};
})();
