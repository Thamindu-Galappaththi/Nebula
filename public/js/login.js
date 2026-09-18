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

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Signing in...';
            }
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
