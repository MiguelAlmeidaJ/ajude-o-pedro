document.addEventListener('DOMContentLoaded', () => {
    const raffleForm = document.querySelector('[data-raffle-form]');

    if (raffleForm) {
        const grid = raffleForm.querySelector('[data-number-grid]');
        const list = raffleForm.querySelector('[data-selected-list]');
        const count = raffleForm.querySelector('[data-selected-count]');
        const total = raffleForm.querySelector('[data-selected-total]');
        const submit = raffleForm.querySelector('[data-submit]');
        const inputs = raffleForm.querySelector('[data-selected-inputs]');
        const prev = raffleForm.querySelector('[data-number-prev]');
        const next = raffleForm.querySelector('[data-number-next]');
        const pageLabel = raffleForm.querySelector('[data-number-page-label]');
        const search = raffleForm.querySelector('[data-number-search]');
        const clearSearch = raffleForm.querySelector('[data-number-search-clear]');

        const price = Number(raffleForm.dataset.price || 0);
        const digits = Number(raffleForm.dataset.digits || 3);
        const campaignId = raffleForm.dataset.campaignId;
        const api = raffleForm.dataset.numbersApi;
        const selected = new Set();

        let currentPage = 1;
        let currentPages = 1;
        let searchTimer = null;
        let requestController = null;

        const formatNumber = (value) => String(value).padStart(digits, '0');

        const renderSummary = () => {
            const values = [...selected].sort((a, b) => a - b);

            count.textContent = values.length;
            total.textContent = (values.length * price).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
            submit.disabled = values.length === 0;

            list.innerHTML = values.length
                ? values.map((n) => '<span class="selected-pill">#' + formatNumber(n) + '</span>').join('')
                : '<span class="text-secondary small">Nenhum número selecionado.</span>';

            inputs.innerHTML = values
                .map((n) => '<input type="hidden" name="numbers[]" value="' + n + '">')
                .join('');
        };

        const renderNumbers = (items) => {
            if (!items.length) {
                grid.innerHTML = '<div class="number-grid-empty">Nenhum número encontrado.</div>';
                return;
            }

            grid.innerHTML = items.map((item) => {
                const number = Number(item.number);
                const available = item.status === 'available';
                const isSelected = selected.has(number) && available;

                if (!available && selected.has(number)) {
                    selected.delete(number);
                }

                return '<button type="button" class="number-button' +
                    (isSelected ? ' selected' : '') +
                    (!available ? ' unavailable' : '') +
                    '" data-number="' + number + '"' +
                    (!available ? ' disabled' : '') +
                    ' aria-pressed="' + (isSelected ? 'true' : 'false') + '">' +
                    formatNumber(number) +
                    '</button>';
            }).join('');

            renderSummary();
        };

        const loadNumbers = async (page = 1, query = '') => {
            if (requestController) requestController.abort();
            requestController = new AbortController();

            grid.classList.add('is-loading');
            pageLabel.textContent = 'Carregando...';
            prev.disabled = true;
            next.disabled = true;

            try {
                const params = new URLSearchParams({
                    campaign_id: campaignId,
                    page: String(page)
                });
                if (query) params.set('q', query);

                const response = await fetch(api + '?' + params.toString(), {
                    cache: 'no-store',
                    signal: requestController.signal,
                    headers: {'Accept': 'application/json'}
                });

                if (!response.ok) throw new Error('Não foi possível carregar os números.');

                const data = await response.json();
                if (!data.ok) throw new Error(data.message || 'Não foi possível carregar os números.');

                currentPage = Number(data.page || 1);
                currentPages = Number(data.pages || 1);
                renderNumbers(data.items || []);

                if (data.search) {
                    pageLabel.textContent = data.items && data.items.length
                        ? 'Resultado da busca'
                        : 'Número não encontrado';
                    prev.disabled = true;
                    next.disabled = true;
                } else {
                    pageLabel.textContent = data.from && data.to
                        ? 'Números ' + formatNumber(data.from) + ' a ' + formatNumber(data.to) + ' • página ' + currentPage + ' de ' + currentPages
                        : 'Página ' + currentPage + ' de ' + currentPages;
                    prev.disabled = currentPage <= 1;
                    next.disabled = currentPage >= currentPages;
                }
            } catch (error) {
                if (error.name === 'AbortError') return;
                grid.innerHTML = '<div class="number-grid-empty text-danger">Não foi possível carregar os números. Tente novamente.</div>';
                pageLabel.textContent = 'Erro ao carregar';
            } finally {
                grid.classList.remove('is-loading');
            }
        };

        grid.addEventListener('click', (event) => {
            const button = event.target.closest('[data-number]');
            if (!button || button.disabled) return;

            const number = Number(button.dataset.number);

            if (selected.has(number)) {
                selected.delete(number);
                button.classList.remove('selected');
                button.setAttribute('aria-pressed', 'false');
            } else {
                if (selected.size >= 50) {
                    alert('É possível reservar até 50 números por compra.');
                    return;
                }
                selected.add(number);
                button.classList.add('selected');
                button.setAttribute('aria-pressed', 'true');
            }

            renderSummary();
        });

        prev.addEventListener('click', () => {
            if (currentPage > 1) loadNumbers(currentPage - 1, search.value.trim());
        });

        next.addEventListener('click', () => {
            if (currentPage < currentPages) loadNumbers(currentPage + 1, search.value.trim());
        });

        search.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const query = search.value.trim();
                loadNumbers(1, query);
            }, 250);
        });

        clearSearch.addEventListener('click', () => {
            search.value = '';
            loadNumbers(1, '');
            search.focus();
        });

        renderSummary();
        loadNumbers();
    }

    const donationForm = document.querySelector('[data-donation-form]');
    if (donationForm) {
        const amount = donationForm.querySelector('[data-donation-amount]');
        const presets = [...donationForm.querySelectorAll('[data-donation-preset]')];

        presets.forEach((button) => {
            button.addEventListener('click', () => {
                amount.value = button.dataset.donationPreset || '';
                amount.focus();

                presets.forEach((item) => item.classList.remove('btn-primary'));
                presets.forEach((item) => item.classList.add('btn-light'));
                button.classList.remove('btn-light');
                button.classList.add('btn-primary');
            });
        });

        amount.addEventListener('input', () => {
            presets.forEach((item) => {
                const isSelected = Number(item.dataset.donationPreset) === Number(amount.value);
                item.classList.toggle('btn-primary', isSelected);
                item.classList.toggle('btn-light', !isSelected);
            });
        });
    }
});
