<?php

$routes->get('/', 'Home::index');
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->post('login', 'AuthController::login');
    $routes->get('transactions', 'TransactionController::list');
    $routes->get('transactions/(:num)', 'TransactionController::detail/$1');
    $routes->delete('transactions/(:num)', 'TransactionController::delete/$1');
    $routes->post('transactions', 'TransactionController::create');
    $routes->post('apriori/run', 'AprioriController::run');
    $routes->get('apriori/itemsets', 'AprioriController::itemsets');
    $routes->get('apriori/rules', 'AprioriController::rules');
    $routes->get('report', 'ReportController::list');
    $routes->get('report/(:num)', 'ReportController::detail/$1');

});
$routes->get('login', 'AuthController::loginView', ['namespace' => 'App\Controllers\Api']);
$routes->get('halaman-utama', 'HomeController::homeView', ['namespace' => 'App\Controllers\Api']);
$routes->get('transaksi', 'TransactionController::transactionView', ['namespace' => 'App\Controllers\Api']);
$routes->get('transaksi/detail', 'TransactionController::detailView', ['namespace' => 'App\Controllers\Api']);
$routes->get('apriori', 'AprioriController::aprioriView', ['namespace' => 'App\Controllers\Api']);
$routes->get('report', 'ReportController::reportView', ['namespace' => 'App\Controllers\Api']);
$routes->get('report/detail', 'ReportController::detailView', ['namespace' => 'App\Controllers\Api']);
