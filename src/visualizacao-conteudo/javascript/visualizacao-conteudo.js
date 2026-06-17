/**
 * StudyShare – estilo-visualizacao.js
 * Página: Visualização de Conteúdo (visualizacao-conteudo.html)
 *
 * Funcionalidades:
 *  1.  Favoritar/desfavoritar – persiste no localStorage
 *  2.  Postar comentário – persiste no localStorage
 *  3.  Curtir comentário – persiste no localStorage
 *  4.  Responder comentário – persiste no localStorage
 *  5.  Compartilhar material (Web Share API / fallback clipboard)
 *  6.  Download simulado com feedback visual – persiste contador
 *  7.  Contadores de estatísticas (views, downloads, favoritos) – persistidos
 *  8.  Busca na topbar
 *  9.  Navegação ativa na sidebar
 * 10.  Botão "Novo Material"
 * 11.  Botão "Visualizar Perfil" (CTA)
 */

'use strict';

/* ============================================================
   CHAVES DO localStorage
   ============================================================ */

/**
 * Gera a chave de armazenamento do material atual com base no título da página.
 * @returns {string}
 */
function getMaterialKey() {
  const titulo = document.querySelector('.file-title')?.textContent?.trim() || 'material';
  const slug   = titulo.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
  return `ss_material_${slug}`;
}

/* ============================================================
   ESTRUTURA PADRÃO DO ESTADO DO MATERIAL
   ============================================================ */

/**
 * Lê o estado salvo do material ou retorna os valores-base do HTML.
 * @returns {Object}
 */
function loadState() {
  try {
    const saved = localStorage.getItem(getMaterialKey());
    if (saved) return JSON.parse(saved);
  } catch {
    // localStorage indisponível ou dado corrompido — usa valores do HTML
  }

  // Valores-base lidos diretamente do HTML para primeira visita
  return {
    favoritado:    false,
    downloads:     getStatFromDOM('Downloads')     || 234,
    visualizacoes: getStatFromDOM('Visualizações') || 1205,
    favoritos:     getStatFromDOM('Favoritos')     || 45,
    comentarios:   [],   // comentários gerados pelo usuário (não os do HTML estático)
  };
}

/**
 * Persiste o estado atual do material no localStorage.
 * @param {Object} state
 */
function saveState(state) {
  try {
    localStorage.setItem(getMaterialKey(), JSON.stringify(state));
  } catch {
    // Quota excedida ou modo privado sem permissão — falha silenciosa
  }
}

/** Estado em memória do material. Carregado uma vez no DOMContentLoaded. */
let STATE = {};

/* ============================================================
   UTILITÁRIOS
   ============================================================ */

/**
 * Formata um número com separador de milhar (pt-BR).
 * @param {number} n
 * @returns {string}
 */
function formatNumber(n) {
  return n.toLocaleString('pt-BR');
}

/**
 * Lê o valor numérico de uma estatística diretamente do DOM.
 * @param {string} label
 * @returns {number}
 */
function getStatFromDOM(label) {
  const rows = document.querySelectorAll('.stat-row');
  for (const row of rows) {
    if (row.textContent.includes(label)) {
      const val = row.querySelector('.stat-value');
      return val ? parseInt(val.textContent.replace(/\D/g, ''), 10) : 0;
    }
  }
  return 0;
}

/**
 * Atualiza o valor exibido de uma estatística no painel lateral
 * e persiste o novo estado.
 * @param {'downloads'|'visualizacoes'|'favoritos'} key
 * @param {number} delta
 */
function updateStat(key, delta) {
  if (delta === 0) return;

  STATE[key] = (STATE[key] || 0) + delta;

  // Mapeia chave interna → texto do rótulo no DOM
  const labelMap = {
    downloads:     'Downloads',
    visualizacoes: 'Visualizações',
    favoritos:     'Favoritos',
  };

  const label = labelMap[key];
  if (!label) return;

  const rows = document.querySelectorAll('.stat-row');
  for (const row of rows) {
    if (row.textContent.includes(label)) {
      const valEl = row.querySelector('.stat-value');
      if (valEl) valEl.textContent = formatNumber(STATE[key]);
      break;
    }
  }

  saveState(STATE);
}

