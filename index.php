<?php
// File Hash: cf1645e3237927569d2b9587330a28b8
session_start();
include 'database/db.php';



if (!isset($_SESSION['user_id'])) {
    header("Location: login/");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT u.role, up.* 
    FROM users u 
    LEFT JOIN user_permissions up ON u.id = up.user_id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

$_SESSION['role'] = $user_data['role'];
$_SESSION['permissions'] = [
    'dashboard' => $user_data['dashboard_access'],
    'pedidos' => $user_data['pedidos_access'],
    'movimentacao' => $user_data['movimentacao_access'],
    'evolution' => $user_data['evolution_access'],
    'typebot' => $user_data['typebot_access'],
    'settings' => $user_data['settings_access'],
    'customization' => $user_data['customization_access'],
    'stats' => $user_data['stats_access'],
    'pos' => $user_data['pos_access'],
    'chaflow' => $user_data['chaflow_access']
];

$canAccessDashboard = $_SESSION['permissions']['dashboard'];
$canAccessPedidos = $_SESSION['permissions']['pedidos'];
$canAccessMovimentacao = $_SESSION['permissions']['movimentacao'];
$canAccessEvolution = $_SESSION['permissions']['evolution'];
$canAccessTypebot = $_SESSION['permissions']['typebot'];
$canAccessSettings = $_SESSION['permissions']['settings'];
$canAccessCustomization = $_SESSION['permissions']['customization'];
$canAccessStats = $_SESSION['permissions']['stats'];
$canAccessPos = $_SESSION['permissions']['pos'];
$canAccessChaflow = $_SESSION['permissions']['chaflow'];

$firstAvailableTab = 'dashboard';
if (!$canAccessDashboard) {
    if ($canAccessPedidos) $firstAvailableTab = 'pedidos';
    elseif ($canAccessMovimentacao) $firstAvailableTab = 'movimentacao';
    elseif ($canAccessEvolution) $firstAvailableTab = 'evolution';
    elseif ($canAccessTypebot) $firstAvailableTab = 'typebot';
    elseif ($canAccessSettings) $firstAvailableTab = 'settings';
    elseif ($canAccessCustomization) $firstAvailableTab = 'customization';
    elseif ($canAccessStats) $firstAvailableTab = 'stats';
    elseif ($canAccessPos) $firstAvailableTab = 'pos';
    elseif ($canAccessChaflow) $firstAvailableTab = 'chaflow';
}


$config = json_decode(file_get_contents('customizacao.json'), true);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['site_title'] ?? 'Delivery PRO'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($config['favicon_url'] ?? ''); ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- jQuery (deve ser o primeiro) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 com tema personalizado -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css" rel="stylesheet">
    <!-- jsPlumb -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsPlumb/2.15.6/js/jsplumb.js"></script>    
    <!-- Custom CSS -->
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/tooltips.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
    <link href="css/preloader.css" rel="stylesheet">
    <?php include 'includes/apply_customization.php'; ?>
</head>
<body>
    <style>
        /* Esconder conteúdo inicialmente */
        body {
            visibility: hidden;
        }
        body.loaded {
            visibility: visible;
        }
    </style>

    <!-- Pre-loader -->
    <div id="preloader" class="preloader">
        <div class="loader-container">
            <img src="<?php echo $config['dashboard_logo_url']; ?>" alt="Loading..." class="loader-image">
        </div>
    </div>

    <!-- Hamburger Button -->
    <button class="hamburger-btn" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="https://cdn.jsdelivr.net/gh/mathuzabr/img-packtypebot/linkpro-logo-wt.png" alt="Logo">
        </div>
        <div class="sidebar-menu">
            <?php if ($canAccessDashboard): ?>
            <a href="#" class="menu-item active" data-tab="dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <?php endif; ?>

            <?php if ($canAccessPos): ?>
            <a href="#" class="menu-item" data-tab="pos">
                <i class="fas fa-cash-register"></i>
                <span>Venda Balcão</span>
            </a>
            <?php endif; ?> 

            <?php if ($canAccessPos): ?>
            <a href="#" class="menu-item" data-tab="pedidosdelivery">
                <i class="fas fa-cash-register"></i>
                <span>Venda Delivery</span>
            </a>
            <?php endif; ?>  

            <?php if ($canAccessPedidos): ?>
            <a href="#" class="menu-item" data-tab="pedido">
                <i class="fas fa-shopping-cart"></i>
                <span>Pedidos em Andamento</span>
            </a>            
            <?php endif; ?>
    
            <?php if ($canAccessMovimentacao): ?>
            <a href="#" class="menu-item" data-tab="movimentacao">
                <i class="fas fa-truck"></i>
                <span>Enviar Pedido (Delivery)</span>
            </a>
            <?php endif; ?>


            
 

            <?php if ($canAccessStats): ?>
            <a href="#" class="menu-item" data-tab="stats">
                <i class="fas fa-chart-bar"></i>
                <span>Relatórios</span>
            </a>
            <?php endif; ?>    
            
            <?php if ($canAccessSettings): ?>
            <a href="#" class="menu-item" data-tab="settings">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>
            <?php endif; ?>      
            
            <?php if ($canAccessCustomization): ?>
            <a href="#" class="menu-item" data-tab="customization">
                <i class="fas fa-paint-brush"></i>
                <span>Customização</span>
            </a>
            <?php endif; ?>            

            <?php if ($canAccessEvolution): ?>
            <a href="#" class="menu-item" data-tab="evolution">
                <i class="fas fa-robot"></i>
                <span>EvolutionAPI</span>
            </a>
            <?php endif; ?>

            <?php if ($canAccessChaflow): ?>
            <a href="#" class="menu-item" data-tab="chaflow">
                <i class="fas fa-project-diagram"></i>
                <span>Chatflow</span>
            </a>
            <?php endif; ?>

            <a href="#" class="menu-item" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="tabs-container">
           <!-- COMENTAR AQUI DEPOIS 
           <ul class="nav nav-tabs" id="myTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" href="#dashboard-content" role="tab">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pedido-tab" data-bs-toggle="tab" href="#pedido-content" role="tab">Pedidos em Andamento</a>
                </li>                
                <li class="nav-item">
                    <a class="nav-link" id="pedidosdelivery-tab" data-bs-toggle="tab" href="#pedidosdelivery-content" role="tab">Pedidos Delivery</a>
                </li>
                <?php if ($canAccessMovimentacao): ?>
                <li class="nav-item">
                    <a class="nav-link" id="movimentacao-tab" data-bs-toggle="tab" href="#movimentacao-content" role="tab">Enviar Pedido (Delivery)</a>
                </li>
                <?php endif; ?>
                <?php if ($canAccessPos): ?>
                <li class="nav-item">
                    <a class="nav-link" id="pos-tab" data-bs-toggle="tab" href="#pos-content" role="tab">Venda Balcão</a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link" id="stats-tab" data-bs-toggle="tab" href="#stats-content" role="tab">Relatórios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="settings-tab" data-bs-toggle="tab" href="#settings-content" role="tab">Configurações</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="customization-tab" data-bs-toggle="tab" href="#customization-content" role="tab">Customização</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="evolution-tab" data-bs-toggle="tab" href="#evolution-content" role="tab">EvolutionAPI</a>
                </li>
                <?php if ($canAccessChaflow): ?>
                <li class="nav-item">
                    <a class="nav-link" id="chaflow-tab" data-bs-toggle="tab" href="#chaflow-content" role="tab">Chatflow</a>
                </li>
                <?php endif; ?>
            </ul>
            -->
            <div class="tab-content" id="myTabsContent">
                <!-- Dashboard Tab -->
                <div class="tab-pane fade <?php echo $firstAvailableTab === 'dashboard' ? 'show active' : ''; ?>" id="dashboard-content" role="tabpanel">
                    <?php if ($canAccessDashboard): ?>
                    <?php include 'includes/dashboard_content.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-warning m-4">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Você não tem permissão para acessar esta área.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pedidos Tab -->
                <div class="tab-pane fade <?php echo $firstAvailableTab === 'pedidos' ? 'show active' : ''; ?>" id="pedido-content" role="tabpanel">
                    <?php if ($canAccessPedidos): ?>
                    <?php include 'includes/pedido_content.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-warning m-4">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Você não tem permissão para acessar esta área.
                        </div>
                    <?php endif; ?>
                </div>                

                <!-- Pedidos Delivery -->
                <div class="tab-pane fade <?php echo $firstAvailableTab === 'pedidos-delivery' ? 'show active' : ''; ?>" id="pedidosdelivery-content" role="tabpanel">
                   <!-- <?php if ($canAccessPedidos): ?> -->
                    <?php include 'includes/pedido_delivery.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-warning m-4">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Você não tem permissão para acessar esta área.
                        </div>
                    <?php endif; ?>
                </div>        

                <!-- Movimentação Tab -->
                <div class="tab-pane fade <?php echo $firstAvailableTab === 'movimentacao' ? 'show active' : ''; ?>" id="movimentacao-content" role="tabpanel">
                    <?php if ($canAccessMovimentacao): ?>
                    <?php include 'includes/movimentacao_content.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-warning m-4">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Você não tem permissão para acessar esta área.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Stats Tab -->
                <div class="tab-pane fade <?php echo $firstAvailableTab === 'stats' ? 'show active' : ''; ?>" id="stats-content" role="tabpanel">
                    <?php if ($canAccessStats): ?>
                    <?php include 'includes/stats_content.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-warning m-4">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Você não tem permissão para acessar esta área.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Settings Tab -->
                <div class="tab-pane fade <?php echo $firstAvailableTab === 'settings' ? 'show active' : ''; ?>" id="settings-content" role="tabpanel">
                    <?php if ($canAccessSettings): ?>
                    <?php include 'includes/settings_content.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-warning m-4">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Você não tem permissão para acessar esta área.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Customization Tab -->
                <div class="tab-pane fade <?php echo $firstAvailableTab === 'customization' ? 'show active' : ''; ?>" id="customization-content" role="tabpanel">
                    <?php if ($canAccessCustomization): ?>
                    <?php include 'includes/customization_content.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-warning m-4">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Você não tem permissão para acessar esta área.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Evolution Tab -->
                <div class="tab-pane fade <?php echo $firstAvailableTab === 'evolution' ? 'show active' : ''; ?>" id="evolution-content" role="tabpanel">
                    <?php if ($canAccessEvolution): ?>
                    <?php include 'includes/evolution_content.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-warning m-4">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Você não tem permissão para acessar esta área.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Typebot Tab -->
                <div class="tab-pane fade <?php echo $firstAvailableTab === 'typebot' ? 'show active' : ''; ?>" id="typebot-content" role="tabpanel">
                    <?php if ($canAccessTypebot): ?>
                        <?php include 'includes/typebot_content.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-warning m-4">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Você não tem permissão para acessar esta área.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($canAccessPos): ?>
                <div class="tab-pane fade <?php echo $firstAvailableTab === 'pos' ? 'show active' : ''; ?>" id="pos-content" role="tabpanel">
                    <?php include 'includes/pos_content.php'; ?>
                </div>
                <?php endif; ?>

                <?php if (!$canAccessDashboard && !$canAccessPedidos && !$canAccessMovimentacao && 
                          !$canAccessEvolution && !$canAccessTypebot && !$canAccessSettings && 
                          !$canAccessCustomization && !$canAccessStats && !$canAccessPos): ?>
                <div class="alert alert-warning m-4">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Você não tem permissão para acessar nenhuma área do sistema. Entre em contato com o administrador.
                </div>
                <?php endif; ?>

                <!-- Adicionar após a aba Evolution -->
                <?php if ($canAccessChaflow): ?>
                <div class="tab-pane fade" id="chaflow-content" role="tabpanel">
                    <?php if ($canAccessChaflow): ?>
                    <?php include 'includes/chaflow_content.php'; ?>
                    <?php else: ?>
                    <div class="alert alert-warning m-4">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Você não tem permissão para acessar esta área.
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para editar links -->
    <div class="modal fade" id="editLinkModal" tabindex="-1" aria-labelledby="editLinkModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLinkModalLabel">Editar Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editLinkForm" method="post">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_url" class="form-label">URL do Link</label>
                            <input type="text" class="form-control" id="edit_url" name="url" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_max_views" class="form-label">Máximo de Visualizações</label>
                            <input type="number" class="form-control" id="edit_max_views" name="max_views" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>console.log("Loading scripts...");</script>
    <script>
        // Mostrar preloader durante o carregamento inicial
        document.addEventListener('DOMContentLoaded', function() {
            const preloader = document.getElementById("preloader");
            document.body.style.visibility = "visible";
            if (preloader) {
                preloader.style.display = "none";
            }
        });

        // Passar a informação da aba ativa do PHP para o JavaScript
        var activeTab = '<?php echo isset($_SESSION["active_tab"]) ? $_SESSION["active_tab"] : ""; ?>';
        <?php unset($_SESSION["active_tab"]); // Limpar a sessão após usar ?>
    </script>
    <script>console.log("Loading evolution.js...");</script>
    <script src="modal/evolution.js"></script>
    <script>console.log("evolution.js loaded");</script>
    <script src="modal/dashboard.js"></script>
    <script src="modal/notifications.js"></script>
    <script src="modal/typebot.js"></script>
    <script src="modal/evolution_instances.js"></script>
    <script src="modal/evolution_messages.js"></script>
    <script src="modal/chaflow.js"></script>
</body>
</html>