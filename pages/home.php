<?php
// Check if user is logged in
$is_logged_in = isset($_SESSION['user_unique']) && !empty($_SESSION['user_unique']);

// Saldo użytkownika jest już ustawione w header.php - nie modyfikujemy go tutaj

// Get all active cases - sprawdź czy baza danych jest dostępna
if ($conn && !$conn->connect_error) {
    $cases_query = $conn->query("SELECT * FROM cases WHERE is_active = 1 ORDER BY price ASC");
} else {
    // Baza danych niedostępna - utwórz przykładowe skrzynki
    $cases_query = null;
    $sample_cases = [
        ['id' => 1, 'name' => 'Loading...', 'price' => 0.00, 'image' => ''],
        ['id' => 2, 'name' => 'Loading...', 'price' => 0.00, 'image' => ''],
        ['id' => 3, 'name' => 'Loading...', 'price' => 0.00, 'image' => ''],
        ['id' => 4, 'name' => 'Loading...', 'price' => 0.00, 'image' => ''],
        ['id' => 5, 'name' => 'Loading...', 'price' => 0.00, 'image' => ''],
        ['id' => 6, 'name' => 'Loading...', 'price' => 0.00, 'image' => '']
    ];
}
?>

<!-- Main Content -->
<section class="min-h-screen">
    <!-- Cases Section (Recent Drops moved to pages/home_recent_drops.php) -->
        <!-- Case Opening Confirm Modal -->
    <div id="case-confirm-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 w-96 max-w-md mx-4 shadow-2xl transform transition-all duration-300 hover:scale-105">
            <div class="text-center mb-6">
                <i class="fas fa-treasure-chest text-4xl text-purple-400 mb-4"></i>
                <h2 class="text-white text-2xl font-bold mb-2">Open Case</h2>
                <p class="text-gray-400 text-sm">You are about to open a case. Please confirm to proceed.</p>
            </div>
            <div class="flex gap-3">
                <button id="cancel-case-opening" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button id="confirm-case-opening" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-check mr-2"></i>Confirm
                </button>
                </div>
            </div>
    </div>

    <div class="bleed-row mx-auto w-full">
        <div class="mainnav">
          <div class="w-full mx-auto">
            <div class="mb-12">
                <div class="overflow-visible mb-8">


              <style>
              /* Układ: dokładnie 6 w rzędzie (Grid) */
              div#cases-grid { 
                width: calc(6 * 226.656px + 5 * 16px) !important; /* dokładna szerokość 6 kolumn + odstępy */
                display: grid !important;
                grid-template-columns: repeat(6, 226.656px) !important;
                grid-auto-rows: 226.656px !important;
                gap: 16px !important;
                justify-content: center !important;
                margin: 0 auto !important; /* klasyczne centrowanie */
                position: relative !important;
              }

              /* Upewnij się, że nadrzędne kontenery nie ucinają zawartości */
              .bleed-row,
              .mainnav,
              .w-full.mx-auto {
                overflow: visible !important;
              }
              div#cases-grid > div {
                display: block !important;
                width: 100% !important; /* nadpisuje inline width */
                margin-right: 0 !important;
                vertical-align: top !important;
                box-sizing: border-box !important;
              }
              div#cases-grid > div:last-child {
                padding-right: 0 !important;
              }
              div#cases-grid .case-card { 
                display: flex !important; 
                flex-direction: column !important; 
                width: 100% !important;  /* wypełnia komórkę grida */
                height: 100% !important; /* wypełnia komórkę grida */
                min-height: 0 !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
              }
              div#cases-grid .case-card .case-media { 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                flex: 1 1 auto !important;
                min-height: 0 !important;
                width: 100% !important;
              }
              
              /* Usunięto globalne nadpisania Tailwinda (.grid, .grid-cols-*) aby nie psuć layoutu aplikacji */
            </style>

            <div class="py-24 md:py-32" style="width: 100%; max-width: none; padding: 0; display: flex; justify-content: center;">
                        <div id="cases-grid" style="width: calc(6 * 226.656px + 5 * 16px) !important; font-size: 0 !important; margin: 0 auto !important;">
            <?php 
            // Użyj danych z bazy lub przykładowych skrzynek
            $cases_to_display = [];
            if ($cases_query && $cases_query->num_rows > 0) {
                while ($case = $cases_query->fetch_assoc()) {
                    // Get the rarest item in this case to determine case rarity
                    if ($conn && !$conn->connect_error) {
                        $rarity_query = $conn->prepare("SELECT i.rarity FROM case_items ci 
                                                       LEFT JOIN items i ON ci.item_id = i.item_id 
                                                       WHERE ci.case_id = ? 
                                                       ORDER BY FIELD(i.rarity, 'Consumer', 'Industrial', 'Mil-spec', 'Restricted', 'Classified', 'Covert') DESC 
                                                       LIMIT 1");
                        $rarity_query->bind_param("i", $case['id']);
                        $rarity_query->execute();
                        $rarity_result = $rarity_query->get_result();
                        $rarity_row = $rarity_result->fetch_assoc();
                        
                        // Map CS2 rarity to display rarity
                        $rarity_map = [
                            'Consumer' => 'common',
                            'Industrial' => 'common', 
                            'Mil-spec' => 'rare',
                            'Restricted' => 'rare',
                            'Classified' => 'epic',
                            'Covert' => 'legendary',
                            'Contraband' => 'legendary'
                        ];
                        
                        $case['rarity'] = $rarity_map[$rarity_row['rarity']] ?? 'common';
                        $rarity_query->close();
                    } else {
                        $case['rarity'] = 'common';
                    }
                    $cases_to_display[] = $case;
                }
            } else {
                // Przykładowe skrzynki jeśli brak połączenia z bazą
                $cases_to_display = [
                    ['id' => 1, 'name' => 'Starter Box', 'price' => 1.00, 'rarity' => 'common'],
                    ['id' => 2, 'name' => 'Silver Case', 'price' => 5.00, 'rarity' => 'rare'],
                    ['id' => 3, 'name' => 'Gold Chest', 'price' => 15.00, 'rarity' => 'epic'],
                    ['id' => 4, 'name' => 'Diamond Vault', 'price' => 50.00, 'rarity' => 'legendary'],
                    ['id' => 5, 'name' => 'Premium Crate', 'price' => 25.00, 'rarity' => 'epic'],
                    ['id' => 6, 'name' => 'Elite Vault', 'price' => 100.00, 'rarity' => 'legendary']
                ];
            }
            
            foreach ($cases_to_display as $case): 
                $case_rarity = $case['rarity'];
            ?>
            <div style="display: block !important; vertical-align: top !important; box-sizing: border-box !important;">
            <a href="<?php echo $is_logged_in ? ('?p=case/'.str_replace(' ', '_', $case['name'])) : '#'; ?>" 
               class="case-card group block" 
               style="width: 100%; height: 100%; min-height: 0; background: transparent; border-radius: 1rem; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); display: flex; flex-direction: column; transform: translateY(0); transition: all 0.3s; border: 1px solid rgba(75, 85, 99, 0.7); position: relative; cursor: pointer; font-size: 1rem; text-decoration: none;"
               data-name="<?php echo htmlspecialchars($case['name']); ?>"
               data-price="<?php echo number_format($case['price'], 2, '.', ''); ?>"
               data-rarity="<?php echo $case_rarity; ?>"
               <?php if (!$is_logged_in): ?>onclick="event.preventDefault(); showLoginPrompt();"<?php endif; ?>>

                <!-- Case Image -->
                <div class="case-media relative h-52 md:h-64 xl:h-72 bg-transparent flex items-center justify-center overflow-hidden">
                    <!-- Animated Background Pattern -->
                    <!-- bg pattern removed -->
                    
                    <!-- Case Icon -->
                    <div class="relative z-10 transform group-hover:scale-105 transition-transform duration-300">
                        <i class="fas fa-treasure-chest text-6xl md:text-7xl xl:text-8xl text-white drop-shadow-2xl"></i>
                    </div>
                </div>

                <!-- Pasek info: nazwa po LEWEJ (max do lewej), cena po PRAWEJ; hover: nazwa -> Open -->
                <div class="px-4 py-3">
                  <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                      <span class="block text-sm font-semibold text-left text-white truncate group-hover:hidden" title="<?php echo htmlspecialchars($case['name']); ?>"><?php echo htmlspecialchars($case['name']); ?></span>
                      <span class="hidden group-hover:block text-sm font-semibold text-left text-emerald-400 uppercase tracking-wide">Open</span>
                    </div>
                    <span class="text-sm font-semibold text-white">$<?php echo number_format($case['price'], 2); ?></span>
                  </div>
                </div>
            </a>
            </div>
                <?php endforeach; ?>
            </div>
            
        </div>
          </div>
        </div>
    </div>

    <!-- Global FAB jest wstawiany w index.php -->

</section>






<!-- CS:GO Style Case Opening Modal -->
<div id="case-opening-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-gray-900 rounded-xl p-8 max-w-4xl w-full mx-4 border border-gray-700">
        <div class="text-center">
            <h3 class="text-3xl font-bold text-white mb-8" id="modal-title">Opening Case...</h3>
            
            <!-- CS:GO Style Rolling Animation -->
            <div id="roll-container" class="relative bg-gray-800 rounded-lg p-4 mb-6 overflow-hidden" style="height: 200px;">
                <div class="absolute top-1/2 left-1/2 h-full bg-yellow-400 transform -translate-x-1/2 -translate-y-1/2 z-10" style="width:2px"></div>
                <div id="roll-items" class="flex items-center h-full transition-transform duration-[4000ms] ease-out" style="transform: translateX(0px);">
                    <!-- Items will be populated here -->
                </div>
            </div>
            
            <!-- Result Display -->
            <div id="case-result" class="hidden">
                <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg p-6 mb-4">
                    <div id="result-item-icon" class="text-6xl mb-4"></div>
                    <div id="result-item-name" class="text-2xl font-bold text-white mb-2"></div>
                    <div id="result-item-rarity" class="text-lg font-semibold mb-2"></div>
                    <div id="result-item-value" class="text-3xl font-bold text-yellow-400 mb-4"></div>
                </div>
                <button id="collect-item-button" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold text-lg">
                    <i class="fas fa-check mr-2"></i>Collect Item
                </button>
            </div>
        </div>
    </div>
</div>

<!-- (Removed duplicate Case Confirmation Modal) -->

<!-- Duplicate modal removed - using the first one above -->





