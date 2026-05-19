<?php
/* ============================================================
   StudyShare — Tela de Recuperação de Senha
   Arquivo: recuperarSenha.php
   Autor: Yago Mata
   Data: 13/05/2026

   13/05/2026. Yago: LEIA ANTES DE QUALQUER COISA — CONTEXTO DO PROJETO
   -----------------------------------------------------------------------
   Esse arquivo é parte do projeto acadêmico StudyShare, desenvolvido
   para a disciplina de Desenvolvimento Web na PUC Minas.

   Como é um projeto de faculdade e não um sistema real em produção,
   algumas decisões foram tomadas de forma diferente do que seria feito
   num sistema comercial de verdade. Vou explicar cada uma delas aqui:

   1. POR QUE LOCALHOST / XAMPP?
      Em vez de contratar uma hospedagem paga, usamos o XAMPP, que é um
      servidor Apache + PHP que roda direto no seu computador. Isso
      permite testar tudo localmente sem gastar nada. O WAMP é uma
      alternativa ao XAMPP — faz a mesma coisa, só que voltado pra
      Windows. Pode usar qualquer um dos dois, o processo é idêntico.

   2. POR QUE PHP PURO EM VEZ DE FRAMEWORK?
      Poderíamos ter usado Laravel, Symfony etc., mas para um projeto
      acadêmico que precisa rodar sem Composer em qualquer máquina, PHP
      puro é mais simples de entregar, demonstrar e corrigir.

   3. POR QUE TUDO EM UM SÓ ARQUIVO?
      Para facilitar a entrega e apresentação. O professor abre um
      arquivo, vê o backend e o frontend juntos, e entende o fluxo
      completo sem precisar navegar entre pastas.

   4. POR QUE PHPMAILER EM VEZ DO mail() NATIVO?
      O mail() nativo do PHP não consegue autenticar no Gmail via SSL/TLS
      em localhost — ele depende de um sendmail configurado no sistema,
      que o XAMPP não tem. O PHPMailer resolve isso conectando direto
      no SMTP do Gmail com autenticação, igual a qualquer cliente de
      e-mail faria.

   5. POR QUE TWILIO PARA SMS?
      Twilio é a API de SMS mais usada no mercado. No plano trial
      gratuito ($15.50 de crédito) dá pra testar sem pagar nada do
      próprio bolso. O SMS sai do crédito trial, não do cartão.
      Para o projeto funcionar no trial, o número de destino precisa
      estar verificado no painel do Twilio.

   6. POR QUE SESSÃO EM VEZ DE BANCO DE DADOS?
      Como não temos banco configurado nesse arquivo isolado, usamos
      $_SESSION para guardar temporariamente a senha provisória gerada.
      Em produção real isso seria uma tabela no banco com TTL.

   -----------------------------------------------------------------------
   COMO TESTAR (passo a passo):

   COM XAMPP:
     1. Instale o XAMPP em apachefriends.org
     2. Inicie o Apache pelo XAMPP Control Panel
     3. Coloque este arquivo em: C:\xampp\htdocs\studyshare\
     4. Coloque a pasta PHPMailer\ junto (baixe em github.com/PHPMailer/PHPMailer)
     5. Acesse: http://localhost/studyshare/recuperarSenha.php

   COM WAMP:
     1. Instale o WampServer em wampserver.com
     2. Clique no ícone verde na bandeja → Start All Services
     3. Coloque os arquivos em: C:\wamp64\www\studyshare\
     4. Acesse: http://localhost/studyshare/recuperarSenha.php
     (O processo é idêntico ao XAMPP — só muda a pasta)

   PARA O E-MAIL FUNCIONAR:
     - Você precisa de uma App Password do Gmail
     - Acesse: myaccount.google.com/apppasswords
     - Crie uma senha para o app "StudyShare"
     - Cole em GMAIL_APP_PASS abaixo (sem espaços)

   PARA O SMS FUNCIONAR:
     - Precisa de conta no Twilio (twilio.com)
     - No trial, o SMS só chega em números verificados
     - Verifique seu celular em: Phone Numbers → Verified Caller IDs
     - Cole o SID e Token abaixo

   ESTRUTURA DE PASTAS NECESSÁRIA:
     studyshare/
       ├── recuperarSenha.php   ← este arquivo
       └── PHPMailer/
             └── src/
                   ├── PHPMailer.php
                   ├── SMTP.php
                   └── Exception.php
============================================================ */


/* ============================================================
   SECTION 1 — CONFIGURAÇÃO GERAL
   -----------------------------------------------------------------------
   13/05/2026. Yago: Aqui ficam todas as credenciais do sistema.
   É a única seção que precisa ser alterada para adaptar o projeto
   a outro ambiente ou conta. Centralizei tudo aqui de propósito —
   assim quem for dar manutenção não precisa caçar variável por variável
   espalhada pelo código.

   IMPORTANTE: Em produção real, essas credenciais ficariam em variáveis
   de ambiente (.env), nunca hardcoded assim. Mas para fins acadêmicos
   e testes locais, deixar aqui facilita muito.
============================================================ */

// 13/05/2026. Yago: E-mail que aparece como remetente nas mensagens enviadas
// e também é a conta Gmail usada para autenticar no SMTP
define('EMAIL_REMETENTE',  'matayago8@gmail.com');

// 13/05/2026. Yago: Nome que o destinatário vê no campo "De:" do e-mail
define('EMAIL_NOME',       'StudyShare');

// 13/05/2026. Yago: App Password gerada no Google — NÃO é a senha normal da conta.
// Gerada em: myaccount.google.com/apppasswords
// O Gmail exige isso para permitir que apps externos enviem e-mail pela conta.
define('GMAIL_APP_PASS',   'stpkzgmdkmafmmvi');

// 13/05/2026. Yago: Credenciais do Twilio para envio de SMS.
// Account SID: identificador único da conta (começa com AC)
// Auth Token: chave secreta de autenticação da API
// FROM: número Twilio comprado que aparece como remetente do SMS
define('TWILIO_SID',       'AC652567422d60e8c0abdb416b8385c29e');
define('TWILIO_TOKEN',     '831939e23fdc3b1a1432e781d389b81a');
define('TWILIO_FROM',      '+18148013437');

// 13/05/2026. Yago: Tempo em minutos que a senha provisória fica válida.
// 15 minutos é o padrão do mercado — tempo suficiente para o usuário
// acessar o e-mail/SMS e fazer login, mas curto o bastante para segurança.
define('SENHA_EXPIRY_MIN', 15);


/* ============================================================
   SECTION 2 — RATE LIMITING (controle de tentativas)
   -----------------------------------------------------------------------
   13/05/2026. Yago: Sem esse controle, qualquer pessoa poderia
   ficar chamando a rota de envio infinitamente, derrubando nossa
   cota do Gmail ou gastando todos os créditos do Twilio em minutos.

   A lógica é simples: guardamos na sessão quantas vezes o usuário
   tentou e quando foi a primeira tentativa. Se passou de 5 tentativas
   dentro de 10 minutos, bloqueamos e mandamos HTTP 429 (Too Many Requests).
   Quando os 10 minutos passam, o contador zera automaticamente.

   Usamos sessão aqui porque não temos banco de dados nesse projeto.
   Em produção o ideal seria Redis ou uma tabela de rate limiting.
============================================================ */
session_start();

