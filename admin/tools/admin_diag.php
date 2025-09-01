<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$baseDir = dirname(__DIR__, 1);
$configPath = $baseDir . DIRECTORY_SEPARATOR . 'config.php';
if (file_exists($configPath)) {
    include $configPath; // provides $conn, getCurrentUserUnique(), isAdmin()
} else {
    die('Config not found');
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Diagnostics</title>
  <style>
    body { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; background:#0b0f14; color:#e5e7eb; padding:24px; line-height:1.5; }
    .card { background:#111827; border:1px solid #374151; border-radius:12px; padding:16px; margin-bottom:16px; }
    h2 { margin:0 0 8px; font-size:18px; color:#a78bfa; }
    pre { white-space:pre-wrap; word-break:break-word; }
    .ok { color:#34d399; font-weight:700; }
    .bad { color:#f87171; font-weight:700; }
    .muted { color:#9ca3af; }
    code { color:#93c5fd; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Sesja</h2>
    <pre><?php echo htmlspecialchars(print_r($_SESSION, true)); ?></pre>
  </div>

  <div class="card">
    <h2>Cookies</h2>
    <pre class="muted"><?php echo htmlspecialchars(print_r($_COOKIE, true)); ?></pre>
  </div>

  <div class="card">
    <h2>Detekcja użytkownika</h2>
    <?php
      $detected = null;
      if (function_exists('getCurrentUserUnique')) {
        $detected = getCurrentUserUnique();
      }
      $is_admin = function_exists('isAdmin') ? (isAdmin() ? 'TAK' : 'NIE') : 'N/D';
    ?>
    <p>getCurrentUserUnique(): <code><?php echo htmlspecialchars((string)$detected); ?></code></p>
    <p>isAdmin(): <span class="<?php echo $is_admin==='TAK'?'ok':'bad'; ?>"><?php echo $is_admin; ?></span></p>
  </div>

  <div class="card">
    <h2>Baza danych</h2>
    <?php
      if (isset($conn) && $conn) {
        try {
          $db = null; $hasTable = null;
          if ($res = $conn->query("SELECT DATABASE() AS db")) {
            $r = $res->fetch_assoc();
            $db = $r ? $r['db'] : null;
          }
          if ($res2 = $conn->query("SHOW TABLES LIKE 'user_details'")) {
            $hasTable = ($res2->num_rows > 0);
          }
          echo '<p><b>Aktywna baza:</b> <code>' . htmlspecialchars((string)$db) . '</code></p>';
          echo '<p><b>Tabela user_details:</b> ' . ($hasTable ? '<span class="ok">ISTNIEJE</span>' : '<span class="bad">BRAK</span>') . '</p>';

          // szybki test pod bieżącego usera
          if (!empty($detected)) {
            $cnt = null; $adm = null;
            if ($s = $conn->prepare("SELECT COUNT(*) AS cnt FROM user_details WHERE user_unique = ?")) {
              $s->bind_param('s', $detected);
              $s->execute();
              $rs = $s->get_result();
              $row = $rs ? $rs->fetch_assoc() : null;
              $cnt = $row ? (int)$row['cnt'] : null;
            }
            if ($s2 = $conn->prepare("SELECT admin_access FROM user_details WHERE user_unique = ? LIMIT 1")) {
              $s2->bind_param('s', $detected);
              $s2->execute();
              $rs2 = $s2->get_result();
              $row2 = $rs2 ? $rs2->fetch_assoc() : null;
              $adm = $row2 && isset($row2['admin_access']) ? (string)$row2['admin_access'] : null;
            }
            echo '<p><b>COUNT(user_unique=detected):</b> ' . htmlspecialchars((string)$cnt) . '</p>';
            echo '<p><b>admin_access (detected):</b> <code>' . htmlspecialchars((string)$adm) . '</code></p>';
          }
        } catch (Throwable $e) {
          echo '<p class="bad">Błąd DB info: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
      } else {
        echo '<p class="bad">Brak połączenia z DB.</p>';
      }
    ?>
  </div>

  <div class="card">
    <h2>user_details</h2>
    <?php
      $row = null; $source = null;
      if (isset($conn) && $conn) {
        // 1) po user_unique
        if (!empty($detected)) {
          try {
            $stmt = $conn->prepare("SELECT user_unique, username, admin_access FROM user_details WHERE user_unique = ? LIMIT 1");
            if ($stmt) {
              $stmt->bind_param('s', $detected);
              $stmt->execute();
              $res = $stmt->get_result();
              $row = $res ? $res->fetch_assoc() : null;
              if ($row) { $source = 'user_unique'; }
            }
          } catch (Throwable $e) {
            echo '<p class="bad">Błąd zapytania (user_unique): ' . htmlspecialchars($e->getMessage()) . '</p>';
          }
        }

        // 2) po username (jeśli brak wyniku)
        if (!$row && !empty($_SESSION['username'])) {
          try {
            $u = $_SESSION['username'];
            $stmt = $conn->prepare("SELECT user_unique, username, admin_access FROM user_details WHERE username = ? LIMIT 1");
            if ($stmt) {
              $stmt->bind_param('s', $u);
              $stmt->execute();
              $res = $stmt->get_result();
              $tmp = $res ? $res->fetch_assoc() : null;
              if ($tmp) { $row = $tmp; $source = 'username'; }
            }
          } catch (Throwable $e) {
            echo '<p class="bad">Błąd zapytania (username): ' . htmlspecialchars($e->getMessage()) . '</p>';
          }
        }

        // 3) po steam kolumnach (jeśli brak wyniku)
        if (!$row) {
          $steamSessionKeys = ['steamid','steam_id','steamid64'];
          $steamCols = ['steamid','steam_id','steamid64'];
          foreach ($steamSessionKeys as $ssk) {
            if (!empty($_SESSION[$ssk])) {
              $sid = $_SESSION[$ssk];
              foreach ($steamCols as $col) {
                try {
                  $stmt = $conn->prepare("SELECT user_unique, username, admin_access FROM user_details WHERE $col = ? LIMIT 1");
                  if ($stmt) {
                    $stmt->bind_param('s', $sid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $tmp = $res ? $res->fetch_assoc() : null;
                    if ($tmp) { $row = $tmp; $source = 'steam:' . $col; break 2; }
                  }
                } catch (Throwable $e) {
                  // ignoruj, próbujemy dalej
                }
              }
            }
          }
        }
      }

      if ($row) {
        echo '<p class="ok">Znaleziono rekord (źródło: <code>' . htmlspecialchars($source) . '</code>)</p>';
        echo '<pre>' . htmlspecialchars(print_r($row, true)) . '</pre>';
      } else {
        echo '<p class="bad">Brak dopasowanego rekordu w user_details po user_unique/username/steam.</p>';
      }
    ?>
  </div>

  <div class="card">
    <h2>admins (fallback)</h2>
    <?php
      $adminRow = null;
      if (!empty($detected) && isset($conn) && $conn) {
        try {
          $stmt2 = $conn->prepare("SELECT * FROM admins WHERE user_unique = ? LIMIT 1");
          if ($stmt2) {
            $stmt2->bind_param('s', $detected);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            $adminRow = $res2 ? $res2->fetch_assoc() : null;
          } else {
            echo '<p class="muted">Tabela <code>admins</code> może nie istnieć (brak przygotowania zapytania).</p>';
          }
        } catch (Throwable $e) {
          echo '<p class="muted">Tabela <code>admins</code> może nie istnieć: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
      }
      if ($adminRow) {
        echo '<pre>' . htmlspecialchars(print_r($adminRow, true)) . '</pre>';
      } else {
        echo '<p class="muted">Brak wpisu w <code>admins</code> dla: <code>' . htmlspecialchars((string)$detected) . '</code></p>';
      }
    ?>
  </div>

  <div class="card">
    <h2>Podsumowanie</h2>
    <ul>
      <li>Upewnij się, że wiersz w <code>user_details</code> dla wykrytego <code>user_unique</code> ma <code>admin_access=1</code>.</li>
      <li>Jeśli identyfikacja idzie po SteamID/username/email – sprawdź, czy te pola istnieją i się zgadzają.</li>
    </ul>
  </div>
</body>
</html>
