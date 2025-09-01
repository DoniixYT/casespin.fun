<?php
// Upewnij się, że mamy połączenie z bazą
if (!isset($conn)) {
    // include względny do index.php (głównego pliku)
    @include 'server/conn.php';
}

// Pobierz identyfikator skrzynki z routera: preferuj case_name (np. Elite_Vault) lub case_id
$raw_case_name = isset($_GET['case_name']) ? $_GET['case_name'] : (isset($_GET['case']) ? $_GET['case'] : '');
$case_id_param = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;

$normalized_name = trim(str_replace('_', ' ', $raw_case_name));

$case_row = null;
if ($conn && !$conn->connect_error) {
    if ($case_id_param > 0) {
        // Wyszukaj po ID
        $stmt = mysqli_prepare($conn, "SELECT id, name, description, price, image_url FROM cases WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $case_id_param);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res && mysqli_num_rows($res) > 0) {
                $case_row = mysqli_fetch_assoc($res);
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($normalized_name !== '') {
        // Wyszukaj po nazwie (dokładnej) lub po slugu (REPLACE(name,' ','_'))
        $stmt = mysqli_prepare($conn, "SELECT id, name, description, price, image_url FROM cases WHERE name = ? OR REPLACE(name,' ','_') = ? LIMIT 1");
        if ($stmt) {
            $slug_try = str_replace(' ', '_', $normalized_name);
            mysqli_stmt_bind_param($stmt, 'ss', $normalized_name, $slug_try);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res && mysqli_num_rows($res) > 0) {
                $case_row = mysqli_fetch_assoc($res);
            }
            mysqli_stmt_close($stmt);
        }
        // Dodatkowy fallback: bezpośrednio po oryginalnym surowym znaku (gdy np. nazwa w DB ma już podkreślenia)
        if (!$case_row && $raw_case_name !== '') {
            $stmt2 = mysqli_prepare($conn, "SELECT id, name, description, price, image_url FROM cases WHERE name = ? LIMIT 1");
            if ($stmt2) {
                $raw_spaces = str_replace('_', ' ', $raw_case_name);
                mysqli_stmt_bind_param($stmt2, 's', $raw_spaces);
                mysqli_stmt_execute($stmt2);
                $res2 = mysqli_stmt_get_result($stmt2);
                if ($res2 && mysqli_num_rows($res2) > 0) {
                    $case_row = mysqli_fetch_assoc($res2);
                }
                mysqli_stmt_close($stmt2);
            }
        }
    }
}

if (!$case_row) {
    echo "<div class='text-center text-red-500 mt-10'>Skrzynka nie została znaleziona!</div>";
    return;
}

// Zbuduj dane skrzynki
$case_data = [
    'id' => $case_row['id'] ?? 0,
    'name' => $case_row['name'] ?? 'Case',
    'price' => isset($case_row['price']) ? floatval($case_row['price']) : 0.0,
    'image' => 'assets/img/logo4.png', // Placeholder
    'description' => $case_row['description'] ?? 'No description available for this case.'
];

// Pobierz dropy dla skrzynki (case_items + items)
$case_items = [];
if ($conn && !$conn->connect_error) {
    $cid = intval($case_row['id']);
    $sql = "SELECT ci.drop_rate, i.item_id, i.skin_name, i.weapon_name, i.rarity, i.price, i.image_url
            FROM case_items ci
            JOIN items i ON ci.item_id = i.item_id
            WHERE ci.case_id = ?
            ORDER BY i.price DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $cid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $case_items[] = [
                    'item_id' => $row['item_id'],
                    'skin_name' => $row['skin_name'] ?? '',
                    'weapon_name' => $row['weapon_name'] ?? '',
                    'rarity' => strtolower($row['rarity'] ?? 'common'),
                    'price' => isset($row['price']) ? floatval($row['price']) : 0.0,
                    'image_url' => $row['image_url'] ?? 'assets/img/logo4.png',
                    'drop_chance' => isset($row['drop_rate']) ? floatval($row['drop_rate']) : 0.0,
                ];
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Pobierz balans użytkownika (jeśli zalogowany)
$user_balance = 0.0;
if (isset($_SESSION['user_unique'])) { 
    $user_unique_session = $_SESSION['user_unique'];
    $stmt_balance = mysqli_prepare($conn, "SELECT total_balance FROM user_details WHERE user_unique = ?");
    if ($stmt_balance) {
        mysqli_stmt_bind_param($stmt_balance, 's', $user_unique_session);
        mysqli_stmt_execute($stmt_balance);
        $res_balance = mysqli_stmt_get_result($stmt_balance);
        if ($res_balance && $row_balance = mysqli_fetch_assoc($res_balance)) {
            $user_balance = floatval($row_balance['total_balance']);
        }
        mysqli_stmt_close($stmt_balance);
    }
}

// Mapowanie rzadkości na kolory Tailwind CSS
$rarity_colors = [
    'consumer' => ['border' => 'border-gray-500', 'bg' => 'bg-gray-500/10', 'text' => 'text-gray-300'],
    'industrial' => ['border' => 'border-sky-500', 'bg' => 'bg-sky-500/10', 'text' => 'text-sky-300'],
    'mil-spec' => ['border' => 'border-blue-500', 'bg' => 'bg-blue-500/10', 'text' => 'text-blue-300'],
    'restricted' => ['border' => 'border-purple-500', 'bg' => 'bg-purple-500/10', 'text' => 'text-purple-300'],
    'classified' => ['border' => 'border-pink-500', 'bg' => 'bg-pink-500/10', 'text' => 'text-pink-300'],
    'covert' => ['border' => 'border-red-500', 'bg' => 'bg-red-500/10', 'text' => 'text-red-300'],
    'contraband' => ['border' => 'border-yellow-500', 'bg' => 'bg-yellow-500/10', 'text' => 'text-yellow-300'],
    'common' => ['border' => 'border-gray-500', 'bg' => 'bg-gray-500/10', 'text' => 'text-gray-300'],
    'rare' => ['border' => 'border-blue-500', 'bg' => 'bg-blue-500/10', 'text' => 'text-blue-300'],
    'epic' => ['border' => 'border-purple-500', 'bg' => 'bg-purple-500/10', 'text' => 'text-purple-300'],
    'legendary' => ['border' => 'border-yellow-500', 'bg' => 'bg-yellow-500/10', 'text' => 'text-yellow-300'],
];

?>

<div class="w-full px-4 py-8 text-white">

    <!-- Case Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-4xl font-bold"><?php echo htmlspecialchars($case_data['name']); ?></h1>
            <p class="text-gray-400 text-md mt-1"><?php echo htmlspecialchars($case_data['description'] ?? 'No description available for this case.'); ?></p>
        </div>
    </div>

    <!-- Case Animation Reel -->
    <div id="case-reel-container" class="relative h-48 w-full overflow-hidden mb-8 bg-gray-900/50 rounded-lg border-2 border-purple-500 py-8">
        <!-- Center line -->
        <div class="absolute top-0 bottom-0 left-1/2 w-1 bg-purple-500 z-20" style="transform: translateX(-50%);"></div>
        <!-- Reel track -->
        <div id="case-reel-track" class="case-reel-track absolute top-0 left-0 h-full w-full flex items-center justify-center">
            <!-- Items will be injected here by JavaScript -->
        </div>
    </div>

    <!-- Open Case Section -->
    <div class="flex flex-col items-center justify-center my-8">
        <?php 
        if ($case_data['price'] > 0 && (!isset($_SESSION['user_unique']) || $user_balance < $case_data['price'])): 
            $needed = $case_data['price'] - $user_balance;
        ?>
            <a href="https://casespin.fun/?p=topUp" class="w-full max-w-xs bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-200 text-lg flex items-center justify-center gap-2 text-center">
                <i class="fas fa-wallet"></i>
                <span>Top up $<?php echo number_format($needed, 2); ?> to open</span>
            </a>
        <?php else: ?>
            <button id="open-case-btn" data-case-id="<?php echo $case_data['id']; ?>" data-case-price="<?php echo $case_data['price']; ?>" class="w-full max-w-xs bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-200 text-lg flex items-center justify-center gap-2">
                <i class="fas fa-box-open"></i>
                <span>Open for $<?php echo number_format($case_data['price'], 2); ?></span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Possible Items Grid -->
    <div>
        <h2 class="text-2xl font-bold mb-6">Possible Items</h2>
        <?php if (empty($case_items)): ?>
            <p class="text-gray-400">No items found for this case.</p>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                <?php foreach ($case_items as $item): 
                    $colors = $rarity_colors[$item['rarity']] ?? $rarity_colors['common'];
                ?>
                    <div class="item-card <?php echo $colors['bg']; ?> rounded-lg p-4 border <?php echo $colors['border']; ?> text-center transition-all duration-300 hover:scale-105 hover:shadow-lg h-full flex flex-col justify-between">
                         <div class="flex-grow flex flex-col justify-center items-center">
                            <div class="h-24 flex justify-center items-center mb-3">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="max-h-full max-w-full object-contain">
                            </div>
                            <p class="text-sm font-semibold truncate w-full"><?php echo htmlspecialchars($item['name']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs <?php echo $colors['text']; ?> font-bold uppercase tracking-wider"><?php echo htmlspecialchars($item['rarity']); ?></p>
                            <p class="text-yellow-400 font-bold mt-1">$<?php echo number_format($item['price'], 2); ?></p>
                            <p class="text-gray-500 text-xs mt-1"><?php echo number_format($item['drop_chance'], 2); ?>%</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
    // Przekazanie wszystkich przedmiotów ze skrzynki do JS
    window.caseAllItems = <?php echo json_encode($case_items); ?>;
</script>
<script src="assets/js/case-opener.simple.js?v=<?php echo time(); ?>" defer></script>