/**
 * Aplica todos os contadores do STATE ao DOM.
 */
function applyStatsToDOM() {
  const keys = ['downloads', 'visualizacoes', 'favoritos'];
  const labelMap = {
    downloads:     'Downloads',
    visualizacoes: 'Visualizações',
    favoritos:     'Favoritos',
  };

  keys.forEach((key) => {
    const label = labelMap[key];
    const rows  = document.querySelectorAll('.stat-row');
    for (const row of rows) {
      if (row.textContent.includes(label)) {
        const valEl = row.querySelector('.stat-value');
        if (valEl) valEl.textContent = formatNumber(STATE[key]);
        break;
      }
    }
  });
}

/**
 * Exibe notificação toast temporária.
 * @param {string} message
 * @param {'success'|'error'|'info'} type
 */
function showToast(message, type = 'info') {
  const existing = document.getElementById('ss-toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.id = 'ss-toast';
  toast.setAttribute('role', 'status');
  toast.setAttribute('aria-live', 'polite');

  const colors = { success: '#065f46', error: '#9b2335', info: '#004AAD' };

  Object.assign(toast.style, {
    position:     'fixed',
    bottom:       '24px',
    left:         '50%',
    transform:    'translateX(-50%)',
    background:   colors[type] || colors.info,
    color:        '#fff',
    padding:      '10px 22px',
    borderRadius: '20px',
    fontSize:     '14px',
    fontFamily:   "'Inter', sans-serif",
    boxShadow:    '0 4px 12px rgba(0,0,0,0.2)',
    zIndex:       '9999',
    opacity:      '0',
    transition:   'opacity 0.25s',
    whiteSpace:   'nowrap',
  });

  toast.textContent = message;
  document.body.appendChild(toast);

  requestAnimationFrame(() => {
    requestAnimationFrame(() => { toast.style.opacity = '1'; });
  });

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.addEventListener('transitionend', () => toast.remove(), { once: true });
  }, 3000);
}

/**
 * Escapa HTML para prevenir XSS.
 * @param {string} str
 * @returns {string}
 */
function escapeHtml(str) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(str));
  return div.innerHTML;
}

/* ============================================================
   0. CARREGAR MATERIAL SELECIONADO DO FEED
   ============================================================ */

function loadMaterialFromFeed() {
  let material;
  try {
    const raw = localStorage.getItem('ss_selected_material');
    if (!raw) return;
    material = JSON.parse(raw);
  } catch {
    return;
  }

  // Título da página
  if (material.title) {
    document.title = `${material.title} – StudyShare`;
    const titleEl = document.querySelector('.file-title');
    if (titleEl) titleEl.textContent = material.title;
  }

  // Badge (abreviação do tipo)
  const badgeEl = document.querySelector('.file-badge');
  if (badgeEl && material.type) {
    badgeEl.textContent = material.type.slice(0, 3).toUpperCase();
    badgeEl.className   = 'file-badge';
  }

  // Avatar e nome do autor
  const metaRow = document.querySelector('.file-meta-row');
  if (metaRow && material.author) {
    const avatarEl = metaRow.querySelector('.file-meta-avatar');
    if (avatarEl) {
      const parts = material.author.trim().split(' ');
      avatarEl.textContent = parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : parts[0][0].toUpperCase();
    }
    const nameSpan = metaRow.querySelectorAll('span')[0];
    if (nameSpan) nameSpan.textContent = material.author;
  }

  // Data de publicação
  const timeEl = document.querySelector('.file-meta-row time');
  if (timeEl && material.date) {
    timeEl.textContent = `Publicado em ${material.date}`;
    const parts = material.date.split('/');
    if (parts.length === 3) {
      timeEl.setAttribute('datetime', `${parts[2]}-${parts[1]}-${parts[0]}`);
    }
  }

  // Descrição
  const descEl = document.querySelector('.description-text');
  if (descEl && material.description) descEl.textContent = material.description;

  // Tags
  const tagsRow = document.querySelector('.tags-row');
  if (tagsRow) {
    const tags = [material.discipline, material.type, material.course].filter(Boolean);
    tagsRow.innerHTML = tags.map((t) => `<span class="tag">#${t}</span>`).join('');
  }

  // Formato no painel de detalhes
  document.querySelectorAll('.detail-row').forEach((row) => {
    const key = row.querySelector('.detail-key');
    const val = row.querySelector('.detail-val');
    if (key && val && key.textContent.trim() === 'Formato' && material.type) {
      val.textContent = material.type;
    }
  });
}

