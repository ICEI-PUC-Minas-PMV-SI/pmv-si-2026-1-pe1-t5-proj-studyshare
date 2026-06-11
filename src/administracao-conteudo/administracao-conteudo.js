document.addEventListener("DOMContentLoaded", () => {

    const buttons = document.querySelectorAll('.filter-btn');
    const contents = document.querySelectorAll('.tab-content');

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            buttons.forEach(btn => btn.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');

            const targetId = button.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });


    // A mesma chave usada no seu sistema de cadastro (estilo-cadastro-usuario.js)
    const LS_KEY_USUARIOS = 'ss_usuarios';

    function atualizarMetricaUsuarios() {
        try {
            // Puxa a string do localStorage (se não tiver nada, puxa uma string de array vazio)
            const dadosSalvos = localStorage.getItem(LS_KEY_USUARIOS) || '[]';
            
            // Transforma a string de volta em uma lista (Array)
            const listaUsuarios = JSON.parse(dadosSalvos);
            
            // O número total é o tamanho da lista
            const totalReal = listaUsuarios.length;
            
            // Pega o span no HTML pelo ID que adicionamos
            const elementoContador = document.getElementById('total-usuarios-dash');
            
            if (elementoContador) {
                // Se o número for maior que 1000, formata com ponto (ex: 1.256)
                elementoContador.textContent = totalReal.toLocaleString('pt-BR');
            }
        } catch (erro) {
            console.error("Erro ao ler dados do localStorage:", erro);
        }
    }

    // Chama a função para atualizar o número assim que a página carregar
    atualizarMetricaUsuarios();
});