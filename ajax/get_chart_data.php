<?php
session_start();
require_once('../database/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Não autorizado');
}

$user_id = $_SESSION['user_id'];

// Buscar todos os links ativos e suas visualizações
$sql = "SELECT url, views, max_views FROM links 
        WHERE user_id = ? 
        AND views < max_views
        ORDER BY views DESC 
        LIMIT 10"; // Limitando aos 10 links mais visualizados

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$views = [];
$max_views = [];

while ($row = $result->fetch_assoc()) {
    // Adicionar visualizações atuais e máximas
    $labels[] = $row['url'];
    $views[] = (int)$row['views'];
    $max_views[] = (int)$row['max_views'];
}

// Retornar dados em formato JSON
header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'views' => $views,
    'max_views' => $max_views
]); 