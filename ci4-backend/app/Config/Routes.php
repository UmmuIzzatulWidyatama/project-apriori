<?php

$routes->get('/', 'Home::index');
//API
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->post('login', 'AuthController::login');
    $routes->get('summary', 'HomeController::summary');
    $routes->get('summary/top-products', 'HomeController::topProducts');
    $routes->get('transactions', 'TransactionController::list');
    $routes->get('transactions/(:num)', 'TransactionController::detail/$1');
    $routes->delete('transactions/(:num)', 'TransactionController::delete/$1');
    $routes->post('transactions/upload', 'TransactionController::upload');
    $routes->post('transactions/save', 'TransactionController::save');
    $routes->post('apriori/run', 'AprioriController::run');
    $routes->get('report', 'ReportController::list');
    $routes->get('report/(:num)', 'ReportController::detail/$1');
    $routes->delete('report/delete/(:num)', 'ReportController::delete/$1');
    $routes->get('report/itemset/(:num)', 'ReportController::itemset/$1');
    $routes->get('report/association/(:num)', 'ReportController::association/$1');
    $routes->get('report/lift/(:num)', 'ReportController::lift/$1');
    $routes->get('report/kesimpulan/(:num)', 'ReportController::kesimpulan/$1');
    $routes->get('report/download-report/(:num)', 'ReportController::downloadReport/$1');
});
//VIEW
$routes->get('login', 'AuthController::loginView', ['namespace' => 'App\Controllers\Api']);
$routes->get('halaman-utama', 'HomeController::homeView', ['namespace' => 'App\Controllers\Api']);
$routes->get('transaksi', 'TransactionController::transactionView', ['namespace' => 'App\Controllers\Api']);
$routes->get('transaksi/detail', 'TransactionController::detailView', ['namespace' => 'App\Controllers\Api']);
$routes->get('transaksi/upload', 'TransactionController::uploadView', ['namespace' => 'App\Controllers\Api']);
$routes->get('apriori', 'AprioriController::aprioriView', ['namespace' => 'App\Controllers\Api']);
$routes->get('report', 'ReportController::reportView', ['namespace' => 'App\Controllers\Api']);
$routes->get('report/main-info/(:num)', 'ReportController::mainInfoView/$1', ['namespace' => 'App\Controllers\Api']);
$routes->get('report/itemset/(:num)','ReportController::itemsetView/$1',['namespace' => 'App\Controllers\Api']);
$routes->get('report/association-rule/(:num)','ReportController::associationRuleView/$1',['namespace' => 'App\Controllers\Api']);
$routes->get('report/lift-ratio/(:num)','ReportController::liftRatioView/$1',['namespace' => 'App\Controllers\Api']);
$routes->get('report/kesimpulan/(:num)','ReportController::kesimpulanView/$1',['namespace' => 'App\Controllers\Api']);
 