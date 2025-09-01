<?php
$users = [];
$total_users = 0;


if (isset($conn)) {
    $create_table = "CREATE TABLE IF NOT EXISTS login_activity (
        la_id int(11) AUTO_INCREMENT PRIMARY KEY,
        la_user_unique varchar(50) NOT NULL,
        la_login_time timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        la_ip_address varchar(45) DEFAULT NULL,
        la_user_agent text DEFAULT NULL,
        INDEX(la_user_unique, la_login_time)
    )";
    mysqli_query($conn, $create_table);
}


if (isset($conn)) {
    $sql = "SELECT user_unique, username, steam_id, total_balance, avatar, created_at FROM user_details LIMIT 10";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while($row = mysqli_fetch_assoc($result)) {
            
            $cases_sql = "SELECT COUNT(*) as count FROM case_openings WHERE user_unique = ?";
            $cases_stmt = mysqli_prepare($conn, $cases_sql);
            if ($cases_stmt) {
                mysqli_stmt_bind_param($cases_stmt, 's', $row['user_unique']);
                mysqli_stmt_execute($cases_stmt);
                $cases_result = mysqli_stmt_get_result($cases_stmt);
                $cases_data = mysqli_fetch_assoc($cases_result);
                $row['cases_opened'] = $cases_data['count'] ?? 0;
                mysqli_stmt_close($cases_stmt);
            } else {
                $row['cases_opened'] = 0;
            }
            
            
            $row['deposited'] = 0.00; 
            $row['withdrawn'] = 0.00; 
            
            
            $row['is_online'] = false; 
            
            
            $online_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM login_activity WHERE la_user_unique = '" . mysqli_real_escape_string($conn, $row['user_unique']) . "' AND la_login_time > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
            
            if ($online_check) {
                $online_data = mysqli_fetch_assoc($online_check);
                $row['is_online'] = (intval($online_data['count']) > 0);
            } else {
                
                $create_table = "CREATE TABLE IF NOT EXISTS login_activity (
                    la_id int(11) AUTO_INCREMENT PRIMARY KEY,
                    la_user_unique varchar(50) NOT NULL,
                    la_login_time timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    la_ip_address varchar(45) DEFAULT NULL,
                    la_user_agent text DEFAULT NULL,
                    INDEX(la_user_unique, la_login_time)
                )";
                mysqli_query($conn, $create_table);
            }
            
            
            $row['email'] = $row['username'] . '@cashplay.com';
            $users[] = $row;
        }
    }
}


