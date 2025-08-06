<?php
// Start session for admin authentication
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection settings
require_once '../config.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Process actions if any
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'cancel':
                if (isset($_POST['subscription_id'])) {
                    $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = :id");
                    $stmt->bindParam(':id', $_POST['subscription_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    
                    // Log the action
                    $logStmt = $pdo->prepare("INSERT INTO subscription_history (user_id, subscription_id, plan, action, description) 
                                             SELECT user_id, id, plan, 'cancelled', 'Subscription cancelled by admin' 
                                             FROM subscriptions WHERE id = :id");
                    $logStmt->bindParam(':id', $_POST['subscription_id'], PDO::PARAM_INT);
                    $logStmt->execute();
                    
                    $_SESSION['message'] = 'Subscription cancelled successfully.';
                }
                break;
                
            case 'extend':
                if (isset($_POST['subscription_id']) && isset($_POST['days'])) {
                    $stmt = $pdo->prepare("UPDATE subscriptions SET end_date = DATE_ADD(end_date, INTERVAL :days DAY) WHERE id = :id");
                    $stmt->bindParam(':days', $_POST['days'], PDO::PARAM_INT);
                    $stmt->bindParam(':id', $_POST['subscription_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    
                    // Log the action
                    $logStmt = $pdo->prepare("INSERT INTO subscription_history (user_id, subscription_id, plan, action, description) 
                                             SELECT user_id, id, plan, 'extended', CONCAT('Subscription extended by ', :days, ' days by admin') 
                                             FROM subscriptions WHERE id = :id");
                    $logStmt->bindParam(':days', $_POST['days'], PDO::PARAM_INT);
                    $logStmt->bindParam(':id', $_POST['subscription_id'], PDO::PARAM_INT);
                    $logStmt->execute();
                    
                    $_SESSION['message'] = 'Subscription extended successfully.';
                }
                break;
                
            case 'create_coupon':
                if (isset($_POST['code']) && isset($_POST['discount'])) {
                    $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_percent, description, valid_from, valid_to, max_uses, active) 
                                          VALUES (:code, :discount, :description, :valid_from, :valid_to, :max_uses, 1)");
                    
                    $stmt->bindParam(':code', $_POST['code'], PDO::PARAM_STR);
                    $stmt->bindParam(':discount', $_POST['discount'], PDO::PARAM_STR);
                    $stmt->bindParam(':description', $_POST['description'], PDO::PARAM_STR);
                    $stmt->bindParam(':valid_from', $_POST['valid_from'], PDO::PARAM_STR);
                    $stmt->bindParam(':valid_to', $_POST['valid_to'], PDO::PARAM_STR);
                    $stmt->bindParam(':max_uses', $_POST['max_uses'], PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $_SESSION['message'] = 'Coupon created successfully.';
                }
                break;
        }
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Management - AnimeElite Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        /* DataTables customization for dark theme */
        table.dataTable {
            background-color: #121212;
            color: white;
            border-collapse: collapse;
        }
        
        table.dataTable thead th, 
        table.dataTable thead td {
            border-bottom: 1px solid #333;
            padding: 10px 18px;
        }
        
        table.dataTable tbody tr {
            background-color: #121212;
        }
        
        table.dataTable tbody tr:hover {
            background-color: #1a1a1a;
        }
        
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #a0a0a0;
            margin-bottom: 10px;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            background-color: #1a1a1a;
            color: white;
            border: 1px solid #333;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_length select {
            background-color: #1a1a1a;
            color: white;
            border: 1px solid #333;
            border-radius: 0.25rem;
            padding: 0.25rem 1rem 0.25rem 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #a0a0a0 !important;
            border: 1px solid #333;
            background: #1a1a1a;
            border-radius: 0.25rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            color: white !important;
            border: 1px solid #7c3aed;
            background: #7c3aed;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            color: white !important;
            border: 1px solid #7c3aed;
            background: rgba(124, 58, 237, 0.5) !important;
        }
    </style>
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <!-- Admin Navigation -->
    <nav class="navbar py-3 px-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <a href="index.php" class="text-xl font-bold logo-text">AnimeElite Admin</a>
                <div class="ml-8 hidden md:flex items-center space-x-4">
                    <a href="index.php" class="text-gray-300 hover:text-white transition-colors duration-200">Dashboard</a>
                    <a href="users.php" class="text-gray-300 hover:text-white transition-colors duration-200">Users</a>
                    <a href="subscription_management.php" class="text-white font-medium">Subscriptions</a>
                    <a href="coupons.php" class="text-gray-300 hover:text-white transition-colors duration-200">Coupons</a>
                    <a href="settings.php" class="text-gray-300 hover:text-white transition-colors duration-200">Settings</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-300"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="logout.php" class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded-md text-sm transition-colors duration-200">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-900 border border-green-700 text-white px-4 py-3 rounded mb-6 flex justify-between items-center">
                <span><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></span>
                <button class="text-white hover:text-green-300 focus:outline-none" onclick="this.parentElement.remove();">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-900 border border-red-700 text-white px-4 py-3 rounded mb-6 flex justify-between items-center">
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                <button class="text-white hover:text-red-300 focus:outline-none" onclick="this.parentElement.remove();">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Subscription Management</h1>
            <div>
                <button onclick="document.getElementById('add-coupon-modal').classList.remove('hidden')" class="btn btn-primary py-2 px-4">
                    Create Coupon
                </button>
            </div>
        </div>
        
        <!-- Subscriptions Table -->
        <div class="bg-gray-900 rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">Active Subscriptions</h2>
            <table id="subscriptionsTable" class="w-full stripe hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        // Fetch subscriptions data
                        $stmt = $pdo->prepare("
                            SELECT s.id, s.user_id, u.email, u.display_name, s.plan, s.start_date, s.end_date, s.status, s.price
                            FROM subscriptions s
                            JOIN users u ON s.user_id = u.id
                            ORDER BY s.created_at DESC
                        ");
                        $stmt->execute();
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['display_name'] ? $row['display_name'] : $row['email']) . '</td>';
                            echo '<td>' . htmlspecialchars(ucfirst($row['plan'])) . '</td>';
                            echo '<td>' . htmlspecialchars($row['start_date']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['end_date'] ?? 'Never') . '</td>';
                            
                            // Status with color
                            $statusColor = 'bg-gray-700';
                            switch ($row['status']) {
                                case 'active':
                                    $statusColor = 'bg-green-700';
                                    break;
                                case 'expired':
                                    $statusColor = 'bg-red-700';
                                    break;
                                case 'cancelled':
                                    $statusColor = 'bg-yellow-700';
                                    break;
                            }
                            
                            echo '<td><span class="px-2 py-1 rounded ' . $statusColor . ' text-white text-xs">' . 
                                 htmlspecialchars(ucfirst($row['status'])) . '</span></td>';
                            
                            // Actions
                            echo '<td class="flex space-x-2">';
                            
                            if ($row['status'] === 'active') {
                                // Cancel button
                                echo '<form method="post">';
                                echo '<input type="hidden" name="action" value="cancel">';
                                echo '<input type="hidden" name="subscription_id" value="' . $row['id'] . '">';
                                echo '<button type="submit" class="text-xs bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded" 
                                       onclick="return confirm(\'Are you sure you want to cancel this subscription?\');">Cancel</button>';
                                echo '</form>';
                                
                                // Extend button
                                echo '<button class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded" 
                                       onclick="openExtendModal(' . $row['id'] . ')">Extend</button>';
                            } else if ($row['status'] === 'expired' || $row['status'] === 'cancelled') {
                                // Reactive button
                                echo '<button class="text-xs bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded" 
                                       onclick="openReactivateModal(' . $row['id'] . ')">Reactivate</button>';
                            }
                            
                            // View history button
                            echo '<button class="text-xs bg-gray-600 hover:bg-gray-700 text-white px-2 py-1 rounded" 
                                   onclick="loadSubscriptionHistory(\'' . $row['user_id'] . '\')">History</button>';
                                   
                            echo '</td>';
                            echo '</tr>';
                        }
                    } catch (PDOException $e) {
                        echo '<tr><td colspan="7" class="text-red-500">Error loading subscription data</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Coupon Table -->
        <div class="bg-gray-900 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold mb-4">Active Coupons</h2>
            <table id="couponsTable" class="w-full stripe hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Description</th>
                        <th>Valid From</th>
                        <th>Valid To</th>
                        <th>Uses</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        // Fetch coupon data
                        $stmt = $pdo->prepare("
                            SELECT id, code, discount_percent, description, valid_from, valid_to, max_uses, current_uses
                            FROM coupons
                            WHERE active = 1
                            ORDER BY created_at DESC
                        ");
                        $stmt->execute();
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['code']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['discount_percent']) . '%</td>';
                            echo '<td>' . htmlspecialchars($row['description'] ?? '') . '</td>';
                            echo '<td>' . htmlspecialchars($row['valid_from'] ?? 'No limit') . '</td>';
                            echo '<td>' . htmlspecialchars($row['valid_to'] ?? 'No limit') . '</td>';
                            echo '<td>' . htmlspecialchars($row['current_uses'] . '/' . ($row['max_uses'] ?? '∞')) . '</td>';
                            
                            // Actions
                            echo '<td>';
                            echo '<button class="text-xs bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded mr-2" 
                                   onclick="deactivateCoupon(' . $row['id'] . ')">Deactivate</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } catch (PDOException $e) {
                        echo '<tr><td colspan="7" class="text-red-500">Error loading coupon data</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Extend Subscription Modal -->
    <div id="extend-modal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">Extend Subscription</h3>
            <form method="post">
                <input type="hidden" name="action" value="extend">
                <input type="hidden" name="subscription_id" id="extend-subscription-id">
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Number of Days</label>
                    <input type="number" name="days" min="1" value="30" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('extend-modal').classList.add('hidden')" 
                            class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        Extend
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Coupon Modal -->
    <div id="add-coupon-modal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Create New Coupon</h3>
                <button onclick="document.getElementById('add-coupon-modal').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="post">
                <input type="hidden" name="action" value="create_coupon">
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Coupon Code</label>
                    <input type="text" name="code" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Discount Percentage</label>
                    <input type="number" name="discount" min="1" max="100" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Description</label>
                    <textarea name="description" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Valid From (Optional)</label>
                    <input type="date" name="valid_from" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Valid To (Optional)</label>
                    <input type="date" name="valid_to" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-300 mb-2">Maximum Uses (Optional)</label>
                    <input type="number" name="max_uses" min="1" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                    <p class="text-gray-400 text-xs mt-1">Leave empty for unlimited uses</p>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary px-6 py-2">
                        Create Coupon
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Subscription History Modal -->
    <div id="history-modal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-3xl max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Subscription History</h3>
                <button onclick="document.getElementById('history-modal').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="subscription-history-content">
                <div class="flex justify-center items-center h-32">
                    <div class="spinner-border animate-spin h-8 w-8 border-t-2 border-b-2 border-purple-500 rounded-full"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('#subscriptionsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100]
            });
            
            $('#couponsTable').DataTable({
                order: [[3, 'desc']],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100]
            });
        });
        
        // Open extend subscription modal
        function openExtendModal(subscriptionId) {
            document.getElementById('extend-subscription-id').value = subscriptionId;
            document.getElementById('extend-modal').classList.remove('hidden');
        }
        
        // Deactivate coupon
        function deactivateCoupon(couponId) {
            if (confirm('Are you sure you want to deactivate this coupon?')) {
                // You can use AJAX here to deactivate the coupon
                fetch('ajax/deactivate_coupon.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `coupon_id=${couponId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Coupon deactivated successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            }
        }
        
        // Load subscription history
        function loadSubscriptionHistory(userId) {
            const modal = document.getElementById('history-modal');
            const content = document.getElementById('subscription-history-content');
            
            modal.classList.remove('hidden');
            content.innerHTML = `<div class="flex justify-center items-center h-32">
                <div class="spinner-border animate-spin h-8 w-8 border-t-2 border-b-2 border-purple-500 rounded-full"></div>
            </div>`;
            
            // Load history data via AJAX
            fetch('ajax/subscription_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${userId}`
            })
            .then(response => response.text())
            .then(data => {
                content.innerHTML = data;
            })
            .catch(error => {
                content.innerHTML = `<div class="text-red-500">Error loading subscription history: ${error}</div>`;
            });
        }
        
        // Reactivate subscription
        function openReactivateModal(subscriptionId) {
            // You can implement this functionality as needed
            alert('Reactivate subscription functionality will be implemented here');
        }
    </script>
</body>
</html> 