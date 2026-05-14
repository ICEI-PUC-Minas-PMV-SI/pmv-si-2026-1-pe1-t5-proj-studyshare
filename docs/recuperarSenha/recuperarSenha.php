<?php
/* ============================================================
   StudyShare — Recuperação de Senha
   Arquivo: recuperar-senha.php
   13/05/2026. Yago: Arquivo único PHP — backend + frontend.
   Sobe em qualquer hospedagem PHP sem instalar nada.
   Basta colocar na pasta do projeto e acessar pelo navegador.
============================================================ */


/* ============================================================
   SECTION 1 — CONFIGURAÇÃO
   13/05/2026. Yago: Preencha as credenciais antes de subir.
   Para o e-mail: qualquer hospedagem PHP já tem mail() pronto.
   Para SMS (Twilio):
     1. Crie conta em https://twilio.com (trial gratuito)
     2. Copie Account SID, Auth Token e número Twilio do dashboard
============================================================ */
define('EMAIL_REMETENTE',  'noreply@studyshare.com');
define('EMAIL_NOME',       'StudyShare');

define('TWILIO_SID',       'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TWILIO_TOKEN',     'sua_auth_token_aqui');
define('TWILIO_FROM',      '+15551234567'); // número Twilio comprado

// 13/05/2026. Yago: Senha provisória expira em 15 minutos
define('SENHA_EXPIRY_MIN', 15);


/* ============================================================
   SECTION 2 — RATE LIMITING (via sessão)
   13/05/2026. Yago: Limita 5 tentativas por sessão/IP em 10 min.
   Evita flood na rota de envio.
============================================================ */
session_start();

function checar_rate_limit(): bool {
    $agora   = time();
    $janela  = 10 * 60; // 10 minutos em segundos
    $max     = 5;

    if (!isset($_SESSION['rl_count'], $_SESSION['rl_inicio'])) {
        $_SESSION['rl_count']  = 0;
        $_SESSION['rl_inicio'] = $agora;
    }

    // 13/05/2026. Yago: Reseta a janela se já passou o tempo
    if ($agora - $_SESSION['rl_inicio'] > $janela) {
        $_SESSION['rl_count']  = 0;
        $_SESSION['rl_inicio'] = $agora;
    }

    $_SESSION['rl_count']++;

    return $_SESSION['rl_count'] <= $max;
}


/* ============================================================
   SECTION 3 — GERADOR DE SENHA PROVISÓRIA
   13/05/2026. Yago: Usa random_int() — criptograficamente seguro.
   Sem caracteres ambíguos (0/O, 1/l/I) para facilitar leitura.
============================================================ */
function gerar_senha_provisoria(int $tamanho = 8): string {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $senha = '';
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $senha;
}


/* ============================================================
   SECTION 4 — ARMAZENAMENTO DA SENHA PROVISÓRIA (sessão)
   13/05/2026. Yago: Armazena o hash SHA-256 com TTL.
   Em produção com múltiplos servidores: trocar por banco de dados.
   A senha em texto puro nunca é guardada — só o hash.
============================================================ */
function salvar_senha_provisoria(string $identificador, string $senha): void {
    $hash = hash('sha256', $senha);
    $_SESSION['senhas_prov'][$identificador] = [
        'hash'      => $hash,
        'expira_em' => time() + (SENHA_EXPIRY_MIN * 60),
    ];
}

// 13/05/2026. Yago: Exposto para uso futuro na tela de login — valida a senha provisória
function verificar_senha_provisoria(string $identificador, string $candidata): bool {
    $dados = $_SESSION['senhas_prov'][$identificador] ?? null;
    if (!$dados || time() > $dados['expira_em']) return false;
    return hash_equals($dados['hash'], hash('sha256', $candidata));
}


