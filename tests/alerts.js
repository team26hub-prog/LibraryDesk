const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync('public/assets/js/app.js', 'utf8');

async function test(path, {approved = true, valid = true, submitter, auth = false, fallback = false} = {}) {
    const dialogs = [];
    const callbacks = [];
    let submissions = 0;
    let sentButton;
    let validations = 0;
    const email = {value: 'invalid', validity: {typeMismatch: true}, setCustomValidity() {}, addEventListener() {}};
    const form = {
        action: 'https://example.test/librarymanagement' + path,
        dataset: {},
        querySelector(selector) {return auth && selector.includes('email') ? email : null},
        matches() {return false},
        addEventListener(name, callback) {callbacks.push(callback)},
        checkValidity() {validations++; return valid},
        reportValidity() {},
        requestSubmit(button) {
            sentButton = button;
            const event = {defaultPrevented: false, submitter: button, preventDefault() {this.defaultPrevented = true}};
            callbacks.forEach(callback => callback(event));
            if (!event.defaultPrevented) submissions++;
        },
    };
    const document = {
        querySelector() {return null},
        querySelectorAll(selector) {
            if (selector === 'form[method="post"]') return [form];
            if (auth && selector === 'form[data-auth-login], form[data-auth-register]') return [form];
            return [];
        },
        addEventListener() {},
    };
    const window = {location: {href: 'https://example.test/librarymanagement/'}, confirm(message) {dialogs.push({text: message}); return approved}};
    if (!fallback) window.Swal = {mixin: () => ({fire: options => {dialogs.push(options); return Promise.resolve({isConfirmed: approved})}})};
    vm.runInNewContext(source, {document, window, URL, TextEncoder});
    const event = {defaultPrevented: false, submitter, preventDefault() {this.defaultPrevented = true}};
    for (const callback of callbacks) await callback(event);
    return {dialogs, submissions, sentButton, validations};
}

(async () => {
    for (const path of ['/users/store', '/users/update', '/users/delete', '/books/store', '/books/update', '/books/delete', '/borrow/checkout', '/borrow/return', '/book-requests', '/book-requests/cancel', '/profile/name', '/profile/password', '/register', '/logout']) {
        const dismissed = await test(path, {approved: false});
        assert.equal(dismissed.submissions, 0, 'Cancel must stop ' + path);
        assert.equal(dismissed.dialogs.length, 1);
        assert.equal(dismissed.dialogs[0].showCancelButton, true);
        const confirmed = await test(path);
        assert.equal(confirmed.submissions, 1, 'Confirmation must submit exactly once: ' + path);
        assert.equal(confirmed.dialogs.length, 1, 'Resubmission must not repeat the dialog');
    }
    for (const value of ['granted', 'cancelled']) {
        const button = {name: 'status', value};
        const result = await test('/book-requests/status', {submitter: button});
        assert.equal(result.submissions, 1);
        assert.equal(result.sentButton, button, 'Clicked status button must survive confirmation');
        assert.equal(result.dialogs[0].confirmButtonText, value === 'granted' ? 'Grant request' : 'Cancel request');
        const dismissed = await test('/book-requests/status', {approved: false, submitter: button});
        assert.equal(dismissed.submissions, 0);
    }
    const invalid = await test('/users/store', {valid: false});
    assert.equal(invalid.dialogs.length, 0, 'Invalid forms must not ask for confirmation');
    assert.equal(invalid.submissions, 0);
    const invalidAuth = await test('/register', {valid: false, auth: true});
    assert.equal(invalidAuth.dialogs.length, 0, 'Auth validation must run before confirmation');
    assert.equal(invalidAuth.submissions, 0);
    assert.equal((await test('/login')).dialogs.length, 0, 'Sign-in should retain its normal validation flow');
    assert.equal((await test('/users/delete', {fallback: true, approved: false})).submissions, 0);
    assert.equal((await test('/users/delete', {fallback: true})).submissions, 1);
    console.log('Passed: confirmations, cancellation, validation ordering, named status buttons, one submission, and browser fallback.');
})().catch(error => {console.error(error); process.exitCode = 1});
