<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('events/(:segment)', 'Home::show/$1');

$routes->get('login', 'LoginController::index');
$routes->post('login', 'LoginController::authenticate');
$routes->get('logout', 'LoginController::logout');
$routes->get('register', 'LoginController::register');
$routes->post('register', 'LoginController::storeRegister');
$routes->get('lost-password', 'LoginController::lostPassword');
$routes->post('lost-password', 'LoginController::sendResetLink');
$routes->get('reset-password', 'LoginController::resetPasswordForm');
$routes->post('reset-password', 'LoginController::updatePasswordWithToken');
