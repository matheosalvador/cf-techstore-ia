const money = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
const searchHistory = [];
window.searchHistory = searchHistory;

const store = {
    token: localStorage.getItem('techstore_token') || '',
    user: JSON.parse(localStorage.getItem('techstore_user') || 'null'),
    cart: JSON.parse(localStorage.getItem('techstore_cart') || '[]'),
    discount: Number(localStorage.getItem('techstore_discount') || '0')
};

function saveCart() {
    localStorage.setItem('techstore_cart', JSON.stringify(store.cart));
    localStorage.setItem('techstore_discount', String(store.discount));
}

function formatPrice(cents) {
    return money.format(Number(cents || 0) / 100);
}

function authHeaders() {
    return store.token ? { Authorization: `Bearer ${store.token}` } : {};
}

function showMessage(id, message) {
    const element = document.getElementById(id);
    if (!element) return;
    element.textContent = message;
    element.classList.toggle('hidden', !message);
}

async function api(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            ...authHeaders(),
            ...(options.headers || {})
        }
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(data.message || 'Erreur serveur.');
    }
    return data;
}

function updateNavigation() {
    const authLink = document.querySelector('.auth-link');
    const logoutButton = document.querySelector('.logout-button');
    const managerLink = document.querySelector('.manager-link');
    if (authLink) authLink.classList.toggle('hidden', Boolean(store.token));
    if (logoutButton) logoutButton.classList.toggle('hidden', !store.token);
    if (managerLink) managerLink.classList.toggle('hidden', store.user?.role !== 'manager');
}

function renderProductGrid(products) {
    const grid = document.getElementById('product-grid');
    if (!grid) return;
    grid.innerHTML = products.map(product => `
        <article class="product-card">
            <div>
                <p class="category">${escapeHtml(product.category_name)}</p>
                <h2>${escapeHtml(product.name)}</h2>
                <p>${escapeHtml(product.description)}</p>
            </div>
            <div class="product-footer">
                <strong>${formatPrice(product.price_cents)} HT</strong>
                <span class="${Number(product.available) === 1 ? 'available' : 'unavailable'}">
                    ${Number(product.available) === 1 ? 'Disponible' : 'Indisponible'}
                </span>
                ${Number(product.available) === 1 ? `<button type="button" class="add-to-cart" data-id="${product.id}">Ajouter</button>` : ''}
            </div>
        </article>
    `).join('');

    grid.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', () => {
            const product = products.find(item => String(item.id) === String(button.dataset.id));
            if (product) addToCart(product);
        });
    });
}

async function searchCatalog() {
    const input = document.getElementById('catalog-search');
    const sort = document.getElementById('catalog-sort');
    if (!input || !sort) return;

    try {
        const data = await api(`/api/products/search?q=${encodeURIComponent(input.value)}&sort=${encodeURIComponent(sort.value)}`);
        searchHistory.push(data.products);
        renderProductGrid(data.products);
        showMessage('catalog-message', '');
    } catch (error) {
        showMessage('catalog-message', error.message);
    }
}

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

    saveCart();
    showMessage('catalog-message', 'Produit ajouté au panier.');
}

function renderCart() {
    const body = document.getElementById('cart-lines');
    const discount = document.getElementById('discount-percent');
    if (!body || !discount) return;

    discount.value = String(store.discount);
    body.innerHTML = store.cart.map(item => `
        <tr>
            <td>${escapeHtml(item.product_name)}</td>
            <td>${formatPrice(item.unit_price_cents)}</td>
            <td><input class="quantity-input" type="number" min="1" value="${item.quantity}" data-product="${item.product_id}"></td>
            <td>${formatPrice(item.unit_price_cents * item.quantity)}</td>
            <td><button type="button" class="remove-line" data-product="${item.product_id}">Supprimer</button></td>
        </tr>
    `).join('');

    body.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', () => {
            const item = store.cart.find(line => String(line.product_id) === String(input.dataset.product));
            if (item) item.quantity = Math.max(1, Number(input.value || 1));
            saveCart();
            renderCart();
            refreshTotals();
        });
    });

    body.querySelectorAll('.remove-line').forEach(button => {
        button.addEventListener('click', () => {
            store.cart = store.cart.filter(line => String(line.product_id) !== String(button.dataset.product));
            saveCart();
            renderCart();
            refreshTotals();
        });
    });

    refreshTotals();
}

async function refreshTotals() {
    if (!document.getElementById('subtotal')) return;
    try {
        const data = await api('/api/cart/totals', {
            method: 'POST',
            body: JSON.stringify({ items: store.cart, discount_percent: store.discount })
        });
        document.getElementById('subtotal').textContent = formatPrice(data.totals.subtotal_cents);
        document.getElementById('discount').textContent = formatPrice(data.totals.discount_amount_cents);
        document.getElementById('tax').textContent = formatPrice(data.totals.tax_cents);
        document.getElementById('total').textContent = formatPrice(data.totals.total_cents);
    } catch (error) {
        showMessage('cart-message', error.message);
    }
}

