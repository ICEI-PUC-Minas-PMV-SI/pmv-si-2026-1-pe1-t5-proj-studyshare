document.addEventListener("DOMContentLoaded", () => {

    const buttons = document.querySelectorAll('.filter-btn');
    const contents = document.querySelectorAll('.tab-content');

    // Lógica das abas
    buttons.forEach(button => {
        button.addEventListener('click', () => {
            buttons.forEach(btn => btn.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');

            const targetId = button.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // ==========================================
    // MÉTRICAS DO LOCAL STORAGE
    // ==========================================

    const LS_KEY_USUARIOS = 'ss_usuarios'; 
    const LS_KEY_UPLOADS = 'ss_materiais';

    // Atualiza Total de Usuários
    function atualizarMetricaUsuarios() {
        try {
            const dadosSalvos = localStorage.getItem(LS_KEY_USUARIOS) || '[]';
            const listaUsuarios = JSON.parse(dadosSalvos);
            const totalReal = listaUsuarios.length;
            
            const elementoContador = document.getElementById('total-usuarios-dash');
            
            if (elementoContador) {
                elementoContador.textContent = totalReal.toLocaleString('pt-BR');
            }
        } catch (erro) {
            console.error("Erro ao ler dados de usuários do localStorage:", erro);
        }
    }

    // Atualiza Total de Uploads
    function atualizarMetricaUploads() {
        try {
            const dadosSalvos = localStorage.getItem(LS_KEY_UPLOADS) || '[]';
            const listaUploads = JSON.parse(dadosSalvos);
            const totalReal = listaUploads.length;
            
            const elementoContador = document.getElementById('total-uploads-dash');
            
            if (elementoContador) {
                elementoContador.textContent = totalReal.toLocaleString('pt-BR');
            }
        } catch (erro) {
            console.error("Erro ao ler dados de uploads do localStorage:", erro);
        }
    }

    // Chama as funções para atualizar os números assim que a página carregar
    atualizarMetricaUsuarios();
    atualizarMetricaUploads();
});