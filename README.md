# GrowMeet

Application web de gestion de cours d'éducation canine (PHP / MySQL) : les propriétaires réservent des séances pour leurs chiens, les coachs et les administrateurs gèrent les cours.

## Architecture

Architecture MVC maison, sans framework :

- `app/Core` : autoloader, routeur, routes et chargement du `.env`
- `app/Controllers` : contrôleurs
- `app/Models` : managers (accès PDO à la base)
- `app/Views` : vues et layout
- `config` : configuration de la base et déclaration des routes
- `database/migrations` : script SQL (tables et données de démo)
- `public` : point d'entrée, CSS, JS et images
- `tests/e2e` : tests de bout en bout (Playwright)

## Configuration

Les identifiants de la base ne sont pas dans le code : ils sont lus dans un fichier `.env` à la racine, ignoré par Git.

```
cp .env.example .env
```

Puis adapter les valeurs (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`).

## Installation

1. Créer le fichier `.env` (voir ci-dessus).
2. Importer `database/migrations/growmeet.sql` dans MySQL.
3. Lancer le serveur PHP intégré : `php -S localhost:8000 -t public`
4. Ouvrir http://localhost:8000

Compte administrateur de démo : `admin@growmeet.com` / `Admin1234!`

## Tests

```
npm install
npm run test:e2e
```
