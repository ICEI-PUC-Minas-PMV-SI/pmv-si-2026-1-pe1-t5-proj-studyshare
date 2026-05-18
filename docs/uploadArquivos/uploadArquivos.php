<?php
/* ============================================================
   StudyShare — Tela de Upload de Materiais
   Arquivo: uploadMaterial.php
   Autor: Yago Mata
   Data: 18/05/2026

   18/05/2026. Yago: LEIA ANTES DE QUALQUER COISA — CONTEXTO DO PROJETO
   -----------------------------------------------------------------------
   Esse arquivo é parte do projeto acadêmico StudyShare, desenvolvido
   para a disciplina de Desenvolvimento Web na PUC Minas.

   Requisito funcional atendido:
   RF-01 — O sistema deve permitir que o estudante faça upload de
          arquivos nos formatos PDF, resumos (texto) e slides.

   Como é um projeto de faculdade e não um sistema real em produção,
   algumas decisões foram tomadas de forma diferente do que seria feito
   num sistema comercial de verdade. Vou explicar cada uma delas aqui:

   1. POR QUE LOCALHOST / XAMPP?
      Usamos XAMPP (ou WAMP, tanto faz) que é um servidor Apache + PHP
      rodando direto no computador. Não precisa pagar hospedagem para
      testar — é o ambiente ideal para projeto acadêmico.

   2. POR QUE SALVAR EM PASTA EM VEZ DE BANCO DE DADOS?
      Os arquivos físicos vão para uma pasta `uploads/` do servidor.
      Os metadados (título, autor, disciplina, etc) seriam salvos em
      banco em produção, mas aqui guardamos num arquivo JSON simples
      (uploads/metadados.json) para o projeto rodar sem precisar
      configurar MySQL. Quem for fazer a evolução, é só trocar a função
      salvar_metadados() para inserir no banco.

   3. POR QUE LIMITE DE 20MB POR ARQUIVO?
      O PHP por padrão limita uploads a 2MB no php.ini (upload_max_filesize
      e post_max_size). 20MB é um meio-termo razoável: cobre PDFs grandes
      e apresentações de slides, sem ocupar muito espaço no servidor.
      Para arquivos maiores, ajustar o php.ini.

   4. POR QUE RENOMEAR O ARQUIVO NO SERVIDOR?
      Nunca confiar no nome do arquivo enviado pelo usuário — pode
      conter caracteres maliciosos, caracteres de path traversal (../),
      ou simplesmente conflitar com outro arquivo já existente.
      Geramos um nome único usando hash + timestamp e guardamos o nome
      original nos metadados para exibir ao usuário.

   5. POR QUE VALIDAR TIPO PELO MIME E NÃO PELA EXTENSÃO?
      Extensão é só texto — qualquer um renomeia "virus.exe" para
      "trabalho.pdf". O MIME type é detectado lendo os primeiros bytes
      do arquivo (magic numbers), então é confiável. O PHP tem o
      finfo_file() nativo justamente para isso.

   -----------------------------------------------------------------------
   COMO TESTAR (passo a passo):

   COM XAMPP:
     1. Inicie o Apache pelo XAMPP Control Panel
     2. Coloque este arquivo em: C:\xampp\htdocs\studyshare\
     3. Crie a pasta de uploads: C:\xampp\htdocs\studyshare\uploads\
     4. Acesse: http://localhost/studyshare/uploadMaterial.php

   COM WAMP:
     1. Inicie o WampServer
     2. Coloque os arquivos em: C:\wamp64\www\studyshare\
     3. Crie a pasta uploads/ dentro
     4. Acesse: http://localhost/studyshare/uploadMaterial.php

   ESTRUTURA DE PASTAS NECESSÁRIA:
     studyshare/
       ├── uploadMaterial.php   ← este arquivo
       └── uploads/             ← pasta onde os arquivos serão salvos
             └── metadados.json ← criado automaticamente no primeiro upload

   AJUSTE NO php.ini (opcional — só se quiser uploads acima de 8MB):
     - upload_max_filesize = 20M
     - post_max_size = 25M
     - max_execution_time = 60
============================================================ */


/* ============================================================
   SECTION 1 — CONFIGURAÇÃO GERAL
   -----------------------------------------------------------------------
   18/05/2026. Yago: Centralizei todas as configurações aqui no topo.
   Quem for adaptar o projeto para outro contexto só precisa mexer
   nessa seção — limites de tamanho, tipos aceitos, caminhos etc.
============================================================ */

// 18/05/2026. Yago: Pasta onde os arquivos serão fisicamente salvos.
// O __DIR__ retorna o caminho do diretório do arquivo atual, então
// funciona em qualquer ambiente sem precisar hardcodar o caminho absoluto.
define('PASTA_UPLOADS', __DIR__ . '/uploads');

// 18/05/2026. Yago: Arquivo JSON que guarda os metadados de cada upload
// (título, autor, disciplina, etc). Em produção isso seria uma tabela no banco.
define('ARQUIVO_METADADOS', PASTA_UPLOADS . '/metadados.json');

// 18/05/2026. Yago: Tamanho máximo permitido por arquivo (20 megabytes).
// Em bytes porque é a unidade que o $_FILES['size'] retorna.
define('TAMANHO_MAX_BYTES', 20 * 1024 * 1024);

// 18/05/2026. Yago: Mapeamento de MIME types permitidos para extensão.
// Validamos o MIME (que é confiável) e usamos a extensão só para
// dar um nome amigável ao arquivo no servidor.
// RF-01 pede: PDF, resumos (texto), slides
const TIPOS_PERMITIDOS = [
    // PDF
    'application/pdf' => 'pdf',

    // Resumos (texto)
    'text/plain'      => 'txt',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',

    // Slides
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
];

// 18/05/2026. Yago: Lista de disciplinas que aparecem no select da tela.
// Em produção isso viria do banco de dados (tabela de disciplinas).
// Aqui mantive estático para o projeto rodar sem dependências.
const DISCIPLINAS_DISPONIVEIS = [
    'Algoritmos e Estruturas de Dados',
    'Banco de Dados',
    'Cálculo I',
    'Cálculo II',
    'Engenharia de Software',
    'Front-end',
    'Inteligência Artificial',
    'Programação Web',
    'Redes de Computadores',
    'Sistemas Operacionais',
];


