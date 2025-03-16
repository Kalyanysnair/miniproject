<?php
session_start();
include 'connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get all drivers
$drivers_query = "SELECT u.userid, u.username 
                 FROM tbl_user u 
                 WHERE u.role = 'driver'
                 ORDER BY u.username";
$drivers_result = $conn->query($drivers_query);
$drivers = [];
while ($row = $drivers_result->fetch_assoc()) {
    $drivers[] = $row;
}

// Get selected driver and status filters
$selected_driver = isset($_GET['driver']) ? $_GET['driver'] : 'all';
$selected_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$selected_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build the query for requests with consistent collation
$requests_query = "
    (SELECT 
        'emergency' COLLATE utf8mb4_unicode_ci as request_type,
        e.request_id as id,
        e.patient_name COLLATE utf8mb4_unicode_ci as patient_name,
        e.pickup_location COLLATE utf8mb4_unicode_ci as location,
        e.contact_phone COLLATE utf8mb4_unicode_ci as phone,
        e.status COLLATE utf8mb4_unicode_ci as status,
        e.created_at,
        e.amount,
        COALESCE(e.payment_status, 'Pending') COLLATE utf8mb4_unicode_ci as payment_status,
        u.username COLLATE utf8mb4_unicode_ci as driver_name,
        u.userid as driver_id
    FROM tbl_emergency e
    LEFT JOIN tbl_user u ON e.driver_id = u.userid
    WHERE e.status IN ('Accepted', 'Approved', 'Completed'))
    
    UNION ALL
    
    (SELECT 
        'palliative' COLLATE utf8mb4_unicode_ci as request_type,
        p.palliativeid as id,
        u_req.username COLLATE utf8mb4_unicode_ci as patient_name,
        p.address COLLATE utf8mb4_unicode_ci as location,
        u_req.phoneno COLLATE utf8mb4_unicode_ci as phone,
        p.status COLLATE utf8mb4_unicode_ci as status,
        p.created_at,
        p.amount,
        COALESCE(p.payment_status, 'Pending') COLLATE utf8mb4_unicode_ci as payment_status,
        u_dr.username COLLATE utf8mb4_unicode_ci as driver_name,
        u_dr.userid as driver_id
    FROM tbl_palliative p
    LEFT JOIN tbl_user u_req ON p.userid = u_req.userid
    LEFT JOIN tbl_user u_dr ON p.driver_id = u_dr.userid
    WHERE p.status IN ('Accepted', 'Approved', 'Completed'))
    
    UNION ALL
    
    (SELECT 
        'prebooking' COLLATE utf8mb4_unicode_ci as request_type,
        p.prebookingid as id,
        u.username COLLATE utf8mb4_unicode_ci as patient_name,
        p.pickup_location COLLATE utf8mb4_unicode_ci as location,
        u.phoneno COLLATE utf8mb4_unicode_ci as phone,
        p.status COLLATE utf8mb4_unicode_ci as status,
        p.created_at,
        p.amount,
        COALESCE(p.payment_status, 'Pending') COLLATE utf8mb4_unicode_ci as payment_status,
        u_dr.username COLLATE utf8mb4_unicode_ci as driver_name,
        u_dr.userid as driver_id
    FROM tbl_prebooking p
    LEFT JOIN tbl_user u ON p.userid = u.userid
    LEFT JOIN tbl_user u_dr ON p.driver_id = u_dr.userid
    WHERE p.status IN ('Accepted', 'Approved', 'Completed'))
    ORDER BY created_at DESC";

// Execute the query
$requests_result = $conn->query($requests_query);

// Check for query error
if (!$requests_result) {
    die("Query failed: " . $conn->error);
}

