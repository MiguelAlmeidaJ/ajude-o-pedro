document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-raffle-form]');
    if (!form) return;

    const checks = [...form.querySelectorAll('input[name="numbers[]"]')];
    const list = document.querySelector('[data-selected-list]');
    const count = document.querySelector('[data-selected-count]');
    const total = document.querySelector('[data-selected-total]');
    const submit = form.querySelector('[data-submit]');
    const price = Number(form.dataset.price || 0);

    const render = () => {
        const selected = checks.filter((item) => item.checked).map((item) => item.value);
        count.textContent = selected.length;
        total.textContent = (selected.length * price).toLocaleString('pt-BR', {style:'currency', currency:'BRL'});
        submit.disabled = selected.length === 0;
        list.innerHTML = selected.length
            ? selected.map((n) => '<span class="selected-pill">#' + String(n).padStart(form.dataset.digits || 3, '0') + '</span>').join('')
            : '<span class="text-secondary small">Nenhum número selecionado.</span>';
    };

    checks.forEach((item) => item.addEventListener('change', render));
    render();
});