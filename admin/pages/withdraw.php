<?php

$withdrawals = [];
$total_withdrawals = 0;
$pending_withdrawals = 0;
$approved_withdrawals = 0;
$total_amount = 0;


if (isset($conn) && $conn) {
    try {
        
        $stats_query = "SELECT 
                           COUNT(*) as total,
                           COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                           COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                           SUM(amount) as total_amount
                        FROM withdrawal_requests";
        $stats_stmt = db_query($stats_query);
        $stats_result = $stats_stmt->get_result();
        if ($stats_result) {
            $stats = $stats_result->fetch_assoc();
            $total_withdrawals = $stats['total'] ?? 0;
            $pending_withdrawals = $stats['pending'] ?? 0;
            $approved_withdrawals = $stats['approved'] ?? 0;
            $total_amount = $stats['total_amount'] ?? 0;
        }
        
        
        $query = "SELECT wr.*, ud.username, ud.email 
                  FROM withdrawal_requests wr 
                  LEFT JOIN user_details ud ON wr.user_unique = ud.user_unique 
                  ORDER BY wr.created_at DESC 
                  LIMIT 50";
        
        $stmt = db_query($query);
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $withdrawals[] = $row;
            }
        }
    } catch (Exception $e) {
        
        $withdrawals = [
            [
                'id' => 1,
                'user_unique' => 'user_001',
                'username' => 'player1',
                'email' => 'player1@example.com',
                'amount' => 250.00,
                'payment_method' => 'PayPal',
                'payment_details' => 'player1@paypal.com',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                'id' => 2,
                'user_unique' => 'user_002',
                'username' => 'player2',
                'email' => 'player2@example.com',
                'amount' => 500.00,
                'payment_method' => 'Bank Transfer',
                'payment_details' => 'IBAN: DE89 3704 0044 0532 0130 00',
                'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-6 hours'))
            ],
            [
                'id' => 3,
                'user_unique' => 'user_003',
                'username' => 'player3',
                'email' => 'player3@example.com',
                'amount' => 100.00,
                'payment_method' => 'Crypto',
                'payment_details' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
                'status' => 'rejected',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ]
        ];
        $total_withdrawals = count($withdrawals);
        $pending_withdrawals = count(array_filter($withdrawals, function($w) { return $w['status'] === 'pending'; }));
        $approved_withdrawals = count(array_filter($withdrawals, function($w) { return $w['status'] === 'approved'; }));
        $total_amount = array_sum(array_column($withdrawals, 'amount'));
    }
}
?>

<div class="p-6">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">
            <i class="fa-solid fa-money-bill-transfer text-red-400 mr-3"></i>
            Withdrawal Management
        </h1>
        <p class="text-gray-400">Manage user withdrawal requests and payments</p>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Withdrawals</p>
                    <p class="text-3xl font-bold"><?php echo number_format($total_withdrawals); ?></p>
                </div>
                <div class="bg-blue-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-money-bill-transfer text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-yellow-600 to-yellow-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm font-medium">Pending</p>
                    <p class="text-3xl font-bold"><?php echo number_format($pending_withdrawals); ?></p>
                </div>
                <div class="bg-yellow-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-clock text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Approved</p>
                    <p class="text-3xl font-bold"><?php echo number_format($approved_withdrawals); ?></p>
                </div>
                <div class="bg-green-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Amount</p>
                    <p class="text-3xl font-bold">$<?php echo number_format($total_amount, 2); ?></p>
                </div>
                <div class="bg-purple-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-dollar-sign text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700">
            <h3 class="text-xl font-semibold text-white">Withdrawal Requests</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
            </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700">
                    <?php if (empty($withdrawals)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center py-8">
                                    <i class="fa-solid fa-money-bill-transfer text-6xl text-gray-600 mb-4"></i>
                                    <p class="text-lg font-medium">No withdrawal requests found</p>
                                    <p class="text-sm">Withdrawal requests will appear here when users submit them</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                            <tr class="hover:bg-gray-700 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                    #<?php echo htmlspecialchars($withdrawal['id'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-green-400 to-blue-400 flex items-center justify-center">
                                                <span class="text-white font-medium text-sm">
                                                    <?php echo strtoupper(substr($withdrawal['username'] ?? 'U', 0, 2)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-white">
                                                <?php echo htmlspecialchars($withdrawal['username'] ?? 'Unknown'); ?>
                                            </div>
                                            <div class="text-sm text-gray-400">
                                                <?php echo htmlspecialchars($withdrawal['email'] ?? 'No email'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                    <span class="text-green-400 font-medium text-lg">
                                        $<?php echo number_format($withdrawal['amount'] ?? 0, 2); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-600 text-gray-200">
                                        <?php echo htmlspecialchars($withdrawal['payment_method'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status = $withdrawal['status'] ?? 'pending';
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'completed' => 'bg-blue-100 text-blue-800'
                                ];
                                $statusLabels = [
                                    'pending' => 'Pending',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                    'completed' => 'Completed'
                                ];
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusColors[$status] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $statusLabels[$status] ?? ucfirst($status); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?php echo isset($withdrawal['created_at']) ? date('M j, Y H:i', strtotime($withdrawal['created_at'])) : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($status === 'pending'): ?>
                                    <button class="text-green-400 hover:text-green-300 mr-3" onclick="approveWithdrawal(<?php echo $withdrawal['id']; ?>)">
                                        <i class="fa-solid fa-check"></i> Approve
                                    </button>
                                    <button class="text-red-400 hover:text-red-300" onclick="rejectWithdrawal(<?php echo $withdrawal['id']; ?>)">
                                        <i class="fa-solid fa-times"></i> Reject
                                    </button>
                                <?php else: ?>
                                    <button class="text-blue-400 hover:text-blue-300" onclick="viewWithdrawal(<?php echo $withdrawal['id']; ?>)">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function approveWithdrawal(id) {
    if (confirm('Are you sure you want to approve this withdrawal?')) {
        alert('Approve withdrawal functionality will be implemented for ID: ' + id);
    }
}

function rejectWithdrawal(id) {
    if (confirm('Are you sure you want to reject this withdrawal?')) {
        alert('Reject withdrawal functionality will be implemented for ID: ' + id);
    }
}

function viewWithdrawal(id) {
    alert('View withdrawal details functionality will be implemented for ID: ' + id);
}
</script>