$requests = [];
while ($row = $requests_result->fetch_assoc()) {
    // Modified filtering logic to handle combined status and request type
    $status_match = $selected_status === 'all' || 
                   $row['status'] === $selected_status || 
                   ($selected_status === 'active' && ($row['status'] === 'Accepted' || $row['status'] === 'Approved'));
    
    $type_match = $selected_type === 'all' || $row['request_type'] === $selected_type;
    
    if (($selected_driver === 'all' || $row['driver_id'] == $selected_driver) && 
        $status_match && $type_match) {
        $requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Requests - Admin Dashboard</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background-image: url('assets/assets/img/template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
            padding-top: 100px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .filters {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }

        .request-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .request-card:hover {
            transform: translateY(-2px);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .request-type {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }

        .type-emergency { background: #ffd7d7; color: #c41e3a; }
        .type-palliative { background: #d7ffd7; color: #1e8449; }
        .type-prebooking { background: #d7d7ff; color: #1e3a8a; }

        .request-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .detail-group {
            padding: 10px;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 8px;
        }

        .detail-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 600;
        }

        .status-Accepted { background: #d4edda; color: #155724; }
        .status-Approved { background: #cce5ff; color: #004085; }
        .status-Completed { background: #d4edda; color: #155724; }

        .payment-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .payment-Paid { background: #d4edda; color: #155724; }
        .payment-Pending { background: #fff3cd; color: #856404; }

        .back-button {
            position: fixed;
            top: 30px;
            left: 30px;
            color: rgba(40, 167, 69, 0.9);  /* Bootstrap's success green with slight transparency */
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            transition: all 0.2s ease;
            font-size: 28px;
        }

        .back-button:hover {
            color: rgb(40, 167, 69);  /* Solid green on hover */
            text-decoration: none;
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .request-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <a href="admin.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="container">
        <div class="glass-card">
            <h2 class="mb-4"><i class="fas fa-tasks"></i> Driver Request Management</h2>

            <div class="filters">
                <div class="filter-group">
                    <label for="type-filter">Request Type:</label>
                    <select id="type-filter" name="type" onchange="updateFilters()">
                        <option value="all">All Types</option>
                        <option value="emergency" <?php echo $selected_type == 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                        <option value="prebooking" <?php echo $selected_type == 'prebooking' ? 'selected' : ''; ?>>Pre-booking</option>
                        <option value="palliative" <?php echo $selected_type == 'palliative' ? 'selected' : ''; ?>>Palliative</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="driver-filter">Driver:</label>
                    <select id="driver-filter" name="driver" onchange="updateFilters()">
                        <option value="all">All Drivers</option>
                        <?php foreach ($drivers as $driver): ?>
                            <option value="<?php echo $driver['userid']; ?>" 
                                <?php echo $selected_driver == $driver['userid'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($driver['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status-filter">Status:</label>
                    <select id="status-filter" name="status" onchange="updateFilters()">
                        <option value="all">All Statuses</option>
                        <option value="active" <?php echo $selected_status == 'active' ? 'selected' : ''; ?>>Accepted & Approved</option>
                        <option value="Completed" <?php echo $selected_status == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
            </div>

            <?php if (empty($requests)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                    <p class="text-muted">No requests found matching your criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $request): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div>
                                <span class="request-type type-<?php echo htmlspecialchars($request['request_type']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($request['request_type'])); ?>
                                </span>
                                <span class="ms-2 text-muted">#<?php echo htmlspecialchars($request['id']); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo htmlspecialchars($request['status']); ?>">
                                <?php echo htmlspecialchars($request['status']); ?>
                            </span>
                        </div>

                        <div class="request-details">
                            <div class="detail-group">
                                <div class="detail-label"><i class="fas fa-user"></i> Patient Name</div>
                                <div class="detail-value"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label"><i class="fas fa-map-marker-alt"></i> Location</div>
                                <div class="detail-value"><?php echo htmlspecialchars($request['location']); ?></div>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label"><i class="fas fa-phone"></i> Contact</div>
                                <div class="detail-value"><?php echo htmlspecialchars($request['phone']); ?></div>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label"><i class="fas fa-user-md"></i> Assigned Driver</div>
                                <div class="detail-value"><?php echo htmlspecialchars($request['driver_name'] ?? 'Not Assigned'); ?></div>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label"><i class="fas fa-calendar"></i> Created At</div>
                                <div class="detail-value">
                                    <?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?>
                                </div>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label"><i class="fas fa-money-bill-wave"></i> Amount</div>
                                <div class="detail-value">
                                    <?php echo $request['amount'] ? '₹' . number_format($request['amount'], 2) : 'Not Set'; ?>
                                </div>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label"><i class="fas fa-check-circle"></i> Payment Status</div>
                                <div class="detail-value">
                                    <span class="payment-status payment-<?php echo htmlspecialchars($request['payment_status'] ?? 'Pending'); ?>">
                                        <?php echo htmlspecialchars($request['payment_status'] ?? 'Pending'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateFilters() {
            const driverFilter = document.getElementById('driver-filter').value;
            const statusFilter = document.getElementById('status-filter').value;
            const typeFilter = document.getElementById('type-filter').value;
            
            let url = new URL(window.location.href);
            url.searchParams.set('driver', driverFilter);
            url.searchParams.set('status', statusFilter);
            url.searchParams.set('type', typeFilter);
            
            window.location.href = url.toString();
        }
    </script>
</body>
</html> 