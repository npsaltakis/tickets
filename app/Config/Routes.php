<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('my-events', 'ReportController::myEvents');
$routes->get('report', 'ReportController::report');
$routes->get('check-in', 'ReportController::checkIn');
$routes->get('check-in/export', 'ReportController::exportCheckIn');
$routes->post('check-in', 'ReportController::processCheckIn');
$routes->get('admin-logs', 'AdminLogController::index');
$routes->post('admin-logs/clear', 'AdminLogController::clear');
$routes->post('admin/test-email', 'AdminToolsController::sendTestEmail');
$routes->get('users', 'UserAdminController::index');
$routes->get('users/create', 'UserAdminController::create');
$routes->post('users', 'UserAdminController::store');
$routes->get('users/(:num)/edit', 'UserAdminController::edit/$1');
$routes->post('users/(:num)/update', 'UserAdminController::update/$1');
$routes->post('users/(:num)/block', 'UserAdminController::block/$1');
$routes->post('users/(:num)/unblock', 'UserAdminController::unblock/$1');
$routes->post('users/(:num)/delete', 'UserAdminController::delete/$1');
$routes->post('users/(:num)/resend-verification', 'UserAdminController::resendVerification/$1');
$routes->get('gdpr', 'PagesController::gdpr');
$routes->get('privacy-policy', 'PagesController::privacy');
$routes->get('terms', 'PagesController::terms');
$routes->get('events/create', 'EventAdminController::create');
$routes->post('events', 'EventAdminController::store');
$routes->get('events/(:segment)/edit', 'EventAdminController::edit/$1');
$routes->post('events/(:segment)/update', 'EventAdminController::update/$1');
$routes->post('events/(:segment)/duplicate', 'EventAdminController::duplicate/$1');
$routes->post('events/(:segment)/delete', 'EventAdminController::delete/$1');
$routes->get('events/deleted', 'EventAdminController::deleted');
$routes->post('events/(:segment)/restore', 'EventAdminController::restore/$1');
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
