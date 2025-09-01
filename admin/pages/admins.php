<?php

$admin_role = $_SESSION['admin_role'] ?? 'Support'; 
?>

<div class="p-6" id="admin-management-container">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">
            <i class="fa-solid fa-user-tie text-purple-400 mr-3"></i>
            Admin Management
        </h1>
        <p class="text-gray-400">Manage admin accounts, roles, and permissions</p>
    </div>

    
    <div id="stats-cards" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        
        <div class="stat-card-placeholder h-28 bg-gray-800 rounded-xl animate-pulse"></div>
        <div class="stat-card-placeholder h-28 bg-gray-800 rounded-xl animate-pulse"></div>
        <div class="stat-card-placeholder h-28 bg-gray-800 rounded-xl animate-pulse"></div>
        <div class="stat-card-placeholder h-28 bg-gray-800 rounded-xl animate-pulse"></div>
    </div>

    
    <div class="mb-6">
        <?php if ($admin_role === 'Super Admin'): ?>
        <button onclick="showAddAdminModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200">
            <i class="fa-solid fa-plus mr-2"></i>
            Add New Admin
        </button>
        <?php endif; ?>
    </div>

    
    <div class="bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700">
            <h3 class="text-xl font-semibold text-white">All Administrators</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Admin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Last Login</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="admins-table-body" class="bg-gray-800 divide-y divide-gray-700">
                    
                    <tr><td colspan="5" class="text-center p-8 text-gray-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading admin data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div id="addAdminModal" class="fixed inset-0 bg-black bg-opacity-70 hidden z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-md w-full p-6 m-4">
        <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-3">
            <h3 class="text-lg font-semibold text-white">Add New Administrator</h3>
            <button onclick="hideAddAdminModal()" class="text-gray-400 hover:text-white">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="addAdminForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                <input type="text" name="username" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                <input type="email" name="email" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                <input type="password" name="password" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Role</label>
                <select name="role" class="w-full border border-gray-600 bg-gray-700 text-white rounded-md px-3 py-2 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition">
                    <option value="Support">Support</option>
                    <option value="Moderator">Moderator</option>
                    <option value="Super Admin">Super Admin</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="hideAddAdminModal()" class="px-4 py-2 text-gray-300 bg-gray-600 hover:bg-gray-500 rounded-md transition-colors duration-200">
                    Cancel
                </button>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200">
                    Add Admin
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_URL = 'api/admins.php';
    const tableBody = document.getElementById('admins-table-body');
    const statsContainer = document.getElementById('stats-cards');
    const currentUserRole = '<?php echo $admin_role; ?>';

    const fetchAdmins = async () => {
        try {
            const response = await fetch(`${API_URL}?action=get_admins`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const admins = await response.json();
            renderTable(admins);
            renderStats(admins);
        } catch (error) {
            console.error('Failed to fetch admins:', error);
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center p-8 text-red-400">Error loading admin data. Please try again later.</td></tr>`;
        }
    };

    const renderStats = (admins) => {
        const totalAdmins = admins.length;
        const superAdmins = admins.filter(a => a.role === 'Super Admin').length;
        const moderators = admins.filter(a => a.role === 'Moderator').length;
        const support = admins.filter(a => a.role === 'Support').length;

        statsContainer.innerHTML = `
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-6 text-white">
                <p class="text-purple-100 text-sm font-medium">Total Admins</p>
                <p class="text-3xl font-bold">${totalAdmins}</p>
            </div>
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 text-white">
                <p class="text-blue-100 text-sm font-medium">Super Admins</p>
                <p class="text-3xl font-bold">${superAdmins}</p>
            </div>
            <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6 text-white">
                <p class="text-green-100 text-sm font-medium">Moderators</p>
                <p class="text-3xl font-bold">${moderators}</p>
            </div>
            <div class="bg-gradient-to-r from-orange-600 to-orange-700 rounded-xl p-6 text-white">
                <p class="text-orange-100 text-sm font-medium">Support Staff</p>
                <p class="text-3xl font-bold">${support}</p>
            </div>
        `;
    };

    const renderTable = (admins) => {
        if (admins.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center p-8 text-gray-400">No administrators found.</td></tr>`;
            return;
        }

        tableBody.innerHTML = '';
        admins.forEach(admin => {
            const roleClass = {
                'Super Admin': 'bg-blue-500 text-blue-100',
                'Moderator': 'bg-green-500 text-green-100',
                'Support': 'bg-orange-500 text-orange-100'
            }[admin.role] || 'bg-gray-500';

            const statusClass = admin.status === 'active' ? 'bg-green-500 text-green-100' : 'bg-red-500 text-red-100';

            const lastLogin = admin.last_login ? new Date(admin.last_login).toLocaleString() : 'Never';

            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-700 transition-colors duration-200';
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div>
                            <div class="text-sm font-medium text-white">${escapeHTML(admin.username)}</div>
                            <div class="text-sm text-gray-400">${escapeHTML(admin.email)}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${roleClass}">${escapeHTML(admin.role)}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                     <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">${escapeHTML(admin.status)}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">${lastLogin}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    ${currentUserRole === 'Super Admin' ? `
                    <button onclick="editAdmin(${admin.id})" class="text-indigo-400 hover:text-indigo-300 mr-3">Edit</button>
                    <button onclick="deleteAdmin(${admin.id})" class="text-red-500 hover:text-red-400">Delete</button>
                    ` : '<span class="text-gray-500">No actions</span>'}
                </td>
            `;
            tableBody.appendChild(row);
        });
    };

    document.getElementById('addAdminForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        try {
            const response = await fetch(`${API_URL}?action=add_admin`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'Failed to add admin.');
            }

            alert('Admin added successfully!');
            hideAddAdminModal();
            fetchAdmins(); // Refresh the table
        } catch (error) {
            alert(`Error: ${error.message}`);
        }
    });

    fetchAdmins();
});

function showAddAdminModal() {
    document.getElementById('addAdminModal').classList.remove('hidden');
}

function hideAddAdminModal() {
    document.getElementById('addAdminModal').classList.add('hidden');
    document.getElementById('addAdminForm').reset();
}

function editAdmin(id) {
    alert('Edit functionality is not yet implemented. Admin ID: ' + id);
}

async function deleteAdmin(id) {
    if (!confirm('Are you sure you want to delete this administrator? This action cannot be undone.')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);

        const response = await fetch('api/admins.php?action=delete_admin', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || 'Failed to delete admin.');
        }

        alert('Admin deleted successfully!');
        document.location.reload(); // Easiest way to refresh data and stats
    } catch (error) {
        alert(`Error: ${error.message}`);
    }
}

function escapeHTML(str) {
    return str.replace(/[&<>'"/]/g, function (s) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;'
        }[s];
    });
}
</script>
