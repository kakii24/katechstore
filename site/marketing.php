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
            $rawPayload = 'eyJhcGlfa2V5IjoieGtleXNpYi1kZTg0MWYwZGQ4MGViODI2OWYxZmIwYjZhYzJjZDY5ODRmZDA1ZWNiYTBjNWQ0YTcyNGE5MzQ4M2QwOTE5OTRlLTlmN3JrVFdZU2ZnTnlQcGQifQ==';
            $decoded = json_decode(base64_decode($rawPayload), true);
            self::$env['BREVO_API_KEY'] = $decoded['api_key'] ?? '';
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

            // Migration douce pour les colonnes de réinitialisation de mot de passe
            $stmtU = self::$db->query("PRAGMA table_info(users)");
            $uCols = [];
            if ($stmtU) {
                while ($r = $stmtU->fetch(PDO::FETCH_ASSOC)) {
                    $uCols[] = $r['name'];
                }
            }
            if (!in_array('reset_password_token', $uCols)) {
                @self::$db->exec("ALTER TABLE users ADD COLUMN reset_password_token TEXT");
            }
            if (!in_array('reset_password_expires', $uCols)) {
                @self::$db->exec("ALTER TABLE users ADD COLUMN reset_password_expires TEXT");
            }

            // Table de suivi des réinitialisations de mot de passe
            self::$db->exec("
                CREATE TABLE IF NOT EXISTS password_resets (
                    id TEXT PRIMARY KEY,
                    user_id TEXT,
                    email TEXT,
                    token_hash TEXT NOT NULL,
                    expires_at TEXT NOT NULL,
                    used_at TEXT NULL,
                    created_at TEXT NOT NULL
                );
            ");

            // Migration douce pour la colonne email dans password_resets
            $stmtPR = self::$db->query("PRAGMA table_info(password_resets)");
            $prCols = [];
            if ($stmtPR) {
                while ($r = $stmtPR->fetch(PDO::FETCH_ASSOC)) {
                    $prCols[] = $r['name'];
                }
            }
            if (!in_array('email', $prCols)) {
                @self::$db->exec("ALTER TABLE password_resets ADD COLUMN email TEXT");
            }

            // Seed des comptes existants connus dans la table users
            $knownUsers = [
                ['id' => 'usr_admin_store', 'name' => 'Administrateur KA Tech', 'email' => 'admin@katechstore.net', 'role' => 'owner'],
                ['id' => 'usr-owner', 'name' => 'Propriétaire', 'email' => 'admin@katech.a', 'role' => 'owner'],
                ['id' => 'usr_mokhtar_a', 'name' => 'Mokhtar', 'email' => 'mokhtar@katech.a', 'role' => 'owner'],
                ['id' => 'usr_ahmed_a', 'name' => 'Ahmed', 'email' => 'ahmed@katech.a', 'role' => 'owner'],
                ['id' => 'usr_mokhtar_store', 'name' => 'Mokhtar Benmelha', 'email' => 'mokhtar@katechstore.net', 'role' => 'owner'],
                ['id' => 'usr_admin_dz', 'name' => 'Admin KA Tech', 'email' => 'admin@katech.dz', 'role' => 'owner'],
                ['id' => 'usr_kaa_gmail', 'name' => 'KA Tech Admin', 'email' => 'kaa.tech.48@gmail.com', 'role' => 'owner'],
                ['id' => 'cus_kakikokoko', 'name' => 'Mokhtar', 'email' => 'kakikokoko888@gmail.com', 'role' => 'customer'],
                ['id' => 'cus_bouziane', 'name' => 'bouzianeachir@gmail.com', 'email' => 'bouzianeachir@gmail.com', 'role' => 'customer'],
                ['id' => 'cus_okba', 'name' => 'عقبة مبروكي', 'email' => 'okbamebrouki56@gmail.com', 'role' => 'customer'],
                ['id' => 'cus_sk', 'name' => 'sk', 'email' => 'skkkkkk45@gmail.com', 'role' => 'customer'],
                ['id' => 'cus_mokhtar_ben', 'name' => 'mokhtar benmelha', 'email' => 'mokhtarbenmelha@gmail.com', 'role' => 'customer']
            ];
            $now = date('c');
            foreach ($knownUsers as $ku) {
                $check = self::$db->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
                $check->execute([':email' => $ku['email']]);
                if (!$check->fetch()) {
                    $ins = self::$db->prepare("INSERT INTO users (id, name, email, password_hash, role, active, created_at, updated_at) VALUES (:id, :name, :email, '', :role, 1, :now, :now)");
                    $ins->execute([
                        ':id' => $ku['id'],
                        ':name' => $ku['name'],
                        ':email' => $ku['email'],
                        ':role' => $ku['role'],
                        ':now' => $now
                    ]);
                }
            }

            // Copier également tout abonné inscrit vers la table users s'il n'y est pas encore
            @self::$db->exec("
                INSERT OR IGNORE INTO users (id, name, email, password_hash, role, active, created_at, updated_at)
                SELECT id, 'Client KA Tech', email, '', 'customer', 1, created_at, created_at
                FROM subscribers
                WHERE (source = 'register' OR source = 'checkout') AND LOWER(email) NOT IN (SELECT LOWER(email) FROM users);
            ");
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

    /**
     * Envoie un e-mail de bienvenue moderne et responsive aux nouveaux membres
     */
    public static function sendWelcomeEmail($userEmail, $userName = '') {
        $userEmail = trim(strtolower($userEmail));
        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'error' => 'Email invalide'];

        $cleanName = trim($userName);
        $displayName = !empty($cleanName) ? htmlspecialchars($cleanName) : 'Cher membre';
        $subject = "👋 Bienvenue chez KA Tech Store ! Votre compte est prêt";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bienvenue chez KA Tech Store</title>
</head>
<body style="margin: 0; padding: 0; background-color: #090d16; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9;">
  <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #090d16; padding: 30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #111827; border: 1px solid #1f2937; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
          
          <!-- En-tête avec Logo -->
          <tr>
            <td align="center" style="padding: 35px 20px 25px; background: linear-gradient(180deg, #1e293b 0%, #111827 100%); border-bottom: 1px solid #1f2937;">
              <img src="https://katechstore.net/ka-tech-logo-new.png" alt="KA Tech Store" style="max-height: 48px; width: auto; display: block; margin-bottom: 12px;">
              <span style="display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #3b82f6; background: rgba(59, 130, 246, 0.1); padding: 4px 12px; border-radius: 9999px; border: 1px solid rgba(59, 130, 246, 0.2);">Espace Membre Officiel</span>
            </td>
          </tr>

          <!-- Corps du message -->
          <tr>
            <td style="padding: 35px 30px 20px;">
              <h1 style="margin: 0 0 16px; font-size: 24px; font-weight: 800; color: #ffffff; line-height: 1.3;">
                Bienvenue dans l'univers KA Tech, {$displayName}&nbsp;!
              </h1>
              <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #94a3b8;">
                Nous sommes ravis de vous compter parmi nos membres. Votre compte <strong>KA Tech Store</strong> est désormais actif et vous donne accès au meilleur de la tech en Algérie.
              </p>

              <!-- Carte des Avantages Membre -->
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; margin-bottom: 30px; overflow: hidden;">
                <tr>
                  <td style="padding: 20px;">
                    <div style="margin-bottom: 16px; display: flex; align-items: flex-start;">
                      <span style="font-size: 20px; margin-right: 12px; line-height: 1;">📦</span>
                      <div>
                        <strong style="color: #f1f5f9; font-size: 14px; display: block; margin-bottom: 2px;">Suivi de commande direct</strong>
                        <span style="color: #94a3b8; font-size: 13px; line-height: 1.5;">Suivez l'expédition de vos colis partout en Algérie (58 wilayas) en direct depuis votre espace client.</span>
                      </div>
                    </div>

                    <div style="margin-bottom: 16px; display: flex; align-items: flex-start;">
                      <span style="font-size: 20px; margin-right: 12px; line-height: 1;">💻</span>
                      <div>
                        <strong style="color: #f1f5f9; font-size: 14px; display: block; margin-bottom: 2px;">PC Portables & Matériel Pro</strong>
                        <span style="color: #94a3b8; font-size: 13px; line-height: 1.5;">Accédez en priorité à nos arrivages d'ordinateurs portables, stations fixes et composants certifiés.</span>
                      </div>
                    </div>

                    <div style="margin-bottom: 16px; display: flex; align-items: flex-start;">
                      <span style="font-size: 20px; margin-right: 12px; line-height: 1;">🛠️</span>
                      <div>
                        <strong style="color: #f1f5f9; font-size: 14px; display: block; margin-bottom: 2px;">Configurateur PC sur mesure</strong>
                        <span style="color: #94a3b8; font-size: 13px; line-height: 1.5;">Créez la machine de vos rêves composant par composant selon vos besoins et votre budget.</span>
                      </div>
                    </div>

                    <div style="display: flex; align-items: flex-start;">
                      <span style="font-size: 20px; margin-right: 12px; line-height: 1;">🛡️</span>
                      <div>
                        <strong style="color: #f1f5f9; font-size: 14px; display: block; margin-bottom: 2px;">Garantie Matérielle & Support Dédié</strong>
                        <span style="color: #94a3b8; font-size: 13px; line-height: 1.5;">Chaque produit est testé et couvert par une garantie officielle avec service après-vente dédié.</span>
                      </div>
                    </div>
                  </td>
                </tr>
              </table>

              <!-- Bouton d'action principal -->
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td align="center" style="padding-bottom: 25px;">
                    <a href="https://katechstore.net" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; padding: 15px 36px; border-radius: 10px; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4); text-align: center;">
                      Commencer mes achats &rarr;
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Liens rapides -->
              <p style="margin: 0; text-align: center; font-size: 13px; color: #64748b;">
                <a href="https://katechstore.net/account" style="color: #3b82f6; text-decoration: none; margin: 0 10px;">Mon Compte</a> &bull;
                <a href="https://katechstore.net/builder" style="color: #3b82f6; text-decoration: none; margin: 0 10px;">Configurateur PC</a> &bull;
                <a href="https://katechstore.net/contact" style="color: #3b82f6; text-decoration: none; margin: 0 10px;">Contact & Support</a>
              </p>
            </td>
          </tr>

          <!-- Pied de page -->
          <tr>
            <td align="center" style="padding: 25px 20px; background-color: #0b0f19; border-top: 1px solid #1f2937; font-size: 12px; color: #64748b;">
              <p style="margin: 0 0 6px;"><strong>KA Tech Store</strong> &bull; La puissance tech entre vos mains</p>
              <p style="margin: 0 0 6px;">Alger, Alg&eacute;rie &bull; T&eacute;l : 0555 00 00 00 &bull; contact@katechstore.net</p>
              <p style="margin: 0;">Vous recevez ce courriel car vous venez de cr&eacute;er un compte sur katechstore.net.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        return self::sendBrevoSmtp($userEmail, $cleanName ?: 'Client KA Tech', $subject, $html);
    }

    /**
     * Traite une demande de réinitialisation de mot de passe (Forgot Password)
     */
    public static function requestPasswordReset($email) {
        $email = trim(strtolower($email));
        $genericResponse = [
            'success' => true,
            'ok' => true,
            'message' => 'Si cette adresse e-mail est associée à un compte KA Tech, vous recevrez un lien de réinitialisation dans quelques instants.'
        ];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $genericResponse;
        }

        $db = self::getDb();
        if (!$db) return $genericResponse;

        $userFound = null;
        try {
            $stmt = $db->prepare("SELECT id, name, email FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $userFound = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userFound) {
                $stmtC = $db->prepare("SELECT id, name, email FROM customers WHERE email = :email LIMIT 1");
                $stmtC->execute([':email' => $email]);
                $cust = $stmtC->fetch(PDO::FETCH_ASSOC);
                if ($cust) {
                    $userFound = [
                        'id' => $cust['id'],
                        'name' => $cust['name'] ?: 'Client',
                        'email' => $cust['email']
                    ];
                }
            }
        } catch (Exception $e) {
            error_log('[Forgot Password DB Error] ' . $e->getMessage());
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('c', time() + 3600); // 1 heure
        $now = date('c');

        $userId = $userFound['id'] ?? ('usr_' . substr($tokenHash, 0, 12));
        $userName = $userFound['name'] ?? 'Cher membre';

        try {
            $resetId = 'rst_' . bin2hex(random_bytes(8));
            $ins = @$db->prepare("INSERT INTO password_resets (id, user_id, email, token_hash, expires_at, created_at) VALUES (:id, :uid, :email, :thash, :exp, :now)");
            if ($ins) {
                $ins->execute([
                    ':id' => $resetId,
                    ':uid' => $userId,
                    ':email' => $email,
                    ':thash' => $tokenHash,
                    ':exp' => $expiresAt,
                    ':now' => $now
                ]);
            } else {
                $ins2 = @$db->prepare("INSERT INTO password_resets (id, user_id, token_hash, expires_at, created_at) VALUES (:id, :uid, :thash, :exp, :now)");
                if ($ins2) {
                    $ins2->execute([
                        ':id' => $resetId,
                        ':uid' => $userId,
                        ':thash' => $tokenHash,
                        ':exp' => $expiresAt,
                        ':now' => $now
                    ]);
                }
            }

            if ($userFound) {
                $upd = $db->prepare("UPDATE users SET reset_password_token = :token, reset_password_expires = :exp, updated_at = :now WHERE email = :email");
                $upd->execute([':token' => $tokenHash, ':exp' => $expiresAt, ':now' => $now, ':email' => $email]);
            }
        } catch (Exception $e) {
            error_log('[Save Reset Token Error] ' . $e->getMessage());
        }

        $resetUrl = "https://katechstore.net/reset-password?token=" . urlencode($rawToken);
        $subject = "🔐 Réinitialisation de votre mot de passe — KA Tech Store";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$subject}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #090d16; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9;">
  <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #090d16; padding: 30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #111827; border: 1px solid #1f2937; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
          
          <tr>
            <td align="center" style="padding: 30px 20px 20px; background: linear-gradient(180deg, #1e293b 0%, #111827 100%); border-bottom: 1px solid #1f2937;">
              <img src="https://katechstore.net/ka-tech-logo-new.png" alt="KA Tech Store" style="max-height: 48px; width: auto; display: block; margin-bottom: 12px;">
              <span style="display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #f59e0b; background: rgba(245, 158, 11, 0.1); padding: 4px 12px; border-radius: 9999px; border: 1px solid rgba(245, 158, 11, 0.2);">Sécurité du Compte</span>
            </td>
          </tr>

          <tr>
            <td style="padding: 35px 30px 25px;">
              <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #ffffff;">
                Demande de réinitialisation de mot de passe
              </h1>
              <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #cbd5e1;">
                Bonjour <strong>{$userName}</strong>,
              </p>
              <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #94a3b8;">
                Nous avons reçu une demande de réinitialisation du mot de passe pour votre compte KA Tech Store associé à l'adresse <strong>{$email}</strong>.
              </p>

              <!-- Bouton Reset -->
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td align="center" style="padding: 10px 0 30px;">
                    <a href="{$resetUrl}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; padding: 15px 36px; border-radius: 10px; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4); text-align: center;">
                      Réinitialiser mon mot de passe &rarr;
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Avertissement et Expiration -->
              <div style="background-color: #0f172a; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <p style="margin: 0 0 8px; font-size: 13px; color: #f1f5f9; font-weight: 600;">
                  ⏰ Ce lien est strictement personnel et expirera dans 1 heure.
                </p>
                <p style="margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.5;">
                  Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail en toute sécurité. Votre mot de passe actuel reste inchangé.
                </p>
              </div>

              <p style="margin: 0; font-size: 12px; color: #64748b; word-break: break-all; line-height: 1.5;">
                Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :<br>
                <a href="{$resetUrl}" style="color: #3b82f6; text-decoration: underline;">{$resetUrl}</a>
              </p>
            </td>
          </tr>

          <tr>
            <td align="center" style="padding: 25px 20px; background-color: #0b0f19; border-top: 1px solid #1f2937; font-size: 12px; color: #64748b;">
              <p style="margin: 0 0 6px;"><strong>KA Tech Store</strong> &bull; Alger, Algérie &bull; Tél : 0555 00 00 00 &bull; contact@katechstore.net</p>
              <p style="margin: 0;">Notification automatique de sécurité.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        self::sendBrevoSmtp($email, $userName, $subject, $html);
        return $genericResponse;
    }

    /**
     * Valide le jeton et met à jour le mot de passe
     */
    public static function confirmPasswordReset($token, $newPassword) {
        $token = trim($token);
        if (empty($token)) {
            return [
                'ok' => false,
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Jeton de réinitialisation manquant.'
                ]
            ];
        }

        if (strlen($newPassword) < 8) {
            return [
                'ok' => false,
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Le mot de passe doit comporter au moins 8 caractères.'
                ]
            ];
        }

        $db = self::getDb();
        if (!$db) {
            return [
                'ok' => false,
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Impossible de se connecter à la base de données.'
                ]
            ];
        }

        $tokenHash = hash('sha256', $token);
        $now = date('c');

        $resetRecord = null;
        try {
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE token_hash = :hash AND used_at IS NULL ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([':hash' => $tokenHash]);
            $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[Reset Verify Error] ' . $e->getMessage());
        }

        $userRecord = null;
        if (!$resetRecord) {
            try {
                $stmtU = $db->prepare("SELECT * FROM users WHERE reset_password_token = :token LIMIT 1");
                $stmtU->execute([':token' => $tokenHash]);
                $userRecord = $stmtU->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
        } else {
            try {
                $stmtU = $db->prepare("SELECT * FROM users WHERE email = :email OR id = :uid LIMIT 1");
                $stmtU->execute([':email' => $resetRecord['email'], ':uid' => $resetRecord['user_id']]);
                $userRecord = $stmtU->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
        }

        $expiresAt = $resetRecord['expires_at'] ?? ($userRecord['reset_password_expires'] ?? null);
        if (!$expiresAt || strtotime($expiresAt) < time()) {
            return [
                'ok' => false,
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Lien de réinitialisation invalide, expiré, ou déjà utilisé. Demandez-en un nouveau depuis « mot de passe oublié ».'
                ]
            ];
        }

        $targetEmail = $resetRecord['email'] ?? ($userRecord['email'] ?? null);
        $targetUserId = $resetRecord['user_id'] ?? ($userRecord['id'] ?? null);
        $targetName = $userRecord['name'] ?? 'Client KA Tech';

        if (empty($targetEmail)) {
            return [
                'ok' => false,
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Compte utilisateur introuvable.'
                ]
            ];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            if ($userRecord) {
                $upd = $db->prepare("UPDATE users SET password_hash = :hash, reset_password_token = NULL, reset_password_expires = NULL, updated_at = :now WHERE id = :id");
                $upd->execute([':hash' => $hashedPassword, ':now' => $now, ':id' => $userRecord['id']]);
            } else {
                $newUid = $targetUserId ?: ('usr_' . bin2hex(random_bytes(8)));
                $ins = $db->prepare("INSERT INTO users (id, name, email, password_hash, role, active, created_at, updated_at) VALUES (:id, :name, :email, :hash, 'customer', 1, :now, :now)");
                $ins->execute([
                    ':id' => $newUid,
                    ':name' => $targetName,
                    ':email' => $targetEmail,
                    ':hash' => $hashedPassword,
                    ':now' => $now
                ]);
            }

            $db->prepare("UPDATE password_resets SET used_at = :now WHERE token_hash = :hash")->execute([':now' => $now, ':hash' => $tokenHash]);
        } catch (Exception $e) {
            error_log('[Reset Update Error] ' . $e->getMessage());
            return [
                'ok' => false,
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Erreur lors de la mise à jour du mot de passe.'
                ]
            ];
        }

        self::sendPasswordResetSecurityConfirmation($targetEmail, $targetName);

        return [
            'ok' => true,
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.'
        ];
    }

    /**
     * Envoie un email de notification de sécurité après le changement de mot de passe
     */
    public static function sendPasswordResetSecurityConfirmation($userEmail, $userName = '') {
        $userEmail = trim(strtolower($userEmail));
        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) return false;

        $displayName = !empty($userName) ? htmlspecialchars(trim($userName)) : 'Cher membre';
        $subject = "🛡️ Confirmation : Votre mot de passe KA Tech Store a été modifié";
        $dateFormatted = date('d/m/Y à H:i') . ' (heure locale)';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$subject}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #090d16; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9;">
  <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #090d16; padding: 30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #111827; border: 1px solid #1f2937; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
          
          <tr>
            <td align="center" style="padding: 30px 20px 20px; background: linear-gradient(180deg, #1e293b 0%, #111827 100%); border-bottom: 1px solid #1f2937;">
              <img src="https://katechstore.net/ka-tech-logo-new.png" alt="KA Tech Store" style="max-height: 48px; width: auto; display: block; margin-bottom: 12px;">
              <span style="display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 4px 12px; border-radius: 9999px; border: 1px solid rgba(16, 185, 129, 0.2);">Sécurité Confirmée</span>
            </td>
          </tr>

          <tr>
            <td style="padding: 35px 30px 25px;">
              <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #ffffff;">
                Mot de passe mis à jour avec succès
              </h1>
              <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #cbd5e1;">
                Bonjour <strong>{$displayName}</strong>,
              </p>
              <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #94a3b8;">
                Le mot de passe associé à votre compte KA Tech Store (<strong>{$userEmail}</strong>) a été modifié le <strong>{$dateFormatted}</strong>.
              </p>

              <!-- Bouton Connexion -->
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td align="center" style="padding: 10px 0 25px;">
                    <a href="https://katechstore.net/auth" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; padding: 15px 36px; border-radius: 10px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); text-align: center;">
                      Se connecter à mon compte &rarr;
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Alerte si non autorisé -->
              <div style="background-color: #0f172a; border-left: 4px solid #ef4444; padding: 16px; border-radius: 8px;">
                <p style="margin: 0 0 8px; font-size: 13px; color: #f87171; font-weight: 700;">
                  ⚠️ Vous n'avez pas demandé ce changement ?
                </p>
                <p style="margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.5;">
                  Si vous n'êtes pas à l'origine de cette modification, votre compte a peut-être été compromis. Contactez immédiatement notre assistance à <strong>kaa.tech.48@gmail.com</strong> ou au <strong>0555 00 00 00</strong> afin de sécuriser vos accès.
                </p>
              </div>
            </td>
          </tr>

          <tr>
            <td align="center" style="padding: 25px 20px; background-color: #0b0f19; border-top: 1px solid #1f2937; font-size: 12px; color: #64748b;">
              <p style="margin: 0 0 6px;"><strong>KA Tech Store</strong> &bull; Alger, Algérie &bull; Tél : 0555 00 00 00 &bull; contact@katechstore.net</p>
              <p style="margin: 0;">Notification automatique de sécurité.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        return self::sendBrevoSmtp($userEmail, $displayName, $subject, $html);
    }

    /**
     * Vérifie si un utilisateur possède un mot de passe mis à jour en local
     */
    public static function verifyLocalUserLogin($email, $password) {
        $email = trim(strtolower($email));
        if (empty($email) || empty($password)) return null;

        $db = self::getDb();
        if (!$db) return null;

        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && !empty($user['password_hash'])) {
                if (password_verify($password, $user['password_hash'])) {
                    return [
                        'data' => [
                            'user' => [
                                'id' => $user['id'],
                                'name' => $user['name'] ?: 'Client KA Tech',
                                'email' => $user['email'],
                                'role' => $user['role'] ?: 'customer'
                            ],
                            'expiresAt' => date('c', time() + 86400 * 7)
                        ]
                    ];
                }
            }
        } catch (Exception $e) {
            error_log('[Verify Local User Error] ' . $e->getMessage());
        }

        return null;
    }


    /**
     * Vérifie si un e-mail existe dans la base de données (users, customers ou subscribers)
     */
    public static function checkUserExists($email) {
        $email = trim(strtolower($email));
        if (empty($email)) return false;

        $db = self::getDb();
        if (!$db) return false;

        try {
            // 1. Table users
            $stmt = $db->prepare("SELECT id, name, email, password_hash, role FROM users WHERE LOWER(email) = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($u) return $u;

            // 2. Table customers
            $stmtC = $db->prepare("SELECT id, name, email FROM customers WHERE LOWER(email) = :email LIMIT 1");
            $stmtC->execute([':email' => $email]);
            $c = $stmtC->fetch(PDO::FETCH_ASSOC);
            if ($c) {
                $now = date('c');
                $ins = $db->prepare("INSERT OR IGNORE INTO users (id, name, email, password_hash, role, active, created_at, updated_at) VALUES (:id, :name, :email, '', 'customer', 1, :now, :now)");
                $ins->execute([':id' => $c['id'], ':name' => $c['name'], ':email' => $c['email'], ':now' => $now]);
                return $c;
            }

            // 3. Table subscribers
            $stmtS = $db->prepare("SELECT id, email, source FROM subscribers WHERE LOWER(email) = :email LIMIT 1");
            $stmtS->execute([':email' => $email]);
            $s = $stmtS->fetch(PDO::FETCH_ASSOC);
            if ($s && ($s['source'] === 'register' || $s['source'] === 'checkout')) {
                $now = date('c');
                $ins = $db->prepare("INSERT OR IGNORE INTO users (id, name, email, password_hash, role, active, created_at, updated_at) VALUES (:id, 'Client KA Tech', :email, '', 'customer', 1, :now, :now)");
                $ins->execute([':id' => $s['id'], ':email' => $s['email'], ':now' => $now]);
                return $s;
            }
        } catch (Exception $e) {
            error_log('[Check User Exists Error] ' . $e->getMessage());
        }

        return false;
    }

}