/* ============================================================
   1. FAVORITAR / DESFAVORITAR – com localStorage
   ============================================================ */

function initFavorite() {
  const favBtn = document.querySelector('.btn-icon-outline[aria-label*="favoritos"]');
  if (!favBtn) return;

  // Restaura estado salvo
  if (STATE.favoritado) {
    favBtn.style.background  = '#fff0f3';
    favBtn.style.borderColor = '#e53e3e';
    favBtn.ariaLabel         = 'Remover dos favoritos';
  }

  favBtn.addEventListener('click', () => {
    STATE.favoritado = !STATE.favoritado;

    if (STATE.favoritado) {
      favBtn.style.background  = '#fff0f3';
      favBtn.style.borderColor = '#e53e3e';
      favBtn.ariaLabel         = 'Remover dos favoritos';
      updateStat('favoritos', +1);
      showToast('Adicionado aos favoritos!', 'success');
    } else {
      favBtn.style.background  = '';
      favBtn.style.borderColor = '';
      favBtn.ariaLabel         = 'Adicionar aos favoritos';
      updateStat('favoritos', -1);
      showToast('Removido dos favoritos.', 'info');
    }

    saveState(STATE);
  });
}

/* ============================================================
   3. COMENTÁRIOS – com localStorage
   ============================================================ */

/** Cores para avatares gerados dinamicamente */
const AVATAR_COLORS = [
  '#004AAD', '#e17055', '#0984e3', '#00b894',
  '#6c5ce7', '#fd79a8', '#e17055', '#fdcb6e',
];

let avatarColorIdx = 0;

/**
 * Cria e insere um card de comentário no DOM.
 * @param {Object}  data         Dados do comentário
 * @param {boolean} saveToStorage  Se true, persiste no STATE
 */
function insertCommentCard(data, saveToStorage = true) {
  const inputCard = document.querySelector('.comment-input-card');
  if (!inputCard) return;

  const color = AVATAR_COLORS[avatarColorIdx % AVATAR_COLORS.length];
  avatarColorIdx++;

  const article = document.createElement('article');
  article.className = 'comment-card';
  article.setAttribute('role', 'comment');
  article.dataset.commentId = data.id;

  if (saveToStorage) {
    // Animação de entrada apenas para comentários novos
    article.style.cssText =
      'opacity:0;transform:translateY(8px);transition:opacity 0.3s,transform 0.3s';
  }

  article.innerHTML = `
    <div class="comment-header">
      <div class="comment-avatar" style="background:${data.avatarColor || color};" aria-hidden="true">
        ${escapeHtml(data.initials || 'Eu')}
      </div>
      <span class="comment-name">${escapeHtml(data.autor || 'Você')}</span>
      <time class="comment-time" datetime="${data.datetime}">${escapeHtml(data.tempoLabel)}</time>
    </div>
    <p class="comment-body">${renderTextoComentario(data.texto)}</p>
    <div class="comment-actions-row">
      <button class="comment-action-btn like-btn" type="button"
        data-count="${data.curtidas || 0}"
        data-liked="${data.liked || false}"
        aria-label="Curtir comentário (${data.curtidas || 0} curtidas)">
        <span class="material-symbols-outlined" aria-hidden="true" style="font-size:14px;">favorite</span>
        <span>${data.curtidas || 0}</span>
      </button>
      <button class="comment-action-btn reply-btn" type="button">Responder</button>
    </div>
  `;

  // Renderiza respostas salvas
  if (data.respostas && data.respostas.length > 0) {
    data.respostas.forEach((r) => {
      const replyEl = buildReplyElement(r.texto);
      article.appendChild(replyEl);
    });
  }

  // Restaura estado de curtida
  const likeBtn = article.querySelector('.like-btn');
  if (likeBtn && data.liked) {
    likeBtn.style.color = '#e53e3e';
    const icon = likeBtn.querySelector('.material-symbols-outlined');
    if (icon) icon.style.fontVariationSettings = "'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 20";
  }

  inputCard.insertAdjacentElement('afterend', article);

  if (saveToStorage) {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        article.style.opacity   = '1';
        article.style.transform = 'translateY(0)';
      });
    });
  }

  bindCommentActions(article);
  return article;
}

