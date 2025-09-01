<?php
// Check if user is logged in for header display
$header_is_logged_in = isset($_SESSION['user_unique']) && !empty($_SESSION['user_unique']);

// POBIERZ RZECZYWISTE SALDO Z BAZY DANYCH
$user_balance = 0; // Domyślne saldo

if ($header_is_logged_in && isset($conn)) {
    $user_unique = $_SESSION['user_unique'];
    
    // Pobierz rzeczywiste saldo użytkownika z bazy danych
    $balance_query = $conn->prepare("SELECT total_balance FROM user_details WHERE user_unique = ?");
    if ($balance_query) {
        $balance_query->bind_param("s", $user_unique);
        $balance_query->execute();
        $balance_result = $balance_query->get_result();
        
        if ($balance_result && $balance_result->num_rows > 0) {
            $balance_data = $balance_result->fetch_assoc();
            $user_balance = floatval($balance_data['total_balance'] ?? 0);
        }
        $balance_query->close();
    }
}

// Include global components
if ($header_is_logged_in) {
    // Global Recent Drops will be included at the end of body
    $GLOBALS['include_global_recent_drops'] = true;
    $GLOBALS['include_global_inventory'] = true;
}
?>
<!-- // HEADER -->

<nav class="Navigation bg-gray-900 nav-blur shadow-2xl z-50 border-b border-gray-800">
    <div class="mainnav">
        <div class="w-full px-4">
            <div class="flex items-center justify-between h-20 w-full">
                <!-- Logo - Far Left -->
                <div class="flex-shrink-0">
                    <a href="/" class="logo flex items-center">
                        <img class="h-12 w-auto transition-transform duration-300 hover:scale-105" src="assets/img/logo4.png" alt="CashPlay" width="48" height="48" fetchpriority="high">
                    </a>
                </div>
                
                <?php if ($header_is_logged_in): ?>
                <!-- Left Navigation removed -->
                <div class="hidden lg:flex items-center space-x-2 ml-8">
                    <!-- Navigation buttons removed -->
                </div>
                
                <!-- Right Side - User Section -->
                <?php if ($is_logged_in): ?>
                    <!-- Authenticated User Section -->
                    <div class="user flex items-center h-full space-x-5">
                        <!-- Deposit Button -->
                        <a href="?p=topUp" class="btn-base btn-gradient-success btn-shine glow relative group flex items-center h-11 px-5 rounded-md overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="relative z-10 flex items-center h-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-white" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-white font-bold text-base tracking-wide">DEPOSIT</span>
                            </div>
                            <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-white/30 group-hover:bg-white/50 transition-all duration-300 transform scale-x-0 group-hover:scale-x-100"></div>
                        </a>
                        
                        <!-- User Info -->
                        <div class="account flex items-center h-full">
                            <div class="info mr-4 text-right">
                                <div class="username text-white font-medium text-sm"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></div>
                                <div class="wallet flex items-center justify-end">
                                    <div class="balance flex items-center bg-gray-800/50 px-3 py-1 rounded-full">
                                        <span class="text-amber-300 text-sm font-semibold" id="header-balance">$<?php echo number_format($user_balance, 2); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center h-full space-x-4">
                                <div class="flex-shrink-0 h-12 w-12 flex items-center">
                                    <?php 
                                    // Pobierz awatar Steam z bazy danych
                                    $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username'] ?? 'U') . '&background=6d28d9&color=fff&size=80';
                                    
                                    if ($header_is_logged_in && isset($conn) && isset($_SESSION['user_unique'])) {
                                        $user_unique = $_SESSION['user_unique'];
                                        
                                        // Pobierz awatar z bazy danych
                                        $avatar_query = $conn->prepare("SELECT avatar, steam_id FROM user_details WHERE user_unique = ?");
                                        if ($avatar_query) {
                                            $avatar_query->bind_param("s", $user_unique);
                                            $avatar_query->execute();
                                            $avatar_result = $avatar_query->get_result();
                                            
                                            if ($avatar_result && $avatar_result->num_rows > 0) {
                                                $avatar_data = $avatar_result->fetch_assoc();
                                                $db_avatar = $avatar_data['avatar'] ?? null;
                                                $db_steam_id = $avatar_data['steam_id'] ?? null;
                                                
                                                // Użyj awatara z bazy danych jeśli istnieje
                                                if ($db_avatar && !empty($db_avatar)) {
                                                    $avatar_url = $db_avatar;
                                                }
                                                
                                                // Zapisz Steam ID do sesji jeśli nie ma
                                                if ($db_steam_id && !isset($_SESSION['steam_id'])) {
                                                    $_SESSION['steam_id'] = $db_steam_id;
                                                }
                                            }
                                            $avatar_query->close();
                                        }
                                    }
                                    
                                    // Fallback do sesji
                                    if (isset($_SESSION['steam_avatar']) && !empty($_SESSION['steam_avatar'])) {
                                        $avatar_url = $_SESSION['steam_avatar'];
                                    } elseif (isset($_SESSION['avatar']) && !empty($_SESSION['avatar'])) {
                                        $avatar_url = $_SESSION['avatar'];
                                    }
                                    ?>
                                    <div class="relative w-12 h-12 rounded-full overflow-hidden border-2 border-purple-500">
                                        <img src="<?php echo $avatar_url; ?>" 
                                             alt="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>" 
                                             class="absolute inset-0 w-full h-full object-cover"
                                             width="48" height="48"
                                             loading="lazy"
                                             decoding="async">
                                    </div>
                                </div>
                                
                                <!-- Logout Button -->
                                <a href="auth/logout.php" class="text-gray-400 hover:text-white transition-colors duration-200 h-12 w-12 flex items-center justify-center" title="Logout">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Unauthenticated User - Steam Login Button -->
                    <div class="flex items-center h-full">
                        <a href="server/steam_login.php?action=login" class="btn-base btn-gradient-steam btn-shine glow relative group flex items-center h-11 px-6 rounded-md overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="relative z-10 flex items-center h-full">
                                <!-- Steam icon -->
                                <svg class="w-5 h-5 mr-2 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                                    <path d="M20.469 0A3.53 3.53 0 0124 3.531v16.938A3.53 3.53 0 0120.469 24H3.531A3.53 3.53 0 010 20.469V12.34l6.413 2.727a3.787 3.787 0 007.348.829l3.815-2.383a3.884 3.884 0 10-1.74-7.41l-2.43 3.77A3.787 3.787 0 006.47 9.79L0 7.551V3.531A3.53 3.53 0 013.531 0zM23.502 8.013a2.356 2.356 0 11-4.712 0 2.356 2.356 0 014.712 0zM11.602 15.775a2.358 2.358 0 10-.003-4.716 2.358 2.358 0 00.003 4.716zm8.8-7.762a1.59 1.59 0 11-3.18 0 1.59 1.59 0 013.18 0z"/>
                                </svg>
                                <span class="text-white font-bold text-base tracking-wide">LOGIN WITH STEAM</span>
                            </div>
                            <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-white/30 group-hover:bg-white/50 transition-all duration-300 transform scale-x-0 group-hover:scale-x-100"></div>
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Mobile Hamburger -->
                <button class="hamburger lg:hidden flex flex-col justify-center items-center w-8 h-8 space-y-1" id="sidebarToggleOpenButton">
                    <span class="block w-6 h-0.5 bg-gray-300 transition-all duration-200"></span>
                    <span class="block w-6 h-0.5 bg-gray-300 transition-all duration-200"></span>
                    <span class="block w-6 h-0.5 bg-gray-300 transition-all duration-200"></span>
                </button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Simple Navigation for Unauthenticated Users -->
    <div class="flex items-center">
        <a href="server/steam_login.php?action=login" class="btn-base btn-gradient-steam btn-shine glow flex items-center px-4 py-2 text-white font-medium rounded-lg">
            <!-- Steam icon -->
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                <path d="M20.469 0A3.53 3.53 0 0124 3.531v16.938A3.53 3.53 0 0120.469 24H3.531A3.53 3.53 0 010 20.469V12.34l6.413 2.727a3.787 3.787 0 007.348.829l3.815-2.383a3.884 3.884 0 10-1.74-7.41l-2.43 3.77A3.787 3.787 0 006.47 9.79L0 7.551V3.531A3.53 3.53 0 013.531 0zM23.502 8.013a2.356 2.356 0 11-4.712 0 2.356 2.356 0 014.712 0zM11.602 15.775a2.358 2.358 0 10-.003-4.716 2.358 2.358 0 00.003 4.716zm8.8-7.762a1.59 1.59 0 11-3.18 0 1.59 1.59 0 013.18 0z"/>
            </svg>
            Login with Steam
        </a>
    </div>
    </div>
    </div>
    </div>
    <?php endif; ?>
    
    <?php if ($header_is_logged_in): ?>
    <!-- Mobile menu, show/hide based on menu open state. -->
    <div class="hidden duration-500" id="sidebarToggle" role="dialog" aria-modal="true">
        <!-- Background backdrop, show/hide based on slide-over state. -->
        <div class="fixed inset-0 bg-black/50 z-40" id="sidebarBackdrop"></div>
    <div class="hidden duration-500" id="sidebarToggle" role="dialog" aria-modal="true">
        <!-- Background backdrop, show/hide based on slide-over state. -->
        <div class="fixed inset-0 bg-black/50 z-40" id="sidebarBackdrop"></div>

        <div class="fixed inset-y-0 right-0 z-50 w-full max-w-sm bg-gray-900 shadow-2xl overflow-y-auto">
            <div class="flex items-center justify-between p-4 border-b border-gray-800">
                <a href="/" class="flex items-center">
                    <img class="h-8 w-auto" src="assets/img/logo4.png" alt="CashPlay">
                </a>
                <button type="button" class="text-gray-400 hover:text-white transition-colors duration-200" onclick="document.getElementById('sidebarToggle').classList.add('hidden')">
                    <span class="sr-only">Close menu</span>
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
            </div>
            
            <!-- Balance Display -->
            <div class="p-4 border-b border-gray-800">
                <div class="bg-gradient-to-r from-yellow-500 to-amber-500 text-black px-4 py-3 rounded-lg font-bold flex items-center justify-center gap-2 shadow-lg">
                    <i class="fas fa-coins text-yellow-900"></i>
                    <span id="mobile-balance">$<?php echo number_format($user_balance ?? 0, 2); ?></span>
                </div>
            </div>
            <div class="p-4 space-y-1">
                <p class="px-4 py-2 text-sm font-medium text-gray-400 uppercase">Dashboard</p>
                <a href="/" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition-colors duration-200">
                    <i class="fas fa-home w-5 h-5 mr-3"></i>
                    <span>Home</span>
                </a>
                
                <p class="px-4 py-2 mt-4 text-sm font-medium text-gray-400 uppercase">User</p>
                <a href="?p=profile" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition-colors duration-200">
                    <i class="fas fa-user w-5 h-5 mr-3"></i>
                    <span>Profile</span>
                </a>
                <a href="?p=history" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition-colors duration-200">
                    <i class="fas fa-history w-5 h-5 mr-3"></i>
                    <span>History</span>
                </a>
                <a href="?p=refer" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition-colors duration-200">
                    <i class="fas fa-gift w-5 h-5 mr-3"></i>
                    <span>Refer & Earn</span>
                </a>
                
                <p class="px-4 py-2 mt-4 text-sm font-medium text-gray-400 uppercase">Important</p>
                <a href="?p=userGuide" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition-colors duration-200">
                    <i class="fas fa-book w-5 h-5 mr-3"></i>
                    <span>User Guide</span>
                </a>
                <a href="?p=legal" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition-colors duration-200">
                    <i class="fas fa-balance-scale w-5 h-5 mr-3"></i>
                    <span>Legal Terms</span>
                </a>
                <a href="?p=settings" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition-colors duration-200">
                    <i class="fas fa-cog w-5 h-5 mr-3"></i>
                    <span>Settings</span>
                </a>
                <a href="?p=support" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg transition-colors duration-200">
                    <i class="fas fa-headset w-5 h-5 mr-3"></i>
                    <span>Support</span>
                </a>
                
                <div class="border-t border-gray-700 mt-4 pt-4">
                    <button onclick="document.getElementById('logOutModal').style.display='Flex'" class="flex items-center w-full px-4 py-3 text-red-400 hover:text-red-300 hover:bg-gray-700 rounded-lg transition-colors duration-200">
                        <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>
                        <span>Logout</span>
                    </button>
                </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</nav>

