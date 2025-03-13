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
        $patientColumn = "CONCAT('Patient') AS patient_name"; 
        $driverIdColumn = 'driver_id';
        break;
    case 'palliative':
        $table = 'tbl_palliative';
        $idColumn = 'palliativeid';
        $statusValue = "'Completed'"; 
       
        $patientColumn = "CONCAT('Palliative Care') AS patient_name";
        $driverIdColumn = 'driver_id';
        break;
    default:
        $table = 'tbl_emergency';
        $idColumn = 'request_id';
        $statusValue = "'Completed'"; 
        $patientColumn = 'patient_name';
        $driverIdColumn = 'driver_id';
        break;
}
$sql = "SELECT p.$idColumn AS id, p.userid, 
               u.username, 
               $patientColumn, p.amount, p.payment_status, p.created_at,
               d.driver_id, d.vehicle_no, d.ambulance_type, u2.username AS driver_name
        FROM $table p
        LEFT JOIN tbl_user u ON p.userid = u.userid
        LEFT JOIN tbl_driver d ON p.$driverIdColumn = d.driver_id
        LEFT JOIN tbl_user u2 ON d.userid = u2.userid
        WHERE p.status = $statusValue AND p.amount > 0";

$params = [];
$paramTypes = "";
if ($paymentStatus !== 'all') {
    $sql .= " AND p.payment_status = ?";
    $params[] = $paymentStatus;
    $paramTypes .= "s";
}
if (!empty($searchQuery)) {
    $sql .= " AND (p.$idColumn LIKE ? OR u.username LIKE ?)";
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
            --secondary-color: #f8f9fa;
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background:  rgba(252, 250, 250, 0.86);
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
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .username {
            font-weight: 500;
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
    </style>
</head>
<body>
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
                        <th>ID</th>
                        
                        <th>Patient</th>
                        <th>Driver</th>
                        <th>Vehicle</th>
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
                                <td><?= htmlspecialchars($payment['id']) ?></td>
                                <td><?= htmlspecialchars($payment['patient_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($payment['driver_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($payment['vehicle_no'] ?? 'N/A') ?></td>
                                <td>$<?= number_format((float)$payment['amount'], 2) ?></td>
                                <td>
                                    <span class="status <?= strtolower($payment['payment_status']) === 'paid' ? 'status-paid' : 'status-pending' ?>">
                                        <?= htmlspecialchars($payment['payment_status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($payment['created_at'])) ?></td>
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