document.querySelectorAll('[data-table-search]').forEach((input) => {
    input.addEventListener('input', () => {
        const table = input.closest('.table-panel')?.querySelector('[data-search-table]');
        const query = input.value.trim().toLowerCase();

        table?.querySelectorAll('tbody tr:not(.empty-row)').forEach((row) => {
            row.hidden = !row.textContent.toLowerCase().includes(query);
        });
    });
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-nav-toggle]').forEach((toggle) => {
    const sidebar = toggle.closest('.sidebar');
    if (!sidebar) return;

    const setOpen = (open) => {
        sidebar.classList.toggle('is-nav-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
    };

    toggle.addEventListener('click', () => {
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    sidebar.querySelectorAll('.primary-nav a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });

    window.matchMedia('(min-width: 621px)').addEventListener('change', () => setOpen(false));
});

const controlCharacterPattern = /[\u0000-\u001f\u007f]/;
const strictEmailPattern = /^[^\s@]{1,64}@(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?$/i;

const isStrictEmail = (email) => email.length <= 190 && strictEmailPattern.test(email);

document.querySelectorAll('form[data-auth-login], form[data-auth-register]').forEach((form) => {
    const email = form.querySelector('input[name="email"]');
    const password = form.querySelector('input[name="password"]');
    const name = form.querySelector('[data-auth-name]');
    const confirmation = form.querySelector('[data-auth-confirm-password]');
    const isRegistration = form.matches('[data-auth-register]');

    const validateName = () => {
        if (!name) return;
        const trimmedName = name.value.trim();
        name.setCustomValidity(
            trimmedName.length === 0 || controlCharacterPattern.test(trimmedName)
                ? 'Enter a name without control characters.'
                : ''
        );
    };

    const validatePassword = () => {
        if (!password) return;
        const byteLength = new TextEncoder().encode(password.value).length;
        password.setCustomValidity(
            password.value !== ''
                && (isRegistration ? byteLength < 8 || byteLength > 72 : byteLength > 4096)
                ? (isRegistration
                    ? 'Password must contain between 8 and 72 bytes.'
                    : 'Password must not exceed 4096 bytes.')
                : ''
        );
    };

    const validateConfirmation = () => {
        if (!confirmation || !password) return;
        confirmation.setCustomValidity(
            confirmation.value !== '' && confirmation.value !== password.value
                ? 'Passwords do not match.'
                : ''
        );
    };

    email?.addEventListener('blur', () => {
        email.value = email.value.trim().toLowerCase();
        email.setCustomValidity(
            email.value !== '' && (email.validity.typeMismatch || !isStrictEmail(email.value))
                ? 'Enter a valid email with a fully qualified domain, such as name@example.com.'
                : ''
        );
    });

    email?.addEventListener('input', () => email.setCustomValidity(''));
    name?.addEventListener('input', validateName);
    password?.addEventListener('input', () => {
        validatePassword();
        validateConfirmation();
    });
    confirmation?.addEventListener('input', validateConfirmation);

    form.addEventListener('submit', (event) => {
        if (email) {
            email.value = email.value.trim().toLowerCase();
            email.setCustomValidity(
                email.value !== '' && (email.validity.typeMismatch || !isStrictEmail(email.value))
                    ? 'Enter a valid email with a fully qualified domain, such as name@example.com.'
                    : ''
            );
        }
        validateName();
        validatePassword();
        validateConfirmation();

        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });
});