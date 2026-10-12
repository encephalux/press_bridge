# API AJAX

Press Bridge expose une action AJAX WordPress permettant à l'application front-end de lister les contenus publiés. Elle est accessible aux visiteurs connectés et anonymes (`wp_ajax_articles` et `wp_ajax_nopriv_articles`), sans authentification.

## Requête

```
POST /wp-admin/admin-ajax.php
Content-Type: application/x-www-form-urlencoded
```

Les paramètres sont lus depuis `$_POST` : il faut les envoyer en *form-urlencoded* ou *multipart/form-data*, **pas en JSON**.

| Paramètre | Requis | Défaut | Description |
|---|---|---|---|
| `action` | oui | — | Toujours `articles` |
| `page` | non | `1` | Numéro de page, à partir de `1` |
| `per_page` | non | `5` | Nombre d'éléments par page |
| `offset` | non | `0` | Décalage supplémentaire ajouté après le calcul de pagination |
| `post_type` | non | `post` | Type de contenu ; doit figurer dans `_SUPPORTED_POST_TYPES_` |

Le décalage SQL est calculé ainsi :

```
sql_offset = per_page × (page − 1) + offset
```

Seuls les contenus `post_status = 'publish'` sont renvoyés, triés par date décroissante.

## Réponse

```
Content-Type: application/json
Access-Control-Allow-Origin: <_ALLOWED_ORIGIN_>
```

```json
{
  "total": 128,
  "list": [
    {
      "id": "42",
      "slug": "mon-article",
      "title": "Mon article",
      "excerpt": "Résumé",
      "date": "2026-10-11 14:32:00",
      "thumbnail": "https://example.com/wp-content/uploads/2026/10/image-300x200.jpg",
      "author": "Jane Doe"
    }
  ]
}
```

| Champ | Type | Description |
|---|---|---|
| `total` | int | Nombre total de contenus publiés du `post_type` demandé, toutes pages confondues (calculé par `wp_count_posts()`, indépendant de `page`, `per_page` et `offset`) |
| `list[].id` | string | ID WordPress (renvoyé en chaîne par `$wpdb`) |
| `list[].slug` | string | `post_name` |
| `list[].title` | string | Titre |
| `list[].excerpt` | string | Extrait manuel (vide si non renseigné) |
| `list[].date` | string | `post_date`, format `Y-m-d H:i:s` |
| `list[].thumbnail` | string \| false | URL de l'image mise en avant en taille `medium` (300 px max par défaut, réglable dans *Réglages → Médias*), ou `false` s'il n'y en a pas. Si l'image originale est plus petite que cette taille, c'est son URL qui est renvoyée |
| `list[].author` | string | Nom affiché de l'auteur |

### Pagination

- Nombre de pages : `ceil((total − offset) / per_page)`
- Il reste des pages tant que `per_page × page + offset < total`
- Nombre d'éléments de la page reçue : `list.length`

Une page au-delà de la dernière renvoie `list: []` avec le `total` réel.

## Erreurs

| Situation | Réponse |
|---|---|
| `page`, `per_page` ou `offset` n'est pas un entier (chiffres uniquement) | `{"total": 0, "list": []}` |
| `page` ou `per_page` vaut `0` | `{"total": 0, "list": []}` |
| `post_type` non supporté | `{"total": 0, "list": []}` |
| `action` inconnue | `0` (comportement standard de WordPress) |

## Exemples

### curl

```bash
curl -X POST https://wp.example.com/wp-admin/admin-ajax.php \
  -d action=articles -d page=2 -d per_page=10
```

### JavaScript (navigateur / Node 18+)

```js
async function fetchPosts({ page = 1, perPage = 5, postType = "post" } = {}) {
  const body = new URLSearchParams({
    action: "articles",
    page: String(page),
    per_page: String(perPage),
    post_type: postType,
  });

  const res = await fetch("https://wp.example.com/wp-admin/admin-ajax.php", {
    method: "POST",
    body,
  });

  return res.json(); // { total, list }
}

// Pagination
const perPage = 10;
const { total, list } = await fetchPosts({ page: 1, perPage });
const pageCount = Math.ceil(total / perPage);
```

## Récupérer le contenu complet

L'endpoint ne renvoie pas le corps des articles. Pour l'obtenir, faire un `GET` sur le permalien du contenu : grâce à `single.php` / `page.php`, la réponse contient uniquement le HTML de `the_content()`. Voir [Templates](templates.md).
