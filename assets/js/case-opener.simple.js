/*
  CashPlay Case Opener - Simple CS:GO style rolling animation
  - Global function: window.openCase(caseId:number, casePrice?:number)
  - Works across pages:
    * case.php -> animuje w kontenerze #case-reel-container / #case-reel-track bazując na window.caseAllItems
    * cases.php -> tworzy własny modal z kontenerem rolki i animacją
    * (opcjonalnie) home.php -> jeśli istnieje modal z #roll-container/#roll-items, skrypt może z niego skorzystać
*/
(function(){
  if (window.CaseOpener) return; // guard przed wielokrotnym ładowaniem
  window.CaseOpener = { version: '1.0.0' };

  const STATE = { isRolling: false };

  // Mapy kolorów wg rzadkości
  const rarityMap = {
    consumer: { border: 'border-gray-500', bg: 'bg-gray-700', text: 'text-gray-200' },
    industrial: { border: 'border-sky-500', bg: 'bg-sky-900/40', text: 'text-sky-300' },
    'mil-spec': { border: 'border-blue-500', bg: 'bg-blue-900/40', text: 'text-blue-300' },
    restricted: { border: 'border-purple-500', bg: 'bg-purple-900/40', text: 'text-purple-300' },
    classified: { border: 'border-pink-500', bg: 'bg-pink-900/40', text: 'text-pink-300' },
    covert: { border: 'border-red-500', bg: 'bg-red-900/40', text: 'text-red-300' },
    contraband: { border: 'border-yellow-500', bg: 'bg-yellow-900/40', text: 'text-yellow-300' },

    common: { border: 'border-gray-500', bg: 'bg-gray-700', text: 'text-gray-200' },
    uncommon: { border: 'border-green-500', bg: 'bg-green-900/40', text: 'text-green-300' },
    rare: { border: 'border-blue-500', bg: 'bg-blue-900/40', text: 'text-blue-300' },
    epic: { border: 'border-purple-500', bg: 'bg-purple-900/40', text: 'text-purple-300' },
    legendary: { border: 'border-yellow-500', bg: 'bg-yellow-900/40', text: 'text-yellow-300' },
    mythic: { border: 'border-red-500', bg: 'bg-red-900/40', text: 'text-red-300' },
  };

  const fallbackItems = [
    { name: 'Glock-18 | Candy', rarity: 'common', price: 0.07, image: '', icon: 'fa-gun' },
    { name: 'P250 | Frost', rarity: 'uncommon', price: 0.35, image: '', icon: 'fa-snowflake' },
    { name: 'UMP-45 | Neon', rarity: 'rare', price: 1.2, image: '', icon: 'fa-bolt' },
    { name: 'M4A1-S | Phantom', rarity: 'epic', price: 4.5, image: '', icon: 'fa-ghost' },
    { name: 'AK-47 | Ruby', rarity: 'legendary', price: 23.0, image: '', icon: 'fa-gem' },
    { name: 'AWP | Ember', rarity: 'mythic', price: 120.0, image: '', icon: 'fa-fire' },
  ];

  function getStylesForRarity(rarityRaw) {
    const key = String(rarityRaw || 'common').toLowerCase();
    return rarityMap[key] || rarityMap.common;
  }

  // Notyfikacje helper
  function notify(msg, type){
    try { if (window.showNotification) return window.showNotification(msg, type || 'info'); } catch(_){}
    try { alert(msg); } catch(_) {}
  }

  // Balance helper
  function applyBalance(newBalance){
    try { if (typeof window.updateBalance === 'function') window.updateBalance(newBalance); } catch(_){}
  }

  // Tworzy (jeśli brak) modal do rolki dla stron bez dedykowanego kontenera
  function ensureOverlay(){
    let overlay = document.getElementById('cp-case-open-modal');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'cp-case-open-modal';
      overlay.className = 'fixed inset-0 bg-black/60 hidden z-[100] items-center justify-center';
      overlay.innerHTML = `
        <div class="bg-gray-900 rounded-xl p-6 border border-gray-700 max-w-3xl w-full mx-4">
          <div class="flex items-start justify-between mb-2">
            <h3 id="cp-modal-title" class="text-2xl font-bold text-white">Opening Case...</h3>
            <button id="cp-close" class="text-gray-400 hover:text-white transition"><i class="fas fa-times"></i></button>
          </div>
          <div id="cp-roll-container" class="relative bg-gray-800 rounded-lg p-4 mb-6 overflow-hidden" style="height: 180px;">
            <div class="absolute top-0 bottom-0 left-1/2 w-[2px] bg-yellow-400 z-10" style="transform: translateX(-50%);"></div>
            <div id="cp-roll-track" class="flex items-center h-full will-change-transform" style="transform: translateX(0px);"></div>
          </div>
          <div id="cp-result" class="hidden">
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg p-4 mb-3">
              <div id="cp-result-icon" class="text-5xl mb-2"></div>
              <div id="cp-result-name" class="text-xl font-bold text-white mb-1"></div>
              <div id="cp-result-rarity" class="text-sm font-semibold mb-1 text-gray-200"></div>
              <div id="cp-result-value" class="text-2xl font-bold text-yellow-400"></div>
            </div>
            <button id="cp-collect" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold">Collect Item</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);

      const closeBtn = overlay.querySelector('#cp-close');
      closeBtn?.addEventListener('click', () => hideOverlay());
      overlay.querySelector('#cp-collect')?.addEventListener('click', () => hideOverlay());
    }
    return overlay;
  }

  function showOverlay(){
    const overlay = ensureOverlay();
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    const result = overlay.querySelector('#cp-result');
    result?.classList.add('hidden');
    const track = overlay.querySelector('#cp-roll-track');
    if (track) { track.innerHTML = ''; track.style.transition = 'none'; track.style.transform = 'translateX(0px)'; }
  }

  function hideOverlay(){
    const overlay = document.getElementById('cp-case-open-modal');
    if (!overlay) return;
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    STATE.isRolling = false;
  }

  // Buduje pojedynczy kafelek itemu na taśmie
  function buildItemNode(item){
    const styles = getStylesForRarity(item.rarity);
    const node = document.createElement('div');
    node.className = `mx-2 w-[120px] h-full flex flex-col items-center justify-center p-2 rounded-lg border ${styles.border} ${styles.bg} text-center select-none`;
    node.style.flex = '0 0 auto';
    node.style.width = '120px';

    const imgWrap = document.createElement('div');
    imgWrap.className = 'h-16 w-full flex items-center justify-center mb-2 overflow-hidden';

    if (item.image) {
      const img = document.createElement('img');
      img.src = item.image;
      img.alt = item.name || 'item';
      img.className = 'max-h-full max-w-full object-contain';
      img.loading = 'lazy';
      imgWrap.appendChild(img);
    } else {
      const i = document.createElement('i');
      i.className = `fas ${item.icon || 'fa-gem'} text-2xl ${styles.text}`;
      imgWrap.appendChild(i);
    }

    const name = document.createElement('div');
    name.className = 'text-[11px] font-semibold text-white truncate w-full';
    name.title = item.name || '';
    name.textContent = item.name || 'Unknown';

    const rarity = document.createElement('div');
    rarity.className = `text-[10px] uppercase tracking-wide ${styles.text}`;
    rarity.textContent = (item.rarity || 'common');

    const price = document.createElement('div');
    price.className = 'text-[12px] font-bold text-yellow-400 mt-1';
    if (typeof item.price === 'number') price.textContent = '$' + item.price.toFixed(2);

    node.appendChild(imgWrap);
    node.appendChild(name);
    node.appendChild(rarity);
    node.appendChild(price);

    return node;
  }

  function pickBaseItems(){
    try {
      if (Array.isArray(window.caseAllItems) && window.caseAllItems.length) {
        return window.caseAllItems.map(x => ({
          name: (x.name || (x.weapon_name ? (x.weapon_name + (x.skin_name ? (' | ' + x.skin_name) : '')) : 'Item')),
          rarity: (x.rarity || 'common').toLowerCase(),
          price: typeof x.price === 'number' ? x.price : parseFloat(x.price || '0'),
          image: x.image_url || x.image || '',
          icon: 'fa-gem'
        }));
      }
    } catch(_){ /* ignore */ }
    return fallbackItems.slice();
  }

  // Pobierz listę itemów danej skrzynki do animacji (API fallback)
  async function fetchCaseItemsFromAPI(caseId){
    try {
      const res = await fetch(`server/get_drops.php?case_id=${encodeURIComponent(caseId)}`);
      const j = await res.json();
      const drops = (j && j.data && Array.isArray(j.data.drops)) ? j.data.drops : [];
      if (!drops.length) return [];
      return drops.map(d => ({
        name: (d.name || d.item_name || 'Item'),
        rarity: String(d.rarity || 'common').toLowerCase(),
        price: (typeof d.value === 'number' ? d.value : parseFloat(d.value || d.item_value || '0')),
        image: '',
        icon: 'fa-gem'
      }));
    } catch(_){
      return [];
    }
  }

  // Ustal bazowy zestaw kafelków dla danej skrzynki
  async function resolveBaseItems(caseId){
    // 1) case.php przekazuje window.caseAllItems
    try {
      if (Array.isArray(window.caseAllItems) && window.caseAllItems.length) {
        return window.caseAllItems.map(x => ({
          name: (x.name || (x.weapon_name ? (x.weapon_name + (x.skin_name ? (' | ' + x.skin_name) : '')) : 'Item')),
          rarity: (x.rarity || 'common').toLowerCase(),
          price: typeof x.price === 'number' ? x.price : parseFloat(x.price || '0'),
          image: x.image_url || x.image || '',
          icon: 'fa-gem'
        }));
      }
    } catch(_){ /* ignore */ }

    // 2) Spróbuj pobrać z API na stronach typu cases.php
    const apiItems = await fetchCaseItemsFromAPI(caseId);
    if (apiItems.length >= 1) return apiItems;

    // 3) Fallback
    return fallbackItems.slice();
  }

  function buildSequence(baseItems, stopItem, total = 40){
    const seq = [];
    const n = Math.max(total, 20);
    for (let i=0;i<n;i++) {
      const r = Math.floor(Math.random() * baseItems.length);
      seq.push(baseItems[r]);
    }
    // wstaw wygrany na pozycję zatrzymania (około 75% długości)
    const stopIndex = Math.max(10, Math.floor(n * 0.75));
    seq[stopIndex] = stopItem;
    return { seq, stopIndex };
  }

  function runRoll(containerEl, trackEl, winner, baseItems){
    // zbuduj sekwencję
    const { seq, stopIndex } = buildSequence(baseItems, winner, 46);

    // wyczyść i wstaw elementy
    trackEl.innerHTML = '';
    const nodes = seq.map(buildItemNode);
    nodes.forEach(n => trackEl.appendChild(n));

    // przygotuj animację
    trackEl.style.transition = 'none';
    trackEl.style.transform = 'translateX(0px)';

    // enforce reflow
    void trackEl.offsetWidth;

    // oblicz finalną pozycję
    const containerRect = containerEl.getBoundingClientRect();
    const containerCenter = containerRect.left + containerRect.width / 2;

    const stopEl = nodes[stopIndex];
    const stopRect = stopEl.getBoundingClientRect();
    const stopCenter = stopRect.left + stopRect.width / 2;

    // translateX liczymy względem aktualnej pozycji; potrzebujemy różnicę centrów
    const currentTranslate = 0;
    let finalTranslate = currentTranslate + (containerCenter - stopCenter);

    // Dodaj lekką wariację, by nie było "sztywno" (max +/- 6px)
    finalTranslate += (Math.random() * 12 - 6);

    // animuj
    const duration = 4200 + Math.floor(Math.random() * 600); // 4.2s - 4.8s
    trackEl.style.transition = `transform ${duration}ms cubic-bezier(0.15, 0.85, 0.15, 1)`;
    trackEl.style.transform = `translateX(${finalTranslate}px)`;

    return new Promise(resolve => {
      const onEnd = () => {
        trackEl.removeEventListener('transitionend', onEnd);
        // podświetl zwycięski element
        stopEl.classList.add('ring-4','ring-yellow-400','ring-offset-2','ring-offset-gray-900');
        resolve({ stopEl, stopIndex });
      };
      trackEl.addEventListener('transitionend', onEnd, { once: true });
    });
  }

  async function fetchOpenCase(caseId){
    const res = await fetch('server/open_case.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ case_id: Number(caseId) || 0 })
    });
    const data = await res.json().catch(() => ({ success: false, message: 'Nieprawidłowa odpowiedź serwera' }));
    if (!res.ok || !data || data.success !== true) {
      const msg = (data && data.message) ? data.message : `Błąd (${res.status}) podczas otwierania skrzynki`;
      throw new Error(msg);
    }
    return data; // { success, item, new_balance }
  }

  function formatWinnerItem(item){
    // Normalizujemy odpowiedź backendu do wewnętrznego formatu
    return {
      name: item && item.name ? item.name : 'Unknown',
      rarity: (item && item.rarity) ? String(item.rarity).toLowerCase() : 'common',
      price: item && typeof item.price === 'number' ? item.price : parseFloat(item && item.price || '0'),
      image: (item && (item.image || item.image_url)) || '',
      icon: 'fa-gem'
    };
  }

  function tryGetCaseReelElements(){
    const container = document.getElementById('case-reel-container');
    const track = document.getElementById('case-reel-track');
    if (container && track) return { container, track };
    return null;
  }

  function tryGetHomeRollElements(){
    const container = document.getElementById('roll-container');
    const track = document.getElementById('roll-items');
    if (container && track) return { container, track };
    return null;
  }

  function showResultInOverlay(winner){
    const overlay = ensureOverlay();
    const resWrap = overlay.querySelector('#cp-result');
    const icon = overlay.querySelector('#cp-result-icon');
    const name = overlay.querySelector('#cp-result-name');
    const rarity = overlay.querySelector('#cp-result-rarity');
    const val = overlay.querySelector('#cp-result-value');
    const styles = getStylesForRarity(winner.rarity);

    icon.innerHTML = `<i class="fas fa-gem text-5xl ${styles.text}"></i>`;
    name.textContent = winner.name;
    rarity.textContent = String(winner.rarity || 'common').toUpperCase();
    val.textContent = '$' + (Number(winner.price || 0).toFixed(2));

    resWrap.classList.remove('hidden');
  }

  function showResultInCasePage(winner){
    // cases.php ma własny wynik (#case-opening-modal), ale my używamy overlay
    // case.php nie posiada dedykowanego UI wyniku – wynik pokażemy poprzez notification + highlight na taśmie
    notify(`Wygrano: ${winner.name} ($${Number(winner.price||0).toFixed(2)})`, 'success');
  }

  async function openCase(caseId, casePrice){
    if (STATE.isRolling) return;
    STATE.isRolling = true;

    // Spróbuj wykryć gdzie animować
    const caseReel = tryGetCaseReelElements();
    const homeRoll = tryGetHomeRollElements();

    let useOverlay = true;
    let containerEl = null;
    let trackEl = null;

    if (caseReel) {
      useOverlay = false;
      containerEl = caseReel.container; trackEl = caseReel.track;
      // reset toru
      trackEl.innerHTML = '';
      trackEl.style.transition = 'none';
      trackEl.style.transform = 'translateX(0px)';
    } else if (homeRoll) {
      useOverlay = false;
      containerEl = homeRoll.container; trackEl = homeRoll.track;
      // pokaż modal, jeśli ukryty
      const modal = document.getElementById('case-opening-modal');
      if (modal) { modal.classList.remove('hidden'); }
      trackEl.innerHTML = '';
      trackEl.style.transition = 'none';
      trackEl.style.transform = 'translateX(0px)';
    } else {
      useOverlay = true;
      showOverlay();
      const overlay = ensureOverlay();
      containerEl = overlay.querySelector('#cp-roll-container');
      trackEl = overlay.querySelector('#cp-roll-track');
    }

    try {
      // Wywołaj backend
      const data = await fetchOpenCase(caseId);
      const winner = formatWinnerItem(data.item);
      if (typeof data.new_balance !== 'undefined') applyBalance(data.new_balance);

      // Dobierz bazowe itemy
      const baseItems = await resolveBaseItems(caseId);

      // Uruchom animację
      await runRoll(containerEl, trackEl, winner, baseItems);

      // Po animacji: pokaż wynik
      if (useOverlay) {
        showResultInOverlay(winner);
      } else {
        showResultInCasePage(winner);
      }

    } catch (err) {
      notify(err && err.message ? err.message : 'Coś poszło nie tak przy otwieraniu skrzynki', 'error');
      // jeżeli overlay był otwarty, zamknij
      if (useOverlay) hideOverlay();
    } finally {
      STATE.isRolling = false;
    }
  }

  // Eksport globalny
  window.openCase = openCase;

  // Auto-bind dla case.php (guzik z data attrs)
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('open-case-btn');
    if (btn) {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const id = btn.getAttribute('data-case-id');
        const price = btn.getAttribute('data-case-price');
        openCase(id ? Number(id) : 0, price ? Number(price) : undefined);
      });
    }
  });

})();
