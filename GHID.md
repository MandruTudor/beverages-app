# Ghid PHP pentru aplicația Beverages
*Pentru cineva care cunoaște C / C++ / Java și citește PHP pentru prima dată.*

---

## Cuprins
1. [Cum diferă PHP față de C/C++/Java](#1-cum-diferă-php-față-de-ccjava)
2. [Sintaxă de bază PHP](#2-sintaxă-de-bază-php)
3. [Cum funcționează modelul de cerere web](#3-cum-funcționează-modelul-de-cerere-web)
4. [Structura fișierelor proiectului](#4-structura-fișierelor-proiectului)
5. [Nivelul 1 — Punctul de intrare (api/*.php)](#5-nivelul-1--punctul-de-intrare)
6. [Nivelul 2 — includes/ (utilitare comune)](#6-nivelul-2--includes-utilitare-comune)
7. [Nivelul 3 — Controller-e](#7-nivelul-3--controllere)
8. [Nivelul 4 — Service-uri (logică de business)](#8-nivelul-4--servicuri-logică-de-business)
9. [Nivelul 5 — Repository-uri (baza de date)](#9-nivelul-5--repositoryuri-baza-de-date)
10. [Nivelul 6 — DTO-uri (validare input)](#10-nivelul-6--dtoouri-validare-input)
11. [Traseu complet al unei cereri — login](#11-traseu-complet-al-unei-cereri--login)
12. [Sesiuni PHP explicate](#12-sesiuni-php-explicate)
13. [Stratul de baze de date (PDO + SQLite)](#13-stratul-de-baze-de-date-pdo--sqlite)
14. [Paginile frontend](#14-paginile-frontend)
15. [Tabel comparativ PHP vs C/C++/Java](#15-tabel-comparativ)

---

## 1. Cum diferă PHP față de C/C++/Java

### PHP este un limbaj de scripting, nu unul compilat

În C sau C++ compilezi codul sursă într-un binar, apoi rulezi binarul.  
În Java compilezi în bytecode, iar JVM-ul îl execută.  
În PHP, **fișierul sursă este cel care rulează direct** — interpretorul PHP îl citește și îl execută linie cu linie la fiecare cerere.

Nu există o funcție `main()`. Fișierul în sine este punctul de intrare. Execuția începe de la linia 1 și se termină la ultima linie (sau când se apelează `exit`).

### PHP nu are memorie persistentă între cereri

În C poți avea o variabilă globală care trăiește pe toată durata procesului.  
În PHP, **fiecare cerere HTTP pornește de la zero**. Toate variabilele sunt create din nou, tot codul rulează din nou. Când răspunsul este trimis, totul se șterge.

Singurele moduri de a persista stare între cereri sunt:
- **Sesiuni** — date stocate într-un fișier pe server, identificate printr-un cookie (`$_SESSION`)
- **O bază de date** — SQLite în această aplicație
- **Fișiere pe disc** — citire/scriere manuală de fișiere

### PHP este dinamic tipizat (ca Python)

În C scrii `int x = 5;`. În PHP scrii `$x = 5;`.  
Nu este necesară declararea tipului. PHP determină tipul la rulare.  
Totuși, *poți* adăuga indicații de tip în PHP 7+ și această aplicație le folosește peste tot:

```php
// Echivalent C: int aduna(int a, int b) { return a + b; }
function aduna(int $a, int $b): int {
    return $a + $b;
}
```

`: int` după `)` este tipul returnat. `int` înaintea `$a` este tipul parametrului.  
Acestea sunt impuse la rulare, nu la compilare.

### Orice variabilă începe cu `$`

Aceasta este cea mai vizibilă diferență față de C/Java.

```php
$nume    = "Tudor";   // string
$varsta  = 21;        // integer
$pret    = 9.50;      // float (număr cu zecimale)
$activ   = true;      // boolean
$nimic   = null;      // null (ca nullptr în C++ sau null în Java)
```

---

## 2. Sintaxă de bază PHP

### Tagurile PHP

Codul PHP se scrie între taguri speciale. Orice este în afara lor este trimis direct ca text/HTML în răspuns:

```php
<?php
// Acesta este cod PHP — interpretorul îl execută
$x = 5;
echo $x;   // afișează 5 în răspuns
?>
<!-- Acesta este HTML — este trimis ca atare în răspuns -->
<p>Salut</p>
<?php
// Din nou cod PHP
?>
```

În fișierele API pure (care returnează doar JSON), tot fișierul este PHP, deci există un singur `<?php` la început și nu se mai închide niciodată — asta este corect și obișnuit.

### String-uri

```php
$prenume = "Tudor";
$nume    = "Mandru";

// Concatenarea folosește . (punct), NU + ca în Java
$complet = $prenume . " " . $nume;   // "Tudor Mandru"

// String-urile cu ghilimele duble interpolează variabilele direct
$salut = "Buna ziua, $nume!";        // "Buna ziua, Mandru!"
$salut = "Buna ziua, {$nume}!";      // {} se folosesc când variabila e lângă alt text

// String-urile cu ghilimele simple sunt LITERALE — fără interpolare
$literal = 'Salut $nume';            // "Salut $nume" (semnul $ este afișat ca text)
```

### Array-uri — cea mai importantă structură de date PHP

Array-urile PHP funcționează atât ca **liste (ca `vector<T>`)** cât și ca **dicționare (ca `unordered_map<string, T>`)** — totul în același tip.

```php
// Array indexat — ca int[] sau vector<int>
$fructe = ["mar", "banana", "cires"];
echo $fructe[0];   // "mar"
echo $fructe[2];   // "cires"

// Array asociativ — ca std::map<string, mixed> sau HashMap în Java
// Cheile pot fi string-uri
$utilizator = [
    "id"       => 1,
    "username" => "tudor",
    "email"    => "tudor@example.com",
    "rol"      => "admin",
];
echo $utilizator["username"];   // "tudor"

// Array imbricat — array în interiorul unui array (ca un struct în interiorul unui map)
$bautura = [
    "nume"        => "Ceai de ghimbir",
    "pret"        => 9.50,
    "ingrediente" => [
        ["id" => 1, "nume" => "Ghimbir"],
        ["id" => 2, "nume" => "Lamaie"],
    ],
];

// Accesarea valorilor imbricate
echo $bautura["ingrediente"][0]["nume"];   // "Ghimbir"
```

Această aplicație folosește array-uri asociative **peste tot**: ca valori returnate din funcții, ca rânduri din baza de date, ca body-uri JSON, ca date de sesiune. Gândește-te la ele ca la structuri anonime.

### Null și operatorii pentru null

```php
$valoare = null;

// ?? este operatorul null-coalescing
// "dacă stânga este null SAU nu există, folosește dreapta"
$nume = $valoare ?? "implicit";   // "implicit"

// ?string înseamnă că tipul este fie string FIE null
function get_nume(): ?string {
    return null;   // permis — funcția poate returna null
}
```

### isset() vs empty()

Aceste funcții apar constant în această aplicație:

```php
$data = ["nume" => "Tudor", "varsta" => 0, "email" => ""];

isset($data["nume"]);    // true  — cheia există și nu e null
isset($data["varsta"]);  // true  — 0 nu este null
isset($data["oras"]);    // false — cheia nu există

empty($data["nume"]);    // false — "Tudor" nu este gol
empty($data["varsta"]);  // true  — 0 este considerat "gol" (ATENȚIE!)
empty($data["email"]);   // true  — "" este considerat "gol"
empty($data["oras"]);    // true  — cheia lipsă este și ea "goală"
```

`empty()` tratează `0`, `""`, `"0"`, `false`, `null` și `[]` ca "gol". Aceasta este o sursă frecventă de bug-uri când te aștepți ca valoarea numerică 0 să fie validă.

### Clase și constructori

```php
// Similar cu clasele Java, dar cu public/private/protected
class AuthService {

    // Constructor injection — același concept ca dependency injection în Java
    // 'private UserRepository $repo' este scurtătură pentru:
    //   private UserRepository $repo;
    //   public function __construct(UserRepository $repo) { $this->repo = $repo; }
    public function __construct(private UserRepository $repo) {}

    // Metodele folosesc $this ca 'this' în Java
    public function login(string $email, string $parola): array {
        $user = $this->repo->findByEmail($email);
        // ...
    }
}
```

Sintaxa `private UserRepository $repo` în constructor se numește **constructor property promotion** (PHP 8). Declară ȘI atribuie proprietatea într-o singură linie. Java nu are echivalent — ai scrie trei linii.

### Expresia match (ca switch, dar mai bun)

```php
// PHP match — similar cu switch dar:
// - folosește comparație strictă === (nu ==)
// - fiecare ramură este o expresie (returnează o valoare)
// - aruncă excepție dacă nicio ramură nu se potrivește (spre deosebire de switch care ignoră silențios)

$actiune = "login";

match ($actiune) {
    'register' => $this->register(),   // apelează register() dacă actiune === 'register'
    'login'    => $this->login(),      // apelează login() dacă actiune === 'login'
    'logout'   => $this->logout(),
    'me'       => $this->me(),
    default    => json_error('Actiune invalida.', 404),   // ca default: în switch
};
```

În C ai scrie `switch(actiune) { case ...: }` dar C poate face switch doar pe întregi. PHP match funcționează și pe string-uri.

### Funcții arrow (closures)

```php
// fn($param) => expresie  este echivalent cu un lambda în Java / C++

// Java:  (v) -> v instanceof String ? sanitize(v) : v
// C++:   [](auto v) { return is_string(v) ? sanitize(v) : v; }
// PHP:
$rezultat = array_map(fn($v) => is_string($v) ? sanitize($v) : $v, $data);
```

`array_map(callback, array)` aplică callback-ul pe fiecare element din array și returnează un array nou — ca `std::transform` în C++ sau `stream().map()` în Java.

### require_once

```php
// Ca #include în C, sau import în Java
// Dar require_once garantează că fișierul este inclus exact o dată,
// chiar dacă îl apelezi din mai multe locuri.
require_once __DIR__ . '/../includes/db.php';

// __DIR__ este o constantă magică: calea absolută a directorului fișierului curent
// '/../includes/db.php' urcă un nivel, apoi intră în includes/
```

### Operatorul ternar și ?:

```php
// Operatorul ternar — ca în C și Java
$mesaj = ($varsta >= 18) ? "major" : "minor";

// Shorthand (Elvis operator) — dacă stânga e truthy, o returnează; altfel dreapta
$nume  = $input ?: "Anonim";   // dacă $input e truthy, folosește $input; altfel "Anonim"

// Null coalescing — dacă stânga e null sau inexistentă, returnează dreapta
$actiune = $_GET['action'] ?? 'lista';   // dacă ?action= lipsește din URL, folosim 'lista'
```

### Comparații: == vs ===

```php
// == compară valorile cu conversie de tip (ca == în C pentru int vs char)
0  == "0"    // true  — PHP convertește "0" la 0
0  == false  // true
0  == null   // true  — PERICULOS!

// === compară valoarea ȘI tipul (nicio conversie)
0  === "0"   // false — tipuri diferite (int vs string)
0  === false // false
0  === null  // false

// Această aplicație folosește === peste tot pentru comparații sigure
$_SESSION['role'] === 'admin'   // strict — nu poate fi păcălit de o conversie de tip
```

### Tipuri nullable (? prefix)

```php
// ?string înseamnă "string sau null"
// ?int înseamnă "int sau null"
// ?array înseamnă "array sau null"

function gaseste_user(int $id): ?array {
    // poate returna un array cu datele userului, sau null dacă nu a fost găsit
    $rand = $stmt->fetch();
    return $rand ?: null;   // ?: null transformă false în null
}

$user = gaseste_user(99);
if ($user === null) {
    // user-ul nu există
}
```

---

## 3. Cum funcționează modelul de cerere web

Când un browser (sau JavaScript `fetch()`) trimite o cerere către această aplicație, iată ce se întâmplă pas cu pas:

```
Browser/JS                         Server XAMPP / PHP
───────────────────────────────────────────────────────────
POST /api/auth.php?action=login
{                           ──────►  PHP citește api/auth.php
  "email": "a@b.com",               Îl execută de sus în jos
  "password": "secret"              Codul citește body-ul: json_body()
}                                   Codul citește parametrul URL: get_param('action')
                                    Codul rulează logica de business
                                    Codul apelează echo json_encode(...)
◄─────────────────────────────────  HTTP 200 {"message": "..."}
```

Lucruri esențiale de înțeles:
- **URL-ul determină ce fișier PHP rulează.** `/api/auth.php` rulează `api/auth.php`.
- **`?action=login` este query string-ul.** Este disponibil în `$_GET['action']`.
- **Body-ul cererii** (JSON-ul) se citește prin `php://input`.
- **`echo` scrie în corpul răspunsului HTTP.** Ce faci `echo` primește browser-ul.
- **`header(...)` setează header-ele HTTP** — cum ar fi `Content-Type: application/json`.
- **`exit` termină scriptul PHP imediat**, ca `return` din `main()` în C.

### Metodele HTTP

Fiecare cerere HTTP are o metodă. Aplicația folosește patru:

| Metodă | Sens | Când se folosește |
|--------|------|-------------------|
| GET | Citire date | Afișarea unei liste, citirea unui element |
| POST | Creare date noi | Crearea unei băuturi, autentificare |
| PATCH | Actualizare parțială | Modificarea doar a anumitor câmpuri |
| DELETE | Ștergere | Ștergerea unei băuturi, eliminarea unui element |

Metoda curentă este disponibilă în: `$_SERVER['REQUEST_METHOD']` — pe care aplicația o înfășoară în `http_method()`.

### Codurile de stare HTTP

Numărul care vine înapoi cu fiecare răspuns. Ca un cod de eroare în C, dar standardizat:

| Cod | Semnificație |
|-----|-------------|
| 200 | OK — cererea a reușit |
| 201 | Created — o nouă resursă a fost creată |
| 400 | Bad Request — ai trimis date invalide |
| 401 | Unauthorized — trebuie să te autentifici mai întâi |
| 403 | Forbidden — ești autentificat, dar nu ai permisiunea |
| 404 | Not Found — resursa/acțiunea nu există |
| 409 | Conflict — de ex., username-ul este deja luat |
| 500 | Internal Server Error — serverul a căzut |

### Superglobalele PHP

PHP creează automat câteva variabile globale la fiecare cerere, numite **superglobale**:

```php
$_GET     // parametrii din URL: /pagina.php?cheie=valoare → $_GET['cheie'] = 'valoare'
$_POST    // câmpurile unui formular HTML trimis cu method="POST"
$_SESSION // datele sesiunii curente (user logat, preferințe etc.)
$_SERVER  // informații despre cerere: REQUEST_METHOD, HTTP_HOST, REQUEST_URI etc.
$_COOKIE  // cookie-urile trimise de browser
```

---

## 4. Structura fișierelor proiectului

```
beverages-app/
│
├── api/                  ← Puncte de intrare. Un fișier per resursă.
│   ├── auth.php          ← /api/auth.php → login, register, logout
│   ├── beverages.php     ← /api/beverages.php → CRUD pentru băuturi
│   ├── categories.php    ← /api/categories.php → CRUD pentru categorii
│   └── ...               ← un fișier per resursă
│
├── controllers/          ← Parsează cererea HTTP, apelează service-ul
│   ├── AuthController.php
│   ├── BeverageController.php
│   └── ...
│
├── services/             ← Logică de business (validare, reguli, permisiuni)
│   ├── AuthService.php
│   ├── BeverageService.php
│   └── ...
│
├── repositories/         ← Comunicare cu baza de date (doar query-uri SQL)
│   ├── UserRepository.php
│   ├── BeverageRepository.php
│   └── ...
│
├── dto/                  ← Data Transfer Objects (validare și tipizare input)
│   ├── BeverageCreateDto.php
│   ├── BeverageUpdateDto.php
│   └── ...
│
├── includes/             ← Utilitare comune, încărcate de fiecare endpoint
│   ├── autoload.php      ← Face PHP să găsească clasele automat
│   ├── db.php            ← Conexiunea la baza de date (singleton PDO)
│   ├── auth.php          ← Gestionarea sesiunilor + gardieni de autentificare
│   └── helpers.php       ← json_response(), json_error(), etc.
│
├── data/
│   ├── init.sql          ← CREATE TABLE + date seed (rulat o singură dată la start)
│   └── beverages.sqlite  ← Fișierul bazei de date (creat automat)
│
├── admin/                ← Pagini frontend exclusiv pentru admin
├── js/                   ← Fișierele JavaScript pentru browser
└── *.php                 ← Pagini HTML frontend (login.php, beverages.php...)
```

Arhitectura urmează un pattern în straturi:

```
Cerere HTTP
     │
     ▼
api/auth.php          ← "Punct de intrare": încarcă dependențele, construiește obiectele, apelează handle()
     │
     ▼
AuthController        ← "Ce acțiune? Ce date din cerere?"
     │
     ▼
AuthService           ← "Este permis? Are logică corectă?"
     │
     ▼
UserRepository        ← "Vorbește cu baza de date"
     │
     ▼
PDO (SQLite)          ← Baza de date propriu-zisă
```

Fiecare strat comunică doar cu stratul imediat de dedesubt. Controller-ul nu atinge niciodată baza de date. Repository-ul nu impune niciodată reguli de business. Această separare face fiecare piesă ușor de înțeles și testat independent.

---

## 5. Nivelul 1 — Punctul de intrare

Fișier: `api/auth.php`

```php
<?php

// Încarcă autoloader-ul — spune PHP-ului unde să găsească fișierele claselor
require_once __DIR__ . '/../includes/autoload.php';

// Încarcă funcția get_db() pentru conexiunea la baza de date
require_once __DIR__ . '/../includes/db.php';

// Încarcă helper-ele de sesiune: require_auth(), is_logged_in() etc.
require_once __DIR__ . '/../includes/auth.php';

// Încarcă helper-ele de răspuns: json_response(), json_error(), get_param() etc.
require_once __DIR__ . '/../includes/helpers.php';

// Setează header-ul implicit Content-Type pentru toate răspunsurile din acest fișier
header('Content-Type: application/json; charset=utf-8');

// Construiește lanțul de dependențe de la interior spre exterior:
//   1. Creează UserRepository, dându-i conexiunea PDO
//   2. Creează AuthService, dându-i UserRepository
//   3. Creează AuthController, dându-i AuthService
//   4. Apelează handle() cu acțiunea din URL (?action=login etc.)
//
// get_param('action') citește $_GET['action']
// ?? '' înseamnă "dacă action lipsește din URL, folosește string gol"
(new AuthController(
    new AuthService(
        new UserRepository(get_db())
    )
))->handle(get_param('action') ?? '');
```

Acest fișier are 14 linii. Singurul lui rol este **legarea** componentelor. Creează obiectele în ordinea corectă și apelează metoda de nivel superior. Nicio logică nu trăiește aici.

Pattern-ul `(new ClassName(...))->method()` se numește **method chaining pe constructor**. Este echivalent cu:

```php
$repo       = new UserRepository(get_db());
$service    = new AuthService($repo);
$controller = new AuthController($service);
$controller->handle(get_param('action') ?? '');
```

---

## 6. Nivelul 2 — includes/ (utilitare comune)

Aceste patru fișiere sunt încărcate de fiecare endpoint. Ele oferă fundația pe care se bazează tot restul.

---

### includes/helpers.php — Helper-e pentru răspuns și input

Cele mai frecvent apelate funcții din aplicație:

#### `json_response($data, $status = 200)` — trimite un răspuns

```php
function json_response(mixed $data, int $status = 200): void {
    http_response_code($status);                              // setează statusul HTTP (200, 201, 400...)
    header('Content-Type: application/json; charset=utf-8'); // spune clientului că e JSON
    echo json_encode($data, JSON_UNESCAPED_UNICODE);          // serializează array PHP în string JSON
    exit;                                                     // oprește execuția PHP — răspunsul e gata
}
```

Aceasta este apelată la finalul **fiecărei** acțiuni API reușite. `exit` este intenționat — odată ce ai trimis răspunsul, nu mai este nimic de făcut.

#### `json_error($message, $status = 400)` — trimite un răspuns de eroare

```php
function json_error(string $message, int $status = 400): void {
    json_response(['error' => $message], $status);
    // json_response apelează exit, deci nici această funcție nu returnează niciodată
}
```

Aceasta înfășoară mesajul în `{"error": "..."}` și îl trimite. Este apelată oriunde validarea eșuează.

#### `json_body()` — citește body-ul cererii ca JSON

```php
function json_body(): array {
    $raw     = file_get_contents('php://input');  // citește corpul brut HTTP
    $decoded = json_decode($raw, true);           // parsează JSON → array PHP
    return is_array($decoded) ? $decoded : [];    // returnează array sau array gol dacă parsarea a eșuat
}
```

Când JavaScript face `fetch('/api/auth.php?action=login', { method: 'POST', body: JSON.stringify({email, password}) })`, JSON-ul ajunge ca bytes bruti în corpul cererii. `php://input` este un stream special care îți permite să citești acei bytes. `json_decode(..., true)` îi transformă într-un array asociativ PHP.

#### `get_param($key)` — citește un parametru din URL

```php
function get_param(string $key): ?string {
    return isset($_GET[$key]) ? trim($_GET[$key]) : null;
}
```

URL `/api/auth.php?action=login` → `get_param('action')` → `"login"`.  
`$_GET` este o superglobală PHP care conține toate perechile `cheie=valoare` din query string-ul URL-ului.

#### `missing_field($data, $required)` — validează câmpuri obligatorii

```php
function missing_field(array $data, array $required): ?string {
    foreach ($required as $field) {
        if (empty($data[$field])) {    // empty() = null, "", 0, false, []
            return $field;             // returnează numele primului câmp lipsă
        }
    }
    return null;   // toate câmpurile sunt prezente
}
```

Pattern-ul pe care îl vei vedea peste tot:
```php
$lipsa = missing_field($data, ['username', 'email', 'password']);
if ($lipsa) {
    json_error("Campul '$lipsa' este obligatoriu.");  // iese aici dacă ceva lipsește
}
// codul de mai jos rulează doar dacă toate câmpurile sunt prezente
```

---

### includes/db.php — Conexiunea la baza de date

```php
function get_db(): PDO {
    static $pdo = null;       // 'static' înseamnă că această variabilă persistă între apeluri
                              // Primul apel: $pdo este null. După primul apel: $pdo ține conexiunea.

    if ($pdo !== null) {
        return $pdo;          // Deja conectat — returnează conexiunea existentă
    }

    $db_exists = file_exists(DB_PATH);   // Verifică înainte de creare (new PDO creează fișierul)

    $pdo = new PDO('sqlite:' . DB_PATH); // Deschide (sau creează) fișierul SQLite

    // Modul eroare: aruncă excepții în loc să returneze false silențios
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Modul fetch: returnează rânduri ca ['nume_coloana' => valoare] în loc de [0 => valoare, 'nume_coloana' => valoare]
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec('PRAGMA foreign_keys = ON;');   // Activează ștergeri în cascadă în SQLite

    if (!$db_exists && file_exists(INIT_SQL_PATH)) {
        $pdo->exec(file_get_contents(INIT_SQL_PATH));   // Rulează CREATE TABLE + seed data o singură dată
    }

    return $pdo;
}
```

Pattern-ul **`static $pdo = null`** este pattern-ul Singleton. În C++ ai folosi o variabilă locală statică sau o clasă cu un membru static. Aici este mai simplu: o variabilă locală statică într-o funcție. Apelezi `get_db()` de zece ori într-o cerere — primești același obiect `PDO` de fiecare dată, nu zece conexiuni separate la baza de date.

**PDO** (PHP Data Objects) este stratul de abstractizare a bazei de date din PHP. Funcționează cu SQLite, MySQL, PostgreSQL și altele. Folosești același API indiferent de baza de date.

---

### includes/auth.php — Gestionarea sesiunilor

Sesiunile PHP funcționează astfel:
1. Prima dată când un utilizator se autentifică, PHP creează un fișier pe server (ex: `/tmp/sess_abc123`) și trimite un cookie `PHPSESSID=abc123` browser-ului.
2. La fiecare cerere ulterioară, browser-ul trimite înapoi acel cookie.
3. PHP citește cookie-ul, găsește fișierul și încarcă `$_SESSION` din el.
4. Poți citi și scrie în `$_SESSION` ca într-un array asociativ obișnuit.
5. Când utilizatorul se deconectează, fișierul este șters.

```php
function login_user(array $user): void {
    session_init();                   // pornește sesiunea (dacă nu e deja pornită)
    session_regenerate_id(true);      // creează un nou ID de sesiune pentru a preveni atacuri de fixare a sesiunii

    // Scrie datele utilizatorului în fișierul de sesiune
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];
}

function is_logged_in(): bool {
    session_init();
    return isset($_SESSION['user_id']);   // dacă user_id este în sesiune, cineva este autentificat
}

function require_auth(): void {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentificare necesara.']);
        exit;   // oprește totul — apelatorul nu continuă niciodată dincolo de aceasta
    }
}

function require_admin(): void {
    require_auth();   // mai întâi verifică dacă este autentificat (poate ieși cu 401)
    if (!is_admin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acces interzis.']);
        exit;   // oprește totul
    }
}
```

`require_auth()` și `require_admin()` sunt **funcții de gardă**. Fie ies (opresc cererea), fie nu fac nimic. Dacă returnează, știi că utilizatorul a trecut verificarea. Acest pattern este folosit la începutul fiecărei metode de service care necesită protecție:

```php
public function create(BeverageCreateDto $dto): array {
    require_admin();   // dacă nu e admin → iese cu 403, nimic de mai jos nu rulează
    // ... creează băutura
}
```

---

### includes/autoload.php — Încărcarea claselor

În Java, fiecare clasă este în propriul fișier și `import` o aduce. În PHP, ar trebui să faci `require_once 'controllers/AuthController.php'` manual peste tot. Autoloader-ul automatizează acest lucru:

```php
spl_autoload_register(function (string $clasa) {
    // Când PHP încearcă să folosească o clasă pe care nu a încărcat-o încă,
    // această funcție este apelată cu numele clasei.
    // Încercăm să găsim fișierul și să îl încărcăm.

    $directoare = ['controllers', 'services', 'repositories', 'dto', 'includes'];
    foreach ($directoare as $dir) {
        $fisier = __DIR__ . "/../$dir/$clasa.php";
        if (file_exists($fisier)) {
            require_once $fisier;
            return;
        }
    }
});
```

Când PHP vede `new AuthController(...)` și `AuthController` nu a fost încărcat încă, apelează această funcție cu `"AuthController"`. Funcția caută în `controllers/AuthController.php` și îl încarcă. De aceea punctul de intrare trebuie doar să facă `require_once` pentru cele patru fișiere din `includes/` — toate fișierele de clasă sunt încărcate automat la cerere.

---

## 7. Nivelul 3 — Controllere

Fișier: `controllers/AuthController.php`

```php
class AuthController {

    // Constructor injection: service-ul este transmis din exterior (din api/auth.php)
    // 'private AuthService $service' declară și atribuie $this->service într-o singură linie
    public function __construct(private AuthService $service) {}

    // handle() este metoda principală de dispatch — apelată din api/auth.php
    public function handle(string $actiune): void {
        match ($actiune) {
            'register' => $this->register(),   // POST cu username/email/password
            'login'    => $this->login(),      // POST cu email/password
            'logout'   => $this->logout(),     // POST, nu necesită body
            'me'       => $this->me(),         // GET, returnează utilizatorul curent
            default    => json_error('Actiune invalida.', 404),
        };
    }

    private function register(): void {
        $data  = json_body();                                    // parsează body-ul cererii JSON
        $lipsa = missing_field($data, ['username', 'email', 'password']);
        if ($lipsa) {
            json_error("Campul '$lipsa' este obligatoriu.");     // iese aici dacă câmpul lipsește
        }
        // Transmite cele trei câmpuri service-ului, înfășoară rezultatul în HTTP 201
        json_response($this->service->register($data['username'], $data['email'], $data['password']), 201);
    }

    private function login(): void {
        $data  = json_body();
        $lipsa = missing_field($data, ['email', 'password']);
        if ($lipsa) {
            json_error("Campul '$lipsa' este obligatoriu.");
        }
        json_response($this->service->login($data['email'], $data['password']));
    }

    private function logout(): void {
        logout_user();                                            // distruge sesiunea
        json_response(['message' => 'Deconectare reusita.']);
    }

    private function me(): void {
        require_auth();                                           // gardă: 401 dacă nu e autentificat
        json_response(['user' => current_user()]);                // returnează datele din sesiune
    }
}
```

Responsabilitățile controller-ului sunt clare:
1. Citește date din cererea HTTP (`json_body()`, `get_param()`)
2. Face validare de prezență de bază (`missing_field()`)
3. Apelează metoda de service corespunzătoare
4. Trimite răspunsul (`json_response()`)

NU validează reguli de business (asta e treaba service-ului) și NU atinge baza de date (asta e treaba repository-ului).

---

## 8. Nivelul 4 — Service-uri (logică de business)

Fișier: `services/AuthService.php`

```php
class AuthService {

    public function __construct(private UserRepository $repo) {}

    public function register(string $username, string $email, string $parola): array {
        $username = trim($username);
        $email    = trim($email);

        // Regulă de business: email-ul trebuie să aibă format valid
        // filter_var() este validatorul built-in al PHP — FILTER_VALIDATE_EMAIL folosește un regex intern
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('Adresa de email este invalida.');
        }

        // Regulă de business: parola trebuie să aibă cel puțin 8 caractere
        // strlen() numără bytes (ok pentru parole ASCII)
        if (strlen($parola) < 8) {
            json_error('Parola trebuie sa aiba cel putin 8 caractere.');
        }

        // Regulă de business: username-ul și email-ul trebuie să fie unice
        // Repository-ul face un SELECT — dacă returnează ceva, username/email e deja luat
        if ($this->repo->findByUsernameOrEmail($username, $email)) {
            json_error('Username-ul sau emailul este deja folosit.', 409);  // 409 Conflict
        }

        // password_hash() rulează bcrypt — un hash unidirecțional lent, conceput pentru parole.
        // Șirul rezultat include salt-ul și versiunea algoritmului.
        // NICIODATĂ nu stoca parole în text clar. NICIODATĂ nu folosi md5() sau sha1() pentru parole.
        $hash = password_hash($parola, PASSWORD_BCRYPT);

        // Cere repository-ului să insereze rândul și să returneze ID-ul noului record
        $id = $this->repo->create($username, $email, $hash);

        // Returnează un array PHP — controller-ul va face json_encode() pe el
        return [
            'message' => 'Cont creat cu succes.',
            'user'    => ['id' => $id, 'username' => $username, 'email' => $email],
        ];
    }

    public function login(string $email, string $parola): array {
        // Cere repository-ului să găsească un rând de user după email
        $user = $this->repo->findByEmail($email);

        // password_verify() verifică parola în text clar față de hash-ul bcrypt stocat.
        // Folosim UN SINGUR mesaj de eroare atât pentru "email negăsit" cât și pentru "parolă greșită"
        // — asta previne ca un atacator să știe care dintre cele două e greșită (user enumeration)
        if (!$user || !password_verify($parola, $user['password_hash'])) {
            json_error('Email sau parola incorecte.', 401);
        }

        // Scrie datele utilizatorului în sesiune — creează starea de autentificat
        login_user($user);

        return [
            'message' => 'Autentificare reusita.',
            'user'    => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ],
        ];
    }
}
```

Responsabilitățile service-ului:
1. Impune reguli de business (email valid, lungime minimă parolă, unicitate)
2. Impune autorizarea (require_auth, require_admin)
3. Apelează repository-ul pentru citire/scriere date
4. Returnează array-uri PHP simple cu rezultatul

NU știe nimic despre header-ele HTTP, JSON sau parsarea cererilor.

---

## 9. Nivelul 5 — Repository-uri (baza de date)

Fișier: `repositories/UserRepository.php`

```php
class UserRepository {

    // Conexiunea PDO este injectată — repository-ul nu creează niciodată propria conexiune
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array {
        // prepare() creează un prepared statement
        // ? este un placeholder — valoarea reală este legată mai târziu
        // Aceasta PREVINE SQL injection: valoarea nu este niciodată concatenată în string-ul SQL
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');

        // execute() rulează query-ul cu placeholder-ul înlocuit de valoarea reală
        // PDO se ocupă de escaping — valoarea nu poate ieși din contextul string-ului
        $stmt->execute([trim($email)]);

        // fetch() returnează UN singur rând ca array asociativ, sau false dacă nu s-a găsit niciun rând
        // ?: null transformă false în null (deoarece ?array nu poate returna false)
        return $stmt->fetch() ?: null;
    }

    public function findByUsernameOrEmail(string $username, string $email): ?array {
        // Placeholder-uri multiple ? — array-ul trebuie să aibă valorile în aceeași ordine
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $username, string $email, string $hash): int {
        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $hash]);

        // lastInsertId() returnează ID-ul auto-incrementat al rândului tocmai inserat
        // (int) convertește string-ul în integer — lastInsertId() returnează întotdeauna un string
        return (int) $this->pdo->lastInsertId();
    }

    public function findAll(): array {
        // query() se folosește când nu există parametri (niciun input de utilizator în SQL)
        // fetchAll() returnează TOATE rândurile dintr-o dată ca un array de array-uri
        return $this->pdo->query(
            'SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC'
        )->fetchAll();
    }

    public function setRole(int $id, string $rol): void {
        $stmt = $this->pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$rol, $id]);
        // Fără valoare returnată — void înseamnă că funcția nu returnează nimic (ca void în C)
    }
}
```

Responsabilitățile repository-ului:
1. Execută query-uri SQL folosind prepared statements
2. Returnează rânduri ca array-uri PHP (sau null/array gol dacă nu s-a găsit nimic)
3. Nu validează niciodată, nu impune reguli de business, nu verifică permisiunile

### De ce contează prepared statements

Compară aceste două abordări:

```php
// PERICULOS — vulnerabilitate SQL injection
$email = $_POST['email'];                               // atacatorul trimite: ' OR 1=1 --
$sql   = "SELECT * FROM users WHERE email = '$email'";  // devine: WHERE email = '' OR 1=1 --'
// Atacatorul primește acum TOȚI utilizatorii

// SIGUR — prepared statement
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);   // PDO scapă valoarea — ' devine \', query-ul este sigur
```

Această aplicație folosește prepared statements peste tot în stratul repository. Nu vei vedea niciodată un string ca `"SELECT ... WHERE email = '$email'"` — mereu placeholder-uri `?`.

---

## 10. Nivelul 6 — DTO-uri (validare input)

DTO înseamnă **Data Transfer Object**. Validează și tipizează input-ul brut la punctul de intrare, astfel încât restul codului să poată lucra cu date curate și tipizate.

Fișier: `dto/BeverageCreateDto.php`

```php
class BeverageCreateDto {

    // Proprietăți publice tipizate — declară ce câmpuri ține DTO-ul și tipurile lor
    public string  $name;               // obligatoriu
    public ?int    $categoryId;         // opțional (? = nullable)
    public ?float  $price;              // opțional
    public ?string $description;        // opțional
    public ?string $imagePath;          // opțional
    public ?int    $perishabilityDays;  // opțional
    public ?string $season;             // opțional
    public ?string $region;             // opțional
    public array   $ingredients;        // opțional, implicit []
    public array   $allergens;          // opțional, implicit []
    public array   $venues;             // opțional, implicit []

    // Constructorul primește body-ul brut al cererii (JSON decodat în array PHP)
    public function __construct(array $data) {

        // Validează mai întâi câmpul obligatoriu
        $lipsa = missing_field($data, ['name']);
        if ($lipsa) {
            json_error("Campul '$lipsa' este obligatoriu.", 400);  // iese dacă name lipsește
        }

        // Convertește și atribuie toate câmpurile
        $this->name = trim($data['name']);

        // isset() verifică existența fără a trata 0 ca empty
        // (int) convertește la integer — gestionează string-uri ca "5" → 5
        $this->categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;

        // (float) convertește la zecimal — "9.50" → 9.5
        $this->price             = isset($data['price'])              ? (float) $data['price']              : null;
        $this->description       = isset($data['description'])        ? trim($data['description'])          : null;
        $this->imagePath         = isset($data['image_path'])         ? trim($data['image_path'])           : null;
        $this->perishabilityDays = isset($data['perishability_days']) ? (int)   $data['perishability_days'] : null;
        $this->season            = $data['season']  ?? null;
        $this->region            = isset($data['region'])             ? trim($data['region'])               : null;

        // Relații many-to-many: array-uri de obiecte
        // ?? [] înseamnă: dacă cheia nu există, folosește un array gol
        $this->ingredients = $data['ingredients'] ?? [];
        $this->allergens   = $data['allergens']   ?? [];
        $this->venues      = $data['venues']      ?? [];
    }
}
```

Fără DTO-uri, service-ul ar fi plin de `isset`, cast-uri și apeluri `trim` împrăștiate peste tot. DTO-ul le colectează pe toate într-un singur loc. După ce constructorul DTO rulează:
- Toate câmpurile obligatorii sunt garantat prezente
- Toate tipurile sunt garantat corecte
- Service-ul citește pur și simplu `$dto->name`, `$dto->price` etc. fără să se preocupe de string-uri brute

---

## 11. Traseu complet al unei cereri — login

Să urmărim exact ce se întâmplă când JavaScript trimite:

```javascript
fetch('/api/auth.php?action=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: 'admin@beverages.local', password: 'password' })
});
```

**Pasul 1 — PHP pornește `api/auth.php`**
```
require_once includes/autoload.php   → înregistrează autoloader-ul
require_once includes/db.php         → definește get_db()
require_once includes/auth.php       → definește funcțiile de sesiune
require_once includes/helpers.php    → definește json_response() etc.
header('Content-Type: application/json; charset=utf-8')
```

**Pasul 2 — Obiectele sunt construite**
```
get_db()                        → creează conexiunea PDO la data/beverages.sqlite
new UserRepository($pdo)        → repository-ul ține PDO-ul
new AuthService($userRepo)      → service-ul ține repository-ul
new AuthController($authService) → controller-ul ține service-ul
```

**Pasul 3 — `handle('login')` este apelat**
```php
get_param('action')   // citește $_GET['action'] = 'login'

match ('login') {
    'login' => $this->login()   // ← această ramură rulează
}
```

**Pasul 4 — `AuthController::login()` rulează**
```php
$data  = json_body()        // citește php://input → ['email' => '...', 'password' => '...']
$lipsa = missing_field($data, ['email', 'password'])   // null (ambele prezente)
// lipsa este null/falsy, deci nu apelăm json_error
json_response($this->service->login($data['email'], $data['password']))
// ↑ apelează service-ul, apoi înfășoară rezultatul într-un răspuns
```

**Pasul 5 — `AuthService::login()` rulează**
```php
$user = $this->repo->findByEmail('admin@beverages.local')
// ↓ apelează repository-ul
```

**Pasul 6 — `UserRepository::findByEmail()` rulează**
```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?')
$stmt->execute(['admin@beverages.local'])
$rand = $stmt->fetch()
// $rand = ['id' => 1, 'username' => 'admin', 'email' => 'admin@beverages.local',
//           'password_hash' => '$2y$10$...', 'role' => 'admin', 'created_at' => '...']
return $rand   // returnează array-ul înapoi la service
```

**Pasul 7 — Înapoi în `AuthService::login()`**
```php
// $user este array-ul de rând din repository
password_verify('password', '$2y$10$...')   // true — parola se potrivește cu hash-ul
login_user($user)    // scrie în $_SESSION
// ↓ în includes/auth.php:
//   session_regenerate_id(true)
//   $_SESSION['user_id'] = 1
//   $_SESSION['username'] = 'admin'
//   $_SESSION['email'] = 'admin@beverages.local'
//   $_SESSION['role'] = 'admin'

return [
    'message' => 'Autentificare reusita.',
    'user'    => ['id' => 1, 'username' => 'admin', 'email' => '...', 'role' => 'admin'],
]
```

**Pasul 8 — Înapoi în `AuthController::login()` → `json_response()`**
```php
http_response_code(200)
header('Content-Type: application/json; charset=utf-8')
echo json_encode(['message' => 'Autentificare reusita.', 'user' => [...]])
exit   // PHP se oprește aici
```

**Ce primește browser-ul:**
```
HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8
Set-Cookie: PHPSESSID=abc123; path=/     ← cookie-ul de sesiune

{"message":"Autentificare reusita.","user":{"id":1,"username":"admin",...}}
```

De acum înainte, fiecare cerere ulterioară din acest browser include cookie-ul `PHPSESSID=abc123`, iar `$_SESSION['user_id']` va fi egal cu `1` — utilizatorul este "autentificat".

---

## 12. Sesiuni PHP explicate

**Problema pe care o rezolvă sesiunile:** HTTP este fără stare. Fiecare cerere este independentă — serverul nu are memorie despre cine a trimis cererea anterioară. Sesiunile sunt modul în care simulezi statefulness.

**Cum funcționează:**
```
Browser                                    Server
────────────────────────────────────────────────────────────
POST /api/auth.php?action=login
{ email, parola }                  ──►    Verifică credențialele
                                          Creează fișier: /tmp/sess_XYZ
                                          Conținut fișier: {"user_id":1, "role":"admin"}
                                   ◄──    200 OK
                                          Set-Cookie: PHPSESSID=XYZ

GET /api/beverages.php
Cookie: PHPSESSID=XYZ              ──►    Citește cookie-ul: XYZ
                                          Deschide /tmp/sess_XYZ
                                          Încarcă în $_SESSION
                                          $_SESSION['user_id'] = 1
                                          require_auth() → is_logged_in() → true
                                   ◄──    200 OK, datele băuturilor

DELETE /api/auth.php?action=logout
Cookie: PHPSESSID=XYZ              ──►    session_destroy()
                                          Șterge /tmp/sess_XYZ
                                   ◄──    200 OK
```

ID-ul de sesiune (valoarea `XYZ`) este un string aleatoriu generat de PHP. Este stocat într-un cookie de browser numit `PHPSESSID`. Datele reale sunt stocate într-un fișier **pe server**, nu în cookie — astfel chiar dacă cineva interceptează cookie-ul, obține doar un ID opac, nu datele tale.

---

## 13. Stratul de baze de date (PDO + SQLite)

**SQLite** este o bază de date care trăiește într-un singur fișier (`data/beverages.sqlite`). Spre deosebire de MySQL sau PostgreSQL, nu există un server de baze de date separat de pornit. PHP deschide pur și simplu fișierul și citește/scrie direct. Este bun pentru development și aplicații mici.

**PDO** (PHP Data Objects) este modul standard PHP de a comunica cu bazele de date. Același cod funcționează cu SQLite, MySQL, PostgreSQL — doar string-ul de conexiune se schimbă.

### Cele trei operații PDO pe care le vei vedea

**1. `query()` — pentru query-uri fără input de utilizator:**
```php
$randuri = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
// Returnează un array de array-uri: [['id' => 1, 'name' => 'Ceaiuri'], ...]
```

**2. `prepare()` + `execute()` — pentru query-uri cu input de utilizator:**
```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([42]);        // ? este înlocuit în siguranță de 42
$rand = $stmt->fetch();      // un rând, sau false
```

**3. `lastInsertId()` — obține ID-ul rândului tocmai inserat:**
```php
$pdo->prepare('INSERT INTO users (username) VALUES (?)')->execute(['tudor']);
$id = (int) $pdo->lastInsertId();   // ID-ul auto-incrementat atribuit noului rând
```

### fetch() vs fetchAll()

```php
$stmt->fetch()     // returnează UN singur rând ca ['coloana' => valoare, ...] sau false dacă nu există rânduri
$stmt->fetchAll()  // returnează TOATE rândurile ca [['coloana' => valoare], ['coloana' => valoare], ...] sau []
```

---

## 14. Paginile frontend

Fișierele `.php` din rădăcina proiectului (`login.php`, `beverages.php`, `dashboard.php` etc.) sunt paginile pe care le vede utilizatorul în browser. Sunt fișiere HTML cu PHP mixt.

Spre deosebire de fișierele API, acestea:
- Afișează HTML, nu JSON
- Verifică `is_logged_in()` și redirecționează dacă nu ești autentificat
- Folosesc taguri `<script>` pentru a încărca JavaScript care apelează endpoint-urile API

```php
<?php
// Verifică autentificarea la începutul fișierului, înainte de orice HTML
require_once __DIR__ . '/includes/auth.php';
session_init();

if (!is_logged_in()) {
    // header('Location: ...') trimite o redirecționare HTTP
    header('Location: /login.php');
    exit;   // stop — nu afișa HTML-ul de mai jos
}
?>
<!DOCTYPE html>
<html>
<head>...</head>
<body>
    <h1>Bun venit, <?= htmlspecialchars($_SESSION['username']) ?></h1>
    <!-- <?= ... ?> este scurtătură pentru <?php echo ... ?> -->
    <!-- htmlspecialchars() previne XSS — nu face niciodată echo la date de utilizator fără el -->
</body>
</html>
```

Sintaxa `<?= ... ?>` este echo prescurtat. Tot ce se află între tagurile `<?php` și `?>` este cod PHP. Tot ce se află în afara lor este trimis ca atare în răspunsul HTML.

---

## 15. Tabel comparativ

| Concept | C / C++ | Java | PHP |
|---------|---------|------|-----|
| Declarare variabilă | `int x = 5;` | `int x = 5;` | `$x = 5;` |
| Concatenare string | `strcat` / `+` (greșit) | `+` | `.` (punct) |
| Verificare null | `ptr == NULL` | `obj == null` | `$x === null` sau `!isset($x)` |
| Array/dicționar | `std::vector` / `std::map` | `ArrayList` / `HashMap` | `[]` (ambele în unul) |
| Afișare | `printf` | `System.out.println` | `echo` |
| Includere fișier | `#include` | `import` | `require_once` |
| Metodă de clasă | `this->method()` | `this.method()` | `$this->method()` |
| Valoare implicită null | N/A | `Optional.orElse()` | `$x ?? 'implicit'` |
| Conversie tip | `(int) x` | `(int) x` | `(int) $x` |
| Sfârșit instrucțiune | `;` | `;` | `;` |
| Formatare string | `sprintf` | `String.format` | `sprintf` sau interpolarea string-urilor |
| Ieșire program | `return 0` / `exit(0)` | `System.exit(0)` | `exit` |
| Egalitate strictă | `==` (implicit) | `==` (primitive), `.equals()` (obiecte) | `===` (tip + valoare) |
| Instanțiere obiect | `new MyClass()` | `new MyClass()` | `new MyClass()` |
| Accesare proprietate | `obj.field` / `ptr->field` | `obj.field` | `$obj->field` |
| Funcție anonimă | `[](auto x){ return x; }` | `(x) -> x` | `fn($x) => $x` |
