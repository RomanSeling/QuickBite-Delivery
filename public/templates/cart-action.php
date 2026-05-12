<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$cartCount = function(): int {
    return array_sum(array_column($_SESSION['cart']['items'] ?? [], 'qty'));
};

switch ($action) {

    case 'add':
        $id               = (string)($_POST['id'] ?? '');
        $name             = trim($_POST['name'] ?? '');
        $price            = (float)($_POST['price'] ?? 0);
        $restauracia_id   = (int)($_POST['restauracia_id'] ?? 0);
        $restauracia_nazov = trim($_POST['restauracia_nazov'] ?? '');

        if (!$id || !$name || $price <= 0 || !$restauracia_id) {
            echo json_encode(['ok' => false, 'msg' => 'Neplatné údaje']);
            exit;
        }

        // Ak je košík pre inú reštauráciu, vymazať
        if (!empty($_SESSION['cart']) && (int)$_SESSION['cart']['restauracia_id'] !== $restauracia_id) {
            $_SESSION['cart'] = null;
        }

        if (empty($_SESSION['cart'])) {
            $_SESSION['cart'] = [
                'restauracia_id'    => $restauracia_id,
                'restauracia_nazov' => $restauracia_nazov,
                'items'             => [],
            ];
        }

        if (isset($_SESSION['cart']['items'][$id])) {
            $_SESSION['cart']['items'][$id]['qty']++;
        } else {
            $_SESSION['cart']['items'][$id] = [
                'id'             => $id,
                'name'           => $name,
                'price'          => $price,
                'qty'            => 1,
                'restauracia_id' => $restauracia_id,
            ];
        }

        echo json_encode(['ok' => true, 'count' => $cartCount()]);
        break;

    case 'update':
        $id  = (string)($_POST['id'] ?? '');
        $qty = (int)($_POST['qty'] ?? 0);
        if ($id && isset($_SESSION['cart']['items'][$id])) {
            if ($qty <= 0) {
                unset($_SESSION['cart']['items'][$id]);
                if (empty($_SESSION['cart']['items'])) {
                    $_SESSION['cart'] = null;
                }
            } else {
                $_SESSION['cart']['items'][$id]['qty'] = $qty;
            }
        }
        echo json_encode(['ok' => true, 'count' => $cartCount()]);
        break;

    case 'remove':
        $id = (string)($_POST['id'] ?? '');
        if ($id && isset($_SESSION['cart']['items'][$id])) {
            unset($_SESSION['cart']['items'][$id]);
            if (empty($_SESSION['cart']['items'])) {
                $_SESSION['cart'] = null;
            }
        }
        echo json_encode(['ok' => true, 'count' => $cartCount()]);
        break;

    case 'clear':
        $_SESSION['cart'] = null;
        echo json_encode(['ok' => true, 'count' => 0]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Neznáma akcia']);
}
