<?php
include 'connect.php'; 


$paymentType = isset($_GET['payment_type']) ? htmlspecialchars($_GET['payment_type']) : 'emergency'; 
$paymentStatus = isset($_GET['payment_status']) ? htmlspecialchars($_GET['payment_status']) : 'all'; 
$searchQuery = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; // Search query
switch ($paymentType) {
    case 'prebookings':
        $table = 'tbl_prebooking';
        $idColumn = 'prebookingid';
        $statusValue = "'Completed'";
        $patientColumn = "COALESCE(u_req.username, 'Not Specified') AS patient_name";
        $locationColumn = 'pickup_location';
        $driverIdColumn = 'driver_id';
        break;
    case 'palliative':
        $table = 'tbl_palliative';
        $idColumn = 'palliativeid';
        $statusValue = "'Completed'";
        $patientColumn = "medical_condition AS patient_name";
        $locationColumn = 'address';
        $driverIdColumn = 'driver_id';
        break;
    default: // emergency
        $table = 'tbl_emergency';
        $idColumn = 'request_id';
        $statusValue = "'Completed'";
        $patientColumn = "patient_name";
        $locationColumn = 'pickup_location';
        $driverIdColumn = 'driver_id';
        break;
}

$sql = "SELECT 
            p.$idColumn AS id,
            p.userid,
            $patientColumn,
            p.$locationColumn AS location,
            p.amount,
            p.payment_status,
            p.created_at,
            p.ambulance_type as request_ambulance_type,
            d.driver_id,
            d.vehicle_no,
            d.ambulance_type as driver_ambulance_type,
            d.service_area,
            u_drv.username AS driver_name,
            u_drv.phoneno AS driver_phone,
            u_drv.userid AS driver_userid,
            u_req.username AS requester_name,
            u_req.userid AS requester_userid
        FROM $table p
        LEFT JOIN tbl_user u_req ON p.userid = u_req.userid
        LEFT JOIN tbl_driver d ON p.$driverIdColumn = d.driver_id
        LEFT JOIN tbl_user u_drv ON d.userid = u_drv.userid
        WHERE p.status = $statusValue 
        AND p.amount > 0";

$params = [];
$paramTypes = "";
if ($paymentStatus !== 'all') {
    $sql .= " AND p.payment_status = ?";
    $params[] = $paymentStatus;
    $paramTypes .= "s";
}
if (!empty($searchQuery)) {
    $sql .= " AND (p.$idColumn LIKE ? OR u_drv.username LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $paramTypes .= "ss";
}

// Order by creation date (newest first)
$sql .= " ORDER BY p.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($paramTypes, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
    }
    $stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}

