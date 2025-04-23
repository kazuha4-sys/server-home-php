<?php
include 'config/db.php';
// Caso queria usar uma forma mais bruta siga o tutorial no .txt

// Função para verificar se o IP é de um proxy, VPN ou Tor
function is_vpn_or_tor($ip) {
    $apiKey = "COLOQUE SUA API AQUI";  // Você vai precisar registrar uma chave da API, se necessário
    $url = "http://ipinfo.io/{$ip}/json?token={$apiKey}";  // Substitua pela URL da API

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    // Verifica se há informações sobre VPN ou Tor
    if (isset($data['org']) && (strpos($data['org'], 'VPN') !== false || strpos($data['org'], 'Tor') !== false)) {
        return true;
    }

    return false;
}

// Captura o IP e verifica se é de um VPN ou Tor
$ip = $_SERVER['REMOTE_ADDR'];
if (is_vpn_or_tor($ip)) {
    echo "<script>alert('Acesso bloqueado! VPN ou Tor detectado.');</script>";
    exit();  // Finaliza a execução para bloquear o acesso
}

// Blacklist de IPs conhecidos de proxies/VPNs
$blocked_ips = ['123.45.67.89', '98.76.54.32'];  // Exemplos de IPs bloqueados
if (in_array($ip, $blocked_ips)) {
    echo "<script>alert('Acesso bloqueado! IP na lista negra de proxies/VPNs.');</script>";
    exit();  // Bloqueia o acesso se o IP estiver na blacklist
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';
$pagina = $_SERVER['REQUEST_URI'];

// Log do acesso
$sql_log = "INSERT INTO logs_acess (ip, user_agent, pagina_acessada) VALUES (:ip, :ua, :pagina)";
$stmt_log = $pdo->prepare($sql_log);
$stmt_log->execute([
    ':ip' => $ip,
    ':ua' => $userAgent,
    ':pagina' => $pagina
]);


//include 'config/db.php';

// Upload de Arquivo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $fileName = basename($_FILES["file"]["name"]);
    $fileSize = $_FILES["file"]["size"];
    $dono = "admin"; // Pode ser dinâmico depois

    $uploadPath = "uploads/" . $fileName;
    
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $uploadPath)) {
        $sql = "INSERT INTO arquivos (nome, tamanho, dono) VALUES (:nome, :tamanho, :dono)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $fileName,
            ':tamanho' => $fileSize,
            ':dono' => $dono
        ]);
        echo "<script>alert('Arquivo enviado com sucesso!');</script>";
    } else {
        echo "<script>alert('Erro ao enviar o arquivo.');</script>";
    }
}

// Listar arquivos do banco
$sql = "SELECT * FROM arquivos ORDER BY data_upload DESC";
$stmt = $pdo->query($sql);
$arquivos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servidor de Arquivos</title>
    <style>
        /* Estilo geral aprimorado */
body {
    font-family: 'Fira Code', monospace;
    background-color: #1a1a1a;
    color: #ddd;
    text-align: center;
    margin: 20px;
}

.container {
    max-width: 900px;
    margin: auto;
}


header {
    text-align: right;
}
/* Estilo do terminal */
.terminal {
    background: #121212;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0px 0px 12px rgba(255, 102, 0, 0.8);
    text-align: left;
    border: 1px solid #ff6600;
    overflow: hidden;
}

h1, h2 {
    color: #ff6600;
}

/* Comandos no estilo terminal */
.cmd {
    background: rgba(34, 34, 34, 0.9);
    padding: 12px;
    border-radius: 6px;
    font-size: 1.1em;
    margin-bottom: 12px;
    border-left: 4px solid #ff6600;
}

.user {
    color: #00ff00;
    font-weight: bold;
}

.cmd-text {
    color: #fff;
}

/* Formulário de upload */
form {
    background: #222;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: inline-block;
}

input[type="file"] {
    background: #333;
    color: #fff;
    border: 1px solid #ff6600;
    padding: 8px;
    border-radius: 4px;
}

button {
    background: linear-gradient(135deg, #ff6600, #cc5500);
    color: white;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    border-radius: 6px;
    transition: 0.3s;
}

button:hover {
    background: linear-gradient(135deg, #cc5500, #aa4400);
    transform: scale(1.05);
}

/* Estilo da tabela */
table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
    background: #222;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0px 0px 10px rgba(255, 102, 0, 0.5);
}

th, td {
    padding: 12px;
    border: 1px solid #444;
    text-align: left;
}

th {
    background: #333;
    color: #ff6600;
}

td {
    background: #282828;
}

/* Links */
a {
    color: #00ff00;
    text-decoration: none;
    transition: color 0.3s;
}

a:hover {
    color: #55ff55;
    text-decoration: underline;
}

a.delete {
    color: #ff4444;
    font-weight: bold;
}

a.delete:hover {
    color: #ff2222;
}

/* Responsivo */
@media (max-width: 600px) {
    .terminal {
        max-width: 95%;
        padding: 15px;
    }

    table {
        font-size: 0.9em;
    }

    .cmd {
        font-size: 1em;
    }
}

    </style>
</head>
<body>
    <header>
        <div class="icons">
            <div class="red"></div>
            <div class="blue"></div>
            <div class="yellow"></div>
        </div>
    </header>
    <div class="container">
        <h1>Servidor de Arquivos 📂</h1>

        <form action="" method="post" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit">Enviar Arquivo</button>
        </form>

        <h2>📁 Arquivos Disponíveis</h2>
        <table>
            <tr>
                <th>Nome</th>
                <th>Tamanho</th>
                <th>Data</th>
                <th>Dono</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($arquivos as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row["nome"]); ?></td>
                <td><?php echo round($row["tamanho"] / 1024, 2) . " KB"; ?></td>
                <td><?php echo $row["data_upload"]; ?></td>
                <td><?php echo htmlspecialchars($row["dono"]); ?></td>
                <td>
                    <a href="uploads/<?php echo urlencode($row['nome']); ?>" download>📥 Baixar</a>
                    <a href="delete.php?id=<?php echo $row['id']; ?>" class="delete">❌ Deletar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