/* ============================================================
   SECTION 5 — ENVIO DE E-MAIL (mail() nativo do PHP)
   13/05/2026. Yago: mail() funciona em qualquer hosting com
   sendmail/postfix configurado (Hostinger, Locaweb, KingHost etc.)
   Headers MIME para garantir que o e-mail seja lido como HTML.
============================================================ */
function enviar_email(string $para, string $senha_provisoria): bool {
    $boundary = md5(uniqid((string) time(), true));

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $headers .= 'From: ' . EMAIL_NOME . ' <' . EMAIL_REMETENTE . ">\r\n";
    $headers .= "Reply-To: " . EMAIL_REMETENTE . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $assunto = '=?UTF-8?B?' . base64_encode('Recuperação de senha — StudyShare') . '?=';

    // 13/05/2026. Yago: Versão texto puro — fallback para clientes sem HTML
    $texto = implode("\n", [
        'Recuperação de senha — StudyShare',
        '',
        "Sua senha provisória é: {$senha_provisoria}",
        '',
        'Essa senha expira em ' . SENHA_EXPIRY_MIN . ' minutos.',
        'Acesse o StudyShare, faça login com essa senha e redefina-a em Configurações.',
        '',
        'Se você não solicitou a recuperação, ignore este e-mail.',
    ]);

    // 13/05/2026. Yago: Template HTML com identidade visual do projeto
    $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
</head>
<body style="margin:0;padding:0;background:#F5F7FA;font-family:'Helvetica Neue',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
    <tr>
      <td align="center">
        <table width="520" cellpadding="0" cellspacing="0"
               style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

          <!-- Header -->
          <tr>
            <td style="background:#002F87;padding:28px 32px;">
              <p style="margin:0;font-size:20px;font-weight:700;color:#fff;letter-spacing:-.3px;">StudyShare</p>
              <p style="margin:4px 0 0;font-size:13px;color:rgba(255,255,255,.65);">Recuperação de senha</p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 16px;font-size:14px;color:#475569;line-height:1.6;">
                Recebemos uma solicitação para redefinir sua senha. Use a senha provisória abaixo:
              </p>

              <!-- Caixa da senha -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                <tr>
                  <td align="center"
                      style="background:#E8F0FF;border:1.5px solid #004AAD;border-radius:8px;padding:18px 24px;">
                    <p style="margin:0 0 8px;font-size:11px;font-weight:600;letter-spacing:.7px;text-transform:uppercase;color:#64748B;">
                      Sua senha provisória
                    </p>
                    <p style="margin:0;font-size:28px;font-weight:700;letter-spacing:6px;color:#002F87;font-family:monospace;">
                      {$senha_provisoria}
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:-8px 0 16px;font-size:12px;color:#94A3B8;text-align:center;">
                ⏱ Válida por {SENHA_EXPIRY_MIN} minutos
              </p>

              <p style="margin:0 0 12px;font-size:14px;color:#475569;line-height:1.6;">
                Após o login, acesse <strong>Configurações → Segurança</strong> e redefina sua senha permanente.
              </p>
              <p style="margin:0;font-size:14px;color:#475569;line-height:1.6;">
                Se você não solicitou a recuperação, ignore este e-mail. Sua senha atual continua ativa.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 32px;background:#F5F7FA;border-top:1px solid #E2E8F0;
                       font-size:12px;color:#94A3B8;text-align:center;line-height:1.5;">
              StudyShare · Este é um e-mail automático, não responda.<br/>
              © 2026 StudyShare. Todos os direitos reservados.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    // 13/05/2026. Yago: Substitui a constante dentro da string heredoc
    $html = str_replace('{SENHA_EXPIRY_MIN}', SENHA_EXPIRY_MIN, $html);

    $corpo  = "--{$boundary}\r\n";
    $corpo .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $corpo .= $texto . "\r\n\r\n";
    $corpo .= "--{$boundary}\r\n";
    $corpo .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $corpo .= $html . "\r\n\r\n";
    $corpo .= "--{$boundary}--";

    return mail($para, $assunto, $corpo, $headers);
}


