(function () {
    'use strict';

    function showResult(ids, baseUrl) {
        const box  = document.getElementById('subticket-result');
        const list = document.getElementById('subticket-result-list');
        if (!box || !list) {
            return;
        }
        ids.forEach(function (id) {
            const li = document.createElement('li');
            const a  = document.createElement('a');
            a.href        = baseUrl + '?id=' + id;
            a.target      = '_blank';
            a.rel         = 'noopener';
            a.textContent = 'Chamado #' + id;
            li.appendChild(a);
            list.appendChild(li);
        });
        box.style.display = '';
    }

    document.addEventListener('click', function (ev) {
        const close = ev.target.closest('#subticket-result-close');
        if (close) {
            const box  = document.getElementById('subticket-result');
            const list = document.getElementById('subticket-result-list');
            if (box)  box.style.display = 'none';
            if (list) list.innerHTML    = '';
        }
    });

    document.addEventListener('click', async function (ev) {
        const btn = ev.target.closest('#subticket-btn');
        if (!btn) {
            return;
        }
        ev.preventDefault();

        const input = document.getElementById('subticket-count');
        const max   = parseInt(btn.dataset.max || '20', 10);
        let count   = parseInt((input && input.value) || '1', 10);
        if (!Number.isFinite(count) || count < 1) count = 1;
        if (count > max) count = max;

        const msg = count === 1
            ? 'Criar um SubChamado a partir deste chamado, com todo o histórico? Nenhuma notificação será enviada.'
            : 'Criar ' + count + ' SubChamados a partir deste chamado, cada um com todo o histórico? Nenhuma notificação será enviada.';
        if (!window.confirm(msg)) {
            return;
        }

        const meta  = document.querySelector('meta[property="glpi:csrf_token"]');
        const token = meta ? meta.getAttribute('content') : '';

        const body = new FormData();
        body.append('id', btn.dataset.ticketId);
        body.append('count', String(count));
        body.append('_glpi_csrf_token', token);

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('disabled');
        if (input) input.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader-2"></i> Criando' + (count > 1 ? ' (' + count + ')' : '') + '...';

        try {
            const resp = await fetch(btn.dataset.url, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': token
                }
            });
            let data = null;
            try { data = await resp.json(); } catch (_) {}

            if (resp.ok && data && data.ok) {
                showResult(data.ids, btn.dataset.ticketBaseUrl);
            } else {
                window.alert('Falha ao criar SubChamado (' + resp.status + '): ' + ((data && data.error) || 'resposta inválida'));
            }
        } catch (e) {
            window.alert('Falha ao criar SubChamado: ' + e.message);
        }
        btn.disabled = false;
        btn.classList.remove('disabled');
        if (input) input.disabled = false;
        btn.innerHTML = original;
    });
})();
