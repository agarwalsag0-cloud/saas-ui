(function () {
    const toggle = document.querySelector('[data-sidebar-toggle]');
    if (toggle) {
        toggle.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
    }
    document.addEventListener('click', (event) => {
        if (document.body.classList.contains('sidebar-open') && event.target.closest('.content, .topbar')) {
            document.body.classList.remove('sidebar-open');
        }
    });

    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            const message = element.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-table-search]').forEach((input) => {
        const target = document.querySelector(input.getAttribute('data-table-search'));
        if (!target) return;
        input.addEventListener('input', () => {
            const term = input.value.toLowerCase();
            target.querySelectorAll('tbody tr').forEach((row) => {
                row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    });

    document.querySelectorAll('[data-copy-url]').forEach((button) => {
        button.addEventListener('click', async () => {
            const raw = button.getAttribute('data-copy-url') || '/';
            const full = new URL(raw, window.location.origin).href;
            try {
                await navigator.clipboard.writeText(full);
                const old = button.textContent;
                button.textContent = 'Copied!';
                setTimeout(() => button.textContent = old, 1400);
            } catch (e) {
                window.prompt('Copy this link:', full);
            }
        });
    });

    document.querySelectorAll('[data-theme-preset]').forEach((radio) => {
        radio.addEventListener('change', () => {
            if (!radio.checked) return;
            let preset = {};
            try { preset = JSON.parse(radio.getAttribute('data-theme-preset') || '{}'); } catch (e) {}
            Object.keys(preset).forEach((key) => {
                const field = document.querySelector(`[data-theme-field="${key}"]`);
                if (field && key !== 'label') field.value = preset[key];
            });
        });
    });

    // Password visibility toggle (Stitch login screens)
    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.querySelector(button.getAttribute('data-toggle-password'));
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.textContent = show ? 'Hide' : 'Show';
        });
    });

    // Auto-dismiss flash alerts (top-right toast behavior on mobile-safe flow)
    window.setTimeout(() => {
        document.querySelectorAll('.alert').forEach((alert) => {
            alert.style.transition = 'opacity .25s ease, transform .25s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-4px)';
            window.setTimeout(() => alert.remove(), 260);
        });
    }, 6000);
})();
