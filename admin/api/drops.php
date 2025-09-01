<?php
// API endpoint do zarządzania dropami (items w skrzynkach)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($conn)) { 
    require_once __DIR__ . '/../config.php'; 
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if (!$conn) {
        throw new Exception('Brak połączenia z bazą danych');
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'get_drops':
            // Pobierz wszystkie drops dla konkretnej skrzynki
            $case_id = (int)($_GET['case_id'] ?? 0);
            
            if ($case_id <= 0) {
                throw new Exception('Nieprawidłowe ID skrzynki');
            }

            $query = "SELECT ci.*, i.skin_name, i.weapon_name, i.rarity, i.price, i.image_url
                      FROM case_items ci
                      JOIN items i ON ci.item_id = i.item_id
                      WHERE ci.case_id = $case_id
                      ORDER BY ci.drop_rate DESC";
            
            $result = $conn->query($query);
            $drops = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $drops[] = $row;
                }
            }
            
            $response['success'] = true;
            $response['data'] = $drops;
            break;

        case 'get_available_items':
            // Pobierz wszystkie dostępne items do dodania do skrzynki
            $search = $conn->real_escape_string($_GET['search'] ?? '');
            $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
            
            $where_clause = '';
            if (!empty($search)) {
                $where_clause = "WHERE skin_name LIKE '%$search%' OR weapon_name LIKE '%$search%' OR item_id LIKE '%$search%'";
            }
            
            $query = "SELECT item_id, skin_name, weapon_name, rarity, price, image_url 
                      FROM items 
                      $where_clause
                      ORDER BY rarity DESC, price DESC 
                      LIMIT $limit";
            
            $result = $conn->query($query);
            $items = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $items[] = $row;
                }
            }
            
            $response['success'] = true;
            $response['data'] = $items;
            break;

        case 'add_drop':
            // Dodaj nowy drop do skrzynki
            $case_id = (int)($_POST['case_id'] ?? 0);
            $item_id = $conn->real_escape_string($_POST['item_id'] ?? '');
            $drop_rate = (float)($_POST['drop_rate'] ?? 0);

            if ($case_id <= 0) {
                throw new Exception('Nieprawidłowe ID skrzynki');
            }

            if (empty($item_id)) {
                throw new Exception('Wybierz item do dodania');
            }

            if ($drop_rate <= 0 || $drop_rate > 100) {
                throw new Exception('Drop rate musi być między 0.01 a 100');
            }

            // Sprawdź czy item już nie istnieje w tej skrzynce
            $check_query = "SELECT id FROM case_items WHERE case_id = $case_id AND item_id = '$item_id'";
            $check_result = $conn->query($check_query);
            
            if ($check_result && $check_result->num_rows > 0) {
                throw new Exception('Ten item już istnieje w tej skrzynce');
            }

            // Sprawdź czy item istnieje w tabeli items
            $item_check = "SELECT item_id FROM items WHERE item_id = '$item_id'";
            $item_result = $conn->query($item_check);
            
            if (!$item_result || $item_result->num_rows === 0) {
                throw new Exception('Item nie istnieje w bazie danych');
            }

            $query = "INSERT INTO case_items (case_id, item_id, drop_rate) 
                      VALUES ($case_id, '$item_id', $drop_rate)";
            
            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = 'Drop został dodany do skrzynki';
                $response['data'] = ['id' => $conn->insert_id];
            } else {
                throw new Exception('Błąd dodawania dropu: ' . $conn->error);
            }
            break;

        case 'update_drop':
            // Aktualizuj drop
            $drop_id = (int)($_POST['drop_id'] ?? 0);
            $drop_rate = (float)($_POST['drop_rate'] ?? 0);

            if ($drop_id <= 0) {
                throw new Exception('Nieprawidłowe ID dropu');
            }

            if ($drop_rate <= 0 || $drop_rate > 100) {
                throw new Exception('Drop rate musi być między 0.01 a 100');
            }

            $query = "UPDATE case_items SET drop_rate = $drop_rate WHERE id = $drop_id";
            
            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = 'Drop rate został zaktualizowany';
            } else {
                throw new Exception('Błąd aktualizacji dropu: ' . $conn->error);
            }
            break;

        case 'delete_drop':
            // Usuń drop ze skrzynki
            $drop_id = (int)($_POST['drop_id'] ?? 0);

            if ($drop_id <= 0) {
                throw new Exception('Nieprawidłowe ID dropu');
            }

            $query = "DELETE FROM case_items WHERE id = $drop_id";
            
            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = 'Drop został usunięty ze skrzynki';
            } else {
                throw new Exception('Błąd usuwania dropu: ' . $conn->error);
            }
            break;

        case 'auto_populate':
            // Automatycznie wypełnij skrzynkę itemami według rzadkości
            $case_id = (int)($_POST['case_id'] ?? 0);
            $rarity_filter = $conn->real_escape_string($_POST['rarity'] ?? '');

            if ($case_id <= 0) {
                throw new Exception('Nieprawidłowe ID skrzynki');
            }

            // Usuń istniejące drops
            $conn->query("DELETE FROM case_items WHERE case_id = $case_id");

            // Mapowanie rzadkości na drop rates
            $rarity_rates = [
                'Consumer' => 40.0,
                'Industrial' => 25.0, 
                'Mil-spec' => 20.0,
                'Restricted' => 10.0,
                'Classified' => 4.0,
                'Covert' => 1.0,
                'Contraband' => 0.1
            ];

            $where_clause = '';
            if (!empty($rarity_filter) && isset($rarity_rates[$rarity_filter])) {
                $where_clause = "WHERE rarity = '$rarity_filter'";
            }

            // Pobierz losowe items z każdej rzadkości
            foreach ($rarity_rates as $rarity => $base_rate) {
                if (!empty($rarity_filter) && $rarity !== $rarity_filter) {
                    continue;
                }

                $query = "SELECT item_id FROM items WHERE rarity = '$rarity' ORDER BY RAND() LIMIT 3";
                $result = $conn->query($query);
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $item_id = $row['item_id'];
                        $drop_rate = $base_rate + (rand(-50, 50) / 100); // Dodaj losową wariację
                        $drop_rate = max(0.01, min(100, $drop_rate)); // Ogranicz do 0.01-100
                        
                        $conn->query("INSERT INTO case_items (case_id, item_id, drop_rate) 
                                     VALUES ($case_id, '$item_id', $drop_rate)");
                    }
                }
            }

            $response['success'] = true;
            $response['message'] = 'Skrzynka została automatycznie wypełniona itemami';
            break;

        case 'normalize_rates':
            // Wyrównaj drop rates tak żeby łącznie wynosiły 100%
            $case_id = (int)($_POST['case_id'] ?? 0);

            if ($case_id <= 0) {
                throw new Exception('Nieprawidłowe ID skrzynki');
            }

            // Pobierz wszystkie dropy dla tej skrzynki
            $query = "SELECT id, drop_rate FROM case_items WHERE case_id = $case_id";
            $result = $conn->query($query);
            
            if (!$result || $result->num_rows === 0) {
                throw new Exception('Brak dropów w tej skrzynce');
            }

            $drops = [];
            $totalRate = 0;
            
            while ($row = $result->fetch_assoc()) {
                $drops[] = $row;
                $totalRate += $row['drop_rate'];
            }

            if ($totalRate <= 0) {
                throw new Exception('Suma drop rates wynosi 0 - nie można wyrównać');
            }

            // Przelicz drop rates proporcjonalnie do 100%
            $updatedCount = 0;
            foreach ($drops as $drop) {
                $newRate = ($drop['drop_rate'] / $totalRate) * 100;
                $newRate = round($newRate, 2); // Zaokrąglij do 2 miejsc po przecinku
                
                $updateQuery = "UPDATE case_items SET drop_rate = $newRate WHERE id = {$drop['id']}";
                if ($conn->query($updateQuery)) {
                    $updatedCount++;
                }
            }

            $response['success'] = true;
            $response['message'] = "Wyrównano szanse dla $updatedCount dropów (łącznie 100%)";
            break;

        case 'analyze_balance':
            // Analizuj balans skrzynki
            $case_id = (int)($_GET['case_id'] ?? 0);

            if ($case_id <= 0) {
                throw new Exception('Nieprawidłowe ID skrzynki');
            }

            // Pobierz informacje o skrzynce
            $case_query = "SELECT price FROM cases WHERE id = $case_id";
            $case_result = $conn->query($case_query);
            
            if (!$case_result || $case_result->num_rows === 0) {
                throw new Exception('Skrzynka nie istnieje');
            }
            
            $case_data = $case_result->fetch_assoc();
            $case_price = $case_data['price'];

            // Pobierz dropy z cenami
            $drops_query = "SELECT ci.drop_rate, i.price 
                           FROM case_items ci
                           JOIN items i ON ci.item_id = i.item_id
                           WHERE ci.case_id = $case_id";
            
            $drops_result = $conn->query($drops_query);
            
            $analysis = [
                'status' => 'unknown',
                'message' => 'Brak dropów',
                'issues' => [],
                'stats' => [
                    'total_drops' => 0,
                    'total_rate' => 0,
                    'avg_value' => 0,
                    'expected_value' => 0
                ]
            ];

            if ($drops_result && $drops_result->num_rows > 0) {
                $drops = [];
                $total_rate = 0;
                $weighted_value = 0;
                
                while ($row = $drops_result->fetch_assoc()) {
                    $drops[] = $row;
                    $total_rate += $row['drop_rate'];
                    $weighted_value += ($row['drop_rate'] / 100) * $row['price'];
                }
                
                $analysis['stats']['total_drops'] = count($drops);
                $analysis['stats']['total_rate'] = $total_rate;
                $analysis['stats']['expected_value'] = $weighted_value;
                $analysis['stats']['avg_value'] = count($drops) > 0 ? array_sum(array_column($drops, 'price')) / count($drops) : 0;
                
                // Analiza problemów
                $issues = [];
                
                // Sprawdź czy suma drop rates wynosi 100%
                if (abs($total_rate - 100) > 1) {
                    $issues[] = "Szanse nie sumują się do 100% (obecnie: " . round($total_rate, 2) . "%)";
                }
                
                // Sprawdź czy expected value jest sensowny względem ceny skrzynki
                $profit_margin = (($case_price - $weighted_value) / $case_price) * 100;
                $house_edge = $profit_margin;
                
                if ($profit_margin > 75) {
                    $issues[] = "Za wysoka marża (" . round($profit_margin, 1) . "%) - klienci szybko się zorientują";
                } elseif ($profit_margin < 15) {
                    $issues[] = "Za niska marża (" . round($profit_margin, 1) . "%) - mały zysk dla domu";
                } elseif ($profit_margin >= 15 && $profit_margin <= 35) {
                    // Idealna marża - nie dodawaj do issues
                }
                
                // Sprawdź czy są bardzo rzadkie dropy (< 1%)
                $very_rare = array_filter($drops, function($d) { return $d['drop_rate'] < 1; });
                if (count($very_rare) > count($drops) * 0.3) {
                    $issues[] = "Za dużo bardzo rzadkich dropów (< 1%)";
                }
                
                // Sprawdź czy są bardzo częste dropy (> 50%)
                $very_common = array_filter($drops, function($d) { return $d['drop_rate'] > 50; });
                if (count($very_common) > 0) {
                    $issues[] = "Niektóre dropy są za częste (> 50%) - klienci stracą zainteresowanie";
                }
                
                // Sprawdź atrakcyjność dla klientów
                $expensive_items = array_filter($drops, function($d) use ($case_price) { 
                    return $d['price'] > $case_price * 2; 
                });
                $jackpot_items = array_filter($drops, function($d) use ($case_price) { 
                    return $d['price'] > $case_price * 5; 
                });
                
                if (count($expensive_items) == 0) {
                    $issues[] = "Brak atrakcyjnych dropów (2x+ wartość skrzynki) - klienci nie będą zainteresowani";
                }
                
                if (count($jackpot_items) == 0 && $case_price > 10) {
                    $issues[] = "Brak jackpot dropów (5x+ wartość) dla droższej skrzynki";
                }
                
                // Sprawdź czy są tanie "pocieszenia"
                $cheap_items = array_filter($drops, function($d) use ($case_price) { 
                    return $d['price'] < $case_price * 0.3; 
                });
                $cheap_rate = 0;
                foreach($cheap_items as $item) {
                    $cheap_rate += $item['drop_rate'];
                }
                
                if ($cheap_rate < 60) {
                    $issues[] = "Za mało tanich dropów (" . round($cheap_rate, 1) . "%) - klienci będą tracić za dużo";
                }
                
                $analysis['issues'] = $issues;
                
                // Generuj konkretne sugestie dropów
                $suggestions = [];
                
                if (count($expensive_items) == 0) {
                    $min_price = $case_price * 2;
                    $suggestions[] = "Dodaj skiny warte co najmniej €" . number_format($min_price, 2) . " (np. AK-47 | Redline, AWP | Asiimov)";
                }
                
                if (count($jackpot_items) == 0 && $case_price > 10) {
                    $jackpot_price = $case_price * 5;
                    $suggestions[] = "Dodaj jackpot drop wart €" . number_format($jackpot_price, 2) . "+ (np. AK-47 | Fire Serpent, AWP | Dragon Lore)";
                }
                
                if ($cheap_rate < 60) {
                    $needed_cheap = 60 - $cheap_rate;
                    $max_cheap_price = $case_price * 0.3;
                    $suggestions[] = "Dodaj " . round($needed_cheap, 1) . "% tanich dropów (max €" . number_format($max_cheap_price, 2) . ") jako pocieszenia";
                }
                
                if (abs($total_rate - 100) > 1) {
                    $suggestions[] = "Użyj przycisku 'Wyrównaj szanse' aby zsumować do 100%";
                }
                
                if ($profit_margin > 75) {
                    $new_price = $weighted_value / 0.65; // 35% marża
                    $suggestions[] = "Obniż cenę skrzynki do €" . number_format($new_price, 2) . " lub dodaj droższe dropy";
                } elseif ($profit_margin < 15) {
                    $new_price = $weighted_value / 0.85; // 15% marża
                    $suggestions[] = "Podnieś cenę do €" . number_format($new_price, 2) . " lub usuń najdroższe dropy";
                }
                
                // Sugestie konkretnych dropów do zmiany
                if (count($very_common) > 0) {
                    foreach ($very_common as $common) {
                        if ($common['drop_rate'] > 50) {
                            $suggestions[] = "Zmniejsz szansę dla '" . $common['weapon_name'] . " | " . $common['skin_name'] . "' z " . $common['drop_rate'] . "% do max 30%";
                        }
                    }
                }
                
                $analysis['suggestions'] = $suggestions;
                
                // Określ status
                if (empty($issues)) {
                    $analysis['status'] = 'good';
                    $analysis['message'] = 'Dobrze zbalansowana';
                } elseif (count($issues) <= 2) {
                    $analysis['status'] = 'warning';
                    $analysis['message'] = 'Wymaga uwagi';
                } else {
                    $analysis['status'] = 'bad';
                    $analysis['message'] = 'Źle zbalansowana';
                }
            }

            $response['success'] = true;
            $response['data'] = $analysis;
            break;

        default:
            throw new Exception('Nieznana akcja: ' . $action);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
