<?php
require_once(__DIR__ . '/../config/config.php');

function db() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function user_by_id($id) {
    $stmt = db()->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function user_by_dev_email($email) {
    $stmt = db()->prepare("SELECT * FROM users WHERE dev_email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function create_user($display_name, $dev_email, $external_id = null, $is_admin = 0, $is_approver = 0, $is_super_admin = 0, $prod_email = null, $notif_pref = 'dev', $local_password_hash = null) {
    $stmt = db()->prepare(
        "INSERT INTO users (display_name, dev_email, external_id, is_admin, is_approver, is_super_admin, prod_email, notification_email_preference, local_password_hash) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssiiisss", $display_name, $dev_email, $external_id, $is_admin, $is_approver, $is_super_admin, $prod_email, $notif_pref, $local_password_hash);
    $stmt->execute();
    return db()->insert_id;
}

function update_user($id, $fields) {
    $sets = [];
    $types = '';
    $params = [];
    foreach ($fields as $k => $v) {
        $sets[] = "$k=?";
        // type guessing
        if (in_array($k, ['is_admin', 'is_approver', 'is_super_admin'])) $types .= 'i';
        else $types .= 's';
        $params[] = $v;
    }
    $params[] = $id;
    $types .= 'i';
    $sql = "UPDATE users SET ".implode(',', $sets)." WHERE id=?";
    $stmt = db()->prepare($sql);
    $stmt->bind_param($types, ...$params);
    return $stmt->execute();
}

function delete_user($id) {
    $stmt = db()->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function all_users() {
    $res = db()->query("SELECT * FROM users ORDER BY created_at DESC");
    $out = [];
    while ($row = $res->fetch_assoc()) $out[] = $row;
    return $out;
}
?>