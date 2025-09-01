<?php

$tickets = [];
$total_tickets = 0;
$open_tickets = 0;
$closed_tickets = 0;
$pending_tickets = 0;


if (isset($conn) && $conn) {
    try {
        
        $stats_query = "SELECT 
                           COUNT(*) as total,
                           COUNT(CASE WHEN status = 'open' THEN 1 END) as open_count,
                           COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_count,
                           COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
                        FROM support_tickets";
        $stats_result = $conn->query($stats_query);
        if ($stats_result) {
            $stats = $stats_result->fetch_assoc();
            $total_tickets = $stats['total'] ?? 0;
            $open_tickets = $stats['open_count'] ?? 0;
            $closed_tickets = $stats['closed_count'] ?? 0;
            $pending_tickets = $stats['pending_count'] ?? 0;
        }
        
        
        $query = "SELECT st.*, ud.username, ud.email 
                  FROM support_tickets st 
                  LEFT JOIN user_details ud ON st.user_unique = ud.user_unique 
                  ORDER BY st.created_at DESC 
                  LIMIT 50";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tickets[] = $row;
            }
        }
    } catch (Exception $e) {
        
        $tickets = [
            [
                'id' => 1,
                'user_unique' => 'user_001',
                'username' => 'player1',
                'email' => 'player1@example.com',
                'subject' => 'Problem with withdrawal',
                'message' => 'I cannot withdraw my funds, please help',
                'status' => 'open',
                'priority' => 'high',
                'category' => 'payment',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                'id' => 2,
                'user_unique' => 'user_002',
                'username' => 'player2',
                'email' => 'player2@example.com',
                'subject' => 'Account verification',
                'message' => 'How do I verify my account?',
                'status' => 'pending',
                'priority' => 'medium',
                'category' => 'account',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-6 hours'))
            ],
            [
                'id' => 3,
                'user_unique' => 'user_003',
                'username' => 'player3',
                'email' => 'player3@example.com',
                'subject' => 'Game bug report',
                'message' => 'Found a bug in the slot game',
                'status' => 'closed',
                'priority' => 'low',
                'category' => 'technical',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ]
        ];
        $total_tickets = count($tickets);
        $open_tickets = count(array_filter($tickets, function($t) { return $t['status'] === 'open'; }));
        $closed_tickets = count(array_filter($tickets, function($t) { return $t['status'] === 'closed'; }));
        $pending_tickets = count(array_filter($tickets, function($t) { return $t['status'] === 'pending'; }));
    }
}
?>

<div class="p-6">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2 leading-tight flex items-center">
            <i class="fa-solid fa-headset text-green-400 mr-3"></i>
            Support Management
        </h1>
        <p class="text-gray-400 leading-snug">Manage user support tickets and inquiries</p>
    </div>


<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-blue-500 transition-colors duration-300">
        <div class="flex items-center min-w-0">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-headset text-white"></i>
            </div>
            <div class="ml-4 min-w-0">
                <p class="text-sm text-gray-400 font-semibold leading-tight truncate">Łącznie zgłoszeń</p>
                <p class="text-2xl font-bold text-white leading-tight truncate"><?php echo number_format($total_tickets); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-red-500 transition-colors duration-300">
        <div class="flex items-center min-w-0">
            <div class="p-2 bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-exclamation-circle text-white"></i>
            </div>
            <div class="ml-4 min-w-0">
                <p class="text-sm text-gray-400 font-semibold leading-tight truncate">Otwarte</p>
                <p class="text-2xl font-bold text-white leading-tight truncate"><?php echo number_format($open_tickets); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-green-500 transition-colors duration-300">
        <div class="flex items-center min-w-0">
            <div class="p-2 bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-check-circle text-white"></i>
            </div>
            <div class="ml-4 min-w-0">
                <p class="text-sm text-gray-400 font-semibold leading-tight truncate">Zamknięte</p>
                <p class="text-2xl font-bold text-white leading-tight truncate"><?php echo number_format($closed_tickets); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800 rounded-lg shadow-2xl p-6 border border-gray-700 hover:border-purple-500 transition-colors duration-300">
        <div class="flex items-center min-w-0">
            <div class="p-2 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg">
                <i class="fa-solid fa-chart-line text-white"></i>
            </div>
            <div class="ml-4 min-w-0">
                <p class="text-sm text-gray-400 font-semibold leading-tight truncate">Współczynnik rozwiązań</p>
                <p class="text-2xl font-bold text-white leading-tight truncate"><?php echo $total_tickets > 0 ? round(($closed_tickets / $total_tickets) * 100) : 0; ?>%</p>
            </div>
        </div>
    </div>
