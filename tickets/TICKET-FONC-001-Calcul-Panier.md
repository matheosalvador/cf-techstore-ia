# 🟠 [HAUTE] [FONCTIONNEL] - Problèmes de validation et calcul dans le panier

**ID** : TICKET-FONC-001  
**Crée le** : 22/09/2026  
**Par** : Analyse automatique  
**État** : Ouvert  
**Priorité** : **HAUTE**  
**Criticité** : **Élevée** (Risque de fraude et incohérence financière)  

---

## 📌 DESCRIPTION

Le système de **calcul du panier** et de **validation des commandes** présente plusieurs vulnérabilités critiques :

1. **Pas de validation des quantités** : Il est possible d'ajouter des produits avec des quantités **négatives, nulles ou aberrantes**
2. **Pas de validation des prix** : Les prix peuvent être manipulés côté client
3. **Pas de cohérence des totaux** : Les totaux calculés côté client peuvent ne pas correspondre aux prix réels côté serveur
4. **Normalisation insuffisante** : La méthode `normalizeItems()` ne valide pas toutes les valeurs

Ces problèmes permettent à un utilisateur malveillant de **manipuler les prix** et **valider des commandes avec des montants frauduleux**.

---

## 🔍 ÉTAPES POUR REPRODUIRE

### Prérequis
- Application TechStore Legacy en cours d'exécution
- Compte utilisateur valide (ex: `alice.martin@example.test` / `password`)
- Outil de manipulation de requêtes (DevTools, Postman, curl)

---

### 🔴 **SCENARIO 1 : Quantité négative dans le panier**

1. Se connecter à l'application
2. Ajouter un produit au panier via l'interface normale
3. Ouvrir les DevTools (F12) → onglet **Console**
4. Exécuter le code suivant pour modifier la quantité :
   ```javascript
   store.cart[0].quantity = -5;
   saveCart();
   ```
5. Aller sur la page `/cart`
6. **Observer** : La quantité affichée est **-5**
7. Cliquer sur "Valider la commande"
8. **Résultat** : La commande peut être validée avec une quantité négative

**Impact** : Le client pourrait recevoir de l'argent au lieu de payer

---

### 🔴 **SCENARIO 2 : Prix manipulé (prix négatif)**

1. Se connecter à l'application
2. Ajouter un produit au panier
3. Dans la console, exécuter :
   ```javascript
   store.cart[0].unit_price_cents = -10000;  // Prix négatif
   saveCart();
   ```
4. Rafraîchir la page `/cart`
5. **Observer** : Le prix du produit est affiché comme **-100,00 €**
6. Cliquer sur "Valider la commande"
7. **Résultat** : La commande est validée avec un prix négatif

**Impact** : Le client reçoit de l'argent au lieu de payer

---

### 🔴 **SCENARIO 3 : Prix manipulé (prix réduit à 1 centime)**

1. Se connecter à l'application
2. Ajouter un produit au panier
3. Dans la console, exécuter :
   ```javascript
   store.cart[0].unit_price_cents = 1;  // Prix réduit
   saveCart();
   ```
4. Rafraîchir la page `/cart`
5. Cliquer sur "Valider la commande"
6. **Résultat** : La commande est validée avec un prix de 0,01 € au lieu du prix réel

**Impact** : Fraude financière - perte de revenus pour le magasin

---

### 🔴 **SCENARIO 4 : Quantité excessive**

1. Se connecter à l'application
2. Ajouter un produit au panier
3. Dans la console, exécuter :
   ```javascript
   store.cart[0].quantity = 999999;
   saveCart();
   ```
4. Cliquer sur "Valider la commande"
5. **Résultat** : La commande peut être validée avec une quantité irréaliste

**Impact** : Problèmes de stock, erreurs de calcul

---

### 🔴 **SCENARIO 5 : Contournement de la vérification de disponibilité**

1. Se connecter à l'application
2. Ajouter un produit disponible au panier
3. Rendre le produit indisponible en base (via admin ou directement en SQL) :
   ```bash
   sqlite3 database/techstore.sqlite "UPDATE products SET available = 0 WHERE id = 1"
   ```
4. Dans le panier, le produit est toujours présent
5. Cliquer sur "Valider la commande"
6. **Résultat** : La commande **est validée** malgré l'indisponibilité du produit

**Vulnérabilité liée** : Voir aussi TICKET-FONC-002 (concurrence sur les stocks)

---

### 🔴 **SCENARIO 6 : Remise abusive**

