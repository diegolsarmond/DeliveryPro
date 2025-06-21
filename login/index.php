<?php
session_start();
include '../database/db.php';

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function getSecretKey(mysqli $conn): string {
    $result = $conn->query("SELECT chave_secreta FROM license_codes WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    if ($result && ($row = $result->fetch_assoc()) && !empty($row['chave_secreta'])) {
        return $row['chave_secreta'];
    }
    return 'packtypebot';
}

function generateJwt(int $userId, string $username, string $secret): string {
    $header  = base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64UrlEncode(json_encode([
        'sub'      => $userId,
        'username' => $username,
        'iat'      => time(),
        'exp'      => time() + 3600
    ]));
    $signature = base64UrlEncode(hash_hmac('sha256', "$header.$payload", $secret, true));
    return "$header.$payload.$signature";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $username = $_POST['username'];
    $password = $_POST['password'];

   

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $secret = getSecretKey($conn);
            $token  = generateJwt($row['id'], $username, $secret);
            echo json_encode(['success' => true, 'token' => $token]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Senha incorreta']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
    }
    exit();
}
$config = json_decode(file_get_contents('../customizacao.json'), true);



?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['site_title'] ?? 'Delivery PRO'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($config['favicon_url'] ?? ''); ?>">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/login.css" rel="stylesheet">
    <style>
    :root {
        --primary-color: <?php echo $config['primary_color']; ?>;
        --primary-hover: <?php echo $config['primary_hover_color']; ?>;
    }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="logo-container">
                <img src="<?php echo $config['login_logo_url']; ?>" alt="Logo" class="logo">
            </div>
            <form id="loginForm" method="POST">
                <div class="form-group">
                    <label for="username">Usuário</label>
                    <div class="input-group">
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="input-group">
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input type="password" id="password" name="password" class="form-control" required>
                        <div class="input-icon password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="custom-btn">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </button>
                </div>
                <div class="register-link">
                    Não tem uma conta? <a href="registrar.php">Registrar-se</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../modal/login.js"></script>
</body>
</html>
