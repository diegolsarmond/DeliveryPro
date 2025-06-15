<?php
// Definir fuso horário para Brasil
date_default_timezone_set('America/Sao_Paulo');

$servername = "localhost";
$username = "deliverypro";
$password = "C@104rm0nd1994";
$dbname = "deliverypro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Configurar charset para UTF-8
$conn->set_charset("utf8mb4");
mysqli_set_charset($conn, "utf8mb4");

// Definir fuso horário do MySQL
$conn->query("SET time_zone = '-03:00'");

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

if (PHP_VERSION_ID < 80000) {
    if (!interface_exists('Stringable')) {
        interface Stringable {
            public function __toString(): string;
        }
    }
    
    if (!interface_exists('ReturnTypeWillChange')) {
        #[Attribute(Attribute::TARGET_METHOD)]
        class ReturnTypeWillChange {
            public function __construct() {}
        }
    }
}





function verificarPermissaoUsuario($conn, $user_id, $permissao = null) {
    // Buscar informações do usuário incluindo permissões
    $sql = "SELECT u.role, up.* 
            FROM users u 
            LEFT JOIN user_permissions up ON u.id = up.user_id 
            WHERE u.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Se não encontrar o usuário, retorna false
    if (!$user) {
        error_log("Usuário não encontrado: " . $user_id);
        return false;
    }
    
    // Se for admin, tem todas as permissões
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // Se não especificou permissão, retorna true se o usuário existe
    if ($permissao === null) {
        return true;
    }
    
    // Verifica a permissão específica
    $permissao_campo = $permissao . '_access';
    if (isset($user[$permissao_campo])) {
        return (bool)$user[$permissao_campo];
    }
    
    error_log("Permissão não encontrada: " . $permissao . " para usuário " . $user_id);
    return false;
} 