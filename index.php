<?php 
session_start();
include 'server/conn.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_unique']) && !empty($_SESSION['user_unique']);

if ($is_logged_in) {
    $user_unique = $_SESSION['user_unique'];
    
    // Sprawdź czy połączenie z bazą danych istnieje
    if ($conn && !$conn->connect_error) {
        // Zmniejsz liczbę zapytań: pobierz wszystkie potrzebne pola naraz
        $user_details = $conn->query("SELECT `avatar`,`current_game_status`,`log_out_globally` FROM user_details WHERE user_unique = '$user_unique' LIMIT 1");
        $row = $user_details ? $user_details->fetch_assoc() : null;
        if ($row && isset($row['log_out_globally']) && (int)$row['log_out_globally'] === 1) {
            header("Location: /auth/logout.php");
            exit();
        }
    } else {
        // Baza danych niedostępna - ustaw domyślne wartości
        $row = null;
    }
    
    // Check current game status (legacy code - may not be needed for case opening)
    if (isset($row['current_game_status']) && $row['current_game_status'] == '2') {
        if (isset($_GET['p'])) {
            if ($_GET['p'] === 'currentBattle') {
            } else {
                header("Location: /?p=currentBattle");
            }
        } else {
            header("Location: /?p=currentBattle");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:site_name" content="Cashplay">
    <meta property="og:title" content="Cashplay">
    <meta property="og:description" content="Your Page Description Here">
    <meta property="og:url" content="https://www.cashplay.in/">
    <meta property="og:image" content="https://www.cashplay.in/assets/img/favicon_io/favicon-16x16.png">

    <!-- Resource Hints: speed up third-party connections -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://pro.fontawesome.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <meta http-equiv="x-dns-prefetch-control" content="on">
    <link rel="dns-prefetch" href="//cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="dns-prefetch" href="//pro.fontawesome.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">


    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="/assets/img/favicon_io/site.webmanifest?v=2025-08-10-12-56">
    <title>CashPlay</title>
    <!-- // TAILWIND CSS -->
    <script>
        // Ukryj tylko ostrzeżenie Tailwind o CDN w produkcji - PRZED załadowaniem Tailwind
        const originalWarn = console.warn;
        console.warn = function(...args) {
            const message = args.join(' ');
            if (message.includes('tailwindcss.com should not be used in production') || 
                message.includes('cdn.tailwindcss.com should not be used in production')) {
                return; // Nie pokazuj tego ostrzeżenia
            }
            originalWarn.apply(console, args); // Pokaż inne ostrzeżenia normalnie
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- // FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- // FONT AWESOME (sync for consistent initial paint) -->
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v6.0.0-beta3/css/all.css">

    <!-- // JQUERY (defer to avoid parser blocking) -->
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="assets/css/theme.css" />
    <!-- Preload critical logo image to stabilize header -->
    <link rel="preload" as="image" href="assets/img/logo4.png" imagesrcset="assets/img/logo4.png 1x" />
    
    <!-- Init home dynamic content without preloader -->
    <script>
      (function(){
        // WYŁĄCZONO - konflikt z nową funkcją loadRecentDropsData w home.php
        // let recentDropsLoaded = false;
        // function tryLoadRecent(){
        //   if (typeof loadRecentDrops === 'function' && !recentDropsLoaded) {
        //     recentDropsLoaded = true; try { loadRecentDrops(); } catch(e){}
        //   }
        // }
        // if (document.readyState === 'loading') {
        //   document.addEventListener('DOMContentLoaded', tryLoadRecent);
        // } else {
        //   tryLoadRecent();
        // }
      })();
    </script>

    <style>
        *::-webkit-scrollbar {
            display: none;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }


        /* Animacje kropek i kostki usunięte */
    </style>
    <!-- preloader styles removed -->
</head>

<?php 
  $is_home_page = !isset($_GET['p']) || $_GET['p'] === 'home';
  $body_classes = 'theme-cashplay select-none min-h-screen overflow-auto';
  if ($is_home_page) { $body_classes .= ' is-home'; }
?>
<body class="<?php echo $body_classes; ?>" id="body">

    


    <!-- // HEADER -->
    <?php include 'components/header.php' ?>

    <?php 
    // Ładuj Recent Drops dla zalogowanych użytkowników na wszystkich stronach
    if ($is_logged_in) { 
        include 'components/global_recent_drops.php'; 
    } 
    ?>

    <!-- Global Container -->
    <div class="app-container py-6">


    <!-- Content for Authenticated Users -->
    <?php
    if ($is_logged_in && isset($row['current_game_status']) && $row['current_game_status'] == '1') {

        if (isset($_GET['p'])) {
            if ($_GET['p'] === 'currentBattle') {
            } else {
    ?>
                <div id="CurrentProcessingBattleNot" class="fixed hidden top-0 w-full justify-center h-screen bg-[#a8a8a8a0] z-[99]">
                    <div class="rounded-b-xl bg-white p-5 flex justify-between items-center flex-col gap-2 relative shadow-2xl h-fit top-0">
                        <div class="flex items-center gap-3 justify-between w-full px-[3vw] sm:px-6">
                            <div class="flex items-center gap-10 text-lg font-semibold">
                                <img src="assets/img/avatar/<?php echo $row['avatar'] ?>" alt="C Coin" class="object-cover w-10 h-10 rounded-full border-2" loading="lazy" decoding="async" fetchpriority="low">
                                <div class="flex items-center gap-2">
                                    <i class="fa-duotone fa-swords text-[3.5vw] sm:text-lg"></i>
                                    <p id="CurrentProcessingBattleNotStatus" class="text-[2.5vw] sm:text-sm text-gray-400 font-medium">Waiting</p>
                                </div>
                                <img src="assets/img/avatar/user.png" alt="C Coin" class="object-cover w-10 h-10 rounded-full border-2" loading="lazy" decoding="async" fetchpriority="low">
                            </div>
                        </div>
                        <button onclick="removeBattle()" class="text-[2.5vw] sm:text-sm font-semibold tracking-tight bg-gray-100 text-black rounded-xl px-5 py-2 hover:shadow-sm">Cancel</button>
                    </div>
                </div>
            <?php
            }
        } else {
            ?>
            <div id="CurrentProcessingBattleNot" class="fixed hidden top-0 w-full justify-center h-screen bg-[#a8a8a8a0] z-[99]">
                <div class="rounded-b-xl bg-white p-5 flex justify-between items-center flex-col gap-2 relative shadow-2xl h-fit top-0">
                    <div class="flex items-center gap-3 justify-between w-full px-[3vw] sm:px-6">
                        <div class="flex items-center gap-10 text-lg font-semibold">
                            <img src="assets/img/avatar/<?php echo $row['avatar'] ?>" alt="C Coin" class="object-cover w-10 h-10 rounded-full border-2">
                            <div class="flex items-center gap-2">
                                <i class="fa-duotone fa-swords text-[3.5vw] sm:text-lg"></i>
                                <p id="CurrentProcessingBattleNotStatus" class="text-[2.5vw] sm:text-sm text-gray-400 font-medium">Waiting</p>
                            </div>
                            <img src="assets/img/avatar/user.png" alt="C Coin" class="object-cover w-10 h-10 rounded-full border-2">
                        </div>
                    </div>
                    <button onclick="removeBattle()" class="text-[2.5vw] sm:text-sm font-semibold tracking-tight bg-gray-100 text-black rounded-xl px-5 py-2 hover:shadow-sm">Cancel</button>
                </div>
            </div>
        <?php
        }
        ?>
        <script>
            if (document.getElementById("CurrentProcessingBattleNot")) {
                document.getElementById("CurrentProcessingBattleNot").style.display = "flex";
            }
            setInterval(() => {
                battleCheck();
            }, 1000);
        </script>
    <?php }



    if (isset($_GET['p'])) {
        if ($_GET['p'] === 'home') {
            include 'pages/home.php';
        } else if ($_GET['p'] === 'profile') {
            include 'pages/profile.php';
        } else if ($_GET['p'] === 'history') {
            include 'pages/history.php';
        } else if ($_GET['p'] === 'transaction') {
            include 'pages/transaction.php';
        } else if ($_GET['p'] === 'refer') {
            include 'pages/refer.php';
        } else if ($_GET['p'] === 'settings') {
            include 'pages/settings.php';
        } else if ($_GET['p'] === 'wallet') {
            include 'pages/wallet.php';
        } else if ($_GET['p'] === 'support') {
            include 'pages/support.php';
        } else if ($_GET['p'] === 'topUp') {
            include 'pages/topUp.php';
        } else if ($_GET['p'] === 'withdraw') {
            include 'pages/withdraw.php';
        } else if ($_GET['p'] === 'getGameUID') {
            include 'pages/getGameUID.php';
        } else if ($_GET['p'] === 'myRefer') {
            include 'pages/myRefer.php';
        } else if ($_GET['p'] === 'battles') {
            include 'pages/battles.php';
        } else if ($_GET['p'] === 'joinBattles') {
            include 'pages/joinBattles.php';
        } else if ($_GET['p'] === 'joinBattlesAll') {
            include 'pages/joinBattlesAll.php';
        } else if ($_GET['p'] === 'currentBattle') {
            include 'pages/currentBattle.php';
        } else if ($_GET['p'] === 'settings') {
            include 'pages/settings.php';
        } else if ($_GET['p'] === 'application') {
            include 'pages/application.php';
        } else if ($_GET['p'] === 'security') {
            include 'pages/security.php';
        } else if ($_GET['p'] === 'securityTips') {
            include 'pages/securityTips.php';
        } else if ($_GET['p'] === 'strength') {
            include 'pages/strength.php';
        } else if ($_GET['p'] === 'logOutGlobally') {
            include 'pages/logOutGlobally.php';
        } else if ($_GET['p'] === 'getGameUID') {
            include 'pages/getGameUID.php';
        } else if ($_GET['p'] === 'loginActivity') {
            include 'pages/loginActivity.php';
        } else if ($_GET['p'] === 'changePass') {
            include 'pages/changePass.php';
        } else if ($_GET['p'] === 'passGenerate') {
            include 'pages/passGenerate.php';
        } else if ($_GET['p'] === 'deleteAccount') {
            include 'pages/deleteAccount.php';
        } else if ($_GET['p'] === 'feedback') {
            include 'pages/feedback.php';
        } else if ($_GET['p'] === 'rateTheApp') {
            include 'pages/rateTheApp.php';
        } else if ($_GET['p'] === 'faq') {
            include 'pages/faq.php';
        } else if ($_GET['p'] === 'paymentByQR') {
            include 'pages/paymentByQR.php';
        } else if ($_GET['p'] === 'privacyPolicy') {
            include 'pages/document/privacyPolicy.php';
        } else if ($_GET['p'] === 'refundPolicy') {
            include 'pages/document/refundPolicy.php';
        } else if ($_GET['p'] === 'termsAndCondition') {
            include 'pages/document/termsAndCondition.php';
        } else if ($_GET['p'] === 'aboutUs') {
            include 'pages/document/aboutUs.php';
        } else if ($_GET['p'] === 'userGuide') {
            include 'pages/document/userGuide.php';
        } else if ($_GET['p'] === 'legalTerms') {
            include 'pages/document/legalTerms.php';
        } else if (strpos($_GET['p'], 'case/') === 0) {
            // Routing dla stron skrzynek: case/Elite_Vault, case/Diamond_Vault itp.
            $case_name = substr($_GET['p'], 5); // Usuń "case/" z początku
            $case_file = 'pages/cases/' . $case_name . '.php';
            
            if (file_exists($case_file)) {
                include $case_file;
            } else {
                // Jeśli plik nie istnieje, użyj uniwersalnej strony skrzynki
                $_GET['case_name'] = $case_name;
                include 'pages/case.php';
            }
        } else {
            include 'pages/home.php';
        }
    } else {
        include 'pages/home.php';
    }
    ?>

    <!-- End Global Container -->
    </div>

    <div id="logOutModal" class="hidden size-full fixed bottom-0 start-0 h-screen justify-center items-center z-[80] overflow-x-hidden overflow-y-auto bg-[#3c3c3c62]">
        <div class="duration-300 sm:max-w-lg sm:w-full m-3 sm:mx-auto">
            <div class="relative flex flex-col bg-white shadow-2xl rounded-xl">
                <div class="absolute top-2 end-2">
                    <button type="button" onclick="document.getElementById('logOutModal').style.display='none';" class="flex justify-center items-center size-7 text-sm font-semibold rounded-lg border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none">
                        <i class="fa-solid fa-close"></i>
                    </button>
                </div>

                <div class="text-center overflow-y-auto p-10">
                    <h3 class="mb-2 text-2xl font-bold text-gray-800">
                        <i class="fa-solid fa-arrow-right-to-bracket text-3xl py-3 px-4 aspect-square shadow-md w-fit rounded-full mb-1 flex justify-center items-center mx-auto"></i>
                    </h3>
                    <p class="text-gray-500 mt-8 px-10 text-center">
                        Are you sure you want to <b>logout</b> CASHPLAY account?
                    </p>
                </div>

                <div class="flex items-center">
                    <button type="button" onclick="document.getElementById('logOutModal').style.display='none';" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-es-xl border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none">
                        Cancel
                    </button>
                    <button type="button" onclick="location.assign('/auth/logout.php')" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-ee-xl border border-transparent bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50 disabled:pointer-events-none">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </div>







    
    <!-- End of main content -->

    <!-- Custom Notification System -->
    <div id="notification-container" class="fixed bottom-4 right-4 z-[9999] space-y-2"></div>

    <script>

        // Custom Notification System
        function showNotification(message, type = 'info', duration = 4000) {
            const container = document.getElementById('notification-container');
            if (!container) return;
            
            const notification = document.createElement('div');
            const notificationId = 'notification-' + Date.now();
            notification.id = notificationId;
            
            const typeClasses = {
                'success': 'bg-green-600 border-green-500',
                'error': 'bg-red-600 border-red-500',
                'warning': 'bg-yellow-600 border-yellow-500',
                'info': 'bg-blue-600 border-blue-500'
            };
            
            const typeIcons = {
                'success': 'fas fa-check-circle',
                'error': 'fas fa-exclamation-circle',
                'warning': 'fas fa-exclamation-triangle',
                'info': 'fas fa-info-circle'
            };
            
            notification.className = `${typeClasses[type] || typeClasses.info} text-white px-6 py-4 rounded-lg shadow-2xl border-l-4 transform translate-x-full transition-all duration-300 ease-in-out max-w-sm`;
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="${typeIcons[type] || typeIcons.info} text-xl mr-3"></i>
                    <div class="flex-1">
                        <p class="font-medium">${message}</p>
                    </div>
                    <button onclick="removeNotification('${notificationId}')" class="ml-3 text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 10);
            
            // Auto remove
            setTimeout(() => {
                removeNotification(notificationId);
            }, duration);
        }
        
        function removeNotification(notificationId) {
            const notification = document.getElementById(notificationId);
            if (notification) {
                notification.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }
        
        // Replace all alert() calls with custom notifications
        window.alert = function(message) {
            showNotification(message, 'info');
        };
    </script>

    <!-- Cache-busting to avoid stale app.js causing data.forEach errors -->
    <script defer src="assets/js/app.js?v=2025-01-09-12-30"></script>

    <?php 
    // Ładuj globalny inventory dla zalogowanych użytkowników na wszystkich stronach
    if ($is_logged_in) { 
        include 'components/global_inventory.php'; 
    } 
    ?>
    
</body>

</html>