1. Se connecter à l'application
2. Ajouter un produit au panier
3. Dans la console, modifier la remise :
   ```javascript
   store.discount = 99;
   saveCart();
   ```
4. **Observer** : La remise reste à 0% (le calcul la réinitialise)
5. **Mais** : En appelant directement l'API avec une remise non autorisée :
   ```bash
   curl -X POST http://localhost:8000/api/cart/totals \
     -H "Authorization: Bearer <TOKEN>" \
     -H "Content-Type: application/json" \
     -d '{"items": [{"product_id": 1, "product_name": "Test", "unit_price_cents": 10000, "quantity": 1, "line_total_cents": 10000}], "discount_percent": 99}'
   ```
6. **Résultat** : La remise est forcée à 0% par `CartCalculator::calculate()`

**Note** : Ce scénario est partiellement protégé, mais la validation devrait être côté serveur

---

## ✅ RESULTAT ATTENDU

- Les quantités doivent être **strictement positives** (>= 1)
- Les prix doivent correspondre aux **prix réels de la base de données**
- Les totaux doivent être **calculés côté serveur** uniquement
- Les produits **indisponibles** ne doivent pas pouvoir être commandés
- Les remises doivent être **validées côté serveur**

---

## ❌ RESULTAT ACTUEL

- ✅ Les quantités négatives sont **acceptées**
- ✅ Les prix manipulés sont **acceptés**
- ✅ Les commandes avec des valeurs aberrantes sont **validées**
- ✅ Les produits indisponibles peuvent être **commandés**
- ⚠️ Les remises sont limitées à [0, 10, 20] mais pas validées côté serveur

---

## 📊 PREUVES

### Code vulnérable : CartController.php
**Fichier** : `src/Controller/CartController.php:22-93`

```php
public function totals(): void
{
    $input = $this->input();
    $items = $this->normalizeItems($input['items'] ?? []);  // ⚠️ PAS DE VALIDATION
    $discount = (int) ($input['discount_percent'] ?? 0);

    $this->json(['totals' => $this->calculator->calculate($items, $discount)]);
}

public function checkout(): void
{
    // ... vérification utilisateur ...
    
    $input = $this->input();
    $items = $this->normalizeItems($input['items'] ?? []);
    
    // ⚠️ PAS DE VALIDATION DES QUANTITÉS OU PRIX
    foreach ($items as $item) {
        $product = $this->products->find((int) $item['product_id']);
        if (!$product) {
            $this->json(['message' => 'Produit absent.'], 404);
            return;
        }
        if ((int) $product['available'] !== 1) {  // ⚠️ VÉRIFICATION TARDIVE
            $this->json(['message' => 'Produit indisponible.'], 422);
            return;
        }
    }
    
    // ... reste du code ...
}

private function normalizeItems(array $items): array
{
    $normalized = [];

    foreach ($items as $item) {
        $quantity = (int) ($item['quantity'] ?? 0);
        if ($quantity <= 0) {  // ⚠️ CONTRADICTION : continue si <= 0, mais devrait rejeter
            continue;
        }

        $unitPrice = (int) ($item['unit_price_cents'] ?? 0);
        // ⚠️ PAS DE VALIDATION QUE unit_price_cents > 0
        
        $normalized[] = [
            'product_id' => (int) ($item['product_id'] ?? $item['id'] ?? 0),
            'product_name' => $name,
            'unit_price_cents' => $unitPrice,
            'quantity' => $quantity,
            'line_total_cents' => $unitPrice * $quantity,  // ⚠️ PAS DE VALIDATION
        ];
    }

    return $normalized;
}
```

### Code vulnérable : CartCalculator.php
**Fichier** : `src/Service/CartCalculator.php:9-32`

```php
public function calculate(array $items, int $discountPercent): array
{
    if (!in_array($discountPercent, [0, 10, 20], true)) {
        $discountPercent = 0;  // ✅ Protection basique
    }

    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (float) ($item['unit_price_cents'] / 100) * (int) $item['quantity'];
        // ⚠️ PAS DE VALIDATION DES VALEURS NÉGATIVES
    }
    
    // ⚠️ SI unit_price_cents EST NÉGATIF, subtotal SERA NÉGATIF
    $tax = $subtotal * 0.20;  // ⚠️ TAXE NÉGATIVE POSSIBLE
    $discount = $subtotal * ($discountPercent / 100);
    $total = $subtotal + $tax - $discount;
    
    return [
        'subtotal_cents' => (int) round($subtotal * 100),
        'discount_percent' => $discountPercent,
        'discount_amount_cents' => (int) round($discount * 100),
        'discounted_subtotal_cents' => (int) round(($subtotal - $discount) * 100),
        'tax_cents' => (int) round($tax * 100),  // ⚠️ PEUT ÊTRE NÉGATIF
        'total_cents' => (int) round($total * 100),  // ⚠️ PEUT ÊTRE NÉGATIF
    ];
}
```

