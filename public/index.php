<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use TechStore\Auth\AuthHelper;
use TechStore\Auth\JwtService;
use TechStore\Controller\AuthController;
use TechStore\Controller\CartController;
use TechStore\Controller\ManagerController;
use TechStore\Controller\OrderController;
use TechStore\Controller\PageController;
use TechStore\Controller\ProductController;
use TechStore\Database\Connection;
use TechStore\Repository\OrderRepository;
use TechStore\Repository\ProductRepository;
use TechStore\Repository\UserRepository;
use TechStore\Service\CartCalculator;

$pdo = Connection::get();
$products = new ProductRepository($pdo);
$users = new UserRepository($pdo);
$orders = new OrderRepository($pdo);
$jwt = new JwtService();
$auth = new AuthHelper($jwt, $users);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($path === '/' || $path === '/catalog') {
        (new PageController($products))->catalog();
    } elseif ($path === '/login') {
        (new PageController($products))->login();
    } elseif ($path === '/cart') {
        (new PageController($products))->cart();
    } elseif ($path === '/orders') {
        (new PageController($products))->orders();
    } elseif ($path === '/order') {
        (new PageController($products))->orderDetail();
    } elseif ($path === '/manager/orders') {
        (new PageController($products))->managerOrders();
    } elseif ($path === '/api/products/search') {
        (new ProductController($products))->search();
    } elseif ($path === '/api/products/show') {
        (new ProductController($products))->show();
    } elseif ($path === '/api/login' && $method === 'POST') {
        (new AuthController($users, $jwt, $auth))->login();
    } elseif ($path === '/api/me') {
        (new AuthController($users, $jwt, $auth))->me();
    } elseif ($path === '/api/cart/totals' && $method === 'POST') {
        (new CartController($auth, $products, $orders, new CartCalculator()))->totals();
    } elseif ($path === '/api/checkout' && $method === 'POST') {
        (new CartController($auth, $products, $orders, new CartCalculator()))->checkout();
    } elseif ($path === '/api/orders') {
        (new OrderController($auth, $orders))->mine();
    } elseif ($path === '/api/orders/show') {
        (new OrderController($auth, $orders))->show();
    } elseif ($path === '/api/manager/orders') {
        (new ManagerController($auth, $orders))->orders();
    } else {
        http_response_code(404);
        require project_path('views/404.php');
    }
} catch (Throwable) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Erreur serveur</h1><p>Une erreur est survenue.</p>';
}

