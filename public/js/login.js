document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('loaded');

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');
    const togglePassword = document.getElementById('togglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');
    const submitBtn = document.getElementById('loginSubmitBtn');

    if (emailInput) {
        emailInput.addEventListener('input', function () {
            emailInput.classList.remove('is-invalid');
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            passwordInput.classList.remove('is-invalid');
        });
    }

    if (loginForm && emailInput && passwordInput) {
        loginForm.addEventListener('submit', function (e) {
            let valid = true;

            if (!emailInput.value.trim()) {
                emailInput.classList.add('is-invalid');
                valid = false;
            }

            if (!passwordInput.value.trim()) {
                passwordInput.classList.add('is-invalid');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                return;
            }

            if (loginForm.dataset.csrfReady === '1') {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Signing in...';
                }
                return;
            }

            e.preventDefault();

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Signing in...';
            }

            refreshLoginCsrfToken(loginForm).finally(function () {
                loginForm.dataset.csrfReady = '1';
                loginForm.submit();
            });
        });
    }

    if (togglePassword && passwordInput && togglePasswordIcon) {
        togglePassword.addEventListener('click', function () {
            const hidden = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', hidden ? 'text' : 'password');
            togglePasswordIcon.classList.toggle('bi-eye', !hidden);
            togglePasswordIcon.classList.toggle('bi-eye-slash', hidden);
            togglePassword.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
            togglePassword.setAttribute('title', hidden ? 'Hide password' : 'Show password');
        });
    }
});

function refreshLoginCsrfToken(loginForm) {
    return fetch('/refresh-csrf', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    }).then(function (response) {
        if (!response.ok) {
            return null;
        }

        return response.json();
    }).then(function (data) {
        if (!data || !data.token) {
            return;
        }

        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', data.token);
        }

        const hidden = loginForm.querySelector('input[name="_token"]');
        if (hidden) {
            hidden.value = data.token;
        }
    }).catch(function () {
        // Submit with the token already on the form.
    });
}
