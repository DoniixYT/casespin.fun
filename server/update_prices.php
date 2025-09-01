<?php
/**
 * Skrypt do aktualizacji cen skinów CS2 ze Steam Market API
 * Uruchamiać przez cron co kilka godzin
 */

require_once 'config.php';

class SteamPriceUpdater {
    private $db;
    private $steam_api_url = 'https://steamcommunity.com/market/priceoverview/';
    private $app_id = 730; // CS2 App ID
    private $currency = 6; // EUR
    private $delay = 2; // Opóźnienie między requestami (sekundy)
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Pobiera cenę skina ze Steam Market
     */
    private function getSteamPrice($market_name) {
        $url = $this->steam_api_url . '?' . http_build_query([
            'appid' => $this->app_id,
            'currency' => $this->currency,
            'market_hash_name' => $market_name
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept: application/json'
                ],
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['lowest_price'])) {
            return null;
        }
        
        // Konwertuj cenę z formatu "€1,23" na float
        $price_str = $data['lowest_price'];
        $price = floatval(str_replace(['€', ','], ['', '.'], $price_str));
        
        return $price > 0 ? $price : null;
    }
    
    /**
     * Konwertuje nazwę skina na format Steam Market
     */
    private function formatMarketName($name) {
        // Usuń prefix broni i zostaw tylko skin name
        if (strpos($name, ' | ') !== false) {
            return $name; // Już w dobrym formacie
        }
        return $name;
    }
    
    /**
     * Aktualizuje ceny wszystkich skinów
     */
    public function updateAllPrices() {
        echo "Rozpoczynam aktualizację cen skinów CS2...\n";
        
        try {
            // Pobierz wszystkie skiny z bazy
            $stmt = $this->db->prepare("SELECT item_id, name FROM items ORDER BY id");
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updated = 0;
            $failed = 0;
            $total = count($items);
            
            foreach ($items as $item) {
                echo "Aktualizuję: {$item['name']}... ";
                
                $market_name = $this->formatMarketName($item['name']);
                $price = $this->getSteamPrice($market_name);
                
                if ($price !== null) {
                    // Aktualizuj cenę w bazie
                    $update_stmt = $this->db->prepare(
                        "UPDATE items SET price = ?, updated_at = NOW() WHERE item_id = ?"
                    );
                    
                    if ($update_stmt->execute([$price, $item['item_id']])) {
                        echo "OK (€{$price})\n";
                        $updated++;
                    } else {
                        echo "BŁĄD BAZY\n";
                        $failed++;
                    }
                } else {
                    echo "BRAK CENY\n";
                    $failed++;
                }
                
                // Opóźnienie żeby nie przeciążyć Steam API
                sleep($this->delay);
            }
            
            echo "\n=== PODSUMOWANIE ===\n";
            echo "Łącznie skinów: {$total}\n";
            echo "Zaktualizowano: {$updated}\n";
            echo "Niepowodzenia: {$failed}\n";
            echo "Ukończono: " . date('Y-m-d H:i:s') . "\n";
            
            // Zapisz log do bazy
            $this->logUpdate($total, $updated, $failed);
            
        } catch (Exception $e) {
            echo "BŁĄD: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Aktualizuje tylko wybrane skiny (najdroższe/najczęściej używane)
     */
    public function updatePopularPrices() {
        echo "Aktualizuję ceny popularnych skinów...\n";
        
        // Lista najważniejszych skinów do częstej aktualizacji
        $popular_items = [
            'AWP_Lightning_Strike',
            'AK-47_Case_Hardened', 
            'Desert_Eagle_Hypnotic',
            'Glock-18_Fade',
            'Desert_Eagle_Blaze',
            'USP-S_Dark_Water',
            'M4A1-S_Dark_Water',
            'Glock-18_Dragon_Tattoo'
        ];
        
        try {
            $updated = 0;
            
            foreach ($popular_items as $item_id) {
                $stmt = $this->db->prepare("SELECT name FROM items WHERE item_id = ?");
                $stmt->execute([$item_id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$item) continue;
                
                echo "Aktualizuję: {$item['name']}... ";
                
                $price = $this->getSteamPrice($item['name']);
                
                if ($price !== null) {
                    $update_stmt = $this->db->prepare(
                        "UPDATE items SET price = ?, updated_at = NOW() WHERE item_id = ?"
                    );
                    
                    if ($update_stmt->execute([$price, $item_id])) {
                        echo "OK (€{$price})\n";
                        $updated++;
                    } else {
                        echo "BŁĄD BAZY\n";
                    }
                } else {
                    echo "BRAK CENY\n";
                }
                
                sleep($this->delay);
            }
            
            echo "Zaktualizowano {$updated} popularnych skinów.\n";
            
        } catch (Exception $e) {
            echo "BŁĄD: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Zapisuje log aktualizacji
     */
    private function logUpdate($total, $updated, $failed) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO price_update_log (total_items, updated_items, failed_items, update_time) 
                 VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$total, $updated, $failed]);
        } catch (Exception $e) {
            echo "Nie można zapisać logu: " . $e->getMessage() . "\n";
        }
    }
}

// Sprawdź czy skrypt jest uruchamiany z linii komend
if (php_sapi_name() === 'cli') {
    // Uruchomienie z terminala
    $mode = isset($argv[1]) ? $argv[1] : 'popular';
    
    try {
        $updater = new SteamPriceUpdater($pdo);
        
        if ($mode === 'all') {
            $updater->updateAllPrices();
        } else {
            $updater->updatePopularPrices();
        }
        
    } catch (Exception $e) {
        echo "BŁĄD POŁĄCZENIA Z BAZĄ: " . $e->getMessage() . "\n";
        exit(1);
    }
    
} else {
    // Uruchomienie przez przeglądarkę (tylko dla testów)
    if (isset($_GET['key']) && $_GET['key'] === 'update_prices_2025') {
        header('Content-Type: text/plain; charset=utf-8');
        
        $mode = isset($_GET['mode']) ? $_GET['mode'] : 'popular';
        
        try {
            $updater = new SteamPriceUpdater($pdo);
            
            if ($mode === 'all') {
                $updater->updateAllPrices();
            } else {
                $updater->updatePopularPrices();
            }
            
        } catch (Exception $e) {
            echo "BŁĄD: " . $e->getMessage() . "\n";
        }
        
    } else {
        http_response_code(403);
        echo "Dostęp zabroniony";
    }
}
?>
