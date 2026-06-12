function showAlert(el, message, type = 'error') {
    el.textContent = message;
    el.className = `alert alert-${type} visible`;
}

function hideAlert(el) {
    el.classList.remove('visible');
}

function setLoading(btn, state) {
    btn.disabled = state;
    btn.classList.toggle('loading', state);
}

function setFieldError(input, message) {
    const errEl = input.closest('.form-group')?.querySelector('.field-error');
    input.classList.toggle('error', !!message);
    if (errEl) {
        errEl.textContent = message || '';
        errEl.classList.toggle('visible', !!message);
    }
}

function clearErrors(form) {
    form.querySelectorAll('input.error').forEach(i => i.classList.remove('error'));
    form.querySelectorAll('.field-error').forEach(e => { e.textContent = ''; e.classList.remove('visible'); });
}

async function apiFetch(url, body) {
    const res  = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
    const data = await res.json();
    return { ok: res.ok, status: res.status, data };
}

document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = btn.previousElementSibling;
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        btn.textContent = isText ? '👁' : '🙈';
    });
});

function initPasswordStrength() {
    const pwInput = document.getElementById('password');
    const bar     = document.getElementById('pw-strength-fill');
    const label   = document.getElementById('pw-strength-label');
    const wrapper = document.getElementById('pw-strength');
    if (!pwInput || !bar) return;

    const levels = [
        { label: 'Prea slabă',  color: '#ef4444', pct: 20  },
        { label: 'Slabă',       color: '#f97316', pct: 40  },
        { label: 'Medie',       color: '#eab308', pct: 60  },
        { label: 'Bună',        color: '#22c55e', pct: 80  },
        { label: 'Excelentă',   color: '#16a34a', pct: 100 },
    ];

    function score(pw) {
        let s = 0;
        if (pw.length >= 8)           s++;
        if (pw.length >= 12)          s++;
        if (/[A-Z]/.test(pw))         s++;
        if (/[0-9]/.test(pw))         s++;
        if (/[^A-Za-z0-9]/.test(pw))  s++;
        return s;
    }

    pwInput.addEventListener('input', () => {
        const pw = pwInput.value;
        if (!pw) { wrapper.classList.remove('visible'); return; }
        wrapper.classList.add('visible');
        const lvl = levels[Math.max(0, score(pw) - 1)];
        bar.style.width      = lvl.pct + '%';
        bar.style.background = lvl.color;
        label.textContent    = lvl.label;
    });
}

function initLoginPage() {
    const form  = document.getElementById('login-form');
    if (!form) return;

    const alert = document.getElementById('alert');
    const btn   = form.querySelector('button[type=submit]');

    form.addEventListener('submit', async e => {
        e.preventDefault();
        clearErrors(form);
        hideAlert(alert);

        const email    = form.email.value.trim();
        const password = form.password.value;

        let valid = true;
        if (!email)    { setFieldError(form.email,    'Emailul este obligatoriu.'); valid = false; }
        if (!password) { setFieldError(form.password, 'Parola este obligatorie.');  valid = false; }
        if (!valid) return;

        setLoading(btn, true);
        const { ok, data } = await apiFetch('/beverages-app/api/auth.php?action=login', { email, password });
        setLoading(btn, false);

        if (ok) {
            if (data.token) localStorage.setItem('jwt_token', data.token);
            showAlert(alert, 'Autentificare reușită! Redirecționare…', 'success');
            setTimeout(() => { window.location.href = '/beverages-app/pages/dashboard.php'; }, 800);
        } else {
            showAlert(alert, data.error || 'Eroare la autentificare.');
        }
    });
}

function initRegisterPage() {
    const form = document.getElementById('register-form');
    if (!form) return;

    const alert   = document.getElementById('alert');
    const btn     = form.querySelector('button[type=submit]');
    const pwInput = form.querySelector('#password');
    const pw2     = form.querySelector('#password2');

    initPasswordStrength();

    pw2?.addEventListener('input', () => {
        if (pw2.value && pw2.value !== pwInput.value) {
            setFieldError(pw2, 'Parolele nu coincid.');
        } else {
            setFieldError(pw2, '');
        }
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        clearErrors(form);
        hideAlert(alert);

        const username  = form.username.value.trim();
        const email     = form.email.value.trim();
        const password  = pwInput.value;
        const password2 = pw2.value;

        let valid = true;
        if (!username) { setFieldError(form.username, 'Username-ul este obligatoriu.'); valid = false; }
        if (!email)    { setFieldError(form.email,    'Emailul este obligatoriu.');     valid = false; }
        if (!password) { setFieldError(pwInput,       'Parola este obligatorie.');      valid = false; }
        if (password && password.length < 8) {
            setFieldError(pwInput, 'Parola trebuie să aibă cel puțin 8 caractere.');
            valid = false;
        }
        if (password && password2 && password !== password2) {
            setFieldError(pw2, 'Parolele nu coincid.');
            valid = false;
        }
        if (!valid) return;

        setLoading(btn, true);
        const { ok, data } = await apiFetch('/beverages-app/api/auth.php?action=register', { username, email, password });
        setLoading(btn, false);

        if (ok) {
            showAlert(alert, 'Cont creat cu succes! Redirecționare spre login…', 'success');
            setTimeout(() => { window.location.href = '/beverages-app/pages/login.php'; }, 1200);
        } else {
            showAlert(alert, data.error || 'Eroare la înregistrare.');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initLoginPage();
    initRegisterPage();
});
