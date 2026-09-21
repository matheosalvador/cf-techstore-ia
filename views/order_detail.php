<section class="page-head">
    <div>
        <h1>Détail commande</h1>
        <p id="order-heading">Chargement de la commande.</p>
    </div>
</section>

<section id="order-message" class="notice hidden"></section>
<section id="order-detail" class="detail-panel hidden">
    <dl class="order-meta">
        <div><dt>Référence</dt><dd id="order-reference"></dd></div>
        <div><dt>Date UTC</dt><dd id="order-date"></dd></div>
        <div><dt>Client</dt><dd id="order-customer"></dd></div>
        <div><dt>Statut</dt><dd id="order-status"></dd></div>
    </dl>
    <table class="data-table">
        <thead>
        <tr>
            <th>Produit</th>
            <th>Prix HT</th>
            <th>Quantité</th>
            <th>Total ligne</th>
        </tr>
        </thead>
        <tbody id="order-items"></tbody>
    </table>
    <dl class="summary-lines">
        <div><dt>HT après remise</dt><dd id="order-discounted"></dd></div>
        <div><dt>Taxe</dt><dd id="order-tax"></dd></div>
        <div><dt>Total TTC</dt><dd id="order-total"></dd></div>
    </dl>
</section>
