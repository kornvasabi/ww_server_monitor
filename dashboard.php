<?php
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ฟังก์ชันตรวจสอบสถานะเซิร์ฟเวอร์
function checkServerStatus($address) {
    // ตรวจสอบว่าเป็น IP หรือ URL
    if (filter_var($address, FILTER_VALIDATE_IP)) {
        // ping IP address
        $output = shell_exec("ping -c 1 " . escapeshellarg($address) . " 2>&1");
        return (strpos($output, 'received') !== false || strpos($output, '1 packets transmitted, 1 received') !== false) ? 'Online' : 'Offline';
    } else {
        // ตรวจสอบ HTTP/HTTPS
        $ch = curl_init($address);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode >= 200 && $httpCode < 400) ? 'Online' : 'Offline';
    }
}

// ดึงข้อมูลเซิร์ฟเวอร์ทั้งหมด
$query = "SELECT id, name, address FROM servers";
$result = $conn->query($query);
$servers = $result;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Server Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">🖥️ Server Monitor</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Add New Server</h3>
            </div>
            <div class="card-body">
                <form action="actions/add_server.php" method="post" class="row g-3">
                    <div class="col-md-4">
                        <label for="name" class="form-label">Server Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g., Main Website" required>
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label">Address (IP or URL)</label>
                        <input type="text" class="form-control" id="address" name="address" placeholder="e.g., 192.168.1.1 or https://example.com" required>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-primary w-100">Add Server</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- เปลี่ยนส่วนนี้ในไฟล์ dashboard.php -->

        <div class="container mt-4">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="mb-0">
                    <i class="bi bi-server"></i> สถานะเซิร์ฟเวอร์
                    <span id="loading-spinner" class="spinner-border spinner-border-sm text-secondary ms-2 d-none" role="status"></span>
                </h2>
                <a href="server_status.php" class="btn btn-info" target="_blank">
                    <i class="bi bi-fullscreen"></i> ดูแบบเต็มหน้า
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ชื่อเซิร์ฟเวอร์</th>
                            <th>ที่อยู่</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($servers->num_rows > 0) {
                            while ($server = $servers->fetch_assoc()): ?>
                                <?php
                                    $status = checkServerStatus($server['address']);
                                    $status_class = ($status == 'Online') ? 'bg-success' : 'bg-danger';
                                ?>
                                <tr id="server-row-<?php echo $server['id']; ?>"> 
                                    <td><?php echo htmlspecialchars($server['name']); ?></td>
                                    <td><?php echo htmlspecialchars($server['address']); ?></td>
                                    <td class="text-center">
                                        <span id="status-badge-<?php echo $server['id']; ?>" class="badge <?php echo $status_class; ?> p-2 server-status">
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
                            echo '<tr><td colspan="4" class="text-center">ไม่พบเซิร์ฟเวอร์ใดๆ</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // ฟังก์ชันสำหรับเรียก API และอัปเดตสถานะ
            async function updateServerStatus() {
                const spinner = document.getElementById('loading-spinner');
                spinner.classList.remove('d-none'); // แสดง spinner

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
                    console.error('Failed to fetch server statuses:', error);
                } finally {
                    spinner.classList.add('d-none'); // ซ่อน spinner
                }
            }

            // เรียกใช้ฟังก์ชันครั้งแรกเมื่อหน้าเว็บโหลดเสร็จ
            document.addEventListener('DOMContentLoaded', () => {
                updateServerStatus();

                // ตั้งให้มีการอัปเดตสถานะทุกๆ 15 วินาที
                setInterval(updateServerStatus, 15000); 
            });
        </script>
    </div>

</body>
</html>