/* ============================================================
   SECTION 2 — RATE LIMITING (controle de uploads)
   -----------------------------------------------------------------------
   18/05/2026. Yago: Limita uploads para evitar abuso — sem isso, alguém
   poderia ficar enviando arquivos infinitamente e lotar o disco do servidor.
   10 uploads por sessão em 10 minutos é razoável para uso normal de estudante.
============================================================ */
session_start();

function checar_rate_limit(): bool {
    $agora  = time();
    $janela = 10 * 60; // 10 minutos
    $max    = 10;      // 10 uploads máximos na janela

    // 18/05/2026. Yago: Inicializa o contador na primeira tentativa
    if (!isset($_SESSION['upload_count'], $_SESSION['upload_inicio'])) {
        $_SESSION['upload_count']  = 0;
        $_SESSION['upload_inicio'] = $agora;
    }

    // 18/05/2026. Yago: Reseta a janela quando o tempo passa
    if ($agora - $_SESSION['upload_inicio'] > $janela) {
        $_SESSION['upload_count']  = 0;
        $_SESSION['upload_inicio'] = $agora;
    }

    $_SESSION['upload_count']++;
    return $_SESSION['upload_count'] <= $max;
}


/* ============================================================
   SECTION 3 — VALIDADORES
   -----------------------------------------------------------------------
   18/05/2026. Yago: Toda validação roda no servidor.
   O HTML5 e o JavaScript ajudam na experiência do usuário (mostrando
   feedback rápido), mas a validação que importa de verdade é essa aqui,
   porque o JS pode ser desabilitado ou contornado.
============================================================ */

// 18/05/2026. Yago: Detecta o MIME type real do arquivo lendo seus
// primeiros bytes (magic numbers). Mais confiável que checar pela extensão.
function detectar_mime(string $caminho_arquivo): string {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $caminho_arquivo);
    finfo_close($finfo);
    return $mime ?: '';
}

// 18/05/2026. Yago: Verifica se o MIME do arquivo está na lista de permitidos
function tipo_permitido(string $mime): bool {
    return isset(TIPOS_PERMITIDOS[$mime]);
}

// 18/05/2026. Yago: Validações de metadados — título e disciplina obrigatórios
function validar_titulo(string $titulo): bool {
    $titulo = trim($titulo);
    return strlen($titulo) >= 3 && strlen($titulo) <= 120;
}

function validar_disciplina(string $disciplina): bool {
    return in_array($disciplina, DISCIPLINAS_DISPONIVEIS, true);
}


/* ============================================================
   SECTION 4 — SANITIZAÇÃO DE NOMES DE ARQUIVO
   -----------------------------------------------------------------------
   18/05/2026. Yago: Nunca confiar no nome do arquivo enviado pelo usuário.
   Esse nome vai aparecer na interface, em links, possivelmente no banco —
   se contiver caracteres maliciosos ou de path traversal (../), pode
   abrir brecha de segurança.

   A estratégia é gerar um nome 100% novo para o arquivo físico no servidor
   (com hash) e guardar o nome original "limpo" nos metadados.
============================================================ */

// 18/05/2026. Yago: Gera um nome único para o arquivo no servidor.
// Formato: timestamp_hash.extensao
// Exemplo: 1715630000_a3f7c2b8.pdf
// Isso garante que dois uploads simultâneos nunca colidam.
function gerar_nome_arquivo(string $extensao): string {
    $timestamp = time();
    $hash      = bin2hex(random_bytes(4)); // 8 caracteres hex
    return "{$timestamp}_{$hash}.{$extensao}";
}

// 18/05/2026. Yago: Limpa o nome original do arquivo para guardar nos metadados.
// Remove caracteres perigosos mantendo legibilidade.
function limpar_nome_original(string $nome): string {
    // 18/05/2026. Yago: basename() remove qualquer tentativa de path traversal
    $nome = basename($nome);

    // 18/05/2026. Yago: Substitui caracteres que não são letra/número/ponto/hífen/underline
    $nome = preg_replace('/[^a-zA-Z0-9._\- ]/u', '', $nome);

    // 18/05/2026. Yago: Limita o tamanho para não estourar a interface
    return mb_substr($nome, 0, 100);
}


/* ============================================================
   SECTION 5 — PROCESSAMENTO DO UPLOAD
   -----------------------------------------------------------------------
   18/05/2026. Yago: Função principal que recebe o arquivo, valida,
   move para a pasta correta e retorna o nome gerado no servidor.
   Em caso de erro retorna null e deixa a mensagem no $_SESSION para
   o chamador ler.
============================================================ */
function processar_upload(array $arquivo): ?string {

    // 18/05/2026. Yago: Verifica se o upload chegou sem erro do PHP.
    // UPLOAD_ERR_OK = 0 = tudo certo. Outros valores indicam erros como
    // arquivo maior que o limite do php.ini, upload parcial etc.
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['ultimo_erro_upload'] = match ($arquivo['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o tamanho máximo permitido.',
            UPLOAD_ERR_PARTIAL                        => 'Upload incompleto. Tente novamente.',
            UPLOAD_ERR_NO_FILE                        => 'Nenhum arquivo foi enviado.',
            UPLOAD_ERR_NO_TMP_DIR                     => 'Erro no servidor (sem pasta temporária).',
            UPLOAD_ERR_CANT_WRITE                     => 'Erro no servidor (sem permissão de escrita).',
            default                                   => 'Erro desconhecido no upload.',
        };
        return null;
    }

    // 18/05/2026. Yago: Valida o tamanho. Pode parecer redundante com a checagem
    // do php.ini, mas se o arquivo de configuração tiver um limite maior que
    // nossa regra de negócio, essa checagem é a última barreira.
    if ($arquivo['size'] > TAMANHO_MAX_BYTES) {
        $_SESSION['ultimo_erro_upload'] = 'Arquivo maior que 20MB. Reduza o tamanho e tente novamente.';
        return null;
    }

    // 18/05/2026. Yago: Detecta o MIME real do arquivo e valida
    $mime = detectar_mime($arquivo['tmp_name']);
    if (!tipo_permitido($mime)) {
        $_SESSION['ultimo_erro_upload'] = 'Tipo de arquivo não permitido. Use PDF, TXT, DOC, DOCX, PPT ou PPTX.';
        return null;
    }

    // 18/05/2026. Yago: Garante que a pasta de uploads existe
    if (!is_dir(PASTA_UPLOADS)) {
        // 18/05/2026. Yago: 0755 = dono pode ler/escrever/executar, outros só ler/executar.
        // O `true` no terceiro parâmetro cria pastas pai recursivamente se necessário.
        mkdir(PASTA_UPLOADS, 0755, true);
    }

    // 18/05/2026. Yago: Gera o nome final do arquivo no servidor
    $extensao    = TIPOS_PERMITIDOS[$mime];
    $nome_final  = gerar_nome_arquivo($extensao);
    $caminho_final = PASTA_UPLOADS . '/' . $nome_final;

    // 18/05/2026. Yago: Move o arquivo da pasta temporária para a definitiva.
    // move_uploaded_file() é específica para isso e tem validações de segurança
    // que rename() ou copy() não têm.
    if (!move_uploaded_file($arquivo['tmp_name'], $caminho_final)) {
        $_SESSION['ultimo_erro_upload'] = 'Falha ao salvar o arquivo no servidor.';
        return null;
    }

    return $nome_final;
}