/**
 * Constrói um elemento de resposta a partir de texto.
 * @param {string} texto
 * @returns {HTMLElement}
 */
function buildReplyElement(texto) {
  const replyEl = document.createElement('div');
  replyEl.className = 'comment-reply';
  replyEl.style.cssText =
    'margin-top:8px;border-left:2px solid #e2e8f0;padding-left:12px;';
  replyEl.innerHTML = `
    <span style="font:600 12px/1 Inter,sans-serif;color:#000;">Você</span>
    <span style="font:400 12px/1 Inter,sans-serif;color:#718096;margin-left:8px;">Anteriormente</span>
    <p style="font:400 13px/1.5 Inter,sans-serif;color:#4a5568;margin-top:4px;">${renderTextoComentario(texto)}</p>
  `;
  return replyEl;
}

/**
 * Atualiza o contador no título da seção de comentários.
 * @param {number} delta
 */
function updateCommentCount(delta) {
  const title = document.querySelector('.comments-title');
  if (!title) return;
  const match = title.textContent.match(/\d+/);
  const count = match ? parseInt(match[0], 10) + delta : delta;
  title.textContent = `Comentários (${count})`;
}

/**
 * Vincula os eventos de curtir e responder a um card de comentário.
 * @param {HTMLElement} card
 */
