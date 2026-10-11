# Webhooks

Press Bridge notifie l'application front-end lorsque du contenu change dans WordPress. Les notifications sont envoyées de façon **synchrone** par la fonction `notify()` (`functions.php`) via cURL.

## Format commun

- **Méthode** : `POST`
- **URL** : `_HOOK_PATH_BASE_` + endpoint (par défaut `<_UI_URL_>/press-bridge/<endpoint>`)
- **En-tête** : `Content-Type: application/json`
- **Corps** : objet JSON contenant toujours le champ `token` (valeur de `_API_TOKEN_`) fusionné avec les données de l'événement.

Délais : 3 s pour la connexion, 5 s au total. En cas d'échec réseau ou de réponse HTTP ≥ 400, une ligne préfixée `[press_bridge]` est écrite dans le journal d'erreurs PHP (`error_log`). Il n'y a pas de nouvelle tentative.

## `POST /add` et `POST /update`

Déclenchés par le hook WordPress `save_post`, pour les types listés dans `_SUPPORTED_POST_TYPES_`.

- `/add` lorsque WordPress indique qu'il s'agit d'une création (`$update === false`) ;
- `/update` dans tous les autres cas.

> **Attention** : lorsqu'un rédacteur clique sur *Ajouter*, WordPress crée immédiatement un brouillon automatique (`auto-draft`). C'est **cet** enregistrement qui déclenche `/add`, avec un titre vide et `status: "not_published"`. La publication ultérieure arrive donc sous forme de `/update`. L'application front-end doit traiter `/update` comme un *upsert*.

### Corps

```json
{
  "token": "api_token",
  "post_type": "post",
  "id": 42,
  "slug": "mon-article",
  "title": "Mon article",
  "status": "published",
  "date": "2026-10-11 14:32:00",
  "author": "Jane Doe",
  "thumbnail": "https://example.com/wp-content/uploads/2026/10/image.jpg",
  "excerpt": "Résumé de l'article"
}
```

| Champ | Type | Description |
|---|---|---|
| `token` | string | Secret partagé (`_API_TOKEN_`) |
| `post_type` | string | `post`, `page`, ou tout type de `_SUPPORTED_POST_TYPES_` |
| `id` | int | ID WordPress |
| `slug` | string | `post_name` (peut être vide pour un brouillon) |
| `title` | string | Titre |
| `status` | string | `"published"` si `post_status === "publish"`, sinon `"not_published"` (brouillon, en attente, privé, programmé, corbeille…) |
| `date` | string | `post_date`, heure locale du site, format `Y-m-d H:i:s` |
| `author` | string | Nom affiché (`display_name`) de l'auteur |
| `thumbnail` | string | URL de l'image mise en avant en taille `full`, ou `""` s'il n'y en a pas |
| `excerpt` | string | Extrait manuel. **Absent pour les pages.** |

### Cas particuliers

- **Mise à la corbeille** : WordPress ré-enregistre le contenu avec le statut `trash`, ce qui produit un `/update` avec `status: "not_published"`. Le front doit donc retirer de l'affichage tout contenu non publié.
- **Dépublication** (repasser en brouillon, rendre privé) : idem, `/update` avec `status: "not_published"`.
- **Publication programmée** : à l'enregistrement, `/update` avec `status: "not_published"` (statut `future`) ; à l'heure prévue, WordPress publie le contenu et envoie un `/update` avec `status: "published"`.
- **Révisions et autosaves** : leur `post_type` est `revision`, elles sont ignorées.

## `POST /remove`

Déclenché par le hook `delete_post`, c'est-à-dire lors de la **suppression définitive** (vidage de la corbeille), pour les types listés dans `_SUPPORTED_POST_TYPES_`.

### Corps

```json
{
  "token": "api_token",
  "post_type": "post",
  "id": 42
}
```

## Exemple de récepteur (Express)

```js
import express from "express";

const app = express();
app.use(express.json());

const TOKEN = process.env.PRESS_BRIDGE_TOKEN;
const cache = new Map(); // clé : `${post_type}:${id}`

app.use("/press-bridge", (req, res, next) => {
  if (req.body?.token !== TOKEN) return res.sendStatus(401);
  next();
});

const upsert = (req, res) => {
  const { token, ...post } = req.body;
  const key = `${post.post_type}:${post.id}`;
  if (post.status === "published") cache.set(key, post);
  else cache.delete(key);
  res.sendStatus(204);
};

app.post("/press-bridge/add", upsert);
app.post("/press-bridge/update", upsert);

app.post("/press-bridge/remove", (req, res) => {
  cache.delete(`${req.body.post_type}:${req.body.id}`);
  res.sendStatus(204);
});

app.listen(3000);
```

## Performance

L'appel cURL est bloquant : une application front-end lente ralentit l'enregistrement dans l'éditeur WordPress, au maximum de 5 secondes par notification. Le récepteur doit répondre vite et différer les traitements lourds.
