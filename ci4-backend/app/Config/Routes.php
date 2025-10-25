<?php

use CodeIgniter\Router\RouteCollection;

// Public (no auth)
$routes->get('/', 'AuthController::loginView', ['namespace' => 'App\Controllers\Api']);
$routes->get('login', 'AuthController::loginView', ['namespace' => 'App\Controllers\Api']);
$routes->post('api/login', 'AuthController::login', ['namespace' => 'App\Controllers\Api']);

// Protected (with auth filter)
$routes->group('', ['namespace' => 'App\Controllers\Api', 'filter' => 'auth'], function($routes) {
    // View
    $routes->get('halaman-utama', 'HomeController::homeView');
    $routes->get('transaksi', 'TransactionController::transactionView');
    $routes->get('transaksi/detail', 'TransactionController::detailView');
    $routes->get('transaksi/upload', 'TransactionController::uploadView');
    $routes->get('apriori', 'AprioriController::aprioriView');
    $routes->get('report', 'ReportController::reportView');
    $routes->get('report/main-info/(:num)', 'ReportController::mainInfoView/$1');
    $routes->get('report/itemset/(:num)','ReportController::itemsetView/$1');
    $routes->get('report/association-rule/(:num)','ReportController::associationRuleView/$1');
    $routes->get('report/lift-ratio/(:num)','ReportController::liftRatioView/$1');
    $routes->get('report/kesimpulan/(:num)','ReportController::kesimpulanView/$1');

    // API
    $routes->get('api/summary', 'HomeController::summary');
    $routes->get('api/summary/top-products', 'HomeController::topProducts');
    $routes->get('api/transactions', 'TransactionController::list');
    $routes->get('api/transactions/(:num)', 'TransactionController::detail/$1');
    $routes->delete('api/transactions/(:num)', 'TransactionController::delete/$1');
    $routes->post('api/transactions/upload', 'TransactionController::upload');
    $routes->post('api/transactions/save', 'TransactionController::save');
    $routes->post('api/apriori/run', 'AprioriController::run');
    $routes->get('api/report', 'ReportController::list');
    $routes->get('api/report/(:num)', 'ReportController::detail/$1');
    $routes->delete('api/report/delete/(:num)', 'ReportController::delete/$1');
    $routes->get('api/report/itemset/(:num)', 'ReportController::itemset/$1');
    $routes->get('api/report/association/(:num)', 'ReportController::association/$1');
    $routes->get('api/report/lift/(:num)', 'ReportController::lift/$1');
    $routes->get('api/report/kesimpulan/(:num)', 'ReportController::kesimpulan/$1');
    $routes->get('api/report/download-report/(:num)', 'ReportController::downloadReport/$1');
    $routes->post('api/logout', 'AuthController::logout');
});
