<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('health', static function () {
    return service('response')->setJSON(['status' => 'ok', 'service' => 'crm-rest-api']);
});

$routes->group('api', static function (RouteCollection $routes) {
    $routes->group('auth', static function (RouteCollection $routes) {
        $routes->post('register', 'AuthController::register');
        $routes->post('login', 'AuthController::login');

        $routes->group('', ['filter' => 'tokenAuth'], static function (RouteCollection $routes) {
            $routes->post('logout', 'AuthController::logout');
            $routes->get('me', 'AuthController::me');
        });
    });

    $routes->group('', ['filter' => 'tokenAuth'], static function (RouteCollection $routes) {
        $routes->get('customers', 'CustomerController::index');
        $routes->post('customers', 'CustomerController::create');
        $routes->get('customers/(:num)', 'CustomerController::show/$1');
        $routes->put('customers/(:num)', 'CustomerController::update/$1');
        $routes->delete('customers/(:num)', 'CustomerController::delete/$1');

        $routes->get('customers/(:num)/notes', 'NoteController::index/$1');
        $routes->post('customers/(:num)/notes', 'NoteController::create/$1');
    });
});
