document.addEventListener('DOMContentLoaded', () => {
    const raffleForm = document.querySelector('[data-raffle-form]');

    if (raffleForm) {
        const checks = [...raffleForm.querySelectorAll('input[name="numbers[]"]')];
        const list = document.querySelector('[data-selected-list]');
        const count = document.querySelector('[data-selected-count]');
        const total = document.querySelector('[data-selected-total]');
        const submit = raffleForm.querySelector('[data-submit]');
        const price = Number(raffleForm.dataset.price || 0);

        const render = () => {
            const selected = checks.filter((item) => item.checked).map((item) => item.value);
            count.textContent = selected.length;
            total.textContent = (selected.length * price).toLocaleString('pt-BR', {style:'currency', currency:'BRL'});
            submit.disabled = selected.length === 0;
            list.innerHTML = selected.length
                ? selected.map((n) => '<span class="selected-pill">#' + String(n).padStart(raffleForm.dataset.digits || 3, '0') + '</span>').join('')
                : '<span class="text-secondary small">Nenhum número selecionado.</span>';
        };

        checks.forEach((item) => item.addEventListener('change', render));
        render();
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
                const selected = Number(item.dataset.donationPreset) === Number(amount.value);
                item.classList.toggle('btn-primary', selected);
                item.classList.toggle('btn-light', !selected);
            });
        });
    }
});
