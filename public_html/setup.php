<?php
require_once(__DIR__ . '/../config/config.php');

// Create DB connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// If admin exists, redirect to index
$result = $conn->query("SELECT id FROM admins LIMIT 1");
if ($result->num_rows > 0) {
    header("Location: /index.php");
    exit;
}

// Handle admin account creation
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_setup'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($username && $email && $password) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password_hash);
        if ($stmt->execute()) {
            header('Location: /setup.php?step=2');
            exit;
        } else {
            $error = "Failed to create admin. Error: " . $stmt->error;
        }
    } else {
        $error = "All fields are required.";
    }
}

// Handle app config (SMTP, Azure, etc.)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['app_config'])
) {
    $smtp_host = trim($_POST['smtp_host']);
    $smtp_port = trim($_POST['smtp_port']);
    $smtp_user = trim($_POST['smtp_user']);
    $smtp_pass = trim($_POST['smtp_pass']);
    $smtp_from = trim($_POST['smtp_from']);
    $keyvault_uri = trim($_POST['keyvault_uri']);
    $client_id = trim($_POST['client_id']);
    $tenant_id = trim($_POST['tenant_id']);
    $cert_path = trim($_POST['cert_path']);
    $key_path = trim($_POST['key_path']);

    $data = [
        'smtp_host' => $smtp_host,
        'smtp_port' => $smtp_port,
        'smtp_user' => $smtp_user,
        'smtp_pass' => $smtp_pass,
        'smtp_from' => $smtp_from,
        'keyvault_uri' => $keyvault_uri,
        'client_id' => $client_id,
        'tenant_id' => $tenant_id,
        'cert_path' => $cert_path,
        'key_path' => $key_path,
    ];
    foreach ($data as $k => $v) {
        $k = $conn->real_escape_string($k);
        $v = $conn->real_escape_string($v);
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value='$v'");
    }
    header('Location: /index.php');
    exit;
}

$step = isset($_GET['step']) ? $_GET['step'] : 1;
?>
<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Setup</title>
</head>
<body>
<?php if ($step == 1): ?>
    <h2>Step 1: Create Admin Account</h2>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post">
        <input type="hidden" name="admin_setup" value="1">
        <label>Admin Username: <input type="text" name="username" required></label><br>
        <label>Admin Email: <input type="email" name="email" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit">Set Up Admin</button>
    </form>
<?php elseif ($step == 2): ?>
    <h2>Step 2: App & Azure/Azure Key Vault/SMTP Settings</h2>
    <form method="post">
        <input type="hidden" name="app_config" value="1">
        <h4>SMTP Settings</h4>
        <label>SMTP Host: <input type="text" name="smtp_host" required></label><br>
        <label>SMTP Port: <input type="text" name="smtp_port" required></label><br>
        <label>SMTP Username: <input type="text" name="smtp_user" required></label><br>
        <label>SMTP Password: <input type="password" name="smtp_pass" required></label><br>
        <label>SMTP From Address: <input type="email" name="smtp_from" required></label><br>
        <h4>Azure/Key Vault</h4>
        <label>Key Vault URI: <input type="text" name="keyvault_uri" required></label><br>
        <label>Azure App (client) ID: <input type="text" name="client_id" required></label><br>
        <label>Azure Tenant ID: <input type="text" name="tenant_id" required></label><br>
        <label>Cert Path: <input type="text" name="cert_path" value="/etc/devgenie/keyvault.crt" required></label><br>
        <label>Key Path: <input type="text" name="key_path" value="/etc/devgenie/keyvault.key" required></label><br>
        <button type="submit">Save Settings</button>
    </form>
<?php endif; ?>
</body>
</html>