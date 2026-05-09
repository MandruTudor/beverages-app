/**
 * auth.js
 * Logica JavaScript a paginilor de login și register.
 * Rulează în browser (client-side), NU pe server.
 *
 * Ce face:
 *  - Validează formularele înainte de a trimite date la server
 *  - Trimite date la API prin fetch() (cereri AJAX — fără reload de pagină)
 *  - Afișează mesaje de eroare/succes
 *  - Gestionează indicatorul de putere al parolei
 *  - Permite afișarea/ascunderea parolei
 */

/* ================================================================
   Funcții utilitare partajate — folosite atât pe login cât și register
   ================================================================ */

/**
 * showAlert(el, message, type)
 * Afișează un mesaj în zona de alertă (banda colorată de deasupra formularului).
 *
 * @param {HTMLElement} el      - Elementul DOM al alertei (div#alert)
 * @param {string}      message - Textul de afișat
 * @param {string}      type    - 'error' (roșu) sau 'success' (verde)
 */
function showAlert(el, message, type = 'error') {
    // Setăm textul alertei — textContent e mai sigur decât innerHTML (fără XSS)
    el.textContent = message;
    // Setăm clasa CSS: alert + alert-error sau alert-success + visible
    // clasa 'visible' schimbă display din 'none' în 'flex' (vezi CSS)
    el.className = `alert alert-${type} visible`;
}

/**
 * hideAlert(el)
 * Ascunde zona de alertă (elimină clasa 'visible').
 *
 * @param {HTMLElement} el - Elementul DOM al alertei
 */
function hideAlert(el) {
    // classList.remove() elimină o clasă fără a afecta celelalte clase
    el.classList.remove('visible');
}

/**
 * setLoading(btn, state)
 * Activează/dezactivează starea de "loading" pe buton.
 * În stare loading: butonul e dezactivat și arată spinner-ul.
 *
 * @param {HTMLButtonElement} btn   - Butonul de submit
 * @param {boolean}           state - true = loading, false = normal
 */
function setLoading(btn, state) {
    // disabled = true blochează click-urile și submiterea repetată
    btn.disabled = state;
    // classList.toggle(cls, force) — adaugă clasa dacă force=true, elimină dacă force=false
    btn.classList.toggle('loading', state);
}

/**
 * setFieldError(input, message)
 * Marchează un câmp input ca invalid și afișează mesajul de eroare sub el.
 * Dacă message e gol, elimină eroarea.
 *
 * @param {HTMLInputElement} input   - Input-ul cu eroare
 * @param {string}           message - Mesajul de eroare (sau '' pentru a șterge)
 */
function setFieldError(input, message) {
    // Urcăm în DOM la .form-group (părintele input-ului)
    // și găsim span-ul .field-error din interiorul lui
    const errEl = input.closest('.form-group')?.querySelector('.field-error');
    // !!message convertește string la boolean: '' → false, 'text' → true
    // toggle(cls, bool): adaugă 'error' dacă e mesaj, elimină dacă nu e
    input.classList.toggle('error', !!message);
    if (errEl) {
        errEl.textContent = message || ''; // Setăm sau ștergem textul
        errEl.classList.toggle('visible', !!message); // Afișăm sau ascundem
    }
}

/**
 * clearErrors(form)
 * Elimină toate erorile de câmp din formular.
 * Apelată la începutul fiecărui submit, înainte de re-validare.
 *
 * @param {HTMLFormElement} form - Elementul formularului
 */
function clearErrors(form) {
    // querySelectorAll returnează o NodeList cu toate input-urile cu clasa 'error'
    // forEach iterează și elimină clasa de pe fiecare
    form.querySelectorAll('input.error').forEach(i => i.classList.remove('error'));
    // La fel pentru span-urile de eroare: golim textul și ascundem
    form.querySelectorAll('.field-error').forEach(e => {
        e.textContent = '';
        e.classList.remove('visible');
    });
}

/**
 * apiFetch(url, body)
 * Trimite o cerere POST cu body JSON la un endpoint API.
 * Returnează un obiect cu { ok, status, data }.
 * Funcție async: returnează un Promise și poate folosi await.
 *
 * @param {string} url  - URL-ul endpoint-ului (ex: '/beverages-app/api/auth.php?action=login')
 * @param {object} body - Datele de trimis (vor fi serializate în JSON)
 * @returns {Promise<{ok: boolean, status: number, data: object}>}
 */
async function apiFetch(url, body) {
    // fetch() este API-ul modern al browserului pentru cereri HTTP
    // await suspendă execuția funcției async până când Promise-ul se rezolvă
    const res = await fetch(url, {
        method: 'POST', // Metoda HTTP
        headers: { 'Content-Type': 'application/json' }, // Anunțăm serverul că trimitem JSON
        body: JSON.stringify(body), // Serializăm obiectul JS în string JSON
    });
    // Parsăm răspunsul JSON primit de la server
    const data = await res.json();
    // Returnăm un obiect cu: ok (true dacă status 200-299), status numeric, și datele parsate
    return { ok: res.ok, status: res.status, data };
}

