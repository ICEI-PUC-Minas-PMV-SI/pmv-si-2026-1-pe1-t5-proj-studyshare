/* ============================================================
   StudyShare — Recuperação de Senha (FRONT-ONLY)
   Arquivo: redefinicao-senha.js

   Sem backend. Toda a lógica roda no browser via localStorage.

   O que faz:
   - Alterna o canal E-mail / SMS (mostra o campo certo + valida formato).
   - No envio: simula o "envio", gera uma SENHA PROVISÓRIA aleatória,
     grava essa senha no localStorage e exibe na tela (única forma de
     pegar a senha sem e-mail/SMS real).
   - Botão "Reenviar" gera uma nova senha provisória no lugar.
   - Botão "Copiar" copia a senha pra área de transferência.

   CONTRATO DE INTEGRAÇÃO (importante):
   O login (login.html) deve VALIDAR a senha digitada contra a MESMA
   chave usada aqui: localStorage["studyshare:senha"].
   Se o login precisar também de e-mail/usuário, é só me avisar que
   eu adiciono uma segunda chave — a estrutura já está isolada pra isso.
============================================================ */

(function () {
  'use strict';

  /* ----------------------------------------------------------
     CONFIG — pontos de ajuste num lugar só
  ---------------------------------------------------------- */
  var STORAGE_KEY   = 'studyshare:senha';   // chave única que o login deve ler
  var FAKE_DELAY_MS = 1200;                  // latência simulada do "envio"
  var SENHA_LEN     = 8;                      // tamanho da senha provisória
  // Charset sem caracteres ambíguos (0/O, 1/l/I) pra leitura/digitação:
  var SENHA_CHARS   = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';

  /* ----------------------------------------------------------
     ESTADO + REFERÊNCIAS DE DOM
  ---------------------------------------------------------- */
  var state = { canal: 'email', enviando: false };
  var el = {};

  function byId(id) { return document.getElementById(id); }

  function mapearDom() {
    el.formState     = byId('form-state');
    el.successState  = byId('success-state');
    el.btnEmail      = byId('btn-email');
    el.btnSms        = byId('btn-sms');
    el.emailField    = byId('email-field');
    el.phoneField    = byId('phone-field');
    el.inputEmail    = byId('input-email');
    el.inputPhone    = byId('input-phone');
    el.form          = byId('recovery-form');
    el.btnSubmit     = byId('btn-submit');
    el.spinner       = byId('spinner');
    el.btnIcon       = byId('btn-icon');
    el.btnLabel      = byId('btn-label');
    el.alertBox      = byId('alert-box');
    el.alertMsg      = byId('alert-msg');
    el.successMsg    = byId('success-msg');
    el.successTitle  = byId('success-title');
    el.linkResend    = byId('link-resend');
    el.provBox       = byId('prov-box');     // adicionado no HTML
    el.provSenha     = byId('prov-senha');   // adicionado no HTML
    el.btnCopy       = byId('btn-copy');      // adicionado no HTML
  }

  /* ----------------------------------------------------------
     STORAGE — escrita resiliente (modo privado/quota podem falhar)
  ---------------------------------------------------------- */
  function salvarSenha(senha) {
    try {
      localStorage.setItem(STORAGE_KEY, senha);
      return true;
    } catch (e) {
      // QuotaExceededError ou storage bloqueado (alguns browsers em aba anônima)
      return false;
    }
  }

  /* ----------------------------------------------------------
     SENHA PROVISÓRIA — crypto.getRandomValues (não Math.random)
  ---------------------------------------------------------- */
  function gerarSenha(len) {
    var n = len || SENHA_LEN;
    var cripto = window.crypto || window.msCrypto;
    var out = '';

    if (cripto && cripto.getRandomValues) {
      var buf = new Uint32Array(n);
      cripto.getRandomValues(buf);
      for (var i = 0; i < n; i++) out += SENHA_CHARS.charAt(buf[i] % SENHA_CHARS.length);
    } else {
      // fallback improvável (browser muito antigo)
      for (var j = 0; j < n; j++) out += SENHA_CHARS.charAt(Math.floor(Math.random() * SENHA_CHARS.length));
    }
    return out;
  }

  /* ----------------------------------------------------------
     VALIDAÇÃO
  ---------------------------------------------------------- */
  function emailValido(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((v || '').trim()); }
  function soDigitos(v)   { return (v || '').replace(/\D/g, ''); }
  function telefoneValido(v) {
    var d = soDigitos(v);
    return d.length === 10 || d.length === 11; // DDD + fixo (10) ou celular (11)
  }

  /* ----------------------------------------------------------
     MÁSCARA DO DESTINO (apenas visual no success)
  ---------------------------------------------------------- */
  function mascararEmail(v) {
    v = (v || '').trim();
    var at = v.indexOf('@');
    if (at < 1) return v;
    return v.charAt(0) + '***' + v.slice(at);
  }
  function mascararTelefone(v) {
    var d = soDigitos(v);
    return '(' + d.slice(0, 2) + ') *****-' + d.slice(-4);
  }

  /* ----------------------------------------------------------
     UI — erro, loading, canal
  ---------------------------------------------------------- */
  function mostrarErro(msg) {
    el.alertMsg.textContent = msg;
    el.alertBox.classList.add('is-error');
    var input = state.canal === 'email' ? el.inputEmail : el.inputPhone;
    if (input) input.classList.add('has-error');
  }

  function limparErro() {
    el.alertBox.classList.remove('is-error');
    el.alertMsg.textContent = '';
    if (el.inputEmail) el.inputEmail.classList.remove('has-error');
    if (el.inputPhone) el.inputPhone.classList.remove('has-error');
  }

  function setLoading(on) {
    state.enviando = on;
    el.btnSubmit.disabled    = on;
    el.spinner.style.display = on ? 'block' : 'none';
    el.btnIcon.style.display = on ? 'none'  : 'inline-block';
    el.btnLabel.textContent  = on ? 'Enviando…' : 'Enviar senha provisória';
  }

  function selecionarCanal(canal) {
    state.canal = canal;
    var ehEmail = canal === 'email';
    el.btnEmail.classList.toggle('active', ehEmail);
    el.btnSms.classList.toggle('active', !ehEmail);
    el.emailField.style.display = ehEmail ? 'flex' : 'none';
    el.phoneField.style.display = ehEmail ? 'none' : 'flex';
    limparErro();
  }

  /* ----------------------------------------------------------
     SUCESSO — mensagem por canal + senha provisória na tela
  ---------------------------------------------------------- */
  function mostrarSucesso(canal, destinoMascarado, senha) {
    if (canal === 'email') {
      el.successMsg.innerHTML =
        'Uma senha provisória foi enviada para o e-mail <strong id="success-target"></strong>.';
    } else {
      el.successMsg.innerHTML =
        'Uma senha provisória foi enviada por SMS para <strong id="success-target"></strong>.';
    }
    // re-busca o <strong> recém-criado e injeta o destino com textContent (sem HTML)
    var target = byId('success-target');
    if (target) target.textContent = destinoMascarado;

    if (el.provSenha) el.provSenha.textContent = senha;

    el.formState.style.display    = 'none';
    el.successState.style.display = 'flex';

    // foco no título pra leitores de tela anunciarem a troca de estado
    if (el.successTitle) {
      el.successTitle.setAttribute('tabindex', '-1');
      el.successTitle.focus();
    }
  }

  function piscarBox() {
    if (!el.provBox) return;
    el.provBox.classList.remove('prov-box--flash');
    void el.provBox.offsetWidth; // força reflow pra reiniciar a animação
    el.provBox.classList.add('prov-box--flash');
  }

  /* ----------------------------------------------------------
     COPIAR — Clipboard API + fallback execCommand
  ---------------------------------------------------------- */
  function copiar(txt) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(txt).then(
        function () { return true; },
        function () { return fallbackCopy(txt); }
      );
    }
    return Promise.resolve(fallbackCopy(txt));
  }

  function fallbackCopy(txt) {
    try {
      var ta = document.createElement('textarea');
      ta.value = txt;
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      var ok = document.execCommand('copy');
      document.body.removeChild(ta);
      return ok;
    } catch (e) {
      return false;
    }
  }

  function flashLabel(btn, msg) {
    if (!btn) return;
    var label = btn.querySelector('[data-copy-label]') || btn;
    var prev = label.textContent;
    label.textContent = msg;
    setTimeout(function () { label.textContent = prev; }, 1500);
  }

  /* ----------------------------------------------------------
     HANDLERS
  ---------------------------------------------------------- */
  function handleSubmit(e) {
    e.preventDefault();
    if (state.enviando) return;
    limparErro();

    var canal = state.canal;
    var raw, destino;

    if (canal === 'email') {
      raw = el.inputEmail.value;
      if (!raw.trim())        { mostrarErro('Informe o e-mail cadastrado.'); return; }
      if (!emailValido(raw))  { mostrarErro('E-mail inválido. Confira o formato.'); return; }
      destino = mascararEmail(raw);
    } else {
      raw = el.inputPhone.value;
      if (!soDigitos(raw))       { mostrarErro('Informe o número de celular.'); return; }
      if (!telefoneValido(raw))  { mostrarErro('Número inválido. Use DDD + número.'); return; }
      destino = mascararTelefone(raw);
    }

    setLoading(true);
    setTimeout(function () {
      var senha = gerarSenha();
      var ok = salvarSenha(senha);
      setLoading(false);

      if (!ok) {
        mostrarErro('Não foi possível salvar a senha (armazenamento bloqueado).');
        return;
      }
      mostrarSucesso(canal, destino, senha);
    }, FAKE_DELAY_MS);
  }

  function handleResend() {
    var senha = gerarSenha();
    if (!salvarSenha(senha)) return;
    if (el.provSenha) el.provSenha.textContent = senha;
    piscarBox();
  }

  function handleCopy() {
    if (!el.provSenha) return;
    var senha = el.provSenha.textContent;
    copiar(senha).then(function (ok) {
      if (ok) flashLabel(el.btnCopy, 'Copiado!');
    });
  }

  /* ----------------------------------------------------------
     INIT
  ---------------------------------------------------------- */
  function init() {
    mapearDom();

    // guarda: se a estrutura do HTML não bate, aborta sem quebrar a página
    if (!el.form || !el.formState || !el.successState) return;

    el.btnEmail.addEventListener('click', function () { selecionarCanal('email'); });
    el.btnSms.addEventListener('click',   function () { selecionarCanal('sms'); });
    el.form.addEventListener('submit', handleSubmit);

    if (el.linkResend) el.linkResend.addEventListener('click', handleResend);
    if (el.btnCopy)    el.btnCopy.addEventListener('click', handleCopy);

    // limpa o erro assim que o usuário começa a corrigir o campo
    if (el.inputEmail) el.inputEmail.addEventListener('input', limparErro);
    if (el.inputPhone) el.inputPhone.addEventListener('input', limparErro);

    selecionarCanal('email');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
