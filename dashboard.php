<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่ KORN
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ฟังก์ชันตรวจสอบสถานะเซิร์ฟเวอร์ (เร็ว)
function checkServerStatus($address, $timeout = 2) {
    // ถ้าเป็น IP address
    if (filter_var($address, FILTER_VALIDATE_IP)) {
        $host = $address;
        $port = 80;
    } else {
        // ถ้าเป็น URL/Domain
        $parsed = parse_url($address);
        $host = $parsed['host'] ?? $address;
        $port = $parsed['port'] ?? 80;
    }
    
    // ใช้ fsockopen เพื่อตรวจสอบ connection
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($fp) {
        fclose($fp);
        return 'Online';
    }
    return 'Offline';
}

// ดึงข้อมูลเซิร์ฟเวอร์ทั้งหมด
$query = "SELECT id, name, address, is_active FROM servers";
$result = $conn->query($query);
$servers = $result;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด - Server Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #007bff;
            color: white;
            font-weight: 600;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .badge {
            font-size: 0.9rem;
            padding: 8px 12px;
        }

        .server-status {
            min-width: 80px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        h2 {
            color: #333;
            font-weight: 600;
        }

        /* Mobile Card View */
        .mobile-server-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
            display: none;
        }

        .mobile-server-card.online {
            border-left-color: #28a745;
            background-color: #f0f9f6;
        }

        .mobile-server-card.offline {
            border-left-color: #dc3545;
            background-color: #fef5f5;
        }

        .mobile-card-name {
            font-weight: bold;
            font-size: 1rem;
            margin-bottom: 8px;
            color: #333;
        }

        .mobile-card-address {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 10px;
            word-break: break-all;
            font-family: monospace;
        }

        .mobile-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
        }

        .mobile-card-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.75rem;
            color: white;
        }

        .mobile-card-actions {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }

        .mobile-card-actions a {
            flex: 1;
            padding: 6px 8px;
            font-size: 0.75rem;
            text-align: center;
        }

        /* Desktop Table View */
        .desktop-table {
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mobile-server-card {
                display: block;
            }

            .desktop-table {
                display: none;
            }

            .card {
                margin-bottom: 15px;
            }

            .form-row {
                flex-direction: column;
            }

            .col-md-4,
            .col-md-6,
            .col-md-2 {
                width: 100% !important;
                margin-bottom: 10px;
            }

            .btn-sm {
                width: 100%;
            }

            .container {
                padding-left: 10px;
                padding-right: 10px;
            }

            .card-body {
                padding: 12px;
            }
        }

        @media (min-width: 769px) {
            .mobile-server-card {
                display: none !important;
            }

            .desktop-table {
                display: block;
            }
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        @media (max-width: 576px) {
            .header-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .header-controls button,
            .header-controls a {
                width: 100%;
            }
        }

        .search-container {
            margin-bottom: 15px;
        }

        .search-container input {
            border-radius: 20px;
            padding: 8px 15px;
            border: 2px solid #ddd;
            transition: border-color 0.3s;
        }

        .search-container input:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.25);
        }

        .no-results {
            text-align: center;
            color: #999;
            padding: 30px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">🖥️ Server Monitor</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm" href="logout.php">ออกจากระบบ</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- ฟอร์มเพิ่มเซิร์ฟเวอร์ -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> เพิ่มเซิร์ฟเวอร์ใหม่</h5>
            </div>
            <div class="card-body">
                <form action="actions/add_server.php" method="post">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="name" class="form-label">ชื่อเซิร์ฟเวอร์</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="เช่น เว็บไซต์หลัก" required>
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">ที่อยู่ (IP หรือ URL)</label>
                            <input type="text" class="form-control" id="address" name="address" placeholder="เช่น 192.168.1.1 หรือ https://example.com" required>
                        </div>
                        <div class="col-md-2 align-self-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus"></i> เพิ่ม
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ส่วนสถานะเซิร์ฟเวอร์ -->
        <div class="card">
            <div class="card-header">
                <div class="header-controls">
                    <div>
                        <h5 class="mb-0">
                            <i class="bi bi-server"></i> สถานะเซิร์ฟเวอร์
                            <span id="loading-spinner" class="spinner-border spinner-border-sm text-light ms-2 d-none" role="status" aria-hidden="true"></span>
                        </h5>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button id="update-btn" class="btn btn-warning btn-sm" onclick="updateServerStatus()">
                            <i class="bi bi-arrow-clockwise"></i> อัปเดต
                        </button>
                        <a href="server_status.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-fullscreen"></i> เต็มหน้า
                        </a>
                        <a href="check_server.php" class="btn btn-danger btn-sm">
                            <i class="bi bi-pencil-square"></i> settime_checkserver
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Box -->
            <div class="card-body" style="background-color: #f9f9f9; border-bottom: 1px solid #ddd; padding: 15px;">
                <div class="search-container">
                    <input type="text" id="search-input" class="form-control" placeholder="🔍 ค้นหาเซิร์ฟเวอร์ (ชื่อ, ที่อยู่, สถานะ)...">
                </div>
                <small class="text-muted">
                    ผลการค้นหา: <span id="search-count">ทั้งหมด</span>
                </small>
            </div>
            <div class="card-body p-0">
                <!-- Desktop Table View -->
                <div class="desktop-table">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>ชื่อเซิร์ฟเวอร์</th>
                                    <th>ที่อยู่</th>
                                    <th class="text-center" style="width: 15%">สถานะ</th>
                                    <th class="text-center" style="width: 20%">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="servers-table-body">
                                <?php 
                                if ($servers->num_rows > 0) {
                                    while ($server = $servers->fetch_assoc()): ?>
                                        <?php
                                            $status = checkServerStatus($server['address']);
                                            $status_class = ($status == 'Online') ? 'bg-success' : 'bg-danger';
                                        ?>
                                        <tr id="server-row-<?php echo $server['id']; ?>">
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="switchactiveserver" data-item-id="<?php echo $server['id']; ?>" <?php echo ($server['is_active'] == true) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="flexSwitchCheckCheckedDisabled"></label>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($server['name']); ?></strong>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($server['address']); ?></code>
                                            </td>
                                            <td class="text-center">
                                                <span id="status-badge-<?php echo $server['id']; ?>" class="badge <?php echo $status_class; ?> server-status">
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="actions/edit_server.php?id=<?php echo $server['id']; ?>" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-pencil-square"></i> แก้ไข
                                                </a>
                                                <a href="actions/delete_server.php?id=<?php echo $server['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('ต้องการลบเซิร์ฟเวอร์นี้หรือไม่?')">
                                                    <i class="bi bi-trash-fill"></i> ลบ
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                } else {
                                    echo '<tr><td colspan="4" class="text-center py-4 text-muted">ไม่พบเซิร์ฟเวอร์ใดๆ</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile Card View -->
                <div id="mobile-servers-container" style="padding: 15px;">
                    <?php 
                    if ($servers->num_rows > 0) {
                        $servers->data_seek(0);
                        while ($server = $servers->fetch_assoc()):
                            $status = checkServerStatus($server['address']);
                            $card_class = ($status == 'Online') ? 'online' : 'offline';
                            $status_color = ($status == 'Online') ? '#28a745' : '#dc3545';
                    ?>
                        <div class="mobile-server-card <?php echo $card_class; ?>" id="mobile-card-<?php echo $server['id']; ?>">
                            <div class="mobile-card-name">
                                <i class="bi bi-circle-fill" style="font-size: 0.6rem; color: <?php echo $status_color; ?>"></i>
                                <?php echo htmlspecialchars($server['name']); ?>
                            </div>
                            <div class="mobile-card-address">
                                <?php echo htmlspecialchars($server['address']); ?>
                            </div>
                            <div class="mobile-card-footer">
                                <span class="mobile-card-status" id="mobile-status-<?php echo $server['id']; ?>" 
                                      style="background-color: <?php echo $status_color; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </div>
                            <div class="mobile-card-actions">
                                <a href="actions/edit_server.php?id=<?php echo $server['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil-square"></i> แก้ไข
                                </a>
                                <a href="actions/delete_server.php?id=<?php echo $server['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('ต้องการลบเซิร์ฟเวอร์นี้หรือไม่?')">
                                    <i class="bi bi-trash-fill"></i> ลบ
                                </a>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    } else {
                        echo '<div class="alert alert-info text-center">ไม่พบเซิร์ฟเวอร์ใดๆ</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let allServers = [];

        // ฟังก์ชันค้นหา
        function filterServers() {
            const searchInput = document.getElementById('search-input').value.toLowerCase();
            let visibleCount = 0;

            // ค้นหา Desktop Table
            const tableRows = document.querySelectorAll('#servers-table-body tr');
            tableRows.forEach(row => {
                const serverName = row.querySelector('td:nth-child(1)')?.textContent.toLowerCase() || '';
                const serverAddress = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                const serverStatus = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';

                const match = serverName.includes(searchInput) || 
                             serverAddress.includes(searchInput) || 
                             serverStatus.includes(searchInput);

                if (match) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // ค้นหา Mobile Cards
            const mobileCards = document.querySelectorAll('.mobile-server-card');
            mobileCards.forEach(card => {
                const serverName = card.querySelector('.mobile-card-name')?.textContent.toLowerCase() || '';
                const serverAddress = card.querySelector('.mobile-card-address')?.textContent.toLowerCase() || '';
                const serverStatus = card.querySelector('.mobile-card-status')?.textContent.toLowerCase() || '';

                const match = serverName.includes(searchInput) || 
                             serverAddress.includes(searchInput) || 
                             serverStatus.includes(searchInput);

                if (match) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // อัปเดตจำนวนผลการค้นหา
            const totalServers = document.querySelectorAll('#servers-table-body tr').length + 
                                document.querySelectorAll('.mobile-server-card').length;
            const searchCount = document.getElementById('search-count');
            
            if (searchInput === '') {
                searchCount.textContent = `ทั้งหมด ${totalServers} เซิร์ฟเวอร์`;
            } else {
                searchCount.textContent = `พบ ${visibleCount} รายการ จากทั้งหมด ${totalServers} เซิร์ฟเวอร์`;
            }

            // แสดงข้อความถ้าไม่มีผลการค้นหา
            if (visibleCount === 0 && searchInput !== '') {
                const noResultsMsg = document.getElementById('no-results-msg');
                if (!noResultsMsg) {
                    const msg = document.createElement('div');
                    msg.id = 'no-results-msg';
                    msg.className = 'no-results';
                    msg.innerHTML = '🔍 ไม่พบเซิร์ฟเวอร์ที่ตรงกับการค้นหา';
                    document.getElementById('mobile-servers-container').appendChild(msg);
                }
            } else {
                const noResultsMsg = document.getElementById('no-results-msg');
                if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }
        }

        // ฟังก์ชันสำหรับอัปเดตสถานะเซิร์ฟเวอร์
        async function updateServerStatus() {
            const spinner = document.getElementById('loading-spinner');
            const btn = document.getElementById('update-btn');
            
            spinner.classList.remove('d-none');
            btn.disabled = true;

            try {
                const response = await fetch('check_status.php');
                console.log('Response Status:', response.status);

                const response_cst = await fetch('check_servers_telegram.php');
                
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }
                
                const statuses = await response.json();
                console.log('Data received:', statuses);

                statuses.forEach(server => {
                    // อัปเดต Desktop Table
                    const statusBadge = document.getElementById(`status-badge-${server.id}`);
                    if (statusBadge) {
                        statusBadge.textContent = server.status;
                        if (server.status === 'Online') {
                            statusBadge.classList.remove('bg-danger');
                            statusBadge.classList.add('bg-success');
                        } else {
                            statusBadge.classList.remove('bg-success');
                            statusBadge.classList.add('bg-danger');
                        }
                    }

                    // อัปเดต Mobile Card
                    const mobileCard = document.getElementById(`mobile-card-${server.id}`);
                    const mobileStatus = document.getElementById(`mobile-status-${server.id}`);
                    if (mobileCard && mobileStatus) {
                        const statusColor = server.status === 'Online' ? '#28a745' : '#dc3545';
                        mobileCard.className = 'mobile-server-card ' + (server.status === 'Online' ? 'online' : 'offline');
                        mobileStatus.textContent = server.status;
                        mobileStatus.style.backgroundColor = statusColor;
                    }
                });

            } catch (error) {
                console.error('Full Error:', error);
                alert(`ไม่สามารถอัปเดตสถานะได้\n${error.message}\n\nตรวจสอบ Console (F12) สำหรับรายละเอียด`);
            } finally {
                spinner.classList.add('d-none');
                btn.disabled = false;
            }
        }

        // โหลดครั้งแรกเมื่อหน้าเว็บโหลดเสร็จ
        document.addEventListener('DOMContentLoaded', () => {
            updateServerStatus();

            // เพิ่ม Event Listener สำหรับ Search Input
            const searchInput = document.getElementById('search-input');
            searchInput.addEventListener('keyup', filterServers);
            searchInput.addEventListener('change', filterServers);

            // ตั้งให้มีการอัปเดตสถานะทุกๆ 60 วินาที
            setInterval(updateServerStatus, 6000000); 
        });

        // รอให้หน้าเว็บโหลดเสร็จก่อน
        
        document.addEventListener('DOMContentLoaded', () => {
            const toggleSwitch = document.getElementById('switchactiveserver');
        });
        
    </script>

</body>
</html>