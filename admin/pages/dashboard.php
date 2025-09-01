<?php


@ini_set('display_errors', 0);
@error_reporting(0);

if (!isset($conn)) {
    @include '../server/conn.php';
}


if (!function_exists('getRecentActivities')) {
    function getRecentActivities($limit = 5) {
        global $conn;
        
        $activities = [];
        
        try {
            
            $activity_query = "SELECT u.username, la.la_login_time 
                             FROM login_activity la 
                             JOIN user_details u ON la.la_user_unique = u.user_unique 
                             ORDER BY la.la_login_time DESC 
                             LIMIT ?";
            
            $stmt = mysqli_prepare($conn, $activity_query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $limit);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $activities[] = [
                        'user' => $row['username'],
                        'action' => 'Zalogował się',
                        'time' => $row['la_login_time'],
                        'type' => 'login'
                    ];
                }
                mysqli_stmt_close($stmt);
            }
            
        } catch (Exception $e) {
            error_log("Recent activities error: " . $e->getMessage());
        }
        
        return $activities;
    }
}

$activities = getRecentActivities(5);
?>

<div class="p-6">
    <h1 class="text-3xl font-bold text-white mb-6 flex items-center leading-tight">
        <i class="fa-solid fa-chart-line text-purple-400 mr-3"></i>
        Dashboard
    </h1>
    <p class="text-gray-300 mb-6 leading-snug">Przegląd statystyk CashPlay</p>

    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6 w-full">
            
            <div class="flex flex-col bg-gray-800 shadow-2xl rounded-xl border border-gray-700 hover:border-purple-500 transition-colors duration-300">
                <div class="p-4 md:p-8 flex gap-x-4 min-w-0">
                    <div class="flex justify-center items-center w-12 h-12 text-purple-400 bg-gray-700 rounded-full">
                        <i class="fa-solid fa-users text-2xl"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-400 font-medium leading-snug">Total Users</p>
                        <div class="text-3xl font-bold text-white leading-snug">
                            <h3 id="total-users-stat" class="animate-pulse">...</h3>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="flex flex-col bg-gray-800 shadow-2xl rounded-xl border border-gray-700 hover:border-green-500 transition-colors duration-300">
                <div class="p-4 md:p-8 flex gap-x-4 min-w-0">
                    <div class="flex justify-center items-center w-12 h-12 text-green-400 bg-gray-700 rounded-full">
                        <i class="fa-solid fa-wallet text-2xl"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-400 font-medium leading-snug">Total Users Balance</p>
                        <div class="text-3xl font-bold text-white leading-snug">
                            <h3 id="total-balance-stat" class="animate-pulse">...</h3>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="flex flex-col bg-gray-800 shadow-2xl rounded-xl border border-gray-700 hover:border-purple-500 transition-colors duration-300">
                <div class="p-4 md:p-8 flex gap-x-4">
                    <div class="flex justify-center items-center w-12 h-12 text-blue-400 bg-gray-700 rounded-full">
                        <i class="fa-solid fa-box-open text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400 font-medium leading-snug">Cases Opened Today</p>
                        <div class="text-3xl font-bold text-white leading-snug">
                            <h3 id="cases-today-stat" class="animate-pulse">...</h3>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="flex flex-col bg-gray-800 shadow-2xl rounded-xl border border-gray-700 hover:border-orange-500 transition-colors duration-300">
                <div class="p-4 md:p-8 flex gap-x-4 min-w-0">
                    <div class="flex justify-center items-center w-12 h-12 text-orange-400 bg-gray-700 rounded-full">
                        <i class="fa-solid fa-gem text-2xl"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-400 font-medium leading-snug">Total Items in Game</p>
                        <div class="text-3xl font-bold text-white leading-snug">
                            <h3 id="total-items-stat" class="animate-pulse">...</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-8">
            
            <div class="bg-gray-800 shadow-2xl rounded-xl p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <i class="fa-solid fa-chart-line text-green-400 mr-3"></i>
                    <span class="leading-tight">Przychody (7 dni)</span>
                </h3>
                <canvas id="revenueChart" style="width:100%;height:220px;max-width:100%"></canvas>
            </div>
            
            
            <div class="bg-gray-800 shadow-2xl rounded-xl p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <i class="fa-solid fa-trophy text-yellow-400 mr-3"></i>
                    <span class="leading-tight">Top 5 Najpopularniejszych Skrzynek</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <?php 
                    $top_cases = [
                        ['name' => 'Premium Case', 'opens' => 245, 'revenue' => 6125.00, 'image' => 'premium.png'],
                        ['name' => 'Starter Case', 'opens' => 189, 'revenue' => 945.00, 'image' => 'starter.png'],
                        ['name' => 'Legendary Case', 'opens' => 87, 'revenue' => 4350.00, 'image' => 'legendary.png'],
                        ['name' => 'Mystery Case', 'opens' => 156, 'revenue' => 2340.00, 'image' => 'mystery.png'],
                        ['name' => 'Elite Case', 'opens' => 98, 'revenue' => 2940.00, 'image' => 'elite.png']
                    ];
                    foreach($top_cases as $index => $case): ?>
                    <div class="bg-gray-700 rounded-lg p-4 border border-gray-600 hover:border-purple-500 transition-colors duration-300 min-w-0">
                        <div class=" mb-3">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-blue-500 rounded-lg ">
                                <i class="fa-solid fa-box text-white text-2xl"></i>
                            </div>
                        </div>
                        <h4 class="text-white font-bold text-sm text-center mb-2 truncate"><?php echo $case['name']; ?></h4>
                        <div class="text-center">
                            <p class="text-green-400 font-bold"><?php echo number_format($case['opens']); ?> otwarć</p>
                            <p class="text-gray-400 text-xs">$<?php echo number_format($case['revenue'], 2); ?></p>
                        </div>
                        <div class="mt-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                #<?php echo $index + 1; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        
        <div class="mt-8">
            <div class="bg-gray-800 shadow-2xl rounded-xl p-6 border border-gray-700">
                <h2 class="text-xl font-bold text-white mb-4 flex items-center">
                    <i class="fa-solid fa-clock text-purple-400 mr-3"></i>
                    <span class="leading-tight">Live Activity Feed</span>
                    <span class="ml-auto">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse mr-2"></div>
                            <span class="text-green-400 text-sm">Na żywo</span>
                        </div>
                    </span>
                </h2>
                
                <div class="space-y-4">
                    <?php if (empty($activities)): ?>
                        <p class="text-gray-400 text-center py-8">Brak ostatnich aktywności</p>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-700 rounded-lg border border-gray-600 hover:border-purple-500 transition-colors duration-200">
                                <div class="flex items-center space-x-3 min-w-0">
                                    <div class="flex-shrink-0">
                                        <i class="fa-solid fa-sign-in-alt text-green-400"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-white truncate">
                                            <?php echo htmlspecialchars($activity['user']); ?>
                                        </p>
                                        <p class="text-sm text-gray-300 truncate">
                                            <?php echo htmlspecialchars($activity['action']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">
                                        <?php 
                                        $time_diff = time() - strtotime($activity['time']);
                                        if ($time_diff < 60) {
                                            echo $time_diff . ' sek temu';
                                        } elseif ($time_diff < 3600) {
                                            echo floor($time_diff / 60) . ' min temu';
                                        } else {
                                            echo date('H:i', strtotime($activity['time']));
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

 
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch dashboard stats from API
    async function fetchDashboardStats() {
        try {
            const response = await fetch('/admin/api/dashboard.php?action=get_stats');
            const result = await response.json();

            if (result.status === 'success') {
                const stats = result.data;
                document.getElementById('total-users-stat').textContent = new Intl.NumberFormat().format(stats.total_users);
                document.getElementById('total-balance-stat').textContent = '$' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(stats.total_balance);
                document.getElementById('cases-today-stat').textContent = new Intl.NumberFormat().format(stats.cases_today);
                document.getElementById('total-items-stat').textContent = new Intl.NumberFormat().format(stats.total_items);

                // Remove pulse animation after loading
                document.querySelectorAll('.animate-pulse').forEach(el => el.classList.remove('animate-pulse'));
            } else {
                throw new Error(result.message || 'Failed to load stats');
            }
        } catch (error) {
            console.error('Error fetching dashboard stats:', error);
            document.getElementById('total-users-stat').textContent = 'Error';
            document.getElementById('total-balance-stat').textContent = 'Error';
            document.getElementById('cases-today-stat').textContent = 'Error';
            document.getElementById('total-items-stat').textContent = 'Error';
        }
    }

    fetchDashboardStats();

    // Revenue Chart (guarded)
    const revenueCanvas = document.getElementById('revenueChart');
    if (revenueCanvas) {
      const revenueCtx = revenueCanvas.getContext('2d');
      new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nd'],
            datasets: [{
                label: 'Przychody ($)',
                data: [1200, 1900, 800, 1500, 2000, 2800, 2200],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: 'white'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgb(156, 163, 175)'
                    },
                    grid: {
                        color: 'rgba(156, 163, 175, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgb(156, 163, 175)'
                    },
                    grid: {
                        color: 'rgba(156, 163, 175, 0.1)'
                    }
                }
            }
        }
      });
    }
    
    // Cases Chart (guarded)
    const casesCanvas = document.getElementById('casesChart');
    if (casesCanvas) {
      const casesCtx = casesCanvas.getContext('2d');
      new Chart(casesCtx, {
        type: 'bar',
        data: {
            labels: ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nd'],
            datasets: [{
                label: 'Otwarte skrzynki',
                data: [45, 67, 32, 58, 89, 125, 98],
                backgroundColor: 'rgba(147, 51, 234, 0.8)',
                borderColor: 'rgb(147, 51, 234)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: 'white'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgb(156, 163, 175)'
                    },
                    grid: {
                        color: 'rgba(156, 163, 175, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgb(156, 163, 175)'
                    },
                    grid: {
                        color: 'rgba(156, 163, 175, 0.1)'
                    }
                }
            }
        }
      });
    }
    
    // Live Activity Feed Simulation
    setInterval(updateLiveActivity, 10000); // Update every 10 seconds
});