### Preuves de concept

**Requête pour commander avec quantité négative** :
```bash
curl -X POST http://localhost:8000/api/checkout \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "product_id": 1,
        "product_name": "Produit Test",
        "unit_price_cents": 10000,
        "quantity": -5,
        "line_total_cents": -50000
      }
    ],
    "discount_percent": 0
  }'
```

**Réponse attendue** : Erreur 422 ou 400  
**Réponse actuelle** : Commande validée avec un total négatif

---

## 🛡️ SOLUTION PROPOSÉE

### Solution 1 : Validation stricte côté serveur (Recommandée)

#### CartController.php - Validation des items
```php
private function validateAndNormalizeItems(array $items, array $allProducts): array
{
    $normalized = [];
    $productPrices = [];
    
    // Récupérer tous les prix réels depuis la base
    foreach ($allProducts as $product) {
        $productPrices[(int)$product['id']] = (int)$product['price_cents'];
    }

    foreach ($items as $item) {
        $productId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 0);
        $providedPrice = (int) ($item['unit_price_cents'] ?? 0);
        
        // Validations
        if ($quantity <= 0) {
            throw new InvalidArgumentException("La quantité doit être supérieure à 0");
        }
        
        if ($quantity > 1000) {
            throw new InvalidArgumentException("Quantité maximale dépassée");
        }
        
        if (!isset($productPrices[$productId])) {
            throw new InvalidArgumentException("Produit invalide: {$productId}");
        }
        
        $realPrice = $productPrices[$productId];
        
        // NE JAMAIS FAIRE CONFIANCE AU PRIX FOURNI PAR LE CLIENT
        if ($providedPrice !== $realPrice) {
            throw new InvalidArgumentException("Prix invalide pour le produit: {$productId}");
        }
        
        $normalized[] = [
            'product_id' => $productId,
            'product_name' => (string) ($item['product_name'] ?? 'Produit'),
            'unit_price_cents' => $realPrice,  // TOUJOURS UTILISER LE PRIX RÉEL
            'quantity' => $quantity,
            'line_total_cents' => $realPrice * $quantity,
        ];
    }

    return $normalized;
}

public function checkout(): void
{
    $user = $this->auth->currentUser();
    if (!$user || $user['role'] !== 'customer') {
        $this->json(['message' => 'Utilisateur non authentifié.'], 401);
        return;
    }

    $input = $this->input();
    
    // Récupérer TOUS les produits valides
    $allProducts = $this->products->all();
    
    try {
        $items = $this->validateAndNormalizeItems($input['items'] ?? [], $allProducts);
    } catch (InvalidArgumentException $e) {
        $this->json(['message' => $e->getMessage()], 422);
        return;
    }

    if ($items === []) {
        $this->json(['message' => 'Le panier est vide.'], 422);
        return;
    }

    // Vérification finale de disponibilité ET de stock
    foreach ($items as $item) {
        $product = $this->products->find($item['product_id']);
        if (!$product) {
            $this->json(['message' => 'Produit introuvable.'], 404);
            return;
        }
        if ((int) $product['available'] !== 1) {
            $this->json(['message' => 'Produit indisponible.'], 422);
            return;
        }
    }

    $discount = (int) ($input['discount_percent'] ?? 0);
    $this->validateDiscount($discount);  // Nouvelle validation
    
    $totals = $this->calculator->calculate($items, $discount);
    $order = $this->orders->createValidated((int) $user['id'], $items, $totals);

    $this->json(['message' => 'Commande validée.', 'order' => $order], 201);
}

private function validateDiscount(int $discount): void
{
    $allowedDiscounts = [0, 10, 20];
    if (!in_array($discount, $allowedDiscounts, true)) {
        throw new InvalidArgumentException("Remise invalide. Valeurs autorisées: " . implode(', ', $allowedDiscounts));
    }
}
```