</div>


<div class="bg-gray-800 shadow-2xl rounded-xl border border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-700">
        <h3 class="text-xl font-bold text-white flex items-center">
            <i class="fa-solid fa-headset text-green-400 mr-3"></i>
            Support Tickets
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full table-fixed">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
                <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            <div class="flex flex-col items-center py-8">
                                <i class="fa-solid fa-headset text-6xl text-gray-600 mb-4"></i>
                                <p class="text-lg font-medium">No support tickets found</p>
                                <p class="text-sm">Tickets will appear here when users submit them</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr class="hover:bg-gray-700 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                #<?php echo htmlspecialchars($ticket['id'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-400 to-purple-400 flex items-center justify-center">
                                            <span class="text-white font-medium text-sm">
                                                <?php echo strtoupper(substr($ticket['username'] ?? 'U', 0, 2)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4 min-w-0">
                                        <div class="text-sm font-medium text-white truncate">
                                            <?php echo htmlspecialchars($ticket['username'] ?? 'Unknown'); ?>
                                        </div>
                                        <div class="text-sm text-gray-400 truncate">
                                            <?php echo htmlspecialchars($ticket['email'] ?? 'No email'); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-white truncate max-w-[260px]">
                                    <?php echo htmlspecialchars($ticket['subject'] ?? 'No subject'); ?>
                                </div>
                                <div class="text-sm text-gray-400 truncate max-w-[420px]">
                                    <?php echo htmlspecialchars($ticket['message'] ?? 'No message'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-600 text-gray-200">
                                    <?php 
                                    $category = $ticket['category'] ?? 'general';
                                    $categoryLabels = [
                                        'general' => 'General',
                                        'payment' => 'Payment',
                                        'technical' => 'Technical',
                                        'account' => 'Account',
                                        'bug' => 'Bug'
                                    ];
                                    echo $categoryLabels[$category] ?? ucfirst($category);
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $priority = $ticket['priority'] ?? 'medium';
                                $priorityColors = [
                                    'low' => 'bg-green-100 text-green-800',
                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                    'high' => 'bg-red-100 text-red-800',
                                    'urgent' => 'bg-purple-100 text-purple-800'
                                ];
                                $priorityLabels = [
                                    'low' => 'Low',
                                    'medium' => 'Medium',
                                    'high' => 'High',
                                    'urgent' => 'Urgent'
                                ];
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $priorityColors[$priority] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $priorityLabels[$priority] ?? ucfirst($priority); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status = $ticket['status'] ?? 'open';
                                $statusColors = [
                                    'open' => 'bg-red-100 text-red-800',
                                    'in_progress' => 'bg-yellow-100 text-yellow-800',
                                    'resolved' => 'bg-blue-100 text-blue-800',
                                    'closed' => 'bg-green-100 text-green-800'
                                ];
                                $statusLabels = [
                                    'open' => 'Open',
                                    'pending' => 'Pending',
                                    'in_progress' => 'In Progress',
                                    'resolved' => 'Resolved',
                                    'closed' => 'Closed'
                                ];
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusColors[$status] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $statusLabels[$status] ?? ucfirst($status); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?php echo isset($ticket['created_at']) ? date('M j, Y H:i', strtotime($ticket['created_at'])) : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="text-blue-400 hover:text-blue-300 mr-3" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                    <i class="fa-solid fa-eye"></i> View
                                </button>
                                <button class="text-yellow-400 hover:text-yellow-300 mr-3" onclick="editTicket(<?php echo $ticket['id']; ?>)">
                                    <i class="fa-solid fa-edit"></i> Edit
                                </button>
                                <?php if ($status !== 'closed'): ?>
                                    <button class="text-green-400 hover:text-green-300" onclick="closeTicket(<?php echo $ticket['id']; ?>)">
                                        <i class="fa-solid fa-check"></i> Close
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

<script>
function viewTicket(id) {
    alert('View ticket functionality will be implemented for ticket #' + id);
}

function editTicket(id) {
    alert('Edit ticket functionality will be implemented for ticket #' + id);
}

function closeTicket(id) {
    if (confirm('Are you sure you want to close this ticket?')) {
        alert('Close ticket functionality will be implemented for ticket #' + id);
    }
}
</script>
</div>
