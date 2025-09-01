<?php
/**
 * Skrypt do utworzenia tabeli logów aktualizacji cen
 */

require_once 'config.php';

try {
    // Utwórz tabelę price_update_log
    $sql = "CREATE TABLE IF NOT EXISTS `price_update_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `total_items` int(11) NOT NULL,
        `updated_items` int(11) NOT NULL,
        `failed_items` int(11) NOT NULL,
        `update_time` timestamp NULL DEFAULT current_timestamp(),
        `duration_seconds` int(11) DEFAULT NULL,
        `notes` text DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `update_time` (`update_time`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql);
    echo "✓ Tabela price_update_log została utworzona pomyślnie.\n";
    
    // Dodaj kolumnę updated_at do tabeli items jeśli nie istnieje
    $check_column = $pdo->query("SHOW COLUMNS FROM items LIKE 'updated_at'");
    if ($check_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE items ADD COLUMN updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()");
        echo "✓ Dodano kolumnę updated_at do tabeli items.\n";
    } else {
        echo "✓ Kolumna updated_at już istnieje w tabeli items.\n";
    }
    
    echo "\nTabela do logowania aktualizacji cen jest gotowa!\n";
    echo "Możesz teraz uruchamiać skrypt update_prices.php\n";
    
} catch (PDOException $e) {
    echo "BŁĄD: " . $e->getMessage() . "\n";
    exit(1);
}
?>
