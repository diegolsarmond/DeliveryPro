<?php
require_once '../database/db.php';

$sql = "CREATE TABLE IF NOT EXISTS user_permissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    dashboard_access TINYINT(1) DEFAULT 0,
    pedidos_access TINYINT(1) DEFAULT 0,
    movimentacao_access TINYINT(1) DEFAULT 0,
    evolution_access TINYINT(1) DEFAULT 0,
    typebot_access TINYINT(1) DEFAULT 0,
    settings_access TINYINT(1) DEFAULT 0,
    customization_access TINYINT(1) DEFAULT 0,
    stats_access TINYINT(1) DEFAULT 0,
    pos_access TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Tabela user_permissions criada com sucesso ou já existente";
} else {
    echo "Erro ao criar tabela: " . $conn->error;
}