/* ================================================================
   Toggle vizibilitate parolă
   Se inițializează imediat, nu în DOMContentLoaded,
   pentru toate butoanele .toggle-pw din pagină.
   ================================================================ */

// querySelectorAll('.toggle-pw') returnează toți butoanele din pagină
// forEach adaugă event listener pe fiecare
document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', () => {
        // btn.previousElementSibling = input-ul de parolă imediat înaintea butonului în DOM
        const input = btn.previousElementSibling;
        // Verificăm tipul curent al input-ului
        const isText = input.type === 'text';
        // Comutăm tipul: text ↔ password
        input.type = isText ? 'password' : 'text';
        // Comutăm și emoji-ul butonului ca feedback vizual
        btn.textContent = isText ? '👁' : '🙈';
    });
});

/* ================================================================
   Indicatorul de putere al parolei (numai pe pagina de register)
   ================================================================ */

/**
 * initPasswordStrength()
 * Inițializează bara de putere a parolei.
 * Ascultă evenimentele 'input' pe câmpul de parolă și actualizează bara.
 * Este apelată din initRegisterPage() — funcționează numai dacă elementele există.
 */
function initPasswordStrength() {
    // Selectăm elementele necesare
    const pwInput  = document.getElementById('password');         // Input-ul de parolă
    const bar      = document.getElementById('pw-strength-fill'); // Bara colorată
    const label    = document.getElementById('pw-strength-label'); // Textul "Slabă" etc.
    const wrapper  = document.getElementById('pw-strength');      // Container-ul barei

    // Dacă nu există aceste elemente (ex: pe pagina de login), ieșim
    if (!pwInput || !bar) return;

    // Tabelul de niveluri: scorul minim, eticheta și culoarea barei
    const levels = [
        { max: 1, label: 'Prea slabă',  color: '#ef4444', pct: 20  }, // roșu intens
        { max: 2, label: 'Slabă',       color: '#f97316', pct: 40  }, // portocaliu
        { max: 3, label: 'Medie',       color: '#eab308', pct: 60  }, // galben
        { max: 4, label: 'Bună',        color: '#22c55e', pct: 80  }, // verde deschis
        { max: 5, label: 'Excelentă',   color: '#16a34a', pct: 100 }, // verde închis
    ];

    /**
     * score(pw)
     * Calculează un scor de putere al parolei de la 0 la 5.
     * Fiecare criteriu îndeplinit adaugă 1 punct.
     *
     * @param {string} pw - Parola introdusă
     * @returns {number} Scor 0-5
     */
    function score(pw) {
        let s = 0;
        if (pw.length >= 8)  s++; // Lungime minimă acceptabilă
        if (pw.length >= 12) s++; // Lungime bună
        if (/[A-Z]/.test(pw)) s++; // Conține literă mare (regex test)
        if (/[0-9]/.test(pw)) s++; // Conține cifră
        if (/[^A-Za-z0-9]/.test(pw)) s++; // Conține caracter special
        return s;
    }

    // Ascultăm evenimentul 'input' — se declanșează la fiecare tastă apăsată
    pwInput.addEventListener('input', () => {
        const pw = pwInput.value;
        // Dacă parola e goală, ascundem bara
        if (!pw) { wrapper.classList.remove('visible'); return; }
        // Afișăm bara
        wrapper.classList.add('visible');
        const s = score(pw);
        // Alegem nivelul corespunzător scorului
        // Math.max(0, s - 1) previne index negativ când s = 0
        const lvl = levels[Math.max(0, s - 1)];
        // Actualizăm lățimea și culoarea barei prin stiluri inline
        bar.style.width = lvl.pct + '%';
        bar.style.background = lvl.color;
        // Actualizăm textul etichetei
        label.textContent = lvl.label;
    });
}

/* ================================================================
   Pagina de Login
   ================================================================ */

/**
 * initLoginPage()
 * Inițializează comportamentul formularului de login.
 * Apelat din DOMContentLoaded — funcționează numai dacă #login-form există.
 */