function bindCommentActions(card) {
  // --- Curtir ---
  const likeBtn = card.querySelector('.like-btn');
  if (likeBtn) {
    likeBtn.addEventListener('click', () => {
      const liked   = likeBtn.dataset.liked === 'true';
      let count     = parseInt(likeBtn.dataset.count, 10);
      const countEl = likeBtn.querySelector('span:last-child');
      const iconEl  = likeBtn.querySelector('.material-symbols-outlined');

      if (!liked) {
        count++;
        likeBtn.dataset.liked = 'true';
        likeBtn.style.color   = '#e53e3e';
        if (iconEl) iconEl.style.fontVariationSettings = "'FILL' 1";
        likeBtn.ariaLabel = `Descurtir (${count} curtidas)`;
      } else {
        count--;
        likeBtn.dataset.liked = 'false';
        likeBtn.style.color   = '';
        if (iconEl) iconEl.style.fontVariationSettings = '';
        likeBtn.ariaLabel = `Curtir (${count} curtidas)`;
      }

      likeBtn.dataset.count = count;
      if (countEl) countEl.textContent = count;

      // Persiste curtida no comentário do STATE
      const commentId = card.dataset.commentId;
      if (commentId) {
        const comentario = STATE.comentarios.find((c) => c.id === commentId);
        if (comentario) {
          comentario.curtidas = count;
          comentario.liked    = !liked;
          saveState(STATE);
        }
      }
    });
  }

  // --- Responder ---
  const replyBtn = card.querySelector('.reply-btn');
  if (replyBtn) {
    replyBtn.addEventListener('click', () => {
      if (card.querySelector('.reply-form')) return;

      const replyForm = document.createElement('div');
      replyForm.className = 'reply-form';
      replyForm.style.cssText =
        'margin-top:10px;padding-left:40px;display:flex;flex-direction:column;gap:6px;';

      replyForm.innerHTML = `
        <textarea
          class="comment-textarea"
          placeholder="Escreva sua resposta..."
          rows="2"
          aria-label="Campo de resposta"
          style="border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;"
        ></textarea>
        <div style="display:flex;gap:8px;justify-content:flex-end;">
          <button type="button" class="cancel-reply-btn"
            style="font-size:13px;color:#718096;background:none;border:none;cursor:pointer;">
            Cancelar
          </button>
          <button type="button" class="send-reply-btn"
            style="background:#004AAD;color:#fff;border:none;border-radius:20px;padding:6px 16px;font-size:13px;cursor:pointer;">
            Responder
          </button>
        </div>
      `;

      card.appendChild(replyForm);
      const replyTextarea = replyForm.querySelector('textarea');
      initMentions(replyTextarea);
      replyTextarea.focus();

      replyForm.querySelector('.cancel-reply-btn').addEventListener('click', () => {
        replyForm.remove();
      });

      replyForm.querySelector('.send-reply-btn').addEventListener('click', () => {
        const text = replyForm.querySelector('textarea').value.trim();
        if (!text) { showToast('Digite algo para responder.', 'error'); return; }

        const filtro = filtrarConteudo(text);
        if (!filtro.valido) { showToast(filtro.motivo, 'error'); return; }

        const replyEl = buildReplyElement(text);
        replyForm.replaceWith(replyEl);
        showToast('Resposta postada!', 'success');
        updateCommentCount(+1);

        // Persiste resposta no STATE
        const commentId = card.dataset.commentId;
        if (commentId) {
          const comentario = STATE.comentarios.find((c) => c.id === commentId);
          if (comentario) {
            if (!comentario.respostas) comentario.respostas = [];
            comentario.respostas.push({ texto: text, criadoEm: new Date().toISOString() });
            saveState(STATE);
          }
        }
      });
    });
  }
}

/**
 * Renderiza os comentários persistidos do STATE no DOM.
 */
function renderSavedComments() {
  if (!STATE.comentarios || STATE.comentarios.length === 0) return;

  STATE.comentarios.forEach((c) => {
    insertCommentCard(c, false); // false = não re-persiste
  });

  // Atualiza contador (comentários do HTML + salvos)
  updateCommentCount(STATE.comentarios.length);
}

/* ============================================================
   RF-18: FILTRO DE CONTEÚDO INADEQUADO
   ============================================================ */

const PALAVRAS_BLOQUEADAS = [
  'merda', 'porra', 'caralho', 'foda', 'foda-se', 'fodase',
  'puta', 'viado', 'buceta', 'cu', 'desgraca', 'desgraça',
  'idiota', 'imbecil', 'babaca', 'cretino', 'otario', 'otário',
  'fdp', 'filhadaputa', 'arrombado', 'vsf',
];

const PADRAO_URL_SUSPEITA = /https?:\/\/\S+|www\.\S+/i;

/**
 * Verifica se o texto contém palavras de baixo calão ou URLs suspeitas.
 * @param {string} texto
 * @returns {{ valido: boolean, motivo: string }}
 */
function filtrarConteudo(texto) {
  const normalizado = texto
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '');

  const temPalavraBloqueada = PALAVRAS_BLOQUEADAS.some((p) => normalizado.includes(p));

  if (temPalavraBloqueada) {
    return {
      valido: false,
      motivo: 'Seu comentário contém linguagem inadequada e não pode ser publicado.',
    };
  }

  if (PADRAO_URL_SUSPEITA.test(texto)) {
    return {
      valido: false,
      motivo: 'Seu comentário contém um link externo suspeito e não pode ser publicado.',
    };
  }

  return { valido: true, motivo: '' };
}

/* ============================================================
   RF-17: MENÇÃO DE USUÁRIOS COM @
   ============================================================ */

function getUsuariosLS() {
  try {
    return JSON.parse(localStorage.getItem('ss_usuarios') || '[]');
  } catch {
    return [];
  }
}

