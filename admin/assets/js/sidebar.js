document.addEventListener('DOMContentLoaded', () => {
  function setupDropdown(openId, contentId) {
    const openEl = document.getElementById(openId);
    const contentEl = document.getElementById(contentId);
    if (!openEl || !contentEl) return;
    contentEl.style.marginTop = "-200%";
    openEl.addEventListener('click', () => {
      contentEl.style.marginTop = contentEl.style.marginTop === "-200%" ? "0%" : "-200%";
    });
  }

  
  setupDropdown('sideBarPlayersDropdownOpen', 'sideBarPlayersDropdownContent');

  
  setupDropdown('sideBarPaymentsDropdownOpen', 'sideBarPaymentsDropdownContent');

  
  setupDropdown('sideBarSettingsDropdownOpen', 'sideBarSettingsDropdownContent');

  
  setupDropdown('sideBarBattleDropdownOpen', 'sideBarBattleDropdownContent');
});










