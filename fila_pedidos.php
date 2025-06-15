<?php
session_start();
require_once 'database/db.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header("Location: login/");
    exit();
}

// Carregar configurações
$config = json_decode(file_get_contents('customizacao.json'), true);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fila de Pedidos - POS</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/fila_pedidos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: <?php echo $config['primary_color']; ?>;
            --primary-hover: <?php echo $config['primary_hover_color']; ?>;
            --navbar-color: <?php echo $config['navbar_color']; ?>;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Logo -->
        <div class="logo-container">
            <img src="<?php echo $config['dashboard_logo_url']; ?>" alt="Logo" style="height: <?php echo $config['dashboard_logo_height']; ?>">
        </div>

        <!-- Seção Em Preparo -->
        <div class="section-preparo">
            <h2>Em Preparo</h2>
            <div class="row" id="preparo-grid">
                <!-- Pedidos em preparo serão inseridos aqui -->
            </div>
        </div>

        <!-- Seção Finalizando -->
        <div class="section-finalizando">
            <h2>Finalizando</h2>
            <div class="row" id="finalizando-grid">
                <!-- Pedidos prontos para entrega serão inseridos aqui -->
            </div>
        </div>

        <!-- Seção Finalizados -->
        <div class="section-finalizados">
            <h2>Finalizados</h2>
            <div class="row" id="finalizados-grid">
                <!-- Pedidos entregues serão inseridos aqui -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="modal/fila_pedidos.js"></script>
</body>
</html> 