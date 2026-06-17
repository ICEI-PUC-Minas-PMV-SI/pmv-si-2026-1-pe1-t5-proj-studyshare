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

## Funcionalidade: Cadastro de Usuário

Responsável: Ana Paula

Arquivos criados:

- `src/cadastro-usuario/cadastro-usuario.html`
- `src/cadastro-usuario/css/estilo-cadastro-usuario.css`
- `src/cadastro-usuario/javascript/cadastro-usuario.js`

A tela de Cadastro de Usuário permite que novos estudantes criem uma conta na plataforma StudyShare. O formulário coleta nome, sobrenome, e-mail, nome de usuário, instituição de ensino e senha, com validação em tempo real e persistência no `localStorage`. A página também cria automaticamente um perfil de demonstração caso o armazenamento local esteja vazio, garantindo que outras telas do sistema funcionem mesmo sem um cadastro prévio.

Requisitos atendidos:

- `RF-06`: o formulário valida todos os campos obrigatórios, verifica duplicidade de e-mail e nome de usuário, exibe indicador de força de senha e, ao concluir, persiste o usuário nas chaves `ss_usuarios` e `ss_usuario_logado` do `localStorage`.

Estruturas e dados utilizados:

- O objeto de usuário é salvo no array `ss_usuarios` e uma cópia é mantida em `ss_usuario_logado` representando a sessão ativa.
- A validação cobre comprimento mínimo e máximo, formato de e-mail, padrão de caracteres do nome de usuário e correspondência das senhas.
- Um usuário-padrão de demonstração (`estudante.demo@studyshare.com`) é criado automaticamente ao primeiro acesso caso o `localStorage` esteja vazio.

Instruções para verificação:

1. Abrir o arquivo `src/cadastro-usuario/cadastro-usuario.html` no navegador.
2. Preencher os campos do formulário com dados válidos e clicar em **Criar conta**.
3. Verificar o toast de confirmação e abrir o DevTools → Application → Local Storage para confirmar que o objeto foi salvo em `ss_usuarios` e `ss_usuario_logado`.
4. Tentar cadastrar um segundo usuário com o mesmo e-mail ou nome de usuário e verificar a mensagem de erro de duplicidade.
5. Recarregar a página e verificar que os dados persistem no `localStorage`.

Observação: nesta etapa, as senhas são tratadas somente no front-end para fins de demonstração. Em produção, o hash das senhas e a autenticação devem ser realizados no back-end.

---

## Funcionalidade: Visualização de Conteúdo

Responsável: Ana Paula

Arquivos criados:

- `src/visualizacao-conteudo/visualizacao-conteudo.html`
- `src/visualizacao-conteudo/css/estilo-visualizacao-conteudo.css`
- `src/visualizacao-conteudo/javascript/visualizacao-conteudo.js`

A tela de Visualização de Conteúdo exibe os detalhes de um material acadêmico selecionado a partir do feed ou do perfil do usuário. A página apresenta título, autor, descrição, tags, prévia do arquivo, estatísticas (visualizações, downloads e favoritos) e uma seção de comentários com respostas aninhadas. Todos os dados interativos são persistidos no `localStorage` por material.

Requisitos atendidos:

- `RF-03`: a seção de comentários permite que usuários publiquem perguntas e respostas vinculadas ao material, com persistência dos comentários e respostas no `localStorage`.
- `RF-05`: o botão de favoritar alterna o estado do material entre favoritado e não favoritado, atualizando o contador de favoritos e persistindo a escolha no `localStorage`.
- `RF-17`: o campo de comentário aceita a menção a outros usuários com o caractere `@` seguido do nome, permitindo marcação dentro do texto do comentário.
- `RF-18`: o sistema aplica um filtro automático que bloqueia a publicação de comentários contendo palavras de baixo calão ou URLs externas suspeitas, exibindo uma mensagem de alerta ao usuário.

Estruturas e dados utilizados:

- O material a ser exibido é lido da chave `ss_selected_material` do `localStorage`, populada pelo feed ou pela página de perfil no momento do clique.
- O estado de cada material (favorito, contadores e comentários) é salvo individualmente na chave `ss_material_{slug}`, onde `slug` é gerado a partir do título do material.
- Os comentários postados pelo usuário são incluídos no array `comentarios` dentro do estado do material, com campos de autor, iniciais, texto, curtidas e respostas.
- O nome e as iniciais do autor dos comentários são extraídos de `ss_usuario_logado`, garantindo que os comentários reflitam o usuário logado.

Instruções para verificação:

1. A partir do feed (`src/feed/index.html`), clicar em **Ver conteúdo** em qualquer card para navegar à página de visualização com os dados do material preenchidos automaticamente.
2. Clicar no botão de favoritar e verificar a alteração visual e o incremento do contador de favoritos.
3. Digitar um comentário no campo de texto e clicar em **Postar Comentário**; verificar que o comentário aparece abaixo com nome e iniciais do usuário logado.
4. Clicar em **Responder** em um comentário, digitar uma resposta e confirmar; verificar que a resposta aparece aninhada.
5. Recarregar a página e verificar que favorito, contadores e comentários continuam salvos via `localStorage`.
6. Tentar postar um comentário com palavra de baixo calão e verificar que a publicação é bloqueada com mensagem de alerta.

