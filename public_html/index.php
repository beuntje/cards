<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Cards\Auth;
use Cards\Database;
use Cards\Router;
use Cards\Controller\AuthController;
use Cards\Controller\CardController;
use Cards\Controller\ApiController;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$_ENV['TWIG_PATH'] = __DIR__ . '/../templates';

Database::migrate();

$method = $_SERVER['REQUEST_METHOD'];
$path   = strtok($_SERVER['REQUEST_URI'], '?');
$appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');

$publicRoutes = ['/login', '/register', '/sw.js', '/manifest.json'];
$user = Auth::getUser();

if (!$user && !in_array($path, $publicRoutes)) {
    header('Location: /login');
    exit;
}

$router = new Router();
$auth = new AuthController($appUrl);

// --- Auth routes ---
$router->get('/register', [$auth, 'showRegister']);
$router->post('/register', [$auth, 'register']);
$router->get('/login', [$auth, 'showLogin']);
$router->post('/login', [$auth, 'login']);
$router->get('/logout', [$auth, 'logout']);

// --- Authenticated routes ---
if ($user) {
    $cards = new CardController($appUrl, $user);
    $api = new ApiController($user);

    // API
    $router->get('/api/logo-search', [$api, 'logoSearch']);
    $router->post('/api/logo-upload', [$api, 'logoUpload']);
    $router->get('/api/cards', [$api, 'cards']);
    $router->post('/api/cards/(\d+)/usage', [$api, 'recordUsage']);

    // Cards
    $router->get('/cards/create', [$cards, 'create']);
    $router->post('/cards/create', [$cards, 'store']);
    $router->get('/cards/(\d+)/edit', [$cards, 'edit']);
    $router->post('/cards/(\d+)/edit', [$cards, 'update']);
    $router->post('/cards/(\d+)/delete', [$cards, 'delete']);
    $router->get('/cards/(\d+)/use', [$cards, 'show']);

    // Homepage
    $router->get('/', [$cards, 'index']);
}

$router->dispatch($method, $path);
