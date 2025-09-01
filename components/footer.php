    <footer class="w-full bg-white relative bottom-0" aria-labelledby="footer-heading">
        <h2 id="footer-heading" class="sr-only">Footer</h2>
        <div class="px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-16">
            <div class="flex flex-col items-baseline space-y-6">
                <div onclick="location.assign('/')" class="mx-auto">
                    <img class="h-8 w-auto" src="../assets/img/logo4.png" alt="">
                </div>
                <div class="mx-auto">
                    <span class="mx-auto text-base text-gray-500">
                        Copyright © 2024 <span class="text-4xl">.</span> All Rights Reserved
                    </span>
                </div>
            </div>
        </div>
    </footer>

<?php
// Include global components if user is logged in
if (isset($GLOBALS['include_global_recent_drops']) && $GLOBALS['include_global_recent_drops']) {
    include 'components/global_recent_drops.php';
}

if (isset($GLOBALS['include_global_inventory']) && $GLOBALS['include_global_inventory']) {
    include 'components/global_inventory.php';
}
?>