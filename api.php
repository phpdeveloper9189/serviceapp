<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

const STORE_PATH = __DIR__ . '/data/store.json';

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_PRETTY_PRINT);
    exit;
}

function loadStore(): array
{
    if (!file_exists(STORE_PATH)) {
        return [
            'counters' => [
                'user' => 1,
                'provider' => 1,
                'category' => 1,
                'service' => 1,
                'booking' => 1,
            ],
            'users' => [],
            'providers' => [],
            'categories' => [],
            'services' => [],
            'coupons' => [],
            'bookings' => [],
        ];
    }

    $decoded = json_decode((string) file_get_contents(STORE_PATH), true);
    if (!is_array($decoded)) {
        return loadStore();
    }

    return $decoded;
}

function saveStore(array $store): void
{
    if (!is_dir(dirname(STORE_PATH))) {
        mkdir(dirname(STORE_PATH), 0777, true);
    }

    file_put_contents(STORE_PATH, json_encode($store, JSON_PRETTY_PRINT));
}

function nextId(array &$store, string $scope): int
{
    $id = (int) $store['counters'][$scope];
    $store['counters'][$scope]++;

    return $id;
}

function body(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

$action = $_GET['action'] ?? '';
$store = loadStore();
$data = body();

switch ($action) {
    case 'health':
        jsonResponse(['ok' => true, 'app' => 'service-delivery-cordova']);
        break;

    case 'signup_user':
        if (empty($data['name']) || empty($data['phone'])) {
            jsonResponse(['ok' => false, 'error' => 'name and phone are required'], 422);
        }
        $id = nextId($store, 'user');
        $store['users'][(string) $id] = [
            'id' => $id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'blocked' => false,
            'wallet_balance' => 0,
        ];
        saveStore($store);
        jsonResponse(['ok' => true, 'user' => $store['users'][(string) $id]]);
        break;

    case 'add_provider':
        if (empty($data['name']) || empty($data['phone'])) {
            jsonResponse(['ok' => false, 'error' => 'name and phone are required'], 422);
        }
        $id = nextId($store, 'provider');
        $store['providers'][(string) $id] = [
            'id' => $id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'status' => 'pending',
            'docs_verified' => false,
            'availability' => true,
            'earnings' => 0,
        ];
        saveStore($store);
        jsonResponse(['ok' => true, 'provider' => $store['providers'][(string) $id]]);
        break;

    case 'approve_provider':
        $providerId = (string) ($data['provider_id'] ?? '');
        if (!isset($store['providers'][$providerId])) {
            jsonResponse(['ok' => false, 'error' => 'provider not found'], 404);
        }
        $store['providers'][$providerId]['status'] = 'approved';
        $store['providers'][$providerId]['docs_verified'] = true;
        saveStore($store);
        jsonResponse(['ok' => true, 'provider' => $store['providers'][$providerId]]);
        break;

    case 'add_category':
        if (empty($data['name'])) {
            jsonResponse(['ok' => false, 'error' => 'name is required'], 422);
        }
        $id = nextId($store, 'category');
        $store['categories'][(string) $id] = [
            'id' => $id,
            'name' => $data['name'],
            'enabled' => true,
        ];
        saveStore($store);
        jsonResponse(['ok' => true, 'category' => $store['categories'][(string) $id]]);
        break;

    case 'add_service':
        $categoryId = (string) ($data['category_id'] ?? '');
        if (!isset($store['categories'][$categoryId])) {
            jsonResponse(['ok' => false, 'error' => 'category not found'], 404);
        }
        if (empty($data['name']) || !isset($data['price'])) {
            jsonResponse(['ok' => false, 'error' => 'name and price are required'], 422);
        }
        $id = nextId($store, 'service');
        $store['services'][(string) $id] = [
            'id' => $id,
            'category_id' => (int) $categoryId,
            'name' => $data['name'],
            'price' => (float) $data['price'],
            'enabled' => true,
        ];
        saveStore($store);
        jsonResponse(['ok' => true, 'service' => $store['services'][(string) $id]]);
        break;

    case 'add_coupon':
        if (empty($data['code']) || !isset($data['discount_percent'])) {
            jsonResponse(['ok' => false, 'error' => 'code and discount_percent are required'], 422);
        }
        $code = strtoupper((string) $data['code']);
        $store['coupons'][$code] = [
            'code' => $code,
            'discount_percent' => (int) $data['discount_percent'],
            'enabled' => true,
        ];
        saveStore($store);
        jsonResponse(['ok' => true, 'coupon' => $store['coupons'][$code]]);
        break;

    case 'home_data':
        jsonResponse([
            'ok' => true,
            'categories' => array_values($store['categories']),
            'featured_services' => array_values($store['services']),
            'banner' => ['Welcome', 'Book trusted professionals'],
        ]);
        break;

    case 'create_booking':
        $userId = (string) ($data['user_id'] ?? '');
        $serviceId = (string) ($data['service_id'] ?? '');

        if (!isset($store['users'][$userId])) {
            jsonResponse(['ok' => false, 'error' => 'user not found'], 404);
        }
        if (!isset($store['services'][$serviceId])) {
            jsonResponse(['ok' => false, 'error' => 'service not found'], 404);
        }
        if (empty($data['address']) || empty($data['slot'])) {
            jsonResponse(['ok' => false, 'error' => 'address and slot are required'], 422);
        }

        $price = (float) $store['services'][$serviceId]['price'];
        $couponCode = strtoupper((string) ($data['coupon_code'] ?? ''));
        if ($couponCode && isset($store['coupons'][$couponCode])) {
            $discount = (int) $store['coupons'][$couponCode]['discount_percent'];
            $price = $price * (100 - $discount) / 100;
        }

        $id = nextId($store, 'booking');
        $store['bookings'][(string) $id] = [
            'id' => $id,
            'user_id' => (int) $userId,
            'service_id' => (int) $serviceId,
            'provider_id' => null,
            'address' => $data['address'],
            'slot' => $data['slot'],
            'status' => 'requested',
            'price' => round($price, 2),
            'coupon_code' => $couponCode ?: null,
        ];
        saveStore($store);
        jsonResponse(['ok' => true, 'booking' => $store['bookings'][(string) $id]]);
        break;

    case 'assign_booking':
        $bookingId = (string) ($data['booking_id'] ?? '');
        $providerId = (string) ($data['provider_id'] ?? '');

        if (!isset($store['bookings'][$bookingId])) {
            jsonResponse(['ok' => false, 'error' => 'booking not found'], 404);
        }
        if (!isset($store['providers'][$providerId])) {
            jsonResponse(['ok' => false, 'error' => 'provider not found'], 404);
        }
        if ($store['providers'][$providerId]['status'] !== 'approved') {
            jsonResponse(['ok' => false, 'error' => 'provider must be approved'], 422);
        }

        $store['bookings'][$bookingId]['provider_id'] = (int) $providerId;
        $store['bookings'][$bookingId]['status'] = 'assigned';
        saveStore($store);
        jsonResponse(['ok' => true, 'booking' => $store['bookings'][$bookingId]]);
        break;

    case 'partner_update_status':
        $bookingId = (string) ($data['booking_id'] ?? '');
        $providerId = (string) ($data['provider_id'] ?? '');
        $status = (string) ($data['status'] ?? '');

        if (!isset($store['bookings'][$bookingId])) {
            jsonResponse(['ok' => false, 'error' => 'booking not found'], 404);
        }
        if (!isset($store['providers'][$providerId])) {
            jsonResponse(['ok' => false, 'error' => 'provider not found'], 404);
        }
        if ((int) $store['bookings'][$bookingId]['provider_id'] !== (int) $providerId) {
            jsonResponse(['ok' => false, 'error' => 'booking not assigned to provider'], 422);
        }

        $allowed = ['accepted', 'started', 'completed'];
        if (!in_array($status, $allowed, true)) {
            jsonResponse(['ok' => false, 'error' => 'invalid status'], 422);
        }

        $store['bookings'][$bookingId]['status'] = $status;

        if ($status === 'completed') {
            $store['providers'][$providerId]['earnings'] += (float) $store['bookings'][$bookingId]['price'];
        }

        saveStore($store);
        jsonResponse(['ok' => true, 'booking' => $store['bookings'][$bookingId]]);
        break;

    case 'list_bookings':
        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
        $providerId = isset($_GET['provider_id']) ? (int) $_GET['provider_id'] : null;

        $bookings = array_values($store['bookings']);
        if ($userId !== null) {
            $bookings = array_values(array_filter($bookings, fn ($b) => (int) $b['user_id'] === $userId));
        }
        if ($providerId !== null) {
            $bookings = array_values(array_filter($bookings, fn ($b) => (int) $b['provider_id'] === $providerId));
        }

        jsonResponse(['ok' => true, 'bookings' => $bookings]);
        break;

    case 'revenue_report':
        $completed = array_values(array_filter($store['bookings'], fn ($b) => $b['status'] === 'completed'));
        $revenue = array_reduce(
            $completed,
            fn ($carry, $item) => $carry + (float) $item['price'],
            0
        );

        jsonResponse([
            'ok' => true,
            'completed_bookings' => count($completed),
            'total_revenue' => round($revenue, 2),
        ]);
        break;

    default:
        jsonResponse([
            'ok' => false,
            'error' => 'Unknown action',
            'available_actions' => [
                'health', 'signup_user', 'add_provider', 'approve_provider', 'add_category',
                'add_service', 'add_coupon', 'home_data', 'create_booking', 'assign_booking',
                'partner_update_status', 'list_bookings', 'revenue_report',
            ],
        ], 404);
}
