<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'TechStore Legacy') ?> - TechStore Legacy</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/catalog">TechStore Legacy</a>
    <nav class="nav">
        <a href="/catalog">Catalogue</a>
        <a href="/cart">Panier</a>
        <a href="/orders">Mes commandes</a>
        <a class="manager-link" href="/manager/orders">Suivi magasin</a>
        <a class="auth-link" href="/login">Connexion</a>
        <button class="logout-button hidden" type="button">Déconnexion</button>
    </nav>
</header>
<main class="page">
    <?php require $viewFile; ?>
</main>
<script src="/assets/js/app.js"></script>
</body>
</html>
