<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



$cases = [];
$total_cases = 0;
$active_cases = 0;
$total_opens = 0;

if (isset($conn) && $conn) {
    try {
        
        $stats_query = "SELECT 
                           COUNT(*) as total,
                           COUNT(CASE WHEN is_active = 1 THEN 1 END) as active
                        FROM cases";
        $stats_result = $conn->query($stats_query);
        if ($stats_result) {
            $stats = $stats_result->fetch_assoc();
            $total_cases = $stats['total'] ?? 0;
            $active_cases = $stats['active'] ?? 0;
        }
        
        
        $opens_query = "SELECT COUNT(*) as total_opens FROM case_openings";
        $opens_result = $conn->query($opens_query);
        if ($opens_result) {
            $opens = $opens_result->fetch_assoc();
            $total_opens = $opens['total_opens'] ?? 0;
        }
        
        
        $query = "SELECT c.*, 
                         COUNT(co.id) as open_count,
                         SUM(c.price) as total_revenue
                  FROM cases c 
                  LEFT JOIN case_openings co ON c.id = co.case_id 
                  GROUP BY c.id
                  ORDER BY c.created_at DESC";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cases[] = $row;
            }
        }
    } catch (Exception $e) {
        
    }
}


if (empty($cases) && $total_cases == 0) {
    
    $sample_cases = [
        [
            'name' => 'Starter Case',
            'description' => 'Perfect case for beginners with common items',
            'price' => 5.00,
            'image' => 'assets/cases/starter_case.png',
            'status' => 'active',
            'rarity' => 'common'
        ],
        [
            'name' => 'Premium Case', 
            'description' => 'High-value items with rare drops',
            'price' => 25.00,
            'image' => 'assets/cases/premium_case.png',
            'status' => 'active',
            'rarity' => 'rare'
        ],
        [
            'name' => 'Legendary Case',
            'description' => 'Exclusive legendary items inside',
            'price' => 50.00,
            'image' => 'assets/cases/legendary_case.png', 
            'status' => 'active',
            'rarity' => 'legendary'
        ]
    ];
    
    
    if (isset($conn) && $conn) {
        foreach ($sample_cases as $sample_case) {
            $stmt = $conn->prepare("INSERT INTO cases (name, description, price, image_url, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("ssdsi", $sample_case['name'], $sample_case['description'], $sample_case['price'], $sample_case['image'], ($sample_case['status'] === 'active' ? 1 : 0));
                $stmt->execute();
            }
        }
        
        
        $result = $conn->query("SELECT c.*, COUNT(co.id) as open_count, SUM(c.price) as total_revenue FROM cases c LEFT JOIN case_openings co ON c.id = co.case_id GROUP BY c.id ORDER BY c.created_at DESC");
        if ($result) {
            $cases = [];
            while ($row = $result->fetch_assoc()) {
                $cases[] = $row;
            }
            $total_cases = count($cases);
            $active_cases = count(array_filter($cases, function($c) { return $c['is_active'] == 1; }));
        }
    } else {
        
        $cases = [
            [
                'id' => 1,
                'name' => 'Starter Case',
                'description' => 'Perfect case for beginners',
                'price' => 5.00,
                'image_url' => 'assets/cases/starter_case.png',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'open_count' => 45,
                'total_revenue' => 225.00
            ],
            [
                'id' => 2,
                'name' => 'Premium Case',
                'description' => 'High-value items inside',
                'price' => 25.00,
                'image_url' => 'assets/cases/premium_case.png',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'open_count' => 18,
                'total_revenue' => 450.00
            ],
            [
                'id' => 3,
                'name' => 'Legendary Case',
                'description' => 'Rare and legendary items',
                'price' => 50.00,
                'image_url' => 'assets/cases/legendary_case.png',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'open_count' => 7,
                'total_revenue' => 350.00
            ]
        ];
        $total_cases = count($cases);
        $active_cases = 3;
        $total_opens = 70;
    }
}
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-white flex items-center">
        <i class="fa-solid fa-box text-purple-400 mr-3"></i>
        Zarządzanie skrzynkami
    </h1>
    <p class="text-gray-300">Twórz i konfiguruj skrzynki na platformie</p>
</div>


<div id="editCaseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-600 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white">Edytuj skrzynkę</h3>
                <button onclick="hideEditCaseModal()" class="text-gray-400 hover:text-white text-2xl leading-none">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="editCaseForm" class="p-6">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="editCaseId" name="case_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nazwa skrzynki</label>
                    <input id="editCaseName" type="text" name="name" required class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Cena ($)</label>
                    <input id="editCasePrice" type="number" name="price" step="0.01" required class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                    <select id="editCaseStatus" name="status" required class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="active">Aktywna</option>
                        <option value="inactive">Nieaktywna</option>
                        <option value="draft">Szkic</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Obrazek (URL)</label>
                    <input id="editCaseImage" type="url" name="image" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="https://example.com/image.png">
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideEditCaseModal()" class="px-4 py-2 text-gray-300 bg-gray-700 border border-gray-600 rounded-md hover:bg-gray-600">Anuluj</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Zapisz zmiany</button>
                </div>
            </form>
        </div>
    </div>
    
</div>

