<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->post('transactions', 'TransactionController::create');
    $routes->post('apriori/run', 'AprioriController::run');
    $routes->get('apriori/itemsets', 'AprioriController::itemsets');
    $routes->get('apriori/rules', 'AprioriController::rules');
    $routes->post('login', 'AuthController::login');
});
