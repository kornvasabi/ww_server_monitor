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
        body {
            background-color: #f8f9fa;
        }
        .status-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .server-online {
            background-color: #d4edda;
        }
        .server-offline {
            background-color: #f8d7da;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 4px;
        }
        .btn-back {
            margin-bottom: 20px;
        }
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">🖥️ Server Monitor</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light" href="logout.php">ออกจากระบบ</a>
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

    <div class="container-fluid px-4">
        <a href="dashboard.php" class="btn btn-secondary btn-back">
            <i class="bi bi-arrow-left"></i> กลับไปแดชบอร์ด
        </a>

        <!-- สถิติ -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number" id="total-servers">0</div>
                <div class="stat-label">เซิร์ฟเวอร์ทั้งหมด</div>
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
                <div>
                    <button class="btn btn-sm btn-primary" id="refresh-btn" onclick="refreshStatus()">
                        <i class="bi bi-arrow-clockwise"></i> รีเฟรช
                    </button>
                </div>
                <div class="stat-label">อัปเดตล่าสุด: <span id="last-update">-</span></div>
            </div>
        </div>

        <!-- ตาราง -->
        <div class="table-container mb-5">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ฟังก์ชันอัปเดตสถานะ
        async function refreshStatus() {
            const btn = document.getElementById('refresh-btn');
            btn.disabled = true;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังตรวจสอบ...';
            
            try {
                const response = await fetch('check_status.php');
                if (!response.ok) {
                    throw new Error('ข้อผิดพลาดในการดึงข้อมูล');
                }
                const statuses = await response.json();

                let onlineCount = 0;
                let offlineCount = 0;

                statuses.forEach(server => {
                    const statusBadge = document.getElementById(`status-badge-${server.id}`);
                    const checkTime = document.getElementById(`check-time-${server.id}`);
                    const row = document.getElementById(`server-row-${server.id}`);

                    if (statusBadge) {
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

                        // อัปเดตเวลา
                        const now = new Date();
                        checkTime.textContent = now.toLocaleTimeString('th-TH');
                    }
                });

                // อัปเดตสถิติ
                document.getElementById('online-servers').textContent = onlineCount;
                document.getElementById('offline-servers').textContent = offlineCount;
                document.getElementById('total-servers').textContent = onlineCount + offlineCount;

                // อัปเดตเวลาล่าสุด
                const now = new Date();
                document.getElementById('last-update').textContent = now.toLocaleTimeString('th-TH');

            } catch (error) {
                console.error('ข้อผิดพลาด:', error);
                alert('ไม่สามารถอัปเดตสถานะได้');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        }

        // โหลดครั้งแรกเมื่อหน้าโหลดเสร็จ
        document.addEventListener('DOMContentLoaded', () => {
            refreshStatus();

            // อัปเดตทุก 10 วินาที
            setInterval(refreshStatus, 10000);
        });
    </script>

</body>
</html>