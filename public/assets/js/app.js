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
    const completed = icon === 'success';
    alerts.fire({
        icon,
        titleText: flashNotice.dataset.flashTitle || (icon === 'success' ? 'Success' : icon === 'error' ? 'Something went wrong' : 'Please note'),
        text: flashNotice.textContent.trim(),
        toast: completed,
        position: completed ? 'top-end' : 'center',
        showConfirmButton: !completed,
        showCancelButton: !completed,
        confirmButtonText: icon === 'error' || icon === 'warning' ? 'Review details' : 'OK',
        cancelButtonText: 'Close',
        timer: completed ? 3000 : undefined,
        timerProgressBar: completed,
        returnFocus: false,
    }).then((result) => {
        if (result.isConfirmed && (icon === 'error' || icon === 'warning')) {
            document.querySelector('.page-content form input:not([type="hidden"]):not([disabled])')?.focus();
        }
    });
    flashNotice.hidden = true;
}

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
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        returnFocus: false,
    }).then((result) => {
        validationAlertOpen = false;
        if (result.isConfirmed) field.focus();
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

const validateFullName = (input) => {
    const name = input.value.trim();
    const pattern = new RegExp(`^(?:${input.pattern})$`, 'u');
    input.setCustomValidity(
        name === '' || Array.from(name).length > 150 || !pattern.test(name)
            ? 'Enter a full name using letters, spaces, apostrophes, or hyphens, up to 150 characters.'
            : ''
    );
};

document.querySelectorAll('[data-full-name]').forEach((input) => {
    input.addEventListener('input', () => validateFullName(input));
    input.addEventListener('blur', () => {
        input.value = input.value.trim();
        validateFullName(input);
    });
});
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
        validateFullName(name);
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

// Register after validation so invalid forms never ask to confirm an action.
const confirmationActions = [
    ['/users/store', 'Add this user?', 'Create this library user with the details entered?', 'Add user'],
    ['/users/update', 'Save user changes?', 'Update this user with the details entered?', 'Save changes'],
    ['/users/delete', 'Delete this user?', 'This will permanently delete the user. This action cannot be undone.', 'Delete user', 'warning'],
    ['/books/store', 'Add this book?', 'Add this book to the library catalogue?', 'Add book'],
    ['/books/update', 'Save book changes?', 'Update this book with the details entered?', 'Save changes'],
    ['/books/delete', 'Delete this book?', 'This will permanently delete the book. This action cannot be undone.', 'Delete book', 'warning'],
    ['/borrow/checkout', 'Check out this book?', 'Record the selected member, book, and due date as a new borrow?', 'Check out'],
    ['/borrow/return', 'Mark this book as returned?', 'Confirm the book has been returned to the library.', 'Mark as returned'],
    ['/book-requests/cancel', 'Cancel your book request?', 'Remove your pending request? You can request the book again later.', 'Cancel request', 'warning'],
    ['/book-requests', 'Request this book?', 'Send this book request to the library admin?', 'Request book'],
    ['/profile/name', 'Save your name?', 'Update your profile with the name entered?', 'Save name'],
    ['/profile/password', 'Change your password?', 'Use the new password for future sign-ins?', 'Change password'],
    ['/register', 'Create your account?', 'Register your library account with the details entered?', 'Create account'],
    ['/logout', 'Sign out?', 'You can sign in again whenever you need to.', 'Sign out'],
];

document.querySelectorAll('form[method="post"]').forEach((form) => {
    let confirmed = false;
    let pending = false;
    form.addEventListener('submit', async (event) => {
        if (event.defaultPrevented || confirmed) return;
        const path = new URL(form.action, window.location.href).pathname;
        const submitter = event.submitter;
        let action = confirmationActions.find(([suffix]) => path.endsWith(suffix));
        if (path.endsWith('/book-requests/status')) {
            if (submitter?.value === 'granted') {
                action = [path, 'Grant this book request?', 'Mark this request as Granted? Record any book checkout separately in Borrow & return.', 'Grant request'];
            } else if (submitter?.value === 'cancelled') {
                action = [path, 'Cancel this book request?', 'Mark this request as Cancelled? The member can request the book again.', 'Cancel request', 'warning'];
            }
        }
        if (!action && !form.dataset.confirm) return;
        event.preventDefault();
        if (pending) return;
        if (!form.checkValidity()) {
            if (!alerts) form.reportValidity();
            return;
        }
        pending = true;
        const title = form.dataset.confirmTitle || action?.[1] || 'Are you sure?';
        const message = action?.[2] || form.dataset.confirm;
        const icon = form.dataset.confirmIcon || action?.[4] || 'question';
        try {
            const approved = alerts
                ? (await alerts.fire({
                    icon,
                    titleText: title,
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: form.dataset.confirmButton || action?.[3] || 'OK',
                    confirmButtonColor: icon === 'warning' ? '#aa5145' : '#286852',
                    cancelButtonText: 'Cancel',
                    focusCancel: true,
                    reverseButtons: true,
                })).isConfirmed
                : window.confirm(`${title}\n${message}`);
            if (approved) {
                confirmed = true;
                try {
                    // Preserve the clicked button's name/value (Granted versus Cancel).
                    form.requestSubmit(submitter || undefined);
                } finally {
                    confirmed = false;
                }
            }
        } finally {
            pending = false;
        }
    });
});
