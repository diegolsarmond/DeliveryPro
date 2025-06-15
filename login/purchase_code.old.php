<?php
// File Hash: 3b2b715a96db8fcebdbede459c672dc1

session_start();
require_once('../database/db.php');
require_once('../includes/check_license.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');
ob_start();
$_0c1d0e2e = getConnection();
if (!dateDefaultTimezonePainel()) {
    die("");}
function salvarCodigo($_0c1d0e2e, $_41ef8940) {
    try {
        $_2a2f26da = explode('-', $_41ef8940);
        $_8d777f38 = $_2a2f26da[0];
        $_1a2ed467 = \DateTime::createFromFormat('ymd', $_8d777f38);
        $_a7f62bb2 = clone $_1a2ed467;
        $_a7f62bb2->modify('+30 days');
        $_dc1e72bd = hash('sha256', $_41ef8940 . time());
        $_217ecb18 = $_0c1d0e2e->prepare("UPDATE license_codes SET is_active = 0");
        $_217ecb18->execute();
        $_26988f38 = $_1a2ed467->format('Y-m-d');
        $_5e96055d = $_a7f62bb2->format('Y-m-d');
        $_217ecb18 = $_0c1d0e2e->prepare("INSERT INTO license_codes (code, generated_at, valid_until, is_active, hash_ativacao) VALUES (?, ?, ?, 1, ?)");
        $_217ecb18->bind_param("\x73\x73\x73\x73", $_41ef8940, $_26988f38, $_5e96055d, $_dc1e72bd);
        $_b4a88417 = $_217ecb18->execute();
        if ($_b4a88417) {
            unset($_SESSION['purchase_code_required']);
            return (bool)1;
        }
        return (bool)0;
    } catch (Exception $_e1671797) {
        error_log("\x45\x72\x72\x6f\x20\x61\x6f\x20\x73\x61\x6c\x76\x61\x72\x20\x63\xc3\xb3\x64\x69\x67\x6f\x3a\x20" . $_e1671797->getMessage());
        return (bool)0;
    }
}
if ($_SERVER["\x52\x45\x51\x55\x45\x53\x54\x5f\x4d\x45\x54\x48\x4f\x44"] == "\x50\x4f\x53\x54") {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    try {
        if (empty($_POST['purchase_code'])) {
            echo json_encode(['success' => (bool)0, 'message' => 'Por favor, insira o código de licença']);
            exit;
        }
        $_41ef8940 = trim($_POST['purchase_code']);
        if (validarCodigoLicenca($_41ef8940)) {
            if (salvarCodigo($_0c1d0e2e, $_41ef8940)) {
                echo json_encode([
                    'success' => (bool)1, 
                    'message' => 'Código validado com sucesso!',
                    'redirect' => './index.php'
                ]);
                exit;
            } else {
                echo json_encode(['success' => (bool)0, 'message' => 'Erro ao salvar o código no banco de dados']);
                exit;
            }
        } else {
            echo json_encode(['success' => (bool)0, 'message' => 'Código de licença inválido ou expirado!']);
            exit;
        }
    } catch (Exception $_e1671797) {
        error_log("\x45\x72\x72\x6f\x20\x6e\x6f\x20\x70\x72\x6f\x63\x65\x73\x73\x61\x6d\x65\x6e\x74\x6f\x3a\x20" . $_e1671797->getMessage());
        echo json_encode(['success' => (bool)0, 'message' => 'Erro interno do servidor: ' . $_e1671797->getMessage()]);
        exit;
    }
}
if (!isset($_SESSION['purchase_code_required'])) {
    header("\x4c\x6f\x63\x61\x74\x69\x6f\x6e\x3a\x20\x69\x6e\x64\x65\x78\x2e\x70\x68\x70");
    exit;
}
ob_clean();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Validar Licença - Delivery PRO</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css" rel="stylesheet">
    <link href="../css/login.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="logo-container">
                <img src="https://cdn.jsdelivr.net/gh/mathuzabr/img-packtypebot/logo-deliverypro.png" alt="Logo" class="logo">
            </div>
            <form id="licenseForm">
                <div class="form-group">
                    <label for="purchase_code">Código de Licença</label>
                    <div class="input-group">
                        <div class="input-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <input type="text" id="purchase_code" name="purchase_code" class="form-control" required 
                               placeholder="Formato: YYMMDD-XXXXXXXX-XXXX">
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="custom-btn">
                        <i class="fas fa-check-circle"></i> Validar Licença
                    </button>
                </div>
                <div class="text-center mt-3">
                    <a href="logout.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left"></i> Voltar ao Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        $('#licenseForm').on('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Processando...',
                text: 'Validando seu código de licença',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: window.location.href,
                type: 'POST',
                dataType: 'json',
                data: {
                    purchase_code: $('#purchase_code').val()
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                window.location.href = './index.php';
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: response.message,
                            confirmButtonColor: '#0d524a'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Status:', status);
                    console.error('Erro:', error);
                    console.error('Resposta:', xhr.responseText);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao processar a requisição. Por favor, tente novamente.',
                        confirmButtonColor: '#0d524a'
                    });
                }
            });
        });
    });
    </script>
</body>
</html>
<?php
ob_end_flush();
?> 