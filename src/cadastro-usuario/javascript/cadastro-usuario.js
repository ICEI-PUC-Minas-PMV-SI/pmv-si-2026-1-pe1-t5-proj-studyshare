/**
 * StudyShare – estilo-cadastro-usuario.js
 * Página: Cadastro de Usuário (cadastro-usuario.html)
 *
 * Funcionalidades:
 *  1. Toggle de visibilidade das senhas
 *  2. Validação em tempo real (blur) por campo
 *  3. Indicador de força de senha
 *  4. Contador de caracteres do username
 *  5. Verificação de correspondência das senhas
 *  6. Validação completa no submit
 *  7. Persistência no localStorage (ss_usuarios, ss_usuario_logado)
 *  8. Verificação de e-mail e username já cadastrados
 *  9. Estado de carregamento no botão de envio
 * 10. Feedback de sucesso ao cadastrar
 * 11. Marcação visual de campos obrigatórios
 * 12. Scroll automático ao primeiro erro
 */

'use strict';

/* ============================================================
   CHAVES DO localStorage
   ============================================================ */

const LS_KEYS = {
  usuarios:       'ss_usuarios',        // Array de todos os usuários cadastrados
  usuarioLogado:  'ss_usuario_logado',  // Objeto do usuário na sessão atual
};

/* ============================================================
   CONSTANTES DE VALIDAÇÃO
   ============================================================ */

const RULES = {
  nome:      { minLen: 2, maxLen: 50, pattern: /^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/ },
  sobrenome: { minLen: 2, maxLen: 50, pattern: /^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/ },
  email:     { pattern: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/ },
  username:  { minLen: 3, maxLen: 30,  pattern: /^[A-Za-z0-9_.-]+$/ },
  senha:     { minLen: 8 },
};

const MESSAGES = {
  nome:             'Por favor, informe seu nome (mínimo 2 letras).',
  sobrenome:        'Por favor, informe seu sobrenome (mínimo 2 letras).',
  email:            'Informe um e-mail válido (ex: voce@email.com).',
  emailDuplicado:   'Este e-mail já está cadastrado.',
  username:         'Escolha um nome de usuário (3–30 caracteres; letras, números, _ . -).',
  usernameDuplicado:'Este nome de usuário já está em uso.',
  senha:            'A senha deve ter pelo menos 8 caracteres.',
  'confirma-senha': 'As senhas não coincidem.',
  'aceite-termos':  'Você precisa aceitar os Termos de Uso para continuar.',
};

/* ============================================================
   CAMADA DE PERSISTÊNCIA (localStorage)
   ============================================================ */

/**
 * Lê a lista de usuários do localStorage.
 * @returns {Array<Object>}
 */
function getUsuarios() {
  try {
    return JSON.parse(localStorage.getItem(LS_KEYS.usuarios) || '[]');
  } catch {
    return [];
  }
}

/**
 * Salva a lista de usuários no localStorage.
 * @param {Array<Object>} lista
 */
function saveUsuarios(lista) {
  localStorage.setItem(LS_KEYS.usuarios, JSON.stringify(lista));
}

/**
 * Verifica se um e-mail já está cadastrado.
 * @param {string} email
 * @returns {boolean}
 */
function emailJaCadastrado(email) {
  return getUsuarios().some(
    (u) => u.email.toLowerCase() === email.toLowerCase()
  );
}

/**
 * Verifica se um username já está em uso.
 * @param {string} username
 * @returns {boolean}
 */
function usernameJaCadastrado(username) {
  return getUsuarios().some(
    (u) => u.username.toLowerCase() === username.toLowerCase()
  );
}

/**
 * Persiste um novo usuário e marca-o como logado.
 * A senha é armazenada como está (texto simples).
 * Em produção, use hashing no back-end — nunca armazene senhas em texto puro.
 * @param {Object} dados  Dados coletados do formulário
 * @returns {Object}      O usuário criado
 */
function cadastrarUsuario(dados) {
  const novoUsuario = {
    id:          Date.now(),
    nome:        dados.nome,
    sobrenome:   dados.sobrenome,
    email:       dados.email,
    username:    dados.username,
    instituicao: dados.instituicao || '',
    criadoEm:    new Date().toISOString(),
  };

  const lista = getUsuarios();
  lista.push(novoUsuario);
  saveUsuarios(lista);

  // Marca como usuário logado (sem a senha)
  localStorage.setItem(LS_KEYS.usuarioLogado, JSON.stringify(novoUsuario));

  return novoUsuario;
}