<script>
function hideEditCaseModal() {
    document.getElementById('editCaseModal').classList.add('hidden');
    const f = document.getElementById('editCaseForm');
    if (f) f.reset();
}
</script>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-purple-500 transition-colors duration-300">
        <div class="flex items-center">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-box text-white"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400 font-semibold">Łącznie skrzynek</p>
                <p class="text-2xl font-bold text-white"><?php echo number_format($total_cases); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-green-500 transition-colors duration-300">
        <div class="flex items-center">
            <div class="p-2 bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-box-open text-white"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400 font-semibold">Aktywne skrzynki</p>
                <p class="text-2xl font-bold text-white"><?php echo number_format($active_cases); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-purple-500 transition-colors duration-300">
        <div class="flex items-center">
            <div class="p-2 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-chart-bar text-white"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400 font-semibold">Łącznie otwarć</p>
                <p class="text-2xl font-bold text-white"><?php echo number_format($total_opens); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-orange-500 transition-colors duration-300">
        <div class="flex items-center">
            <div class="p-2 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-dollar-sign text-white"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400 font-semibold">Średnia cena</p>
                <p class="text-2xl font-bold text-white">$<?php echo $total_cases > 0 ? number_format(array_sum(array_column($cases, 'price')) / $total_cases, 2) : '0.00'; ?></p>
            </div>
        </div>
    </div>
</div>


<div class="mb-6 flex flex-wrap gap-4 items-center justify-between">
    <div class="flex gap-4">
        <button onclick="showAddCaseModal()" class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white px-6 py-3 rounded-lg flex items-center shadow-lg hover:shadow-xl transition-all duration-300">
            <i class="fa-solid fa-plus mr-2"></i>
            Dodaj nową skrzynkę
        </button>
    </div>
    
    <div class="flex gap-2">
        <select id="statusFilter" onchange="filterCases()" class="bg-gray-700 text-white px-3 py-2 rounded-lg border border-gray-600">
            <option value="all">Wszystkie statusy</option>
            <option value="active">Aktywne</option>
            <option value="inactive">Nieaktywne</option>
        </select>
        
        <input type="text" id="searchCases" placeholder="Szukaj skrzynek..." onkeyup="searchCases()" class="bg-gray-700 text-white px-3 py-2 rounded-lg border border-gray-600 placeholder-gray-400">
    </div>
</div>


