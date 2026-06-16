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

function getUsuarioLogado() {
  try {
    return JSON.parse(localStorage.getItem(LS_KEYS.usuarioLogado) || 'null');
  } catch {
    return null;
  }
}

/**
 * Cria o perfil demo se o localStorage estiver vazio,
 * herdando o mesmo usuário gerado por cadastro-usuario.js.
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
   CARREGAMENTO DO PERFIL A PARTIR DO localStorage
   ============================================================ */

function carregarPerfil() {
  const usuario = getUsuarioLogado();
  if (!usuario) return;

  const nomeCompleto = `${usuario.nome || ''} ${usuario.sobrenome || ''}`.trim();
  const iniciais = [usuario.nome, usuario.sobrenome]
    .filter(Boolean)
    .map((s) => s[0].toUpperCase())
    .join('');

  // --- Área de exibição do perfil ---
  const h1 = document.querySelector('.perfil-centro h1');
  if (h1) h1.textContent = nomeCompleto;

  const usuarioEl = document.querySelector('.perfil-centro .usuario');
  if (usuarioEl) usuarioEl.textContent = `@${usuario.username || ''}`;

  const cursoEl = document.querySelector('.perfil-centro .curso');
  if (cursoEl && usuario.instituicao) cursoEl.textContent = usuario.instituicao;

  const bioEl = document.querySelector('.perfil-centro .bio');
  if (bioEl && usuario.bio) bioEl.textContent = usuario.bio;

  const avatarEl = document.querySelector('.avatar-grande');
  if (avatarEl) avatarEl.textContent = iniciais || '?';

  // Atualiza também o ícone de conta do cabeçalho, se houver avatar lá
  const avatarTopo = document.querySelector('.acoes-topo .avatar-topo');
  if (avatarTopo) avatarTopo.textContent = iniciais || '?';

  // --- Visibilidade dos botões: perfil próprio ocult seguir/seguindo ---
  const btnSeguir       = document.getElementById('btnSeguir');
  const btnDeixarSeguir = document.getElementById('btnDeixarSeguir');
  const btnEditar       = document.getElementById('btnEditar');
  const btnLogout       = document.getElementById('btnLogout');

  if (btnSeguir)       btnSeguir.style.display       = 'none';
  if (btnDeixarSeguir) btnDeixarSeguir.style.display = 'none';
  if (btnEditar)       btnEditar.style.display       = 'inline-flex';
  if (btnLogout)       btnLogout.style.display       = 'inline-flex';

  // --- Pré-preenchimento do formulário de edição ---
  const nomeInput = document.getElementById('nome');
  if (nomeInput) nomeInput.value = nomeCompleto;

  const usuarioInput = document.getElementById('usuarioInput');
  if (usuarioInput) usuarioInput.value = `@${usuario.username || ''}`;

  const cursoInput = document.getElementById('cursoInput');
  if (cursoInput) cursoInput.value = usuario.instituicao || '';

  const bioInput = document.getElementById('bioInput');
  if (bioInput && usuario.bio) bioInput.value = usuario.bio;
}

/* ============================================================
   RF-07: EDIÇÃO DE PERFIL
   ============================================================ */

function ativarEdicao() {
  const form = document.getElementById('formEdicao');
  if (!form) return;
  form.style.display = 'block';
  form.scrollIntoView({ behavior: 'smooth' });
}

