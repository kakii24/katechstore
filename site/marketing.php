<?php
/**
 * KA Tech Store — Service Marketing Automatisé (Brevo + Google Gemini)
 * Gère la capture des abonnés, la synchronisation Brevo List #2,
 * la génération d'emails marketing par IA Gemini, et les envois SMTP Brevo.
 */

class KaTechMarketing {
    private static $env = null;
    private static $db = null;

    /**
     * Charge les variables depuis le fichier .env
     */
    public static function loadEnv() {
        if (self::$env !== null) return self::$env;
        self::$env = [];
        
        $envPaths = [
            __DIR__ . '/.env',
            __DIR__ . '/../.env'
        ];

        foreach ($envPaths as $p) {
            if (file_exists($p)) {
                $lines = file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0) continue;
                    if (strpos($line, '=') !== false) {
                        list($key, $val) = explode('=', $line, 2);
                        $key = trim($key);
                        $val = trim($val);
                        // Strip quotes if present
                        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                            $val = substr($val, 1, -1);
                        }
                        self::$env[$key] = $val;
                    }
                }
            }
        }

        // Valeurs par défaut depuis la configuration demandée
        if (empty(self::$env['BREVO_API_KEY'])) {
            self::$env['BREVO_API_KEY'] = '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';
        }
        if (empty(self::$env['BREVO_LIST_ID'])) {
            self::$env['BREVO_LIST_ID'] = '2';
        }
        if (empty(self::$env['BREVO_SENDER_EMAIL'])) {
            self::$env['BREVO_SENDER_EMAIL'] = 'news@katechstore.net';
        }
        if (empty(self::$env['BREVO_SENDER_NAME'])) {
            self::$env['BREVO_SENDER_NAME'] = 'KA Tech Store';
        }
        if (empty(self::$env['BREVO_REPLY_TO'])) {
            self::$env['BREVO_REPLY_TO'] = 'kaa.tech.48@gmail.com';
        }
        if (empty(self::$env['GEMINI_API_KEY'])) {
            self::$env['GEMINI_API_KEY'] = 'AQ.Ab8RN6LpdUxetV_E3D10YZQaHSktMkIXBg07mDgZKQ_mjrsVQQ';
        }

        return self::$env;
    }

    /**
     * Initialise et retourne la connexion PDO SQLite
     */
    public static function getDb() {
        if (self::$db !== null) return self::$db;
        $dbPath = __DIR__ . '/katech.sqlite';
        try {
            self::$db = new PDO('sqlite:' . $dbPath);
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

            // Vérification et création / migration de la table subscribers
            self::$db->exec("
                CREATE TABLE IF NOT EXISTS subscribers (
                    id TEXT PRIMARY KEY,
                    email TEXT UNIQUE NOT NULL,
                    status TEXT DEFAULT 'active',
                    unsubscribe_token TEXT,
                    source TEXT,
                    created_at TEXT NOT NULL
                );
            ");

            // Migration douce pour les colonnes manquantes
            $stmt = self::$db->query("PRAGMA table_info(subscribers)");
            $columns = [];
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $columns[] = $row['name'];
                }
            }
            if (!in_array('status', $columns)) {
                @self::$db->exec("ALTER TABLE subscribers ADD COLUMN status TEXT DEFAULT 'active'");
            }
            if (!in_array('unsubscribe_token', $columns)) {
                @self::$db->exec("ALTER TABLE subscribers ADD COLUMN unsubscribe_token TEXT");
            }
            if (!in_array('source', $columns)) {
                @self::$db->exec("ALTER TABLE subscribers ADD COLUMN source TEXT");
            }
        } catch (Exception $e) {
            error_log('[Marketing DB Error] ' . $e->getMessage());
        }
        return self::$db;
    }

    /**
     * Synchronise un contact dans la liste Brevo
     */
    public static function syncToBrevo($email, $listId = null) {
        $env = self::loadEnv();
        $apiKey = $env['BREVO_API_KEY'];
        $lid = (int)($listId ?? $env['BREVO_LIST_ID'] ?? 2);

        if (empty($apiKey) || empty($email)) return false;

        $url = 'https://api.brevo.com/v3/contacts';
        $payload = json_encode([
            'email' => $email,
            'listIds' => [$lid],
            'updateEnabled' => true
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return in_array($code, [200, 201, 204]);
    }

    /**
     * Enregistre ou met à jour un abonné en base locale ET synchronise sur Brevo List #2
     */
    public static function captureSubscriber($email, $source = 'footer') {
        $email = trim(strtolower($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email invalide'];
        }

        $db = self::getDb();
        $token = bin2hex(random_bytes(16));
        $now = date('c');

        if ($db) {
            try {
                // Vérifier si existe déjà
                $check = $db->prepare("SELECT id, unsubscribe_token, status FROM subscribers WHERE email = :email LIMIT 1");
                $check->execute([':email' => $email]);
                $row = $check->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $token = $row['unsubscribe_token'] ?: $token;
                    $upd = $db->prepare("UPDATE subscribers SET status = 'active', unsubscribe_token = :token WHERE id = :id");
                    $upd->execute([':token' => $token, ':id' => $row['id']]);
                    $subId = $row['id'];
                } else {
                    $subId = 'sub_' . bin2hex(random_bytes(8));
                    $ins = $db->prepare("INSERT INTO subscribers (id, email, status, unsubscribe_token, source, created_at) VALUES (:id, :email, 'active', :token, :source, :now)");
                    $ins->execute([
                        ':id' => $subId,
                        ':email' => $email,
                        ':token' => $token,
                        ':source' => $source,
                        ':now' => $now
                    ]);
                }
            } catch (Exception $e) {
                error_log('[Capture Subscriber DB Error] ' . $e->getMessage());
            }
        }

        // Brevo sync
        self::syncToBrevo($email);

        return [
            'success' => true,
            'id' => $subId ?? 'sub_ok',
            'email' => $email,
            'status' => 'active',
            'unsubscribe_token' => $token
        ];
    }

    /**
     * Désabonne un contact via son jeton
     */
    public static function unsubscribe($token) {
        $token = trim($token);
        if (empty($token)) {
            self::renderUnsubscribePage(false, 'Jeton de désabonnement manquant ou invalide.');
            return;
        }

        $db = self::getDb();
        $emailFound = null;

        if ($db) {
            $stmt = $db->prepare("SELECT email FROM subscribers WHERE unsubscribe_token = :token LIMIT 1");
            $stmt->execute([':token' => $token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $emailFound = $row['email'];
                $upd = $db->prepare("UPDATE subscribers SET status = 'unsubscribed' WHERE unsubscribe_token = :token");
                $upd->execute([':token' => $token]);
            }
        }

        // Désabonnement Brevo si email trouvé
        if ($emailFound) {
            $env = self::loadEnv();
            $apiKey = $env['BREVO_API_KEY'];
            $lid = (int)($env['BREVO_LIST_ID'] ?? 2);
            if (!empty($apiKey)) {
                $ch = curl_init("https://api.brevo.com/v3/contacts/lists/{$lid}/contacts/remove");
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'api-key: ' . $apiKey
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['emails' => [$emailFound]]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                curl_exec($ch);
                curl_close($ch);
            }
        }

        self::renderUnsubscribePage(true, 'Votre adresse a bien été retirée de toutes nos listes de diffusion.');
    }

    /**
     * Génère un email marketing HTML ultra-moderne avec Google Gemini AI
     */
    public static function generateEmailWithGemini($data) {
        $env = self::loadEnv();
        $apiKey = $env['GEMINI_API_KEY'];

        $type = $data['type'] ?? 'new_item';
        $title = $data['title'] ?? 'Nouveau Produit';
        $desc = $data['description'] ?? 'Découvrez nos nouveautés technologiques chez KA Tech Store.';
        $imageUrl = $data['imageUrl'] ?? 'https://katechstore.net/ka-tech-logo-new.png';
        $oldPrice = !empty($data['oldPrice']) ? (float)$data['oldPrice'] : null;
        $newPrice = (float)($data['newPrice'] ?? 0);
        $productUrl = $data['productUrl'] ?? 'https://katechstore.net';

        $discountPercent = 0;
        if ($oldPrice && $oldPrice > $newPrice) {
            $discountPercent = round((($oldPrice - $newPrice) / $oldPrice) * 100);
        }

        $prompt = "You are an elite email marketing copywriter and HTML email designer for \"KA Tech Store\" (katechstore.net), a premium computer and tech store in Algeria.
Create a high-converting, mobile-responsive HTML promotional email in French.

Product Details:
- Type: {$type} (" . ($type === 'discount' ? 'Promotion / Solde' : 'Nouvel Arrivage') . ")
- Title: {$title}
- Description & Specs: {$desc}
- Product Image URL: {$imageUrl}
- Old Price: " . ($oldPrice ? number_format($oldPrice, 0, '', ' ') . ' DA' : '') . "
- New Selling Price: " . number_format($newPrice, 0, '', ' ') . " DA
- Discount: " . ($discountPercent > 0 ? "-{$discountPercent}%" : '') . "
- Product Link: {$productUrl}
- Unsubscribe URL placeholder: {{unsubscribeUrl}}

Requirements:
1. Modern luxury tech dark mode (background #0b0f19, container card #131c31, text #f1f5f9, emerald CTA button #10b981, borders #1e293b).
2. Bulletproof email client compatibility (tables with inline styles, 600px width on desktop, 100% on mobile).
3. Header: KA Tech Store branding with emerald accents.
4. Hero Image: Centered product photo with clean border and subtle glow.
5. Content:
   - Badge: \"" . ($type === 'discount' ? "🔥 OFFRE SPÉCIALE -{$discountPercent}%" : '✨ NOUVEL ARRIVAGE') . "\"
   - Bold title and bulleted key specs.
   - Pricing box (if discount, show old price crossed out and new price in large bold emerald).
   - High-contrast Call to Action button \"COMMANDER MAINTENANT\" linking to {$productUrl}.
   - Trust highlights: \"Livraison 58 Wilayas\", \"Garantie KA Tech incluse\", \"Service Client 7j/7\".
6. Footer: KA Tech Store contact, and an unsubscribe link using {{unsubscribeUrl}}.

Return ONLY a strict JSON object with this exact structure:
{\"subject\": \"French catchy subject with emoji\", \"htmlContent\": \"<!DOCTYPE html>...\"}";

        // Liste des modèles à essayer (priorité au modèle demandé puis aux versions disponibles)
        $models = ['gemini-2.5-flash', 'gemini-3-flash-preview', 'gemini-3.8-flash', 'gemini-flash-latest'];
        
        $result = null;

        foreach ($models as $m) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key={$apiKey}";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['responseMimeType' => 'application/json']
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && !empty($resp)) {
                $decoded = json_decode($resp, true);
                $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $parsed = json_decode($text, true);
                if (is_array($parsed) && !empty($parsed['subject']) && !empty($parsed['htmlContent'])) {
                    $result = $parsed;
                    break;
                }
            }
        }

        // Fallback haute qualité si Gemini indisponible
        if (!$result) {
            $subject = ($type === 'discount')
                ? "🔥 -{$discountPercent}% de réduction chez KA Tech Store sur {$title} !"
                : "✨ Nouvel Arrivage chez KA Tech Store : {$title}";
            
            $formattedOld = $oldPrice ? number_format($oldPrice, 0, '', ' ') . ' DA' : '';
            $formattedNew = number_format($newPrice, 0, '', ' ') . ' DA';

            $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$subject}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b0f19; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0b0f19; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #131c31; border: 1px solid #1e293b; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
          <!-- Header -->
          <tr>
            <td style="padding: 28px 32px; background: linear-gradient(135deg, #0d1527 0%, #17233f 100%); border-bottom: 1px solid #1e293b; text-align: center;">
              <span style="font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: 1px;">KA TECH <span style="color: #10b981;">STORE</span></span>
              <div style="font-size: 12px; color: #94a3b8; margin-top: 4px; text-transform: uppercase; letter-spacing: 2px;">Informatique & Technologie de Pointe</div>
            </td>
          </tr>
          <!-- Image Hero -->
          <tr>
            <td align="center" style="padding: 30px 30px 15px 30px;">
              <img src="{$imageUrl}" alt="{$title}" style="max-width: 100%; width: 480px; height: auto; border-radius: 12px; border: 1px solid #23314d; display: block;" />
            </td>
          </tr>
          <!-- Content -->
          <tr>
            <td style="padding: 20px 32px 30px 32px; text-align: center;">
              <div style="display: inline-block; padding: 6px 14px; background-color: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; border-radius: 50px; color: #10b981; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 15px;">
                {$type}
              </div>
              <h1 style="font-size: 22px; font-weight: 700; color: #ffffff; margin: 0 0 12px 0; line-height: 1.4;">{$title}</h1>
              <p style="font-size: 14px; color: #94a3b8; line-height: 1.6; margin: 0 0 25px 0;">{$desc}</p>
              <!-- Price box -->
              <table align="center" cellpadding="0" cellspacing="0" style="background-color: #0d1527; border: 1px solid #1e293b; border-radius: 10px; padding: 12px 24px; margin-bottom: 25px;">
                <tr>
                  <td align="center">
                    <span style="font-size: 13px; color: #64748b; text-decoration: line-through; margin-right: 10px;">{$formattedOld}</span>
                    <span style="font-size: 24px; font-weight: 800; color: #10b981;">{$formattedNew}</span>
                  </td>
                </tr>
              </table>
              <!-- CTA Button -->
              <table align="center" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background-color: #10b981; border-radius: 10px;">
                    <a href="{$productUrl}" target="_blank" style="display: inline-block; padding: 14px 36px; font-size: 15px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 10px; letter-spacing: 0.5px;">COMMANDER MAINTENANT &rarr;</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <!-- Avantages -->
          <tr>
            <td style="padding: 20px 32px; background-color: #0d1527; border-top: 1px solid #1e293b; text-align: center;">
              <span style="font-size: 12px; color: #94a3b8; margin: 0 10px;">🚚 Livraison 58 Wilayas</span>
              <span style="font-size: 12px; color: #94a3b8; margin: 0 10px;">🛡️ Garantie Incluse</span>
              <span style="font-size: 12px; color: #94a3b8; margin: 0 10px;">💬 Support 7j/7</span>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="padding: 24px 32px; background-color: #080c16; text-align: center; font-size: 11px; color: #64748b; line-height: 1.6;">
              <p style="margin: 0 0 8px 0;">KA Tech Store &bull; Alger, Alg&eacute;rie &bull; T&eacute;l : 0555 00 00 00 &bull; contact@katechstore.net</p>
              <p style="margin: 0;"><a href="{{unsubscribeUrl}}" style="color: #94a3b8; text-decoration: underline;">Se d&eacute;sabonner de nos e-mails</a></p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
            $result = ['subject' => $subject, 'htmlContent' => $html];
        }

        return $result;
    }

    /**
     * Envoie un email SMTP via Brevo
     */
    public static function sendBrevoSmtp($toEmail, $toName, $subject, $htmlContent) {
        $env = self::loadEnv();
        $apiKey = $env['BREVO_API_KEY'];
        $senderEmail = $env['BREVO_SENDER_EMAIL'];
        $senderName = $env['BREVO_SENDER_NAME'];
        $replyTo = $env['BREVO_REPLY_TO'];

        $url = 'https://api.brevo.com/v3/smtp/email';
        $payload = json_encode([
            'sender' => [
                'name' => $senderName,
                'email' => $senderEmail
            ],
            'to' => [
                ['email' => $toEmail, 'name' => $toName ?: 'Client KA Tech']
            ],
            'replyTo' => [
                'email' => $replyTo,
                'name' => $senderName
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'success' => in_array($code, [200, 201, 202]),
            'status' => $code,
            'response' => json_decode($resp, true)
        ];
    }

    /**
     * Diffuse un email généré par IA à l'ensemble des abonnés actifs
     */
    public static function broadcastToSubscribers($productData) {
        // 1. Générer l'email avec Gemini
        $emailGen = self::generateEmailWithGemini($productData);
        $subject = $emailGen['subject'];
        $rawHtml = $emailGen['htmlContent'];

        $db = self::getDb();
        $subscribers = [];
        if ($db) {
            $stmt = $db->query("SELECT email, unsubscribe_token FROM subscribers WHERE status = 'active'");
            if ($stmt) {
                $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // Si aucun abonné local, inclure au moins l'adresse de notification admin
        if (empty($subscribers)) {
            $subscribers = [['email' => 'kaa.tech.48@gmail.com', 'unsubscribe_token' => 'admin_tok']];
        }

        $sentCount = 0;
        foreach ($subscribers as $sub) {
            $email = $sub['email'];
            $token = $sub['unsubscribe_token'] ?? 'tok';
            $unsubUrl = "https://katechstore.net/unsubscribe?token=" . urlencode($token);
            $personalizedHtml = str_replace('{{unsubscribeUrl}}', $unsubUrl, $rawHtml);

            $res = self::sendBrevoSmtp($email, 'Client', $subject, $personalizedHtml);
            if ($res['success']) {
                $sentCount++;
            }
        }

        return [
            'success' => true,
            'sent_count' => $sentCount,
            'total_subscribers' => count($subscribers),
            'subject' => $subject
        ];
    }

    /**
     * Page HTML de confirmation de désabonnement
     */
    public static function renderUnsubscribePage($success, $message) {
        header('Content-Type: text/html; charset=utf-8');
        $title = $success ? "Désabonnement Confirmé" : "Erreur de Désabonnement";
        $color = $success ? "#10b981" : "#f43f5e";
        $icon = $success ? "✓" : "✕";

        echo <<<HTML
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title} — KA Tech Store</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      background: #090d16;
      color: #f1f5f9;
      font-family: 'Inter', -apple-system, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      text-align: center;
    }
    .card {
      background: #111827;
      border: 1px solid #1f2937;
      border-radius: 16px;
      padding: 40px 30px;
      max-width: 480px;
      width: 90%;
      box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    }
    .icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid {$color};
      color: {$color};
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 20px;
    }
    h1 {
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 12px;
    }
    p {
      color: #94a3b8;
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 25px;
    }
    .btn {
      display: inline-block;
      background: #10b981;
      color: #ffffff;
      text-decoration: none;
      padding: 12px 28px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      transition: background 0.2s;
    }
    .btn:hover {
      background: #059669;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">{$icon}</div>
    <h1>{$title}</h1>
    <p>{$message}</p>
    <a href="https://katechstore.net" class="btn">Retour à la boutique</a>
  </div>
</body>
</html>
HTML;
        exit;
    }
}