/* ============================================================
   UTILITÁRIOS DE UI
   ============================================================ */

function setError(group, message) {
  group.classList.add('has-error');
  const input = group.querySelector('.field-input');
  if (input) input.classList.add('error');
  const msg = group.querySelector('.field-error-msg');
  if (msg && message) msg.textContent = message;
}

function clearError(group) {
  group.classList.remove('has-error');
  const input = group.querySelector('.field-input');
  if (input) input.classList.remove('error');
}

function getGroup(input) {
  return input.closest('.field-group');
}

function scrollToFirstError() {
  const firstError = document.querySelector('.field-group.has-error');
  if (firstError) {
    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    const input = firstError.querySelector('.field-input, input[type="checkbox"]');
    if (input) input.focus();
  }
}

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
  }, 4000);
}

/* ============================================================
   1. TOGGLE DE VISIBILIDADE DAS SENHAS
   ============================================================ */

function initPasswordToggle() {
  document.querySelectorAll('.toggle-password').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('aria-controls');
      const input    = document.getElementById(targetId);
      const icon     = btn.querySelector('.material-symbols-outlined');
      if (!input || !icon) return;

      const isHidden   = input.type === 'password';
      input.type       = isHidden ? 'text' : 'password';
      icon.textContent = isHidden ? 'visibility_off' : 'visibility';
      btn.setAttribute('aria-label', isHidden ? 'Ocultar senha' : 'Mostrar senha');
    });
  });
}

/* ============================================================
   2. INDICADOR DE FORÇA DE SENHA
   ============================================================ */

function calcPasswordStrength(password) {
  let score = 0;
  if (password.length >= 8)  score++;
  if (password.length >= 12) score++;
  if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++;
  if (/\d/.test(password))   score++;
  if (/[^A-Za-z0-9]/.test(password)) score++;

  const levels = [
    { label: '',         color: '' },
    { label: 'Fraca',    color: '#dc2626' },
    { label: 'Razoável', color: '#d97706' },
    { label: 'Boa',      color: '#2563eb' },
    { label: 'Forte',    color: '#16a34a' },
  ];

  return { score, ...levels[Math.min(score, 4)] };
}

function createStrengthIndicator() {
  const senhaGroup = document.getElementById('senha')?.closest('.field-group');
  if (!senhaGroup || document.getElementById('strength-indicator')) return;

  const wrapper = document.createElement('div');
  wrapper.id    = 'strength-indicator';
  wrapper.setAttribute('aria-live', 'polite');
  wrapper.style.cssText = 'display:flex;align-items:center;gap:8px;margin-top:2px;';

  wrapper.innerHTML = `
    <div id="strength-bars" style="display:flex;gap:3px;flex:1;">
      ${[0, 1, 2, 3].map((i) =>
        `<div data-bar="${i}" style="height:4px;flex:1;border-radius:2px;background:#e2e8f0;transition:background 0.3s;"></div>`
      ).join('')}
    </div>
    <span id="strength-label" style="font:400 11px/1 Inter,sans-serif;color:#718096;min-width:52px;text-align:right;"></span>
  `;

  const wrap = senhaGroup.querySelector('.field-input-wrap');
  if (wrap) wrap.insertAdjacentElement('afterend', wrapper);
}

function updateStrengthIndicator(password) {
  const bars   = document.querySelectorAll('#strength-bars [data-bar]');
  const labelEl = document.getElementById('strength-label');
  if (!bars.length || !labelEl) return;

  const { score, label, color } = calcPasswordStrength(password);
  const filled = password.length === 0 ? 0 : Math.max(1, score);

  bars.forEach((bar, i) => {
    bar.style.background = i < filled ? color : '#e2e8f0';
  });

  labelEl.textContent = password.length ? label : '';
  labelEl.style.color = color || '#718096';
}

/* ============================================================
   3. CONTADOR DE CARACTERES DO USERNAME
   ============================================================ */

function initUsernameCounter() {
  const input = document.getElementById('username');
  if (!input) return;

  const counter = document.createElement('span');
  counter.id    = 'username-counter';
  counter.setAttribute('aria-live', 'polite');
  counter.style.cssText =
    'font:400 11px/1 Inter,sans-serif;color:#718096;align-self:flex-end;margin-top:-2px;';
  counter.textContent = `0 / ${RULES.username.maxLen}`;

  const group = getGroup(input);
  if (group) group.appendChild(counter);

  input.addEventListener('input', () => {
    const len = input.value.length;
    counter.textContent = `${len} / ${RULES.username.maxLen}`;
    counter.style.color = len > RULES.username.maxLen ? '#dc2626' : '#718096';
  });
}

