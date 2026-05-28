/* ============================================================
   StudyShare — Tela de Upload de Materiais
   Arquivo: upload-material.js

   Lógica de UI front-only — sem backend, sem upload real.
   Apenas simula a barra de progresso e exibe a tela de sucesso.
============================================================ */

'use strict';

(function () {

  // Garante que o DOM esteja pronto antes de manipular elementos.
  document.addEventListener('DOMContentLoaded', init);

  /* ============================================================
     REFERÊNCIAS DOS ELEMENTOS DA TELA
     Cacheadas dentro de init() para evitar getElementById() espalhado.
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
     MAPA DE ÍCONES POR TIPO DE ARQUIVO
     Cada extensão tem um ícone do Material Symbols que combina
     com o tipo. Melhora a usabilidade do preview.
  ============================================================ */
  const ICONES_POR_TIPO = {
    'pdf':  'picture_as_pdf',
    'txt':  'description',
    'doc':  'description',
    'docx': 'description',
    'ppt':  'co_present',
    'pptx': 'co_present'
  };

  // Limite de tamanho do arquivo — 20MB.
  const LIMITE_BYTES = 20 * 1024 * 1024;

  /* ============================================================
     INICIALIZAÇÃO
     Captura referências do DOM e registra os event listeners.
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
    // stopPropagation() impede que o clique no botão reabra o seletor de arquivo.
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
        // Atribui o arquivo dropado ao input file via DataTransfer
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
     FORMATA O TAMANHO DO ARQUIVO
     Converte bytes para string legível: 1234567 → "1.18 MB"
  ============================================================ */
  function formatarTamanho(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
  }

  /* ============================================================
     PEGA A EXTENSÃO DE UM NOME DE ARQUIVO
     "trabalho.final.pdf" → "pdf" — usado para escolher o ícone.
  ============================================================ */
  function pegarExtensao(nome) {
    const partes = nome.split('.');
    return partes.length > 1 ? partes.pop().toLowerCase() : '';
  }

  /* ============================================================
     TRATA A SELEÇÃO/MUDANÇA DE ARQUIVO
     Chamada pelo clique no input file e pelo drag & drop.
     Atualiza o preview com nome, tamanho e ícone correto.
  ============================================================ */
  function tratarArquivoSelecionado(arquivo) {
    if (!arquivo) return;

    // Feedback rápido de tamanho no client (puro front, sem backend)
    if (arquivo.size > LIMITE_BYTES) {
      showAlert('Arquivo maior que 20MB. Reduza o tamanho e tente novamente.');
      fileInput.value = '';
      dropzone.classList.remove('has-file');
      return;
    }

    // Atualiza o preview visual
    const extensao = pegarExtensao(arquivo.name);
    fileName.textContent = arquivo.name;
    fileMeta.textContent = extensao.toUpperCase() + ' · ' + formatarTamanho(arquivo.size);
    fileIcon.textContent = ICONES_POR_TIPO[extensao] || 'description';

    // Muda o visual do dropzone para o estado verde "arquivo selecionado"
    dropzone.classList.add('has-file');

    clearAlert();
  }

  /* ============================================================
     FUNÇÕES UTILITÁRIAS DE FEEDBACK (UI)
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

  /* ============================================================
     TRANSIÇÃO PARA O ESTADO DE SUCESSO
  ============================================================ */
  function showSuccess(titulo) {
    formState.style.display    = 'none';
    successState.style.display = 'flex';
    successTitulo.textContent  = titulo;
  }

  /* ============================================================
     RESETA O FORMULÁRIO PARA NOVO UPLOAD
     Chamado pelo botão "Enviar outro" no estado de sucesso.
  ============================================================ */
  function resetForm() {
    successState.style.display = 'none';
    formState.style.display    = 'block';

    // Limpa todos os campos
    fileInput.value       = '';
    inputTitulo.value     = '';
    inputDisciplina.value = '';
    inputDescricao.value  = '';
    dropzone.classList.remove('has-file');
    tituloCounter.textContent    = '0/120';
    descricaoCounter.textContent = '0/500';

    // Reseta a barra de progresso
    progressWrap.classList.remove('is-visible');
    progressFill.style.width    = '0%';
    progressPercent.textContent = '0%';

    clearAlert();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /* ============================================================
     SUBMIT DO FORMULÁRIO — FRONT-ONLY (sem backend, sem validação)
     Apenas simula a barra de progresso de 0 a 100% e depois
     mostra o estado de sucesso.

     Quando houver backend, substituir a simulação abaixo por um
     FormData + XMLHttpRequest (que tem xhr.upload.onprogress para
     alimentar a barra de verdade) apontando para a API real.
  ============================================================ */
  function handleSubmit(e) {
    e.preventDefault();
    clearAlert();

    // Texto exibido na tela de sucesso: título digitado, ou nome do
    // arquivo, ou um fallback genérico.
    const arquivo = fileInput.files && fileInput.files[0];
    const titulo  = inputTitulo.value.trim() || (arquivo ? arquivo.name : 'Material');

    setLoading(true);
    progressWrap.classList.add('is-visible');

    // Simulação de progresso — incrementos irregulares para parecer real
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
        // Pequena pausa para o usuário ver os 100% antes de trocar de tela
        setTimeout(function () {
          setLoading(false);
          progressWrap.classList.remove('is-visible');
          showSuccess(titulo);
        }, 350);
      }
    }, 160);
  }

})();
