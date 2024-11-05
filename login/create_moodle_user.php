<?php

// Permitir solicitudes CORS
header("Access-Control-Allow-Origin: *"); // Puedes especificar el dominio en lugar de '*'
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once(__DIR__ . '/config.php'); // Ruta al archivo de configuración de Moodle.
require_once($CFG->libdir . '/moodlelib.php');

header('Content-Type: application/json');

// Leer los datos recibidos
$data = json_decode(file_get_contents('php://input'), true);
$ethereumAddress = $data['ethereumAddress'];

// Generar una contraseña por defecto
$defaultPassword = bin2hex(random_bytes(8));

// Crear un nuevo objeto de usuario
$newUser = new stdClass();
$newUser->auth = 'manual'; // Método de autenticación manual
$newUser->confirmed = 1;
$newUser->username = $ethereumAddress; // Usar la dirección Ethereum como nombre de usuario
$newUser->password = $defaultPassword;
$newUser->firstname = 'Ethereum'; // Nombre genérico o personalizado
$newUser->lastname = 'User';
$newUser->email = $ethereumAddress . '@example.com'; // Email de ejemplo basado en la dirección Ethereum
$newUser->city = 'Web3';
$newUser->country = 'XX';

// Comprobar si el usuario ya existe
if ($DB->record_exists('user', ['username' => $ethereumAddress])) {
    echo json_encode(['error' => 'User already exists']);
    exit;
}

// Insertar el usuario en la base de datos
$newUserId = user_create_user($newUser, true);
if ($newUserId) {
    echo json_encode(['success' => true, 'defaultPassword' => $defaultPassword]);
} else {
    echo json_encode(['error' => 'Failed to create user']);
}
?>