<div class="bg-gray-800 shadow-2xl rounded-lg border border-gray-700">
    <div class="px-6 py-4 border-b border-gray-600">
        <h3 class="text-xl font-bold text-white flex items-center">
            <i class="fa-solid fa-list text-purple-400 mr-3"></i>
            Lista skrzynek
        </h3>
    </div>
    
    <!-- Wyszukiwanie i filtry -->
    <div class="mb-4 px-6 flex flex-wrap gap-4 items-center">
        <div class="flex-1 min-w-64">
            <div class="relative">
                <input type="text" id="searchCases" placeholder="Wyszukaj skrzynki..." 
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500">
                <i class="fa-solid fa-search absolute right-3 top-3 text-gray-400"></i>
            </div>
        </div>
        <div class="flex gap-2">
            <select id="statusFilter" class="px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">Wszystkie statusy</option>
                <option value="active">Aktywne</option>
                <option value="inactive">Nieaktywne</option>
            </select>
            <select id="balanceFilter" class="px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">Wszystkie balansy</option>
                <option value="good">Dobrze zbalansowane</option>
                <option value="warning">Wymaga uwagi</option>
                <option value="bad">Źle zbalansowane</option>
            </select>
        </div>
    </div>
    
    <div class="w-full">
        <table class="min-w-full divide-y divide-gray-600" id="casesTable">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-600" onclick="sortTable('name')">
                        Nazwa <i class="fa-solid fa-sort ml-1"></i>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-600" onclick="sortTable('price')">
                        Cena <i class="fa-solid fa-sort ml-1"></i>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-600" onclick="sortTable('status')">
                        Status <i class="fa-solid fa-sort ml-1"></i>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-600" onclick="sortTable('opens')">
                        Otwarcia <i class="fa-solid fa-sort ml-1"></i>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balans</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcje</th>
                </tr>
            </thead>
            <tbody id="casesTableBody">
                <?php if (!empty($cases)): ?>
                    <?php foreach ($cases as $case): ?>
                        <tr data-name="<?php echo htmlspecialchars($case['name']); ?>" 
                            data-price="<?php echo $case['price']; ?>" 
                            data-status="<?php echo $case['is_active'] == 1 ? 'active' : 'inactive'; ?>" 
                            data-opens="<?php echo $case['open_count'] ?? 0; ?>" 
                            data-balance="" 
                            class="case-row">
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($case['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($case['price'], 2); ?></td>
                            
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $case['is_active'] == 1 ? 'Aktywna' : 'Nieaktywna'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-blue-400 font-semibold"><?php echo number_format($case['open_count'] ?? 0); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div id="balance-<?php echo $case['id']; ?>" class="text-sm">
                                    <span class="text-gray-400">Sprawdzanie...</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button onclick="manageDrops(<?php echo $case['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-2">Dropy</button>
                                <button 
                                    class="text-yellow-600 hover:text-yellow-900 mr-2"
                                    onclick="editCase(this)"
                                    data-id="<?php echo (int)$case['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($case['name'], ENT_QUOTES); ?>"
                                    data-price="<?php echo (float)$case['price']; ?>"
                                    data-status="<?php echo ($case['is_active'] == 1 ? 'active' : 'inactive'); ?>"
                                    data-image="<?php echo htmlspecialchars($case['image_url'] ?? '', ENT_QUOTES); ?>"
                                >Edytuj</button>
                                <button onclick="deleteCase(<?php echo $case['id']; ?>)" class="text-red-600 hover:text-red-900">Usuń</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-gray-500 py-6">Brak skrzynek do wyświetlenia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<div id="dropsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-600 flex justify-between items-center">
                <h3 id="dropsModalTitle" class="text-lg font-medium text-white">Zarządzanie dropami</h3>
                <button onclick="hideDropsModal()" class="text-gray-400 hover:text-white text-2xl leading-none">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4 flex gap-3 flex-wrap">
                    <button onclick="showAddDropModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fa-solid fa-plus mr-2"></i>Dodaj drop
                    </button>
                    <button onclick="autoPopulateCase()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        <i class="fa-solid fa-dice mr-2"></i>Wypełnij losowo
                    </button>
                    <button onclick="normalizeDropRates()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg">
                        <i class="fa-solid fa-balance-scale mr-2"></i>Wyrównaj szanse
                    </button>
                    <button onclick="saveDropChanges()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                        <i class="fa-solid fa-save mr-2"></i>Zapisz
                    </button>
                </div>
                <table class="min-w-full divide-y divide-gray-600">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Przedmiot</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Wartość</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Rzadkość</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Szansa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Akcje</th>
                        </tr>
                    </thead>
                    <tbody id="dropsTableBody" class="bg-gray-800 divide-y divide-gray-600">
                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div id="addDropModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-600 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white">Dodaj nowy drop</h3>
                <button onclick="hideAddDropModal()" class="text-gray-400 hover:text-white text-2xl leading-none">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <!-- Search for items -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Wyszukaj skin</label>
                    <input type="text" id="itemSearch" placeholder="Wpisz nazwę skina..." 
                           class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                
                <!-- Items list -->
                <div class="mb-4">
                    <div class="max-h-64 overflow-y-auto border border-gray-600 rounded-md bg-gray-700">
                        <div id="itemsList" class="p-2">
                            <div class="text-center text-gray-400 py-4">Wpisz nazwę skina aby wyszukać</div>
                        </div>
                    </div>
                </div>
                
                <!-- Selected item and drop rate -->
                <div id="selectedItemInfo" class="hidden mb-4 p-3 bg-gray-700 rounded-md border border-gray-600">
                    <div class="flex items-center space-x-3">
                        <img id="selectedItemImage" src="" alt="" class="w-12 h-12 rounded object-cover">
                        <div>
                            <div id="selectedItemName" class="text-white font-medium"></div>
                            <div id="selectedItemPrice" class="text-gray-400 text-sm"></div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Drop rate (%)</label>
                    <input type="number" id="dropRateInput" step="0.01" min="0.01" max="100" value="10" 
                           class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideAddDropModal()" class="px-4 py-2 text-gray-300 bg-gray-700 border border-gray-600 rounded-md hover:bg-gray-600">Anuluj</button>
                    <button type="button" onclick="addSelectedDrop()" id="addDropBtn" disabled 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">Dodaj drop</button>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="editDropModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-600 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white">Edytuj drop</h3>
                <button onclick="hideEditDropModal()" class="text-gray-400 hover:text-white text-2xl leading-none">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="editDropForm" class="p-6">
                <input type="hidden" id="editDropId" name="drop_id">
                <input type="hidden" id="editDropCaseId" name="case_id">
                <input type="hidden" name="action" value="edit">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nazwa przedmiotu</label>
                    <input type="text" id="editDropName" name="name" required class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Wartość ($)</label>
                    <input type="number" id="editDropValue" name="value" step="0.01" required class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Rzadkość</label>
                    <select id="editDropRarity" name="rarity" required class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="common">Common</option>
                        <option value="rare">Rare</option>
                        <option value="epic">Epic</option>
                        <option value="legendary">Legendary</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Szansa (%)</label>
                    <input type="number" id="editDropChance" name="chance" step="0.1" min="0" max="100" required class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideEditDropModal()" class="px-4 py-2 text-gray-300 bg-gray-700 border border-gray-600 rounded-md hover:bg-gray-600">Anuluj</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Zapisz zmiany</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Global variables
let currentDropCaseId = null;

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show notification function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;
    
    const colors = {
        'success': 'bg-green-600 text-white',
        'error': 'bg-red-600 text-white',
        'info': 'bg-blue-600 text-white',
        'warning': 'bg-yellow-600 text-black'
    };
    
    notification.className += ` ${colors[type] || colors.info}`;
    notification.innerHTML = `
        <div class="flex items-center">
            <div class="flex-1">${message}</div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-xl">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Case management functions
function showAddCaseModal() {
    document.getElementById('addCaseModal').classList.remove('hidden');
}

function hideAddCaseModal() {
    document.getElementById('addCaseModal').classList.add('hidden');
    document.getElementById('addCaseForm').reset();
}

function editCase(btn) {
    const modal = document.getElementById('editCaseModal');
    const form = document.getElementById('editCaseForm');
    if (!modal || !form) return;

    // Fill form
    form.querySelector('#editCaseId').value = btn.dataset.id;
    form.querySelector('#editCaseName').value = btn.dataset.name || '';
    form.querySelector('#editCasePrice').value = btn.dataset.price || '';
    form.querySelector('#editCaseStatus').value = btn.dataset.status || 'active';
    form.querySelector('#editCaseImage').value = btn.dataset.image || '';

    // Show modal
    modal.classList.remove('hidden');
}

function deleteCase(id) {
    if (confirm('Czy na pewno chcesz usunąć tę skrzynkę?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('case_id', id);
        fetch('../../server/manage_case.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => { window.location.reload(); }, 1000);
            } else {
                showNotification(data.message || 'Błąd podczas usuwania skrzynki', 'error');
            }
        })
        .catch(error => {
            showNotification('Błąd połączenia z serwerem', 'error');
        });
    }
}

// Drops management functions
function manageDrops(caseId) {
    currentDropCaseId = caseId;
    document.getElementById('dropsModal').classList.remove('hidden');
    document.getElementById('dropsModalTitle').textContent = 'Skrzynka #' + caseId;
    loadDrops(caseId);
}

function hideDropsModal() {
    document.getElementById('dropsModal').classList.add('hidden');
}

// Global variable to store current drops data
let currentDropsData = [];

function loadDrops(caseId) {
    const tbody = document.getElementById('dropsTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-400">Ładowanie...</td></tr>';
    
    // AJAX request do pobrania dropów z prawdziwej bazy
    fetch(`api/drops.php?action=get_drops&case_id=${caseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-400">Brak dropów w tej skrzynce</td></tr>';
                    return;
                }
                
                let html = '';
                data.data.forEach(drop => {
                    const rarityColors = {
                        'Consumer': 'text-gray-400',
                        'Industrial': 'text-blue-400', 
                        'Mil-spec': 'text-purple-400',
                        'Restricted': 'text-pink-400',
                        'Classified': 'text-red-400',
                        'Covert': 'text-yellow-400',
                        'Contraband': 'text-orange-400'
                    };
                    
                    html += `
                        <tr class="border-b border-gray-700 hover:bg-gray-700">
                            <td class="px-4 py-3">
                                <img src="${escapeHtml(drop.image_url)}" alt="${escapeHtml(drop.skin_name)}" 
                                     class="w-10 h-10 rounded object-cover" 
                                     onerror="this.src='https://via.placeholder.com/40x40'">
                            </td>
                            <td class="px-4 py-3 text-white">
                                <div class="font-medium">${escapeHtml(drop.weapon_name)} | ${escapeHtml(drop.skin_name)}</div>
                                <div class="text-sm text-gray-400">€${parseFloat(drop.price).toFixed(2)}</div>
                            </td>
                            <td class="px-4 py-3 ${rarityColors[drop.rarity] || 'text-gray-400'}">${drop.rarity}</td>
                            <td class="px-4 py-3 text-green-400 cursor-pointer hover:bg-gray-600 rounded" 
                                ondblclick="editDropRate(${drop.id}, ${drop.drop_rate})" 
                                title="Podwójne kliknięcie aby edytować">${parseFloat(drop.drop_rate).toFixed(2)}%</td>
                            <td class="px-4 py-3">
                                <button onclick="editDrop(${drop.id}, ${drop.drop_rate})" 
                                        class="text-blue-400 hover:text-blue-300 mr-2" title="Edytuj drop rate">
                                    <i class="fa-solid fa-edit"></i>
                                </button>
                                <button onclick="deleteDrop(${drop.id})" 
                                        class="text-red-400 hover:text-red-300" title="Usuń drop">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-400">Błąd: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Błąd ładowania dropów:', error);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-red-400">Błąd ładowania dropów</td></tr>';
        });
}

// Usunięte niepotrzebne funkcje - używamy teraz prawdziwych danych z API

let selectedItemId = null;

function showAddDropModal() {
    const modal = document.getElementById('addDropModal');
    modal.classList.remove('hidden');
    
    // Reset modal state
    selectedItemId = null;
    document.getElementById('itemSearch').value = '';
    document.getElementById('itemsList').innerHTML = '<div class="text-center text-gray-400 py-4">Wpisz nazwę skina aby wyszukać</div>';
    document.getElementById('selectedItemInfo').classList.add('hidden');
    document.getElementById('addDropBtn').disabled = true;
    document.getElementById('dropRateInput').value = '10';
    
    // Add search event listener
    const searchInput = document.getElementById('itemSearch');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.oninput = function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                document.getElementById('itemsList').innerHTML = '<div class="text-center text-gray-400 py-4">Wpisz co najmniej 2 znaki</div>';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchItems(query);
            }, 300);
        };
    }
}

function hideAddDropModal() {
    document.getElementById('addDropModal').classList.add('hidden');
    selectedItemId = null;
}

function searchItems(query) {
    const itemsList = document.getElementById('itemsList');
    itemsList.innerHTML = '<div class="text-center text-gray-400 py-4"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Wyszukiwanie...</div>';
    
    fetch(`api/drops.php?action=get_available_items&search=${encodeURIComponent(query)}&limit=20`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                let html = '';
                data.data.forEach(item => {
                    const rarityColors = {
                        'Consumer': 'border-gray-400',
                        'Industrial': 'border-blue-400', 
                        'Mil-spec': 'border-purple-400',
                        'Restricted': 'border-pink-400',
                        'Classified': 'border-red-400',
                        'Covert': 'border-yellow-400',
                        'Contraband': 'border-orange-400'
                    };
                    
                    html += `
                        <div class="flex items-center p-2 hover:bg-gray-600 cursor-pointer rounded border-l-4 ${rarityColors[item.rarity] || 'border-gray-400'}" 
                             onclick="selectItem('${item.item_id}', '${escapeHtml(item.weapon_name)}', '${escapeHtml(item.skin_name)}', '${item.price}', '${escapeHtml(item.image_url)}', '${item.rarity}')">
                            <img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.skin_name)}" 
                                 class="w-10 h-10 rounded object-cover mr-3" 
                                 onerror="this.src='https://via.placeholder.com/40x40'">
                            <div class="flex-1">
                                <div class="text-white font-medium">${escapeHtml(item.weapon_name)} | ${escapeHtml(item.skin_name)}</div>
                                <div class="text-sm text-gray-400">€${parseFloat(item.price).toFixed(2)} • ${item.rarity}</div>
                            </div>
                        </div>
                    `;
                });
                itemsList.innerHTML = html;
            } else {
                itemsList.innerHTML = '<div class="text-center text-gray-400 py-4">Nie znaleziono skinów</div>';
            }
        })
        .catch(error => {
            console.error('Błąd wyszukiwania:', error);
            itemsList.innerHTML = '<div class="text-center text-red-400 py-4">Błąd wyszukiwania</div>';
        });
}

function selectItem(itemId, weaponName, skinName, price, imageUrl, rarity) {
    selectedItemId = itemId;
    
    // Show selected item info
    document.getElementById('selectedItemImage').src = imageUrl;
    document.getElementById('selectedItemName').textContent = `${weaponName} | ${skinName}`;
    document.getElementById('selectedItemPrice').textContent = `€${parseFloat(price).toFixed(2)} • ${rarity}`;
    document.getElementById('selectedItemInfo').classList.remove('hidden');
    
    // Enable add button
    document.getElementById('addDropBtn').disabled = false;
}

function addSelectedDrop() {
    if (!selectedItemId) {
        showNotification('Wybierz skin do dodania', 'error');
        return;
    }
    
    const dropRate = parseFloat(document.getElementById('dropRateInput').value);
    if (isNaN(dropRate) || dropRate <= 0 || dropRate > 100) {
        showNotification('Drop rate musi być między 0.01 a 100', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add_drop');
    formData.append('case_id', currentDropCaseId);
    formData.append('item_id', selectedItemId);
    formData.append('drop_rate', dropRate);
    
    const btn = document.getElementById('addDropBtn');
    btn.disabled = true;
    btn.textContent = 'Dodawanie...';
    
    fetch('api/drops.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            hideAddDropModal();
            loadDrops(currentDropCaseId);
            // Odśwież analizę balansu
            setTimeout(() => analyzeCaseBalance(currentDropCaseId), 500);
        } else {
            showNotification(data.message || 'Błąd podczas dodawania dropa', 'error');
        }
    })
    .catch(error => {
        console.error('Błąd:', error);
        showNotification('Błąd połączenia z serwerem', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Dodaj drop';
    });
}

function deleteDrop(id) {
    if (!confirm('Na pewno usunąć ten drop?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_drop');
    formData.append('drop_id', id);
    
    fetch('api/drops.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadDrops(currentDropCaseId);
            // Odśwież analizę balansu
            setTimeout(() => analyzeCaseBalance(currentDropCaseId), 500);
        } else {
            showNotification(data.message || 'Błąd usuwania dropa', 'error');
        }
    })
    .catch(() => showNotification('Błąd połączenia z serwerem', 'error'));
}

// Funkcja edycji drop rate przez podwójne kliknięcie
function editDropRate(dropId, currentDropRate) {
    const newDropRate = prompt(`Wprowadź nowy drop rate (aktualny: ${currentDropRate}%):`, currentDropRate);
    
    if (newDropRate === null || newDropRate === '') return;
    
    const rate = parseFloat(newDropRate);
    if (isNaN(rate) || rate < 0 || rate > 100) {
        showNotification('Nieprawidłowy drop rate. Wprowadź liczbę między 0 a 100.', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update_drop');
    formData.append('drop_id', dropId);
    formData.append('drop_rate', rate);
    
    fetch('api/drops.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadDrops(currentDropCaseId);
            // Odśwież analizę balansu
            setTimeout(() => analyzeCaseBalance(currentDropCaseId), 500);
        } else {
            showNotification(data.message || 'Błąd podczas aktualizacji dropa', 'error');
        }
    })
    .catch(() => showNotification('Błąd połączenia z serwerem', 'error'));
}

function editDrop(dropId, currentDropRate) {
    // Prosty modal do edycji drop rate
    const newDropRate = prompt(`Wprowadź nowy drop rate (aktualny: ${currentDropRate}%):`, currentDropRate);
    
    if (newDropRate === null || newDropRate === '') return;
    
    const rate = parseFloat(newDropRate);
    if (isNaN(rate) || rate < 0 || rate > 100) {
        showNotification('Nieprawidłowy drop rate. Wprowadź liczbę między 0 a 100.', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update_drop');
    formData.append('drop_id', dropId);
    formData.append('drop_rate', rate);
    
    fetch('api/drops.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadDrops(currentDropCaseId);
            // Odśwież analizę balansu
            setTimeout(() => analyzeCaseBalance(currentDropCaseId), 500);
        } else {
            showNotification(data.message || 'Błąd podczas aktualizacji dropa', 'error');
        }
    })
    .catch(() => showNotification('Błąd połączenia z serwerem', 'error'));
}

function autoPopulateCase() {
    if (!confirm('Czy na pewno chcesz automatycznie wypełnić tę skrzynkę losowymi skinami? To usunie wszystkie istniejące dropy.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'auto_populate');
    formData.append('case_id', currentDropCaseId);
    
    fetch('api/drops.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadDrops(currentDropCaseId);
        } else {
            showNotification(data.message || 'Błąd podczas wypełniania losowo', 'error');
        }
    })
    .catch(error => {
        console.error('Błąd:', error);
        showNotification('Błąd połączenia z serwerem', 'error');
    });
}

function normalizeDropRates() {
    if (!confirm('Czy na pewno chcesz wyrównać szanse dropów tak, aby łącznie wynosiły 100%?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'normalize_rates');
    formData.append('case_id', currentDropCaseId);
    
    fetch('api/drops.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadDrops(currentDropCaseId);
        } else {
            showNotification(data.message || 'Błąd podczas wyrównywania szans', 'error');
        }
    })
    .catch(error => {
        console.error('Błąd:', error);
        showNotification('Błąd połączenia z serwerem', 'error');
    });
}

function saveDropChanges() {
    showNotification('Wszystkie zmiany są automatycznie zapisywane w bazie danych', 'info');
}

// Funkcja analizy balansu skrzynki
function analyzeCaseBalance(caseId) {
    fetch(`api/drops.php?action=analyze_balance&case_id=${caseId}`)
        .then(response => response.json())
        .then(data => {
            const balanceElement = document.getElementById(`balance-${caseId}`);
            if (!balanceElement) return;
            
            if (data.success) {
                const analysis = data.data;
                let statusClass = '';
                let icon = '';
                
                switch (analysis.status) {
                    case 'good':
                        statusClass = 'text-green-400';
                        icon = 'fa-check-circle';
                        break;
                    case 'warning':
                        statusClass = 'text-yellow-400';
                        icon = 'fa-exclamation-triangle';
                        break;
                    case 'bad':
                        statusClass = 'text-red-400';
                        icon = 'fa-times-circle';
                        break;
                    default:
                        statusClass = 'text-gray-400';
                        icon = 'fa-question-circle';
                }
                
                let tooltip = `📊 STATYSTYKI SKRZYNKI:\\n`;
                tooltip += `• Liczba dropów: ${analysis.stats.total_drops}\\n`;
                tooltip += `• Suma szans: ${analysis.stats.total_rate.toFixed(1)}%\\n`;
                tooltip += `• Oczekiwana wartość: €${analysis.stats.expected_value.toFixed(2)}\\n`;
                tooltip += `• Średnia cena dropów: €${analysis.stats.avg_value.toFixed(2)}`;
                
                if (analysis.issues.length > 0) {
                    tooltip += '\\n\\n🚨 WYKRYTE PROBLEMY:\\n';
                    analysis.issues.forEach((issue, index) => {
                        tooltip += `${index + 1}. ${issue}\\n`;
                    });
                    
                    tooltip += '\\n💡 ZALECENIA:\\n';
                    if (analysis.issues.some(i => i.includes('nie sumują się do 100%'))) {
                        tooltip += '• Użyj przycisku "Wyrównaj szanse"\\n';
                    }
                    if (analysis.issues.some(i => i.includes('za droga'))) {
                        tooltip += '• Obniż cenę skrzynki lub dodaj droższe dropy\\n';
                    }
                    if (analysis.issues.some(i => i.includes('za tania'))) {
                        tooltip += '• Podnieś cenę skrzynki lub dodaj tańsze dropy\\n';
                    }
                    if (analysis.issues.some(i => i.includes('bardzo rzadkich'))) {
                        tooltip += '• Zwiększ szanse dla niektórych rzadkich dropów\\n';
                    }
                    if (analysis.issues.some(i => i.includes('za częste'))) {
                        tooltip += '• Zmniejsz szanse dla najczęstszych dropów\\n';
                    }
                } else {
                    tooltip += '\\n\\n✅ Skrzynka jest idealnie zbalansowana!';
                }
                
                balanceElement.innerHTML = `
                    <div class="${statusClass} flex items-center cursor-help relative balance-tooltip-trigger" data-tooltip-id="tooltip-${caseId}">
                        <i class="fa-solid ${icon} mr-1"></i>
                        <span class="text-xs">${analysis.message}</span>
                        <div id="tooltip-${caseId}" class="balance-tooltip hidden absolute z-50 bg-gray-900 text-white text-xs rounded-lg p-3 shadow-lg border border-gray-600 w-80 -top-2 left-full ml-2">
                            <div class="font-semibold text-blue-300 mb-2">📊 STATYSTYKI SKRZYNKI:</div>
                            <div class="mb-1">• Liczba dropów: <span class="text-yellow-300">${analysis.stats.total_drops}</span></div>
                            <div class="mb-1">• Suma szans: <span class="text-yellow-300">${analysis.stats.total_rate.toFixed(1)}%</span></div>
                            <div class="mb-1">• Oczekiwana wartość: <span class="text-green-300">€${analysis.stats.expected_value.toFixed(2)}</span></div>
                            <div class="mb-3">• Średnia cena dropów: <span class="text-green-300">€${analysis.stats.avg_value.toFixed(2)}</span></div>
                            ${analysis.issues.length > 0 ? `
                                <div class="font-semibold text-red-300 mb-2">🚨 WYKRYTE PROBLEMY:</div>
                                ${analysis.issues.map((issue, index) => `<div class="mb-1 text-red-200">${index + 1}. ${issue}</div>`).join('')}
                                ${analysis.suggestions && analysis.suggestions.length > 0 ? `
                                    <div class="font-semibold text-yellow-300 mb-2 mt-3">🔧 KONKRETNE SUGESTIE:</div>
                                    <div class="text-yellow-200">
                                        ${analysis.suggestions.map((suggestion, index) => `• ${suggestion}<br>`).join('')}
                                    </div>
                                ` : ''}
                                <div class="font-semibold text-green-300 mb-2 mt-3">💡 SZYBKIE AKCJE:</div>
                                <div class="text-green-200">
                                    ${analysis.issues.some(i => i.includes('nie sumują się do 100%')) ? '• Użyj przycisku "Wyrównaj szanse"<br>' : ''}
                                    ${analysis.issues.some(i => i.includes('Za wysoka marża')) ? '• Obniż cenę skrzynki lub dodaj droższe dropy<br>' : ''}
                                    ${analysis.issues.some(i => i.includes('Za niska marża')) ? '• Podnieś cenę skrzynki lub usuń drogie dropy<br>' : ''}
                                    ${analysis.issues.some(i => i.includes('bardzo rzadkich')) ? '• Zwiększ szanse dla niektórych rzadkich dropów<br>' : ''}
                                    ${analysis.issues.some(i => i.includes('za częste')) ? '• Zmniejsz szanse dla najczęstszych dropów<br>' : ''}
                                    ${analysis.issues.some(i => i.includes('Brak atrakcyjnych')) ? '• Dodaj dropy warte 2x+ ceny skrzynki<br>' : ''}
                                    ${analysis.issues.some(i => i.includes('Brak jackpot')) ? '• Dodaj bardzo drogie dropy (5x+ wartość)<br>' : ''}
                                    ${analysis.issues.some(i => i.includes('Za mało tanich')) ? '• Dodaj więcej tanich dropów jako "pocieszenia"<br>' : ''}
                                </div>
                            ` : `
                                <div class="font-semibold text-green-300">✅ Skrzynka jest idealnie zbalansowana!</div>
                            `}
                        </div>
                    </div>
                `;
                
                // Aktualizuj data-balance dla filtrowania
                const row = balanceElement.closest('.case-row');
                if (row) {
                    row.dataset.balance = analysis.status;
                }
            } else {
                balanceElement.innerHTML = '<span class="text-red-400 text-xs">Błąd analizy</span>';
            }
        })
        .catch(error => {
            console.error('Błąd analizy balansu:', error);
            const balanceElement = document.getElementById(`balance-${caseId}`);
            if (balanceElement) {
                balanceElement.innerHTML = '<span class="text-gray-400 text-xs">Niedostępne</span>';
            }
        })
        .finally(() => {
            // Setup tooltips po zakończeniu analizy
            setTimeout(setupBalanceTooltips, 100);
        });
}

// Analizuj balans wszystkich skrzynek po załadowaniu strony
function analyzeAllCases() {
    // Znajdź wszystkie elementy balance-*
    const balanceElements = document.querySelectorAll('[id^="balance-"]');
    balanceElements.forEach(element => {
        const caseId = element.id.replace('balance-', '');
        if (caseId) {
            // Dodaj małe opóźnienie między requestami
            setTimeout(() => {
                analyzeCaseBalance(caseId);
            }, Math.random() * 1000);
        }
    });
}

// Obsługa tooltipów dla balansu
function setupBalanceTooltips() {
    // Usuń stare event listenery
    document.querySelectorAll('.balance-tooltip-trigger').forEach(trigger => {
        const tooltipId = trigger.getAttribute('data-tooltip-id');
        const tooltip = document.getElementById(tooltipId);
        
        if (tooltip) {
            trigger.addEventListener('mouseenter', () => {
                tooltip.classList.remove('hidden');
            });
            
            trigger.addEventListener('mouseleave', () => {
                tooltip.classList.add('hidden');
            });
        }
    });
}

// Zmienne do sortowania i filtrowania
let currentSort = { column: '', direction: 'asc' };
let allCases = [];

// Funkcja sortowania tabeli
function sortTable(column) {
    const tbody = document.getElementById('casesTableBody');
    const rows = Array.from(tbody.querySelectorAll('.case-row'));
    
    // Zmień kierunek sortowania
    if (currentSort.column === column) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.column = column;
        currentSort.direction = 'asc';
    }
    
    // Sortuj wiersze
    rows.sort((a, b) => {
        let aVal, bVal;
        
        switch (column) {
            case 'name':
                aVal = a.dataset.name.toLowerCase();
                bVal = b.dataset.name.toLowerCase();
                break;
            case 'price':
                aVal = parseFloat(a.dataset.price);
                bVal = parseFloat(b.dataset.price);
                break;
            case 'status':
                aVal = a.dataset.status;
                bVal = b.dataset.status;
                break;
            case 'opens':
                aVal = parseInt(a.dataset.opens);
                bVal = parseInt(b.dataset.opens);
                break;
            default:
                return 0;
        }
        
        if (aVal < bVal) return currentSort.direction === 'asc' ? -1 : 1;
        if (aVal > bVal) return currentSort.direction === 'asc' ? 1 : -1;
        return 0;
    });
    
    // Aktualizuj ikony sortowania
    document.querySelectorAll('th i.fa-sort, th i.fa-sort-up, th i.fa-sort-down').forEach(icon => {
        icon.className = 'fa-solid fa-sort ml-1';
    });
    
    const currentHeader = document.querySelector(`th[onclick="sortTable('${column}')"] i`);
    if (currentHeader) {
        currentHeader.className = `fa-solid fa-sort-${currentSort.direction === 'asc' ? 'up' : 'down'} ml-1`;
    }
    
    // Przebuduj tabelę
    rows.forEach(row => tbody.appendChild(row));
}

// Funkcja wyszukiwania i filtrowania
function filterTable() {
    console.log('filterTable wywołane');
    const searchInput = document.getElementById('searchCases');
    const statusSelect = document.getElementById('statusFilter');
    const balanceSelect = document.getElementById('balanceFilter');
    
    if (!searchInput || !statusSelect || !balanceSelect) {
        console.error('Nie znaleziono elementów filtrowania');
        return;
    }
    
    const searchTerm = searchInput.value.toLowerCase();
    const statusFilter = statusSelect.value;
    const balanceFilter = balanceSelect.value;
    
    console.log('Filtry:', { searchTerm, statusFilter, balanceFilter });
    
    const rows = document.querySelectorAll('.case-row');
    console.log('Znaleziono wierszy:', rows.length);
    
    rows.forEach(row => {
        const name = row.dataset.name ? row.dataset.name.toLowerCase() : '';
        const status = row.dataset.status || '';
        const balance = row.dataset.balance || '';
        
        let showRow = true;
        
        // Filtr wyszukiwania
        if (searchTerm && !name.includes(searchTerm)) {
            showRow = false;
        }
        
        // Filtr statusu
        if (statusFilter && status !== statusFilter) {
            showRow = false;
        }
        
        // Filtr balansu
        if (balanceFilter && balance !== balanceFilter) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
        console.log(`Wiersz ${name}: ${showRow ? 'pokazany' : 'ukryty'}`);
    });
}

// Event listenery
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM załadowany, szukam elementów...');
    
    // Wyszukiwanie
    const searchInput = document.getElementById('searchCases');
    console.log('searchCases element:', searchInput);
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            console.log('Wyszukiwanie zmienione:', this.value);
            filterTable();
        });
        console.log('Event listener dodany do searchCases');
    } else {
        console.error('Nie znaleziono elementu searchCases');
    }
    
    // Filtry
    const statusFilter = document.getElementById('statusFilter');
    console.log('statusFilter element:', statusFilter);
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            console.log('Status filter zmieniony:', this.value);
            filterTable();
        });
        console.log('Event listener dodany do statusFilter');
    } else {
        console.error('Nie znaleziono elementu statusFilter');
    }
    
    const balanceFilter = document.getElementById('balanceFilter');
    console.log('balanceFilter element:', balanceFilter);
    if (balanceFilter) {
        balanceFilter.addEventListener('change', function() {
            console.log('Balance filter zmieniony:', this.value);
            filterTable();
        });
        console.log('Event listener dodany do balanceFilter');
    } else {
        console.error('Nie znaleziono elementu balanceFilter');
    }
    
    // Analizuj balans skrzynek po załadowaniu
    setTimeout(() => {
        analyzeAllCases();
        // Setup tooltips po analizie
        setTimeout(setupBalanceTooltips, 2000);
    }, 500);
    // Edit Case Form Handler (moved out of deleteCase to ensure it always binds)
    const editCaseForm = document.getElementById('editCaseForm');
    if (editCaseForm) {
        editCaseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = editCaseForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Zapisywanie...';
            const formData = new FormData(editCaseForm);

            fetch('../../server/manage_case.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Skrzynka zaktualizowana pomyślnie!', 'success');
                    hideEditCaseModal();
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showNotification(data.message || 'Błąd podczas aktualizacji skrzynki', 'error');
                }
            })
            .catch(() => showNotification('Błąd połączenia z serwerem', 'error'))
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }
    // Add Drop Form Handler
    const addDropForm = document.getElementById('addDropForm');
    if (addDropForm) {
        addDropForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = addDropForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Dodawanie...';
            const formData = new FormData(addDropForm);

            fetch('../../server/manage_drop.php', {
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Błąd połączenia z serwerem', 'error');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }
    
    // Add Case Form Handler
    const addCaseForm = document.getElementById('addCaseForm');
    if (addCaseForm) {
        addCaseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = addCaseForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Dodawanie...';
            const formData = new FormData(addCaseForm);

            fetch('../../server/manage_case.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Skrzynka została dodana pomyślnie!', 'success');
                    addCaseForm.reset();
                    hideAddCaseModal();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Błąd podczas dodawania skrzynki', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Błąd połączenia z serwerem', 'error');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }
    
    // Edit Drop Form Handler
    const editDropForm = document.getElementById('editDropForm');
    if (editDropForm) {
        editDropForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = editDropForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Zapisywanie...';
            const formData = new FormData(editDropForm);

            fetch('../../server/manage_drop.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Drop został zaktualizowany pomyślnie!', 'success');
                    editDropForm.reset();
                    hideEditDropModal();
                    loadDrops(currentDropCaseId); // Reload drops table
                } else {
                    showNotification(data.message || 'Błąd podczas aktualizacji dropa', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Błąd połączenia z serwerem', 'error');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }
    
    // Usunięto funkcjonalność click outside - używamy przycisków X
});

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
        type === 'success' ? 'bg-green-600 text-white' : 
        type === 'error' ? 'bg-red-600 text-white' : 
        'bg-blue-600 text-white'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fa-solid ${
                type === 'success' ? 'fa-check-circle' : 
                type === 'error' ? 'fa-exclamation-circle' : 
                'fa-info-circle'
            } mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Placeholder functions for buttons that aren't implemented yet

function filterCases() {
    // Funkcja filtrowania - do implementacji
    console.log('Filter cases function called');
}

function searchCases() {
    // Funkcja wyszukiwania - do implementacji  
    console.log('Search cases function called');
}


</script>


<div id="addCaseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full border border-gray-700">
            <div class="px-6 py-4 border-b border-gray-600 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white">Dodaj nową skrzynkę</h3>
                <button onclick="hideAddCaseModal()" class="text-gray-400 hover:text-white text-2xl leading-none">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="addCaseForm" class="p-6">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nazwa skrzynki</label>
                    <input type="text" name="name" required class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Cena ($)</label>
                    <input type="number" name="price" step="0.01" required class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                    <select name="status" required class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="active">Aktywna</option>
                        <option value="inactive">Nieaktywna</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Obrazek (URL)</label>
                    <input type="url" name="image" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="https://example.com/image.png">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideAddCaseModal()" class="px-4 py-2 text-gray-300 bg-gray-700 border border-gray-600 rounded-md hover:bg-gray-600">Anuluj</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Dodaj skrzynkę</button>
                </div>
            </form>
        </div>
    </div>
</div>