// Live Activity Feed Update
function updateLiveActivity() {
    const activities = [
        {user: 'Player' + Math.floor(Math.random() * 1000), case: 'Premium Case', value: (Math.random() * 100 + 10).toFixed(2)},
        {user: 'Gamer' + Math.floor(Math.random() * 1000), case: 'Starter Case', value: (Math.random() * 50 + 5).toFixed(2)},
        {user: 'Pro' + Math.floor(Math.random() * 1000), case: 'Legendary Case', value: (Math.random() * 200 + 50).toFixed(2)}
    ];
    
    const randomActivity = activities[Math.floor(Math.random() * activities.length)];
    const feed = document.getElementById('liveActivityFeed');
    
    if (feed) {
        const newActivity = document.createElement('div');
        newActivity.className = 'flex items-center justify-between p-4 bg-gray-700 rounded-lg border border-gray-600 opacity-0 transform translate-y-4';
        newActivity.innerHTML = `
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-box text-purple-400"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-white">${randomActivity.user}</p>
                    <p class="text-sm text-gray-300">Otworzył skrzynkę: ${randomActivity.case}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm font-medium text-green-400">$${randomActivity.value}</p>
                <p class="text-xs text-gray-500">Teraz</p>
            </div>
        `;
        
        feed.insertBefore(newActivity, feed.firstChild);
        
        // Animate in
        setTimeout(() => {
            newActivity.classList.remove('opacity-0', 'transform', 'translate-y-4');
            newActivity.classList.add('transition-all', 'duration-500');
        }, 100);
        
        // Remove old activities (keep only 5)
        const activities_list = feed.children;
        if (activities_list.length > 5) {
            feed.removeChild(activities_list[activities_list.length - 1]);
        }
    }
}
</script>
