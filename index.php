<?php
require_once __DIR__ . '/app/core/App.php';
App::init();

if (Auth::check() && !Auth::isCustomer()) {
    Redirect::redirect('public/templates/dashboard.php');
} else {
    Redirect::redirect('public/templates/restaurants.php');
}
