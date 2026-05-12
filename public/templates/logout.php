<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();
Auth::logout();
Redirect::redirect('login.php');
