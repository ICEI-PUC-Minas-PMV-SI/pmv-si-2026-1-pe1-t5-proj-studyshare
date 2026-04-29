# Programação de Funcionalidades

Implementação do sistema descritas por meio dos requisitos funcionais e/ou não funcionais. Deve relacionar os requisitos atendidos os artefatos criados (código fonte) além das estruturas de dados utilizadas e as instruções para acesso e verificação da implementação que deve estar funcional no ambiente de hospedagem.

Para cada requisito funcional, pode ser entregue um artefato desse tipo.

O professor Rommel Carneiro apresenta alguns exemplos prontos para serem utilizados como referência:
- Login do sistema: [https://repl.it/@rommelpuc/LoginApp](https://repl.it/@rommelpuc/LoginApp) 
- Cadastro de Contatos: [https://repl.it/@rommelpuc/Cadastro-de-Contatos](https://repl.it/@rommelpuc/Cadastro-de-Contatos)


> **Links Úteis**:
>
> - [Trabalhando com HTML5 Local Storage e JSON](https://www.devmedia.com.br/trabalhando-com-html5-local-storage-e-json/29045)
> - [JSON Tutorial](https://www.w3resource.com/JSON)
> - [JSON Data Set Sample](https://opensource.adobe.com/Spry/samples/data_region/JSONDataSetSample.html)
> - [JSON - Introduction (W3Schools)](https://www.w3schools.com/js/js_json_intro.asp)
> - [JSON Tutorial (TutorialsPoint)](https://www.tutorialspoint.com/json/index.htm)

## Exemplo

## Requisitos Atendidos

As tabelas que se seguem apresentam os requisitos funcionais e não-funcionais que relacionam o escopo do projeto com os artefatos criados:

### Requisitos Funcionais

|ID    | Descrição do Requisito | Responsável | Artefato Criado |
|------|------------------------|------------|-----------------|
|RF-01| O sistema deve permitir que o estudante faça upload de arquivos nos formatos PDF, resumos (texto) e slides. | Yago Silva | upload.html |
|RF-02| O sistema deve oferecer um motor de busca que permita filtrar materiais por disciplina, curso ou palavras-chave. | Otavio Santos | feed.html |
|RF-03| O sistema deve permitir a postagem de perguntas e respostas vinculadas aos usuários. | Ana Paula | visualizacao-conteudo.html |
|RF-04| O sistema deve permitir que usuários "curtam" ou atribuam notas (estrelas/pontuação) aos materiais. | Otavio Santos | feed.html |
|RF-05| O sistema deve permitir que o usuário salve materiais em uma lista de "Favoritos" para acesso rápido. | Ana Paula | visualizacao-conteudo.html |
|RF-06| O sistema deve permitir a criação de conta e autenticação de usuários. | Ana Paula | cadastro-usuario.html |
|RF-07| O sistema deve permitir que o estudante visualize e edite suas informações pessoais e histórico de atividades. | João Victor | perfil-usuario.html |
|RF-08| O sistema deve permitir que um usuário siga outros perfis para receber atualizações de novos conteúdos. | João Victor | perfil-usuario.html |
|RF-10| O sistema deve permitir que o administrador remova ou edite conteúdos publicados. | Livia Moreira | administracao-conteudo.html |
|RF-11| O sistema deve permitir que usuários denunciem conteúdos e que o administrador visualize e trate esses chamados. | Livia Moreira | administracao-conteudo.html |
|RF-12| O sistema deve permitir ao administrador banir ou suspender contas que violem os termos de uso. | Livia Moreira | administracao-usuarios.html |
|RF-13| O sistema deve permitir a redefinição de senha através de um link enviado por e-mail ou SMS. | Yago Silva | redefinicao-senha.html |
|RF-15| O sistema deve permitir que o usuário visualize as primeiras 3 páginas de um PDF antes de decidir fazer o download ou favoritar. | Otavio Santos | feed.html |
|RF-16| O sistema deve exibir no feed principal os materiais e perguntas mais recentes das disciplinas que o usuário selecionou como "Interesses" no perfil. | Otavio Santos | feed.html |
|RF-17| O sistema deve permitir marcar outros usuários em comentários ou dúvidas utilizando o caractere @ seguido do nome de usuário. | Ana Paula | visualizacao-conteudo.html |
|RF-18| O sistema deve possuir um filtro automático que impede a publicação de mensagens contendo palavras de baixo calão ou links externos suspeitos. | Ana Paula | visualizacao-conteudo.html |


## Descrição das estruturas:

## Notícia
|  **Nome**      | **Tipo**          | **Descrição**                             | **Exemplo**                                    |
|:--------------:|-------------------|-------------------------------------------|------------------------------------------------|
| Id             | Numero (Inteiro)  | Identificador único da notícia            | 1                                              |
| Título         | Texto             | Título da notícia                         | Sistemas de Informação PUC Minas é o melhor                                   |
| Conteúdo       | Texto             | Conteúdo da notícia                       | Sistemas de Informação da PUC Minas é eleito o melhor curso do Brasil                            |
| Id do usuário  | Numero (Inteiro)  | Identificador do usuário autor da notícia | 1                                              |