function injetarEstilosMencao() {
  if (document.getElementById('ss-mention-style')) return;
  const style = document.createElement('style');
  style.id = 'ss-mention-style';
  style.textContent = `
    .ss-mention {
      color: #004AAD;
      font-weight: 600;
      background: #e6eef8;
      border-radius: 4px;
      padding: 0 3px;
    }
    .mention-dropdown {
      position: absolute;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      list-style: none;
      margin: 0;
      padding: 4px 0;
      min-width: 200px;
      max-width: 300px;
      z-index: 9999;
      font-family: 'Inter', sans-serif;
    }
    .mention-dropdown li {
      padding: 8px 14px;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .mention-dropdown li:hover,
    .mention-dropdown li.is-active {
      background: #e6eef8;
    }
    .mention-dropdown .mention-username {
      font: 600 13px/1 Inter, sans-serif;
      color: #004AAD;
    }
    .mention-dropdown .mention-fullname {
      font: 400 11px/1 Inter, sans-serif;
      color: #718096;
    }
  `;
  document.head.appendChild(style);
}

/**
 * Substitui @username no texto por um span estilizado.
 * Deve ser chamada após escapeHtml para evitar XSS.
 * @param {string} texto
 * @returns {string} HTML seguro com menções destacadas
 */
function renderTextoComentario(texto) {
  return escapeHtml(texto).replace(
    /@([A-Za-z0-9_.\\-]+)/g,
    '<span class="ss-mention">@$1</span>'
  );
}

/**
 * Ativa o autocomplete de @ em um textarea.
 * @param {HTMLTextAreaElement} textarea
 */
function initMentions(textarea) {
  let dropdown  = null;
  let activeIdx = -1;

  function fecharDropdown() {
    if (dropdown) { dropdown.remove(); dropdown = null; }
    activeIdx = -1;
  }

  function getMencaoAtual() {
    const cursor = textarea.selectionStart;
    const antes  = textarea.value.slice(0, cursor);
    const match  = antes.match(/@([A-Za-z0-9_.]*)$/);
    return match ? { termo: match[1], inicio: cursor - match[0].length } : null;
  }

  function inserirUsername(usuario, mencao) {
    const cursor    = textarea.selectionStart;
    const antes     = textarea.value.slice(0, mencao.inicio);
    const depois    = textarea.value.slice(cursor);
    textarea.value  = `${antes}@${usuario.username} ${depois}`;
    fecharDropdown();
    textarea.focus();
    const pos = mencao.inicio + usuario.username.length + 2;
    textarea.setSelectionRange(pos, pos);
  }

  function renderDropdown(filtrados, mencao) {
    if (!dropdown) {
      dropdown = document.createElement('ul');
      dropdown.className = 'mention-dropdown';
      dropdown.setAttribute('role', 'listbox');
      document.body.appendChild(dropdown);
    }

    const rect = textarea.getBoundingClientRect();
    dropdown.style.top  = `${rect.bottom + window.scrollY + 4}px`;
    dropdown.style.left = `${rect.left  + window.scrollX}px`;
    activeIdx = -1;

    dropdown.innerHTML = '';
    filtrados.forEach((u) => {
      const li = document.createElement('li');
      li.setAttribute('role', 'option');
      li.innerHTML = `
        <span class="mention-username">@${escapeHtml(u.username)}</span>
        <span class="mention-fullname">${escapeHtml(u.nome)} ${escapeHtml(u.sobrenome)}</span>
      `;
      li.addEventListener('mousedown', (e) => {
        e.preventDefault();
        inserirUsername(u, mencao);
      });
      dropdown.appendChild(li);
    });
  }

  textarea.addEventListener('input', () => {
    const mencao = getMencaoAtual();
    if (!mencao) { fecharDropdown(); return; }

    const filtrados = getUsuariosLS()
      .filter((u) => u.username.toLowerCase().startsWith(mencao.termo.toLowerCase()))
      .slice(0, 5);

    if (!filtrados.length) { fecharDropdown(); return; }
    renderDropdown(filtrados, mencao);
  });

  textarea.addEventListener('keydown', (e) => {
    if (!dropdown) return;
    const items = [...dropdown.querySelectorAll('li')];

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (activeIdx >= 0) items[activeIdx].classList.remove('is-active');
      activeIdx = (activeIdx + 1) % items.length;
      items[activeIdx].classList.add('is-active');
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (activeIdx >= 0) items[activeIdx].classList.remove('is-active');
      activeIdx = (activeIdx - 1 + items.length) % items.length;
      items[activeIdx].classList.add('is-active');
    } else if (e.key === 'Enter' && activeIdx >= 0) {
      e.preventDefault();
      items[activeIdx].dispatchEvent(new MouseEvent('mousedown'));
    } else if (e.key === 'Escape') {
      fecharDropdown();
    }
  });

  textarea.addEventListener('blur', () => {
    setTimeout(fecharDropdown, 150);
  });
}

