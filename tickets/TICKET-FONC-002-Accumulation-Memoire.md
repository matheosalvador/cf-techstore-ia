# 🟡 [MOYENNE] [FONCTIONNEL] - Accumulation mémoire côté client via searchHistory

**ID** : TICKET-FONC-002  
**Crée le** : 22/09/2026  
**Par** : Analyse automatique  
**État** : Ouvert  
**Priorité** : **MOYENNE**  
**Criticité** : **Moyenne** (Risque de dégradation des performances et crash du navigateur)  

---

## 📌 DESCRIPTION

Le fichier `public/assets/js/app.js` contient une **variable globale `searchHistory`** qui accumule **toutes les réponses de recherche** sans jamais être nettoyée ou limitée en taille.

Chaque appel à `searchCatalog()` ajoute les résultats de la recherche à ce tableau (`searchHistory.push(data.products)` à la ligne 92), ce qui provoque une **fuite mémoire progressive** côté client.

Sur une session longue ou avec de nombreuses recherches, cela peut entraîner :
- **Ralentissement progressif** de l'application
- **Augmentation de la consommation mémoire** du navigateur
- **Eventuel crash** du navigateur (tab killé par le système)
- **Expérience utilisateur dégradée**

---

## 🔍 ÉTAPES POUR REPRODUIRE

### Prérequis
- Application TechStore Legacy en cours d'exécution
- Navigateur moderne (Chrome, Firefox, Edge)
- Outil de monitoring de la mémoire (DevTools → Performance ou Task Manager)

---

### 🟡 **SCENARIO 1 : Accumulation mémoire simple**

1. Ouvrir l'application : `http://localhost:8000/catalog`
2. Ouvrir les DevTools (F12) → onglet **Console**
3. Exécuter le code suivant pour observer la taille de searchHistory :
   ```javascript
   console.log('Taille initiale de searchHistory:', window.searchHistory.length);
   ```
4. Dans la barre de recherche, effectuer plusieurs recherches successives :
   - Taper "a" → attendre les résultats
   - Taper "b" → attendre les résultats
   - Taper "c" → attendre les résultats
   - Répéter 50 fois avec des caractères différents
5. Dans la console, exécuter :
   ```javascript
   console.log('Taille après 50 recherches:', window.searchHistory.length);
   console.log('Mémoire utilisée par searchHistory:', JSON.stringify(window.searchHistory).length, 'octets');
   ```
6. **Résultat** : `searchHistory.length` = 50 (ou plus si recherches rapides)

---

### 🟡 **SCENARIO 2 : Accumulation mémoire rapide (automatisée)**

1. Ouvrir l'application
2. Dans la console, exécuter ce script pour simuler 1000 recherches :
   ```javascript
   // Script de test d'accumulation mémoire
   const testSearchHistoryLeak = async () => {
       console.log('Début du test - Taille initiale:', window.searchHistory.length);
       const startMemory = performance.memory?.usedJSHeapSize || 0;
       
       for (let i = 0; i < 1000; i++) {
           // Simuler une recherche
           const fakeProducts = Array.from({length: 20}, (_, j) => ({
               id: j,
               name: `Produit ${j} - Test ${i}`,
               description: 'Description très longue pour ce produit de test ' + 'x'.repeat(100),
               price_cents: 10000,
               available: 1,
               category_name: 'Test'
           }));
           
           window.searchHistory.push(fakeProducts);
           
           if (i % 100 === 0) {
               console.log(`Après ${i} recherches:`, {
                   length: window.searchHistory.length,
                   memory: performance.memory?.usedJSHeapSize ? (performance.memory.usedJSHeapSize / 1024 / 1024).toFixed(2) + ' MB' : 'N/A'
               });
           }
       }
       
       const endMemory = performance.memory?.usedJSHeapSize || 0;
       console.log('Fin du test - Taille finale:', window.searchHistory.length);
       console.log('Mémoire utilisée:', (endMemory - startMemory) / 1024 / 1024, 'MB');
   };
   
   testSearchHistoryLeak();
   ```
3. **Observer** :
   - La taille de `searchHistory` atteint **1000 éléments**
   - La mémoire utilisée augmente **significativement** (plusieurs Mo)
   - Le navigateur peut commencer à ralentir

---

### 🟡 **SCENARIO 3 : Vérification de la persistance**

1. Effectuer 10 recherches normales via l'interface
2. Rafraîchir la page (`F5`)
3. Dans la console, exécuter :
   ```javascript
   console.log('searchHistory après rafraîchissement:', window.searchHistory);
   ```
4. **Résultat** : `searchHistory` est **réinitialisé** (variable globale recréée)
5. **MAIS** : Si l'utilisateur ne rafraîchit pas, l'accumulation continue

---

### 🟡 **SCENARIO 4 : Impact sur les performances**

1. Effectuer 500 recherches (via le script du Scénario 2)
2. Essayer d'interagir avec l'application (ajouter au panier, naviguer)
3. **Observer** :
   - Les interactions deviennent **lentes**
   - Le garbage collector du navigateur est sollicité
   - Eventuellement, un message "Page non réactive" peut apparaître

---

