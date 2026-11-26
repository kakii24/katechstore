<?php
/**
 * KA Tech — API Gateway & Proxy
 * Permet au site déployé sur Hostinger de communiquer avec la base de données,
 * gère le stockage des médias, les images multiples, le calcul de profit en Caisse,
 * et orchestre le système d'email marketing avec Brevo et Google Gemini.
 */

require_once __DIR__ . '/marketing.php';

$targetHost = 'bisque-shrew-404215.hostingersite.com';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$pos = strpos($requestUri, '/api/v1');
$apiPath = ($pos !== false) ? substr($requestUri, $pos) : '/api/v1';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 1. Route Désabonnement Newsletter : /unsubscribe?token=...
if (strpos($requestUri, '/unsubscribe') !== false) {
    $token = $_GET['token'] ?? '';
    KaTechMarketing::unsubscribe($token);
    exit;
}

// 2. Route de Test Email Marketing : /api/test-marketing-email
if (strpos($requestUri, 'test-marketing-email') !== false) {
    header('Content-Type: application/json; charset=utf-8');
    $targetEmail = $_GET['email'] ?? 'kaa.tech.48@gmail.com';
    $productData = [
        'type' => $_GET['type'] ?? 'new_item',
        'title' => $_GET['title'] ?? 'HP PROBOOK I5 8EM GEN — 16GB RAM / 512GB SSD',
        'description' => $_GET['description'] ?? 'PC Portable Professionnel reconditionné à neuf, processeur Intel Core i5 8ème Génération, écran 14 pouces Full HD, autonomie longue durée, parfait pour le travail et la bureautique.',
        'imageUrl' => $_GET['imageUrl'] ?? 'https://res.cloudinary.com/wt1yutck/image/upload/v1793961184/probook_i58th.jpg',
        'oldPrice' => !empty($_GET['oldPrice']) ? (float)$_GET['oldPrice'] : 58000,
        'newPrice' => !empty($_GET['newPrice']) ? (float)$_GET['newPrice'] : 52000,
        'productUrl' => $_GET['productUrl'] ?? 'https://katechstore.net/products/probook8th'
    ];
    $gen = KaTechMarketing::generateEmailWithGemini($productData);
    $unsubUrl = "https://katechstore.net/unsubscribe?token=test_preview_token";
    $finalHtml = str_replace('{{unsubscribeUrl}}', $unsubUrl, $gen['htmlContent']);
    $sendResult = KaTechMarketing::sendBrevoSmtp($targetEmail, 'Mokhtar - KA Tech', $gen['subject'], $finalHtml);
    echo json_encode([
        'success' => $sendResult['success'],
        'message' => $sendResult['success'] ? "Email de test marketing généré par Gemini et envoyé avec succès à {$targetEmail} via Brevo !" : "Erreur lors de l'envoi Brevo",
        'targetEmail' => $targetEmail,
        'gemini' => [
            'subject' => $gen['subject'],
            'htmlLength' => strlen($gen['htmlContent'])
        ],
        'brevo' => $sendResult
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Route de test Welcome Email : /api/test-welcome-email?email=...
if (strpos($requestUri, 'test-welcome-email') !== false) {
    header('Content-Type: application/json; charset=utf-8');
    $targetEmail = $_GET['email'] ?? 'kaa.tech.48@gmail.com';
    $targetName = $_GET['name'] ?? 'Mokhtar - KA Tech';
    $sendResult = KaTechMarketing::sendWelcomeEmail($targetEmail, $targetName);
    echo json_encode([
        'success' => $sendResult['success'],
        'message' => $sendResult['success'] ? "Email de bienvenue envoyé avec succès à {$targetEmail} !" : "Erreur envoi Brevo",
        'targetEmail' => $targetEmail,
        'brevo' => $sendResult
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. Traitement Mot de passe oublié : POST /api/v1/auth/forgot-password
if ((strpos($apiPath, '/auth/forgot-password') !== false || strpos($requestUri, 'forgot-password') !== false) && $method === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
    $email = $input['email'] ?? ($_POST['email'] ?? '');
    $res = KaTechMarketing::requestPasswordReset($email);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 5. Traitement Réinitialisation Mot de passe : POST /api/v1/auth/reset-password
if ((strpos($apiPath, '/auth/reset-password') !== false || strpos($requestUri, 'reset-password') !== false) && $method === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
    $token = $input['token'] ?? ($_POST['token'] ?? '');
    $password = $input['password'] ?? ($_POST['password'] ?? '');
    $res = KaTechMarketing::confirmPasswordReset($token, $password);
    if (empty($res['success']) && empty($res['ok'])) {
        http_response_code(422);
    }
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

define('KATECH_UPSTREAM_UA', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36');

/**
 * Récupère le cookie de session administrateur amont (bisque-shrew-404215.hostingersite.com).
 * Mis en cache pendant 4 heures dans un fichier temporaire.
 */
function getUpstreamAdminCookie($forceRefresh = false) {
    static $memoryCookie = null;
    if ($memoryCookie !== null && !$forceRefresh) return $memoryCookie;

    $targetHost = 'bisque-shrew-404215.hostingersite.com';
    $cacheFile = sys_get_temp_dir() . '/katech_admin_sid.txt';

    if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile) < 14400)) {
        $cached = trim(@file_get_contents($cacheFile));
        if (!empty($cached)) {
            $memoryCookie = $cached;
            return $cached;
        }
    }

    $ch = curl_init("https://{$targetHost}/api/v1/auth/login");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => 'mokhtar@katech.a',
        'password' => 'Alger2026**'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, KATECH_UPSTREAM_UA);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($code === 200) {
        $headers = substr($res, 0, $headerSize);
        if (preg_match('/Set-Cookie:\s*(katech_sid=[^;\r\n]+)/i', $headers, $m)) {
            $cookie = $m[1];
            @file_put_contents($cacheFile, $cookie);
            $memoryCookie = $cookie;
            return $cookie;
        }
    }

    return null;
}

// 6. Traitement Authentification Login : POST /api/v1/auth/login
if (strpos($apiPath, '/auth/login') !== false && $method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
    $loginEmail = trim(strtolower($input['email'] ?? ''));
    $loginPassword = $input['password'] ?? '';

    // A. Identifiants administrateur directs (admin / admin, admin@katech.dz, admin@katech.a, mokhtar@katech.a)
    $isAdminAuth = false;
    if (($loginEmail === 'admin' || $loginEmail === 'admin@katech.dz' || $loginEmail === 'admin@katech.a') && $loginPassword === 'admin') {
        $isAdminAuth = true;
    } elseif ($loginEmail === 'mokhtar@katech.a' && $loginPassword === 'Alger2026**') {
        $isAdminAuth = true;
    }

    if ($isAdminAuth) {
        $adminCookie = getUpstreamAdminCookie(true);
        if (!empty($adminCookie)) {
            header("Set-Cookie: {$adminCookie}; path=/; secure; HttpOnly; SameSite=Lax", false);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'data' => [
                'user' => [
                    'id' => 'usr_admin',
                    'name' => 'Administrateur KA Tech',
                    'email' => $loginEmail ?: 'admin@katech.dz',
                    'role' => 'owner'
                ],
                'expiresAt' => date('c', time() + 86400 * 30)
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // B. Vérifier si l'utilisateur existe dans la base de données locale
    $existingUser = KaTechMarketing::checkUserExists($loginEmail);
    if ($existingUser) {
        $localAuth = KaTechMarketing::verifyLocalUserLogin($loginEmail, $loginPassword);
        if ($localAuth !== null) {
            header('Content-Type: application/json; charset=utf-8');
            $adminCookie = getUpstreamAdminCookie();
            if (!empty($adminCookie)) {
                header("Set-Cookie: {$adminCookie}; path=/; secure; HttpOnly; SameSite=Lax", false);
            }
            echo json_encode($localAuth, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!empty($existingUser['password_hash'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'errorCode' => 'INVALID_CREDENTIALS',
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Identifiants invalides.'
                ],
                'message' => 'Identifiants invalides.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

/**
 * Récupère la liste de toutes les commandes de la base amont via session admin.
 * Met en cache temporairement (15s) pour garantir des performances optimales.
 */
function getUpstreamOrdersFromAdmin($forceRefresh = false) {
    static $memoryOrders = null;
    if ($memoryOrders !== null && !$forceRefresh) return $memoryOrders;

    $targetHost = 'bisque-shrew-404215.hostingersite.com';
    $ordersCacheFile = sys_get_temp_dir() . '/katech_admin_orders.json';

    if (!$forceRefresh && file_exists($ordersCacheFile) && (time() - filemtime($ordersCacheFile) < 15)) {
        $cachedJson = @file_get_contents($ordersCacheFile);
        $decoded = json_decode($cachedJson, true);
        if (is_array($decoded)) {
            $memoryOrders = $decoded;
            return $decoded;
        }
    }

    $fetchOrders = function($cookie) use ($targetHost) {
        $ch = curl_init("https://{$targetHost}/api/v1/orders?perPage=100");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, KATECH_UPSTREAM_UA);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200) {
            $data = json_decode($res, true);
            return $data['data']['items'] ?? ($data['items'] ?? ($data['data'] ?? []));
        }
        return false;
    };

    $adminCookie = getUpstreamAdminCookie($forceRefresh);
    $orders = false;
    if (!empty($adminCookie)) {
        $orders = $fetchOrders($adminCookie);
    }

    if ($orders === false) {
        $adminCookie = getUpstreamAdminCookie(true);
        if (!empty($adminCookie)) {
            $orders = $fetchOrders($adminCookie);
        }
    }

    $finalOrders = is_array($orders) ? $orders : [];
    if (!empty($finalOrders)) {
        @file_put_contents($ordersCacheFile, json_encode($finalOrders));
    }
    $memoryOrders = $finalOrders;
    return $finalOrders;
}

// 7. Route Synchronisation Commandes Client : GET /api/v1/me/orders
if (strpos($apiPath, '/me/orders') !== false && $method === 'GET') {
    header('Content-Type: application/json; charset=utf-8');

    // Tenter d'abord la requête upstream avec le cookie de session du client
    if (!empty($_SERVER['HTTP_COOKIE'])) {
        $ch = curl_init("https://{$targetHost}/api/v1/me/orders");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $upResp = curl_exec($ch);
        $upCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($upCode === 200 && !empty($upResp)) {
            $parsed = json_decode($upResp, true);
            $items = $parsed['data'] ?? ($parsed['items'] ?? null);
            if (is_array($items) && count($items) > 0) {
                echo $upResp;
                exit;
            }
        }
    }

    // Récupération dynamique selon l'email ou l'ID transmis
    $targetEmail = strtolower(trim($_GET['email'] ?? ($_GET['customerEmail'] ?? '')));
    $targetId = trim($_GET['userId'] ?? ($_GET['customerId'] ?? ''));

    $allOrders = getUpstreamOrdersFromAdmin();
    $matched = [];
    if (!empty($targetEmail) || !empty($targetId)) {
        foreach ($allOrders as $o) {
            $oe = strtolower(trim($o['customerEmail'] ?? ($o['email'] ?? ($o['customer']['email'] ?? ''))));
            $oi = trim($o['customerId'] ?? ($o['customer_id'] ?? ''));
            if (($targetEmail && $oe && $oe === $targetEmail) || ($targetId && $oi && $oi === $targetId)) {
                $matched[] = $o;
            }
        }
    }

    echo json_encode([
        'data' => $matched,
        'items' => $matched,
        'total' => count($matched)
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle direct file upload for /api/v1/media
if (strpos($apiPath, '/api/v1/media') === 0 && $method === 'POST' && !empty($_FILES)) {
    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
    }

    $fileList = [];
    foreach ($_FILES as $key => $fileInfo) {
        if (is_array($fileInfo['name'])) {
            for ($idx = 0; $idx < count($fileInfo['name']); $idx++) {
                if ($fileInfo['error'][$idx] === UPLOAD_ERR_OK) {
                    $fileList[] = [
                        'name' => $fileInfo['name'][$idx],
                        'tmp_name' => $fileInfo['tmp_name'][$idx],
                        'type' => $fileInfo['type'][$idx],
                        'size' => $fileInfo['size'][$idx]
                    ];
                }
            }
        } else {
            if ($fileInfo['error'] === UPLOAD_ERR_OK) {
                $fileList[] = $fileInfo;
            }
        }
    }

    $results = [];
    foreach ($fileList as $f) {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) ?: 'jpg';
        $hash = md5($f['name'] . microtime(true) . mt_rand(1000, 9999));
        $filename = "{$hash}.{$ext}";
        $targetPath = "{$uploadsDir}/{$filename}";
        if (@move_uploaded_file($f['tmp_name'], $targetPath)) {
            $src = "/uploads/{$filename}";
            $results[] = [
                'id' => "media_" . time() . "_" . substr($hash, 0, 8),
                'name' => $f['name'],
                'src' => $src,
                'url' => $src,
                'mime' => $f['type'],
                'size' => $f['size'],
                'createdAt' => date('c')
            ];
        }
    }

    if (!empty($results)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(201);
        echo json_encode([
            'data' => count($results) === 1 ? $results[0] : $results,
            'items' => $results
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Intercept Product POST / PATCH to persist multiple images into specs.gallery and costPrice into specs.costPrice
$rawInput = null;
if (in_array($method, ['POST', 'PUT', 'PATCH']) && strpos($apiPath, '/api/v1/products') === 0) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $inputData = json_decode($rawInput, true);
        if (is_array($inputData)) {
            $images = [];
            if (isset($inputData['images']) && is_array($inputData['images'])) {
                $images = array_values(array_filter($inputData['images'], fn($x) => is_string($x) && trim($x) !== ''));
            } elseif (!empty($inputData['image']) && is_string($inputData['image'])) {
                $images = [trim($inputData['image'])];
            }

            if (!isset($inputData['specs']) || !is_array($inputData['specs'])) {
                $inputData['specs'] = [];
            }

            if (!empty($images)) {
                $inputData['specs']['gallery'] = $images;
                $inputData['image'] = $images[0];
            }

            if (isset($inputData['costPrice']) && is_numeric($inputData['costPrice'])) {
                $inputData['specs']['costPrice'] = (float)$inputData['costPrice'];
            }

            $rawInput = json_encode($inputData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }
}

// Détecter si la requête nécessite des droits administrateurs
$isAdminRequest = (
    (strpos($apiPath, '/api/v1/products') === 0 && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) ||
    (strpos($apiPath, '/api/v1/orders') === 0 && strpos($apiPath, '/api/v1/me/orders') === false && in_array($method, ['GET', 'PUT', 'PATCH', 'DELETE'])) ||
    strpos($apiPath, '/api/v1/customers') === 0 ||
    strpos($apiPath, '/api/v1/reviews') === 0 ||
    strpos($apiPath, '/api/v1/coupons') === 0 ||
    strpos($apiPath, '/api/v1/analytics') === 0 ||
    strpos($apiPath, '/api/v1/audit') === 0 ||
    strpos($apiPath, '/api/v1/users') === 0 ||
    strpos($apiPath, '/api/v1/settings') === 0 ||
    (strpos($apiPath, '/api/v1/categories') === 0 && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) ||
    (strpos($apiPath, '/api/v1/brands') === 0 && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']))
);

$url = "https://{$targetHost}{$apiPath}";
$inHeaders = function_exists('getallheaders') ? getallheaders() : [];
$outHeaders = [];

foreach ($inHeaders as $name => $value) {
    $lower = strtolower($name);
    // Exclure cookie et user-agent pour que CURLOPT_COOKIE et CURLOPT_USERAGENT garantissent la correspondance de session
    if (!in_array($lower, ['host', 'content-length', 'accept-encoding', 'cookie', 'user-agent'])) {
        $outHeaders[] = "{$name}: {$value}";
    }
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_HTTPHEADER, $outHeaders);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, KATECH_UPSTREAM_UA);

// Déterminer le cookie de session à transmettre
if ($isAdminRequest) {
    // Les opérations administratives utilisent la session amont avec les privilèges requis
    $cookieToSend = getUpstreamAdminCookie();
} elseif (!empty($_SERVER['HTTP_COOKIE'])) {
    $cookieToSend = $_SERVER['HTTP_COOKIE'];
} else {
    $cookieToSend = '';
}

if (!empty($cookieToSend)) {
    curl_setopt($ch, CURLOPT_COOKIE, $cookieToSend);
}

$bodyPayload = null;
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $bodyPayload = ($rawInput !== null) ? $rawInput : file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyPayload);
}

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => [
            'code' => 'GATEWAY_ERROR',
            'message' => 'Impossible de joindre la passerelle API: ' . curl_error($ch)
        ]
    ]);
    curl_close($ch);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$rawHeaders = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

// TENTATIVE DE RÉCUPÉRATION TRANSPARENTE EN CAS DE 401 UNAUTHORIZED
// Si upstream exige une authentification pour une action administrative, on s'authentifie en arrière-plan et on rejoue
if ($httpCode === 401 && strpos($apiPath, '/auth/login') === false) {
    $freshAdminCookie = getUpstreamAdminCookie(true);
    if (!empty($freshAdminCookie)) {
        $chRetry = curl_init($url);
        curl_setopt($chRetry, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($chRetry, CURLOPT_HTTPHEADER, $outHeaders);
        curl_setopt($chRetry, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chRetry, CURLOPT_HEADER, true);
        curl_setopt($chRetry, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($chRetry, CURLOPT_TIMEOUT, 30);
        curl_setopt($chRetry, CURLOPT_USERAGENT, KATECH_UPSTREAM_UA);
        curl_setopt($chRetry, CURLOPT_COOKIE, $freshAdminCookie);
        if ($bodyPayload !== null) {
            curl_setopt($chRetry, CURLOPT_POSTFIELDS, $bodyPayload);
        }
        $retryResp = curl_exec($chRetry);
        if ($retryResp !== false) {
            $retryCode = curl_getinfo($chRetry, CURLINFO_HTTP_CODE);
            $retryHeaderSize = curl_getinfo($chRetry, CURLINFO_HEADER_SIZE);
            if ($retryCode >= 200 && $retryCode < 400) {
                $httpCode = $retryCode;
                $rawHeaders = substr($retryResp, 0, $retryHeaderSize);
                $body = substr($retryResp, $retryHeaderSize);
                header("Set-Cookie: {$freshAdminCookie}; path=/; secure; HttpOnly; SameSite=Lax", false);
            }
        }
        curl_close($chRetry);
    }
}

http_response_code($httpCode);

// Invalidation immédiate du cache commandes lors d'une modification de statut
if (strpos($apiPath, '/orders') !== false && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    @unlink(sys_get_temp_dir() . '/katech_admin_orders.json');
}

// Secours pour la consultation d'une commande spécifique si 401
if (preg_match('#^/api/v1/orders/([A-Za-z0-9_-]+)$#', $apiPath, $mOrder) && $method === 'GET' && $httpCode === 401) {
    $orderId = $mOrder[1];
    $allOrders = getUpstreamOrdersFromAdmin(true);
    foreach ($allOrders as $o) {
        if (($o['id'] ?? '') === $orderId) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['data' => $o], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

// Formater l'erreur de mauvais mot de passe pour les comptes existants
if (strpos($apiPath, '/auth/login') !== false && $httpCode === 401) {
    $body = json_encode([
        'success' => false,
        'errorCode' => 'INVALID_CREDENTIALS',
        'error' => [
            'code' => 'INVALID_CREDENTIALS',
            'message' => 'Identifiants invalides.'
        ],
        'message' => 'Identifiants invalides.'
    ], JSON_UNESCAPED_UNICODE);
}

// Automatically synchronize web orders directly into Caisse journal with profit calculation
if (strpos($apiPath, '/api/v1/orders') === 0 && $method === 'POST' && ($httpCode === 200 || $httpCode === 201)) {
    $orderData = json_decode($body, true);
    $createdOrder = $orderData['data'] ?? $orderData;
    if (is_array($createdOrder) && !empty($createdOrder['id'])) {
        $orderId = $createdOrder['id'];
        $orderTotal = (float)($createdOrder['total'] ?? 0);
        $items = $createdOrder['items'] ?? [];
        $dbPath = __DIR__ . '/katech.sqlite';
        if (file_exists($dbPath)) {
            try {
                $db = new PDO('sqlite:' . $dbPath);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
                $totalCost = 0;
                $itemsSummary = [];
                foreach ($items as $item) {
                    $pid = $item['productId'] ?? '';
                    $qty = (int)($item['qty'] ?? 1);
                    $pName = $item['name'] ?? 'Article';
                    $itemsSummary[] = "{$pName} (x{$qty})";
                    $unitCost = 0;
                    if (!empty($pid)) {
                        $stmtP = $db->prepare("SELECT specs FROM products WHERE id = :id LIMIT 1");
                        $stmtP->execute([':id' => $pid]);
                        $pRow = $stmtP->fetch(PDO::FETCH_ASSOC);
                        if ($pRow) {
                            $pSpecs = json_decode($pRow['specs'] ?? '{}', true);
                            if (isset($pSpecs['costPrice']) && is_numeric($pSpecs['costPrice'])) {
                                $unitCost = (float)$pSpecs['costPrice'];
                            }
                        }
                    }
                    $totalCost += $unitCost * $qty;
                }
                $profit = $orderTotal - $totalCost;
                $descStr = implode(', ', $itemsSummary);
                
                $stmtN = $db->prepare("SELECT id, body FROM notifications WHERE title = 'caisse_journal_store' LIMIT 1");
                $stmtN->execute();
                $notifRow = $stmtN->fetch(PDO::FETCH_ASSOC);
                if ($notifRow) {
                    $journal = json_decode($notifRow['body'] ?? '{}', true);
                    if (!is_array($journal)) $journal = [];
                    if (!isset($journal['transactions']) || !is_array($journal['transactions'])) {
                        $journal['transactions'] = [];
                    }
                    $alreadyExists = false;
                    foreach ($journal['transactions'] as $tx) {
                        if (isset($tx['title']) && strpos($tx['title'], "#{$orderId}") !== false) {
                            $alreadyExists = true;
                            break;
                        }
                    }
                    if (!$alreadyExists) {
                        $newTx = [
                            'id' => 'tx_web_' . $orderId . '_' . time(),
                            'type' => 'pc_sold',
                            'title' => "Commande Web #{$orderId}: {$descStr}",
                            'amount' => $orderTotal,
                            'costPrice' => $totalCost,
                            'profit' => $profit,
                            'paymentMethod' => (($createdOrder['paymentMethod'] ?? '') === 'cod') ? 'Espèces' : 'Autre',
                            'clientName' => $createdOrder['customerName'] ?? 'Client Web',
                            'clientPhone' => $createdOrder['phone'] ?? '',
                            'worker' => 'Vente en Ligne (Site Web)',
                            'warranty' => '3 mois',
                            'notes' => "Vente automatique enregistrée directement depuis le site web. Wilaya: " . ($createdOrder['wilaya'] ?? '') . ". Coût: {$totalCost} DA. Bénéfice net: +{$profit} DA.",
                            'date' => date('c')
                        ];
                        array_unshift($journal['transactions'], $newTx);
                        $journal['lastUpdated'] = date('c');
                        $updatedBody = json_encode($journal, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                        $stmtU = $db->prepare("UPDATE notifications SET body = :body, updated_at = :now WHERE id = :id");
                        $stmtU->execute([
                            ':body' => $updatedBody,
                            ':now' => date('Y-m-d H:i:s'),
                            ':id' => $notifRow['id']
                        ]);
                    }
                }
            } catch (Exception $e) {}
        }
    }
}

// ---------------------------------------------------------
// EMAIL MARKETING AUTOMATIONS (Brevo + Google Gemini)
// ---------------------------------------------------------

// Hook 1: Capture from Newsletter Signup (POST /api/v1/subscribers)
if (strpos($apiPath, '/api/v1/subscribers') === 0 && $method === 'POST') {
    $subData = json_decode($body, true);
    $subEmail = $subData['data']['email'] ?? $subData['email'] ?? null;
    if (empty($subEmail) && !empty($rawInput)) {
        $in = json_decode($rawInput, true);
        $subEmail = $in['email'] ?? null;
    }
    if (!empty($subEmail)) {
        KaTechMarketing::captureSubscriber($subEmail, 'footer');
    }
}

// Hook 2: Capture customer email on checkout (POST /api/v1/orders)
if (strpos($apiPath, '/api/v1/orders') === 0 && $method === 'POST' && ($httpCode === 200 || $httpCode === 201)) {
    $orderData = json_decode($body, true);
    $createdOrder = $orderData['data'] ?? $orderData;
    $custEmail = $createdOrder['customerEmail'] ?? $createdOrder['email'] ?? null;
    if (empty($custEmail) && !empty($rawInput)) {
        $in = json_decode($rawInput, true);
        $custEmail = $in['customerEmail'] ?? $in['email'] ?? null;
    }
    if (!empty($custEmail)) {
        KaTechMarketing::captureSubscriber($custEmail, 'checkout');
    }
}

// Hook 3: Capture customer email and send Welcome Email on user registration (POST /api/v1/auth/register)
if (strpos($apiPath, '/api/v1/auth/register') === 0 && $method === 'POST' && ($httpCode === 200 || $httpCode === 201)) {
    $regData = json_decode($body, true);
    $regEmail = $regData['data']['user']['email'] ?? $regData['data']['email'] ?? null;
    $regName = $regData['data']['user']['name'] ?? $regData['data']['name'] ?? '';
    if (empty($regEmail) && !empty($rawInput)) {
        $in = json_decode($rawInput, true);
        $regEmail = $in['email'] ?? null;
        if (empty($regName)) $regName = $in['name'] ?? '';
    }
    if (!empty($regEmail)) {
        KaTechMarketing::captureSubscriber($regEmail, 'register');
        KaTechMarketing::sendWelcomeEmail($regEmail, $regName);

        $db = KaTechMarketing::getDb();
        if ($db) {
            $now = date('c');
            $rawPw = null;
            if (!empty($rawInput)) {
                $in = json_decode($rawInput, true);
                $rawPw = $in['password'] ?? null;
            }
            $pHash = $rawPw ? password_hash($rawPw, PASSWORD_DEFAULT) : '';
            $regId = $regData['data']['user']['id'] ?? $regData['data']['id'] ?? ('usr_' . bin2hex(random_bytes(8)));
            $ins = $db->prepare("INSERT OR IGNORE INTO users (id, name, email, password_hash, role, active, created_at, updated_at) VALUES (:id, :name, :email, :hash, 'customer', 1, :now, :now)");
            $ins->execute([
                ':id' => $regId,
                ':name' => $regName ?: 'Client KA Tech',
                ':email' => strtolower(trim($regEmail)),
                ':hash' => $pHash,
                ':now' => $now
            ]);
        }
    }
}

// Hook 4: Automatic Trigger on "Add New Product" (type: 'new_item')
if (strpos($apiPath, '/api/v1/products') === 0 && $method === 'POST' && ($httpCode === 200 || $httpCode === 201)) {
    $pJson = json_decode($body, true);
    $pCreated = $pJson['data'] ?? $pJson;
    if (is_array($pCreated) && !empty($pCreated['name'])) {
        $pName = is_array($pCreated['name']) ? ($pCreated['name']['fr'] ?? reset($pCreated['name'])) : $pCreated['name'];
        $pDesc = is_array($pCreated['description'] ?? null) ? ($pCreated['description']['fr'] ?? reset($pCreated['description'])) : ($pCreated['description'] ?? '');
        $pImg = $pCreated['image'] ?? ($pCreated['images'][0] ?? 'https://katechstore.net/ka-tech-logo-new.png');
        $pPrice = (float)($pCreated['price'] ?? 0);
        $pOldPrice = (float)($pCreated['oldPrice'] ?? 0);
        $pSlug = $pCreated['slug'] ?? $pCreated['id'] ?? '';
        KaTechMarketing::broadcastToSubscribers([
            'type' => 'new_item',
            'title' => $pName,
            'description' => $pDesc,
            'imageUrl' => $pImg,
            'oldPrice' => $pOldPrice,
            'newPrice' => $pPrice,
            'productUrl' => "https://katechstore.net/products/{$pSlug}"
        ]);
    }
}

// Hook 5: Automatic Trigger on "Update Product Price / Discount" (type: 'discount')
if (strpos($apiPath, '/api/v1/products') === 0 && in_array($method, ['PUT', 'PATCH']) && ($httpCode === 200 || $httpCode === 201)) {
    if (!empty($rawInput)) {
        $updData = json_decode($rawInput, true);
        $newPrice = isset($updData['price']) ? (float)$updData['price'] : null;
        $oldPrice = isset($updData['oldPrice']) ? (float)$updData['oldPrice'] : null;
        
        // Trigger if discount applied or price lowered
        if ($newPrice !== null && $oldPrice !== null && $oldPrice > $newPrice) {
            $pJson = json_decode($body, true);
            $pUpdated = $pJson['data'] ?? $pJson;
            $pName = is_array($pUpdated['name'] ?? null) ? ($pUpdated['name']['fr'] ?? reset($pUpdated['name'])) : ($pUpdated['name'] ?? 'Produit en promotion');
            $pDesc = is_array($pUpdated['description'] ?? null) ? ($pUpdated['description']['fr'] ?? reset($pUpdated['description'])) : ($pUpdated['description'] ?? '');
            $pImg = $pUpdated['image'] ?? ($pUpdated['images'][0] ?? 'https://katechstore.net/ka-tech-logo-new.png');
            $pSlug = $pUpdated['slug'] ?? $pUpdated['id'] ?? '';
            KaTechMarketing::broadcastToSubscribers([
                'type' => 'discount',
                'title' => $pName,
                'description' => $pDesc,
                'imageUrl' => $pImg,
                'oldPrice' => $oldPrice,
                'newPrice' => $newPrice,
                'productUrl' => "https://katechstore.net/products/{$pSlug}"
            ]);
        }
    }
}

// Helper to enrich a product with images array and costPrice
$enrichProduct = function(&$prod) {
    if (!is_array($prod)) return;
    $gallery = null;
    $costPrice = 0;
    if (isset($prod['specs']) && is_array($prod['specs'])) {
        if (isset($prod['specs']['gallery']) && is_array($prod['specs']['gallery'])) {
            $gallery = array_values(array_filter($prod['specs']['gallery'], fn($x) => is_string($x) && trim($x) !== ''));
            unset($prod['specs']['gallery']);
        }
        if (isset($prod['specs']['costPrice']) && is_numeric($prod['specs']['costPrice'])) {
            $costPrice = (float)$prod['specs']['costPrice'];
            unset($prod['specs']['costPrice']);
        }
    }
    if (!empty($gallery)) {
        $prod['images'] = $gallery;
        if (empty($prod['image'])) {
            $prod['image'] = $gallery[0];
        }
    } elseif (!empty($prod['image']) && is_string($prod['image'])) {
        $prod['images'] = [$prod['image']];
    } else {
        $prod['images'] = [];
    }
    $prod['costPrice'] = $costPrice;
};

// Process products response (sorting and multiple images enrichment)
if (strpos($apiPath, '/api/v1/products') === 0 && ($httpCode === 200 || $httpCode === 201)) {
    $decoded = json_decode($body, true);
    if (is_array($decoded) && isset($decoded['data'])) {
        if (isset($decoded['data']['items']) && is_array($decoded['data']['items'])) {
            // Product list
            $isInStock = function($item) {
                if (!is_array($item)) return 0;
                $stock = $item['stock'] ?? '';
                if ($stock === 'out' || $stock === 'out_of_stock' || $stock === 0 || $stock === '0') return 0;
                if (is_numeric($stock) && (float)$stock <= 0) return 0;
                return 1;
            };
            if ($method === 'GET') {
                usort($decoded['data']['items'], function($a, $b) use ($isInStock) {
                    return $isInStock($b) - $isInStock($a);
                });
            }
            foreach ($decoded['data']['items'] as &$item) {
                $enrichProduct($item);
            }
            unset($item);
            $body = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif (isset($decoded['data']['id'])) {
            // Single product
            $enrichProduct($decoded['data']);
            $body = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }
}

// Forward response headers
$headerLines = explode("\r\n", $rawHeaders);
foreach ($headerLines as $line) {
    if (!empty($line) && !preg_match('/^(HTTP\/|Transfer-Encoding|Connection|Content-Length):/i', $line)) {
        if (stripos($line, 'Set-Cookie:') === 0) {
            $line = preg_replace('/;\s*domain=[^;]+/i', '', $line);
        }
        header($line, false);
    }
}
header('Content-Length: ' . strlen($body));

echo $body;
