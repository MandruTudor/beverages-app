<?php
// ============================================================
// dashboard.php — Pagina principală post-autentificare.
// Accesibilă doar utilizatorilor autentificați.
// ============================================================

// Includem funcțiile de sesiune și autentificare.
require_once __DIR__ . '/includes/auth.php';

// Pornim sesiunea PHP.
session_init();

// Protecție server-side: dacă userul nu e logat, îl trimitem la login.
// Această verificare este OBLIGATORIE pe fiecare pagină protejată.
// Nu ne bazăm pe JavaScript pentru securitate — JS poate fi ocolit.
if (!is_logged_in()) {
    header('Location: /beverages-app/login.php');
    exit;
}

// Preluăm datele utilizatorului curent din sesiune.
// $user este un array cu cheile: id, username, email, role.
$user = current_user();

// Extragem prima literă din username pentru avatar (ex: "Ion" → "I").
// strtoupper() face litera majusculă.
// substr($string, 0, 1) extrage 1 caracter de la poziția 0 (primul).
$initial = strtoupper(substr($user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Beverages App</title>
    <link rel="stylesheet" href="/beverages-app/public/css/main.css">
</head>
<body>

<!-- Bara de navigare superioară fixă (sticky) -->
<header class="topbar">

    <!-- Logo + numele aplicației -->
    <div class="topbar-brand">🧃 Beverages App</div>

    <!-- Meniu de navigare principal -->
    <nav>
        <ul class="topbar-nav">
            <!-- clasa 'active' evidențiază pagina curentă -->
            <li><a href="/beverages-app/dashboard.php" class="active">Dashboard</a></li>
            <li><a href="/beverages-app/beverages.php">Băuturi</a></li>
            <li><a href="#">Liste</a></li>
            <li><a href="#">Statistici</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <!-- Link-ul Admin e vizibil DOAR pentru utilizatorii cu rol 'admin'.
                 Blocul PHP if/endif este echivalent cu un if din orice limbaj.
                 Sintaxa cu : și endif e mai lizibilă în template-uri HTML. -->
            <li><a href="#">Admin</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Zona utilizator: avatar + username + buton deconectare -->
    <div class="topbar-user">
        <!-- Avatar circular cu inițiala utilizatorului.
             htmlspecialchars() previne XSS dacă username-ul conține < sau >. -->
        <span class="avatar"><?= htmlspecialchars($initial) ?></span>
        <!-- Afișăm username-ul sanitizat -->
        <span><?= htmlspecialchars($user['username']) ?></span>
        <!-- Link de deconectare — duce la logout.php care distruge sesiunea -->
        <a href="/beverages-app/logout.php">Ieșire</a>
    </div>
</header>

<!-- Conținutul principal al paginii -->
<main style="padding:2rem 1.5rem;max-width:900px;margin:0 auto;">

    <!-- Mesaj de bun venit personalizat cu username-ul utilizatorului -->
    <h2 style="margin-bottom:.5rem;">Bun venit, <?= htmlspecialchars($user['username']) ?>!</h2>

    <!-- Afișăm rolul utilizatorului (user / admin) -->
    <p style="color:var(--clr-muted);margin-bottom:2rem;">
        Ești autentificat ca <strong><?= htmlspecialchars($user['role']) ?></strong>.
        Funcționalitățile complete vor fi disponibile în curând.
    </p>

    <!-- Grid de carduri cu funcționalitățile principale ale aplicației.
         grid-template-columns: repeat(auto-fill, minmax(200px, 1fr))
         creează automat câte coloane de minim 200px încap pe lățimea disponibilă. -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">
        <?php
        // Array de carduri: [emoji, titlu, descriere]
        // Folosim o structură de date pentru a evita repetarea HTML-ului.
        // Al patrulea element din fiecare array e link-ul paginii (null = neimplementat).
        $cards = [
            ['🍵', 'Băuturi',    'Catalogul de băuturi non-alcoolice', '/beverages-app/beverages.php'],
            ['🛒', 'Liste',      'Listele tale de cumpărături',         null],
            ['⭐', 'Preferințe', 'Rating-urile și notițele tale',       null],
            ['📊', 'Statistici', 'Grafice și exporturi de date',        null],
        ];
        // foreach iterează prin fiecare card.
        // [$icon, $title, $desc, $link] = destructurare array (PHP 7.1+).
        foreach ($cards as [$icon, $title, $desc, $link]):
        // Dacă cardul are link, îl înfășurăm într-un <a>; altfel, rămâne simplu <div>.
        $tag   = $link ? 'a' : 'div';
        $href  = $link ? "href=\"" . htmlspecialchars($link) . "\"" : '';
        // Stilul suplimentar pentru cardurile cu link: cursor pointer + efect hover via opacity.
        $extra = $link ? 'text-decoration:none;color:inherit;' : '';
        ?>
        <!-- Cardul individual — tag dinamic: <a> dacă are link, <div> dacă nu -->
        <<?= $tag ?> <?= $href ?> style="
            background:var(--clr-surface);
            border:1.5px solid var(--clr-border);
            border-radius:var(--radius);
            padding:1.25rem;
            display:flex;flex-direction:column;gap:.5rem;
            box-shadow:var(--shadow);
            transition:transform .2s ease,box-shadow .2s ease;
            <?= $extra ?>
        "
        <?php if ($link): ?>
            onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 32px rgba(0,0,0,.12)'"
            onmouseout="this.style.transform='';this.style.boxShadow='var(--shadow)'"
        <?php endif; ?>
        >
            <!-- Emoji mare ca icoană -->
            <span style="font-size:2rem;line-height:1;"><?= $icon ?></span>
            <!-- Titlul categoriei -->
            <strong><?= $title ?></strong>
            <!-- Descrierea scurtă -->
            <span style="font-size:.85rem;color:var(--clr-muted);"><?= $desc ?></span>
            <?php if (!$link): ?>
            <!-- Indicator că funcționalitatea nu e disponibilă încă -->
            <span style="font-size:.75rem;color:var(--clr-muted);margin-top:.25rem;">În curând…</span>
            <?php endif; ?>
        </<?= $tag ?>>
        <?php endforeach; // Sfârșitul buclei foreach ?>
    </div>
</main>

</body>
</html>
