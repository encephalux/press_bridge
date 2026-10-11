# Configuration

Toute la configuration se trouve dans `env.php`, sous forme de constantes PHP. Ce fichier est chargé par `functions.php` et `index.php`.

```php
const _RUN_ENV_ = "production";
const _API_TOKEN_ = "api_token";
const _UI_URL_ = "https://";
const _HOOK_PATH_BASE_ = _UI_URL_ . "/press-bridge";
const _SUPPORTED_POST_TYPES_ = ["post", "page"];
const _ALLOWED_ORIGIN_ = "*";
```

| Constante | Type | Défaut | Rôle |
|---|---|---|---|
| `_RUN_ENV_` | `string` | `"production"` | Environnement d'exécution. Non utilisé par le code actuellement ; réservé pour distinguer développement / production. |
| `_API_TOKEN_` | `string` | `"api_token"` | Secret partagé, ajouté au corps de chaque webhook dans le champ `token`. L'application front-end doit le vérifier. **À changer impérativement.** |
| `_UI_URL_` | `string` | `"https://"` | URL de base de l'application front-end, **sans slash final**. Cible des redirections de `index.php` et base des webhooks. |
| `_HOOK_PATH_BASE_` | `string` | `_UI_URL_ . "/press-bridge"` | Préfixe des URLs de webhook. Les endpoints `/add`, `/update`, `/remove` y sont ajoutés. |
| `_SUPPORTED_POST_TYPES_` | `string[]` | `["post", "page"]` | Types de contenu pour lesquels un webhook de sauvegarde est envoyé et qui peuvent être demandés à l'endpoint AJAX. |
| `_ALLOWED_ORIGIN_` | `string` | `"*"` | Valeur de l'en-tête `Access-Control-Allow-Origin` renvoyé par l'endpoint AJAX et les templates. Mettre l'URL du front pour restreindre. |

## Exemple

```php
<?php

const _RUN_ENV_ = "production";
const _API_TOKEN_ = "4f9c1e…";                       // ex. généré avec : openssl rand -hex 32
const _UI_URL_ = "https://www.example.com";
const _HOOK_PATH_BASE_ = _UI_URL_ . "/press-bridge";  // → https://www.example.com/press-bridge/add, …
const _SUPPORTED_POST_TYPES_ = ["post", "page"];
const _ALLOWED_ORIGIN_ = "https://www.example.com";
```

## Recommandations

- Ne pas versionner un `env.php` contenant le vrai token. Garder la version par défaut dans le dépôt et la surcharger au déploiement.
- Utiliser HTTPS pour `_UI_URL_` : le token circule en clair dans le corps des webhooks.
- `_ALLOWED_ORIGIN_` s'applique à toutes les réponses du thème (endpoint AJAX et templates), via `send_cors_headers()`.
