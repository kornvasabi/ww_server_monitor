<?php
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
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
$query = "SELECT id, name, address FROM servers";
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
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">🖥️ Server Monitor</a>
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

    <div class="container mt-4">
        <!-- ฟอร์มเพิ่มเซิร์ฟเวอร์ -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> เพิ่มเซิร์ฟเวอร์ใหม่</h5>
            </div>
            <div class="card-body">
                <form action="actions/add_server.php" method="post" class="row g-3">
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
                </form>
            </div>
        </div>

        <!-- ส่วนสถานะเซิร์ฟเวอร์ -->
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h5 class="mb-0">
                            <i class="bi bi-server"></i> สถานะเซิร์ฟเวอร์
                            <span id="loading-spinner" class="spinner-border spinner-border-sm text-secondary ms-2 d-none" role="status" aria-hidden="true"></span>
                        </h5>
                    </div>
                    <div>
                        <button id="update-btn" class="btn btn-info btn-sm me-2" onclick="updateServerStatus()">
                            <i class="bi bi-arrow-clockwise"></i> อัปเดต
                        </button>
                        <a href="server_status.php" class="btn btn-secondary btn-sm" target="_blank">
                            <i class="bi bi-fullscreen"></i> เต็มหน้า
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ฟังก์ชันสำหรับอัปเดตสถานะเซิร์ฟเวอร์
        async function updateServerStatus() {
            const spinner = document.getElementById('loading-spinner');
            const btn = document.getElementById('update-btn');
            
            // แสดง spinner และปิดใช้งานปุ่ม
            spinner.classList.remove('d-none');
            btn.disabled = true;

            try {
                const response = await fetch('check_status.php');
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const statuses = await response.json();

                // วนลูปข้อมูลสถานะที่ได้รับมา
                statuses.forEach(server => {
                    const statusBadge = document.getElementById(`status-badge-${server.id}`);
                    if (statusBadge) {
                        statusBadge.textContent = server.status;

                        // อัปเดตสีของ badge
                        if (server.status === 'Online') {
                            statusBadge.classList.remove('bg-danger');
                            statusBadge.classList.add('bg-success');
                        } else {
                            statusBadge.classList.remove('bg-success');
                            statusBadge.classList.add('bg-danger');
                        }
                    }
                });

            } catch (error) {
                console.error('เกิดข้อผิดพลาด:', error);
                alert('ไม่สามารถอัปเดตสถานะได้');
            } finally {
                // ซ่อน spinner และเปิดใช้งานปุ่มอีกครั้ง
                spinner.classList.add('d-none');
                btn.disabled = false;
            }
        }

        // เรียกใช้ฟังก์ชันครั้งแรกเมื่อหน้าเว็บโหลดเสร็จ
        document.addEventListener('DOMContentLoaded', () => {
            updateServerStatus();

            // ตั้งให้มีการอัปเดตสถานะทุกๆ 10 วินาที
            setInterval(updateServerStatus, 60000); 
        });
    </script>

</body>
</html>