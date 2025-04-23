<?php
include 'config/db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Verifica se o arquivo existe no banco
    $stmt = $pdo->prepare("SELECT nome FROM arquivos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $file = $stmt->fetch();

    if ($file) {
        $filePath = "uploads/" . $file['nome'];

        // Deleta o arquivo do servidor
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Deleta do banco de dados
        $stmt = $pdo->prepare("DELETE FROM arquivos WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo "<script>alert('Arquivo deletado com sucesso!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Arquivo não encontrado!'); window.location.href='index.php';</script>";
    }
} else {
    echo "<script>alert('ID inválido!'); window.location.href='index.php';</script>";
}
?>
