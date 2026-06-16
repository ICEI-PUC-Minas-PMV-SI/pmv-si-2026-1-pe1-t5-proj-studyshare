'use strict';

/* ============================================================
   CHAVES DO localStorage (espelho de cadastro-usuario.js)
   ============================================================ */

const LS_KEYS = {
  usuarios:      'ss_usuarios',
  usuarioLogado: 'ss_usuario_logado',
};

/* ============================================================
   CAMADA DE PERSISTÊNCIA
   ============================================================ */

function getUsuarios() {
  try {
    return JSON.parse(localStorage.getItem(LS_KEYS.usuarios) || '[]');
  } catch {
    return [];
  }
}

function saveUsuarios(lista) {
  localStorage.setItem(LS_KEYS.usuarios, JSON.stringify(lista));
}

/**
 * Cria o perfil demo se o localStorage estiver vazio,
 * garantindo que a página de login já tenha um usuário para autenticar.
 */
function initUsuarioPadrao() {
  const lista = getUsuarios();
  if (lista.length > 0) return;

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
   UTILITÁRIOS DE UI
   ============================================================ */

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

function setFieldError(inputEl, mensagem) {
  inputEl.style.borderColor = '#dc2626';
  inputEl.style.outline     = '2px solid #dc2626';

  const campo = inputEl.closest('.campo');
  if (!campo) return;

  let errEl = campo.querySelector('.campo-erro');
  if (!errEl) {
    errEl = document.createElement('span');
    errEl.className        = 'campo-erro';
    errEl.role             = 'alert';
    errEl.style.cssText    =
      'display:block;font:400 12px/1 Inter,sans-serif;color:#dc2626;margin-top:4px;';
    campo.appendChild(errEl);
  }
  errEl.textContent = mensagem;
}

function clearFieldError(inputEl) {
  inputEl.style.borderColor = '';
  inputEl.style.outline     = '';

  const campo = inputEl.closest('.campo');
  if (!campo) return;

  const errEl = campo.querySelector('.campo-erro');
  if (errEl) errEl.remove();
}

/* ============================================================
   LÓGICA DE LOGIN
   ============================================================ */

function autenticarUsuario(email, senha) {
  if (!email || !senha) return null;

  const usuario = getUsuarios().find(
    (u) => u.email.toLowerCase() === email.toLowerCase()
  );

  // Nota: senhas não são armazenadas no localStorage (demo).
  // A autenticação valida apenas o e-mail; qualquer senha não vazia é aceita.
  return usuario || null;
}

function initFormLogin() {
  const emailInput = document.getElementById('email');
  const senhaInput = document.getElementById('senha');
  const btnEntrar  = document.querySelector('.botao-primario.botao-largo');

  if (!emailInput || !senhaInput || !btnEntrar) return;

  // Limpa erros ao digitar
  emailInput.addEventListener('input', () => clearFieldError(emailInput));
  senhaInput.addEventListener('input', () => clearFieldError(senhaInput));

  // Permite submeter com Enter em qualquer campo
  [emailInput, senhaInput].forEach((input) => {
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') btnEntrar.click();
    });
  });

  btnEntrar.addEventListener('click', () => {
    const email = emailInput.value.trim();
    const senha = senhaInput.value;

    let valido = true;

    if (!email) {
      setFieldError(emailInput, 'Informe seu e-mail.');
      valido = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
      setFieldError(emailInput, 'Informe um e-mail válido.');
      valido = false;
    }

    if (!senha) {
      setFieldError(senhaInput, 'Informe sua senha.');
      valido = false;
    }

    if (!valido) return;

    const usuario = autenticarUsuario(email, senha);

    if (!usuario) {
      showToast('E-mail não encontrado. Verifique seus dados ou cadastre-se.', 'error');
      setFieldError(emailInput, 'Usuário não encontrado.');
      emailInput.focus();
      return;
    }

    // Marca como logado e redireciona para o feed
    localStorage.setItem(LS_KEYS.usuarioLogado, JSON.stringify(usuario));
    showToast(`Bem-vindo(a), ${usuario.nome}! Redirecionando…`, 'success');

    setTimeout(() => {
      window.location.href = '../feed/index.html';
    }, 1500);
  });
}

/* ============================================================
   INICIALIZAÇÃO GERAL
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  initUsuarioPadrao();
  initFormLogin();
});
