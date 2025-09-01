<?php

$documents = [];
$total_documents = 0;


if (isset($conn) && $conn) {
    
    
    $documents = [
        [
            'id' => 1,
            'title' => 'Terms of Service',
            'description' => 'Platform terms and conditions',
            'type' => 'legal',
            'file_path' => '/documents/terms.pdf',
            'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'id' => 2,
            'title' => 'Privacy Policy',
            'description' => 'User privacy and data protection policy',
            'type' => 'legal',
            'file_path' => '/documents/privacy.pdf',
            'created_at' => date('Y-m-d H:i:s', strtotime('-25 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ],
        [
            'id' => 3,
            'title' => 'User Manual',
            'description' => 'Complete guide for platform usage',
            'type' => 'guide',
            'file_path' => '/documents/manual.pdf',
            'created_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'id' => 4,
            'title' => 'API Documentation',
            'description' => 'Developer API reference',
            'type' => 'technical',
            'file_path' => '/documents/api.pdf',
            'created_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ]
    ];
    $total_documents = count($documents);
}
?>

<div class="p-6">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">
            <i class="fa-solid fa-file-text text-blue-400 mr-3"></i>
            Documents Management
        </h1>
        <p class="text-gray-400">Manage platform documents, policies, and guides</p>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Documents</p>
                    <p class="text-3xl font-bold"><?php echo $total_documents; ?></p>
                </div>
                <div class="bg-blue-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-file-text text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Legal Documents</p>
                    <p class="text-3xl font-bold"><?php echo count(array_filter($documents, function($d) { return $d['type'] === 'legal'; })); ?></p>
                </div>
                <div class="bg-green-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-gavel text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Guides</p>
                    <p class="text-3xl font-bold"><?php echo count(array_filter($documents, function($d) { return $d['type'] === 'guide'; })); ?></p>
                </div>
                <div class="bg-purple-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-book text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-orange-600 to-orange-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Technical Docs</p>
                    <p class="text-3xl font-bold"><?php echo count(array_filter($documents, function($d) { return $d['type'] === 'technical'; })); ?></p>
                </div>
                <div class="bg-orange-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-code text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    
    <div class="mb-6">
        <button onclick="showAddDocumentModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200">
            <i class="fa-solid fa-plus mr-2"></i>
            Add New Document
        </button>
    </div>

    
    <div class="bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700">
            <h3 class="text-xl font-semibold text-white">All Documents</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Document</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Updated</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700">
                    <?php if (!empty($documents)): ?>
                        <?php foreach ($documents as $document): ?>
                        <tr class="hover:bg-gray-700 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center">
                                            <i class="fa-solid fa-file-text text-white"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($document['title']); ?></div>
                                        <div class="text-sm text-gray-400"><?php echo htmlspecialchars($document['description']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php 
                                    switch($document['type']) {
                                        case 'legal': echo 'bg-red-100 text-red-800'; break;
                                        case 'guide': echo 'bg-green-100 text-green-800'; break;
                                        case 'technical': echo 'bg-blue-100 text-blue-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($document['type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?php echo date('M j, Y', strtotime($document['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?php echo date('M j, Y', strtotime($document['updated_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="downloadDocument(<?php echo $document['id']; ?>)" class="text-blue-400 hover:text-blue-300 mr-3">
                                    <i class="fa-solid fa-download"></i> Download
                                </button>
                                <button onclick="editDocument(<?php echo $document['id']; ?>)" class="text-yellow-400 hover:text-yellow-300 mr-3">
                                    <i class="fa-solid fa-edit"></i> Edit
                                </button>
                                <button onclick="deleteDocument(<?php echo $document['id']; ?>)" class="text-red-400 hover:text-red-300">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-400">
                                <div class="flex flex-col items-center py-8">
                                    <i class="fa-solid fa-file-text text-6xl text-gray-600 mb-4"></i>
                                    <p class="text-lg font-medium">No documents found</p>
                                    <p class="text-sm">Start by adding your first document</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div id="addDocumentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-white">Add New Document</h3>
                <button onclick="hideAddDocumentModal()" class="text-gray-400 hover:text-white">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            
            <form id="addDocumentForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Title</label>
                    <input type="text" name="title" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Type</label>
                    <select name="type" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="legal">Legal</option>
                        <option value="guide">Guide</option>
                        <option value="technical">Technical</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">File</label>
                    <input type="file" name="file" accept=".pdf,.doc,.docx" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="hideAddDocumentModal()" class="px-4 py-2 text-gray-300 hover:text-white transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors duration-200">
                        Add Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAddDocumentModal() {
    document.getElementById('addDocumentModal').classList.remove('hidden');
}

function hideAddDocumentModal() {
    document.getElementById('addDocumentModal').classList.add('hidden');
    document.getElementById('addDocumentForm').reset();
}

function downloadDocument(id) {
    // Implement download functionality
    alert('Download functionality will be implemented');
}

function editDocument(id) {
    // Implement edit functionality
    alert('Edit functionality will be implemented');
}

function deleteDocument(id) {
    if (confirm('Are you sure you want to delete this document?')) {
        // Implement delete functionality
        alert('Delete functionality will be implemented');
    }
}

// Handle form submission
document.getElementById('addDocumentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Implement form submission
    alert('Add document functionality will be implemented');
    hideAddDocumentModal();
});
</script>
