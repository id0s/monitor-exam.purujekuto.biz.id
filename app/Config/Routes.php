<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Dashboard::index');
$routes->get('api/check-jadwal', 'Dashboard::checkJadwal');
$routes->get('api/status-pc', 'Dashboard::statusPc');
$routes->post('api/set-jadwal', 'Dashboard::setJadwal');
$routes->post('api/deactivate-jadwal', 'Dashboard::deactivateJadwal');
$routes->post('api/delete-jadwal/(:num)', 'Dashboard::deleteJadwal/$1');
$routes->post('api/reset-status', 'Dashboard::resetStatus');
$routes->post('api/reset-status-lab/(:any)', 'Dashboard::resetStatusLab/$1');
$routes->post('api/wake-on-lan', 'Dashboard::wakeOnLan');
$routes->post('api/update-pc', 'Dashboard::updatePc');
$routes->post('api/update-vlan-config', 'Dashboard::updateVlanConfig');
$routes->post('api/delete-pc', 'Dashboard::deletePc');
$routes->post('api/delete-all-pcs', 'Dashboard::deleteAllPcs');
$routes->get('start', 'Dashboard::startUjian');
