<?php

$settings = [
    'site_name' => 'CashPlay',
    'site_description' => 'Premium Gaming Platform',
    'site_logo' => '/assets/logo.png',
    'maintenance_mode' => false,
    'registration_enabled' => true,
    'min_deposit' => 10.00,
    'max_deposit' => 10000.00,
    'min_withdrawal' => 5.00,
    'max_withdrawal' => 5000.00,
    'withdrawal_fee' => 2.50,
    'currency' => 'USD',
    'timezone' => 'UTC',
    'email_notifications' => true,
    'sms_notifications' => false,
    'two_factor_auth' => false,
    'session_timeout' => 30,
    'max_login_attempts' => 5,
    'support_email' => 'support@cashplay.com',
    'admin_email' => 'admin@cashplay.com'
];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_general') {
        
        $success_message = "General settings updated successfully!";
    } elseif ($_POST['action'] === 'update_financial') {
        
        $success_message = "Financial settings updated successfully!";
    } elseif ($_POST['action'] === 'update_security') {
        
        $success_message = "Security settings updated successfully!";
    } elseif ($_POST['action'] === 'update_notifications') {
        
        $success_message = "Notification settings updated successfully!";
    }
}
?>

<div class="p-6">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">
            <i class="fa-solid fa-cog text-green-400 mr-3"></i>
            System Settings
        </h1>
        <p class="text-gray-400">Configure platform settings and preferences</p>
    </div>

    <?php if (isset($success_message)): ?>
    <div class="bg-green-600 text-white p-4 rounded-lg mb-6">
        <i class="fa-solid fa-check-circle mr-2"></i>
        <?php echo $success_message; ?>
    </div>
    <?php endif; ?>

    
    <div class="mb-6">
        <nav class="flex space-x-8">
            <button onclick="showTab('general')" id="tab-general" class="tab-button active">
                <i class="fa-solid fa-globe mr-2"></i>General
            </button>
            <button onclick="showTab('financial')" id="tab-financial" class="tab-button">
                <i class="fa-solid fa-dollar-sign mr-2"></i>Financial
            </button>
            <button onclick="showTab('security')" id="tab-security" class="tab-button">
                <i class="fa-solid fa-shield mr-2"></i>Security
            </button>
            <button onclick="showTab('notifications')" id="tab-notifications" class="tab-button">
                <i class="fa-solid fa-bell mr-2"></i>Notifications
            </button>
        </nav>
    </div>

    
    <div id="general-tab" class="tab-content">
        <div class="bg-gray-800 rounded-xl p-6">
            <h3 class="text-xl font-semibold text-white mb-6">General Settings</h3>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_general">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" 
                               class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Currency</label>
                        <select name="currency" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                            <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                            <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                            <option value="GBP" <?php echo $settings['currency'] === 'GBP' ? 'selected' : ''; ?>>GBP - British Pound</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Site Description</label>
                    <textarea name="site_description" rows="3" 
                              class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Timezone</label>
                        <select name="timezone" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                            <option value="UTC" <?php echo $settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                            <option value="America/New_York" <?php echo $settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                            <option value="Europe/London" <?php echo $settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" 
                               <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>
                               class="h-4 w-4 text-green-600 bg-gray-700 border-gray-600 rounded focus:ring-green-500">
                        <label for="maintenance_mode" class="ml-2 text-sm text-gray-300">Maintenance Mode</label>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition-colors duration-200">
                        Save General Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div id="financial-tab" class="tab-content hidden">
        <div class="bg-gray-800 rounded-xl p-6">
            <h3 class="text-xl font-semibold text-white mb-6">Financial Settings</h3>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_financial">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Minimum Deposit ($)</label>
                        <input type="number" name="min_deposit" step="0.01" value="<?php echo $settings['min_deposit']; ?>" 
                               class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Maximum Deposit ($)</label>
                        <input type="number" name="max_deposit" step="0.01" value="<?php echo $settings['max_deposit']; ?>" 
                               class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Minimum Withdrawal ($)</label>
                        <input type="number" name="min_withdrawal" step="0.01" value="<?php echo $settings['min_withdrawal']; ?>" 
                               class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Maximum Withdrawal ($)</label>
                        <input type="number" name="max_withdrawal" step="0.01" value="<?php echo $settings['max_withdrawal']; ?>" 
                               class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Withdrawal Fee ($)</label>
                    <input type="number" name="withdrawal_fee" step="0.01" value="<?php echo $settings['withdrawal_fee']; ?>" 
                           class="w-full md:w-1/2 border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition-colors duration-200">
                        Save Financial Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div id="security-tab" class="tab-content hidden">
        <div class="bg-gray-800 rounded-xl p-6">
            <h3 class="text-xl font-semibold text-white mb-6">Security Settings</h3>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_security">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Session Timeout (minutes)</label>
                        <input type="number" name="session_timeout" value="<?php echo $settings['session_timeout']; ?>" 
                               class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Max Login Attempts</label>
                        <input type="number" name="max_login_attempts" value="<?php echo $settings['max_login_attempts']; ?>" 
                               class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="registration_enabled" id="registration_enabled" value="1" 
                               <?php echo $settings['registration_enabled'] ? 'checked' : ''; ?>
                               class="h-4 w-4 text-green-600 bg-gray-700 border-gray-600 rounded focus:ring-green-500">
                        <label for="registration_enabled" class="ml-2 text-sm text-gray-300">Enable User Registration</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="two_factor_auth" id="two_factor_auth" value="1" 
                               <?php echo $settings['two_factor_auth'] ? 'checked' : ''; ?>
                               class="h-4 w-4 text-green-600 bg-gray-700 border-gray-600 rounded focus:ring-green-500">
                        <label for="two_factor_auth" class="ml-2 text-sm text-gray-300">Require Two-Factor Authentication</label>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition-colors duration-200">
                        Save Security Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div id="notifications-tab" class="tab-content hidden">
        <div class="bg-gray-800 rounded-xl p-6">
            <h3 class="text-xl font-semibold text-white mb-6">Notification Settings</h3>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_notifications">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Support Email</label>
                        <input type="email" name="support_email" value="<?php echo htmlspecialchars($settings['support_email']); ?>" 
                               class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Admin Email</label>
                        <input type="email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email']); ?>" 
                               class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="email_notifications" id="email_notifications" value="1" 
                               <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>
                               class="h-4 w-4 text-green-600 bg-gray-700 border-gray-600 rounded focus:ring-green-500">
                        <label for="email_notifications" class="ml-2 text-sm text-gray-300">Enable Email Notifications</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="sms_notifications" id="sms_notifications" value="1" 
                               <?php echo $settings['sms_notifications'] ? 'checked' : ''; ?>
                               class="h-4 w-4 text-green-600 bg-gray-700 border-gray-600 rounded focus:ring-green-500">
                        <label for="sms_notifications" class="ml-2 text-sm text-gray-300">Enable SMS Notifications</label>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition-colors duration-200">
                        Save Notification Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.tab-button {
    @apply px-4 py-2 text-sm font-medium text-gray-400 hover:text-white border-b-2 border-transparent hover:border-green-500 transition-colors duration-200;
}

.tab-button.active {
    @apply text-white border-green-500;
}

.tab-content {
    @apply transition-opacity duration-200;
}
</style>

<script>
function showTab(tabName) {
    // Hide all tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.add('hidden'));
    
    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(button => button.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.remove('hidden');
    document.getElementById('tab-' + tabName).classList.add('active');
}

// Initialize first tab as active
document.addEventListener('DOMContentLoaded', function() {
    showTab('general');
});
</script>
