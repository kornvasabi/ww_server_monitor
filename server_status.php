<?php
require_once 'config.php';

// ตรวจสอบว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
        
        // ถ้าไม่มี protocol ให้เพิ่ม http://
        if (empty($parsed['scheme'])) {
            $address = 'http://' . $address;
        }
    }
    
    // ใช้ fsockopen เพื่อตรวจสอบ connection
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($fp) {
        fclose($fp);
        return 'Online';
    }
    return 'Offline';
}

// ฟังก์ชันตรวจสอบสถานะแบบละเอียด (ตรวจ HTTP status)
function checkServerStatusDetailed($address, $timeout = 2) {
    if (filter_var($address, FILTER_VALIDATE_IP)) {
        $host = $address;
        $port = 80;
    } else {
        $parsed = parse_url($address);
        $host = $parsed['host'] ?? $address;
        $port = $parsed['port'] ?? 80;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => (filter_var($address, FILTER_VALIDATE_IP) ? 'http://' : '') . $address,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_FAILONERROR => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    
    @curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode >= 200 && $httpCode < 400) ? 'Online' : 'Offline';
}

// ดึงข้อมูลเซิร์ฟเวอร์ทั้งหมด
$query = "SELECT id, name, address FROM servers ORDER BY id desc";
$result = $conn->query($query);
$servers = $result;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถานะเซิร์ฟเวอร์ - Server Monitor</title>
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

        .status-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 0;
            margin-bottom: 20px;
        }

        .status-header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .status-header p {
            font-size: 0.95rem;
            opacity: 0.95;
        }

        /* สถิติ - Responsive */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            line-height: 1;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 8px;
            word-break: break-word;
        }

        /* ตาราง Responsive */
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .server-online {
            background-color: #d4edda;
        }

        .server-offline {
            background-color: #f8d7da;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 6px 10px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
            min-width: 70px;
        }

        .btn-back {
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        /* Mobile View - Card Style */
        @media (max-width: 768px) {
            .status-header {
                padding: 20px 0;
            }

            .status-header h1 {
                font-size: 1.5rem;
            }

            .status-header p {
                font-size: 0.9rem;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-bottom: 20px;
            }

            .stat-card {
                padding: 12px;
            }

            .stat-number {
                font-size: 1.8rem;
            }

            .stat-label {
                font-size: 0.8rem;
                margin-top: 6px;
            }

            /* ซ่อนตาราง แสดง Card */
            .table-container {
                display: none;
            }

            .mobile-card-view {
                display: block;
            }

            .server-card {
                background: white;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 12px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                border-left: 4px solid #667eea;
            }

            .server-card.online {
                border-left-color: #28a745;
                background-color: #f0f9f6;
            }

            .server-card.offline {
                border-left-color: #dc3545;
                background-color: #fef5f5;
            }

            .server-card-name {
                font-weight: bold;
                font-size: 1rem;
                margin-bottom: 8px;
                color: #333;
            }

            .server-card-address {
                font-size: 0.85rem;
                color: #6c757d;
                margin-bottom: 8px;
                word-break: break-all;
                font-family: monospace;
            }

            .server-card-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.8rem;
            }

            .server-card-time {
                color: #999;
            }

            table {
                font-size: 0.9rem;
            }

            table thead {
                display: none;
            }

            table tbody tr {
                display: block;
                margin-bottom: 12px;
                border-radius: 8px;
            }

            table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 10px !important;
                border: none;
                border-bottom: 1px solid #e9ecef;
            }

            table tbody td:last-child {
                border-bottom: none;
            }

            table tbody td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #667eea;
                min-width: 100px;
            }
        }

        /* Tablet View */
        @media (min-width: 769px) and (max-width: 1024px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .mobile-card-view {
                display: none;
            }
        }

        /* Desktop View */
        @media (min-width: 1025px) {
            .mobile-card-view {
                display: none;
            }
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        .container-fluid {
            padding-left: 12px;
            padding-right: 12px;
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }
        }

        .search-container {
            background-color: #f9f9f9;
            border-bottom: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .search-container input {
            border-radius: 20px;
            padding: 10px 15px;
            border: 2px solid #ddd;
            transition: border-color 0.3s;
        }

        .search-container input:focus {
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.25);
            outline: none;
        }

        .search-info {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
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
            <a class="navbar-brand" href="dashboard.php">🖥️ Server Monitor</a>
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

    <div class="status-header">
        <div class="container-fluid">
            <h1 class="mb-2">
                <i class="bi bi-server"></i> สถานะเซิร์ฟเวอร์
            </h1>
            <p class="mb-0">ตรวจสอบสถานะของเซิร์ฟเวอร์ทั้งหมด</p>
        </div>
    </div>

    <div class="container-fluid px-md-4">
        <a href="dashboard.php" class="btn btn-secondary btn-back btn-sm">
            <i class="bi bi-arrow-left"></i> กลับไปแดชบอร์ด
        </a>

        <!-- สถิติ -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number" id="total-servers">0</div>
                <div class="stat-label">ทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success" id="online-servers">0</div>
                <div class="stat-label">ออนไลน์</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger" id="offline-servers">0</div>
                <div class="stat-label">ออฟไลน์</div>
            </div>
            <div class="stat-card">
                <button class="btn btn-sm btn-primary w-100" id="refresh-btn" onclick="refreshStatus()">
                    <i class="bi bi-arrow-clockwise"></i> รีเฟรช
                </button>
                <div class="stat-label mt-2" style="font-size: 0.75rem;">
                    <span id="last-update">-</span>
                </div>
            </div>
        </div>

        <!-- Search Box -->
        <div class="search-container">
            <input type="text" id="search-input" class="form-control" placeholder="🔍 ค้นหาเซิร์ฟเวอร์ (ชื่อ, ที่อยู่, สถานะ)...">
            <div class="search-info">
                ผลการค้นหา: <span id="search-count">ทั้งหมด</span>
            </div>
        </div>

        <!-- ตาราง (Desktop) -->
        <div class="table-container d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 5%">ลำดับ</th>
                            <th style="width: 30%">ชื่อเซิร์ฟเวอร์</th>
                            <th style="width: 40%">ที่อยู่</th>
                            <th style="width: 15%" class="text-center">สถานะ</th>
                            <th style="width: 10%" class="text-center">ตรวจสอบเมื่อ</th>
                        </tr>
                    </thead>
                    <tbody id="servers-table-body">
                        <?php 
                        if ($servers->num_rows > 0) {
                            $counter = 1;
                            while ($server = $servers->fetch_assoc()):
                                $status = checkServerStatus($server['address']);
                                $row_class = ($status == 'Online') ? 'server-online' : 'server-offline';
                                $badge_class = ($status == 'Online') ? 'bg-success' : 'bg-danger';
                        ?>
                            <tr class="<?php echo $row_class; ?>" id="server-row-<?php echo $server['id']; ?>">
                                <td><?php echo $counter; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($server['name']); ?></strong>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($server['address']); ?></code>
                                </td>
                                <td class="text-center">
                                    <span id="status-badge-<?php echo $server['id']; ?>" class="badge status-badge <?php echo $badge_class; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <small id="check-time-<?php echo $server['id']; ?>">
                                        <?php echo date('H:i:s'); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php 
                            $counter++;
                            endwhile;
                        } else {
                            echo '<tr><td colspan="5" class="text-center py-4">ไม่พบเซิร์ฟเวอร์ใดๆ</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card View (Mobile) -->
        <div class="mobile-card-view d-md-none" id="mobile-servers">
            <?php 
            if ($servers->num_rows > 0) {
                $servers->data_seek(0);
                while ($server = $servers->fetch_assoc()):
                    $status = checkServerStatus($server['address']);
                    $card_class = ($status == 'Online') ? 'online' : 'offline';
            ?>
                <div class="server-card <?php echo $card_class; ?>" id="mobile-card-<?php echo $server['id']; ?>">
                    <div class="server-card-name">
                        <i class="bi bi-circle-fill" style="font-size: 0.6rem; color: <?php echo ($status == 'Online' ? '#28a745' : '#dc3545'); ?>"></i>
                        <?php echo htmlspecialchars($server['name']); ?>
                    </div>
                    <div class="server-card-address">
                        <?php echo htmlspecialchars($server['address']); ?>
                    </div>
                    <div class="server-card-footer">
                        <span class="badge status-badge" id="mobile-status-<?php echo $server['id']; ?>" 
                              style="background-color: <?php echo ($status == 'Online' ? '#28a745' : '#dc3545'); ?>">
                            <?php echo $status; ?>
                        </span>
                        <span class="server-card-time" id="mobile-time-<?php echo $server['id']; ?>">
                            <?php echo date('H:i:s'); ?>
                        </span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ฟังก์ชันค้นหา
        function filterServers() {
            const searchInput = document.getElementById('search-input').value.toLowerCase();
            let visibleTableCount = 0;
            let visibleMobileCount = 0;
            let totalCount = 0;

            // ค้นหา Desktop Table
            const tableRows = document.querySelectorAll('#servers-table-body tr');
            tableRows.forEach(row => {
                // ข้าม empty row message
                if (row.querySelector('td') && row.querySelector('td').colSpan) {
                    return;
                }

                const serverName = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                const serverAddress = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                const serverStatus = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';

                const match = serverName.includes(searchInput) || 
                             serverAddress.includes(searchInput) || 
                             serverStatus.includes(searchInput);

                if (match) {
                    row.style.display = '';
                    visibleTableCount++;
                } else {
                    row.style.display = 'none';
                }
                totalCount++;
            });

            // ค้นหา Mobile Cards
            const mobileCards = document.querySelectorAll('.server-card');
            mobileCards.forEach(card => {
                const serverName = card.querySelector('.server-card-name')?.textContent.toLowerCase() || '';
                const serverAddress = card.querySelector('.server-card-address')?.textContent.toLowerCase() || '';
                const serverStatus = card.querySelector('.badge')?.textContent.toLowerCase() || '';

                const match = serverName.includes(searchInput) || 
                             serverAddress.includes(searchInput) || 
                             serverStatus.includes(searchInput);

                if (match) {
                    card.style.display = 'block';
                    visibleMobileCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // อัปเดตจำนวนผลการค้นหา
            const visibleTotal = visibleTableCount + visibleMobileCount;
            const searchCount = document.getElementById('search-count');
            
            if (searchInput === '') {
                searchCount.textContent = `ทั้งหมด ${totalCount + mobileCards.length} เซิร์ฟเวอร์`;
            } else {
                searchCount.textContent = `พบ ${visibleTotal} รายการ จากทั้งหมด ${totalCount + mobileCards.length} เซิร์ฟเวอร์`;
            }
        }

        // ฟังก์ชันอัปเดตสถานะ
        async function refreshStatus() {
            const btn = document.getElementById('refresh-btn');
            btn.disabled = true;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลัง...';
            
            try {
                const response = await fetch('check_status.php');
                console.log('Response Status:', response.status);
                console.log('Response OK:', response.ok);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('HTTP Error:', response.status, errorText);
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }
                const response_cst = await fetch('check_servers_telegram.php');
                
                const statuses = await response.json();
                console.log('Data received:', statuses);

                let onlineCount = 0;
                let offlineCount = 0;
                const now = new Date();
                const timeString = now.toLocaleTimeString('th-TH');

                statuses.forEach(server => {
                    // อัปเดต Desktop Table
                    const statusBadge = document.getElementById(`status-badge-${server.id}`);
                    const checkTime = document.getElementById(`check-time-${server.id}`);
                    const row = document.getElementById(`server-row-${server.id}`);

                    // อัปเดต Mobile Card
                    const mobileCard = document.getElementById(`mobile-card-${server.id}`);
                    const mobileStatus = document.getElementById(`mobile-status-${server.id}`);
                    const mobileTime = document.getElementById(`mobile-time-${server.id}`);

                    if (statusBadge && row) {
                        statusBadge.textContent = server.status;
                        if (server.status === 'Online') {
                            statusBadge.classList.remove('bg-danger');
                            statusBadge.classList.add('bg-success');
                            row.classList.remove('server-offline');
                            row.classList.add('server-online');
                            onlineCount++;
                        } else {
                            statusBadge.classList.remove('bg-success');
                            statusBadge.classList.add('bg-danger');
                            row.classList.remove('server-online');
                            row.classList.add('server-offline');
                            offlineCount++;
                        }
                        checkTime.textContent = timeString;
                    }

                    if (mobileCard && mobileStatus) {
                        const statusColor = server.status === 'Online' ? '#28a745' : '#dc3545';
                        const cardClass = server.status === 'Online' ? 'online' : 'offline';
                        
                        mobileCard.className = 'server-card ' + cardClass;
                        mobileStatus.textContent = server.status;
                        mobileStatus.style.backgroundColor = statusColor;
                        mobileTime.textContent = timeString;
                    }
                });

                // อัปเดตสถิติ
                document.getElementById('online-servers').textContent = onlineCount;
                document.getElementById('offline-servers').textContent = offlineCount;
                document.getElementById('total-servers').textContent = onlineCount + offlineCount;
                document.getElementById('last-update').textContent = timeString;

            } catch (error) {
                console.error('Full Error:', error);
                console.error('Error Message:', error.message);
                alert(`ไม่สามารถอัปเดตสถานะได้\n${error.message}\n\nตรวจสอบ Console (F12) สำหรับรายละเอียด`);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        }

        // โหลดครั้งแรกเมื่อหน้าโหลดเสร็จ
        document.addEventListener('DOMContentLoaded', () => {
            refreshStatus();

            // เพิ่ม Event Listener สำหรับ Search Input
            const searchInput = document.getElementById('search-input');
            searchInput.addEventListener('keyup', filterServers);
            searchInput.addEventListener('change', filterServers);

            // ตั้งให้มีการอัปเดตสถานะทุกๆ 10 วินาที
            setInterval(refreshStatus, 60000); 
        });
    </script>

</body>
</html>