$conn->close();
function isSelected($current, $check) {
    return $current === $check ? 'selected' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Payment Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- <link href="assets/css/main.css" rel="stylesheet"> -->
    <style>
        :root {
            --primary-color: #4a6fdc;
            --secondary-color:rgb(234, 235, 236);
            --accent-color: #5cb85c;
            --warning-color: #f0ad4e;
            --danger-color: #d9534f;
            --text-color: #333;
            --light-text: #6c757d;
            --border-color: #dee2e6;
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('assets/assets/img/template/Groovin/hero-carousel/ambulance2.jpg') no-repeat center center/cover;
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        .container {
            position: relative;
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background:  rgba(249, 245, 245, 0.86);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
        }
        
        .filters {
            background-color: var(--secondary-color);
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        table th {
            background-color: var(--secondary-color);
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-paid {
            background-color: rgba(92, 184, 92, 0.2);
            color: #2e7d32;
        }
        
        .status-pending {
            background-color: rgba(240, 173, 78, 0.2);
            color: #f57c00;
        }
        
        .user-info, .vehicle-info, .date-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .username {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .user-id, .phone, .ambulance-type {
            font-size: 0.85rem;
            color: var(--light-text);
        }
        
        .vehicle-no {
            font-weight: 500;
        }
        
        .amount {
            font-weight: 600;
            color: var(--accent-color);
        }
        
        .date-info .time {
            font-size: 0.85rem;
            color: var(--light-text);
        }
        
        .vehicle-info .ambulance-type {
            text-transform: capitalize;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 0;
            color: var(--light-text);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--border-color);
        }
        
        .not-assigned {
            color: #999;
            font-style: italic;
            font-size: 0.9rem;
        }
        
        .user-info .username {
            color: #333;
            font-weight: 600;
        }
        
        .user-info .user-id {
            color: #666;
            font-size: 0.85rem;
        }
        
        .vehicle-info {
            color: #444;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 15px;
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header h1 {
                margin-bottom: 10px;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: #fff;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(74, 111, 220, 0.9);
            border-radius: 50%;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .back-button:hover {
            transform: translateX(-5px);
            background: var(--accent-color);
        }

        .service-area {
            font-size: 0.85rem;
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .phone {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .vehicle-no {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        .ambulance-type {
            color: var(--primary-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .user-info i, .vehicle-info i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .id-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .request-id {
            font-weight: 600;
            color: var(--primary-color);
        }

        .user-id {
            font-size: 0.85rem;
            color: var(--light-text);
        }

        .username small {
            color: var(--light-text);
            font-size: 0.85rem;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <a href="admin.php" class="back-button">
        <i class="fas fa-chevron-left"></i>
    </a>
    
    <div class="container">
        <div class="header">
            <h1>Payment Details</h1>
            <div>
                <span>Total: <?= count($payments) ?> payments</span>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="filters">
            <form method="GET" action="" class="filter-form" id="filterForm">
                <div class="form-group">
                    <label for="payment-type">Payment Type:</label>
                    <select id="payment-type" name="payment_type" onchange="this.form.submit()">
                        <option value="emergency" <?= isSelected($paymentType, 'emergency') ?>>Emergency</option>
                        <option value="prebookings" <?= isSelected($paymentType, 'prebookings') ?>>Prebookings</option>
                        <option value="palliative" <?= isSelected($paymentType, 'palliative') ?>>Palliative</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="payment-status">Payment Status:</label>
                    <select id="payment-status" name="payment_status" onchange="this.form.submit()">
                        <option value="all" <?= isSelected($paymentStatus, 'all') ?>>All</option>
                        <option value="Paid" <?= isSelected($paymentStatus, 'Paid') ?>>Paid</option>
                        <option value="Pending" <?= isSelected($paymentStatus, 'Pending') ?>>Pending</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" 
                           placeholder="Search by ID or username..." 
                           value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
            </form>
        </div>

        <!-- Payment Details Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID/User ID</th>
                        <th>Patient/Service</th>
                        <th>Location</th>
                        <th>Driver Details</th>
                        <th>Vehicle Info</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i class="fas fa-search"></i>
                                <p>No payments found matching your criteria.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <div class="id-info">
                                        <span class="request-id">Request #<?= htmlspecialchars($payment['id']) ?></span>
                                        <span class="user-id">User ID: <?= htmlspecialchars($payment['userid']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($payment['patient_name']) ?></td>
                                <td><?= htmlspecialchars($payment['location']) ?></td>
                                <td>
                                    <div class="user-info">
                                        <?php if ($payment['driver_name'] && $payment['driver_userid']): ?>
                                            <span class="username">
                                                <i class="fas fa-user"></i>
                                                <?= htmlspecialchars($payment['driver_name']) ?>
                                                <small>(ID: <?= htmlspecialchars($payment['driver_userid']) ?>)</small>
                                            </span>
                                            <?php if ($payment['driver_phone']): ?>
                                                <span class="phone">
                                                    <i class="fas fa-phone"></i> 
                                                    <?= htmlspecialchars($payment['driver_phone']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($payment['service_area']): ?>
                                                <span class="service-area">
                                                    <i class="fas fa-map-marker-alt"></i> 
                                                    <?= htmlspecialchars($payment['service_area']) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="not-assigned">Driver Not Assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="vehicle-info">
                                        <?php if ($payment['vehicle_no'] && $payment['driver_ambulance_type']): ?>
                                            <span class="vehicle-no">
                                                <i class="fas fa-ambulance"></i> 
                                                <?= htmlspecialchars($payment['vehicle_no']) ?>
                                            </span>
                                            <span class="ambulance-type">
                                                Type: <?= htmlspecialchars($payment['driver_ambulance_type']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="not-assigned">Vehicle Not Assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="amount">₹<?= number_format((float)$payment['amount'], 2) ?></span>
                                </td>
                                <td>
                                    <span class="status <?= strtolower($payment['payment_status']) === 'paid' ? 'status-paid' : 'status-pending' ?>">
                                        <?= htmlspecialchars($payment['payment_status'] ?: 'Pending') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <span class="created"><?= date('M d, Y', strtotime($payment['created_at'])) ?></span>
                                        <span class="time"><?= date('h:i A', strtotime($payment['created_at'])) ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Auto-submit form when search input changes (after a short delay)
        const searchInput = document.getElementById('search');
        let timeout = null;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                document.getElementById('filterForm').submit();
            }, 500); // Wait 500ms after typing stops
        });

        // Handle search field clearing
        searchInput.addEventListener('search', function() {
            if (this.value === '') {
                document.getElementById('filterForm').submit();
            }
        });
    </script>
</body>
</html>