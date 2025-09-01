<?php
if (!isset($conn)) { require_once __DIR__ . '/../config.php'; }

// Konfiguracja paginacji - domyślnie 100 dla szybkości
$items_per_page = isset($_GET['per_page']) ? max(50, min(10000, (int)$_GET['per_page'])) : 100;
$current_page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
$offset = ($current_page - 1) * $items_per_page;

$items = [];
$columns = [];
$total_items = 0;
$total_pages = 0;

try {
    if ($conn) {
        // Policz wszystkie itemki
        $count_res = $conn->query("SELECT COUNT(*) as total FROM items");
        if ($count_res) {
            $total_items = (int)$count_res->fetch_assoc()['total'];
            $total_pages = ceil($total_items / $items_per_page);
        }
        
        // Pobierz itemki dla aktualnej strony - sortuj według ID numerycznie
        $res = $conn->query("SELECT * FROM items ORDER BY id ASC LIMIT $items_per_page OFFSET $offset");
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) { $items[] = $row; }
            $columns = array_keys($items[0]);
        } else {
            $desc = $conn->query("SHOW COLUMNS FROM items");
            if ($desc) { while ($c = $desc->fetch_assoc()) { $columns[] = $c['Field']; } }
        }
    }
} catch (Throwable $e) {}
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function is_img_col($name){ $n = strtolower($name); return strpos($n,'image')!==false || strpos($n,'img')!==false || strpos($n,'icon')!==false; }
function is_price_col($name){ $n = strtolower($name); return strpos($n,'price')!==false || strpos($n,'value')!==false || strpos($n,'cost')!==false; }
?>
<div class="px-4 sm:px-6 lg:px-8">
  <div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
      <h1 class="text-base font-semibold leading-6 text-white">Przedmioty</h1>
      <p class="mt-2 text-sm text-gray-400">
        Łącznie: <?php echo number_format($total_items); ?> | 
        Strona: <?php echo $current_page; ?>/<?php echo $total_pages; ?> | 
        Pokazuję: <?php echo count($items); ?> itemków
      </p>
    </div>
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center gap-3">
      <div class="relative">
        <input id="itemsSearch" type="text" placeholder="Szukaj w całej bazie..." class="input input-bordered w-80 bg-gray-800 text-gray-200 border-gray-600 pr-10" />
        <div id="searchSpinner" class="absolute right-3 top-1/2 transform -translate-y-1/2 hidden">
          <i class="fas fa-spinner fa-spin text-gray-400"></i>
        </div>
      </div>
      <button id="clearSearch" class="btn btn-sm btn-ghost text-gray-400 hidden">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  <div class="mt-6 overflow-x-auto rounded-lg border border-gray-700">
    <table class="min-w-full divide-y divide-gray-700">
      <thead class="bg-gray-800">
        <tr>
          <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider w-16">#</th>
          <?php if (!empty($columns)) { foreach ($columns as $col) { ?>
            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider"><?php echo e($col); ?></th>
          <?php } } else { ?>
            <th class="px-3 py-3.5 text-left text-xs font-semibold text-gray-300">Brak kolumn</th>
          <?php } ?>
        </tr>
      </thead>
      <tbody id="itemsTbody" class="divide-y divide-gray-700 bg-gray-900">
        <?php if (!empty($items)) { 
          $row_number = $offset + 1; // Zaczynaj od właściwego numeru dla aktualnej strony
          foreach ($items as $row) { ?>
          <tr class="hover:bg-gray-800">
            <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-400 font-mono"><?php echo $row_number++; ?></td>
            <?php foreach ($columns as $col) { $val = $row[$col] ?? ''; ?>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-200 align-top">
                <?php if (is_img_col($col) && is_string($val) && $val !== '') { ?>
                  <img src="<?php echo e($val); ?>" alt="img" class="h-10 w-10 object-cover rounded" onerror="this.style.display='none'" />
                <?php } else if (is_price_col($col) && is_numeric($val)) { ?>
                  €<?php echo number_format((float)$val, 2, '.', ' '); ?>
                <?php } else { ?>
                  <?php echo e(is_scalar($val) ? (string)$val : json_encode($val)); ?>
                <?php } ?>
              </td>
            <?php } ?>
          </tr>
        <?php } } else { ?>
          <tr><td class="px-3 py-4 text-sm text-gray-400" colspan="<?php echo count($columns) + 1; ?>">Brak danych w tabeli items.</td></tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  
  <!-- Kontrolki paginacji -->
  <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
    <!-- Wybór ilości na stronie -->
    <div class="flex items-center gap-2">
      <label class="text-sm text-gray-400">Itemków na stronie:</label>
      <select onchange="changePerPage(this.value)" class="select select-bordered select-sm bg-gray-800 text-gray-200 border-gray-600">
        <option value="50" <?php echo $items_per_page == 50 ? 'selected' : ''; ?>>50</option>
        <option value="100" <?php echo $items_per_page == 100 ? 'selected' : ''; ?>>100 (szybkie)</option>
        <option value="250" <?php echo $items_per_page == 250 ? 'selected' : ''; ?>>250</option>
        <option value="500" <?php echo $items_per_page == 500 ? 'selected' : ''; ?>>500</option>
        <option value="1000" <?php echo $items_per_page == 1000 ? 'selected' : ''; ?>>1000</option>
        <option value="2500" <?php echo $items_per_page == 2500 ? 'selected' : ''; ?>>2500</option>
        <option value="5000" <?php echo $items_per_page == 5000 ? 'selected' : ''; ?>>5000</option>
        <option value="10000" <?php echo $items_per_page == 10000 ? 'selected' : ''; ?>>Wszystkie (10k+)</option>
      </select>
    </div>
    
    <!-- Nawigacja stron -->
    <?php if ($total_pages > 1): ?>
    <div class="flex items-center gap-2">
      <!-- Pierwsza strona -->
      <?php if ($current_page > 1): ?>
        <a href="?page=items&page_num=1&per_page=<?php echo $items_per_page; ?>" 
           class="btn btn-sm btn-outline">
          <i class="fas fa-angle-double-left"></i>
        </a>
        <a href="?page=items&page_num=<?php echo $current_page - 1; ?>&per_page=<?php echo $items_per_page; ?>" 
           class="btn btn-sm btn-outline">
          <i class="fas fa-angle-left"></i>
        </a>
      <?php endif; ?>
      
      <!-- Numery stron -->
      <?php 
      $start_page = max(1, $current_page - 2);
      $end_page = min($total_pages, $current_page + 2);
      
      for ($i = $start_page; $i <= $end_page; $i++): ?>
        <a href="?page=items&page_num=<?php echo $i; ?>&per_page=<?php echo $items_per_page; ?>" 
           class="btn btn-sm <?php echo $i == $current_page ? 'btn-primary' : 'btn-outline'; ?>">
          <?php echo $i; ?>
        </a>
      <?php endfor; ?>
      
      <!-- Ostatnia strona -->
      <?php if ($current_page < $total_pages): ?>
        <a href="?page=items&page_num=<?php echo $current_page + 1; ?>&per_page=<?php echo $items_per_page; ?>" 
           class="btn btn-sm btn-outline">
          <i class="fas fa-angle-right"></i>
        </a>
        <a href="?page=items&page_num=<?php echo $total_pages; ?>&per_page=<?php echo $items_per_page; ?>" 
           class="btn btn-sm btn-outline">
          <i class="fas fa-angle-double-right"></i>
        </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<script>
let searchTimeout;
let currentSearchTerm = '';

// AJAX wyszukiwanie w całej bazie danych
function performSearch(searchTerm, page = 1) {
  const spinner = document.getElementById('searchSpinner');
  const clearBtn = document.getElementById('clearSearch');
  const tbody = document.getElementById('itemsTbody');
  
  // Pokaż spinner
  spinner.classList.remove('hidden');
  
  // Pokaż/ukryj przycisk czyszczenia
  if (searchTerm.trim()) {
    clearBtn.classList.remove('hidden');
  } else {
    clearBtn.classList.add('hidden');
  }
  
  const url = `api/search_items.php?q=${encodeURIComponent(searchTerm)}&page=${page}&per_page=100`;
  
  fetch(url)
    .then(response => response.json())
    .then(data => {
      spinner.classList.add('hidden');
      
      if (data.success) {
        updateTable(data);
        updateStats(data);
        currentSearchTerm = searchTerm;
      } else {
        console.error('Błąd wyszukiwania:', data.error);
      }
    })
    .catch(error => {
      spinner.classList.add('hidden');
      console.error('Błąd AJAX:', error);
    });
}

// Aktualizuj tabelę z wynikami
function updateTable(data) {
  const tbody = document.getElementById('itemsTbody');
  
  if (data.items.length === 0) {
    tbody.innerHTML = '<tr><td colspan="100%" class="px-3 py-4 text-sm text-gray-400 text-center">Nie znaleziono itemków.</td></tr>';
    return;
  }
  
  let html = '';
  data.items.forEach((item, index) => {
    const rowNumber = (data.page - 1) * data.per_page + index + 1;
    html += `<tr class="hover:bg-gray-800">
      <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-400 font-mono">${rowNumber}</td>
      <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-200">${item.id}</td>
      <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-200">${escapeHtml(item.item_id)}</td>
      <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-200">${escapeHtml(item.skin_name)}</td>
      <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-200">${item.rarity}</td>
      <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-200">€${parseFloat(item.price).toFixed(2)}</td>
      <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-200">${escapeHtml(item.weapon_name)}</td>
      <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-200">
        ${item.image_url ? `<img src="${escapeHtml(item.image_url)}" alt="img" class="h-10 w-10 object-cover rounded" onerror="this.style.display='none'" />` : ''}
      </td>
      <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-400">${item.created_at || ''}</td>
    </tr>`;
  });
  
  tbody.innerHTML = html;
}

// Aktualizuj statystyki
function updateStats(data) {
  const statsP = document.querySelector('.sm\\:flex-auto p');
  if (statsP) {
    const searchInfo = data.search_term ? ` (wyszukiwanie: "${data.search_term}")` : '';
    statsP.innerHTML = `
      Łącznie: ${data.total.toLocaleString()}${searchInfo} | 
      Strona: ${data.page}/${data.total_pages} | 
      Pokazuję: ${data.items.length} itemków
      ${data.query_time ? ` | Czas: ${(data.query_time * 1000).toFixed(0)}ms` : ''}
    `;
  }
}

// Escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Event listenery
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('itemsSearch');
  const clearBtn = document.getElementById('clearSearch');
  
  // Wyszukiwanie z opóźnieniem
  searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const term = this.value.trim();
    
    searchTimeout = setTimeout(() => {
      performSearch(term);
    }, 300); // 300ms opóźnienie
  });
  
  // Czyszczenie wyszukiwania
  clearBtn.addEventListener('click', function() {
    searchInput.value = '';
    clearBtn.classList.add('hidden');
    performSearch(''); // Pokaż wszystkie
  });
});

// Funkcja zmiany ilości itemków na stronie
function changePerPage(perPage) {
  var currentUrl = new URL(window.location.href);
  currentUrl.searchParams.set('per_page', perPage);
  currentUrl.searchParams.set('page_num', '1');
  window.location.href = currentUrl.toString();
}
</script>