/**
 * Inicializa o campo de postagem de novo comentário.
 */
function initCommentPost() {
  const textarea = document.querySelector('.comment-textarea');
  const postBtn  = document.querySelector('.btn-post');
  if (!textarea || !postBtn) return;

  initMentions(textarea);

  // Atalho Ctrl+Enter
  textarea.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) postBtn.click();
  });

  // Auto-resize
  textarea.addEventListener('input', () => {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
  });

  postBtn.addEventListener('click', () => {
    const text = textarea.value.trim();
    if (!text) {
      showToast('Digite algo antes de postar.', 'error');
      textarea.focus();
      return;
    }

    const filtro = filtrarConteudo(text);
    if (!filtro.valido) {
      showToast(filtro.motivo, 'error');
      textarea.focus();
      return;
    }

    const usuarioLogado = (() => {
      try {
        return JSON.parse(localStorage.getItem('ss_usuario_logado') || 'null');
      } catch { return null; }
    })();

    const now = new Date();
    const novoComentario = {
      id:          `c_${Date.now()}`,
      texto:       text,
      autor:       usuarioLogado ? `${usuarioLogado.nome} ${usuarioLogado.sobrenome}` : 'Você',
      initials:    usuarioLogado
                    ? (usuarioLogado.nome[0] + usuarioLogado.sobrenome[0]).toUpperCase()
                    : 'Eu',
      avatarColor: AVATAR_COLORS[avatarColorIdx % AVATAR_COLORS.length],
      datetime:    now.toISOString(),
      tempoLabel:  'Agora mesmo',
      curtidas:    0,
      liked:       false,
      respostas:   [],
    };

    // Salva no STATE
    STATE.comentarios.push(novoComentario);
    saveState(STATE);

    // Insere no DOM
    insertCommentCard(novoComentario, false);
    updateCommentCount(+1);

    textarea.value = '';
    textarea.style.height = '';
    showToast('Comentário postado!', 'success');
  });
}

/**
 * Vincula ações de curtir/responder aos comentários estáticos do HTML.
 */
function initExistingComments() {
  document.querySelectorAll('.comment-card').forEach((card) => {
    const likeBtn = card.querySelector('.comment-action-btn');
    if (likeBtn) {
      const countEl = likeBtn.querySelector('span:last-child');
      if (countEl && !likeBtn.dataset.count) {
        likeBtn.dataset.count = parseInt(countEl.textContent, 10) || 0;
      }
      likeBtn.classList.add('like-btn');
    }

    const replyBtn = card.querySelector('.comment-action-btn:last-child');
    if (replyBtn) replyBtn.classList.add('reply-btn');

    bindCommentActions(card);
  });
}

/* ============================================================
   6. COMPARTILHAR MATERIAL
   ============================================================ */

