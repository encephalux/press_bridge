# Templates

Le thème ne fournit aucun rendu visuel. Ses templates servent uniquement à exposer le contenu à l'application front-end ou à y rediriger les visiteurs.

Tous les templates (et l'endpoint AJAX) envoient les en-têtes CORS suivants via `send_cors_headers()` (`functions.php`) :

```
Access-Control-Allow-Origin: <_ALLOWED_ORIGIN_>
Access-Control-Allow-Methods: GET, POST, OPTIONS
Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization
```

## `single.php` — article

Utilisé par WordPress pour l'affichage d'un article seul (`/mon-article/` selon la structure de permaliens).

Renvoie **uniquement** le HTML produit par `the_content()` : pas de `<html>`, `<head>`, titre ni métadonnées. Les shortcodes et blocs Gutenberg sont rendus côté WordPress.

```js
const html = await fetch("https://wp.example.com/mon-article/").then(r => r.text());
document.querySelector("#article").innerHTML = html;
```

> Le HTML provient des rédacteurs WordPress : l'application front-end doit lui faire confiance ou le nettoyer (ex. DOMPurify) avant injection.

## `page.php` — page

Identique à `single.php`, pour les pages WordPress.

## `index.php` — tout le reste

Template de repli de WordPress (accueil, archives, catégories, recherche, 404…). Il redirige immédiatement vers `_UI_URL_` avec un en-tête `Location` (code HTTP 302 par défaut).

Ainsi, un visiteur qui arrive sur le domaine WordPress est renvoyé vers l'application front-end.

## Fonctionnalités du thème

`functions.php` active :

- `post-thumbnails` — images mises en avant, exposées dans les webhooks et l'API AJAX ;
- `title-tag` — gestion du `<title>` par WordPress.
