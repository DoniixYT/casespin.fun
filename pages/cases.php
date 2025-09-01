<?php
// Check if user is logged in
if (!isset($_SESSION['user_unique']) || empty($_SESSION['user_unique'])) {
    header("Location: /auth/login.php");
    exit();
}

// Saldo użytkownika jest już ustawione w header.php - nie modyfikujemy go tutaj
$user_unique = $_SESSION['user_unique'];

// Get all active cases
$cases_query = $conn->query("SELECT * FROM cases WHERE is_active = 1 ORDER BY price ASC");
?>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900">


    <!-- Cases Grid -->
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php while ($case = $cases_query->fetch_assoc()): ?>
            <div class="case-card bg-gray-800 rounded-xl overflow-hidden shadow-2xl transform hover:scale-105 transition-all duration-300 border border-gray-700 hover:border-purple-500">
                <!-- Case Image -->
                <div class="relative h-48 bg-gradient-to-br from-purple-600 to-blue-600 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black opacity-30"></div>
                    <i class="fas fa-treasure-chest text-6xl text-white relative z-10"></i>
                    <div class="absolute top-3 right-3 bg-<?php echo $case['rarity'] == 'legendary' ? 'yellow' : ($case['rarity'] == 'epic' ? 'purple' : ($case['rarity'] == 'rare' ? 'blue' : 'gray')); ?>-500 text-white px-2 py-1 rounded-full text-xs font-bold uppercase">
                        <?php echo $case['rarity']; ?>
                    </div>
                </div>

                <!-- Case Info -->
                <div class="p-6">
                    <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($case['name']); ?></h3>
                    <p class="text-gray-400 text-sm mb-4"><?php echo htmlspecialchars($case['description']); ?></p>
                    
                    <!-- Price and Open Button -->
                    <div class="flex items-center justify-between">
                        <div class="text-2xl font-bold text-yellow-400">
                            $<?php echo number_format($case['price'], 2); ?>
                        </div>
                        <button 
                            data-case-id="<?php echo $case['id']; ?>"
                            data-case-price="<?php echo (float)$case['price']; ?>"
                            onclick="openCase(<?php echo json_encode($case['id']); ?>, <?php echo json_encode((float)$case['price']); ?>)"
                            class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 <?php echo $user_balance < $case['price'] ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                            <?php echo $user_balance < $case['price'] ? 'disabled' : ''; ?>
                        >
                            <i class="fas fa-key mr-2"></i>Open
                        </button>
                    </div>
                </div>

                <!-- Preview Items -->
                <div class="px-6 pb-6">
                    <div class="text-xs text-gray-500 mb-2">Contains:</div>
                    <div class="flex flex-wrap gap-1">
                        <?php
                        $items_query = $conn->query("
                            SELECT ci.name, ci.rarity, ci.value 
                            FROM case_items ci 
                            JOIN case_drops cd ON ci.id = cd.item_id 
                            WHERE cd.case_id = {$case['id']} 
                            ORDER BY ci.value DESC 
                            LIMIT 4
                        ");
                        while ($item = $items_query->fetch_assoc()):
                        ?>
                        <span class="bg-<?php echo $item['rarity'] == 'legendary' ? 'yellow' : ($item['rarity'] == 'epic' ? 'purple' : ($item['rarity'] == 'rare' ? 'blue' : 'gray')); ?>-500/20 text-<?php echo $item['rarity'] == 'legendary' ? 'yellow' : ($item['rarity'] == 'epic' ? 'purple' : ($item['rarity'] == 'rare' ? 'blue' : 'gray')); ?>-300 px-2 py-1 rounded text-xs">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </span>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Recent Drops removed - now using global component -->
</div>

<?php
// Include global components for logged in users
if (isset($_SESSION['user_unique']) && !empty($_SESSION['user_unique'])) {
    include 'components/global_recent_drops.php';
    include 'components/global_inventory.php';
}
?>

<!-- Case Opening Modal -->
<div id="case-opening-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-xl p-8 max-w-md w-full mx-4 border border-gray-700">
        <div class="text-center">
            <div id="opening-animation" class="mb-6">
                <i class="fas fa-box-open text-6xl text-purple-500 animate-pulse"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-4">Opening Case...</h3>
            <div class="bg-gray-700 rounded-lg p-4 mb-6">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto"></div>
            </div>
            <div id="case-result" class="hidden">
                <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg p-6 mb-4">
                    <div id="item-icon" class="text-4xl mb-2"></div>
                    <div id="item-name" class="text-xl font-bold text-white mb-2"></div>
                    <div id="item-value" class="text-2xl font-bold text-yellow-400"></div>
                </div>
                <button onclick="closeModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold">
                    Collect Item
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let isOpening = false;

// openCase function is now defined in home.php
// Removed old definition to prevent conflicts with the main implementation

function showResult(item) {
    document.getElementById('opening-animation').style.display = 'none';
    document.querySelector('#case-opening-modal h3').textContent = 'Congratulations!';
    
    const rarityColors = {
        'common': 'text-gray-400',
        'uncommon': 'text-green-400',
        'rare': 'text-blue-400',
        'epic': 'text-purple-400',
        'legendary': 'text-yellow-400',
        'mythic': 'text-red-400'
    };
    
    document.getElementById('item-icon').innerHTML = '<i class="fas fa-gem ' + (rarityColors[item.rarity] || 'text-gray-400') + '"></i>';
    document.getElementById('item-name').textContent = item.name;
    document.getElementById('item-value').textContent = '$' + parseFloat(item.value).toFixed(2);
    
    document.getElementById('case-result').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('case-opening-modal').classList.add('hidden');
    document.getElementById('opening-animation').style.display = 'block';
    isOpening = false;
    
    // Reload page to update balance
    location.reload();
}

function OLD_loadRecentDrops_CASES_DISABLED() {
    // WYŁĄCZONE - używamy loadRecentDropsData z home.php
    console.log('OLD_loadRecentDrops_CASES_DISABLED - funkcja wyłączona');
    return;
}

// Load recent drops on page load - WYŁĄCZONE
// document.addEventListener('DOMContentLoaded', loadRecentDrops);
</script>

<style>
.case-card {
    background: linear-gradient(145deg, #1f2937, #111827);
}

.case-card:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04), 0 0 0 1px rgba(147, 51, 234, 0.5);
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

<!-- Case Opener (roll animation) -->
<script src="assets/js/case-opener.simple.js?v=<?php echo time(); ?>" defer></script>
