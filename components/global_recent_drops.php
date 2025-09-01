<?php
// Recent Drops full-bleed section rendered above app-container on home page
?>
<section class="w-full bg-gray-800 shadow-xl">
  <div class="w-full">
    <div class="py-3 bg-gray-700 border-b border-gray-600 px-4">
      <h2 class="text-xl font-bold text-white">
        <i class="fa-solid fa-fire text-orange-500 mr-2"></i>Recent Drops
      </h2>
    </div>
    <div class="py-2">
      <div class="relative w-full overflow-hidden">
        <div class="w-full overflow-x-auto pb-1 scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-gray-800 min-h-[100px]">
          <div id="recent-drops-container" class="flex items-center pl-2" style="width: calc(100% + 320px); padding-right: 320px;">
            <!-- Recent drops will be loaded here via AJAX -->
            <div class="text-center text-gray-400 py-4 min-w-full">
              <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
              <p class="text-sm">Loading recent drops...</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
/* Zoptymalizowane recent drops - ostatni element w pełni widoczny */
#recent-drops-container {
    width: calc(100% + 320px) !important;
    padding-left: 8px !important;
    padding-right: 320px !important;
}

#recent-drops-container > div {
    flex-shrink: 0 !important;
    width: 178px !important;
    margin-right: 10px !important;
}

.last-drop-element {
    margin-right: 320px !important;
}
</style>

<script>
// Recent drops functionality
// Track the last known drop ID for smart refreshing
let lastDropId = 0;
let isFirstLoad = true;

// Function to create a drop element
function createDropElement(drop, isNew = false) {
    const dropElement = document.createElement('div');
    const baseClasses = 'flex-shrink-0 w-[178px] bg-gray-700 rounded-lg p-2.5 transition-all duration-300 hover:bg-gray-600 border border-gray-600 hover:border-gray-500 flex flex-col justify-center';
    
    // Add new drop animation class if this is a new drop
    dropElement.className = isNew 
        ? `${baseClasses} transform -translate-y-2 scale-95 opacity-0 animate-drop-in`
        : `${baseClasses} opacity-0`;
    
    const rarityColors = {
        'common': 'text-gray-400',
        'uncommon': 'text-green-400',
        'rare': 'text-blue-400',
        'epic': 'text-purple-400',
        'legendary': 'text-yellow-400',
        'mythic': 'text-red-400'
    };
    
    const rarityBgColors = {
        'common': 'bg-gray-500',
        'uncommon': 'bg-green-500',
        'rare': 'bg-blue-500',
        'epic': 'bg-purple-500',
        'legendary': 'bg-yellow-500',
        'mythic': 'bg-red-500'
    };
    
    dropElement.innerHTML = `
        <div class="flex items-center mb-3">
            <div class="w-10 h-10 ${rarityBgColors[drop.rarity] || 'bg-purple-500'} rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-gem text-white"></i>
            </div>
            <div class="flex-1">
                <div class="text-white font-semibold text-sm">${drop.username || 'Anonymous'}</div>
                <div class="text-gray-400 text-xs">${drop.time_ago || 'just now'}</div>
            </div>
        </div>
        <div class="text-center">
            <div class="${rarityColors[drop.rarity] || 'text-purple-400'} font-bold text-sm mb-1">${drop.item_name || 'Unknown Item'}</div>
            <div class="text-gray-500 text-xs mb-2">from ${drop.case_name || 'Mystery Case'}</div>
            <div class="text-yellow-400 font-bold text-lg">$${parseFloat(drop.item_value || 0).toFixed(2)}</div>
        </div>
    `;
    
    // Fade in animation for non-new items (on initial load)
    if (!isNew) {
        setTimeout(() => {
            dropElement.classList.remove('opacity-0');
            dropElement.classList.add('opacity-100');
        }, 50);
    }
    
    return dropElement;
}

// Function to create a placeholder drop element
function createPlaceholderDrop() {
    const placeholder = document.createElement('div');
    placeholder.className = 'flex-shrink-0 w-[178px] bg-gray-800 rounded-lg p-2.5 border-2 border-dashed border-gray-700 flex items-center justify-center snap-center';
    placeholder.innerHTML = `
        <div class="flex items-center justify-center h-full">
            <div class="text-center text-gray-600">
                <i class="fas fa-box-open text-3xl mb-2"></i>
                <p class="text-xs">Waiting for drops</p>
            </div>
        </div>
    `;
    return placeholder;
}

