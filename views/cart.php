<section class="page-head">
    <div>
        <h1>Panier</h1>
        <p>Vérifiez les quantités, appliquez une remise et validez votre commande.</p>
    </div>
</section>

<section id="cart-message" class="notice hidden"></section>
<section class="cart-layout">
    <div>
        <table class="data-table">
            <thead>
            <tr>
                <th>Produit</th>
                <th>Prix HT</th>
                <th>Quantité</th>
                <th>Total ligne</th>
                <th></th>
            </tr>
            </thead>
            <tbody id="cart-lines"></tbody>
        </table>
    </div>
    <aside class="summary">
        <label>
            Remise
            <select id="discount-percent">
                <option value="0">0 %</option>
                <option value="10">10 %</option>
                <option value="20">20 %</option>
            </select>
        </label>
        <dl>
            <div><dt>Sous-total HT</dt><dd id="subtotal">0,00 €</dd></div>
            <div><dt>Remise</dt><dd id="discount">0,00 €</dd></div>
            <div><dt>Taxe</dt><dd id="tax">0,00 €</dd></div>
            <div class="grand-total"><dt>Total TTC</dt><dd id="total">0,00 €</dd></div>
        </dl>
        <button id="checkout-button" type="button">Valider la commande</button>
    </aside>
</section>