/* ============================================================
   SECTION 6 — ENVIO DE SMS (Twilio via cURL)
   13/05/2026. Yago: Chamada HTTP direta para a API REST do Twilio.
   Sem SDK — só cURL nativo do PHP.
   Número convertido para E.164 (+55 + DDD + número).
============================================================ */
function enviar_sms(string $telefone_raw, string $senha_provisoria): bool {
    // 13/05/2026. Yago: Remove máscara e monta E.164 para Brasil
    $digits = preg_replace('/\D/', '', $telefone_raw);
    $e164   = '+55' . $digits;

    $mensagem = "StudyShare: sua senha provisória é {$senha_provisoria}. "
              . 'Válida por ' . SENHA_EXPIRY_MIN . ' min. Não compartilhe.';

    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_SID . '/Messages.json';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => TWILIO_SID . ':' . TWILIO_TOKEN,
        CURLOPT_POSTFIELDS     => http_build_query([
            'From' => TWILIO_FROM,
            'To'   => $e164,
            'Body' => $mensagem,
        ]),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $resposta   = curl_exec($ch);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log('[StudyShare SMS] cURL erro: ' . $curl_error);
        return false;
    }

    // 13/05/2026. Yago: Twilio retorna 201 Created em sucesso
    if ($http_code !== 201) {
        $dados = json_decode($resposta, true);
        error_log('[StudyShare SMS] Twilio erro: ' . ($dados['message'] ?? $resposta));
        return false;
    }

    return true;
}


/* ============================================================
   SECTION 7 — VALIDADORES
   13/05/2026. Yago: Validação server-side — nunca confiar só no JS
============================================================ */
function validar_email(string $email): bool {
    return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validar_telefone(string $tel): bool {
    $digits = preg_replace('/\D/', '', $tel);
    return strlen($digits) >= 10 && strlen($digits) <= 11;
}


/* ============================================================
   SECTION 8 — ROTEAMENTO DA API
   13/05/2026. Yago: Quando o request é POST com Content-Type JSON,
   processa como chamada de API e retorna JSON.
   Quando é GET, cai no HTML no final do arquivo.
============================================================ */
$method       = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$content_type = $_SERVER['CONTENT_TYPE']   ?? '';
$is_api       = $method === 'POST' && str_contains($content_type, 'application/json');

if ($is_api) {
    // 13/05/2026. Yago: Garante que a resposta sempre seja JSON
    header('Content-Type: application/json; charset=utf-8');

    // 13/05/2026. Yago: Lê e decodifica o body JSON enviado pelo fetch do frontend
    $body  = file_get_contents('php://input');
    $dados = json_decode($body, true);

    if (!$dados) {
        http_response_code(400);
        echo json_encode(['error' => 'Requisição inválida.']);
        exit;
    }

    // 13/05/2026. Yago: Checa rate limit antes de qualquer processamento
    if (!checar_rate_limit()) {
        http_response_code(429);
        echo json_encode(['error' => 'Muitas tentativas. Aguarde 10 minutos e tente novamente.']);
        exit;
    }

    $canal = $dados['canal'] ?? '';

    /* ── E-MAIL ── */
    if ($canal === 'email') {
        $email = trim($dados['email'] ?? '');

        if (!validar_email($email)) {
            http_response_code(400);
            echo json_encode(['error' => 'Informe um endereço de e-mail válido.']);
            exit;
        }

        $senha = gerar_senha_provisoria();
        $ok    = enviar_email($email, $senha);

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao enviar o e-mail. Tente novamente.']);
            exit;
        }

        salvar_senha_provisoria($email, $senha);
        error_log('[StudyShare] Senha enviada por e-mail para: ' . $email);

        echo json_encode(['ok' => true]);
        exit;
    }

    /* ── SMS ── */
    if ($canal === 'sms') {
        $telefone = trim($dados['telefone'] ?? '');

        if (!validar_telefone($telefone)) {
            http_response_code(400);
            echo json_encode(['error' => 'Informe um número de celular válido com DDD.']);
            exit;
        }

        $senha = gerar_senha_provisoria();
        $ok    = enviar_sms($telefone, $senha);

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao enviar o SMS. Verifique o número e tente novamente.']);
            exit;
        }

        $digits = preg_replace('/\D/', '', $telefone);
        salvar_senha_provisoria($digits, $senha);
        error_log('[StudyShare] Senha enviada por SMS para: +55' . $digits);

        echo json_encode(['ok' => true]);
        exit;
    }

    // 13/05/2026. Yago: Canal desconhecido
    http_response_code(400);
    echo json_encode(['error' => 'Canal inválido. Use "email" ou "sms".']);
    exit;
}

