<div class="flex flex-shrink-0 shadow-2xl">
    <div class="flex flex-col w-58">
        <div class="flex flex-col flex-grow pt-5 overflow-y-auto bg-gray-800 nav-blur glass border-r border-gray-700 w-full">
            <div class="flex flex-col flex-shrink-0 px-4 w-full items-center">
                <a href="/admin/" class="flex ml-2 p-2 flex-col gap-1 w-2/3 items-center">
                    <img class="h-16 w-auto mb-2" src="../assets/img/casespinlogo.png" alt="CashPlay">
                    <span class="text-xs text-gray-400 text-center">Admin Panel</span>
                </a>
                <button class="hidden rounded-lg focus:outline-none focus:shadow-outline">
                    <svg fill="currentColor" viewBox="0 0 20 20" class="w-6 h-6">
                        <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
            <div class="flex flex-col flex-grow px-4 mt-5">
                <nav class="flex-1 space-y-1 bg-gray-800 nav-blur glass">
                    <p class="px-4 pt-4 text-xs font-bold text-purple-400 uppercase tracking-wider">
                        <i class="fa-solid fa-chart-line mr-2"></i>Analytics
                    </p>
                    <ul>
                        <li>
                            <a href="?page=dashboard" class="inline-flex items-center w-full px-4 py-2 mt-1 text-sm text-gray-300 transition duration-200 ease-in-out transform rounded-lg focus:shadow-outline hover:bg-gray-700 hover:scale-95 hover:text-purple-400 border border-transparent hover:border-purple-500">
                                <i class="fa-solid fa-chart-pie text-purple-400 w-6 flex-shrink-0 justify-center flex"></i>
                                <span class="ml-4 font-medium">
                                    Dashboard
                                </span>
                            </a>

                        </li>
                        <li>
                            <div id="sideBarPlayersDropdownOpen" class="inline-flex items-center justify-between cursor-pointer w-full px-4 py-2 mt-1 text-sm text-gray-300 transition duration-200 ease-in-out transform rounded-lg focus:shadow-outline hover:bg-gray-700 hover:scale-95 hover:text-purple-400 border border-transparent hover:border-purple-500">
                                <div class="flex items-center gap-1 w-full">
                                    <i class="fa-solid fa-users text-purple-400 w-6 flex-shrink-0 justify-center flex"></i>
                                    <span class="ml-4 font-medium">
                                        Players
                                    </span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>

                            <div class="w-full rounded-xl overflow-hidden pl-5">
                                <ul id="sideBarPlayersDropdownContent" class="duration-500 bg-gray-700 my-2 p-2 rounded-xl border border-gray-600">

                                    <li>
                                        <a href="?page=allPlayers" class="flex items-center gap-1 p-2 text-base text-gray-300 rounded-lg hover:bg-gray-600 hover:text-white transition-colors duration-200">
                                            <i class="fa-solid fa-user-group text-blue-400 w-8 justify-center flex"></i>
                                            <span class="text-[0.75rem] truncate w-full overflow-hidden font-medium">All
                                                Players</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                    <p class="px-4 pt-4 text-xs font-bold text-purple-400 uppercase tracking-wider">
                        <i class="fa-solid fa-cube mr-2"></i>Content
                    </p>
                    <ul>
                        <li>
                            <a href="?page=items" class="inline-flex items-center w-full px-4 py-2 mt-1 cursor-pointer text-sm text-gray-300 transition duration-200 ease-in-out transform rounded-lg focus:shadow-outline hover:bg-gray-700 hover:scale-95 hover:text-purple-400 border border-transparent hover:border-purple-500">
                                <i class="fa-solid fa-list text-purple-400 w-6 flex-shrink-0 justify-center flex"></i>
                                <span class="ml-4 font-medium">
                                    Przedmioty
                                </span>
                            </a>
                        </li>
                        <li>
                            <div id="sideBarPaymentsDropdownOpen" class="inline-flex items-center justify-between cursor-pointer w-full px-4 py-2 mt-1 text-sm text-gray-300 transition duration-200 ease-in-out transform rounded-lg focus:shadow-outline hover:bg-gray-700 hover:scale-95 hover:text-purple-400 border border-transparent hover:border-purple-500">
                                <div class="flex items-center gap-1">
                                    <i class="fa-solid fa-credit-card text-purple-400 w-6 flex-shrink-0 justify-center flex"></i>

                                    <span class="ml-4 font-medium">
                                        Payments
                                    </span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>

                            <div class="w-full rounded-xl overflow-hidden pl-5">
                                <ul id="sideBarPaymentsDropdownContent" class="duration-500 bg-gray-700 my-2 p-2 rounded-xl border border-gray-600">
                                    <li>
                                        <a href="?page=deposit" class="flex items-center gap-1 p-2 text-base text-gray-300 rounded-lg hover:bg-gray-600 hover:text-white transition-colors duration-200">
                                            <i class="fa-solid fa-arrow-down text-green-400 w-8 justify-center flex"></i>
                                            <span class="text-[0.75rem] truncate w-full overflow-hidden font-medium">Deposit</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?page=withdraw" class="flex items-center gap-1 p-2 text-base text-gray-300 rounded-lg hover:bg-gray-600 hover:text-white transition-colors duration-200">
                                            <i class="fa-solid fa-arrow-up text-red-400 w-8 justify-center flex"></i>
                                            <span class="text-[0.75rem] truncate w-full overflow-hidden font-medium">Withdraw</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?page=invoices" class="flex items-center gap-1 p-2 text-base text-gray-300 rounded-lg hover:bg-gray-600 hover:text-white transition-colors duration-200">
                                            <i class="fa-solid fa-receipt text-blue-400 w-8 justify-center flex"></i>
                                            <span class="text-[0.75rem] truncate w-full overflow-hidden font-medium">Invoice</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?page=coupon" class="flex items-center gap-1 p-2 text-base text-gray-300 rounded-lg hover:bg-gray-600 hover:text-white transition-colors duration-200">
                                            <i class="fa-solid fa-percent text-yellow-400 w-8 justify-center flex"></i>
                                            <span class="text-[0.75rem] truncate w-full overflow-hidden font-medium">Coupons</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?page=settings" class="flex items-center gap-1 p-2 text-base text-gray-300 rounded-lg hover:bg-gray-600 hover:text-white transition-colors duration-200">
                                            <i class="fa-solid fa-cog text-gray-400 w-8 justify-center flex"></i>
                                            <span class="text-[0.75rem] truncate w-full overflow-hidden font-medium">Settings</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                    </ul>
                    <p class="px-4 pt-4 text-xs font-bold text-purple-400 uppercase tracking-wider">
                        <i class="fa-solid fa-palette mr-2"></i>Customization
                    </p>
                    <ul>
                        <li>
                            <a href="?page=cases" class="inline-flex items-center w-full px-4 py-2 mt-1 cursor-pointer text-sm text-gray-300 transition duration-200 ease-in-out transform rounded-lg focus:shadow-outline hover:bg-gray-700 hover:scale-95 hover:text-purple-400 border border-transparent hover:border-purple-500">
                                <i class="fa-solid fa-box text-purple-400 w-6 flex-shrink-0 justify-center flex"></i>
                                <span class="ml-4 font-medium">
                                    Cases
                                </span>
                            </a>
                        </li>


                        <li>
                            <a href="?page=support" class="inline-flex items-center w-full px-4 py-2 mt-1 cursor-pointer text-sm text-gray-300 transition duration-200 ease-in-out transform rounded-lg focus:shadow-outline hover:bg-gray-700 hover:scale-95 hover:text-purple-400 border border-transparent hover:border-purple-500">
                                <i class="fa-solid fa-headset text-green-400 w-6 flex-shrink-0 justify-center flex"></i>

                                <span class="ml-4 font-medium">
                                    Support
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=documents" class="inline-flex items-center w-full px-4 py-2 mt-1 cursor-pointer text-sm text-gray-300 transition duration-200 ease-in-out transform rounded-lg focus:shadow-outline hover:bg-gray-700 hover:scale-95 hover:text-purple-400 border border-transparent hover:border-purple-500">
                                <i class="fa-solid fa-file-text text-blue-400 w-6 flex-shrink-0 justify-center flex"></i>
                                <span class="ml-4 font-medium">
                                    Documents
                                </span>
                            </a>
                        </li>
                        <li>
                            <div id="sideBarSettingsDropdownOpen" class="inline-flex items-center justify-between w-full px-4 py-2 mt-1 cursor-pointer text-sm text-gray-300 transition duration-200 ease-in-out transform rounded-lg focus:shadow-outline hover:bg-gray-700 hover:scale-95 hover:text-purple-400 border border-transparent hover:border-purple-500">
                                <div class="flex items-center gap-1">
                                    <i class="fa-solid fa-cog text-purple-400 w-6 flex-shrink-0 justify-center flex"></i>
                                    <span class="ml-4 font-medium">
                                        Settings
                                    </span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>

                            <div class="w-full rounded-xl overflow-hidden pl-5">
                                <ul id="sideBarSettingsDropdownContent" class="duration-500 bg-gray-700 my-2 p-2 rounded-xl border border-gray-600">
                                    <li>
                                        <a href="?page=users" class="flex items-center gap-1 p-2 text-base text-gray-300 rounded-lg hover:bg-gray-600 hover:text-white transition-colors duration-200">
                                            <i class="fa-solid fa-users text-blue-400 w-8 justify-center flex"></i>
                                            <span class="text-[0.75rem] truncate w-full overflow-hidden font-medium">Users</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?page=admins" class="flex items-center gap-1 p-2 text-base text-gray-300 rounded-lg hover:bg-gray-600 hover:text-white transition-colors duration-200">
                                            <i class="fa-solid fa-user-tie text-purple-400 w-8 justify-center flex"></i>
                                            <span class="text-[0.75rem] truncate w-full overflow-hidden font-medium">Admins</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?page=settings" class="flex items-center gap-1 p-2 text-base text-gray-300 rounded-lg hover:bg-gray-600 hover:text-white transition-colors duration-200">
                                            <i class="fa-solid fa-cog text-green-400 w-8 justify-center flex"></i>
                                            <span class="text-[0.75rem] truncate w-full overflow-hidden font-medium">Settings</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </nav>
            </div>
</div>
</div>
</div>