function checar_rate_limit(): bool {
    $agora  = time();        // timestamp atual em segundos
    $janela = 10 * 60;       // janela de 10 minutos em segundos
    $max    = 5;             // máximo de tentativas permitidas na janela

    // 13/05/2026. Yago: Se ainda não existe registro de tentativas nessa sessão,
    // inicializa o contador e marca o início da janela de tempo
    if (!isset($_SESSION['rl_count'], $_SESSION['rl_inicio'])) {
        $_SESSION['rl_count']  = 0;
        $_SESSION['rl_inicio'] = $agora;
    }

    // 13/05/2026. Yago: Se já passou o tempo da janela desde a primeira tentativa,
    // reseta o contador — o usuário tem direito a novas tentativas
    if ($agora - $_SESSION['rl_inicio'] > $janela) {
        $_SESSION['rl_count']  = 0;
        $_SESSION['rl_inicio'] = $agora;
    }

    // 13/05/2026. Yago: Incrementa o contador e verifica se ainda está dentro do limite
    $_SESSION['rl_count']++;
    return $_SESSION['rl_count'] <= $max;
}


/* ============================================================
   SECTION 3 — GERADOR DE SENHA PROVISÓRIA
   -----------------------------------------------------------------------
   13/05/2026. Yago: Gera uma senha aleatória de 8 caracteres que será
   enviada ao usuário por e-mail ou SMS.

   Usamos random_int() em vez de rand() ou array_rand() porque o
   random_int() usa a fonte de entropia do sistema operacional, o que
   o torna criptograficamente seguro — impossível de prever a sequência.

   Removemos os caracteres ambíguos do alfabeto de propósito:
   - Sem 0 (zero) e O (letra O) — parecem iguais em algumas fontes
   - Sem 1 (um), l (ele minúsculo) e I (i maiúsculo) — idem
   Isso evita que o usuário confunda os caracteres ao digitar a senha.
============================================================ */
function gerar_senha_provisoria(int $tamanho = 8): string {
    // 13/05/2026. Yago: Alfabeto sem caracteres que causam confusão visual
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $senha = '';

    // 13/05/2026. Yago: Sorteia um caractere por vez usando índice aleatório seguro
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $senha;
}


/* ============================================================
   SECTION 4 — ARMAZENAMENTO TEMPORÁRIO DA SENHA (sessão PHP)
   -----------------------------------------------------------------------
   13/05/2026. Yago: Depois que geramos e enviamos a senha, precisamos
   guardá-la em algum lugar para poder validar quando o usuário tentar
   fazer login com ela.

   Guardamos apenas o hash SHA-256, não a senha em texto puro.
   Se alguém invadir o servidor e conseguir ler a sessão, não vai
   encontrar a senha — só o hash, que é inútil sem a senha original.

   A função verificar_senha_provisoria() está aqui pronta para ser
   chamada na tela de login quando implementarem essa parte.
============================================================ */
function salvar_senha_provisoria(string $identificador, string $senha): void {
    // 13/05/2026. Yago: Nunca guardamos a senha em texto — sempre o hash
    $hash = hash('sha256', $senha);

    $_SESSION['senhas_prov'][$identificador] = [
        'hash'      => $hash,
        // 13/05/2026. Yago: Calcula o timestamp exato de expiração
        'expira_em' => time() + (SENHA_EXPIRY_MIN * 60),
    ];
}

// 13/05/2026. Yago: Função para a tela de login usar — verifica se a senha
// digitada pelo usuário bate com a que foi enviada e se ainda não expirou.
// hash_equals() compara em tempo constante para evitar timing attacks.
function verificar_senha_provisoria(string $identificador, string $candidata): bool {
    $dados = $_SESSION['senhas_prov'][$identificador] ?? null;

    // 13/05/2026. Yago: Rejeita se não existe registro ou se já expirou
    if (!$dados || time() > $dados['expira_em']) return false;

    return hash_equals($dados['hash'], hash('sha256', $candidata));
}