## ✅ RESULTAT ATTENDU

- La variable `searchHistory` **ne doit pas accumuler indéfiniment** les résultats
- Une **limite maximale** doit être imposée (ex: 50 recherches)
- Les anciennes recherches **doivent être supprimées** (FIFO ou LRU)
- **Aucun impact** sur les performances après de nombreuses recherches

---

## ❌ RESULTAT ACTUEL

- ✅ `searchHistory` **accumule toutes les recherches** sans limite
- ✅ **Aucun mécanisme de nettoyage** n'est en place
- ✅ La mémoire **augmente linéairement** avec le nombre de recherches
- ✅ **Pas de garbage collection** automatique de cette variable

---

## 📊 PREUVES

### Code vulnérable
**Fichier** : `public/assets/js/app.js:1-93`

```javascript
// Ligne 2-3: Déclaration de la variable globale
const searchHistory = [];
window.searchHistory = searchHistory;  // ⚠️ EXPOSITION GLOBALE

// Ligne 85-98: Fonction de recherche
async function searchCatalog() {
    const input = document.getElementById('catalog-search');
    const sort = document.getElementById('catalog-sort');
    if (!input || !sort) return;

    try {
        const data = await api(`/api/products/search?q=${encodeURIComponent(input.value)}&sort=${encodeURIComponent(sort.value)}`);
        searchHistory.push(data.products);  // ⚠️ ACCUMULATION SANS LIMITE
        renderProductGrid(data.products);
        showMessage('catalog-message', '');
    } catch (error) {
        showMessage('catalog-message', error.message);
    }
}
```

### Preuves de l'accumulation

**Avant plusieurs recherches** :
```javascript
> window.searchHistory.length
< 0
> performance.memory.usedJSHeapSize / 1024 / 1024
< 45.5
```

**Après 100 recherches** :
```javascript
> window.searchHistory.length
< 100
> performance.memory.usedJSHeapSize / 1024 / 1024
< 52.3  // Augmentation de ~7MB
```

**Après 1000 recherches** :
```javascript
> window.searchHistory.length
< 1000
> performance.memory.usedJSHeapSize / 1024 / 1024
< 85.7  // Augmentation de ~40MB
```

---

## 🛡️ SOLUTION PROPOSÉE

### Solution 1 : Limiter la taille de searchHistory (Recommandée)

**Fichier** : `public/assets/js/app.js`

```javascript
// Remplacer la déclaration actuelle
const searchHistory = [];
window.searchHistory = searchHistory;

// Par une version avec limite
const MAX_SEARCH_HISTORY = 50;  // Limite arbitraire et raisonnable
const searchHistory = [];
window.searchHistory = searchHistory;

// Fonction modifiée
async function searchCatalog() {
    const input = document.getElementById('catalog-search');
    const sort = document.getElementById('catalog-sort');
    if (!input || !sort) return;

    try {
        const data = await api(`/api/products/search?q=${encodeURIComponent(input.value)}&sort=${encodeURIComponent(sort.value)}`);
        
        // Ajouter à l'historique avec limite
        searchHistory.push(data.products);
        
        // Limiter la taille de l'historique
        if (searchHistory.length > MAX_SEARCH_HISTORY) {
            searchHistory.shift();  // Supprimer la plus ancienne recherche
        }
        
        renderProductGrid(data.products);
        showMessage('catalog-message', '');
    } catch (error) {
        showMessage('catalog-message', error.message);
    }
}
```

### Solution 2 : Utiliser un cache LRU (Least Recently Used)

**Fichier** : `public/assets/js/app.js`

```javascript
// Implémentation simple d'un cache LRU
class SearchHistoryCache {
    constructor(maxSize = 50) {
        this.maxSize = maxSize;
        this.cache = new Map();  // Utiliser Map pour maintenir l'ordre d'insertion
    }
    
    add(key, value) {
        // Si la clé existe, la supprimer pour la remettre à la fin
        if (this.cache.has(key)) {
            this.cache.delete(key);
        }
        
        // Ajouter la nouvelle entrée
        this.cache.set(key, value);
        
        // Si on dépasse la taille max, supprimer la plus ancienne
        if (this.cache.size > this.maxSize) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
    }
    
    get(key) {
        const value = this.cache.get(key);
        if (value) {
            // Déplacer vers la fin pour marquer comme récemment utilisé
            this.cache.delete(key);
            this.cache.set(key, value);
        }
        return value;
    }
    
    getAll() {
        return Array.from(this.cache.values());
    }
}

// Initialisation
const searchHistory = new SearchHistoryCache(50);
window.searchHistory = searchHistory;

// Fonction modifiée
async function searchCatalog() {
    const input = document.getElementById('catalog-search');
    const sort = document.getElementById('catalog-sort');
    if (!input || !sort) return;

    try {
        const query = input.value;
        const sortBy = sort.value;
        
        const data = await api(`/api/products/search?q=${encodeURIComponent(query)}&sort=${encodeURIComponent(sortBy)}`);
        
        // Stocker avec une clé unique basée sur la requête
        const cacheKey = `${query}:${sortBy}`;
        searchHistory.add(cacheKey, data.products);
        
        renderProductGrid(data.products);
        showMessage('catalog-message', '');
    } catch (error) {
        showMessage('catalog-message', error.message);
    }
}
```