/* ============================================================
   4. VALIDAÇÃO POR CAMPO
   ============================================================ */

function validateField(input) {
  const id    = input.id;
  const value = input.value.trim();
  const group = getGroup(input);
  if (!group) return true;

  if (id === 'instituicao') { clearError(group); return true; }

  if (id === 'confirma-senha') {
    const senha = document.getElementById('senha')?.value || '';
    if (!value || value !== senha) {
      setError(group, MESSAGES['confirma-senha']);
      return false;
    }
    clearError(group);
    return true;
  }

  const rule = RULES[id];
  if (!rule) { clearError(group); return true; }

  if (!value) {
    setError(group, MESSAGES[id]);
    return false;
  }
  if (rule.minLen && value.length < rule.minLen) {
    setError(group, MESSAGES[id]);
    return false;
  }
  if (rule.maxLen && value.length > rule.maxLen) {
    setError(group, MESSAGES[id]);
    return false;
  }
  if (rule.pattern && !rule.pattern.test(value)) {
    setError(group, MESSAGES[id]);
    return false;
  }

  // Verifica duplicatas no localStorage (apenas no blur, não em tempo real)
  if (id === 'email' && emailJaCadastrado(value)) {
    setError(group, MESSAGES.emailDuplicado);
    return false;
  }
  if (id === 'username' && usernameJaCadastrado(value)) {
    setError(group, MESSAGES.usernameDuplicado);
    return false;
  }

  clearError(group);
  return true;
}

function validateTerms() {
  const checkbox = document.getElementById('aceite-termos');
  if (!checkbox) return false;

  const label = checkbox.closest('label') || checkbox.parentElement;

  if (!checkbox.checked) {
    let errMsg = document.getElementById('terms-error-msg');
    if (!errMsg) {
      errMsg = document.createElement('span');
      errMsg.id            = 'terms-error-msg';
      errMsg.role          = 'alert';
      errMsg.style.cssText =
        'display:block;font:400 12px/1 Inter,sans-serif;color:#dc2626;margin-top:6px;';
      label.insertAdjacentElement('afterend', errMsg);
    }
    errMsg.textContent     = MESSAGES['aceite-termos'];
    checkbox.style.outline = '2px solid #dc2626';
    return false;
  }

  const errMsg = document.getElementById('terms-error-msg');
  if (errMsg) errMsg.remove();
  checkbox.style.outline = '';
  return true;
}

function initRealtimeValidation() {
  const ids = ['nome', 'sobrenome', 'email', 'username', 'senha', 'confirma-senha'];

  ids.forEach((id) => {
    const input = document.getElementById(id);
    if (!input) return;

    input.addEventListener('blur', () => validateField(input));

    input.addEventListener('input', () => {
      const group = getGroup(input);
      if (group?.classList.contains('has-error')) clearError(group);
      if (id === 'senha') updateStrengthIndicator(input.value);
      if (id === 'senha') {
        const confirmInput = document.getElementById('confirma-senha');
        if (confirmInput?.value) validateField(confirmInput);
      }
    });
  });

  const termsCheckbox = document.getElementById('aceite-termos');
  if (termsCheckbox) {
    termsCheckbox.addEventListener('change', () => {
      if (termsCheckbox.checked) {
        const errMsg = document.getElementById('terms-error-msg');
        if (errMsg) errMsg.remove();
        termsCheckbox.style.outline = '';
      }
    });
  }
}

/* ============================================================
   5. ENVIO DO FORMULÁRIO COM PERSISTÊNCIA NO localStorage
   ============================================================ */