/* ============================================================
   SECTION 9 — FRONTEND HTML
   13/05/2026. Yago: Servido quando o request é GET.
   O fetch do JavaScript aponta para o próprio arquivo PHP.
============================================================ */
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Recuperar Senha — StudyShare</title>

  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* 13/05/2026. Yago: Classe base dos ícones — declarada explicitamente
       para garantir renderização independente do carregamento do Google Fonts */
    .material-symbols-outlined {
      font-family: 'Material Symbols Outlined', sans-serif;
      font-weight: normal;
      font-style: normal;
      font-size: 20px;
      line-height: 1;
      letter-spacing: normal;
      text-transform: none;
      display: inline-block;
      white-space: nowrap;
      word-wrap: normal;
      direction: ltr;
      -webkit-font-smoothing: antialiased;
      font-feature-settings: 'liga';
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      user-select: none;
      pointer-events: none;
      flex-shrink: 0;
    }

    /* 13/05/2026. Yago: Tokens de design — conforme template.md */
    :root {
      --blue       : #004AAD;
      --blue-hover : #0057CC;
      --blue-light : #E8F0FF;
      --blue-glow  : rgba(0,74,173,.16);
      --sidebar-bg : #002F87;
      --white      : #FFFFFF;
      --off-white  : #F5F7FA;
      --black      : #0F172A;
      --gray-200   : #E2E8F0;
      --gray-400   : #94A3B8;
      --gray-500   : #64748B;
      --gray-600   : #475569;
      --success    : #16A34A;
      --error      : #DC2626;
      --font       : 'Inter', system-ui, sans-serif;
      --radius-sm  : 8px;
      --radius-lg  : 16px;
      --shadow     : 0 4px 24px rgba(0,0,0,.09), 0 1px 4px rgba(0,0,0,.05);
      --shadow-btn : 0 4px 14px rgba(0,74,173,.35);
      --tr         : .18s cubic-bezier(.4,0,.2,1);
    }

    body {
      font-family: var(--font);
      background: var(--off-white);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      -webkit-font-smoothing: antialiased;
    }

    /* 13/05/2026. Yago: Card principal — componente isolado */
    .card {
      width: 100%;
      max-width: 460px;
      background: var(--white);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      overflow: hidden;
      animation: cardIn .35s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes cardIn {
      from { opacity:0; transform:translateY(14px); }
      to   { opacity:1; transform:translateY(0); }
    }

    /* 13/05/2026. Yago: Header azul navy — cor da sidebar do Figma */
    .card-header {
      background: var(--sidebar-bg);
      padding: 22px 28px;
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .header-icon {
      width:42px; height:42px; min-width:42px;
      background: rgba(255,255,255,.13);
      border-radius: var(--radius-sm);
      display: flex; align-items: center; justify-content: center;
    }

    .header-icon .material-symbols-outlined { font-size:22px; color:var(--white); font-variation-settings:'FILL' 0,'wght' 300; }
    .header-text h1 { font-size:17px; font-weight:700; color:var(--white); letter-spacing:-.2px; }
    .header-text p  { font-size:13px; color:rgba(255,255,255,.6); margin-top:3px; }

    .card-body { padding: 28px 28px 26px; }

    .back-link {
      display:inline-flex; align-items:center; gap:6px;
      font-size:13px; font-weight:500; color:var(--gray-500);
      text-decoration:none; margin-bottom:22px;
      transition:color var(--tr); line-height:1;
    }
    .back-link:hover { color:var(--blue); }
    .back-link .material-symbols-outlined { font-size:16px; color:currentColor; }

    /* 13/05/2026. Yago: Banner de feedback — controlado via JS */
    .alert {
      display: none;
      align-items: flex-start;
      gap: 10px;
      padding: 12px 14px;
      border-radius: var(--radius-sm);
      margin-bottom: 16px;
      font-size: 13px;
      line-height: 1.5;
      animation: fadeUp .2s ease both;
    }
    .alert.is-error   { display:flex; background:#FEE2E2; color:#991B1B; border:1px solid #FECACA; }
    .alert .material-symbols-outlined { font-size:18px; color:currentColor; margin-top:1px; font-variation-settings:'FILL' 1; }

    .section-label {
      font-size:11px; font-weight:600; letter-spacing:.7px;
      text-transform:uppercase; color:var(--gray-400);
      display:block; margin-bottom:10px;
    }

    .method-row { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:22px; }

    .method-btn {
      display:flex; align-items:center; justify-content:center; gap:7px;
      padding:11px 12px; border-radius:var(--radius-sm);
      border:1.5px solid var(--gray-200); background:var(--white);
      font-family:var(--font); font-size:14px; font-weight:500;
      color:var(--gray-500); cursor:pointer; line-height:1;
      transition:border-color var(--tr), background var(--tr), color var(--tr);
    }
    .method-btn .material-symbols-outlined { font-size:18px; color:currentColor; font-variation-settings:'FILL' 0; transition:font-variation-settings var(--tr); }
    .method-btn.active { border-color:var(--blue); background:var(--blue-light); color:var(--blue); }
    .method-btn.active .material-symbols-outlined { font-variation-settings:'FILL' 1; }
    .method-btn:not(.active):hover { border-color:var(--gray-400); color:var(--gray-600); }

    .form { display:flex; flex-direction:column; gap:14px; }
    .field { display:flex; flex-direction:column; gap:6px; }
    .field > label { font-size:13px; font-weight:500; color:var(--gray-600); }

    .input-row { position:relative; display:flex; align-items:center; }
    .input-ico {
      position:absolute; left:12px; font-size:18px; width:18px;
      text-align:center; color:var(--gray-400);
      font-variation-settings:'FILL' 0; transition:color var(--tr); line-height:1;
    }
    .input-row input {
      width:100%; padding:11px 14px 11px 42px;
      border:1.5px solid var(--gray-200); border-radius:var(--radius-sm);
      font-family:var(--font); font-size:14px; color:var(--black);
      background:var(--off-white); outline:none;
      transition:border-color var(--tr), box-shadow var(--tr), background var(--tr);
    }
    .input-row input::placeholder { color:var(--gray-400); }
    .input-row input:focus { border-color:var(--blue); background:var(--white); box-shadow:0 0 0 3px var(--blue-glow); }
    .input-row:focus-within .input-ico { color:var(--blue); }
    .input-row input.has-error { border-color:var(--error); box-shadow:0 0 0 3px rgba(220,38,38,.12); }
    .hint { font-size:12px; color:var(--gray-400); line-height:1.5; }

    .btn-submit {
      display:flex; align-items:center; justify-content:center; gap:8px;
      width:100%; padding:13px 16px; background:var(--blue); color:var(--white);
      border:none; border-radius:var(--radius-sm); font-family:var(--font);
      font-size:14px; font-weight:600; cursor:pointer;
      box-shadow:var(--shadow-btn); margin-top:4px; line-height:1;
      transition:background var(--tr), transform var(--tr), box-shadow var(--tr);
    }
    .btn-submit .material-symbols-outlined { font-size:18px; color:var(--white); }
    .btn-submit:hover { background:var(--blue-hover); transform:translateY(-1px); box-shadow:0 6px 18px rgba(0,74,173,.42); }
    .btn-submit:active { transform:translateY(0); }
    .btn-submit:disabled { opacity:.6; cursor:not-allowed; transform:none; }

    .spinner {
      width:16px; height:16px; flex-shrink:0;
      border:2px solid rgba(255,255,255,.35); border-top-color:#fff;
      border-radius:50%; animation:spin .7s linear infinite; display:none;
    }
    @keyframes spin    { to { transform:rotate(360deg); } }
    @keyframes fadeUp  { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

    .divider { display:flex; align-items:center; gap:12px; margin:20px 0 16px; }
    .div-line { flex:1; height:1px; background:var(--gray-200); }
    .div-text { font-size:12px; color:var(--gray-400); }

    .card-footer { text-align:center; font-size:13px; color:var(--gray-500); }
    .card-footer a { color:var(--blue); font-weight:600; text-decoration:none; }
    .card-footer a:hover { text-decoration:underline; }

    /* 13/05/2026. Yago: Estado de sucesso — substitui o formulário */
    .success-state {
      display:none; flex-direction:column; align-items:center;
      text-align:center; padding:8px 0 4px;
      animation:fadeUp .3s ease both;
    }
    .success-circle {
      width:60px; height:60px; background:#DCFCE7; border-radius:50%;
      display:flex; align-items:center; justify-content:center; margin-bottom:16px;
    }
    .success-circle .material-symbols-outlined { font-size:30px; color:var(--success); font-variation-settings:'FILL' 1; }
    .success-title { font-size:20px; font-weight:700; color:var(--black); letter-spacing:-.3px; margin-bottom:8px; }
    .success-msg { font-size:14px; color:var(--gray-500); line-height:1.65; max-width:300px; margin-bottom:22px; }
    .success-msg strong { color:var(--black); }

    .btn-outline {
      display:inline-flex; align-items:center; gap:7px; padding:11px 22px;
      border:1.5px solid var(--gray-200); border-radius:var(--radius-sm);
      font-family:var(--font); font-size:14px; font-weight:500;
      color:var(--gray-600); background:var(--white); text-decoration:none;
      cursor:pointer; line-height:1;
      transition:border-color var(--tr), color var(--tr);
    }
    .btn-outline:hover { border-color:var(--blue); color:var(--blue); }
    .btn-outline .material-symbols-outlined { font-size:16px; color:currentColor; }

    .resend-note { font-size:12px; color:var(--gray-400); margin-top:14px; }
    .resend-note a { color:var(--blue); font-weight:500; cursor:pointer; }
    .resend-note a:hover { text-decoration:underline; }

    #phone-field { display:none; }
  </style>
</head>
<body>

  <div class="card">

    <div class="card-header">
      <div class="header-icon">
        <span class="material-symbols-outlined">lock_reset</span>
      </div>
      <div class="header-text">
        <h1>Recuperar senha</h1>
        <p>Enviaremos uma senha provisória para você</p>
      </div>
    </div>

    <div class="card-body">

      <!-- FORM STATE -->
      <div id="form-state">

        <a href="login.php" class="back-link">
          <span class="material-symbols-outlined">arrow_back</span>
          Voltar para o login
        </a>

        <!-- 13/05/2026. Yago: Banner de erro — preenchido e exibido via JS -->
        <div class="alert" id="alert-box" role="alert">
          <span class="material-symbols-outlined">error</span>
          <span id="alert-msg"></span>
        </div>

        <span class="section-label">Enviar via</span>
        <div class="method-row" role="group" aria-label="Método de recuperação">
          <button class="method-btn active" id="btn-email" onclick="setMethod('email')" type="button">
            <span class="material-symbols-outlined">email</span>E-mail
          </button>
          <button class="method-btn" id="btn-sms" onclick="setMethod('sms')" type="button">
            <span class="material-symbols-outlined">phone_android</span>SMS
          </button>
        </div>

        <form class="form" id="recovery-form" novalidate>

          <div class="field" id="email-field">
            <label for="input-email">Endereço de e-mail</label>
            <div class="input-row">
              <span class="material-symbols-outlined input-ico">email</span>
              <input type="email" id="input-email" placeholder="seu@email.com"
                     autocomplete="email" inputmode="email"/>
            </div>
            <span class="hint">Use o e-mail cadastrado na sua conta StudyShare.</span>
          </div>

          <div class="field" id="phone-field">
            <label for="input-phone">Número de celular</label>
            <div class="input-row">
              <span class="material-symbols-outlined input-ico">phone_android</span>
              <input type="tel" id="input-phone" placeholder="(00) 00000-0000"
                     autocomplete="tel" inputmode="tel"/>
            </div>
            <span class="hint">Número com DDD cadastrado na sua conta.</span>
          </div>

          <button type="submit" class="btn-submit" id="btn-submit">
            <div class="spinner" id="spinner"></div>
            <span class="material-symbols-outlined" id="btn-icon">send</span>
            <span id="btn-label">Enviar senha provisória</span>
          </button>

        </form>

        <div class="divider">
          <div class="div-line"></div>
          <span class="div-text">ou</span>
          <div class="div-line"></div>
        </div>

        <div class="card-footer">
          Lembrou sua senha? <a href="login.php">Entrar agora</a>
        </div>

      </div><!-- /form-state -->

      <!-- SUCCESS STATE -->
      <div class="success-state" id="success-state">
        <div class="success-circle">
          <span class="material-symbols-outlined">check_circle</span>
        </div>
        <h2 class="success-title">Senha enviada!</h2>
        <p class="success-msg" id="success-msg">
          Uma senha provisória foi enviada para <strong id="success-target"></strong>.
        </p>
        <a href="login.php" class="btn-outline">
          <span class="material-symbols-outlined">arrow_back</span>Voltar para o login
        </a>
        <p class="resend-note">Não recebeu? <a onclick="resetForm()">Reenviar agora</a></p>
      </div>

    </div>
  </div>

  <script>
    'use strict';

    let currentMethod = 'email';

    function setMethod(m) {
      currentMethod = m;
      document.getElementById('btn-email').classList.toggle('active', m === 'email');
      document.getElementById('btn-sms').classList.toggle('active',   m === 'sms');
      document.getElementById('email-field').style.display = m === 'email' ? 'flex' : 'none';
      document.getElementById('phone-field').style.display = m === 'sms'   ? 'flex' : 'none';
      clearAlert();
      document.getElementById(m === 'email' ? 'input-email' : 'input-phone').focus();
    }

    // 13/05/2026. Yago: Máscara de telefone — (00) 00000-0000
    document.getElementById('input-phone').addEventListener('input', function () {
      let v = this.value.replace(/\D/g, '').substring(0, 11);
      if      (v.length > 6) v = `(${v.slice(0,2)}) ${v.slice(2,7)}-${v.slice(7)}`;
      else if (v.length > 2) v = `(${v.slice(0,2)}) ${v.slice(2)}`;
      else if (v.length)     v = `(${v}`;
      this.value = v;
    });

    function validate() {
      if (currentMethod === 'email')
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(document.getElementById('input-email').value.trim());
      return document.getElementById('input-phone').value.replace(/\D/g, '').length >= 10;
    }

    function flashError(id) {
      const el = document.getElementById(id);
      el.classList.add('has-error');
      el.focus();
      setTimeout(() => el.classList.remove('has-error'), 2200);
    }

    function showAlert(msg) {
      document.getElementById('alert-msg').textContent = msg;
      document.getElementById('alert-box').className = 'alert is-error';
    }

    function clearAlert() {
      document.getElementById('alert-box').className = 'alert';
    }

    function setLoading(on) {
      document.getElementById('btn-submit').disabled    = on;
      document.getElementById('spinner').style.display  = on ? 'block'     : 'none';
      document.getElementById('btn-icon').style.display = on ? 'none'      : 'inline-block';
      document.getElementById('btn-label').textContent  = on ? 'Enviando…' : 'Enviar senha provisória';
    }

    function showSuccess(target) {
      document.getElementById('form-state').style.display    = 'none';
      document.getElementById('success-state').style.display = 'flex';
      document.getElementById('success-target').textContent  = target;
      if (currentMethod === 'sms')
        document.getElementById('success-msg').innerHTML =
          `Uma senha provisória foi enviada por SMS para <strong>${target}</strong>.`;
    }

    function resetForm() {
      document.getElementById('success-state').style.display = 'none';
      document.getElementById('form-state').style.display    = 'block';
      document.getElementById('input-email').value = '';
      document.getElementById('input-phone').value = '';
      clearAlert();
      setMethod(currentMethod);
    }

    // 13/05/2026. Yago: Chama o próprio arquivo PHP via POST JSON
    document.getElementById('recovery-form').addEventListener('submit', async function (e) {
      e.preventDefault();
      clearAlert();

      const inputId = currentMethod === 'email' ? 'input-email' : 'input-phone';

      if (!validate()) {
        flashError(inputId);
        showAlert(
          currentMethod === 'email'
            ? 'Informe um endereço de e-mail válido.'
            : 'Informe um número de celular válido com DDD.'
        );
        return;
      }

      setLoading(true);

      try {
        // 13/05/2026. Yago: POST para o próprio arquivo PHP — mesmo arquivo, dois comportamentos
        const body = currentMethod === 'email'
          ? { canal: 'email',  email:    document.getElementById('input-email').value.trim() }
          : { canal: 'sms',    telefone: document.getElementById('input-phone').value.trim() };

        const res  = await fetch(window.location.pathname, {
          method : 'POST',
          headers: { 'Content-Type': 'application/json' },
          body   : JSON.stringify(body),
        });

        const data = await res.json();

        if (res.ok && data.ok) {
          showSuccess(Object.values(body).find((_, i) => i === 1));
        } else {
          showAlert(data.error || 'Ocorreu um erro inesperado. Tente novamente.');
        }

      } catch (err) {
        showAlert('Erro de conexão. Verifique sua internet e tente novamente.');
        console.error('[Fetch] Erro:', err);
      } finally {
        setLoading(false);
      }
    });
  </script>

</body>
</html>
