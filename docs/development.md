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
|RF-02| O sistema deve oferecer um motor de busca que permita filtrar materiais por disciplina, curso ou palavras-chave. | Otavio Santos | src/feed/index.html, src/feed/feed.js |
|RF-03| O sistema deve permitir a postagem de perguntas e respostas vinculadas aos usuários. | Ana Paula | visualizacao-conteudo.html |
|RF-04| O sistema deve permitir que usuários "curtam" ou atribuam notas (estrelas/pontuação) aos materiais. | Otavio Santos | src/feed/index.html, src/feed/feed.js |
|RF-05| O sistema deve permitir que o usuário salve materiais em uma lista de "Favoritos" para acesso rápido. | Ana Paula | visualizacao-conteudo.html |
|RF-06| O sistema deve permitir a criação de conta e autenticação de usuários. | Ana Paula | cadastro-usuario.html |
|RF-07| O sistema deve permitir que o estudante visualize e edite suas informações pessoais e histórico de atividades. | João Victor | perfil-usuario.html |
|RF-08| O sistema deve permitir que um usuário siga outros perfis para receber atualizações de novos conteúdos. | João Victor | perfil-usuario.html |
|RF-10| O sistema deve permitir que o administrador remova ou edite conteúdos publicados. | Livia Moreira | administracao-conteudo.html |
|RF-11| O sistema deve permitir que usuários denunciem conteúdos e que o administrador visualize e trate esses chamados. | Livia Moreira | administracao-conteudo.html |
|RF-12| O sistema deve permitir ao administrador banir ou suspender contas que violem os termos de uso. | Livia Moreira | administracao-usuarios.html |
|RF-13| O sistema deve permitir a redefinição de senha através de um link enviado por e-mail ou SMS. | Yago Silva | redefinicao-senha.html |
|RF-15| O sistema deve permitir que o usuário visualize as primeiras 3 páginas de um PDF antes de decidir fazer o download ou favoritar. | Otavio Santos | src/feed/index.html |
|RF-16| O sistema deve exibir no feed principal os materiais e perguntas mais recentes das disciplinas que o usuário selecionou como "Interesses" no perfil. | Otavio Santos | src/feed/index.html, src/feed/feed.js |
|RF-17| O sistema deve permitir marcar outros usuários em comentários ou dúvidas utilizando o caractere @ seguido do nome de usuário. | Ana Paula | visualizacao-conteudo.html |
|RF-18| O sistema deve possuir um filtro automático que impede a publicação de mensagens contendo palavras de baixo calão ou links externos suspeitos. | Ana Paula | visualizacao-conteudo.html |

## Funcionalidade: Tela de Feed

Responsável: Otavio Santos

Arquivos criados:

- `src/feed/index.html`
- `src/feed/feed.css`
- `src/feed/feed.js`

A tela de Feed apresenta materiais acadêmicos publicados pelos usuários e representa os conteúdos recentes relacionados aos interesses do estudante. A página possui menu lateral, cabeçalho, formulário de busca, cards de materiais, prévia de PDF e painel de interesses.

Requisitos atendidos:

- `RF-02`: o formulário de busca permite filtrar os cards por palavra-chave, disciplina, curso e tipo de material.
- `RF-04`: os cards possuem botão de curtida e avaliação por estrelas, com atualização visual feita em JavaScript.
- `RF-15`: o card de PDF apresenta uma prévia visual das três primeiras páginas do material.
- `RF-16`: o painel de interesses representa as disciplinas selecionadas pelo usuário e o feed exibe conteúdos associados a essas áreas.

Estruturas e dados utilizados:

- Os cards utilizam atributos `data-*` para armazenar informações como título, descrição, tipo, disciplina, curso e autor.
- O JavaScript lê esses atributos para aplicar os filtros de busca sem recarregar a página.
- As curtidas são armazenadas no `localStorage` pela chave `studyshare-feed-liked`.
- As avaliações por estrelas são armazenadas no `localStorage` pela chave `studyshare-feed-ratings`.
- A função de normalização remove acentos e converte textos para minúsculas, permitindo buscar termos como "banco" ou "algoritmos" com maior tolerância.

Instruções para verificação:

1. Abrir o arquivo `src/feed/index.html` no navegador.
2. Digitar um termo no campo de palavra-chave, por exemplo `banco` ou `algoritmos`.
3. Alterar os filtros de disciplina, curso ou tipo e verificar a atualização da quantidade de materiais encontrados.
4. Clicar no botão `Curtir` de um material e verificar a alteração para `Curtido` e o aumento da contagem.
5. Clicar nas estrelas para alterar a nota do material.
6. Recarregar a página e verificar que curtidas e avaliações continuam salvas no navegador por meio do `localStorage`.

Observação: nesta etapa, os dados exibidos na tela são simulados no front-end. A integração com banco de dados e backend pode ser realizada em etapa posterior do projeto.

## Descrição das estruturas:

## Notícia
|  **Nome**      | **Tipo**          | **Descrição**                             | **Exemplo**                                    |
|:--------------:|-------------------|-------------------------------------------|------------------------------------------------|
| Id             | Numero (Inteiro)  | Identificador único da notícia            | 1                                              |
| Título         | Texto             | Título da notícia                         | Sistemas de Informação PUC Minas é o melhor                                   |
| Conteúdo       | Texto             | Conteúdo da notícia                       | Sistemas de Informação da PUC Minas é eleito o melhor curso do Brasil                            |
| Id do usuário  | Numero (Inteiro)  | Identificador do usuário autor da notícia | 1                                              |