function initLoginPage() {
    // Selectăm formularul de login; dacă nu există, suntem pe altă pagină
    const form  = document.getElementById('login-form');
    if (!form) return; // Guard clause: ieșim dacă nu suntem pe pagina de login

    const alert = document.getElementById('alert'); // Zona de alertă
    const btn   = form.querySelector('button[type=submit]'); // Butonul de submit

    // Adăugăm listener pe evenimentul 'submit' al formularului
    form.addEventListener('submit', async e => {
        // preventDefault() oprește comportamentul default al browser-ului
        // (reload de pagină la submit) și gestionăm noi trimiterea
        e.preventDefault();

        // Ștergem erorile vechi înainte de re-validare
        clearErrors(form);
        hideAlert(alert);

        // Citim valorile câmpurilor
        const email    = form.email.value.trim();    // trim() elimină spații din jur
        const password = form.password.value;

        // Validare client-side: verificăm că câmpurile nu sunt goale
        let valid = true;
        if (!email)    { setFieldError(form.email,    'Emailul este obligatoriu.'); valid = false; }
        if (!password) { setFieldError(form.password, 'Parola este obligatorie.');  valid = false; }
        // Dacă există erori de validare, nu trimitem cererea
        if (!valid) return;

        // Activăm starea de loading pe buton (dezactivat + spinner)
        setLoading(btn, true);

        // Trimitem cererea POST la API
        // await suspendă execuția până când răspunsul sosește
        const { ok, data } = await apiFetch('/beverages-app/api/auth.php?action=login', { email, password });

        // Dezactivăm starea de loading
        setLoading(btn, false);

        if (ok) {
            // Login reușit: afișăm mesaj verde și redirecționăm după 800ms
            showAlert(alert, 'Autentificare reușită! Redirecționare…', 'success');
            // setTimeout amână execuția callback-ului cu 800 milisecunde
            setTimeout(() => { window.location.href = '/beverages-app/dashboard.php'; }, 800);
        } else {
            // Login eșuat: afișăm mesajul de eroare primit de la server
            // || 'Eroare...' e fallback dacă data.error nu există
            showAlert(alert, data.error || 'Eroare la autentificare.');
        }
    });
}

/* ================================================================
   Pagina de Register
   ================================================================ */

/**
 * initRegisterPage()
 * Inițializează comportamentul formularului de înregistrare.
 * Apelat din DOMContentLoaded — funcționează numai dacă #register-form există.
 */
function initRegisterPage() {
    // Selectăm formularul de register
    const form = document.getElementById('register-form');
    if (!form) return; // Guard clause: ieșim dacă nu suntem pe pagina de register

    const alert   = document.getElementById('alert');
    const btn     = form.querySelector('button[type=submit]');
    const pwInput = form.querySelector('#password');   // Input-ul de parolă
    const pw2     = form.querySelector('#password2'); // Input-ul de confirmare parolă

    // Inițializăm bara de putere a parolei
    initPasswordStrength();

    // Validare live pe câmpul de confirmare parolă:
    // verificăm potrivirea în timp real, pe măsură ce userul scrie
    pw2?.addEventListener('input', () => {
        // ?. (optional chaining) previne eroarea dacă pw2 e null
        if (pw2.value && pw2.value !== pwInput.value) {
            // Parolele nu coincid — afișăm eroare imediat
            setFieldError(pw2, 'Parolele nu coincid.');
        } else {
            // Parolele coincid sau câmpul e gol — ștergem eroarea
            setFieldError(pw2, '');
        }
    });

    // Listener pe submit-ul formularului
    form.addEventListener('submit', async e => {
        e.preventDefault(); // Prevenim reload-ul paginii

        // Ștergem erorile din submit-ul anterior
        clearErrors(form);
        hideAlert(alert);

        // Citim valorile tuturor câmpurilor
        const username  = form.username.value.trim();
        const email     = form.email.value.trim();
        const password  = pwInput.value;
        const password2 = pw2.value;

        // Validare completă înainte de a trimite la server
        let valid = true;

        // Câmpuri obligatorii
        if (!username) { setFieldError(form.username, 'Username-ul este obligatoriu.'); valid = false; }
        if (!email)    { setFieldError(form.email,    'Emailul este obligatoriu.');     valid = false; }
        if (!password) { setFieldError(pwInput,       'Parola este obligatorie.');      valid = false; }

        // Lungime minimă parolă (același criteriu ca pe server)
        if (password && password.length < 8) {
            setFieldError(pwInput, 'Parola trebuie să aibă cel puțin 8 caractere.');
            valid = false;
        }

        // Potrivire parole
        if (password && password2 && password !== password2) {
            setFieldError(pw2, 'Parolele nu coincid.');
            valid = false;
        }

        // Nu trimitem cererea dacă există erori de validare
        if (!valid) return;

        // Activăm loading
        setLoading(btn, true);

        // Trimitem cererea de înregistrare la API
        const { ok, data } = await apiFetch('/beverages-app/api/auth.php?action=register', { username, email, password });

        // Dezactivăm loading
        setLoading(btn, false);

        if (ok) {
            // Înregistrare reușită: afișăm mesaj și redirecționăm spre login
            showAlert(alert, 'Cont creat cu succes! Redirecționare spre login…', 'success');
            // 1200ms să aibă timp userul să vadă mesajul
            setTimeout(() => { window.location.href = '/beverages-app/login.php'; }, 1200);
        } else {
            // Eroare de la server (ex: email deja folosit)
            showAlert(alert, data.error || 'Eroare la înregistrare.');
        }
    });
}

/* ================================================================
   Punctul de intrare: DOMContentLoaded
   DOMContentLoaded se declanșează când HTML-ul a fost complet parsat
   (dar înainte ca imaginile și stilurile să fie complet încărcate).
   Este momentul sigur în care putem accesa elementele DOM.
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
    // Apelăm ambele funcții de inițializare.
    // Fiecare verifică dacă formularul ei există (guard clause) —
    // deci apelul pe pagina greșită e inofensiv.
    initLoginPage();
    initRegisterPage();
});