<script>
  // Ustaw motyw na body (jeśli nie ma ustawionego)
  (function(){
    try { document.body.classList.add('theme-cashplay'); } catch(e){}
  })();
  // Dodaj klasę glass dla niektórych paneli jeśli istnieją
  try {
    document.querySelectorAll('.panel, .card').forEach(el=> el.classList.add('glass','card-hover'));
  } catch(e){}
</script>

<script>
    // HEADER
    document.addEventListener("click", (event) => {
        const sidebarToggleOpenButton = document.getElementById("sidebarToggleOpenButton");
        const sidebarToggle = document.getElementById("sidebarToggle");
        
        if (sidebarToggleOpenButton && sidebarToggle && !sidebarToggleOpenButton.contains(event.target)) {
            // sidebarToggle.style.marginRight = "translate(-200%))";
            // setTimeout(() => {
            sidebarToggle.style.display = "none";
            document.body.style.overflow = "auto";


            // }, 600);
        }
    });

    const sidebarToggleBtn = document.getElementById("sidebarToggleOpenButton");
    const sidebarToggle = document.getElementById("sidebarToggle");
    
    if (sidebarToggleBtn && sidebarToggle) {
        sidebarToggleBtn.addEventListener("click", () => {
            sidebarToggle.style.display = "flex";
            document.body.style.overflow = "hidden";
            // sidebarToggle.style.marginRight = "translate(0px))";
        });
    }

    /// WYŁĄCZONO - powodowało resetowanie salda na 0
    // Saldo jest już obsługiwane przez app.js loadUserBalance()
</script>