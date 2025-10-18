<?php
require_once '../config.php';

// ตรวจสอบว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// ตรวจสอบว่ามี id ส่งมาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$server_id = intval($_GET['id']);

// ดึงข้อมูลเซิร์ฟเวอร์ที่ต้องการแก้ไข
$stmt = $conn->prepare("SELECT id, name, address FROM servers WHERE id = ?");
$stmt->bind_param("i", $server_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // ไม่พบข้อมูล
    $stmt->close();
    header("Location: ../dashboard.php");
    exit();
}

$server = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขเซิร์ฟเวอร์ - Server Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">🖥️ Server Monitor</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="btn btn-outline-light" href="../logout.php">ออกจากระบบ</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-pencil-square"></i> แก้ไขเซิร์ฟเวอร์
                        </h4>
                    </div>
                    <div class="card-body">
                        <form action="update_server.php" method="post" id="editForm">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($server['id']); ?>">

                            <div class="mb-3">
                                <label for="name" class="form-label">ชื่อเซิร์ฟเวอร์</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($server['name']); ?>" 
                                       placeholder="เช่น เว็บไซต์หลัก" required>
                                <small class="form-text text-muted">ระบุชื่อที่อธิบายได้ชัดเจน</small>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">ที่อยู่ (IP หรือ URL)</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?php echo htmlspecialchars($server['address']); ?>" 
                                       placeholder="เช่น 192.168.1.1 หรือ https://example.com" required>
                                <small class="form-text text-muted">ระบุ IP address หรือ URL ที่สมบูรณ์</small>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> บันทึกการเปลี่ยนแปลง
                                </button>
                                <a href="../dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> ยกเลิก
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ตรวจสอบความถูกต้องของข้อมูลก่อนส่ง
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const address = document.getElementById('address').value.trim();

            if (!name || !address) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return false;
            }
        });
    </script>

</body>
</html>