function initShare() {
  const shareBtn = document.querySelector('.btn-secondary-icon[aria-label="Compartilhar arquivo"]');
  if (!shareBtn) return;

  shareBtn.addEventListener('click', async () => {
    const shareData = {
      title: document.querySelector('.file-title')?.textContent?.trim() || 'StudyShare',
      text:  'Confira este material no StudyShare!',
      url:   window.location.href,
    };

    if (navigator.share) {
      try { await navigator.share(shareData); } catch { /* cancelado pelo usuário */ }
    } else {
      try {
        await navigator.clipboard.writeText(shareData.url);
        showToast('Link copiado para a área de transferência!', 'success');
      } catch {
        showToast('Não foi possível copiar o link.', 'error');
      }
    }
  });
}

/* ============================================================
   7. DOWNLOAD – persiste contador
   ============================================================ */

function initDownload() {
  const downloadBtn = document.querySelector('.btn-primary');
  if (!downloadBtn) return;

  downloadBtn.addEventListener('click', () => {
    if (downloadBtn.dataset.downloading === 'true') return;

    downloadBtn.dataset.downloading = 'true';
    const original = downloadBtn.innerHTML;
    downloadBtn.innerHTML     = 'Baixando…';
    downloadBtn.style.opacity = '0.75';
    downloadBtn.disabled      = true;

    // Incrementa e persiste
    updateStat('downloads', 1);

    setTimeout(() => {
      downloadBtn.innerHTML           = '✔ Download concluído';
      downloadBtn.style.background    = '#065f46';
      downloadBtn.style.opacity       = '1';
      downloadBtn.disabled            = false;
      downloadBtn.dataset.downloading = 'false';

      showToast('Arquivo baixado com sucesso!', 'success');

      setTimeout(() => {
        downloadBtn.innerHTML        = original;
        downloadBtn.style.background = '';
      }, 3000);
    }, 1800);
  });
}

/* ============================================================
   8. INCREMENTO DE VISUALIZAÇÕES ao carregar – persiste
   ============================================================ */

function initViewIncrement() {
  updateStat('visualizacoes', 1);
}

/* ============================================================
   9. BUSCA NA TOPBAR
   ============================================================ */

function initSearch() {
  const input = document.querySelector('.topbar-search input');
  if (!input) return;

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      const query = input.value.trim();
      if (query) showToast(`Buscando por "${query}"…`, 'info');
    }
  });
}

/* ============================================================
   10. NAVEGAÇÃO ATIVA NA SIDEBAR
   ============================================================ */

function initNavHighlight() {
  const items = document.querySelectorAll('.nav-item');
  items.forEach((item) => {
    item.addEventListener('click', function () {
      items.forEach((i) => i.classList.remove('active'));
      this.classList.add('active');
    });
  });
}

/* ============================================================
   11. BOTÃO "NOVO MATERIAL"
   ============================================================ */

function initNewMaterial() {
  const btn = document.querySelector('.btn-new-material');
  if (!btn) return;
  btn.addEventListener('click', () => {
    showToast('Funcionalidade de upload em breve!', 'info');
  });
}

/* ============================================================
   12. BOTÃO "VISUALIZAR PERFIL" (CTA)
   ============================================================ */

function initProfileCta() {
  const btn = document.querySelector('.btn-cta-outline');
  if (!btn) return;
  btn.addEventListener('click', () => {
    showToast('Redirecionando para o perfil do autor…', 'info');
    // Em produção: window.location.href = 'perfil.html?user=ana-machado';
  });
}

/* ============================================================
   INICIALIZAÇÃO GERAL
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  injetarEstilosMencao();

  // Popula a página com o material clicado no feed (deve vir antes de loadState)
  loadMaterialFromFeed();

  // Carrega estado persistido (ou valores-base do HTML)
  STATE = loadState();

  // Aplica contadores ao DOM antes de qualquer incremento
  applyStatsToDOM();

  initFavorite();
  initExistingComments();
  renderSavedComments();   // renderiza comentários do localStorage
  initCommentPost();
  initShare();
  initDownload();
  initViewIncrement();     // incrementa visualizações e persiste
  initSearch();
  initNavHighlight();
  initNewMaterial();
  initProfileCta();
});
