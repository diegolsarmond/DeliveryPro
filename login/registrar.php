<?php
session_start();
require_once '../database/db.php';

// Verificar se o registro está permitido
$stmt = $conn->prepare("SELECT value FROM system_settings WHERE setting_name = 'allow_registration'");
$stmt->execute();
$result = $stmt->get_result();
$allowRegistration = $result->num_rows > 0 ? $result->fetch_assoc()['value'] : '1';

// Carregar configurações de customização
$config = json_decode(file_get_contents('../customizacao.json'), true);

if ($allowRegistration !== '1') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registro Desativado - Delivery PRO</title>
        <!-- Bootstrap -->
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <!-- Custom CSS -->
        <link href="../css/register.css" rel="stylesheet">
        <style>
        :root {
            --primary-color: <?php echo $config['primary_color']; ?>;
            --primary-hover: <?php echo $config['primary_hover_color']; ?>;
        }
        .error-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        .error-message {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        .back-button {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
            display: inline-block;
        }
        .back-button:hover {
            background-color: var(--primary-hover);
            color: white;
            text-decoration: none;
        }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="logo-container mb-4">
                <img src="<?php echo $config['login_logo_url']; ?>" alt="Logo" class="logo">
            </div>
            <i class="fas fa-user-lock error-icon"></i>
            <h1 class="error-title">Registro Desativado</h1>
            <p class="error-message">
                O registro de novos usuários está temporariamente desativado. 
                Por favor, entre em contato com o administrador do sistema para mais informações.
            </p>
            <a href="./" class="back-button">
                <i class="fas fa-arrow-left"></i> Voltar para Login
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    
    // Validação dos campos
    if (empty($_POST['username']) || empty($_POST['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Todos os campos são obrigatórios'
        ]);
        exit();
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // Iniciar transação
        $conn->begin_transaction();

        // Verificar se o usuário já existe
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            throw new Exception("Nome de usuário já existe");
        }

        // Inserir o novo usuário
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $password_hash);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Limpar registros antigos se existirem
            $tables = ['evolution_settings', 'typebot_settings', 'user_permissions'];
            foreach ($tables as $table) {
                $stmt = $conn->prepare("DELETE FROM $table WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            }

            // Inserir configurações do Evolution
            $stmt = $conn->prepare("INSERT INTO evolution_settings (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Inserir configurações do Typebot
            $default_settings = json_encode([
                'listening_from_me' => false,
                'stop_bot_from_me' => false,
                'keep_open' => true
            ]);
            
            $stmt = $conn->prepare("INSERT INTO typebot_settings (user_id, settings) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $default_settings);
            $stmt->execute();

            // Inserir permissões padrão
            $stmt = $conn->prepare("INSERT INTO user_permissions (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Commit da transação
            $conn->commit();
            
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Erro ao registrar usuário");
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro no registro: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao registrar usuário: ' . $e->getMessage()
        ]);
    }
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['site_title'] ?? 'Delivery PRO'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($config['favicon_url'] ?? ''); ?>">
    <!-- Bootstrap -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/register.css" rel="stylesheet">
    <?php
    // Carregar configurações de customização
    $config = json_decode(file_get_contents('../customizacao.json'), true);
    ?>
    <style>
    :root {
        --primary-color: <?php echo $config['primary_color']; ?>;
        --primary-hover: <?php echo $config['primary_hover_color']; ?>;
    }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-form">
            <div class="logo-container">
                <img src="<?php echo $config['login_logo_url']; ?>" alt="Logo" class="logo">
            </div>
            <form id="registerForm" method="post">
                <div class="form-group">
                    <label for="username">Nome de Usuário</label>
                    <div class="input-group">
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="input-group">
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input type="password" name="password" id="password" class="form-control" required>
                        <div class="input-icon password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="custom-btn">
                        <i class="fas fa-user-plus"></i> Registrar
                    </button>
                </div>
                <div class="login-link">
                    Já tem uma conta? <a href="./">Fazer Login</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../modal/register.js"></script>
</body>
</html>
