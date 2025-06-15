<?php
require_once "database/db.php";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Função para obter o pedido pelo ID
function getPedidoById($conn, $id) {
    $sql = "SELECT * FROM cliente WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Função para obter dados do estabelecimento
function getEstabelecimento($conn) {
    $sql = "SELECT * FROM estabelecimento ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

$pedido = getPedidoById($conn, $id);
$estabelecimento = getEstabelecimento($conn);

if (!$pedido) {
    die("Pedido não encontrado.");
}

// Converter a data do formato brasileiro
$data_pedido = DateTime::createFromFormat('d/m/Y H:i', $pedido['data']);
if (!$data_pedido) {
    $data_pedido = new DateTime($pedido['data']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pedido #<?php echo str_pad($pedido['pedido'], 6, "0", STR_PAD_LEFT); ?></title>
    <style>
        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            background-color: #fff8dc;
        }
        .cupom {
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        /* Efeito picotado superior */
        .cupom:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(45deg, #f5f5f5 25%, transparent 25%),
                        linear-gradient(-45deg, #f5f5f5 25%, transparent 25%);
            background-size: 20px 20px;
            background-position: 0 -10px;
        }
        /* Efeito picotado inferior */
        .cupom:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(45deg, transparent 75%, #f5f5f5 75%),
                        linear-gradient(-45deg, transparent 75%, #f5f5f5 75%);
            background-size: 20px 20px;
            background-position: 0 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .empresa-info {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .pedido-info {
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .items {
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .total {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed #000;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border: 1px dashed #000;
        }
        .qr-code img {
            width: 150px;
            height: 150px;
            margin: 10px auto;
            display: block;
        }
        .qr-code small {
            display: block;
            margin-top: 5px;
            color: #666;
        }
        @media print {
            body {
                background-color: white;
            }
            .cupom {
                box-shadow: none;
            }
        }
        .linha {
            border-bottom: 2px dashed #000;
            margin: 15px 0;
        }
        .center {
            text-align: center;
        }
        .barcode {
            text-align: center;
            margin: 15px 0;
        }
        .barcode img {
            max-width: 100%;
            height: auto;
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="cupom">
        <!-- Cabeçalho com dados da empresa -->
        <div class="header">
            <?php if ($estabelecimento['logo_url']): ?>
                <img src="<?php echo $estabelecimento['logo_url']; ?>" alt="Logo" class="logo">
            <?php endif; ?>
        </div>
        
        <div class="empresa-info">
            <strong><?php echo $estabelecimento['nome_fantasia']; ?></strong><br>
            <?php echo $estabelecimento['razao_social']; ?><br>
            <?php echo $estabelecimento['tipo_documento']; ?>: <?php echo $estabelecimento['documento']; ?><br>
            <?php echo "{$estabelecimento['endereco']}, {$estabelecimento['numero']}"; ?><br>
            <?php if ($estabelecimento['complemento']) echo $estabelecimento['complemento'] . '<br>'; ?>
            <?php echo "{$estabelecimento['bairro']} - {$estabelecimento['cidade']}/{$estabelecimento['estado']}"; ?><br>
            CEP: <?php echo $estabelecimento['cep']; ?><br>
            Tel: <?php echo $estabelecimento['telefone']; ?><br>
        </div>

        <!-- Informações do pedido -->
        <div class="pedido-info">
            <strong>PEDIDO #<?php echo str_pad($pedido['pedido'], 6, "0", STR_PAD_LEFT); ?></strong><br>
            Data: <?php echo $data_pedido->format('d/m/Y H:i'); ?><br>
            Cliente: <?php echo $pedido['nome']; ?><br>
            Telefone: <?php echo $pedido['telefone']; ?><br>
        </div>

        <!-- Itens do pedido -->
        <div class="items">
            <strong>ITENS DO PEDIDO</strong><br>

            <?php 
            $itens = explode(',', $pedido['itens']);
            foreach ($itens as $item) {
                echo trim($item) . "<br>";
            }
            ?>

        </div>

        <!-- Totais -->
        <div class="total">
            Sub-total: R$ <?php echo number_format(floatval(str_replace(['R$', ' '], '', $pedido['sub_total'])), 2, ',', '.'); ?><br>
            Taxa de entrega: R$ <?php echo number_format(floatval(str_replace(['R$', ' '], '', $pedido['taxa_entrega'])), 2, ',', '.'); ?><br>
            <strong>TOTAL: R$ <?php echo number_format(floatval(str_replace(['R$', ' '], '', $pedido['total'])), 2, ',', '.'); ?></strong><br>
            Forma de pagamento: <?php echo $pedido['pagamento']; ?>
        </div>

        <p class="center">Obrigado pela preferência!</p>
        
        <!-- Código de Barras -->
        <div class="barcode">
            <?php 
            $barcodeValue = str_pad($pedido['pedido'], 8, "0", STR_PAD_LEFT);
            echo "<img src='https://barcode.tec-it.com/barcode.ashx?data={$barcodeValue}&code=Code128&dpi=96' alt='Código de Barras'>";
            ?>
        </div>

        <!-- Rodapé -->
        <div class="footer">
            ================================================<br>
            <?php echo $estabelecimento['nome_fantasia']; ?><br>
            Pedido gerado em: <?php echo date('d/m/Y H:i:s'); ?><br>
            ================================================
        </div>
    </div>
</body>
</html> 