<!-- Top Up Page -->
<section class="min-h-screen">
    <div class="bleed-row mx-auto w-full">
        <div class="mainnav">
            <div class="w-full mx-auto">
                <div class="w-full px-2 py-8 text-white">
                    <!-- Header Section -->
                    <div class="mb-4">
                        <h1 class="text-4xl font-bold text-white mb-2">Add Funds</h1>
                        <p class="text-gray-400">Choose your preferred payment method and amount</p>
                    </div>

                    <div class="grid xl:grid-cols-4 lg:grid-cols-3 gap-4">
                        <!-- Payment Amount Section -->
                        <div class="xl:col-span-3 lg:col-span-2">
                            <!-- Current Balance -->
                            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-4 mb-4">
                    <?php
                    $user_details = $conn->query("SELECT `total_balance` FROM user_details WHERE user_unique='$user_unique'");
                    if (mysqli_num_rows($user_details) > 0) {
                        $row = mysqli_fetch_assoc($user_details);
                    ?>
                        <input type="hidden" value="<?php echo $row['total_balance'] ?>" id="totalBalance" class="hidden" readonly disabled>
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-1">Current Balance</h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl font-bold text-green-400">$<?php echo number_format($row['total_balance'], 2); ?></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-400 mb-1">After deposit</p>
                                <div class="flex items-center gap-2">
                                    <span class="text-xl font-bold text-white" id="afterTotalAmount">$<?php echo number_format($row['total_balance'] + 50, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                
                
                

                            <!-- Amount Selection -->
                            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-4 mb-4">
                                <h3 class="text-xl font-semibold text-white mb-6">Select Amount</h3>
                                
                                <!-- Preset Amounts -->
                                <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-8 gap-3 mb-6">
                                    <button onclick="selectAmount(50)" id="amount_50" class="amount-btn bg-gray-700/50 hover:bg-purple-600 border border-gray-600 hover:border-purple-500 rounded-lg p-4 text-center transition-all duration-200 group">
                                        <div class="text-lg font-bold text-white group-hover:text-white">$50</div>
                                    </button>
                                    <button onclick="selectAmount(100)" id="amount_100" class="amount-btn bg-gray-700/50 hover:bg-purple-600 border border-gray-600 hover:border-purple-500 rounded-lg p-4 text-center transition-all duration-200 group">
                                        <div class="text-lg font-bold text-white group-hover:text-white">$100</div>
                                    </button>
                                    <button onclick="selectAmount(250)" id="amount_250" class="amount-btn bg-gray-700/50 hover:bg-purple-600 border border-gray-600 hover:border-purple-500 rounded-lg p-4 text-center transition-all duration-200 group relative">
                                        <div class="absolute -top-2 -right-2 bg-purple-500 text-white text-xs px-2 py-1 rounded-full">Popular</div>
                                        <div class="text-lg font-bold text-white group-hover:text-white">$250</div>
                                    </button>
                                    <button onclick="selectAmount(500)" id="amount_500" class="amount-btn bg-gray-700/50 hover:bg-purple-600 border border-gray-600 hover:border-purple-500 rounded-lg p-4 text-center transition-all duration-200 group">
                                        <div class="text-lg font-bold text-white group-hover:text-white">$500</div>
                                    </button>
                                    <button onclick="selectAmount(1000)" id="amount_1000" class="amount-btn bg-gray-700/50 hover:bg-purple-600 border border-gray-600 hover:border-purple-500 rounded-lg p-4 text-center transition-all duration-200 group">
                                        <div class="text-lg font-bold text-white group-hover:text-white">$1000</div>
                                    </button>
                                    <button onclick="selectAmount(2500)" id="amount_2500" class="amount-btn bg-gray-700/50 hover:bg-purple-600 border border-gray-600 hover:border-purple-500 rounded-lg p-4 text-center transition-all duration-200 group">
                                        <div class="text-lg font-bold text-white group-hover:text-white">$2500</div>
                                    </button>
                                    <button onclick="selectAmount(5000)" id="amount_5000" class="amount-btn bg-gray-700/50 hover:bg-purple-600 border border-gray-600 hover:border-purple-500 rounded-lg p-4 text-center transition-all duration-200 group">
                                        <div class="text-lg font-bold text-white group-hover:text-white">$5000</div>
                                    </button>
                                    <button onclick="selectAmount(10000)" id="amount_10000" class="amount-btn bg-gray-700/50 hover:bg-purple-600 border border-gray-600 hover:border-purple-500 rounded-lg p-4 text-center transition-all duration-200 group">
                                        <div class="text-lg font-bold text-white group-hover:text-white">$10000</div>
                                    </button>
                                </div>

                    <!-- Custom Amount -->
                    <div class="border-t border-gray-700 pt-6">
                        <label class="block text-sm font-medium text-gray-300 mb-3">Custom Amount</label>
                        <div class="relative">
                            <input type="number" min="10" step="0.01" id="customAmount" placeholder="Enter custom amount" 
                                   class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-3 pl-8 text-white placeholder-gray-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition-colors">
                            <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 font-medium">$</div>
                        </div>
                    </div>
                </div>

                            <!-- Payment Methods -->
                            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-4">
                                <h3 class="text-xl font-semibold text-white mb-6">Payment Methods</h3>
                                
                                <div class="space-y-3">
                                    <label class="flex items-center p-4 bg-gray-700/30 hover:bg-gray-700/50 border border-gray-600 rounded-lg cursor-pointer transition-colors group">
                                        <input type="radio" name="payment_method" value="card" class="sr-only" checked>
                                        <div class="w-5 h-5 border-2 border-gray-500 rounded-full mr-4 group-hover:border-purple-400 flex items-center justify-center">
                                            <div class="w-2.5 h-2.5 bg-purple-500 rounded-full opacity-100"></div>
                                        </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-white">Credit/Debit Card</div>
                                    <div class="text-sm text-gray-400">Visa, Mastercard, American Express</div>
                                </div>
                            </div>
                        </label>

                                    <label class="flex items-center p-4 bg-gray-700/30 hover:bg-gray-700/50 border border-gray-600 rounded-lg cursor-pointer transition-colors group">
                                        <input type="radio" name="payment_method" value="paypal" class="sr-only">
                                        <div class="w-5 h-5 border-2 border-gray-500 rounded-full mr-4 group-hover:border-purple-400 flex items-center justify-center">
                                            <div class="w-2.5 h-2.5 bg-purple-500 rounded-full opacity-0"></div>
                                        </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-500 rounded flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-white">PayPal</div>
                                    <div class="text-sm text-gray-400">Pay with your PayPal account</div>
                                </div>
                            </div>
                        </label>

                                    <label class="flex items-center p-4 bg-gray-700/30 hover:bg-gray-700/50 border border-gray-600 rounded-lg cursor-pointer transition-colors group">
                                        <input type="radio" name="payment_method" value="crypto" class="sr-only">
                                        <div class="w-5 h-5 border-2 border-gray-500 rounded-full mr-4 group-hover:border-purple-400 flex items-center justify-center">
                                            <div class="w-2.5 h-2.5 bg-purple-500 rounded-full opacity-0"></div>
                                        </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-orange-500 rounded flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.616 1.065 2.853 1.065V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.617-1.065-2.854-1.065V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-white">Cryptocurrency</div>
                                    <div class="text-sm text-gray-400">Bitcoin, Ethereum, and more</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

                        <!-- Order Summary -->
                        <div class="xl:col-span-1 lg:col-span-1">
                            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-4 sticky top-6">
                                <h3 class="text-xl font-semibold text-white mb-6">Order Summary</h3>
                                
                                <div class="space-y-4 mb-6">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-300">Amount</span>
                                        <span class="text-white font-medium" id="selectedAmount">$50.00</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-300">Processing Fee</span>
                                        <span class="text-white font-medium" id="processingFee">$2.50</span>
                                    </div>
                                    <div class="border-t border-gray-700 pt-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-lg font-semibold text-white">Total</span>
                                            <span class="text-lg font-bold text-purple-400" id="totalAmount">$52.50</span>
                                        </div>
                                    </div>
                                </div>

                                <button id="payButton" class="w-full bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold py-4 px-6 rounded-lg transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        <span>Secure Payment</span>
                                    </div>
                                </button>

                                <div class="mt-4 text-center">
                                    <p class="text-xs text-gray-400">🔒 Your payment is secured with SSL encryption</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="assets/js/g4skins-payment.js"></script>