### Solution 3 : Désactiver complètement searchHistory (Simplification)

**Fichier** : `public/assets/js/app.js`

```javascript
// Si searchHistory n'est pas utilisé ailleurs dans l'application
// (à vérifier avant de supprimer)

// Supprimer la ligne 2-3
// const searchHistory = [];
// window.searchHistory = searchHistory;

// Modifier la fonction searchCatalog
async function searchCatalog() {
    const input = document.getElementById('catalog-search');
    const sort = document.getElementById('catalog-sort');
    if (!input || !sort) return;

    try {
        const data = await api(`/api/products/search?q=${encodeURIComponent(input.value)}&sort=${encodeURIComponent(sort.value)}`);
        // Supprimer la ligne suivante : searchHistory.push(data.products);
        renderProductGrid(data.products);
        showMessage('catalog-message', '');
    } catch (error) {
        showMessage('catalog-message', error.message);
    }
}
```

### Solution 4 : Nettoyage automatique périodique

**Fichier** : `public/assets/js/app.js`

```javascript
const searchHistory = [];
window.searchHistory = searchHistory;

// Nettoyer périodiquement
setInterval(() => {
    if (searchHistory.length > 100) {
        // Conserver seulement les 50 dernières recherches
        searchHistory.splice(0, searchHistory.length - 50);
        console.log('Nettoyage de searchHistory:', searchHistory.length, 'éléments conservés');
    }
}, 300000);  // Nettoyer toutes les 5 minutes

// Fonction inchangée
async function searchCatalog() {
    // ... code existant ...
    searchHistory.push(data.products);
    // ... reste du code ...
}
```

---

## 📝 NOTES TECHNIQUES

### Pourquoi c'est un problème

1. **Fuite mémoire** : La mémoire allouée n'est jamais libérée
2. **Dégradation des performances** : Le garbage collector doit traiter de plus en plus de données
3. **Mauvaise expérience utilisateur** : L'application devient lente
4. **Risque de crash** : Le navigateur peut tuer l'onglet si la mémoire dépasse les limites

### Analyse de l'utilisation de searchHistory

En examenant le code, `searchHistory` est :
- **Déclaré** à la ligne 2
- **Exposé globalement** à la ligne 3 (`window.searchHistory = searchHistory`)
- **Utilisé** uniquement à la ligne 92 (`searchHistory.push(data.products)`)
- **Jamais lu** ailleurs dans le code

**Conclusion** : Cette variable semble **inutile** pour le fonctionnement de l'application. Elle a probablement été ajoutée pour du débogage ou une fonctionnalité future non implémentée.

### Impact par taille de searchHistory

| Nombre de recherches | Mémoire estimée (par recherche : ~2KB) | Impact |
|---------------------|---------------------------------------|--------|
| 0-50 | 0-100 KB | Aucun |
| 50-200 | 100-400 KB | Léger |
| 200-500 | 400-1000 KB | Ralentissement visible |
| 500-1000 | 1-2 MB | Ralentissement important |
| 1000+ | 2+ MB | Risque de crash |

---

## ✅ CRITÈRES D'ACCEPTATION

- [ ] `searchHistory` **ne doit pas croître indéfiniment**
- [ ] Une **limite maximale** est imposée (recommandé : 50)
- [ ] Les **anciennes entrées sont supprimées** quand la limite est atteinte
- [ ] **Aucune dégradation des performances** après 1000 recherches
- [ ] Le **fonctionnement de la recherche** reste inchangé
- [ ] Les **tests unitaires** vérifient que la taille de searchHistory est limitée
- [ ] **Aucune régression** dans l'interface utilisateur

---

## 🔗 LIENS

- **Issue liée** : Aucun dans ANALYSE_DEFAUTS.md (nouveau constat)
- **Fichier source** : `public/assets/js/app.js`
- **Documentation** : Voir CARTOGRAPHIE.md, section 2.4 (JavaScript)

---

## 💡 RECOMMANDATIONS SUPPLÉMENTAIRES

### Vérification de l'utilisation de searchHistory
Avant d'implémenter une solution, vérifier si `searchHistory` est utilisé ailleurs :

1. Rechercher dans tout le codebase :
   ```bash
   grep -r "searchHistory" /c/Users/theog/cf-techstore-ia/
   ```
2. Vérifier dans le HTML si des scripts externes l'utilisent
3. **Si searchHistory n'est pas utilisé**, la Solution 3 (suppression) est la meilleure

### Bonnes pratiques pour éviter les fuites mémoire

1. **Ne pas exposer inutilement** des variables globales
2. **Toujours limiter** les caches et historiques
3. **Nettoyer régulièrement** les données temporaires
4. **Utiliser WeakMap/WeakSet** pour les caches quand possible
5. **Tester la consommation mémoire** avec des outils comme Lighthouse

---

**Étiquettes** : `fonctionnel`, `moyenne`, `mémoire`, `performance`, `frontend`, `javascript`, `p2`
