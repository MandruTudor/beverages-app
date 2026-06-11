<?php
// ============================================================
// api/rss.php — Flux RSS cu cele mai populare băuturi.
// Returnează RSS 2.0 valid cu top 10 băuturi după rating mediu.
// Dacă nu există evaluări, returnează cele mai recente 10 băuturi.
// ============================================================

require_once __DIR__ . '/../includes/db.php';

// RSS trebuie să fie XML cu tipul MIME corect
header('Content-Type: application/rss+xml; charset=utf-8');

// Obținem conexiunea la baza de date
$pdo = get_db();

// ---- Interogăm băuturile cele mai populare ----
// Folosim media bayesiană: score = (v*R + m*C) / (v+m)
// m=3 (prag), C = media globală — băuturile cu puține review-uri sunt penalizate
$stmt = $pdo->query('
    SELECT b.id, b.name, b.description, b.price, b.season, b.region,
           b.created_at, c.name AS category_name,
           ROUND(AVG(up.rating), 2) AS avg_rating,
           COUNT(up.id)             AS rating_count,
           ROUND(
               (COUNT(up.id) * AVG(up.rating) + 3.0 * (SELECT AVG(rating) FROM user_preferences))
               / (COUNT(up.id) + 3.0),
           2)                        AS score
    FROM beverages b
    JOIN categories c          ON c.id = b.category_id
    JOIN user_preferences up   ON up.beverage_id = b.id
    GROUP BY b.id
    ORDER BY score DESC, rating_count DESC
    LIMIT 10
');
$beverages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Construim URL-ul de bază al aplicației ----
// Detectăm protocolul (http/https) și host-ul din request
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host . '/beverages-app';

// Data curentă în formatul RFC 2822 cerut de RSS
$buildDate = date(DATE_RSS);

// ---- Generăm XML-ul RSS 2.0 ----
// Folosim xmlspecialchars() pentru a preveni XSS în XML
// htmlspecialchars() cu ENT_XML1 este suficient pentru XML
?>
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <!-- Titlul și descrierea feed-ului -->
        <title>Beverages App — Băuturi Populare</title>
        <link><?= htmlspecialchars($baseUrl, ENT_XML1) ?></link>
        <description>Clasamentul băuturilor non-alcoolice cele mai apreciate din catalog.</description>
        <language>ro</language>
        <!-- Data ultimei actualizări a feed-ului -->
        <lastBuildDate><?= $buildDate ?></lastBuildDate>
        <!-- Link canonic spre feed-ul RSS însuși (cerut de specificația Atom) -->
        <atom:link href="<?= htmlspecialchars($baseUrl . '/api/rss.php', ENT_XML1) ?>"
                   rel="self" type="application/rss+xml"/>

        <?php foreach ($beverages as $b): ?>
        <item>
            <!-- Titlul itemului: numele băuturii -->
            <title><?= htmlspecialchars($b['name'], ENT_XML1) ?></title>

            <!-- Link spre pagina de băuturi a aplicației -->
            <link><?= htmlspecialchars($baseUrl . '/beverages.php', ENT_XML1) ?></link>

            <!-- Descrierea detaliată a itemului -->
            <description><![CDATA[
                <?php
                // Construim o descriere bogată cu toate detaliile disponibile
                $parts = [];
                if ($b['description'])    $parts[] = $b['description'];
                if ($b['category_name'])  $parts[] = 'Categorie: ' . $b['category_name'];
                if ($b['price'])          $parts[] = 'Preț: ' . number_format((float)$b['price'], 2) . ' lei';
                if ($b['region'])         $parts[] = 'Regiune: ' . $b['region'];
                if ($b['avg_rating'])     $parts[] = 'Rating: ' . $b['avg_rating'] . '/5 (' . $b['rating_count'] . ' evaluări)';
                echo implode(' | ', $parts);
                ?>
            ]]></description>

            <!-- GUID unic pentru fiecare item — folosim ID-ul din baza de date -->
            <guid isPermaLink="false"><?= htmlspecialchars($baseUrl . '/beverage/' . $b['id'], ENT_XML1) ?></guid>

            <!-- Data publicării itemului -->
            <pubDate><?= date(DATE_RSS, strtotime($b['created_at'])) ?></pubDate>

            <!-- Categoria băuturii (câmpul <category> din RSS) -->
            <?php if ($b['category_name']): ?>
            <category><?= htmlspecialchars($b['category_name'], ENT_XML1) ?></category>
            <?php endif; ?>
        </item>
        <?php endforeach; ?>

    </channel>
</rss>