/* ============================================================
   SECTION 5 — ENVIO DE E-MAIL (PHPMailer + Gmail SMTP)
   -----------------------------------------------------------------------
   13/05/2026. Yago: Usamos PHPMailer porque o mail() nativo do PHP
   não consegue se autenticar no Gmail com SSL/TLS em localhost.
   O XAMPP não vem com sendmail configurado, então o mail() simplesmente
   falha silenciosamente sem enviar nada.

   O PHPMailer resolve isso estabelecendo uma conexão SMTP direta com
   os servidores do Gmail na porta 587 com STARTTLS — exatamente como
   o Outlook ou Thunderbird faria ao enviar um e-mail.

   O template HTML do e-mail usa table layout (não div/flexbox) porque
   clientes de e-mail como Outlook não renderizam CSS moderno.
   Isso é uma limitação histórica do mundo dos e-mails em HTML.
============================================================ */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 13/05/2026. Yago: Carrega os três arquivos do PHPMailer que precisamos.
// A pasta PHPMailer/src/ precisa estar no mesmo diretório que este arquivo.
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function enviar_email(string $para, string $senha_provisoria): bool {
    // 13/05/2026. Yago: true no construtor faz o PHPMailer lançar exceções
    // em vez de retornar false — facilita o diagnóstico de erros
    $mail = new PHPMailer(true);

    try {
        // 13/05/2026. Yago: Diz ao PHPMailer para usar SMTP em vez de mail()
        $mail->isSMTP();

        // 13/05/2026. Yago: Endereço do servidor SMTP do Gmail
        $mail->Host = 'smtp.gmail.com';

        // 13/05/2026. Yago: Ativa autenticação — sem isso o Gmail rejeita a conexão
        $mail->SMTPAuth = true;

        // 13/05/2026. Yago: Credenciais da conta Gmail que vai enviar o e-mail
        $mail->Username = EMAIL_REMETENTE;
        $mail->Password = GMAIL_APP_PASS; // App Password, não a senha normal

        // 13/05/2026. Yago: STARTTLS na porta 587 — padrão atual do Gmail.
        // É diferente de SSL puro (porta 465) — o STARTTLS começa a conexão
        // sem criptografia e depois "sobe" para conexão segura.
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8'; // garante acentos sem problema

        // 13/05/2026. Yago: Define quem aparece no campo "De:" do e-mail
        $mail->setFrom(EMAIL_REMETENTE, EMAIL_NOME);

        // 13/05/2026. Yago: Define o destinatário — o e-mail que o usuário digitou
        $mail->addAddress($para);

        $mail->Subject = 'Recuperação de senha — StudyShare';
        $mail->isHTML(true); // permite enviar HTML no corpo

        // 13/05/2026. Yago: Guardamos o tempo de expiração numa variável
        // para usar dentro da string do HTML sem quebrar a sintaxe
        $expiry = SENHA_EXPIRY_MIN;

        // 13/05/2026. Yago: Template HTML do e-mail seguindo a identidade visual
        // do projeto — azul #002F87 no header, branco no corpo, tipografia Arial
        // (sem Google Fonts porque e-mail não carrega fontes externas)
        $mail->Body = "
          <!DOCTYPE html>
          <html lang='pt-BR'>
          <head><meta charset='UTF-8'/></head>
          <body style='margin:0;padding:0;background:#F5F7FA;font-family:Arial,sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='padding:40px 20px;'>
              <tr><td align='center'>
                <table width='520' cellpadding='0' cellspacing='0'
                       style='background:#ffffff;border-radius:12px;overflow:hidden;
                              box-shadow:0 4px 24px rgba(0,0,0,.08);'>

                  <!-- Cabeçalho azul navy — mesma cor da sidebar do projeto -->
                  <tr>
                    <td style='background:#002F87;padding:28px 32px;'>
                      <p style='margin:0;font-size:20px;font-weight:700;color:#ffffff;
                                letter-spacing:-.3px;'>StudyShare</p>
                      <p style='margin:4px 0 0;font-size:13px;
                                color:rgba(255,255,255,.65);'>Recuperação de senha</p>
                    </td>
                  </tr>

                  <!-- Corpo do e-mail -->
                  <tr>
                    <td style='padding:32px;'>
                      <p style='margin:0 0 16px;font-size:14px;color:#475569;line-height:1.6;'>
                        Recebemos uma solicitação para redefinir sua senha no StudyShare.
                        Use a senha provisória abaixo para acessar sua conta:
                      </p>

                      <!-- Caixa de destaque com a senha — azul claro com borda azul -->
                      <table width='100%' cellpadding='0' cellspacing='0' style='margin:24px 0;'>
                        <tr>
                          <td align='center'
                              style='background:#E8F0FF;border:1.5px solid #004AAD;
                                     border-radius:8px;padding:18px 24px;'>
                            <p style='margin:0 0 8px;font-size:11px;font-weight:600;
                                      letter-spacing:.7px;text-transform:uppercase;
                                      color:#64748B;'>Sua senha provisória</p>
                            <p style='margin:0;font-size:32px;font-weight:700;
                                      letter-spacing:8px;color:#002F87;
                                      font-family:monospace;'>{$senha_provisoria}</p>
                          </td>
                        </tr>
                      </table>

                      <!-- Aviso de validade da senha -->
                      <p style='margin:-8px 0 16px;font-size:12px;color:#94A3B8;
                                text-align:center;'>⏱ Válida por {$expiry} minutos</p>

                      <p style='margin:0 0 12px;font-size:14px;color:#475569;line-height:1.6;'>
                        Após o login, acesse <strong>Configurações → Segurança</strong>
                        e redefina sua senha permanente.
                      </p>
                      <p style='margin:0;font-size:14px;color:#475569;line-height:1.6;'>
                        Se você não solicitou essa recuperação, ignore este e-mail.
                        Sua senha atual permanece ativa.
                      </p>
                    </td>
                  </tr>

                  <!-- Rodapé -->
                  <tr>
                    <td style='padding:20px 32px;background:#F5F7FA;
                               border-top:1px solid #E2E8F0;font-size:12px;
                               color:#94A3B8;text-align:center;line-height:1.5;'>
                      StudyShare · Este é um e-mail automático, não responda.<br/>
                      © 2026 StudyShare. Todos os direitos reservados.
                    </td>
                  </tr>

                </table>
              </td></tr>
            </table>
          </body>
          </html>
        ";

        // 13/05/2026. Yago: Versão em texto simples do e-mail — fallback para
        // clientes de e-mail que não renderizam HTML (ex: alguns apps corporativos)
        $mail->AltBody = "Sua senha provisória é: {$senha_provisoria}. "
                       . "Válida por {$expiry} minutos. "
                       . "Acesse o StudyShare e redefina em Configurações → Segurança.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // 13/05/2026. Yago: Registra o erro no log do PHP para debugging
        // sem expor detalhes técnicos para o usuário final
        error_log('[StudyShare Email] Falha ao enviar: ' . $mail->ErrorInfo);
        return false;
    }
}


/* ============================================================
   SECTION 6 — ENVIO DE SMS (Twilio via cURL)
   -----------------------------------------------------------------------
   13/05/2026. Yago: Twilio é uma API REST — para usá-la não precisamos
   instalar nenhum SDK. Basta fazer uma requisição HTTP POST com cURL,
   que já vem nativo em qualquer instalação do PHP.

   O número do usuário vem no formato de máscara brasileiro (11) 99999-9999
   e precisa ser convertido para o formato E.164 internacional antes
   de enviar para a API. E.164 é o padrão global de telefonia:
   + (código do país) + DDD + número, sem espaços ou símbolos.
   Para o Brasil: +55 + DDD + número → ex: +5531992819841

   No plano trial do Twilio, só é possível enviar SMS para números
   previamente verificados no painel. Isso é uma limitação do trial
   para evitar spam — no plano pago não existe essa restrição.
============================================================ */
function enviar_sms(string $telefone_raw, string $senha_provisoria): bool {
    // 13/05/2026. Yago: Remove tudo que não for dígito da string do telefone
    // "(31) 99281-9841" vira "31992819841"
    $digits = preg_replace('/\D/', '', $telefone_raw);

    // 13/05/2026. Yago: Monta o formato E.164 adicionando o DDI do Brasil (+55)
    $e164 = '+55' . $digits;

    // 13/05/2026. Yago: Texto da mensagem SMS — precisa ser curto
    // porque SMS tem limite de 160 caracteres por segmento
    $mensagem = "StudyShare: sua senha provisória é {$senha_provisoria}. "
              . 'Válida por ' . SENHA_EXPIRY_MIN . ' min. Não compartilhe.';

    // 13/05/2026. Yago: URL da API REST do Twilio para criar mensagens
    // O Account SID faz parte da URL — é assim que a API identifica a conta
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_SID . '/Messages.json';

    // 13/05/2026. Yago: Inicia uma requisição cURL para a API do Twilio
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        // 13/05/2026. Yago: Retorna a resposta como string em vez de imprimir direto
        CURLOPT_RETURNTRANSFER => true,

        // 13/05/2026. Yago: Método POST — a API do Twilio exige POST para enviar SMS
        CURLOPT_POST           => true,

        // 13/05/2026. Yago: Autenticação HTTP Basic com SID e Token do Twilio
        // O cURL monta o header Authorization automaticamente com isso
        CURLOPT_USERPWD        => TWILIO_SID . ':' . TWILIO_TOKEN,

        // 13/05/2026. Yago: Corpo da requisição — os três campos que a API exige:
        // From (número Twilio), To (número do usuário em E.164), Body (texto do SMS)
        CURLOPT_POSTFIELDS     => http_build_query([
            'From' => TWILIO_FROM,
            'To'   => $e164,
            'Body' => $mensagem,
        ]),

        // 13/05/2026. Yago: Verifica o certificado SSL da API — não desabilitar isso,
        // pois abriria brecha para ataque man-in-the-middle
        CURLOPT_SSL_VERIFYPEER => true,

        // 13/05/2026. Yago: Timeout de 15 segundos — se a API não responder,
        // não deixa o usuário esperando na tela para sempre
        CURLOPT_TIMEOUT        => 15,
    ]);

    $resposta  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    // 13/05/2026. Yago: Erro de rede — cURL não conseguiu nem chegar na API
    if ($curl_err) {
        error_log('[StudyShare SMS] Erro de rede (cURL): ' . $curl_err);
        return false;
    }

    // 13/05/2026. Yago: Twilio retorna HTTP 201 Created quando o SMS foi aceito
    // para envio. Qualquer outro código significa que algo deu errado.
    if ($http_code !== 201) {
        $dados = json_decode($resposta, true);
        error_log('[StudyShare SMS] Twilio recusou o envio: ' . ($dados['message'] ?? $resposta));
        return false;
    }

    return true;
}


