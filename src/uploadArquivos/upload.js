/* ============================================================
   StudyShare — Tela de Upload de Materiais (FRONT-ONLY)
   Arquivo: upload.js

   100% front-end (HTML/CSS/JS) — sem PHP, sem backend.
   Persistência via localStorage, rodando no GitHub Pages.

   O QUE É GUARDADO:
   - Os DADOS do material ficam em localStorage["ss_materiais"]
     (array JSON), no MESMO formato que a tela de visualização já lê
     (title, type, author, date, description, discipline, course).
     Assim o material sobrevive ao reload e, se o feed for ligado para
     ler "ss_materiais", aparece lá automaticamente.

   O QUE NÃO É GUARDADO:
   - O CONTEÚDO BINÁRIO do arquivo (o PDF/DOCX em si) NÃO é salvo.
     localStorage tem limite de ~5MB e guarda só texto — não serve para
     arquivos de até 20MB. Guardamos só os metadados. Para armazenar o
     arquivo de verdade e permitir download, o caminho é IndexedDB.

   ESCOPO:
   - Este arquivo mexe SÓ no upload. Feed e visualização são de outra
     pessoa do grupo e não são tocados aqui.
============================================================ */

'use strict';

(function () {

  // Garante que o DOM esteja pronto antes de manipular elementos.
  document.addEventListener('DOMContentLoaded', init);

  /* ============================================================
     REFERÊNCIAS DOS ELEMENTOS DA TELA
  ============================================================ */
  let dropzone, fileInput, fileRemove, fileName, fileMeta, fileIcon,
      inputTitulo, inputDisciplina, inputDescricao,
      tituloCounter, descricaoCounter,
      btnSubmit, spinner, btnIcon, btnLabel,
      progressWrap, progressFill, progressPercent,
      alertBox, alertMsg,
      formState, successState, successTitulo,
      uploadForm, btnNovoUpload;

  /* ============================================================
     CONSTANTES
  ============================================================ */
  const ICONES_POR_TIPO = {
    'pdf':  'picture_as_pdf',
    'txt':  'description',
    'doc':  'description',
    'docx': 'description',
    'ppt':  'co_present',
    'pptx': 'co_present'
  };

  const LIMITE_BYTES  = 20 * 1024 * 1024;                       // 20MB
  const ALLOWED_EXT   = ['pdf', 'txt', 'doc', 'docx', 'ppt', 'pptx'];
  const STORAGE_KEY   = 'ss_materiais';        // lista de materiais (convenção ss_* do projeto)
  const USER_KEY      = 'ss_usuario_logado';   // usuário logado (preenchido pelo cadastro/login)
  const CURSO_PADRAO  = 'Sistemas de Informação'; // o form não tem campo Curso → padrão

  /* ============================================================
     INICIALIZAÇÃO
  ============================================================ */
  function init() {
    dropzone         = document.getElementById('dropzone');
    fileInput        = document.getElementById('file-input');
    fileRemove       = document.getElementById('file-remove');
    fileName         = document.getElementById('file-name');
    fileMeta         = document.getElementById('file-meta');
    fileIcon         = document.getElementById('file-icon');
    inputTitulo      = document.getElementById('input-titulo');
    inputDisciplina  = document.getElementById('input-disciplina');
    inputDescricao   = document.getElementById('input-descricao');
    tituloCounter    = document.getElementById('titulo-counter');
    descricaoCounter = document.getElementById('descricao-counter');
    btnSubmit        = document.getElementById('btn-submit');
    spinner          = document.getElementById('spinner');
    btnIcon          = document.getElementById('btn-icon');
    btnLabel         = document.getElementById('btn-label');
    progressWrap     = document.getElementById('progress-wrap');
    progressFill     = document.getElementById('progress-fill');
    progressPercent  = document.getElementById('progress-percent');
    alertBox         = document.getElementById('alert-box');
    alertMsg         = document.getElementById('alert-msg');
    formState        = document.getElementById('form-state');
    successState     = document.getElementById('success-state');
    successTitulo    = document.getElementById('success-titulo');
    uploadForm       = document.getElementById('upload-form');
    btnNovoUpload    = document.getElementById('btn-novo-upload');

    // Seleção via clique no input file
    fileInput.addEventListener('change', function () {
      tratarArquivoSelecionado(this.files[0]);
    });

    // Remove o arquivo selecionado quando clica no X.
    fileRemove.addEventListener('click', function (e) {
      e.stopPropagation();
      fileInput.value = '';
      dropzone.classList.remove('has-file');
    });

    // Drag & drop nativo
    ['dragenter', 'dragover'].forEach(function (evento) {
      dropzone.addEventListener(evento, function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('is-dragging');
      });
    });

    ['dragleave', 'drop'].forEach(function (evento) {
      dropzone.addEventListener(evento, function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('is-dragging');
      });
    });

    dropzone.addEventListener('drop', function (e) {
      const arquivos = e.dataTransfer.files;
      if (arquivos.length > 0) {
        fileInput.files = arquivos;
        tratarArquivoSelecionado(arquivos[0]);
      }
    });

    // Contadores de caracteres em tempo real
    inputTitulo.addEventListener('input', function () {
      tituloCounter.textContent = this.value.length + '/120';
    });

    inputDescricao.addEventListener('input', function () {
      descricaoCounter.textContent = this.value.length + '/500';
    });

    // Submit do formulário
    uploadForm.addEventListener('submit', handleSubmit);

    // Botão "Enviar outro" da tela de sucesso
    if (btnNovoUpload) btnNovoUpload.addEventListener('click', resetForm);
  }

  /* ============================================================
     HELPERS
  ============================================================ */
  function formatarTamanho(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
  }

  function pegarExtensao(nome) {
    const partes = nome.split('.');
    return partes.length > 1 ? partes.pop().toLowerCase() : '';
  }

  // Data de hoje no formato "DD/MM/AAAA" (mesmo formato que a visualização lê).
  function dataHoje() {
    const d = new Date();
    const p = function (n) { return String(n).padStart(2, '0'); };
    return p(d.getDate()) + '/' + p(d.getMonth() + 1) + '/' + d.getFullYear();
  }

  // Nome do autor a partir do usuário logado (ss_usuario_logado).
  function autorAtual() {
    try {
      const u = JSON.parse(localStorage.getItem(USER_KEY) || 'null');
      if (u) {
        const nome = [u.nome, u.sobrenome].filter(Boolean).join(' ').trim();
        return nome || u.username || u.email || 'Anônimo';
      }
    } catch (e) { /* sem usuário logado ou dado corrompido */ }
    return 'Anônimo';
  }

  /* ============================================================
     SELEÇÃO/MUDANÇA DE ARQUIVO — valida formato e tamanho
  ============================================================ */
  function tratarArquivoSelecionado(arquivo) {
    if (!arquivo) return;

    const extensao = pegarExtensao(arquivo.name);

    if (ALLOWED_EXT.indexOf(extensao) === -1) {
      showAlert('Formato não suportado. Use PDF, TXT, DOC, DOCX, PPT ou PPTX.');
      fileInput.value = '';
      dropzone.classList.remove('has-file');
      return;
    }

    if (arquivo.size > LIMITE_BYTES) {
      showAlert('Arquivo maior que 20MB. Reduza o tamanho e tente novamente.');
      fileInput.value = '';
      dropzone.classList.remove('has-file');
      return;
    }

    fileName.textContent = arquivo.name;
    fileMeta.textContent = extensao.toUpperCase() + ' · ' + formatarTamanho(arquivo.size);
    fileIcon.textContent = ICONES_POR_TIPO[extensao] || 'description';
    dropzone.classList.add('has-file');
    clearAlert();
  }

  /* ============================================================
     FEEDBACK (UI)
  ============================================================ */
  function showAlert(msg) {
    alertMsg.textContent = msg;
    alertBox.className   = 'alert is-error';
  }

  function clearAlert() {
    alertBox.className = 'alert';
  }

  function setLoading(on) {
    btnSubmit.disabled    = on;
    spinner.style.display = on ? 'block'      : 'none';
    btnIcon.style.display = on ? 'none'       : 'inline-block';
    btnLabel.textContent  = on ? 'Enviando…'  : 'Publicar material';
  }

  function showSuccess(titulo) {
    formState.style.display    = 'none';
    successState.style.display = 'flex';
    successTitulo.textContent  = titulo;
  }

  function resetForm() {
    successState.style.display = 'none';
    formState.style.display    = 'block';

    fileInput.value       = '';
    inputTitulo.value     = '';
    inputDisciplina.value = '';
    inputDescricao.value  = '';
    dropzone.classList.remove('has-file');
    tituloCounter.textContent    = '0/120';
    descricaoCounter.textContent = '0/500';

    progressWrap.classList.remove('is-visible');
    progressFill.style.width    = '0%';
    progressPercent.textContent = '0%';

    clearAlert();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /* ============================================================
     CAMADA DE PERSISTÊNCIA (localStorage — chave ss_materiais)
  ============================================================ */
  function lerMateriais() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      const arr = raw ? JSON.parse(raw) : [];
      return Array.isArray(arr) ? arr : [];
    } catch (e) {
      return [];
    }
  }

  function gravarMateriais(lista) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(lista));
      return true;
    } catch (e) {
      return false; // QuotaExceededError ou storage bloqueado
    }
  }

  // Monta o registro no formato que a visualização (ss_selected_material) lê.
  function montarRegistro(arquivo, titulo, disciplina, descricao) {
    const ext  = pegarExtensao(arquivo.name);
    const tipo = ext ? ext.toUpperCase() : 'ARQUIVO';
    return {
      id:          'm_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7),
      title:       titulo,
      description: descricao,
      discipline:  disciplina,
      course:      CURSO_PADRAO,
      author:      autorAtual(),
      type:        tipo,        // "PDF", "DOCX"... (a visualização lê "type")
      date:        dataHoje(),  // "DD/MM/AAAA"
      // Metadados do arquivo — o binário NÃO é guardado (ver nota do topo)
      fileName:    arquivo.name,
      fileSize:    arquivo.size,
      fileExt:     ext,
      icon:        ICONES_POR_TIPO[ext] || 'description',
      hasFile:     false,
      createdAt:   new Date().toISOString() // para ordenação
    };
  }

  function salvarMaterial(registro) {
    const lista = lerMateriais();
    lista.unshift(registro); // mais recente primeiro
    return gravarMateriais(lista);
  }

  /* ============================================================
     SUBMIT — valida, simula progresso e PERSISTE o material
  ============================================================ */
  function handleSubmit(e) {
    e.preventDefault();
    clearAlert();

    const arquivo    = fileInput.files && fileInput.files[0];
    const titulo     = inputTitulo.value.trim();
    const disciplina = inputDisciplina.value;
    const descricao  = inputDescricao.value.trim();

    // Validação client-side (não há backend)
    if (!arquivo) { showAlert('Selecione um arquivo para publicar.'); return; }

    const ext = pegarExtensao(arquivo.name);
    if (ALLOWED_EXT.indexOf(ext) === -1) {
      showAlert('Formato não suportado. Use PDF, TXT, DOC, DOCX, PPT ou PPTX.'); return;
    }
    if (arquivo.size > LIMITE_BYTES) {
      showAlert('Arquivo maior que 20MB. Reduza o tamanho e tente novamente.'); return;
    }
    if (titulo.length < 3)   { showAlert('O título deve ter ao menos 3 caracteres.'); return; }
    if (titulo.length > 120) { showAlert('O título deve ter no máximo 120 caracteres.'); return; }
    if (!disciplina)         { showAlert('Selecione a disciplina.'); return; }

    setLoading(true);
    progressWrap.classList.add('is-visible');

    // Simulação de progresso — incrementos irregulares
    let p = 0;
    const timer = setInterval(function () {
      p += Math.random() * 18 + 6;
      if (p >= 100) {
        p = 100;
        clearInterval(timer);
      }
      progressFill.style.width    = p + '%';
      progressPercent.textContent = Math.round(p) + '%';

      if (p === 100) {
        setTimeout(function () {
          // Persiste AGORA (fim do "upload")
          const registro = montarRegistro(arquivo, titulo, disciplina, descricao);
          const ok = salvarMaterial(registro);

          setLoading(false);
          progressWrap.classList.remove('is-visible');

          if (!ok) {
            progressFill.style.width    = '0%';
            progressPercent.textContent = '0%';
            showAlert('Não foi possível salvar (armazenamento cheio ou bloqueado). Tente novamente.');
            return;
          }

          showSuccess(titulo);
        }, 350);
      }
    }, 160);
  }

})();