if (empty($users)) {
    $users = [
        [
            'user_unique' => 'demo_001',
            'username' => 'TestUser123',
            'steam_id' => '76561198123456789',
            'total_balance' => 150.50,
            'cases_opened' => 25,
            'deposited' => 125.00,
            'withdrawn' => 75.50,
            'is_online' => true,
            'avatar' => 'https://avatars.steamstatic.com/fef49e7fa7e1997310d705b2a6158ff8dc1cdfeb_medium.jpg',
            'email' => 'TestUser123@cashplay.com',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'user_unique' => 'demo_002', 
            'username' => 'PlayerPro',
            'steam_id' => '76561198987654321',
            'total_balance' => 89.25,
            'cases_opened' => 12,
            'deposited' => 60.00,
            'withdrawn' => 30.75,
            'is_online' => false,
            'avatar' => 'https://avatars.steamstatic.com/b5bd56c1aa4644a474a2e4972be27ef94b9e35c7_medium.jpg',
            'email' => 'PlayerPro@cashplay.com',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}

$total_users = count($users);


$total_deposited = 0;
$total_withdrawn = 0;
$online_count = 0;

foreach ($users as $user) {
    $total_deposited += $user['deposited'] ?? 0;
    $total_withdrawn += $user['withdrawn'] ?? 0;
    if ($user['is_online'] ?? false) {
        $online_count++;
    }
}
?>

<div class="p-6">
    <h1 class="text-3xl font-bold text-white mb-6 flex items-center">
        <i class="fa-solid fa-users text-purple-400 mr-3"></i>
        Wszyscy gracze
    </h1>
    



<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6 w-full">
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-blue-500 transition-colors duration-300">
        <div class="flex items-center">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-users text-white"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400 font-semibold">Łącznie użytkowników</p>
                <p class="text-2xl font-bold text-white"><?php echo $total_users; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-green-500 transition-colors duration-300">
        <div class="flex items-center">
            <div class="p-2 bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-arrow-down text-white"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400 font-semibold">Łącznie wpłacone</p>
                <p class="text-2xl font-bold text-white">$<?php echo number_format($total_deposited, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-red-500 transition-colors duration-300">
        <div class="flex items-center">
            <div class="p-2 bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-arrow-up text-white"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400 font-semibold">Łącznie wypłacone</p>
                <p class="text-2xl font-bold text-white">$<?php echo number_format($total_withdrawn, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-purple-500 transition-colors duration-300">
        <div class="flex items-center">
            <div class="p-2 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-circle text-white"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400 font-semibold">Online teraz</p>
                <p class="text-2xl font-bold text-white"><?php echo $online_count; ?></p>
            </div>
        </div>
    </div>
</div>


<div class="bg-gray-800 rounded-lg shadow-2xl border border-gray-700 w-full">
    <div class="px-6 py-4 border-b border-gray-700">
        <h2 class="text-xl font-bold text-white flex items-center">
            <i class="fa-solid fa-users text-purple-400 mr-3"></i>
            Lista użytkowników
        </h2>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-300 uppercase tracking-wider">
                        <i class="fa-solid fa-user mr-2"></i>Użytkownik
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-300 uppercase tracking-wider">
                        <i class="fa-solid fa-wallet mr-2"></i>Saldo aktualne
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-300 uppercase tracking-wider">
                        <i class="fa-solid fa-arrow-down mr-2 text-green-400"></i>Wpłacone
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-300 uppercase tracking-wider">
                        <i class="fa-solid fa-arrow-up mr-2 text-red-400"></i>Wypłacone
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-300 uppercase tracking-wider">
                        <i class="fa-solid fa-box mr-2"></i>Otwarte skrzynki
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-300 uppercase tracking-wider">
                        <i class="fa-solid fa-calendar mr-2"></i>Data rejestracji
                    </th>
                </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <?php 
                            
                            $clean_username = preg_replace('/[^a-zA-Z0-9_]/', '', $user['username']);
                            
                            
                            $deposited = $user['deposited'] ?? 0;
                            $withdrawn = $user['withdrawn'] ?? 0;
                            
                            
                            $cases_opened = 0;
                            if (isset($conn) && $conn) {
                                $case_query = $conn->prepare("SELECT COUNT(*) as count FROM case_openings WHERE user_unique = ?");
                                if ($case_query) {
                                    $case_query->bind_param("s", $user['user_unique']);
                                    $case_query->execute();
                                    $case_result = $case_query->get_result();
                                    if ($case_result && $case_row = $case_result->fetch_assoc()) {
                                        $cases_opened = (int)$case_row['count'];
                                    }
                                    $case_query->close();
                                }
                            }
                            
                            
                            if ($cases_opened == 0 && isset($user['cases_opened'])) {
                                $cases_opened = (int)$user['cases_opened'];
                            }
                            
                            
                            $is_online = $user['is_online'] ?? false;
                        ?>
                        <tr class="hover:bg-gray-700 transition-colors duration-200" data-user-id="<?php echo htmlspecialchars($user['user_unique']); ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                         <?php 
                                         
                                         $steam_id = $user['steam_id'] ?? '76561000000000000';
                                         
                                         
                                         if (!empty($user['avatar'])) {
                                             $steam_avatar_url = $user['avatar'];
                                         } else {
                                             
                                             $avatar_hash = substr(md5($steam_id), 0, 8);
                                             $steam_avatar_url = "https://avatars.steamstatic.com/" . $avatar_hash . "_medium.jpg";
                                         }
                                         ?>
                                        <img class="h-10 w-10 rounded-full object-cover border-2 border-purple-500" 
                                             src="<?php echo $steam_avatar_url; ?>" 
                                             alt="<?php echo htmlspecialchars($clean_username); ?>" 
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 items-center justify-center hidden">
                                            <span class="text-white font-bold text-sm"><?php echo strtoupper(substr($clean_username, 0, 2)); ?></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($clean_username, ENT_QUOTES, 'UTF-8'); ?></div>
                                             <?php if ($is_online): ?>
                                                 <div class="status-dot ml-2 h-2 w-2 bg-green-400 rounded-full animate-pulse"></div>
                                                 <span class="status-text ml-1 text-xs text-green-400 font-semibold">ONLINE</span>
                                             <?php else: ?>
                                                 <div class="status-dot ml-2 h-2 w-2 bg-gray-500 rounded-full"></div>
                                                 <span class="status-text ml-1 text-xs text-gray-500">OFFLINE</span>
                                             <?php endif; ?>
                                        </div>
                                        <div class="text-xs text-gray-400">Steam ID: <?php echo htmlspecialchars($steam_id); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-bold text-white">
                                    $<?php echo number_format((float)$user['total_balance'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-bold text-green-400">
                                    $<?php echo number_format($deposited, 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-bold text-red-400">
                                    $<?php echo number_format($withdrawn, 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                    <?php echo $cases_opened; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center">
                                <div class="text-gray-400">
                                    <i class="fa-solid fa-users text-4xl mb-3"></i>
                                    <p class="text-lg font-bold">Brak użytkowników</p>
                                    <p class="text-sm">Nie znaleziono żadnych użytkowników w systemie</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php

if (isset($conn) && $conn) {
    $conn->close();
}
?>

<script>
// AJAX odświeżanie statusów online/offline bez przeładowania strony
function updateOnlineStatus() {
    fetch('../../server/get_online_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Aktualizuj statusy dla każdego użytkownika
            data.users.forEach(function(user) {
                const userRow = document.querySelector(`[data-user-id="${user.user_unique}"]`);
                if (userRow) {
                    const statusDot = userRow.querySelector('.status-dot');
                    const statusText = userRow.querySelector('.status-text');
                    
                    if (user.is_online) {
                        statusDot.className = 'status-dot ml-2 h-2 w-2 bg-green-400 rounded-full animate-pulse';
                        statusText.className = 'status-text ml-1 text-xs text-green-400 font-semibold';
                        statusText.textContent = 'ONLINE';
                    } else {
                        statusDot.className = 'status-dot ml-2 h-2 w-2 bg-gray-500 rounded-full';
                        statusText.className = 'status-text ml-1 text-xs text-gray-500';
                        statusText.textContent = 'OFFLINE';
                    }
                }
            });
            // Statusy zaktualizowane
        }
    })
    .catch(error => {
        // Błąd aktualizacji statusów
    });
}

// Aktualizuj statusy co 15 sekund
setInterval(updateOnlineStatus, 15000);

// Uruchom natychmiast
updateOnlineStatus();

// AJAX odświeżanie statusów włączone - co 15 sekund
</script>