/* ============================================================
   SECTION 7 — VALIDADORES DE ENTRADA
   -----------------------------------------------------------------------
   13/05/2026. Yago: Nunca confie só na validação do JavaScript.
   O JS roda no navegador do usuário e pode ser desabilitado ou
   contornado — qualquer pessoa com Postman ou curl consegue mandar
   uma requisição direto para esse arquivo PHP.

   Por isso validamos tudo de novo aqui no servidor antes de
   qualquer processamento ou envio.
============================================================ */

// 13/05/2026. Yago: Valida e-mail usando o filtro nativo do PHP
// filter_var com FILTER_VALIDATE_EMAIL cobre a grande maioria dos casos
// e segue a RFC 822 de formato de endereços de e-mail
function validar_email(string $email): bool {
    return strlen($email) <= 254 // limite máximo definido pela RFC 5321
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 13/05/2026. Yago: Valida telefone brasileiro — remove a máscara e
// verifica se tem entre 10 dígitos (fixo com DDD) e 11 (celular com DDD)
function validar_telefone(string $tel): bool {
    $digits = preg_replace('/\D/', '', $tel);
    return strlen($digits) >= 10 && strlen($digits) <= 11;
}


/* ============================================================
   SECTION 8 — ROTEADOR DA API
   -----------------------------------------------------------------------
   13/05/2026. Yago: Aqui está o coração do backend. O mesmo arquivo
   PHP serve dois propósitos diferentes dependendo do tipo de requisição:

   → GET: o navegador está abrindo a página → entrega o HTML (Section 9)
   → POST com Content-Type JSON: o JavaScript está chamando a API
     → processa o envio de e-mail ou SMS e retorna JSON

   Isso elimina a necessidade de um arquivo separado para a API.
   O fetch() no JavaScript aponta para window.location.pathname,
   que é o próprio arquivo — o PHP detecta que é POST+JSON e age
   como API em vez de renderizar HTML.
============================================================ */
$method       = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$content_type = $_SERVER['CONTENT_TYPE']   ?? '';

// 13/05/2026. Yago: Só entra no bloco de API se for POST com JSON no body
$is_api = $method === 'POST' && str_contains($content_type, 'application/json');

if ($is_api) {

    // 13/05/2026. Yago: Garante que a resposta será interpretada como JSON
    // pelo fetch() no JavaScript
    header('Content-Type: application/json; charset=utf-8');

    // 13/05/2026. Yago: Lê o corpo da requisição — o JSON enviado pelo fetch()
    // php://input é o stream de leitura do body da requisição HTTP
    $body  = file_get_contents('php://input');
    $dados = json_decode($body, true);

    // 13/05/2026. Yago: Se o JSON vier malformado ou vazio, rejeita imediatamente
    if (!$dados) {
        http_response_code(400);
        echo json_encode(['error' => 'Requisição inválida.']);
        exit;
    }

    // 13/05/2026. Yago: Verifica o rate limit antes de qualquer processamento
    // Se o usuário estourou o limite, retorna 429 (Too Many Requests)
    if (!checar_rate_limit()) {
        http_response_code(429);
        echo json_encode(['error' => 'Muitas tentativas. Aguarde 10 minutos e tente novamente.']);
        exit;
    }

    // 13/05/2026. Yago: Canal define se o envio é por e-mail ou SMS
    $canal = $dados['canal'] ?? '';

    /* ── ROTA DE E-MAIL ── */
    if ($canal === 'email') {
        $email = trim($dados['email'] ?? '');

        // 13/05/2026. Yago: Validação server-side do e-mail
        if (!validar_email($email)) {
            http_response_code(400);
            echo json_encode(['error' => 'Informe um endereço de e-mail válido.']);
            exit;
        }

        // 13/05/2026. Yago: Gera a senha, tenta enviar e trata o erro separadamente
        $senha = gerar_senha_provisoria();
        $ok    = enviar_email($email, $senha);

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao enviar o e-mail. Tente novamente.']);
            exit;
        }

        // 13/05/2026. Yago: Só salva na sessão DEPOIS que o envio foi confirmado
        // Se salvasse antes e o envio falhasse, a senha ficaria guardada em vão
        salvar_senha_provisoria($email, $senha);

        // 13/05/2026. Yago: Log para debugging — aparece no error_log do Apache
        // em C:\xampp\apache\logs\error.log
        error_log('[StudyShare] E-mail enviado para: ' . $email . ' às ' . date('H:i:s'));

        echo json_encode(['ok' => true]);
        exit;
    }

    /* ── ROTA DE SMS ── */
    if ($canal === 'sms') {
        $telefone = trim($dados['telefone'] ?? '');

        // 13/05/2026. Yago: Validação server-side do telefone
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

        // 13/05/2026. Yago: Usa só os dígitos como chave de identificação
        // para não guardar a máscara na sessão
        $digits = preg_replace('/\D/', '', $telefone);
        salvar_senha_provisoria($digits, $senha);

        error_log('[StudyShare] SMS enviado para: +55' . $digits . ' às ' . date('H:i:s'));

        echo json_encode(['ok' => true]);
        exit;
    }

    // 13/05/2026. Yago: Canal desconhecido — alguém mandou um JSON inválido
    http_response_code(400);
    echo json_encode(['error' => 'Canal inválido. Use "email" ou "sms".']);
    exit;
}