Observação: nesta etapa, a prévia do arquivo e o download são simulados no front-end. A integração com armazenamento de arquivos reais pode ser realizada em etapa posterior do projeto.

---

## Descrição das estruturas:

## Usuário
Chaves no `localStorage`: `ss_usuarios` (array) · `ss_usuario_logado` (objeto da sessão ativa)

|  **Nome**      | **Tipo**          | **Descrição**                                                        | **Exemplo**                          |
|:--------------:|-------------------|----------------------------------------------------------------------|--------------------------------------|
| id             | Número (Inteiro)  | Identificador único gerado por `Date.now()`                          | 1716393600000                        |
| nome           | Texto             | Primeiro nome do usuário                                             | Estudante                            |
| sobrenome      | Texto             | Sobrenome do usuário                                                 | Demo                                 |
| email          | Texto             | Endereço de e-mail único utilizado no login                          | estudante.demo@studyshare.com        |
| username       | Texto             | Nome de usuário único, sem espaços                                   | estudante_demo                       |
| instituicao    | Texto             | Instituição de ensino informada no cadastro (campo opcional)         | StudyShare University                |
| bio            | Texto             | Biografia exibida no perfil (preenchida após edição do perfil)       | Compartilhando resumos acadêmicos.   |
| criadoEm       | Texto (ISO 8601)  | Data e hora de criação da conta                                      | 2026-05-22T14:00:00.000Z             |

## Material Selecionado
Chave no `localStorage`: `ss_selected_material`

Objeto temporário gravado pelo feed ou pela página de perfil no momento em que o usuário clica para visualizar um material. Lido pela página de Visualização de Conteúdo ao carregar.

|  **Nome**      | **Tipo**  | **Descrição**                                            | **Exemplo**                                          |
|:--------------:|-----------|----------------------------------------------------------|------------------------------------------------------|
| title          | Texto     | Título do material                                       | Resumo de Modelagem Relacional                       |
| type           | Texto     | Tipo do arquivo                                          | PDF                                                  |
| discipline     | Texto     | Disciplina à qual o material está vinculado              | Banco de Dados                                       |
| course         | Texto     | Curso ao qual o material pertence                        | Sistemas de Informação                               |
| description    | Texto     | Descrição breve do conteúdo do material                  | Material com conceitos de entidade e relacionamento. |
| author         | Texto     | Nome completo do autor do material                       | Helena Campos                                        |
| date           | Texto     | Data de publicação no formato DD/MM/AAAA                 | 18/05/2026                                           |

## Estado do Material
Chave no `localStorage`: `ss_material_{slug}` (uma entrada por material, onde `slug` é gerado a partir do título)

|  **Nome**      | **Tipo**          | **Descrição**                                                              | **Exemplo**    |
|:--------------:|-------------------|----------------------------------------------------------------------------|----------------|
| favoritado     | Booleano          | Indica se o usuário atual favoritou o material                             | true           |
| downloads      | Número (Inteiro)  | Contador acumulado de downloads do material                                | 235            |
| visualizacoes  | Número (Inteiro)  | Contador acumulado de visualizações do material                            | 1206           |
| favoritos      | Número (Inteiro)  | Contador acumulado de vezes que o material foi favoritado                  | 46             |
| comentarios    | Array de objetos  | Lista de comentários postados pelo usuário na página do material           | (ver abaixo)   |

### Comentário (item do array `comentarios`)

|  **Nome**      | **Tipo**          | **Descrição**                                                  | **Exemplo**              |
|:--------------:|-------------------|----------------------------------------------------------------|--------------------------|
| id             | Texto             | Identificador único do comentário gerado por `Date.now()`     | c_1716393600000          |
| texto          | Texto             | Conteúdo do comentário postado pelo usuário                   | Ótimo material!          |
| autor          | Texto             | Nome completo do usuário que postou o comentário              | Estudante Demo           |
| initials       | Texto             | Iniciais do autor para exibição no avatar                     | ED                       |
| avatarColor    | Texto (hex/CSS)   | Cor de fundo gerada para o avatar do comentário               | #004AAD                  |
| datetime       | Texto (ISO 8601)  | Data e hora de criação do comentário                          | 2026-05-22T14:00:00.000Z |
| tempoLabel     | Texto             | Rótulo de tempo exibido na interface                          | Agora mesmo              |
| curtidas       | Número (Inteiro)  | Quantidade de curtidas recebidas pelo comentário              | 3                        |
| liked          | Booleano          | Indica se o usuário atual curtiu este comentário              | false                    |
| respostas      | Array de objetos  | Lista de respostas aninhadas ao comentário                    | []                       |
