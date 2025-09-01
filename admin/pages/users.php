<?php

?>

<div class="p-6">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">
            <i class="fa-solid fa-users text-blue-400 mr-3"></i>
            Users Management
        </h1>
        <p class="text-gray-400">Manage platform users, view statistics, and handle user accounts</p>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Users</p>
                    <p id="total-users-stat" class="text-3xl font-bold">0</p>
                </div>
                <div class="bg-blue-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-users text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Active Users</p>
                    <p id="active-users-stat" class="text-3xl font-bold">0</p>
                </div>
                <div class="bg-green-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-user-check text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-red-600 to-red-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">Banned Users</p>
                    <p id="banned-users-stat" class="text-3xl font-bold">0</p>
                </div>
                <div class="bg-red-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-user-slash text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">New Users (24h)</p>
                    <p id="new-users-stat" class="text-3xl font-bold">0</p>
                </div>
                <div class="bg-purple-500 bg-opacity-50 rounded-full p-3">
                    <i class="fa-solid fa-user-plus text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    
    <div class="bg-gray-800 rounded-xl p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" id="userSearch" placeholder="Search by username, email, or ID..." class="w-full bg-gray-700 text-white rounded-lg p-3 focus:ring-2 focus:ring-blue-500 border-none">
            </div>
            <div>
                <select id="statusFilter" class="w-full md:w-auto bg-gray-700 text-white rounded-lg p-3 focus:ring-2 focus:ring-blue-500 border-none">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="banned">Banned</option>
                </select>
            </div>
            <div>
                <button onclick="fetchUsers(1)" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-200 flex items-center justify-center">
                    <i class="fa-solid fa-search mr-2"></i>Search
                </button>
            </div>
        </div>
    </div>

    
    <div class="bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700">
            <h3 class="text-xl font-semibold text-white">All Users</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table-body" class="bg-gray-800 divide-y divide-gray-700">
                    
                </tbody>
            </table>
        </div>
    </div>

    
    <div id="pagination-container" class="mt-6 flex justify-center">
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    fetchUsers(1);
    // fetchUserStats(); // TODO: Implement this separately
});

let currentPage = 1;

async function fetchUsers(page) {
    currentPage = page;
    const search = document.getElementById('userSearch').value;
    const status = document.getElementById('statusFilter').value;
    const tableBody = document.getElementById('users-table-body');

    tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-16 text-center text-gray-400"><i class="fa-solid fa-spinner fa-spin text-4xl"></i></td></tr>`;

    try {
        const response = await fetch(`api/users.php?action=get_users&search=${search}&status=${status}&page=${page}`);
        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        renderUsers(data.users);
        renderPagination(data.total, data.limit, page);
        document.getElementById('total-users-stat').innerText = data.total; // Simple stat update

    } catch (error) {
        tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-16 text-center text-red-400">Error loading users: ${error.message}</td></tr>`;
        console.error('Failed to fetch users:', error);
    }
}

function renderUsers(users) {
    const tableBody = document.getElementById('users-table-body');
    if (users.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-400">
                    <div class="flex flex-col items-center py-8">
                        <i class="fa-solid fa-users text-6xl text-gray-600 mb-4"></i>
                        <p class="text-lg font-medium">No users found</p>
                        <p class="text-sm">Try adjusting your search or filters.</p>
                    </div>
                </td>
            </tr>`;
        return;
    }

    tableBody.innerHTML = users.map(user => `
        <tr class="hover:bg-gray-700 transition-colors duration-200">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                        <img class="h-10 w-10 rounded-full object-cover" src="https://ui-avatars.com/api/?name=${encodeURIComponent(user.username)}&background=random" alt="">
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-white">${escapeHTML(user.username)}</div>
                        <div class="text-xs text-gray-500">ID: ${escapeHTML(user.user_unique)}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-white">$${parseFloat(user.total_balance).toFixed(2)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">${escapeHTML(user.email)}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${user.status === 'active' ? 'bg-green-600 text-green-100' : 'bg-red-600 text-red-100'}">
                    ${escapeHTML(user.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                ${new Date(user.created_at).toLocaleDateString()}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="viewUser('${escapeHTML(user.user_unique)}')" class="text-blue-400 hover:text-blue-300 mr-3"><i class="fa-solid fa-eye"></i></button>
                <button onclick="editUser('${escapeHTML(user.user_unique)}')" class="text-yellow-400 hover:text-yellow-300 mr-3"><i class="fa-solid fa-edit"></i></button>
                ${user.status === 'active' 
                    ? `<button onclick="banUser('${escapeHTML(user.user_unique)}')" class="text-red-400 hover:text-red-300"><i class="fa-solid fa-ban"></i></button>`
                    : `<button onclick="unbanUser('${escapeHTML(user.user_unique)}')" class="text-green-400 hover:text-green-300"><i class="fa-solid fa-check"></i></button>`}
            </td>
        </tr>
    `).join('');
}

function renderPagination(total, limit, currentPage) {
    const paginationContainer = document.getElementById('pagination-container');
    const totalPages = Math.ceil(total / limit);
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    let paginationHTML = '<nav class="flex space-x-2">';
    for (let i = 1; i <= totalPages; i++) {
        const activeClass = i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600';
        paginationHTML += `<button onclick="fetchUsers(${i})" class="px-3 py-2 rounded-md text-sm font-medium ${activeClass}">${i}</button>`;
    }
    paginationHTML += '</nav>';
    paginationContainer.innerHTML = paginationHTML;
}

function escapeHTML(str) {
    if (typeof str !== 'string') return '';
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

// Stubs for actions - to be implemented later
function viewUser(userId) { alert('View user: ' + userId); }
function editUser(userId) { alert('Edit user: ' + userId); }
function banUser(userId) { if(confirm('Are you sure you want to ban ' + userId + '?')) alert('Banning user...'); }
function unbanUser(userId) { if(confirm('Are you sure you want to unban ' + userId + '?')) alert('Unbanning user...'); }

</script>
