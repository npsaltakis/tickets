<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('my-events', 'ReportController::myEvents');
$routes->get('report', 'ReportController::report');
$routes->get('gdpr', 'PagesController::gdpr');
$routes->get('privacy-policy', 'PagesController::privacy');
$routes->get('terms', 'PagesController::terms');
$routes->get('events/create', 'EventAdminController::create');
$routes->post('events', 'EventAdminController::store');
$routes->get('events/(:segment)/edit', 'EventAdminController::edit/$1');
$routes->post('events/(:segment)/update', 'EventAdminController::update/$1');
$routes->post('events/(:segment)/book', 'BookingController::book/$1');
$routes->post('events/(:segment)/paypal/order', 'BookingController::createDonationOrder/$1');
$routes->post('events/(:segment)/paypal/capture', 'BookingController::captureDonationOrder/$1');
$routes->get('events/feed', 'Home::eventsFeed');
$routes->get('events/(:segment)', 'Home::show/$1');

$routes->get('login', 'LoginController::index');
$routes->post('login', 'LoginController::authenticate');
$routes->get('logout', 'LoginController::logout');
$routes->get('register', 'LoginController::register');
$routes->post('register', 'LoginController::storeRegister');
$routes->get('verify-email', 'LoginController::verifyEmail');
$routes->get('lost-password', 'LoginController::lostPassword');
$routes->post('lost-password', 'LoginController::sendResetLink');
$routes->get('reset-password', 'LoginController::resetPasswordForm');
$routes->post('reset-password', 'LoginController::updatePasswordWithToken');
