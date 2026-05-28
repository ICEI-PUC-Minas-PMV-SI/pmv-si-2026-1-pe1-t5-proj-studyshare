/* ============================================================
   StudyShare — Tela de Recuperação de Senha
   Arquivo: redefinicao-senha.js

   Lógica de UI front-only — sem backend, sem validação real.
   Apenas simula o envio e exibe a tela de sucesso.
============================================================ */

'use strict';

(function () {

  // Garante que o DOM esteja pronto antes de querer manipular elementos.
  document.addEventListener('DOMContentLoaded', init);

  // Controla qual método está ativo no momento — email ou sms.
  let currentMethod = 'email';

  // Cache de referências aos elementos do DOM — evita re-consulta a cada uso.
  let elBtnEmail, elBtnSms, elEmailField, elPhoneField,
      elInputEmail, elInputPhone, elAlertBox, elAlertMsg,
      elBtnSubmit, elSpinner, elBtnIcon, elBtnLabel,
      elForm, elFormState, elSuccessState, elSuccessTarget,
      elSuccessMsg, elLinkResend;

  /* ============================================================
     INICIALIZAÇÃO
     Captura referências do DOM e registra os event listeners.
  ============================================================ */
  function init() {
    elBtnEmail       = document.getElementById('btn-email');
    elBtnSms         = document.getElementById('btn-sms');
    elEmailField     = document.getElementById('email-field');
    elPhoneField     = document.getElementById('phone-field');
    elInputEmail     = document.getElementById('input-email');
    elInputPhone     = document.getElementById('input-phone');
    elAlertBox       = document.getElementById('alert-box');
    elAlertMsg       = document.getElementById('alert-msg');
    elBtnSubmit      = document.getElementById('btn-submit');
    elSpinner        = document.getElementById('spinner');
    elBtnIcon        = document.getElementById('btn-icon');
    elBtnLabel       = document.getElementById('btn-label');
    elForm           = document.getElementById('recovery-form');
    elFormState      = document.getElementById('form-state');
    elSuccessState   = document.getElementById('success-state');
    elSuccessTarget  = document.getElementById('success-target');
    elSuccessMsg     = document.getElementById('success-msg');
    elLinkResend     = document.getElementById('link-resend');

    // Botões de método (E-mail / SMS)
    elBtnEmail.addEventListener('click', function () { setMethod('email'); });
    elBtnSms.addEventListener('click',   function () { setMethod('sms');   });

    // Máscara dinâmica de telefone
    elInputPhone.addEventListener('input', maskPhone);

    // Submit do formulário
    elForm.addEventListener('submit', handleSubmit);

    // Link "Reenviar agora" no estado de sucesso
    if (elLinkResend) elLinkResend.addEventListener('click', resetForm);
  }

  /* ============================================================
     ALTERNA O MÉTODO DE ENVIO (E-MAIL / SMS)
     Atualiza os botões, mostra/oculta o campo correto e foca o input.
  ============================================================ */
  function setMethod(m) {
    currentMethod = m;

    // toggle adiciona/remove 'active' conforme o botão clicado
    elBtnEmail.classList.toggle('active', m === 'email');
    elBtnSms.classList.toggle('active',   m === 'sms');

    // Mostra o campo do método ativo e oculta o outro
    elEmailField.style.display = m === 'email' ? 'flex' : 'none';
    elPhoneField.style.display = m === 'sms'   ? 'flex' : 'none';

    clearAlert(); // limpa qualquer alerta anterior ao trocar de método

    // Foca automaticamente o campo correto — melhora a UX
    (m === 'email' ? elInputEmail : elInputPhone).focus();
  }

  /* ============================================================
     MÁSCARA DE TELEFONE EM TEMPO REAL
     Formata o número enquanto o usuário digita: (11) 99999-9999
  ============================================================ */
  function maskPhone() {
    let v = this.value.replace(/\D/g, '').substring(0, 11); // só dígitos, max 11

    if      (v.length > 6) v = '(' + v.slice(0,2) + ') ' + v.slice(2,7) + '-' + v.slice(7);
    else if (v.length > 2) v = '(' + v.slice(0,2) + ') ' + v.slice(2);
    else if (v.length)     v = '(' + v; // ainda digitando o DDD

    this.value = v;
  }

  /* ============================================================
     BANNER DE ALERTA (apenas feedback visual de UI)
  ============================================================ */
  function showAlert(msg) {
    elAlertMsg.textContent = msg;
    elAlertBox.className   = 'alert is-error';
  }

  function clearAlert() {
    elAlertBox.className = 'alert';
  }

  /* ============================================================
     CONTROLA O ESTADO VISUAL DO BOTÃO DE ENVIO
     Durante o loading: desabilita o botão, troca ícone por spinner
     e muda o texto. Depois: restaura tudo ao estado original.
  ============================================================ */
  function setLoading(on) {
    elBtnSubmit.disabled    = on;
    elSpinner.style.display = on ? 'block'     : 'none';
    elBtnIcon.style.display = on ? 'none'      : 'inline-block';
    elBtnLabel.textContent  = on ? 'Enviando…' : 'Enviar senha provisória';
  }

  /* ============================================================
     TRANSIÇÃO PARA O ESTADO DE SUCESSO
     Oculta o formulário e exibe o bloco de confirmação,
     preenchendo a mensagem conforme o canal (e-mail vs SMS).
  ============================================================ */
  function showSuccess(target) {
    elFormState.style.display    = 'none';
    elSuccessState.style.display = 'flex';
    elSuccessTarget.textContent  = target || '';

    if (currentMethod === 'sms') {
      // Usa textContent + DOM API para não precisar concatenar HTML (mais seguro contra XSS)
      elSuccessMsg.innerHTML = '';
      elSuccessMsg.appendChild(document.createTextNode('Uma senha provisória foi enviada por SMS para '));
      const strong = document.createElement('strong');
      strong.id = 'success-target';
      strong.textContent = target || '';
      elSuccessMsg.appendChild(strong);
      elSuccessMsg.appendChild(document.createTextNode('.'));

      // Reatribui a referência porque o elemento foi recriado
      elSuccessTarget = strong;
    }
  }

  /* ============================================================
     RESETA O CARD PARA O ESTADO INICIAL
     Chamado pelo link "Reenviar agora".
  ============================================================ */
  function resetForm() {
    elSuccessState.style.display = 'none';
    elFormState.style.display    = 'block';
    elInputEmail.value = '';
    elInputPhone.value = '';
    clearAlert();
    setMethod(currentMethod);
  }

  /* ============================================================
     SUBMIT DO FORMULÁRIO — FRONT-ONLY (sem backend, sem validação)
     Apenas simula o carregamento e mostra o estado de sucesso.

     Quando houver backend, basta substituir o setTimeout abaixo
     por um fetch() real para a API, montando o body com o canal
     e o e-mail/telefone digitado.
  ============================================================ */
  function handleSubmit(e) {
    e.preventDefault(); // impede o reload da página
    clearAlert();

    // Pega o valor digitado só para exibir na tela de sucesso
    const valor = currentMethod === 'email'
      ? elInputEmail.value.trim()
      : elInputPhone.value.trim();

    setLoading(true);

    // Simula o tempo de envio (substituir por fetch() quando tiver backend)
    setTimeout(function () {
      setLoading(false);
      showSuccess(valor);
    }, 900);
  }

})();
