# Press Bridge

Thème WordPress « headless » qui transforme une installation WordPress en back-office pour une application front-end externe (Node.js, SPA, etc.).

WordPress reste l'outil d'édition ; l'affichage est délégué à une autre application. Press Bridge fait le lien entre les deux :

- **Notifications sortantes (webhooks)** : à chaque création, mise à jour ou suppression d'un article/d'une page, le thème envoie un `POST` JSON à l'application front-end, qui peut ainsi tenir à jour son propre cache.
- **Endpoint AJAX de listing** : l'application front-end peut récupérer la liste paginée des contenus publiés via `admin-ajax.php`.
- **Rendu du contenu brut** : les URLs d'articles et de pages renvoient uniquement le HTML du contenu (`the_content()`), sans gabarit, avec les en-têtes CORS.
- **Redirection** : toute autre URL publique du site WordPress redirige vers l'interface front-end.

```
┌──────────────────────┐   save_post / delete_post    ┌──────────────────────┐
│      WordPress       │ ───── POST JSON (webhook) ──▶│                      │
│  (thème Press Bridge)│                              │   Application UI     │
│                      │ ◀──── admin-ajax.php ─────── │   (_UI_URL_)         │
│                      │ ◀──── GET /<slug-article> ── │                      │
└──────────────────────┘                              └──────────────────────┘
```

## Prérequis

- WordPress 5.5 ou supérieur (le hook `delete_post` doit transmettre l'objet `$post`)
- PHP 8.0+ (types union `bool|string`)
- Extension PHP `curl`

## Installation

1. Copier (ou cloner) le dossier dans `wp-content/themes/press_bridge` :

   ```bash
   cd wp-content/themes
   git clone https://github.com/encephalux/press_bridge.git
   ```

2. Éditer `env.php` (voir [Configuration](docs/configuration.md)) :

   ```php
   const _API_TOKEN_ = "un-secret-long-et-aleatoire";
   const _UI_URL_ = "https://mon-front.example.com";
   ```

3. Dans l'administration WordPress, activer le thème **Press bridge theme** (*Apparence → Thèmes*).

4. Côté application front-end, exposer les routes `POST /press-bridge/add`, `/update` et `/remove` et vérifier le champ `token` de chaque requête.

## Utilisation rapide

Récupérer les 5 derniers articles publiés :

```bash
curl -X POST https://mon-wordpress.example.com/wp-admin/admin-ajax.php \
  -d action=articles -d page=1 -d per_page=5
```

```json
{
  "total": 128,
  "list": [
    {
      "id": "42",
      "slug": "mon-article",
      "title": "Mon article",
      "excerpt": "…",
      "date": "2026-10-11 14:32:00",
      "thumbnail": "https://…/image-300x200.jpg",
      "author": "Jane Doe"
    }
  ]
}
```

`total` est le nombre total d'articles publiés ; `list` contient la page demandée.

Récupérer le contenu HTML d'un article : `GET https://mon-wordpress.example.com/mon-article/`.

## Documentation

| Document | Contenu |
|---|---|
| [Configuration](docs/configuration.md) | Constantes de `env.php` |
| [Webhooks](docs/webhooks.md) | Notifications envoyées à l'application front-end |
| [API AJAX](docs/ajax-api.md) | Endpoint de listing des contenus |
| [Templates](docs/templates.md) | Comportement de `index.php`, `single.php`, `page.php` |

## Structure du projet

```
press_bridge/
├── env.php         # Configuration (token, URL du front, types supportés, CORS)
├── functions.php   # Webhooks (save_post / delete_post) et endpoint AJAX
├── index.php       # Redirection vers l'UI
├── single.php      # Rendu brut d'un article
├── page.php        # Rendu brut d'une page
├── style.css       # En-tête du thème WordPress
└── docs/           # Documentation
```

## Licence

Distribué sous licence Apache 2.0. Voir [LICENSE](LICENSE).