function salvarEdicao() {
  const nomeCompleto  = (document.getElementById('nome')?.value || '').trim();
  const usuarioRaw    = (document.getElementById('usuarioInput')?.value || '').trim();
  const cursoVal      = (document.getElementById('cursoInput')?.value || '').trim();
  const bioVal        = (document.getElementById('bioInput')?.value || '').trim();

  // Remove o @ se o usuário digitou com ele
  const usernameNovo  = usuarioRaw.startsWith('@') ? usuarioRaw.slice(1) : usuarioRaw;

  // Divide o nome completo em nome + sobrenome
  const partes        = nomeCompleto.split(/\s+/);
  const nomeNovo      = partes[0] || '';
  const sobrenomeNovo = partes.slice(1).join(' ') || '';

  // Atualiza exibição no perfil
  const h1 = document.querySelector('.perfil-centro h1');
  if (h1) h1.textContent = nomeCompleto;

  const usuarioEl = document.querySelector('.perfil-centro .usuario');
  if (usuarioEl) usuarioEl.textContent = `@${usernameNovo}`;

  const cursoEl = document.querySelector('.perfil-centro .curso');
  if (cursoEl) cursoEl.textContent = cursoVal;

  const bioEl = document.querySelector('.perfil-centro .bio');
  if (bioEl) bioEl.textContent = bioVal;

  const iniciais = [nomeNovo, sobrenomeNovo]
    .filter(Boolean)
    .map((s) => s[0].toUpperCase())
    .join('');
  const avatarEl = document.querySelector('.avatar-grande');
  if (avatarEl) avatarEl.textContent = iniciais || '?';

  // Persiste as alterações no localStorage
  const usuarioAtual = getUsuarioLogado();
  if (usuarioAtual) {
    const usuarioAtualizado = {
      ...usuarioAtual,
      nome:        nomeNovo,
      sobrenome:   sobrenomeNovo,
      username:    usernameNovo,
      instituicao: cursoVal,
      bio:         bioVal,
    };

    localStorage.setItem(LS_KEYS.usuarioLogado, JSON.stringify(usuarioAtualizado));

    // Atualiza também a entrada correspondente em ss_usuarios
    const lista = getUsuarios().map((u) =>
      u.id === usuarioAtualizado.id ? usuarioAtualizado : u
    );
    saveUsuarios(lista);
  }

  document.getElementById('formEdicao').style.display = 'none';
  alert('Perfil atualizado com sucesso!');
}

function cancelarEdicao() {
  const form = document.getElementById('formEdicao');
  if (form) form.style.display = 'none';
}

/* ============================================================
   LOGOUT
   ============================================================ */

function logout() {
  localStorage.removeItem(LS_KEYS.usuarioLogado);
  window.location.href = '../login/index.html';
}

/* ============================================================
   MATERIAIS DEMO
   ============================================================ */

const MATERIAIS_DEMO = [
  {
    id:          'mat_perfil_1',
    title:       'Resumo de Banco de Dados',
    type:        'PDF',
    discipline:  'Banco de Dados',
    course:      'Sistemas de Informação',
    description: 'Resumo completo dos principais conceitos de modelagem e SQL.',
    date:        '10/05/2026',
  },
  {
    id:          'mat_perfil_2',
    title:       'Slides de Engenharia de Software',
    type:        'Slides',
    discipline:  'Engenharia de Software',
    course:      'Sistemas de Informação',
    description: 'Apresentação sobre metodologias ágeis e ciclo de vida do software.',
    date:        '08/05/2026',
  },
  {
    id:          'mat_perfil_3',
    title:       'Anotações de Algoritmos',
    type:        'Anotações',
    discipline:  'Algoritmos',
    course:      'Sistemas de Informação',
    description: 'Anotações e exercícios resolvidos de estruturas de dados.',
    date:        '05/05/2026',
  },
];

function initMateriaisDemo() {
  if (localStorage.getItem('ss_materiais_perfil')) return;

  const usuario = getUsuarioLogado();
  const autor   = usuario
    ? `${usuario.nome} ${usuario.sobrenome}`.trim()
    : 'Estudante Demo';

  const materiais = MATERIAIS_DEMO.map((m) => ({ ...m, author: autor }));
  localStorage.setItem('ss_materiais_perfil', JSON.stringify(materiais));
}

function initBotoesMateriais() {
  const usuario = getUsuarioLogado();
  const autor   = usuario
    ? `${usuario.nome} ${usuario.sobrenome}`.trim()
    : 'Estudante Demo';

  document.querySelectorAll('.card-material').forEach((card, index) => {
    const btn      = card.querySelector('.botao-primario');
    const material = MATERIAIS_DEMO[index];
    if (!btn || !material) return;

    btn.addEventListener('click', () => {
      localStorage.setItem(
        'ss_selected_material',
        JSON.stringify({ ...material, author: autor })
      );
      window.location.href = '../visualizacao-conteudo/visualizacao-conteudo.html';
    });
  });
}

/* ============================================================
   RF-08: SEGUIR / DEIXAR DE SEGUIR
   ============================================================ */

function seguir() {
  document.getElementById('btnSeguir').style.display       = 'none';
  document.getElementById('btnDeixarSeguir').style.display = 'inline-flex';
}

function deixarDeSeguir() {
  document.getElementById('btnDeixarSeguir').style.display = 'none';
  document.getElementById('btnSeguir').style.display       = 'inline-flex';
}

/* ============================================================
   INICIALIZAÇÃO GERAL
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  initUsuarioPadrao();
  carregarPerfil();
  initMateriaisDemo();
  initBotoesMateriais();
});
