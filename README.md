# 🥤 Beverages App

A full-stack web application for browsing and managing a catalog of beverages, built **without any framework**: a plain PHP backend with a clean layered architecture, a REST-style JSON API, and a vanilla JavaScript frontend.

Users can explore beverages by category, ingredients, allergens and dietary regime, save personal preferences, join groups with shared preferences, and build shopping lists. Admins manage the catalog from a dedicated dashboard.

> 🎥 A demo video and full project documentation are available in [`livrabile/`](livrabile/).

---

## ✨ Features

- **Authentication** – registration and login with **BCrypt** password hashing and stateless **JWT** tokens (custom HMAC-SHA256 implementation, no libraries)
- **Role-based access** – regular users and admins, with an admin panel for managing the catalog
- **Beverage catalog** – categories, ingredients, allergens, venues and dietary regimes, with client-side filtering
- **Personal & group preferences** – users save preferences individually or as part of a group
- **Shopping lists** – create lists and manage their items
- **Statistics dashboard** – catalog summary and top-rated beverages
- **RSS feed** – RSS 2.0 feed of the top-rated beverages
- **JSON export** of the full catalog with all relations, plus image upload

## 🏗️ Architecture

The backend follows a **4-layer architecture** inspired by Domain-Driven Design, keeping each responsibility in its own place:

```
HTTP request
   │
   ▼
api/*.php            → entry points (one per resource), routed via .htaccess
   │
   ▼
controllers/         → parse the request, validate input, build the HTTP response
   │
   ▼
services/            → business logic and rules
   │
   ▼
repositories/        → all SQL lives here (PDO + prepared statements)
   │
   ▼
SQLite database

dto/                 → typed Data Transfer Objects passed between layers
```

| Folder | Purpose |
|---|---|
| `api/` | REST-style endpoints – `/api/beverages`, `/api/groups`, `/api/shopping_lists`, … |
| `controllers/` | Request handling and validation |
| `services/` | Business logic |
| `repositories/` | Database access |
| `dto/` | Data Transfer Objects |
| `includes/` | Shared code: DB connection, JWT, auth helpers, autoloader |
| `pages/` | Server-rendered pages (login, dashboard, beverages, groups, lists, stats, admin) |
| `public/` | Frontend assets – vanilla JS per page and CSS |
| `data/` | Database schema (`init.sql`) and demo data (`seed_demo.sql`) |

**Database:** SQLite with foreign keys enabled and 17 normalized tables (users, groups, beverages, ingredients, allergens, venues, dietary regimes, shopping lists, preferences, and their many-to-many link tables). The schema is created automatically on first run.

## 🛠️ Tech Stack

- **Backend:** PHP 8 (no framework), PDO, SQLite
- **Frontend:** HTML, CSS, vanilla JavaScript (Fetch API)
- **Auth:** JWT (HMAC-SHA256), BCrypt
- **Server:** Apache with `mod_rewrite`

## 🚀 Running Locally

The app expects to be served from `/beverages-app/`, so the easiest setup is **XAMPP** (or any Apache + PHP 8 install):

1. Clone the repository into your web root:
   ```bash
   cd C:/xampp/htdocs        # or /var/www/html on Linux
   git clone https://github.com/MandruTudor/beverages-app.git
   ```
2. Make sure the **`pdo_sqlite`** PHP extension and Apache **`mod_rewrite`** are enabled.
3. Start Apache and open **http://localhost/beverages-app/**
   The SQLite database is created automatically in `data/` on first launch.
4. *(Optional)* Load demo data – all demo users have the password `password`:
   ```bash
   sqlite3 data/beverages.sqlite < data/seed_demo.sql
   ```

**Configuration:** set the `JWT_SECRET` environment variable in production; the value in `includes/jwt.php` is for local development only.

## 👥 Authors

Developed as a university project for the *Web Technologies* course at the Faculty of Computer Science, "Alexandru Ioan Cuza" University of Iași.

- [Tudor Mândru](https://github.com/MandruTudor)
- [Casian1234](https://github.com/Casian1234)

## 📄 License

[MIT](LICENSE)
