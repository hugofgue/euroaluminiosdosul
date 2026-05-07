<?php
/**
 * EuroAlumínios do Sul — Formulário de Contacto
 * Ficheiro: enviar_email.php
 *
 * ⚠️  CONFIGURAÇÃO NECESSÁRIA (ver secção CONFIGURAÇÃO abaixo):
 *     1. Altere $destinatario para o email real da empresa
 *     2. Configure o servidor SMTP ou use PHPMailer (ver comentários)
 *     3. Coloque este ficheiro na raiz do site (ao lado do index.html)
 *     4. O servidor precisa de PHP 7.4+ e função mail() ativa
 */

/* ============================================================
   CONFIGURAÇÃO — altere estes valores
   ============================================================ */
define('EMAIL_DESTINATARIO', 'euroaluminiosdosul@gmail.com'); // Email real da empresa
define('EMAIL_REMETENTE',    'noreply@euroaluminiosdosul.pt'); // ⚠️ Substitua pelo domínio do alojamento
define('NOME_EMPRESA',       'EuroAlumínios do Sul');
define('ASSUNTO_PREFIXO',    '[Website] Nova Mensagem: ');

// Ativar modo debug (false em produção)
define('DEBUG_MODE', false);

/* ============================================================
   SEGURANÇA: apenas aceitar POST
   ============================================================ */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

/* ============================================================
   FUNÇÕES AUXILIARES
   ============================================================ */

/**
 * Limpa e sanitiza um campo de texto genérico.
 */
function limpar(string $valor): string {
    return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna JSON de erro e termina.
 */
function erro(string $mensagem, int $httpCode = 400): void {
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => $mensagem]);
    exit;
}

/**
 * Retorna JSON de sucesso e termina.
 */
function sucesso(string $mensagem = 'Mensagem enviada com sucesso!'): void {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => $mensagem]);
    exit;
}

/* ============================================================
   1. RECOLHA E VALIDAÇÃO DOS DADOS
   ============================================================ */
$nome     = limpar($_POST['nome']     ?? '');
$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$telefone = limpar($_POST['telefone'] ?? '');
$assunto  = limpar($_POST['assunto']  ?? '');
$mensagem = limpar($_POST['mensagem'] ?? '');

// Verificar campos obrigatórios
if (empty($nome)) {
    erro('O campo Nome é obrigatório.');
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    erro('Endereço de email inválido ou em falta.');
}
if (empty($mensagem)) {
    erro('O campo Mensagem é obrigatório.');
}

// Proteção contra tamanho excessivo
if (strlen($nome)     > 100 ||
    strlen($email)    > 150 ||
    strlen($telefone) > 30  ||
    strlen($assunto)  > 200 ||
    strlen($mensagem) > 5000) {
    erro('Um ou mais campos excedem o tamanho máximo permitido.');
}

// Proteção anti-spam básica (honeypot — campo oculto)
// Para usar: adicione <input type="text" name="website" style="display:none"> no formulário HTML
if (!empty($_POST['website'])) {
    // Silenciosamente aceitar para não revelar a proteção
    sucesso();
}

// Rate limiting simples por sessão (evitar spam via formulário)
session_start();
$agora = time();
$ultimoEnvio = $_SESSION['ultimo_envio'] ?? 0;
if (($agora - $ultimoEnvio) < 60) {
    erro('Por favor aguarde um momento antes de enviar outra mensagem.');
}
$_SESSION['ultimo_envio'] = $agora;

/* ============================================================
   2. CONSTRUÇÃO DO EMAIL
   ============================================================ */
$assuntoEmail = ASSUNTO_PREFIXO . ($assunto ?: 'Contacto Geral');