/* ============================================================
   SECTION 6 — PERSISTÊNCIA DE METADADOS
   -----------------------------------------------------------------------
   18/05/2026. Yago: Salva os metadados do upload num arquivo JSON.
   Em produção isso seria um INSERT no banco de dados — aqui simplificamos
   para não precisar de MySQL/Postgres rodando.

   O JSON guarda uma lista de objetos, cada um representando um upload:
   {
     "id": "...",
     "titulo": "...",
     "descricao": "...",
     "disciplina": "...",
     "nome_original": "...",
     "nome_servidor": "...",
     "tipo": "pdf",
     "tamanho": 1234567,
     "data_upload": "2026-05-13 22:30:00"
   }
============================================================ */

function salvar_metadados(array $dados): bool {
    // 18/05/2026. Yago: Lê o JSON existente ou começa um array vazio
    $existentes = [];
    if (file_exists(ARQUIVO_METADADOS)) {
        $conteudo = file_get_contents(ARQUIVO_METADADOS);
        $existentes = json_decode($conteudo, true) ?: [];
    }

    // 18/05/2026. Yago: Adiciona o novo registro no final da lista
    $existentes[] = $dados;

    // 18/05/2026. Yago: Salva com formatação legível (JSON_PRETTY_PRINT)
    // e suporte a acentos (JSON_UNESCAPED_UNICODE).
    // file_put_contents com LOCK_EX evita corrupção se dois uploads
    // tentarem gravar ao mesmo tempo.
    $json = json_encode($existentes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(ARQUIVO_METADADOS, $json, LOCK_EX) !== false;
}


/* ============================================================
   SECTION 7 — ROTEADOR DA API
   -----------------------------------------------------------------------
   18/05/2026. Yago: Mesma estratégia da tela de recuperação de senha —
   o mesmo arquivo PHP serve dois propósitos:

   → GET: navegador abrindo a página → entrega o HTML (Section 8)
   → POST com multipart/form-data: o JavaScript está enviando o arquivo
     → processa o upload e retorna JSON

   Não usamos JSON puro aqui (como na recuperação) porque arquivos
   precisam ser enviados como multipart/form-data — é o jeito padrão
   de mandar binário via HTTP. O JS usa FormData para isso.
============================================================ */

$method       = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$content_type = $_SERVER['CONTENT_TYPE']   ?? '';
$is_api       = $method === 'POST' && str_contains($content_type, 'multipart/form-data');

if ($is_api) {

    // 18/05/2026. Yago: Toda resposta da API é JSON
    header('Content-Type: application/json; charset=utf-8');

    // 18/05/2026. Yago: Bloqueia spam de uploads
    if (!checar_rate_limit()) {
        http_response_code(429);
        echo json_encode(['error' => 'Muitas tentativas de upload. Aguarde 10 minutos.']);
        exit;
    }

    // 18/05/2026. Yago: Verifica se veio arquivo na requisição
    if (!isset($_FILES['arquivo'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nenhum arquivo foi recebido.']);
        exit;
    }

    // 18/05/2026. Yago: Coleta os metadados enviados junto com o arquivo
    $titulo     = trim($_POST['titulo']     ?? '');
    $descricao  = trim($_POST['descricao']  ?? '');
    $disciplina = trim($_POST['disciplina'] ?? '');

    // 18/05/2026. Yago: Validação dos campos de texto
    if (!validar_titulo($titulo)) {
        http_response_code(400);
        echo json_encode(['error' => 'Informe um título entre 3 e 120 caracteres.']);
        exit;
    }

    if (!validar_disciplina($disciplina)) {
        http_response_code(400);
        echo json_encode(['error' => 'Selecione uma disciplina válida.']);
        exit;
    }

    // 18/05/2026. Yago: Processa o upload do arquivo físico
    $nome_servidor = processar_upload($_FILES['arquivo']);

    if (!$nome_servidor) {
        // 18/05/2026. Yago: Recupera a mensagem específica do erro
        $erro = $_SESSION['ultimo_erro_upload'] ?? 'Falha no upload.';
        unset($_SESSION['ultimo_erro_upload']);
        http_response_code(400);
        echo json_encode(['error' => $erro]);
        exit;
    }

    // 18/05/2026. Yago: Monta o registro de metadados para persistir
    $mime          = detectar_mime(PASTA_UPLOADS . '/' . $nome_servidor);
    $nome_original = limpar_nome_original($_FILES['arquivo']['name']);

    $dados = [
        'id'            => uniqid('mat_', true),
        'titulo'        => $titulo,
        'descricao'     => mb_substr($descricao, 0, 500), // limita descrição em 500 chars
        'disciplina'    => $disciplina,
        'nome_original' => $nome_original,
        'nome_servidor' => $nome_servidor,
        'tipo'          => TIPOS_PERMITIDOS[$mime],
        'tamanho'       => $_FILES['arquivo']['size'],
        'data_upload'   => date('Y-m-d H:i:s'),
    ];

    if (!salvar_metadados($dados)) {
        http_response_code(500);
        echo json_encode(['error' => 'Falha ao salvar os dados do material.']);
        exit;
    }

    error_log('[StudyShare] Upload realizado: ' . $titulo . ' (' . $nome_servidor . ')');

    echo json_encode([
        'ok'     => true,
        'titulo' => $titulo,
        'tipo'   => $dados['tipo'],
    ]);
    exit;
}


/* ============================================================
   SECTION 8 — FRONTEND HTML
   -----------------------------------------------------------------------
   18/05/2026. Yago: A partir daqui é o HTML que o usuário vê.
   Só chega aqui em requisições GET.

   Segue o design system do projeto (template.md):
   - Fonte Inter
   - Cor primária #004AAD
   - Header navy #002F87 (mesma cor da sidebar do Figma)
   - Ícones Material Symbols Outlined
============================================================ */
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Upload de Material — StudyShare</title>

  <!-- 18/05/2026. Yago: Fonte Inter — definida no design system -->
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>

  <!-- 18/05/2026. Yago: Ícones Material Symbols -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>

  <style>
    /* 18/05/2026. Yago: Reset básico do navegador */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ============================================================
       18/05/2026. Yago: CLASSE BASE DOS ÍCONES MATERIAL SYMBOLS
       Declarada explicitamente para garantir renderização mesmo se
       o Google Fonts demorar pra carregar — sem isso os ícones
       aparecem como texto puro ("home", "upload" etc).
    ============================================================ */
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

    /* ============================================================
       18/05/2026. Yago: TOKENS DE DESIGN
       Variáveis CSS centralizando todas as cores do design system.
    ============================================================ */
    :root {
      --blue       : #004AAD; /* Cor primária — Royal Blue                   */
      --blue-hover : #0057CC; /* Hover do botão                              */
      --blue-light : #E8F0FF; /* Fundo do dropzone em hover                  */
      --blue-glow  : rgba(0,74,173,.16); /* Sombra de foco nos inputs        */
      --sidebar-bg : #002F87; /* Navy — header do card                       */
      --white      : #FFFFFF;
      --off-white  : #F5F7FA; /* Fundo da página e dos inputs                */
      --black      : #0F172A; /* Texto principal                             */
      --gray-200   : #E2E8F0; /* Bordas e divisores                          */
      --gray-300   : #CBD5E1; /* Borda do dropzone em estado inicial         */
      --gray-400   : #94A3B8; /* Placeholder e hints                         */
      --gray-500   : #64748B; /* Textos secundários                          */
      --gray-600   : #475569; /* Labels                                      */
      --success    : #16A34A; /* Verde — sucesso                             */
      --success-bg : #DCFCE7;
      --error      : #DC2626; /* Vermelho — erro                             */
      --font       : 'Inter', system-ui, sans-serif;
      --radius-sm  : 8px;
      --radius-lg  : 16px;
      --shadow     : 0 4px 24px rgba(0,0,0,.09), 0 1px 4px rgba(0,0,0,.05);
      --shadow-btn : 0 4px 14px rgba(0,74,173,.35);
      --tr         : .18s cubic-bezier(.4,0,.2,1);
    }

    /* 18/05/2026. Yago: Centraliza o card vertical e horizontalmente */
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
       18/05/2026. Yago: CARD PRINCIPAL
       Componente isolado seguindo padrão da tela de recuperação.
       Max-width maior (520px) porque essa tela tem mais campos.
    ============================================================ */
    .card {
      width: 100%;
      max-width: 520px;
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

    /* 18/05/2026. Yago: Header navy idêntico ao da recuperação de senha */
    .card-header {
      background: var(--sidebar-bg);
      padding: 22px 28px;
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .header-icon {
      width: 42px; height: 42px; min-width: 42px;
      background: rgba(255,255,255,.13);
      border-radius: var(--radius-sm);
      display: flex; align-items: center; justify-content: center;
    }
    .header-icon .material-symbols-outlined {
      font-size: 22px; color: var(--white);
      font-variation-settings: 'FILL' 0, 'wght' 300;
    }
    .header-text h1 { font-size:17px; font-weight:700; color:var(--white); letter-spacing:-.2px; }
    .header-text p  { font-size:13px; color:rgba(255,255,255,.6); margin-top:3px; }

    .card-body { padding: 28px 28px 26px; }

    /* 18/05/2026. Yago: Link de voltar */
    .back-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 13px; font-weight: 500; color: var(--gray-500);
      text-decoration: none; margin-bottom: 22px;
      transition: color var(--tr); line-height: 1;
    }
    .back-link:hover { color: var(--blue); }
    .back-link .material-symbols-outlined { font-size: 16px; color: currentColor; }

    /* 18/05/2026. Yago: Banner de feedback (erro) */
    .alert {
      display: none;
      align-items: flex-start; gap: 10px;
      padding: 12px 14px; border-radius: var(--radius-sm);
      margin-bottom: 16px; font-size: 13px; line-height: 1.5;
      animation: fadeUp .2s ease both;
    }
    .alert.is-error {
      display: flex; background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA;
    }
    .alert .material-symbols-outlined {
      font-size: 18px; color: currentColor; margin-top: 1px;
      font-variation-settings: 'FILL' 1;
    }

    /* ============================================================
       18/05/2026. Yago: DROPZONE — área de drag & drop do arquivo
       É o destaque visual da tela. Borda tracejada para indicar
       que aceita drop, ícone grande de upload, e cor azul claro
       quando o usuário arrasta um arquivo por cima.
    ============================================================ */
    .dropzone {
      position: relative;
      border: 2px dashed var(--gray-300);
      border-radius: var(--radius-sm);
      padding: 32px 20px;
      text-align: center;
      cursor: pointer;
      background: var(--off-white);
      transition: border-color var(--tr), background var(--tr);
      margin-bottom: 16px;
    }

    /* 18/05/2026. Yago: Estado hover/drag — borda e fundo azuis */
    .dropzone:hover,
    .dropzone.is-dragging {
      border-color: var(--blue);
      background: var(--blue-light);
    }

    /* 18/05/2026. Yago: Estado quando o arquivo já foi selecionado —
       muda para verde para indicar sucesso na seleção */
    .dropzone.has-file {
      border-style: solid;
      border-color: var(--success);
      background: var(--success-bg);
    }

    /* 18/05/2026. Yago: Input file real fica escondido — o usuário
       interage com o dropzone visual que dispara o click() do input */
    .dropzone input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
    }

    .dropzone-icon {
      width: 56px; height: 56px;
      background: var(--white);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .dropzone-icon .material-symbols-outlined {
      font-size: 28px; color: var(--blue);
      font-variation-settings: 'FILL' 0, 'wght' 400;
    }

    .dropzone.has-file .dropzone-icon .material-symbols-outlined {
      color: var(--success);
      font-variation-settings: 'FILL' 1;
    }

    .dropzone-title { font-size: 14px; font-weight: 600; color: var(--black); margin-bottom: 4px; }
    .dropzone-sub   { font-size: 12px; color: var(--gray-500); line-height: 1.5; }
    .dropzone-formats { font-size: 11px; color: var(--gray-400); margin-top: 8px; }

    /* ============================================================
       18/05/2026. Yago: PREVIEW DO ARQUIVO SELECIONADO
       Exibido dentro do dropzone quando o usuário escolhe um arquivo.
       Mostra o nome, tamanho formatado e botão de remover.
    ============================================================ */
    .file-preview {
      display: none;
      align-items: center;
      gap: 12px;
      text-align: left;
    }
    .dropzone.has-file .file-preview { display: flex; }
    .dropzone.has-file .dropzone-empty { display: none; }

    .file-icon-box {
      width: 44px; height: 44px;
      background: var(--white);
      border-radius: var(--radius-sm);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .file-icon-box .material-symbols-outlined {
      font-size: 22px; color: var(--blue);
      font-variation-settings: 'FILL' 0;
    }

    .file-info { flex: 1; min-width: 0; }
    .file-name {
      font-size: 14px; font-weight: 600; color: var(--black);
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .file-meta { font-size: 12px; color: var(--gray-500); margin-top: 2px; }

    .file-remove {
      background: var(--white);
      border: none;
      width: 28px; height: 28px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      flex-shrink: 0;
      transition: background var(--tr);
      /* 18/05/2026. Yago: Sobe o z-index para ficar acima do input invisível */
      position: relative; z-index: 2;
    }
    .file-remove:hover { background: #FEE2E2; }
    .file-remove .material-symbols-outlined {
      font-size: 18px; color: var(--gray-500);
      pointer-events: none;
    }
    .file-remove:hover .material-symbols-outlined { color: var(--error); }

    /* 18/05/2026. Yago: Estilos compartilhados de campos de formulário */
    .form { display: flex; flex-direction: column; gap: 14px; }
    .field { display: flex; flex-direction: column; gap: 6px; }
    .field > label {
      font-size: 13px; font-weight: 500; color: var(--gray-600);
      display: flex; justify-content: space-between; align-items: center;
    }
    .field-counter { font-size: 11px; color: var(--gray-400); font-weight: 400; }

    .input-row { position: relative; display: flex; align-items: center; }
    .input-ico {
      position: absolute; left: 12px; font-size: 18px; width: 18px;
      text-align: center; color: var(--gray-400);
      font-variation-settings: 'FILL' 0;
      transition: color var(--tr); line-height: 1;
    }

    .input-row input,
    .input-row select,
    .field textarea {
      width: 100%;
      padding: 11px 14px 11px 42px;
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm);
      font-family: var(--font); font-size: 14px;
      color: var(--black); background: var(--off-white);
      outline: none;
      transition: border-color var(--tr), box-shadow var(--tr), background var(--tr);
    }

    .input-row input::placeholder,
    .field textarea::placeholder { color: var(--gray-400); }

    .input-row input:focus,
    .input-row select:focus,
    .field textarea:focus {
      border-color: var(--blue);
      background: var(--white);
      box-shadow: 0 0 0 3px var(--blue-glow);
    }
    .input-row:focus-within .input-ico { color: var(--blue); }

    /* 18/05/2026. Yago: Select estilizado — remove a aparência padrão do browser */
    .input-row select {
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8' fill='none'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%2394A3B8' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 36px; /* espaço para a seta */
    }

    /* 18/05/2026. Yago: Textarea para descrição — sem padding-left dos inputs */
    .field textarea {
      padding: 11px 14px;
      resize: vertical;
      min-height: 80px;
      font-family: var(--font);
      line-height: 1.5;
    }

    .input-row input.has-error,
    .input-row select.has-error,
    .field textarea.has-error {
      border-color: var(--error);
      box-shadow: 0 0 0 3px rgba(220,38,38,.12);
    }

    .hint { font-size: 12px; color: var(--gray-400); line-height: 1.5; }

    /* ============================================================
       18/05/2026. Yago: BARRA DE PROGRESSO DO UPLOAD
       Exibida durante o envio do arquivo — preenche de 0 a 100%
       conforme o XMLHttpRequest dispara o evento 'progress'.
    ============================================================ */
    .progress-wrap {
      display: none;
      margin-top: 4px;
      padding: 12px 14px;
      background: var(--blue-light);
      border-radius: var(--radius-sm);
      border: 1px solid rgba(0,74,173,.15);
    }
    .progress-wrap.is-visible { display: block; }

    .progress-info {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 12px; color: var(--gray-600); margin-bottom: 8px;
    }
    .progress-info strong { color: var(--blue); font-weight: 600; }

    .progress-bar {
      height: 6px;
      background: var(--gray-200);
      border-radius: 3px;
      overflow: hidden;
    }
    .progress-bar-fill {
      height: 100%;
      background: var(--blue);
      border-radius: 3px;
      width: 0%;
      transition: width .2s ease;
    }

    /* 18/05/2026. Yago: Botão de envio — mesmo padrão da recuperação */
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
    .btn-submit:active { transform: translateY(0); }
    .btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    .spinner {
      width: 16px; height: 16px; flex-shrink: 0;
      border: 2px solid rgba(255,255,255,.35);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .7s linear infinite;
      display: none;
    }
    @keyframes spin   { to { transform: rotate(360deg); } }
    @keyframes fadeUp { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

    /* ============================================================
       18/05/2026. Yago: ESTADO DE SUCESSO
       Substitui o formulário após o upload concluído com sucesso.
    ============================================================ */
    .success-state {
      display: none;
      flex-direction: column; align-items: center;
      text-align: center; padding: 8px 0 4px;
      animation: fadeUp .3s ease both;
    }
    .success-circle {
      width: 60px; height: 60px; background: var(--success-bg);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 16px;
    }
    .success-circle .material-symbols-outlined {
      font-size: 30px; color: var(--success);
      font-variation-settings: 'FILL' 1;
    }
    .success-title { font-size: 20px; font-weight: 700; color: var(--black); letter-spacing: -.3px; margin-bottom: 8px; }
    .success-msg   { font-size: 14px; color: var(--gray-500); line-height: 1.65; max-width: 320px; margin-bottom: 22px; }
    .success-msg strong { color: var(--black); }

    .success-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }

    .btn-outline {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 11px 22px;
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm);
      font-family: var(--font); font-size: 14px; font-weight: 500;
      color: var(--gray-600); background: var(--white);
      text-decoration: none; cursor: pointer; line-height: 1;
      transition: border-color var(--tr), color var(--tr);
    }
    .btn-outline:hover { border-color: var(--blue); color: var(--blue); }
    .btn-outline .material-symbols-outlined { font-size: 16px; color: currentColor; }

    .btn-filled {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 11px 22px;
      background: var(--blue); color: var(--white);
      border: none; border-radius: var(--radius-sm);
      font-family: var(--font); font-size: 14px; font-weight: 500;
      text-decoration: none; cursor: pointer; line-height: 1;
      transition: background var(--tr);
    }
    .btn-filled:hover { background: var(--blue-hover); }
    .btn-filled .material-symbols-outlined { font-size: 16px; color: var(--white); }
  </style>
</head>
<body>

  <!-- 18/05/2026. Yago: Card isolado seguindo o padrão da tela de recuperação.
       Será integrado ao shell completo (sidebar + topbar) nas outras telas. -->
  <div class="card">

    <!-- 18/05/2026. Yago: Header azul navy com ícone de upload -->
    <div class="card-header">
      <div class="header-icon">
        <span class="material-symbols-outlined">upload_file</span>
      </div>
      <div class="header-text">
        <h1>Novo material</h1>
        <p>Compartilhe seus arquivos com a comunidade</p>
      </div>
    </div>

    <div class="card-body">

      <!-- ══════════════════════════════════════════
           FORM STATE — estado padrão (formulário)
      ══════════════════════════════════════════ -->
      <div id="form-state">

        <a href="feed.php" class="back-link">
          <span class="material-symbols-outlined">arrow_back</span>
          Voltar para o feed
        </a>

        <!-- 18/05/2026. Yago: Banner de erro — exibido via JS quando algo falha -->
        <div class="alert" id="alert-box" role="alert">
          <span class="material-symbols-outlined">error</span>
          <span id="alert-msg"></span>
        </div>

        <form class="form" id="upload-form" novalidate>

          <!-- ============================================================
               18/05/2026. Yago: DROPZONE — área de drag & drop
               Aceita arrastar arquivo ou clicar para selecionar.
               O input file fica invisível por cima da área toda.
          ============================================================ -->
          <div class="dropzone" id="dropzone">

            <!-- Input file real — invisível mas funcional -->
            <input
              type="file"
              id="file-input"
              accept=".pdf,.txt,.doc,.docx,.ppt,.pptx,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
            />

            <!-- 18/05/2026. Yago: Estado vazio — mostra antes de selecionar arquivo -->
            <div class="dropzone-empty">
              <div class="dropzone-icon">
                <span class="material-symbols-outlined">cloud_upload</span>
              </div>
              <div class="dropzone-title">Arraste o arquivo aqui ou clique para selecionar</div>
              <div class="dropzone-sub">Tamanho máximo de 20MB por arquivo</div>
              <div class="dropzone-formats">Formatos aceitos: PDF, TXT, DOC, DOCX, PPT, PPTX</div>
            </div>

            <!-- 18/05/2026. Yago: Preview do arquivo — aparece após seleção -->
            <div class="file-preview">
              <div class="file-icon-box">
                <span class="material-symbols-outlined" id="file-icon">description</span>
              </div>
              <div class="file-info">
                <div class="file-name" id="file-name"></div>
                <div class="file-meta" id="file-meta"></div>
              </div>
              <button type="button" class="file-remove" id="file-remove" title="Remover arquivo">
                <span class="material-symbols-outlined">close</span>
              </button>
            </div>

          </div>

          <!-- 18/05/2026. Yago: Campo de título do material — obrigatório -->
          <div class="field">
            <label for="input-titulo">
              Título do material
              <span class="field-counter" id="titulo-counter">0/120</span>
            </label>
            <div class="input-row">
              <span class="material-symbols-outlined input-ico">title</span>
              <input
                type="text"
                id="input-titulo"
                placeholder="Ex: Resumo de Cálculo I — Limites"
                maxlength="120"
              />
            </div>
            <span class="hint">Entre 3 e 120 caracteres. Seja descritivo para outros estudantes encontrarem.</span>
          </div>

          <!-- 18/05/2026. Yago: Disciplina — select com lista pré-definida -->
          <div class="field">
            <label for="input-disciplina">Disciplina</label>
            <div class="input-row">
              <span class="material-symbols-outlined input-ico">school</span>
              <select id="input-disciplina">
                <option value="">Selecione a disciplina...</option>
                <?php
                // 18/05/2026. Yago: Renderiza dinamicamente as disciplinas
                // definidas em DISCIPLINAS_DISPONIVEIS (Section 1).
                // htmlspecialchars() evita injeção de HTML/JS via valores.
                foreach (DISCIPLINAS_DISPONIVEIS as $disciplina) {
                    $valor = htmlspecialchars($disciplina, ENT_QUOTES, 'UTF-8');
                    echo "<option value=\"{$valor}\">{$valor}</option>";
                }
                ?>
              </select>
            </div>
          </div>

          <!-- 18/05/2026. Yago: Descrição opcional — textarea com contador -->
          <div class="field">
            <label for="input-descricao">
              Descrição
              <span class="field-counter" id="descricao-counter">0/500</span>
            </label>
            <textarea
              id="input-descricao"
              placeholder="Conte um pouco sobre o conteúdo do material..."
              maxlength="500"
              rows="3"
            ></textarea>
            <span class="hint">Opcional — ajuda outros estudantes a entenderem o conteúdo.</span>
          </div>

          <!-- 18/05/2026. Yago: Barra de progresso — fica oculta até o upload começar -->
          <div class="progress-wrap" id="progress-wrap">
            <div class="progress-info">
              <span>Enviando arquivo...</span>
              <strong id="progress-percent">0%</strong>
            </div>
            <div class="progress-bar">
              <div class="progress-bar-fill" id="progress-fill"></div>
            </div>
          </div>

          <!-- 18/05/2026. Yago: Botão de envio — desabilitado durante o upload -->
          <button type="submit" class="btn-submit" id="btn-submit">
            <div class="spinner" id="spinner"></div>
            <span class="material-symbols-outlined" id="btn-icon">upload</span>
            <span id="btn-label">Publicar material</span>
          </button>

        </form>

      </div><!-- /form-state -->

      <!-- ══════════════════════════════════════════
           SUCCESS STATE — estado após upload concluído
      ══════════════════════════════════════════ -->
      <div class="success-state" id="success-state">
        <div class="success-circle">
          <span class="material-symbols-outlined">check_circle</span>
        </div>
        <h2 class="success-title">Material publicado!</h2>
        <p class="success-msg" id="success-msg">
          <strong id="success-titulo"></strong> foi enviado com sucesso e já está disponível no feed.
        </p>
        <div class="success-actions">
          <a href="feed.php" class="btn-outline">
            <span class="material-symbols-outlined">arrow_back</span>
            Ver no feed
          </a>
          <button type="button" class="btn-filled" onclick="resetForm()">
            <span class="material-symbols-outlined">add</span>
            Enviar outro
          </button>
        </div>
      </div>

    </div><!-- /card-body -->
  </div><!-- /card -->

  <script>
    'use strict';

    /* ============================================================
       18/05/2026. Yago: REFERÊNCIAS DOS ELEMENTOS DA TELA
       Pego todas as referências de uma vez para evitar
       getElementById() espalhado pelo código.
    ============================================================ */
    const dropzone        = document.getElementById('dropzone');
    const fileInput       = document.getElementById('file-input');
    const fileRemove      = document.getElementById('file-remove');
    const fileName        = document.getElementById('file-name');
    const fileMeta        = document.getElementById('file-meta');
    const fileIcon        = document.getElementById('file-icon');
    const inputTitulo     = document.getElementById('input-titulo');
    const inputDisciplina = document.getElementById('input-disciplina');
    const inputDescricao  = document.getElementById('input-descricao');
    const tituloCounter   = document.getElementById('titulo-counter');
    const descricaoCounter = document.getElementById('descricao-counter');
    const btnSubmit       = document.getElementById('btn-submit');
    const spinner         = document.getElementById('spinner');
    const btnIcon         = document.getElementById('btn-icon');
    const btnLabel        = document.getElementById('btn-label');
    const progressWrap    = document.getElementById('progress-wrap');
    const progressFill    = document.getElementById('progress-fill');
    const progressPercent = document.getElementById('progress-percent');
    const alertBox        = document.getElementById('alert-box');
    const alertMsg        = document.getElementById('alert-msg');
    const formState       = document.getElementById('form-state');
    const successState    = document.getElementById('success-state');
    const successTitulo   = document.getElementById('success-titulo');

    /* ============================================================
       18/05/2026. Yago: MAPA DE ÍCONES POR TIPO DE ARQUIVO
       Cada extensão tem um ícone do Material Symbols que combina
       com o tipo — PDF tem ícone vermelho de PDF, slides tem ícone
       de apresentação, etc. Melhora a usabilidade do preview.
    ============================================================ */
    const ICONES_POR_TIPO = {
      'pdf':  'picture_as_pdf',
      'txt':  'description',
      'doc':  'description',
      'docx': 'description',
      'ppt':  'co_present',
      'pptx': 'co_present',
    };

    /* ============================================================
       18/05/2026. Yago: FORMATA O TAMANHO DO ARQUIVO
       Converte bytes para uma string legível: 1234567 → "1.18 MB"
    ============================================================ */
    function formatarTamanho(bytes) {
      if (bytes < 1024) return bytes + ' B';
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
      return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    /* ============================================================
       18/05/2026. Yago: PEGA A EXTENSÃO DE UM NOME DE ARQUIVO
       "trabalho.final.pdf" → "pdf"
       Usado para escolher o ícone correto no preview.
    ============================================================ */
    function pegarExtensao(nome) {
      const partes = nome.split('.');
      return partes.length > 1 ? partes.pop().toLowerCase() : '';
    }

    /* ============================================================
       18/05/2026. Yago: TRATA A SELEÇÃO/MUDANÇA DE ARQUIVO
       Chamada tanto pelo clique no input file quanto pelo drag & drop.
       Atualiza o preview com nome, tamanho e ícone correto.
    ============================================================ */
    function tratarArquivoSelecionado(arquivo) {
      if (!arquivo) return;

      // 18/05/2026. Yago: Validação rápida de tamanho no client antes de enviar.
      // O servidor valida de novo — essa aqui é só para feedback imediato ao usuário.
      const limite = 20 * 1024 * 1024; // 20MB
      if (arquivo.size > limite) {
        showAlert('Arquivo maior que 20MB. Reduza o tamanho e tente novamente.');
        return;
      }

      // 18/05/2026. Yago: Atualiza o preview visual
      const extensao = pegarExtensao(arquivo.name);
      fileName.textContent = arquivo.name;
      fileMeta.textContent = `${extensao.toUpperCase()} · ${formatarTamanho(arquivo.size)}`;
      fileIcon.textContent = ICONES_POR_TIPO[extensao] || 'description';

      // 18/05/2026. Yago: Adiciona classe que muda o visual do dropzone
      // para o estado verde "arquivo selecionado"
      dropzone.classList.add('has-file');

      clearAlert();
    }

    // 18/05/2026. Yago: Escuta a seleção via clique no input file
    fileInput.addEventListener('change', function () {
      tratarArquivoSelecionado(this.files[0]);
    });

    // 18/05/2026. Yago: Remove o arquivo selecionado quando clica no X
    // stopPropagation() impede que o clique no botão abra o seletor de arquivo
    fileRemove.addEventListener('click', function (e) {
      e.stopPropagation();
      fileInput.value = '';
      dropzone.classList.remove('has-file');
    });

    /* ============================================================
       18/05/2026. Yago: DRAG & DROP NATIVO
       O HTML5 expõe eventos dragover, dragleave e drop direto no DOM.
       Precisamos prevenir o comportamento padrão do browser (que seria
       abrir o arquivo numa nova aba) para que o drop funcione.
    ============================================================ */
    ['dragenter', 'dragover'].forEach(evento => {
      dropzone.addEventListener(evento, function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('is-dragging');
      });
    });

    ['dragleave', 'drop'].forEach(evento => {
      dropzone.addEventListener(evento, function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('is-dragging');
      });
    });

    dropzone.addEventListener('drop', function (e) {
      const arquivos = e.dataTransfer.files;
      if (arquivos.length > 0) {
        // 18/05/2026. Yago: Atribui o arquivo dropado ao input file via DataTransfer
        // — assim o FormData consegue capturar normalmente no submit
        fileInput.files = arquivos;
        tratarArquivoSelecionado(arquivos[0]);
      }
    });

    /* ============================================================
       18/05/2026. Yago: CONTADORES DE CARACTERES NOS CAMPOS DE TEXTO
       Atualiza em tempo real conforme o usuário digita.
    ============================================================ */
    inputTitulo.addEventListener('input', function () {
      tituloCounter.textContent = `${this.value.length}/120`;
    });

    inputDescricao.addEventListener('input', function () {
      descricaoCounter.textContent = `${this.value.length}/500`;
    });

    /* ============================================================
       18/05/2026. Yago: FUNÇÕES UTILITÁRIAS DE FEEDBACK
       Mesmo padrão usado na tela de recuperação de senha.
    ============================================================ */
    function showAlert(msg) {
      alertMsg.textContent = msg;
      alertBox.className = 'alert is-error';
    }

    function clearAlert() {
      alertBox.className = 'alert';
    }

    function flashError(el) {
      el.classList.add('has-error');
      el.focus();
      setTimeout(() => el.classList.remove('has-error'), 2200);
    }

    function setLoading(on) {
      btnSubmit.disabled       = on;
      spinner.style.display    = on ? 'block'      : 'none';
      btnIcon.style.display    = on ? 'none'       : 'inline-block';
      btnLabel.textContent     = on ? 'Enviando…'  : 'Publicar material';
    }

    /* ============================================================
       18/05/2026. Yago: VALIDAÇÃO ANTES DO SUBMIT
       Verifica todos os campos obrigatórios antes de iniciar o upload.
       Retorna true se tudo válido, false se algo falhou (com flash).
    ============================================================ */
    function validarFormulario() {
      // 18/05/2026. Yago: Arquivo é obrigatório — sem ele não tem upload
      if (!fileInput.files || fileInput.files.length === 0) {
        showAlert('Selecione um arquivo para enviar.');
        return false;
      }

      // 18/05/2026. Yago: Título precisa ter entre 3 e 120 caracteres
      const titulo = inputTitulo.value.trim();
      if (titulo.length < 3 || titulo.length > 120) {
        showAlert('Informe um título entre 3 e 120 caracteres.');
        flashError(inputTitulo);
        return false;
      }

      // 18/05/2026. Yago: Disciplina precisa estar selecionada (valor não vazio)
      if (!inputDisciplina.value) {
        showAlert('Selecione uma disciplina.');
        flashError(inputDisciplina);
        return false;
      }

      return true;
    }

    /* ============================================================
       18/05/2026. Yago: TRANSIÇÃO PARA O ESTADO DE SUCESSO
       Oculta o formulário e mostra a tela de confirmação.
    ============================================================ */
    function showSuccess(titulo) {
      formState.style.display    = 'none';
      successState.style.display = 'flex';
      successTitulo.textContent  = titulo;
    }

    /* ============================================================
       18/05/2026. Yago: RESETA O FORMULÁRIO PARA NOVO UPLOAD
       Chamado pelo botão "Enviar outro" no estado de sucesso.
    ============================================================ */
    function resetForm() {
      // 18/05/2026. Yago: Volta para o estado do formulário
      successState.style.display = 'none';
      formState.style.display    = 'block';

      // 18/05/2026. Yago: Limpa todos os campos
      fileInput.value         = '';
      inputTitulo.value       = '';
      inputDisciplina.value   = '';
      inputDescricao.value    = '';
      dropzone.classList.remove('has-file');
      tituloCounter.textContent    = '0/120';
      descricaoCounter.textContent = '0/500';

      // 18/05/2026. Yago: Reseta barra de progresso
      progressWrap.classList.remove('is-visible');
      progressFill.style.width   = '0%';
      progressPercent.textContent = '0%';

      clearAlert();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ============================================================
       18/05/2026. Yago: SUBMIT DO FORMULÁRIO — FLUXO PRINCIPAL
       Usa XMLHttpRequest em vez de fetch() porque XHR oferece evento
       de progresso (xhr.upload.onprogress) que o fetch não tem nativo.
       Isso permite mostrar a barra de progresso em tempo real.
    ============================================================ */
    document.getElementById('upload-form').addEventListener('submit', function (e) {
      e.preventDefault();
      clearAlert();

      if (!validarFormulario()) return;

      // 18/05/2026. Yago: FormData é a forma padrão de enviar arquivos via HTTP.
      // O browser monta o multipart/form-data automaticamente.
      const formData = new FormData();
      formData.append('arquivo',    fileInput.files[0]);
      formData.append('titulo',     inputTitulo.value.trim());
      formData.append('disciplina', inputDisciplina.value);
      formData.append('descricao',  inputDescricao.value.trim());

      // 18/05/2026. Yago: Cria o XHR e configura os eventos
      const xhr = new XMLHttpRequest();
      xhr.open('POST', window.location.pathname);

      // 18/05/2026. Yago: Mostra UI de loading e barra de progresso
      setLoading(true);
      progressWrap.classList.add('is-visible');

      // 18/05/2026. Yago: Evento de progresso — atualiza a barra conforme
      // os bytes são enviados. Só funciona se o servidor suportar
      // upload progressivo, mas funciona na grande maioria dos casos.
      xhr.upload.onprogress = function (event) {
        if (event.lengthComputable) {
          const percent = Math.round((event.loaded / event.total) * 100);
          progressFill.style.width   = percent + '%';
          progressPercent.textContent = percent + '%';
        }
      };

      // 18/05/2026. Yago: Resposta recebida do servidor
      xhr.onload = function () {
        setLoading(false);
        progressWrap.classList.remove('is-visible');

        try {
          const data = JSON.parse(xhr.responseText);

          if (xhr.status >= 200 && xhr.status < 300 && data.ok) {
            showSuccess(data.titulo);
          } else {
            showAlert(data.error || 'Ocorreu um erro inesperado. Tente novamente.');
          }
        } catch (err) {
          showAlert('Erro ao processar a resposta do servidor.');
          console.error('[StudyShare] Resposta inválida:', xhr.responseText);
        }
      };

      // 18/05/2026. Yago: Erro de rede (servidor offline, sem internet etc.)
      xhr.onerror = function () {
        setLoading(false);
        progressWrap.classList.remove('is-visible');
        showAlert('Erro de conexão. Verifique sua internet e tente novamente.');
      };

      // 18/05/2026. Yago: Dispara a requisição com o FormData montado
      xhr.send(formData);
    });
  </script>

</body>
</html>
