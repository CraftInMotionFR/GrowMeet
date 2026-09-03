# GrowMeet

Application web de gestion de cours d'éducation canine (PHP / MySQL) : les propriétaires réservent des séances pour leurs chiens, les coachs et les administrateurs gèrent les cours.

## Architecture

Architecture MVC maison, sans framework :

- `app/Core` : autoloader, routeur et routes
- `app/Controllers` : contrôleurs
- `app/Models` : managers (accès PDO à la base)
- `app/Views` : vues et layout
- `config` : configuration de la base et déclaration des routes
- `database/migrations` : script SQL (tables et données de démo)
- `public` : point d'entrée, CSS, JS et images
- `tests/e2e` : tests de bout en bout (Playwright)

## Installation

1. Importer `database/migrations/growmeet.sql` dans MySQL.
2. Lancer le serveur PHP intégré : `php -S localhost:8000 -t public`
3. Ouvrir http://localhost:8000

Compte administrateur de démo : `admin@growmeet.com` / `Admin1234!`

## Tests

```
npm install
npm run test:e2e
```
