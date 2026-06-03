<?php
// ============================================================
// dashboard.php — Pagina principală post-autentificare.
// Accesibilă doar utilizatorilor autentificați.
// ============================================================

require_once __DIR__ . '/includes/auth.php';

// Pornim sesiunea PHP pentru a accesa datele utilizatorului logat
session_init();

// Protecție server-side: utilizatori neautentificați → redirecționare la login
if (!is_logged_in()) {
    header('Location: /beverages-app/login.php');
    exit;
}

// Preluăm datele utilizatorului din sesiune
$user = current_user();

// Prima literă din username pentru avatar circular (ex: "Tudor" → "T")
$initial = strtoupper(substr($user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <!-- Viewport responsive: pagina se adaptează la lățimea dispozitivului -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Beverages App</title>
    <link rel="stylesheet" href="/beverages-app/public/css/main.css">
</head>
<body>

<!-- ============================================================
     Bara de navigare fixă (topbar)
     ============================================================ -->
<header class="topbar">
    <div class="topbar-brand">🧃 Beverages App</div>
    <nav>
        <ul class="topbar-nav">
            <!-- clasa 'active' marchează pagina curentă -->
            <li><a href="/beverages-app/dashboard.php" class="active">Dashboard</a></li>
            <li><a href="/beverages-app/beverages.php">Băuturi</a></li>
            <li><a href="/beverages-app/lists.php">Liste</a></li>
            <li><a href="/beverages-app/groups.php">Grupuri</a></li>
            <li><a href="/beverages-app/stats.php">Statistici</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <li><a href="#">Admin</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="topbar-user">
        <span class="avatar"><?= htmlspecialchars($initial) ?></span>
        <span><?= htmlspecialchars($user['username']) ?></span>
        <a href="/beverages-app/logout.php">Ieșire</a>
    </div>
</header>

<!-- ============================================================
     Conținut principal
     ============================================================ -->
<main class="page-main">

    <!-- Banner de bun venit -->
    <div class="dash-welcome">
        <!-- Avatar mare cu inițiala utilizatorului -->
        <div class="dash-welcome__avatar"><?= htmlspecialchars($initial) ?></div>
        <div>
            <!-- Salut personalizat -->
            <h2 class="dash-welcome__title">Bun venit, <?= htmlspecialchars($user['username']) ?>!</h2>
            <!-- Rolul utilizatorului: 'user' sau 'admin' -->
            <p class="dash-welcome__sub">
                Ești autentificat ca
                <!-- Badge colorat diferit pentru admin față de user -->
                <span class="role-badge role-badge--<?= htmlspecialchars($user['role']) ?>">
                    <?= htmlspecialchars($user['role']) ?>
                </span>
            </p>
        </div>
    </div>

    <!-- Titlul secțiunii de navigare rapidă -->
    <h3 class="dash-section-title">Navigare rapidă</h3>

    <!-- Grid de carduri — 4 coloane pe desktop, 2 pe tabletă, 1 pe mobil -->
    <div class="dash-grid">
        <?php
        // Fiecare card: [emoji, titlu, descriere, URL destinație]
        $cards = [
            ['🍵', 'Băuturi',    'Catalogul complet de băuturi non-alcoolice',       '/beverages-app/beverages.php'],
            ['🛒', 'Liste',      'Listele tale de cumpărături personale și de grup',  '/beverages-app/lists.php'],
            ['👥', 'Grupuri',    'Gestionează grupuri și preferințe comune',          '/beverages-app/groups.php'],
            ['⭐', 'Preferințe', 'Rating-urile și notițele tale despre băuturi',      '/beverages-app/preferences.php'],
            ['📊', 'Statistici', 'Grafice, exporturi CSV/SVG și clasamente',           '/beverages-app/stats.php'],
        ];

        // foreach iterează prin fiecare card; destructurăm array-ul în variabile
        foreach ($cards as [$icon, $title, $desc, $href]):
        ?>
        <!-- Cardul întreg e un link — fără JS inline, hover-ul e gestionat de CSS -->
        <a href="<?= htmlspecialchars($href) ?>" class="dash-card">
            <!-- Iconița emoji în cerc colorat -->
            <div class="dash-card__icon"><?= $icon ?></div>
            <!-- Titlul categoriei -->
            <strong class="dash-card__title"><?= $title ?></strong>
            <!-- Descrierea scurtă -->
            <span class="dash-card__desc"><?= $desc ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Sectiunea de recomandari personalizate -->
    <h3 class="dash-section-title" style="margin-top:2.5rem">Recomandate pentru tine</h3>
    <p class="page-subtitle" style="margin-bottom:1rem">
        Bazate pe băuturile pe care le-ai evaluat cu 4-5 stele.
    </p>
    <div id="recommendations-grid" class="dash-grid">
        <p class="loading-text">Se încarcă recomandările...</p>
    </div>

</main>
<script src="/beverages-app/public/js/dashboard.js"></script>

</body>
</html>
