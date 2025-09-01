<?php

include 'config.php';



if (!function_exists('isAdmin') || !isAdmin()) {
    
    header('Location: /');
    exit;
}


if (!defined('IN_ADMIN')) {
    define('IN_ADMIN', true);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
      $script_path = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
      $base_path = str_replace(['/server','/components'], '', $script_path);
      if ($base_path === '\\' || $base_path === '/') { $base_path = ''; }
      // korzystamy z serwowanego manifestu z poprawnym MIME i cache-bustingiem
      // UWAGA: ścieżka absolutna z admin=1, by serwować manifest admina
      $manifest_href = '/server/manifest.php?admin=1&v=3';
    ?>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="<?= htmlspecialchars($manifest_href, ENT_QUOTES, 'UTF-8') ?>">
    <title>CashPlay Admin</title>

    
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.3/dist/full.min.css" rel="stylesheet" type="text/css" />
    
    <script>
      (function(){
        try {
          var __origWarn = console.warn;
          window.__origWarnTailwind = __origWarn;
          console.warn = function(msg){
            try {
              if (typeof msg === 'string' && msg.indexOf('cdn.tailwindcss.com should not be used in production') !== -1) return;
            } catch(e) {}
            return __origWarn.apply(console, arguments);
          };
        } catch(e) {}
      })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      // Przywróć oryginalne console.warn po załadowaniu Tailwinda
      try { if (window.__origWarnTailwind) console.warn = window.__origWarnTailwind; } catch(e) {}
    </script>


    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v6.0.0-beta3/css/all.css">

    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    
    <link rel="stylesheet" href="../assets/css/theme.css" />

    <style>
        *::-webkit-scrollbar {
            display: none;
        }

        * {
            font-family: 'Poppins', sans-serif;
            line-height: 1.5;
            word-break: normal;
            overflow-wrap: anywhere;
            scroll-behavior: smooth;
        }
    </style>
 </head>

 <body class="theme-cashplay admin-no-scale select-none min-h-screen overflow-auto">

    <div class="flex min-h-screen overflow-hidden">
        <?php include 'components/sidebar.php' ?>

        <div class="flex flex-col flex-1 w-0 overflow-hidden">
            <main class="relative flex-1 overflow-y-auto focus:outline-none min-h-screen bg-transparent">
                <div class="py-6 app-container">
                    <div class="w-full text-sm">

                        <?php

                        if (isset($_GET['page'])) {
                            if ($_GET['page'] === 'dashboard') {
                                include 'pages/dashboard.php';
                            } else if ($_GET['page'] === 'ratings') {
                                include 'pages/ratings.php';
                            } else if ($_GET['page'] === 'feedback') {
                                include 'pages/feedback.php';
                            } else if ($_GET['page'] === 'allPlayers') {
                                include 'pages/allPlayers_simple_final.php';
                            } else if ($_GET['page'] === 'test') {
                                include 'pages/test.php';
                            } else if ($_GET['page'] === 'cases') {
                                include 'pages/cases.php';
                            } else if ($_GET['page'] === 'support') {
                                include 'pages/support.php';
                            } else if ($_GET['page'] === 'invoices') {
                                include 'pages/invoices.php';
                            } else if ($_GET['page'] === 'withdraw') {
                                include 'pages/withdraw.php';
                            } else if ($_GET['page'] === 'deposit') {
                                include 'pages/deposit.php';
                            } else if ($_GET['page'] === 'coupon') {
                                include 'pages/coupon.php';
                            } else if ($_GET['page'] === 'documents') {
                                include 'pages/documents.php';
                            } else if ($_GET['page'] === 'items') {
                                include 'pages/items.php';
                            } else if ($_GET['page'] === 'users') {
                                include 'pages/users.php';
                            } else if ($_GET['page'] === 'admins') {
                                include 'pages/admins.php';
                            } else if ($_GET['page'] === 'settings') {
                                include 'pages/settings.php';
                            }
                        } else {
                            include 'pages/dashboard.php';
                        }

                        ?>

                    </div>
                </div>
            </main>
        </div>
    </div>



    <script src="assets/js/sidebar.js?v=<?php echo time(); ?>"></script>


</body>

</html>