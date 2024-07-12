<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

require_once 'db.php';

// Verificar CSRF token
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ação não autorizada.");
    }

    // Define o nome do arquivo de backup
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.csv';
    $backup_path = __DIR__ . '/backups/' . $backup_file;

    // Certifique-se de que a pasta 'backups' existe
    if (!is_dir(__DIR__ . '/backups')) {
        mkdir(__DIR__ . '/backups', 0755, true);
    }

    // Abre o arquivo para escrita
    $fp = fopen($backup_path, 'w');

    // Verifica se o arquivo foi aberto com sucesso
    if ($fp === false) {
        echo "Erro ao criar o arquivo de backup.";
        exit();
    }

    // Consulta para selecionar todos os dados da tabela 'usuarios'
    $query = "SELECT * FROM usuarios";
    $result = $mysqli->query($query);

    // Verifica se a consulta foi executada com sucesso
    if ($result === false) {
        echo "Erro ao executar a consulta.";
        fclose($fp);
        exit();
    }

    // Obtém os nomes das colunas
    $fields = $result->fetch_fields();
    $headers = [];
    foreach ($fields as $field) {
        $headers[] = $field->name;
    }

    // Escreve os nomes das colunas no arquivo CSV
    fputcsv($fp, $headers);

    // Escreve os dados no arquivo CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($fp, $row);
    }

    // Fecha o arquivo e a conexão com o banco de dados
    fclose($fp);
    $mysqli->close();

    $_SESSION['message'] = "Backup criado com sucesso: <a href='backups/$backup_file'>$backup_file</a>";
    header('Location: criar_backup.php'); // Redireciona para evitar reenvio do formulário
    exit();
}

// Geração do token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Criar Backup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            text-align: center;
            padding-top: 50px;
        }

        .backup-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .backup-container h2 {
            margin-bottom: 20px;
        }

        .backup-container a {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 16px;
        }

        .backup-container a:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="backup-container">
        <h2>Criar Backup</h2>
        <?php if (isset($_SESSION['message'])): ?>
            <p style="color: green;"><?php echo $_SESSION['message']; ?></p>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="submit" value="Criar Backup">
        </form>
        <br>
        <a href="dashboard.php">Voltar ao Dashboard</a>
    </div>
</body>
</html>