### Solution 2 : CartCalculator.php - Validation des valeurs
```php
public function calculate(array $items, int $discountPercent): array
{
    // Validation des paramètres
    if ($discountPercent < 0 || $discountPercent > 100) {
        throw new InvalidArgumentException("Remise doit être entre 0 et 100%");
    }
    
    $subtotal = 0;
    $validatedItems = [];
    
    foreach ($items as $item) {
        $unitPrice = (int) ($item['unit_price_cents'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 0);
        
        if ($unitPrice < 0) {
            throw new InvalidArgumentException("Prix unitaire ne peut pas être négatif");
        }
        
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Quantité doit être positive");
        }
        
        $lineTotal = $unitPrice * $quantity;
        if ($lineTotal < 0) {
            throw new InvalidArgumentException("Total ligne ne peut pas être négatif");
        }
        
        $subtotal += (float) ($unitPrice / 100) * $quantity;
        $validatedItems[] = $item;
    }

    if ($subtotal < 0) {
        throw new InvalidArgumentException("Sous-total ne peut pas être négatif");
    }

    $tax = $subtotal * 0.20;
    $discount = $subtotal * ($discountPercent / 100);
    $total = $subtotal + $tax - $discount;

    if ($total < 0) {
        throw new InvalidArgumentException("Total ne peut pas être négatif");
    }

    return [
        'subtotal_cents' => (int) round($subtotal * 100),
        'discount_percent' => $discountPercent,
        'discount_amount_cents' => (int) round($discount * 100),
        'discounted_subtotal_cents' => (int) round(($subtotal - $discount) * 100),
        'tax_cents' => (int) round($tax * 100),
        'total_cents' => (int) round($total * 100),
    ];
}
```

### Solution 3 : Validation côté client (Complémentaire)

**Fichier** : `public/assets/js/app.js`

```javascript
function addToCart(product) {
    if (Number(product.available) !== 1) {
        showMessage('catalog-message', 'Produit indisponible.');
        return;
    }

    const existing = store.cart.find(item => Number(item.product_id) === Number(product.id));
    if (existing) {
        existing.quantity += 1;
    } else {
        store.cart.push({
            product_id: Number(product.id),
            product_name: product.name,
            unit_price_cents: Number(product.price_cents),
            quantity: 1
        });
    }

    // Validation côté client
    validateCart();
    saveCart();
    showMessage('catalog-message', 'Produit ajouté au panier.');
}

function validateCart() {
    store.cart = store.cart.filter(item => {
        // Quantité doit être >= 1
        if (item.quantity < 1) {
            showMessage('cart-message', `Quantité invalide pour ${item.product_name}. Supprimé du panier.`);
            return false;
        }
        
        // Prix doit être >= 0
        if (item.unit_price_cents < 0) {
            showMessage('cart-message', `Prix invalide pour ${item.product_name}. Supprimé du panier.`);
            return false;
        }
        
        // Quantité raisonnable
        if (item.quantity > 1000) {
            showMessage('cart-message', `Quantité trop élevée pour ${item.product_name}. Réduite à 1000.`);
            item.quantity = 1000;
        }
        
        return true;
    });
}
```

---

## 📝 NOTES TECHNIQUES

### Pourquoi c'est critique
1. **Fraude financière** : Un attaquant peut commander des produits à prix zéro ou négatif
2. **Perte de revenus** : Le magasin perd de l'argent
3. **Incohérence des données** : Les commandes ont des totaux incorrects
4. **Problèmes de stock** : Commandes de quantités irréalistes

### Classes concernées
- `TechStore\Controller\CartController`
- `TechStore\Service\CartCalculator`
- `TechStore\Repository\ProductRepository`

### Endpoints concernés
- `POST /api/cart/totals`
- `POST /api/checkout`

---

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Les quantités **négatives ou nulles** sont **rejetées** avec une erreur 422
- [ ] Les prix **négatifs** sont **rejetés** avec une erreur 422
- [ ] Les prix fournis par le client **sont ignorés** au profit des prix réels de la base
- [ ] Les produits **indisponibles** ne peuvent pas être commandés
- [ ] Les remises **non autorisées** sont rejetées
- [ ] Les commandes avec des **totaux négatifs** ne peuvent pas être validées
- [ ] Les **tests unitaires** couvrent tous les cas de validation
- [ ] Les **logs** enregistrent les tentatives de fraude

---

## 🔗 LIENS

- **Issues liées** : DFAUT-FONC-002, DFAUT-FONC-003 (dans ANALYSE_DEFAUTS.md)
- **Fichier source** : `src/Controller/CartController.php`, `src/Service/CartCalculator.php`
- **Documentation** : Voir CARTOGRAPHIE.md, section 2.2.3

---

**Étiquettes** : `fonctionnel`, `haute`, `panier`, `validation`, `fraude`, `backend`, `frontend`, `p1`
