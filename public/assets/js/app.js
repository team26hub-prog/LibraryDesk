document.querySelectorAll('[data-table-search]').forEach((input) => {
    input.addEventListener('input', () => {
        const table = input.closest('.table-panel')?.querySelector('[data-search-table]');
        const query = input.value.trim().toLowerCase();

        table?.querySelectorAll('tbody tr:not(.empty-row)').forEach((row) => {
            row.hidden = !row.textContent.toLowerCase().includes(query);
        });
    });
});

const alerts = window.Swal?.mixin({
    confirmButtonColor: '#286852',
    cancelButtonColor: '#65736b',
    customClass: { popup: 'library-alert' },
    heightAuto: false,
});

const flashNotice = document.querySelector('[data-flash]');
if (alerts && flashNotice) {
    const type = flashNotice.dataset.flashType;
    const icon = ['success', 'error', 'warning', 'info', 'question'].includes(type) ? type : 'info';
    alerts.fire({
        icon,
        titleText: flashNotice.dataset.flashTitle || (icon === 'success' ? 'Success' : icon === 'error' ? 'Something went wrong' : 'Please note'),
        text: flashNotice.textContent.trim(),
        confirmButtonText: 'OK',
    });
    flashNotice.hidden = true;
}

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    let confirmed = false;
    let pending = false;
    form.addEventListener('submit', async (event) => {
        if (confirmed) {
            confirmed = false;
            return;
        }
        event.preventDefault();
        if (pending) return;
        pending = true;
        const submitter = event.submitter;
        try {
            const isDelete = new URL(form.action, window.location.href).pathname.endsWith('/delete');
            const isReturn = new URL(form.action, window.location.href).pathname.endsWith('/return');
            const approved = alerts
                ? (await alerts.fire({
                    icon: form.dataset.confirmIcon || (isReturn ? 'question' : 'warning'),
                    titleText: form.dataset.confirmTitle || (isDelete ? 'Delete this record?' : isReturn ? 'Confirm book return?' : 'Are you sure?'),
                    text: form.dataset.confirm,
                    showCancelButton: true,
                    confirmButtonText: form.dataset.confirmButton || (isDelete ? 'Delete' : isReturn ? 'Mark as returned' : 'Confirm'),
                    confirmButtonColor: isDelete ? '#aa5145' : '#286852',
                    cancelButtonText: 'Cancel',
                    focusCancel: true,
                    reverseButtons: true,
                })).isConfirmed
                : window.confirm(form.dataset.confirm);
            if (approved) {
                confirmed = true;
                form.requestSubmit(submitter || undefined);
                confirmed = false;
            }
        } finally {
            pending = false;
        }
    });
});

// Show one validation dialog even when several fields are invalid.
let validationAlertOpen = false;
document.addEventListener('invalid', (event) => {
    if (!alerts) return;
    event.preventDefault();
    if (validationAlertOpen) return;
    validationAlertOpen = true;
    const field = event.target;
    const label = field.labels?.[0]?.childNodes[0]?.textContent.trim();
    alerts.fire({
        icon: 'warning',
        titleText: label ? `Check ${label.toLowerCase()}` : 'Check your details',
        text: field.validationMessage,
        confirmButtonText: 'Review field',
        returnFocus: false,
    }).then(() => {
        validationAlertOpen = false;
        field.focus();
    });
}, true);

// Keep the mobile account bar below the navigation, including its expanded menu.
const mobileSidebar = document.querySelector('.sidebar');
if (mobileSidebar) {
    const updateMobileNavHeight = () => {
        document.documentElement.style.setProperty('--mobile-nav-height', `${mobileSidebar.getBoundingClientRect().height}px`);
    };
    updateMobileNavHeight();
    if (typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(updateMobileNavHeight).observe(mobileSidebar);
    } else {
        window.addEventListener('resize', updateMobileNavHeight);
    }
}

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    const input = document.getElementById(toggle.getAttribute('aria-controls'));
    if (!input) return;
    const fieldName = input.labels?.[0]?.textContent.trim().toLowerCase() || 'password';
    const syncVisibility = () => {
        const visible = input.type === 'text';
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${fieldName}`);
    };
    syncVisibility();
    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        input.type = input.type === 'password' ? 'text' : 'password';
        syncVisibility();
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
    // Run custom validation before displaying field errors.
    form.noValidate = true;
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
            if (!alerts) form.reportValidity();
        }
    });
});
