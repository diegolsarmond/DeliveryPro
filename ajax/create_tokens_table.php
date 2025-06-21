<?php
require_once '../database/db.php';

$sql = "CREATE TABLE IF NOT EXISTS user_tokens (
    user_id INT(11) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    PRIMARY KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Tabela user_tokens criada com sucesso ou já existente";
} else {
    echo "Erro ao criar tabela: " . $conn->error;
}