/* ============================================================
   SECTION 9 — FRONTEND HTML
   -----------------------------------------------------------------------
   13/05/2026. Yago: A partir daqui é o HTML da tela que o usuário vê.
   Só chega aqui quando a requisição é GET (acesso normal pelo navegador).

   O design segue o design system do projeto definido em template.md:
   - Fonte: Inter (Google Fonts)
   - Cor primária: #004AAD (Royal Blue)
   - Fundo: #F5F7FA (Off-white)
   - Ícones: Material Symbols Outlined (Google)
   - Sidebar/header: #002F87 (Navy azul escuro do Figma)
============================================================ */
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Recuperar Senha — StudyShare</title>

  <!-- 13/05/2026. Yago: Fonte Inter — definida como tipografia padrão no template.md -->
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>

  <!-- 13/05/2026. Yago: Ícones Material Symbols — iconografia definida no template.md
       Carregamos a família completa com variações de FILL, peso e tamanho óptico -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>

  <style>
    /* 13/05/2026. Yago: Reset para zerar margens e padding padrão do browser */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ============================================================
       13/05/2026. Yago: CLASSE BASE DOS ÍCONES MATERIAL SYMBOLS
       Declaramos isso explicitamente aqui em vez de depender só do
       CSS que o Google Fonts injeta, porque se a fonte demorar pra
       carregar (rede lenta, bloqueador de rastreamento etc.), os ícones
       aparecem como texto puro — "home", "email", "lock_reset".
       Com essa declaração no próprio CSS da página, o navegador já
       sabe como renderizar assim que a fonte chegar.
    ============================================================ */
    .material-symbols-outlined {
      font-family: 'Material Symbols Outlined', sans-serif;
      font-weight: normal;
      font-style: normal;
      font-size: 20px;
      line-height: 1;
      letter-spacing: normal;
      text-transform: none;       /* evita que o texto seja maiúsculo */
      display: inline-block;
      white-space: nowrap;        /* impede quebra de linha no nome do ícone */
      word-wrap: normal;
      direction: ltr;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      font-feature-settings: 'liga'; /* ativa ligaduras — necessário para os ícones renderizarem */
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      user-select: none;    /* usuário não seleciona ícone como texto */
      pointer-events: none; /* clique passa através do ícone para o elemento pai */
      flex-shrink: 0;       /* ícone não encolhe em layouts flex */
    }

    /* ============================================================
       13/05/2026. Yago: TOKENS DE DESIGN
       Variáveis CSS com todas as cores, fontes e valores do design
       system do projeto — definidos em template.md.
       Centralizar aqui facilita manutenção: muda uma variável,
       muda em todos os lugares que a usam.
    ============================================================ */
    :root {
      --blue       : #004AAD; /* Royal Blue — cor primária, botões e destaques */
      --blue-hover : #0057CC; /* Tom mais escuro para estado hover do botão   */
      --blue-light : #E8F0FF; /* Azul bem claro — fundo do botão de método ativo */
      --blue-glow  : rgba(0,74,173,.16); /* Sombra do foco nos inputs          */
      --sidebar-bg : #002F87; /* Navy — cor da sidebar e header do card        */
      --white      : #FFFFFF;
      --off-white  : #F5F7FA; /* Fundo da página e dos inputs                  */
      --black      : #0F172A; /* Texto principal — não preto puro, mais suave  */
      --gray-200   : #E2E8F0; /* Bordas de inputs e divisores                  */
      --gray-400   : #94A3B8; /* Placeholder e textos de dica (hint)           */
      --gray-500   : #64748B; /* Textos secundários                            */
      --gray-600   : #475569; /* Labels dos campos                             */
      --success    : #16A34A; /* Verde — estado de sucesso                     */
      --error      : #DC2626; /* Vermelho — estado de erro                     */
      --font       : 'Inter', system-ui, sans-serif;
      --radius-sm  : 8px;     /* Arredondamento de inputs e botões             */
      --radius-lg  : 16px;    /* Arredondamento do card principal              */
      --shadow     : 0 4px 24px rgba(0,0,0,.09), 0 1px 4px rgba(0,0,0,.05);
      --shadow-btn : 0 4px 14px rgba(0,74,173,.35); /* Sombra azulada do botão */
      --tr         : .18s cubic-bezier(.4,0,.2,1);  /* Transição padrão suave  */
    }

    /* 13/05/2026. Yago: Centraliza o card tanto vertical quanto horizontalmente */
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

    /* ============================================================
       13/05/2026. Yago: CARD PRINCIPAL
       Componente isolado — não tem sidebar nem topbar aqui porque
       a tela de recuperação é acessada sem estar logado.
       A integração com o shell do app (sidebar + topbar) fica nas
       outras telas do projeto, que são responsabilidade de outros membros.
    ============================================================ */
    .card {
      width: 100%;
      max-width: 460px;
      background: var(--white);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      overflow: hidden; /* garante que o header arredondado não vaze */
      animation: cardIn .35s cubic-bezier(.22,1,.36,1) both;
    }

    /* 13/05/2026. Yago: Animação de entrada — card sobe suavemente ao carregar */
    @keyframes cardIn {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* 13/05/2026. Yago: Header do card com fundo navy (#002F87) —
       mesma cor da sidebar e topbar do Figma, mantém consistência visual */
    .card-header {
      background: var(--sidebar-bg);
      padding: 22px 28px;
      display: flex;
      align-items: center;
      gap: 14px;
    }

    /* 13/05/2026. Yago: Quadrado com fundo semi-transparente para o ícone
       — técnica comum para dar destaque sem usar uma cor sólida forte */
    .header-icon {
      width: 42px; height: 42px; min-width: 42px;
      background: rgba(255,255,255,.13);
      border-radius: var(--radius-sm);
      display: flex; align-items: center; justify-content: center;
    }

    .header-icon .material-symbols-outlined {
      font-size: 22px;
      color: var(--white);
      font-variation-settings: 'FILL' 0, 'wght' 300; /* outline mais fino */
    }

    .header-text h1 { font-size:17px; font-weight:700; color:var(--white); letter-spacing:-.2px; }
    .header-text p  { font-size:13px; color:rgba(255,255,255,.6); margin-top:3px; }

    .card-body { padding: 28px 28px 26px; }

    /* 13/05/2026. Yago: Link de voltar — discreto por padrão, azul no hover */
    .back-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 13px; font-weight: 500; color: var(--gray-500);
      text-decoration: none; margin-bottom: 22px;
      transition: color var(--tr); line-height: 1;
    }
    .back-link:hover { color: var(--blue); }
    .back-link .material-symbols-outlined { font-size: 16px; color: currentColor; }

    /* ============================================================
       13/05/2026. Yago: BANNER DE FEEDBACK (ERRO)
       Fica escondido por padrão (display:none) e é exibido pelo JS
       quando o envio falha. A animação fadeUp evita que apareça
       bruscamente na tela.
    ============================================================ */
    .alert {
      display: none;
      align-items: flex-start; gap: 10px;
      padding: 12px 14px; border-radius: var(--radius-sm);
      margin-bottom: 16px; font-size: 13px; line-height: 1.5;
      animation: fadeUp .2s ease both;
    }
    .alert.is-error {
      display: flex;
      background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA;
    }
    .alert .material-symbols-outlined {
      font-size: 18px; color: currentColor;
      margin-top: 1px; font-variation-settings: 'FILL' 1; /* ícone preenchido */
    }

    /* 13/05/2026. Yago: Label de seção em uppercase com espaçamento — padrão
       de interface comum para categorizar grupos de controles */
    .section-label {
      font-size: 11px; font-weight: 600; letter-spacing: .7px;
      text-transform: uppercase; color: var(--gray-400);
      display: block; margin-bottom: 10px;
    }

    /* ============================================================
       13/05/2026. Yago: SELETOR E-MAIL / SMS
       Grid de dois botões — o ativo fica com borda e fundo azul.
       O ícone vira preenchido (FILL 1) no estado ativo para
       reforçar visualmente qual canal está selecionado.
    ============================================================ */
    .method-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 22px; }

    .method-btn {
      display: flex; align-items: center; justify-content: center; gap: 7px;
      padding: 11px 12px; border-radius: var(--radius-sm);
      border: 1.5px solid var(--gray-200); background: var(--white);
      font-family: var(--font); font-size: 14px; font-weight: 500;
      color: var(--gray-500); cursor: pointer; line-height: 1;
      transition: border-color var(--tr), background var(--tr), color var(--tr);
    }
    .method-btn .material-symbols-outlined {
      font-size: 18px; color: currentColor; font-variation-settings: 'FILL' 0;
      transition: font-variation-settings var(--tr);
    }
    /* 13/05/2026. Yago: Estado ativo — borda azul + fundo azul claro */
    .method-btn.active { border-color: var(--blue); background: var(--blue-light); color: var(--blue); }
    .method-btn.active .material-symbols-outlined { font-variation-settings: 'FILL' 1; }
    .method-btn:not(.active):hover { border-color: var(--gray-400); color: var(--gray-600); }

    /* 13/05/2026. Yago: Estrutura do formulário — campos empilhados com espaço uniforme */
    .form { display: flex; flex-direction: column; gap: 14px; }
    .field { display: flex; flex-direction: column; gap: 6px; }
    .field > label { font-size: 13px; font-weight: 500; color: var(--gray-600); }

    /* ============================================================
       13/05/2026. Yago: INPUT COM ÍCONE À ESQUERDA
       O ícone é posicionado absolutamente dentro do .input-row.
       O padding-left de 42px no input abre o espaço necessário
       para o ícone não sobrepor o texto digitado.
       O width:18px no ícone garante posição estável — sem ele
       o ícone pode vazar sobre o texto do placeholder.
    ============================================================ */
    .input-row { position: relative; display: flex; align-items: center; }

    .input-ico {
      position: absolute; left: 12px;
      font-size: 18px; width: 18px; text-align: center;
      color: var(--gray-400); font-variation-settings: 'FILL' 0;
      transition: color var(--tr); line-height: 1;
    }

    .input-row input {
      width: 100%;
      padding: 11px 14px 11px 42px; /* 42px à esquerda = espaço do ícone */
      border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm);
      font-family: var(--font); font-size: 14px; color: var(--black);
      background: var(--off-white); outline: none;
      transition: border-color var(--tr), box-shadow var(--tr), background var(--tr);
    }
    .input-row input::placeholder { color: var(--gray-400); }

    /* 13/05/2026. Yago: Foco — borda azul + glow suave + fundo branco */
    .input-row input:focus {
      border-color: var(--blue);
      background: var(--white);
      box-shadow: 0 0 0 3px var(--blue-glow);
    }

    /* 13/05/2026. Yago: Quando qualquer filho do .input-row recebe foco,
       o ícone também fica azul — :focus-within propaga o estado para o pai */
    .input-row:focus-within .input-ico { color: var(--blue); }

    /* 13/05/2026. Yago: Classe de erro aplicada via JS quando a validação falha */
    .input-row input.has-error {
      border-color: var(--error);
      box-shadow: 0 0 0 3px rgba(220,38,38,.12);
    }

    .hint { font-size: 12px; color: var(--gray-400); line-height: 1.5; }

    /* ============================================================
       13/05/2026. Yago: BOTÃO DE ENVIO
       Azul primário com sombra colorida — padrão do design system.
       O hover levanta 1px e intensifica a sombra para dar sensação
       de profundidade (efeito de "pressionar" o botão).
    ============================================================ */
    .btn-submit {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      width: 100%; padding: 13px 16px;
      background: var(--blue); color: var(--white);
      border: none; border-radius: var(--radius-sm);
      font-family: var(--font); font-size: 14px; font-weight: 600;
      cursor: pointer; box-shadow: var(--shadow-btn);
      margin-top: 4px; line-height: 1;
      transition: background var(--tr), transform var(--tr), box-shadow var(--tr);
    }
    .btn-submit .material-symbols-outlined { font-size: 18px; color: var(--white); }
    .btn-submit:hover {
      background: var(--blue-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(0,74,173,.42);
    }
    .btn-submit:active  { transform: translateY(0); } /* volta ao lugar ao clicar */
    .btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    /* 13/05/2026. Yago: Spinner de carregamento — aparece no lugar do ícone
       enquanto a requisição de envio está em andamento */
    .spinner {
      width: 16px; height: 16px; flex-shrink: 0;
      border: 2px solid rgba(255,255,255,.35);
      border-top-color: #fff; /* só a parte de cima é branca — cria o efeito giratório */
      border-radius: 50%;
      animation: spin .7s linear infinite;
      display: none; /* começa escondido — o JS mostra quando necessário */
    }

    @keyframes spin   { to { transform: rotate(360deg); } }
    @keyframes fadeUp { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

    /* 13/05/2026. Yago: Divisor "ou" entre o botão e o link de login */
    .divider { display: flex; align-items: center; gap: 12px; margin: 20px 0 16px; }
    .div-line { flex: 1; height: 1px; background: var(--gray-200); }
    .div-text { font-size: 12px; color: var(--gray-400); }

    .card-footer { text-align: center; font-size: 13px; color: var(--gray-500); }
    .card-footer a { color: var(--blue); font-weight: 600; text-decoration: none; }
    .card-footer a:hover { text-decoration: underline; }

    /* ============================================================
       13/05/2026. Yago: ESTADO DE SUCESSO
       Substitui o formulário após o envio bem-sucedido.
       O JS oculta o #form-state e exibe este bloco com flex.
       A animação fadeUp evita transição abrupta.
    ============================================================ */
    .success-state {
      display: none; /* começa oculto — JS mostra quando necessário */
      flex-direction: column; align-items: center;
      text-align: center; padding: 8px 0 4px;
      animation: fadeUp .3s ease both;
    }

    /* 13/05/2026. Yago: Círculo verde com ícone de check — confirmação visual clara */
    .success-circle {
      width: 60px; height: 60px; background: #DCFCE7; border-radius: 50%;
      display: flex; align-items: center; justify-content: center; margin-bottom: 16px;
    }
    .success-circle .material-symbols-outlined {
      font-size: 30px; color: var(--success);
      font-variation-settings: 'FILL' 1; /* ícone preenchido no sucesso */
    }

    .success-title { font-size:20px; font-weight:700; color:var(--black); letter-spacing:-.3px; margin-bottom:8px; }
    .success-msg   { font-size:14px; color:var(--gray-500); line-height:1.65; max-width:300px; margin-bottom:22px; }
    .success-msg strong { color: var(--black); }

    /* 13/05/2026. Yago: Botão de contorno para o link de voltar ao login
       — menos destaque que o botão principal, hierarquia visual correta */
    .btn-outline {
      display: inline-flex; align-items: center; gap: 7px; padding: 11px 22px;
      border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm);
      font-family: var(--font); font-size: 14px; font-weight: 500;
      color: var(--gray-600); background: var(--white); text-decoration: none;
      cursor: pointer; line-height: 1;
      transition: border-color var(--tr), color var(--tr);
    }
    .btn-outline:hover { border-color: var(--blue); color: var(--blue); }
    .btn-outline .material-symbols-outlined { font-size: 16px; color: currentColor; }

    .resend-note { font-size: 12px; color: var(--gray-400); margin-top: 14px; }
    .resend-note a { color: var(--blue); font-weight: 500; cursor: pointer; }
    .resend-note a:hover { text-decoration: underline; }

    /* 13/05/2026. Yago: Campo de telefone começa oculto — o JS exibe
       quando o usuário clica no botão SMS */
    #phone-field { display: none; }
  </style>
</head>
<body>

  <!-- 13/05/2026. Yago: Card isolado — sem sidebar ou topbar porque essa
       tela é acessada antes do login. A integração com o shell completo
       do app fica nas outras telas, implementadas pelos outros membros. -->
  <div class="card">

    <!-- 13/05/2026. Yago: Header azul com ícone de cadeado e título -->
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

      <!-- ══════════════════════════════════════════
           FORM STATE — estado padrão (formulário)
           13/05/2026. Yago: Exibido quando a página carrega.
           O JS oculta este bloco e mostra o success-state
           após o envio bem-sucedido.
      ══════════════════════════════════════════ -->
      <div id="form-state">

        <!-- 13/05/2026. Yago: Link para voltar à tela de login -->
        <a href="login.php" class="back-link">
          <span class="material-symbols-outlined">arrow_back</span>
          Voltar para o login
        </a>

        <!-- 13/05/2026. Yago: Banner de erro — oculto por padrão,
             exibido via JS quando o envio falha ou validação falha -->
        <div class="alert" id="alert-box" role="alert">
          <span class="material-symbols-outlined">error</span>
          <span id="alert-msg"></span>
        </div>

        <!-- 13/05/2026. Yago: Seletor de canal — atende RF-13 que exige
             opção de envio por e-mail OU SMS -->
        <span class="section-label">Enviar via</span>
        <div class="method-row" role="group" aria-label="Método de recuperação">
          <button class="method-btn active" id="btn-email" onclick="setMethod('email')" type="button">
            <span class="material-symbols-outlined">email</span>
            E-mail
          </button>
          <button class="method-btn" id="btn-sms" onclick="setMethod('sms')" type="button">
            <span class="material-symbols-outlined">phone_android</span>
            SMS
          </button>
        </div>

        <form class="form" id="recovery-form" novalidate>
          <!-- novalidate desativa validação nativa do browser —
               usamos nossa própria validação customizada no JS -->

          <!-- 13/05/2026. Yago: Campo de e-mail — visível por padrão,
               oculto quando usuário seleciona SMS -->
          <div class="field" id="email-field">
            <label for="input-email">Endereço de e-mail</label>
            <div class="input-row">
              <span class="material-symbols-outlined input-ico">email</span>
              <input
                type="email"
                id="input-email"
                placeholder="seu@email.com"
                autocomplete="email"
                inputmode="email"
              />
            </div>
            <span class="hint">Use o e-mail cadastrado na sua conta StudyShare.</span>
          </div>

          <!-- 13/05/2026. Yago: Campo de telefone — oculto por padrão,
               exibido pelo JS quando usuário seleciona SMS -->
          <div class="field" id="phone-field">
            <label for="input-phone">Número de celular</label>
            <div class="input-row">
              <span class="material-symbols-outlined input-ico">phone_android</span>
              <input
                type="tel"
                id="input-phone"
                placeholder="(00) 00000-0000"
                autocomplete="tel"
                inputmode="tel"
              />
            </div>
            <span class="hint">Número com DDD cadastrado na sua conta.</span>
          </div>

          <!-- 13/05/2026. Yago: Botão de envio — o JS troca ícone+texto
               por spinner durante o carregamento e desabilita o botão -->
          <button type="submit" class="btn-submit" id="btn-submit">
            <div class="spinner" id="spinner"></div>
            <span class="material-symbols-outlined" id="btn-icon">send</span>
            <span id="btn-label">Enviar senha provisória</span>
          </button>

        </form>

        <!-- 13/05/2026. Yago: Divisor visual com texto "ou" -->
        <div class="divider">
          <div class="div-line"></div>
          <span class="div-text">ou</span>
          <div class="div-line"></div>
        </div>

        <!-- 13/05/2026. Yago: Link alternativo para quem lembrou da senha -->
        <div class="card-footer">
          Lembrou sua senha? <a href="login.php">Entrar agora</a>
        </div>

      </div><!-- /form-state -->

      <!-- ══════════════════════════════════════════
           SUCCESS STATE — estado de confirmação
           13/05/2026. Yago: Exibido após envio bem-sucedido.
           O JS preenche #success-target com o e-mail ou telefone
           digitado pelo usuário antes de mostrar este bloco.
      ══════════════════════════════════════════ -->
      <div class="success-state" id="success-state">

        <!-- 13/05/2026. Yago: Ícone de check em círculo verde — feedback visual positivo -->
        <div class="success-circle">
          <span class="material-symbols-outlined">check_circle</span>
        </div>

        <h2 class="success-title">Senha enviada!</h2>

        <!-- 13/05/2026. Yago: Mensagem dinâmica — o JS altera o innerHTML
             dependendo do canal (e-mail ou SMS) e insere o destino -->
        <p class="success-msg" id="success-msg">
          Uma senha provisória foi enviada para <strong id="success-target"></strong>.
        </p>

        <a href="login.php" class="btn-outline">
          <span class="material-symbols-outlined">arrow_back</span>
          Voltar para o login
        </a>

        <!-- 13/05/2026. Yago: Opção de reenvio — chama resetForm() que
             limpa os campos e volta ao estado do formulário -->
        <p class="resend-note">
          Não recebeu? <a onclick="resetForm()">Reenviar agora</a>
        </p>

      </div><!-- /success-state -->

    </div><!-- /card-body -->
  </div><!-- /card -->

  <script>
    'use strict';

    // 13/05/2026. Yago: Controla qual método está ativo no momento — email ou sms.
    // Variável global necessária porque várias funções precisam saber o estado atual.
    let currentMethod = 'email';

    /* ============================================================
       13/05/2026. Yago: ALTERNA O MÉTODO DE ENVIO (E-MAIL / SMS)
       Chamada pelos botões do seletor via onclick.
       Faz três coisas: atualiza os botões visualmente, mostra/oculta
       o campo correto e foca o input para o usuário já poder digitar.
    ============================================================ */
    function setMethod(m) {
      currentMethod = m;

      // 13/05/2026. Yago: toggle adiciona/remove a classe 'active' dependendo
      // se o botão é o que foi clicado (true) ou não (false)
      document.getElementById('btn-email').classList.toggle('active', m === 'email');
      document.getElementById('btn-sms').classList.toggle('active',   m === 'sms');

      // 13/05/2026. Yago: Mostra o campo do método ativo e oculta o outro
      document.getElementById('email-field').style.display = m === 'email' ? 'flex' : 'none';
      document.getElementById('phone-field').style.display = m === 'sms'   ? 'flex' : 'none';

      clearAlert(); // limpa qualquer erro anterior ao trocar de método

      // 13/05/2026. Yago: Foca automaticamente o campo correto — melhora a UX
      document.getElementById(m === 'email' ? 'input-email' : 'input-phone').focus();
    }

    /* ============================================================
       13/05/2026. Yago: MÁSCARA DE TELEFONE EM TEMPO REAL
       Formata o número enquanto o usuário digita: (11) 99999-9999
       Remove tudo que não é dígito, limita em 11 dígitos e
       vai aplicando a máscara conforme o tamanho cresce.
    ============================================================ */
    document.getElementById('input-phone').addEventListener('input', function () {
      let v = this.value.replace(/\D/g, '').substring(0, 11); // só dígitos, max 11

      if      (v.length > 6) v = `(${v.slice(0,2)}) ${v.slice(2,7)}-${v.slice(7)}`;
      else if (v.length > 2) v = `(${v.slice(0,2)}) ${v.slice(2)}`;
      else if (v.length)     v = `(${v}`; // ainda digitando o DDD

      this.value = v;
    });

    /* ============================================================
       13/05/2026. Yago: VALIDAÇÃO DO CAMPO ATIVO
       Valida só o campo que está visível no momento.
       Regex de e-mail cobre os casos mais comuns sem ser muito restritiva.
       Telefone aceita 10 dígitos (fixo+DDD) ou 11 (celular+DDD).
    ============================================================ */
    function validate() {
      if (currentMethod === 'email')
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
          document.getElementById('input-email').value.trim()
        );

      // 13/05/2026. Yago: Remove máscara e conta só os dígitos para validar
      return document.getElementById('input-phone').value.replace(/\D/g, '').length >= 10;
    }

    /* ============================================================
       13/05/2026. Yago: FEEDBACK VISUAL DE ERRO NO INPUT
       Aplica borda vermelha no campo inválido e foca ele.
       Remove automaticamente após 2.2 segundos — tempo suficiente
       para o usuário ver o erro mas sem deixar o visual poluído.
    ============================================================ */
    function flashError(id) {
      const el = document.getElementById(id);
      el.classList.add('has-error');
      el.focus();
      setTimeout(() => el.classList.remove('has-error'), 2200);
    }

    /* ============================================================
       13/05/2026. Yago: EXIBE / OCULTA O BANNER DE ALERTA
       showAlert() muda a classe do banner para is-error que tem
       display:flex no CSS — isso exibe o elemento.
       clearAlert() remove a classe, voltando para display:none.
    ============================================================ */
    function showAlert(msg) {
      document.getElementById('alert-msg').textContent = msg;
      document.getElementById('alert-box').className = 'alert is-error';
    }

    function clearAlert() {
      document.getElementById('alert-box').className = 'alert';
    }

    /* ============================================================
       13/05/2026. Yago: CONTROLA O ESTADO VISUAL DO BOTÃO DE ENVIO
       Durante o loading: desabilita o botão, troca ícone por spinner
       e muda o texto para "Enviando…"
       Depois do loading: restaura tudo ao estado original.
    ============================================================ */
    function setLoading(on) {
      document.getElementById('btn-submit').disabled    = on;
      document.getElementById('spinner').style.display  = on ? 'block'     : 'none';
      document.getElementById('btn-icon').style.display = on ? 'none'      : 'inline-block';
      document.getElementById('btn-label').textContent  = on ? 'Enviando…' : 'Enviar senha provisória';
    }

    /* ============================================================
       13/05/2026. Yago: TRANSIÇÃO PARA O ESTADO DE SUCESSO
       Oculta o formulário e exibe o bloco de confirmação.
       Preenche a mensagem com o canal correto (e-mail vs SMS)
       e insere o destino digitado pelo usuário.
    ============================================================ */
    function showSuccess(target) {
      document.getElementById('form-state').style.display    = 'none';
      document.getElementById('success-state').style.display = 'flex';
      document.getElementById('success-target').textContent  = target;

      // 13/05/2026. Yago: Mensagem diferente para SMS — menciona "por SMS"
      if (currentMethod === 'sms')
        document.getElementById('success-msg').innerHTML =
          `Uma senha provisória foi enviada por SMS para <strong>${target}</strong>.`;
    }

    /* ============================================================
       13/05/2026. Yago: RESETA O CARD PARA O ESTADO INICIAL
       Chamado pelo link "Reenviar agora" — oculta o sucesso,
       mostra o formulário, limpa os campos e tira qualquer alerta.
    ============================================================ */
    function resetForm() {
      document.getElementById('success-state').style.display = 'none';
      document.getElementById('form-state').style.display    = 'block';
      document.getElementById('input-email').value = '';
      document.getElementById('input-phone').value = '';
      clearAlert();
      setMethod(currentMethod);
    }

    /* ============================================================
       13/05/2026. Yago: SUBMIT DO FORMULÁRIO — FLUXO PRINCIPAL
       1. Bloqueia o submit padrão do HTML (que recarregaria a página)
       2. Valida o campo ativo — mostra erro se inválido
       3. Ativa o estado de loading no botão
       4. Faz POST para o próprio arquivo PHP com JSON no body
       5. O PHP detecta o POST+JSON e processa como API (Section 8)
       6. Com base na resposta: mostra sucesso ou mensagem de erro
       7. Sempre desativa o loading no finally (sucesso ou falha)
    ============================================================ */
    document.getElementById('recovery-form').addEventListener('submit', async function (e) {
      e.preventDefault(); // impede reload da página
      clearAlert();

      const inputId = currentMethod === 'email' ? 'input-email' : 'input-phone';

      // 13/05/2026. Yago: Validação antes de qualquer requisição
      if (!validate()) {
        flashError(inputId);
        showAlert(
          currentMethod === 'email'
            ? 'Informe um endereço de e-mail válido.'
            : 'Informe um número de celular válido com DDD.'
        );
        return; // para aqui — não faz a requisição
      }

      setLoading(true);

      try {
        // 13/05/2026. Yago: Monta o body da requisição com base no canal ativo
        const body = currentMethod === 'email'
          ? { canal: 'email',  email:    document.getElementById('input-email').value.trim() }
          : { canal: 'sms',    telefone: document.getElementById('input-phone').value.trim() };

        // 13/05/2026. Yago: POST para o próprio arquivo PHP.
        // window.location.pathname retorna o caminho atual — funciona em
        // qualquer pasta sem precisar hardcodar o nome do arquivo.
        const res = await fetch(window.location.pathname, {
          method : 'POST',
          headers: { 'Content-Type': 'application/json' },
          body   : JSON.stringify(body),
        });

        const data = await res.json();

        if (res.ok && data.ok) {
          // 13/05/2026. Yago: Pega o valor digitado (e-mail ou telefone)
          // do body para mostrar na mensagem de sucesso.
          // Object.values(body) retorna [canal, valor] — [1] pega o segundo item.
          showSuccess(Object.values(body)[1]);
        } else {
          // 13/05/2026. Yago: Usa a mensagem de erro que veio do PHP
          // ou uma mensagem genérica se o PHP não retornou nada
          showAlert(data.error || 'Ocorreu um erro inesperado. Tente novamente.');
        }

      } catch (err) {
        // 13/05/2026. Yago: Erro de rede — servidor offline, sem internet etc.
        showAlert('Erro de conexão. Verifique sua internet e tente novamente.');
        console.error('[StudyShare] Erro na requisição:', err);
      } finally {
        // 13/05/2026. Yago: finally sempre executa — garante que o botão
        // seja reativado mesmo se der erro, evitando que fique travado
        setLoading(false);
      }
    });
  </script>

</body>
</html>