function initFormSubmit() {
  const form      = document.querySelector('form');
  const submitBtn = document.querySelector('.btn-submit');
  if (!form || !submitBtn) return;

  // Injeta @keyframes spin para o ícone de carregamento
  if (!document.getElementById('ss-spin-style')) {
    const style = document.createElement('style');
    style.id    = 'ss-spin-style';
    style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(style);
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // --- Validação de todos os campos ---
    const fieldIds = ['nome', 'sobrenome', 'email', 'username', 'senha', 'confirma-senha'];
    let allValid   = true;

    fieldIds.forEach((id) => {
      const input = document.getElementById(id);
      if (input && !validateField(input)) allValid = false;
    });

    if (!validateTerms()) allValid = false;

    if (!allValid) {
      scrollToFirstError();
      showToast('Corrija os campos destacados antes de continuar.', 'error');
      return;
    }

    // --- Estado de carregamento ---
    submitBtn.disabled  = true;
    const originalHTML  = submitBtn.innerHTML;
    submitBtn.innerHTML =
      `<img src="docs/icons/progress.svg" alt="" aria-hidden="true"
        style="width:18px;height:18px;animation:spin 1s linear infinite;" /> Criando conta…`;

    // Simula latência de rede (1,5 s)
    await new Promise((resolve) => setTimeout(resolve, 1500));

    // --- Persistência no localStorage ---
    const dados = {
      nome:        document.getElementById('nome').value.trim(),
      sobrenome:   document.getElementById('sobrenome').value.trim(),
      email:       document.getElementById('email').value.trim(),
      username:    document.getElementById('username').value.trim(),
      instituicao: document.getElementById('instituicao').value.trim(),
    };

    const usuario = cadastrarUsuario(dados);

    // --- Feedback de sucesso ---
    submitBtn.innerHTML        = `<img src="docs/icons/check_circle.svg" alt="" aria-hidden="true" style="width:18px;height:18px;" /> Conta criada!`;
    submitBtn.style.background = '#065f46';
    submitBtn.disabled         = false;

    showToast(`Bem-vindo(a), ${usuario.nome}! Conta criada com sucesso 🎉`, 'success');

    // Em produção: redirecionar para o feed após 2 s
    // setTimeout(() => { window.location.href = 'index.html'; }, 2000);

    // Restaura botão após 4 s (demo)
    setTimeout(() => {
      submitBtn.innerHTML        = originalHTML;
      submitBtn.style.background = '';
    }, 4000);
  });
}

/* ============================================================
   6. INDICAR CAMPOS OBRIGATÓRIOS NO LABEL
   ============================================================ */

function initRequiredLabels() {
  document.querySelectorAll('.field-input[required]').forEach((input) => {
    const label = document.querySelector(`label[for="${input.id}"]`);
    if (label && !label.querySelector('.required-mark')) {
      const mark = document.createElement('span');
      mark.className        = 'required-mark';
      mark.textContent      = ' *';
      mark.setAttribute('aria-hidden', 'true');
      mark.style.color      = '#dc2626';
      mark.style.fontWeight = '600';
      label.appendChild(mark);
    }
  });
}

/* ============================================================
   7. USUÁRIO PADRÃO DE DEMONSTRAÇÃO
   Se o localStorage estiver vazio, cria automaticamente um
   registro inicial para facilitar testes sem precisar cadastrar.
   ============================================================ */

function initUsuarioPadrao() {
  const lista = getUsuarios();
  if (lista.length > 0) return; // já existe pelo menos um cadastro

  const usuarioPadrao = {
    id:          Date.now(),
    nome:        'Estudante',
    sobrenome:   'Demo',
    email:       'estudante.demo@studyshare.com',
    username:    'estudante_demo',
    instituicao: 'StudyShare University',
    criadoEm:    new Date().toISOString(),
  };

  saveUsuarios([usuarioPadrao]);
  localStorage.setItem(LS_KEYS.usuarioLogado, JSON.stringify(usuarioPadrao));

  console.info(
    '[StudyShare] Usuário padrão criado automaticamente.\n',
    'E-mail:   estudante.demo@studyshare.com\n',
    'Username: estudante_demo'
  );
}

/* ============================================================
   8. EXIBIR CONTADOR DE USUÁRIOS CADASTRADOS (DEBUG/DEMO)
   Mostra discretamente quantos usuários já estão salvos.
   Remova este bloco em produção se não for necessário.
   ============================================================ */

function initCadastroCounter() {
  const total = getUsuarios().length;
  if (total === 0) return;

  const heading = document.querySelector('.signup-heading');
  if (!heading) return;

  const badge = document.createElement('span');
  badge.style.cssText =
    'margin-left:10px;background:#e6eef8;color:#004AAD;font:500 11px/1 Inter,sans-serif;' +
    'padding:2px 8px;border-radius:20px;vertical-align:middle;';
  badge.textContent = `${total} usuário${total > 1 ? 's' : ''} cadastrado${total > 1 ? 's' : ''}`;
  heading.appendChild(badge);
}

/* ============================================================
   INICIALIZAÇÃO GERAL
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  initUsuarioPadrao();     // cria registro demo se localStorage estiver vazio
  initPasswordToggle();
  createStrengthIndicator();
  initUsernameCounter();
  initRealtimeValidation();
  initFormSubmit();
  initRequiredLabels();
  initCadastroCounter();
});