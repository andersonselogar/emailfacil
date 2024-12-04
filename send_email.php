# emailfacil

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Defina o token para autenticação
define('API_TOKEN', 'd4f0c8e0-1234-4567-890a-bcdef1234567');

// Verifica se é uma solicitação POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido. Use POST.']);
    exit;
}

// Valida o token de autenticação
$headers = getallheaders();
if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bearer ' . API_TOKEN) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit;
}

// Processa os dados enviados no corpo da requisição
$data = json_decode(file_get_contents('php://input'), true);

// Validação dos parâmetros
if (!isset($data['to'], $data['subject'], $data['message'], $data['smtp'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parâmetros ausentes ou inválidos.']);
    exit;
}

// Configurações SMTP
$smtp = $data['smtp'];
$toList = $data['to'];

// Verifica se `to` é um único e-mail ou uma lista
if (is_string($toList)) {
    $toList = [$toList]; // Converte para array se for string
} elseif (!is_array($toList) || empty($toList)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Lista de destinatários inválida.']);
    exit;
}

$mail = new PHPMailer(true);

try {
    // Configuração do servidor SMTP
    $mail->isSMTP();
    $mail->Host = $smtp['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp['username'];
    $mail->Password = $smtp['password'];
    $mail->Port = $smtp['port'];
    $mail->SMTPSecure = ($smtp['encryption'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

    $mail->setFrom($smtp['username'], 'Gerbox');

    // Adiciona todos os destinatários
    foreach ($toList as $to) {
        $mail->addAddress($to);
    }

    // Configuração do e-mail
    $mail->isHTML(true);
    $mail->Subject = htmlspecialchars(strip_tags($data['subject']));
    $mail->Body = nl2br(htmlspecialchars($data['message']));

    $mail->send();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'E-mail enviado com sucesso.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "Erro ao enviar mensagem: {$mail->ErrorInfo}"]);
}
