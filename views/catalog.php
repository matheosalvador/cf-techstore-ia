<section class="page-head">
    <div>
        <h1>Catalogue</h1>
        <p>Matériel informatique pour postes de travail, mises à niveau et périphériques.</p>
    </div>
    <a class="button secondary" href="/cart">Voir le panier</a>
</section>

<section class="toolbar" aria-label="Recherche catalogue">
    <label>
        Rechercher
        <input id="catalog-search" type="search" placeholder="Nom du produit">
    </label>
    <label>
        Trier
        <select id="catalog-sort">
            <option value="name">Nom</option>
            <option value="price_cents">Prix</option>
            <option value="available DESC, name">Disponibilité</option>
        </select>
    </label>
</section>

<section id="catalog-message" class="notice hidden"></section>
<section id="product-grid" class="product-grid">
    <?php foreach ($products as $product): ?>
        <article class="product-card">
            <div>
                <p class="category"><?= htmlspecialchars($product['category_name']) ?></p>
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                <p><?= htmlspecialchars($product['description']) ?></p>
            </div>
            <div class="product-footer">
                <strong><?= format_price((int) $product['price_cents']) ?> HT</strong>
                <span class="<?= (int) $product['available'] === 1 ? 'available' : 'unavailable' ?>">
                    <?= (int) $product['available'] === 1 ? 'Disponible' : 'Indisponible' ?>
                </span>
                <?php if ((int) $product['available'] === 1): ?>
                    <button type="button" class="add-to-cart" data-product='<?= htmlspecialchars(json_encode($product, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'>Ajouter</button>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