// Function to load recent drops
function loadRecentDropsData(forceUpdate = false) {
    const container = document.getElementById('recent-drops-container');
    if (!container) return;
    
    // Wymuś style bezpośrednio na kontenerze
    container.style.paddingRight = '500px';
    container.style.boxSizing = 'border-box';
    
    // Store current scroll position
    const scrollLeft = container.scrollLeft;
    
    const loadingIndicator = document.getElementById('recent-drops-loading');
    
    // Only show loading indicator on first load
    if (isFirstLoad) {
        container.innerHTML = '<div class="text-center text-gray-400 py-4"><i class="fas fa-spinner fa-spin mr-2"></i> Loading recent drops...</div>';
    }
    
    // Add timestamp to prevent caching
    const timestamp = new Date().getTime();
    fetch(`server/get_recent_drops_v2.php?t=${timestamp}`)
        .then(response => response.json())
        .then(data => {
            // Sprawdź czy data jest obiektem i ma właściwość drops jako tablicę
            if (!data || typeof data !== 'object' || !Array.isArray(data.drops)) {
                container.innerHTML = '<div class="text-center text-gray-400 py-4"><p>Error loading drops. Invalid data format.</p></div>';
                return;
            }
            
            if (data.drops.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-400 py-4"><p>No recent drops yet. Be the first to open a case!</p></div>';
                return;
            }
            
            // Check if we have new drops
            const latestDropId = data.drops[0]?.id || 0;
            const hasNewDrops = latestDropId > lastDropId;
            lastDropId = latestDropId;
            
            // If no new drops and not forced, don't update the UI
            if (!hasNewDrops && !forceUpdate && !isFirstLoad) {
                return;
            }
            
            // Clear container only if it's the first load or forced update
            if (isFirstLoad || forceUpdate) {
                container.innerHTML = '';
                
                // Pokaż tylko rzeczywiste itemy (maksymalnie 17)
                const dropsToShow = data.drops.slice(0, 17);
                
                dropsToShow.forEach((drop, index) => {
                    const element = createDropElement(drop, false);
                    
                    // Dodaj margin do ostatniego elementu
                    if (index === dropsToShow.length - 1) {
                        element.style.marginRight = '320px';
                        element.classList.add('last-drop-element');
                    }
                    
                    container.appendChild(element);
                });

                // Spacer nie jest potrzebny - używamy CSS padding
                
                isFirstLoad = false;
            } else if (hasNewDrops) {
                // Only add new drops with animation
                const newDrops = data.drops.filter(drop => drop.id > lastDropId);
                if (newDrops.length > 0) {
                    // Add new drops at the beginning
                    newDrops.reverse().forEach(drop => {
                        const newDrop = createDropElement(drop, true);
                        if (container.firstChild) {
                            container.insertBefore(newDrop, container.firstChild);
                        } else {
                            container.appendChild(newDrop);
                        }
                    });
                    
                    // Remove excess items to maintain 17 items
                    while (container.children.length > 17) {
                        container.removeChild(container.lastChild);
                    }

                    // Spacer nie jest potrzebny - używamy CSS padding
                    
                    // Show notification for new drops
                    if (newDrops.length === 1) {
                        showNotification(`New drop: ${newDrops[0].username} unboxed ${newDrops[0].item_name}!`, 'success');
                    } else if (newDrops.length > 1) {
                        showNotification(`${newDrops.length} new drops!`, 'success');
                    }
                }
            }
        })
        .catch(error => {
            container.innerHTML = '<div class="text-center text-gray-400 py-4"><p>Error loading drops. Please refresh.</p></div>';
        })
        .finally(() => {
            isFirstLoad = false;
            // Restore scroll position after update
            if (!forceUpdate) {
                container.scrollLeft = scrollLeft;
            }
        });
}

// Get FontAwesome icon for item rarity
function getRarityIcon(rarity) {
    const icons = {
        'common': 'fa-circle',
        'uncommon': 'fa-square',
        'rare': 'fa-diamond',
        'epic': 'fa-star',
        'legendary': 'fa-crown',
        'mythic': 'fa-gem'
    };
    return icons[rarity] || 'fa-circle';
}

// Load recent drops on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    loadRecentDropsData(true);
    
    
    // Smart refresh - check for new drops every 2 seconds
    setInterval(() => {
        // Only refresh if not paused (e.g., during case opening)
        if (!window.pauseRecentDropsRefresh) {
            loadRecentDropsData(false);
            // Update last updated time
            const lastUpdated = document.getElementById('last-updated');
            if (lastUpdated) {
                lastUpdated.textContent = 'just now';
            }
        }
    }, 2000);
});
</script>
