<?php

$routes->get('/', 'Home::index');
//API
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
    $routes->get('report/itemset1/(:num)', 'ReportController::itemset1/$1');
    $routes->get('report/itemset2/(:num)', 'ReportController::itemset2/$1');
    $routes->get('report/itemset3/(:num)', 'ReportController::itemset3/$1');
    $routes->get('report/association-itemset2/(:num)', 'ReportController::associationItemset2/$1');
    $routes->get('report/association-itemset3/(:num)', 'ReportController::associationItemset3/$1');
    $routes->get('report/lift/(:num)', 'ReportController::lift/$1');
});
//VIEW
$routes->get('login', 'AuthController::loginView', ['namespace' => 'App\Controllers\Api']);
$routes->get('halaman-utama', 'HomeController::homeView', ['namespace' => 'App\Controllers\Api']);
$routes->get('transaksi', 'TransactionController::transactionView', ['namespace' => 'App\Controllers\Api']);
$routes->get('transaksi/detail', 'TransactionController::detailView', ['namespace' => 'App\Controllers\Api']);
$routes->get('apriori', 'AprioriController::aprioriView', ['namespace' => 'App\Controllers\Api']);
$routes->get('report', 'ReportController::reportView', ['namespace' => 'App\Controllers\Api']);
$routes->get('report/main-info/(:num)', 'ReportController::mainInfoView/$1', ['namespace' => 'App\Controllers\Api']);
$routes->get('report/itemset1/(:num)','ReportController::itemset1View/$1',['namespace' => 'App\Controllers\Api']);
$routes->get('report/itemset2/(:num)','ReportController::itemset2View/$1',['namespace' => 'App\Controllers\Api']);
$routes->get('report/itemset3/(:num)','ReportController::itemset3View/$1',['namespace' => 'App\Controllers\Api']);
$routes->get('report/association-rule/(:num)','ReportController::associationRule/$1',['namespace' => 'App\Controllers\Api']);
$routes->get('report/lift-ratio/(:num)','ReportController::liftRatio/$1',['namespace' => 'App\Controllers\Api']);