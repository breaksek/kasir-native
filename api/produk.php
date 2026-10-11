<?php
require_once '../config/database.php';

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $result = $conn->query("SELECT * FROM produk ORDER BY nama");
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

elseif ($action === 'search') {
    $q = '%' . ($_GET['q'] ?? '') . '%';
    $stmt = $conn->prepare("SELECT * FROM produk WHERE nama LIKE ? OR kode LIKE ? LIMIT 10");
    $stmt->bind_param("ss", $q, $q);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}
?>