// Corpo em HTML
$corpoHTML = '<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; color: #1a202c; background:#f4f6f9; margin:0; padding:0; }
    .wrapper { max-width:600px; margin:30px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
    .header  { background:#0d1f3c; padding:32px 40px; text-align:center; }
    .header h1 { color:#c8a44a; font-size:1.4rem; margin:0; }
    .header p  { color:rgba(255,255,255,0.6); font-size:0.8rem; margin:6px 0 0; }
    .body    { padding:36px 40px; }
    .field   { margin-bottom:20px; }
    .field label { display:block; font-size:0.72rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:#8898aa; margin-bottom:4px; }
    .field p { margin:0; font-size:0.95rem; color:#1a202c; background:#f4f6f9; padding:10px 14px; border-radius:6px; border-left:3px solid #c8a44a; }
    .footer  { background:#06111f; padding:20px 40px; text-align:center; }
    .footer p { color:#8898aa; font-size:0.75rem; margin:0; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>' . NOME_EMPRESA . '</h1>
    <p>Nova mensagem recebida através do website</p>
  </div>
  <div class="body">
    <div class="field">
      <label>Nome</label>
      <p>' . $nome . '</p>
    </div>
    <div class="field">
      <label>Email</label>
      <p>' . $email . '</p>
    </div>';

if (!empty($telefone)) {
    $corpoHTML .= '
    <div class="field">
      <label>Telefone</label>
      <p>' . $telefone . '</p>
    </div>';
}

if (!empty($assunto)) {
    $corpoHTML .= '
    <div class="field">
      <label>Serviço Pretendido</label>
      <p>' . $assunto . '</p>
    </div>';
}

$corpoHTML .= '
    <div class="field">
      <label>Mensagem</label>
      <p>' . nl2br($mensagem) . '</p>
    </div>
  </div>
  <div class="footer">
    <p>Mensagem enviada em ' . date('d/m/Y \à\s H:i') . ' &bull; ' . NOME_EMPRESA . ' &bull; Website</p>
  </div>
</div>
</body>
</html>';

// Corpo em texto simples (fallback)
$corpoTexto  = "Nova mensagem do website — " . NOME_EMPRESA . "\n";
$corpoTexto .= str_repeat("=", 50) . "\n\n";
$corpoTexto .= "NOME:      $nome\n";
$corpoTexto .= "EMAIL:     $email\n";
if (!empty($telefone)) $corpoTexto .= "TELEFONE:  $telefone\n";
if (!empty($assunto))  $corpoTexto .= "SERVIÇO:   $assunto\n";
$corpoTexto .= "\nMENSAGEM:\n$mensagem\n\n";
$corpoTexto .= str_repeat("=", 50) . "\n";
$corpoTexto .= "Enviado em: " . date('d/m/Y H:i') . "\n";

/* ============================================================
   3. ENVIO DO EMAIL
   ============================================================ */

/*
 * OPÇÃO A — PHP mail() nativo
 *   Funciona na maioria dos servidores de alojamento partilhado.
 *   Para usar: certifique-se que o servidor tem sendmail/postfix configurado.
 *
 * OPÇÃO B — PHPMailer via SMTP (recomendado)
 *   Mais fiável para Gmail, Outlook, SMTP privado.
 *   Instale via Composer: composer require phpmailer/phpmailer
 *   Descomente a secção PHPMailer abaixo e comente a secção mail().
 */

// ---- OPÇÃO A: PHP mail() -----------------------------------------
$boundary = md5(time());
$headers  = implode("\r\n", [
    'MIME-Version: 1.0',
    'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    'From: ' . NOME_EMPRESA . ' <' . EMAIL_REMETENTE . '>',
    'Reply-To: ' . $nome . ' <' . $email . '>',
    'X-Mailer: PHP/' . phpversion(),
    'X-Priority: 1',
]);

$corpo  = "--$boundary\r\n";
$corpo .= "Content-Type: text/plain; charset=UTF-8\r\n";
$corpo .= "Content-Transfer-Encoding: base64\r\n\r\n";
$corpo .= chunk_split(base64_encode($corpoTexto));
$corpo .= "--$boundary\r\n";
$corpo .= "Content-Type: text/html; charset=UTF-8\r\n";
$corpo .= "Content-Transfer-Encoding: base64\r\n\r\n";
$corpo .= chunk_split(base64_encode($corpoHTML));
$corpo .= "--$boundary--";

$enviado = mail(EMAIL_DESTINATARIO, '=?UTF-8?B?' . base64_encode($assuntoEmail) . '?=', $corpo, $headers);

// ---- OPÇÃO B: PHPMailer SMTP (descomente para usar) ---------------
/*
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    // Configurações SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';       // ⚠️ Servidor SMTP
    $mail->SMTPAuth   = true;
    $mail->Username   = 'seuemail@gmail.com';    // ⚠️ Utilizador SMTP
    $mail->Password   = 'sua-senha-de-app';      // ⚠️ Senha de app Gmail
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Remetente e destinatário
    $mail->setFrom(EMAIL_REMETENTE, NOME_EMPRESA);
    $mail->addAddress(EMAIL_DESTINATARIO);
    $mail->addReplyTo($email, $nome);

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = $assuntoEmail;
    $mail->Body    = $corpoHTML;
    $mail->AltBody = $corpoTexto;

    $mail->send();
    $enviado = true;
} catch (Exception $e) {
    $enviado = false;
    if (DEBUG_MODE) error_log('PHPMailer: ' . $e->getMessage());
}
*/

/* ============================================================
   4. RESPOSTA
   ============================================================ */
if ($enviado) {
    sucesso('Mensagem enviada com sucesso! Entraremos em contacto brevemente.');
} else {
    if (DEBUG_MODE) {
        error_log('Falha ao enviar email para: ' . EMAIL_DESTINATARIO);
    }
    // Fallback: redirecionar para página com parâmetro de erro
    // (compatível com modo não-AJAX)
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        erro('Não foi possível enviar a mensagem. Por favor tente mais tarde.', 500);
    } else {
        header('Location: index.html?erro=1');
        exit;
    }
}