async function checkout() {
    if (!store.token) {
        showMessage('cart-message', 'Utilisateur non authentifié.');
        return;
    }
    if (store.cart.length === 0) {
        showMessage('cart-message', 'Le panier est vide.');
        return;
    }

    try {
        const data = await api('/api/checkout', {
            method: 'POST',
            body: JSON.stringify({ items: store.cart, discount_percent: store.discount })
        });
        store.cart = [];
        saveCart();
        window.location.href = `/order?id=${data.order.id}`;
    } catch (error) {
        showMessage('cart-message', error.message);
    }
}

async function loadOrders() {
    const body = document.getElementById('orders-body');
    if (!body) return;

    try {
        const data = await api('/api/orders');
        body.innerHTML = data.orders.map(order => `
            <tr>
                <td>${escapeHtml(order.reference)}</td>
                <td>${escapeHtml(order.created_at)}</td>
                <td>${escapeHtml(order.status)}</td>
                <td>${formatPrice(order.total_cents)}</td>
                <td><a class="button" href="/order?id=${order.id}">Ouvrir</a></td>
            </tr>
        `).join('');
    } catch (error) {
        showMessage('orders-message', error.message);
    }
}

async function loadManagerOrders() {
    const body = document.getElementById('manager-orders-body');
    if (!body) return;

    try {
        const data = await api('/api/manager/orders');
        body.innerHTML = data.orders.map(order => `
            <tr>
                <td>${escapeHtml(order.reference)}</td>
                <td>${escapeHtml(order.created_at)}</td>
                <td>${escapeHtml(`${order.first_name} ${order.last_name}`)}</td>
                <td>${formatPrice(order.discounted_subtotal_cents)}</td>
                <td>${formatPrice(order.tax_cents)}</td>
                <td>${formatPrice(order.total_cents)}</td>
                <td><a class="button" href="/order?id=${order.id}">Ouvrir</a></td>
            </tr>
        `).join('');
    } catch (error) {
        showMessage('manager-message', error.message);
    }
}

async function loadOrderDetail() {
    const panel = document.getElementById('order-detail');
    if (!panel) return;

    const id = new URLSearchParams(window.location.search).get('id');
    try {
        const data = await api(`/api/orders/show?id=${encodeURIComponent(id || '')}`);
        const order = data.order;
        document.getElementById('order-heading').textContent = `Commande ${order.reference}`;
        document.getElementById('order-reference').textContent = order.reference;
        document.getElementById('order-date').textContent = order.created_at;
        document.getElementById('order-customer').textContent = `${order.first_name} ${order.last_name}`;
        document.getElementById('order-status').textContent = order.status;
        document.getElementById('order-discounted').textContent = formatPrice(order.discounted_subtotal_cents);
        document.getElementById('order-tax').textContent = formatPrice(order.tax_cents);
        document.getElementById('order-total').textContent = formatPrice(order.total_cents);
        document.getElementById('order-items').innerHTML = order.items.map(item => `
            <tr>
                <td>${escapeHtml(item.product_name)}</td>
                <td>${formatPrice(item.unit_price_cents)}</td>
                <td>${item.quantity}</td>
                <td>${formatPrice(item.line_total_cents)}</td>
            </tr>
        `).join('');
        panel.classList.remove('hidden');
    } catch (error) {
        showMessage('order-message', error.message);
    }
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[character]);
}

document.addEventListener('DOMContentLoaded', () => {
    updateNavigation();

    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', () => addToCart(JSON.parse(button.dataset.product)));
    });

    const searchInput = document.getElementById('catalog-search');
    const sortSelect = document.getElementById('catalog-sort');
    if (searchInput) searchInput.addEventListener('input', searchCatalog);
    if (sortSelect) sortSelect.addEventListener('change', searchCatalog);

    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async event => {
            event.preventDefault();
            const form = new FormData(loginForm);
            try {
                const data = await api('/api/login', {
                    method: 'POST',
                    body: JSON.stringify({
                        email: form.get('email'),
                        password: form.get('password')
                    })
                });
                store.token = data.token;
                store.user = data.user;
                localStorage.setItem('techstore_token', store.token);
                localStorage.setItem('techstore_user', JSON.stringify(store.user));
                window.location.href = '/catalog';
            } catch (error) {
                showMessage('login-message', error.message);
            }
        });
    }

    const logoutButton = document.querySelector('.logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', () => {
            localStorage.removeItem('techstore_token');
            localStorage.removeItem('techstore_user');
            window.location.href = '/catalog';
        });
    }

    const discount = document.getElementById('discount-percent');
    if (discount) {
        discount.addEventListener('change', () => {
            store.discount = Number(discount.value);
            saveCart();
            refreshTotals();
        });
    }

    const checkoutButton = document.getElementById('checkout-button');
    if (checkoutButton) checkoutButton.addEventListener('click', checkout);

    renderCart();
    loadOrders();
    loadManagerOrders();
    loadOrderDetail();
});
