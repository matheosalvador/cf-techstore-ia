# 🔴 [CRITIQUE] [SECURITE] - Vulnérabilité d'Injection SQL dans la recherche de produits

**ID** : TICKET-SEC-001  
**Crée le** : 22/09/2026  
**Par** : Analyse automatique  
**État** : Ouvert  
**Priorité** : **CRITIQUE**  
**Criticité** : **Élevée** (Risque de compromission complète de la base de données)  

---

## 📌 DESCRIPTION

La méthode `ProductRepository::search()` dans le fichier `src/Repository/ProductRepository.php` **utilise une concaténation directe** de la requête utilisateur (`$query`) dans la clause WHERE de la requête SQL **sans aucun échappement ni utilisation de requêtes préparées**.

Cette vulnérabilité permet à un attaquant d'**exécuter des requêtes SQL arbitraires** en injectant du code malveillant via le paramètre de recherche `q`.

---

## 🔍 ÉTAPES POUR REPRODUIRE

### Prérequis
- Application TechStore Legacy en cours d'exécution (`php -S localhost:8000 -t public`)
- Base de données SQLite initialisée (`php scripts/reset_database.php`)
- Navigateur ou outil de requêtage (curl, Postman, navigateur)

### Méthode 1 : Via le navigateur
1. Accéder à l'URL : `http://localhost:8000/catalog`
2. Ouvrir les outils de développement (F12)
3. Dans la barre de recherche, entrer la requête malveillante :
   ```
   ' OR '1'='1
   ```
4. Observer que **tous les produits** sont retournés (au lieu de zéro résultat)

### Méthode 2 : Via curl
```bash
curl -X GET "http://localhost:8000/api/products/search?q=' OR '1'='1&sort=name"
```
**Résultat attendu** : Tous les produits sont retournés

### Méthode 3 : Exploitation avancée (lecture de données sensibles)
```bash
curl -X GET "http://localhost:8000/api/products/search?q=' UNION SELECT * FROM users-- &sort=name"
```
**Résultat attendu** : Les informations des utilisateurs (emails, hash de mots de passe) pourraient être exposées

### Méthode 4 : Exploitation destructive
```bash
curl -X GET "http://localhost:8000/api/products/search?q='; DROP TABLE products;-- &sort=name"
```
**Résultat attendu** : **Suppression de la table products** (si SQLite autorise les requêtes multiples)

---

## ✅ RESULTAT ATTENDU

- Les requêtes de recherche doivent **retourner uniquement les produits correspondant au critère de recherche**
- Les caractères spéciaux doivent être **échappés** ou les requêtes doivent utiliser des **paramètres préparés**
- Toute tentative d'injection SQL doit être **bloquée et rejetée**

---

## ❌ RESULTAT ACTUEL

- **Toutes les requêtes malveillantes réussissent**
- **Accès non autorisé aux données** (tables users, orders, etc.)
- **Modification/suppression des données** possible
- **Aucun mécanisme de protection** détecté

---

## 📊 PREUVES

### Code vulnérable
**Fichier** : `src/Repository/ProductRepository.php:20-34`

```php
public function search(string $query, string $sort = 'name'): array
{
    $orderBy = $sort ?: 'name';
    $where = $query !== '' ? "WHERE p.name LIKE '%" . $query . "%'" : '';  // ⚠️ INJECTION POSSIBLE ICI

    $sql = "
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        $where
        ORDER BY $orderBy
    ";

    return $this->pdo->query($sql)->fetchAll();
}
```

### Logs attendus (si activés)
```
[ERROR] SQL Error: near "OR": syntax error
[WARNING] Suspicious query pattern detected: ' OR '
```
*(Aucun log n'est actuellement généré car la requête passe)*

---

## 🛡️ SOLUTION PROPOSÉE

### Solution 1 : Utilisation de requêtes préparées (Recommandée)
```php
public function search(string $query, string $sort = 'name'): array
{
    $allowedSorts = ['name', 'price_cents', 'available DESC, name'];
    $orderBy = in_array($sort, $allowedSorts) ? $sort : 'name';
    
    $sql = "
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.name LIKE :query
        ORDER BY $orderBy
    ";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':query' => "%" . $query . "%"]);
    
    return $stmt->fetchAll();
}
```

### Solution 2 : Échappement des entrées (Alternative)
```php
$where = $query !== '' ? "WHERE p.name LIKE '%" . $this->pdo->quote($query) . "%'" : '';
```

### Solution 3 : Validation stricte des paramètres
- Valider que `$query` ne contient pas de caractères SQL dangereux (`'`, `;`, `--`, `/*`, etc.)
- Utiliser une liste blanche pour `$sort`

---

## 📝 NOTES TECHNIQUES

### Classes concernées
- `TechStore\Repository\ProductRepository`

### Méthodes concernées
- `search(string $query, string $sort)`
- `all(string $sort)` (appelle `search()`)

### Endpoints concernés
- `GET /api/products/search`
- `GET /catalog` (affichage initial)

---

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Les requêtes SQL utilisent des **paramètres préparés** ou un échappement sécurisé
- [ ] Une requête avec `' OR '1'='1` retourne **0 résultat** ou une erreur
- [ ] Une requête avec des caractères spéciaux (`'`, `;`, `--`) est **correctement traitée**
- [ ] Le paramètre `$sort` est **validé contre une liste blanche**
- [ ] **Aucune donnée sensible** ne peut être extraite via l'injection SQL
- [ ] Les **tests unitaires** couvrent les cas d'injection SQL

---

## 🔗 LIENS

- **Issue liée** : DFAUT-SEC-001 (dans ANALYSE_DEFAUTS.md)
- **Fichier source** : `src/Repository/ProductRepository.php`
- **Documentation** : Voir CARTOGRAPHIE.md, section 2.1.1

---

**Étiquettes** : `securite`, `critique`, `injection-sql`, `backend`, `p0`
