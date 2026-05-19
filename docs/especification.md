# Especificações do Projeto

Definição do problema e ideia de solução a partir da perspectiva do usuário. É composta pela definição do  diagrama de personas, histórias de usuários, requisitos funcionais e não funcionais além das restrições do projeto.

Apresente uma visão geral do que será abordado nesta parte do documento, enumerando as técnicas e/ou ferramentas utilizadas para realizar a especificações do projeto.

Caso deseje atribuir uma imagem a sua persona, utilize o site https://thispersondoesnotexist.com/

## Personas

Pedro Paulo tem 26 anos, é arquiteto recém-formado e autônomo. Pensa em se desenvolver profissionalmente através de um mestrado fora do país, pois adora viajar, é solteiro e sempre quis fazer um intercâmbio. Está buscando uma agência que o ajude a encontrar universidades na Europa que aceitem alunos estrangeiros.

Enumere e detalhe as personas da sua solução. Para tanto, baseie-se tanto nos documentos disponibilizados na disciplina e/ou nos seguintes links:

> **Links Úteis**:
> - [Rock Content](https://rockcontent.com/blog/personas/)
> - [Hotmart](https://blog.hotmart.com/pt-br/como-criar-persona-negocio/)
> - [O que é persona?](https://resultadosdigitais.com.br/blog/persona-o-que-e/)
> - [Persona x Público-alvo](https://flammo.com.br/blog/persona-e-publico-alvo-qual-a-diferenca/)
> - [Mapa de Empatia](https://resultadosdigitais.com.br/blog/mapa-da-empatia/)
> - [Mapa de Stalkeholders](https://www.racecomunicacao.com.br/blog/como-fazer-o-mapeamento-de-stakeholders/)
>
Lembre-se que você deve ser enumerar e descrever precisamente e personalizada todos os clientes ideais que sua solução almeja.

## Histórias de Usuários

Com base na análise das personas forma identificadas as seguintes histórias de usuários:

|EU COMO... `PERSONA`| QUERO/PRECISO ... `FUNCIONALIDADE` |PARA ... `MOTIVO/VALOR`                 |
|--------------------|------------------------------------|----------------------------------------|
|Usuário do sistema  | Registrar minhas tarefas           | Não esquecer de fazê-las               |
|Administrador       | Alterar permissões                 | Permitir que possam administrar contas |

Apresente aqui as histórias de usuário que são relevantes para o projeto de sua solução. As Histórias de Usuário consistem em uma ferramenta poderosa para a compreensão e elicitação dos requisitos funcionais e não funcionais da sua aplicação. Se possível, agrupe as histórias de usuário por contexto, para facilitar consultas recorrentes à essa parte do documento.

> **Links Úteis**:
> - [Histórias de usuários com exemplos e template](https://www.atlassian.com/br/agile/project-management/user-stories)
> - [Como escrever boas histórias de usuário (User Stories)](https://medium.com/vertice/como-escrever-boas-users-stories-hist%C3%B3rias-de-usu%C3%A1rios-b29c75043fac)
> - [User Stories: requisitos que humanos entendem](https://www.luiztools.com.br/post/user-stories-descricao-de-requisitos-que-humanos-entendem/)
> - [Histórias de Usuários: mais exemplos](https://www.reqview.com/doc/user-stories-example.html)
> - [9 Common User Story Mistakes](https://airfocus.com/blog/user-story-mistakes/)

## Requisitos

Esta seção detalha os requisitos funcionais e não funcionais da solução, criados diretamente das Histórias de Usuário levantadas junto aos estudantes e administradores. Cada requisito foi mapeado para garantir que todas as necessidades de compartilhamento de materiais, colaboração acadêmica e moderação da plataforma sejam atendidas, estabelecendo uma correspondência entre as funcionalidades desejadas e as características técnicas da solução.

### Requisitos Funcionais

| ID | Descrição | Prioridade |
| :--- | :--- | :--- |
| RF- 01 | O sistema deve permitir que o estudante faça upload de arquivos nos formatos PDF, resumos (texto) e slides. | Alta |
| RF- 02 | O sistema deve oferecer um motor de busca que permita filtrar materiais por disciplina, curso ou palavras-chave. | Alta |
| RF- 03 | O sistema deve permitir a postagem de perguntas e respostas vinculadas aos usuários. | Alta |
| RF- 04 | O sistema deve permitir que usuários "curtam" ou atribuam notas (estrelas/pontuação) aos materiais. | Média |
| RF- 05 | O sistema deve permitir que o usuário salve materiais em uma lista de "Favoritos" para acesso rápido. | Média |
| RF- 06 | O sistema deve permitir a criação de conta e autenticação de usuários. | Alta |
| RF- 07 | O sistema deve permitir que o estudante visualize e edite suas informações pessoais e histórico de atividades. | Média |
| RF- 08 | O sistema deve permitir que um usuário siga outros perfis para receber atualizações de novos conteúdos. | Alta |
| RF- 09 | O sistema deve enviar alertas sobre interações (novas respostas, seguidores ou novos materiais). | Média |
| RF- 10 | O sistema deve permitir que o administrador remova ou edite conteúdos publicados. | Alta |
| RF- 11 | O sistema deve permitir que usuários denunciem conteúdos e que o administrador visualize e trate esses chamados. | Alta |
| RF- 12 | O sistema deve permitir ao administrador banir ou suspender contas que violem os termos de uso. | Alta |
| RF- 13 | O sistema deve permitir a redefinição de senha através de um link enviado por e-mail ou SMS. | Alta |
| RF- 14 | O sistema deve atribuir "Pontos de Contribuição" ao usuário sempre que seu material for curtido. | Baixa |
| RF- 15 | O sistema deve permitir que o usuário visualize as primeiras 3 páginas de um PDF antes de decidir fazer o download ou favoritar. | Baixa |
| RF- 16 | O sistema deve exibir no feed principal os materiais e perguntas mais recentes das disciplinas que o usuário selecionou como "Interesses" no perfil. | Média |
| RF- 17 | O sistema deve permitir marcar outros usuários em comentários ou dúvidas utilizando o caractere @ seguido do nome de usuário. | Baixa |
| RF- 18 | O sistema deve possuir um filtro automático que impede a publicação de mensagens contendo palavras de baixo calão ou links externos suspeitos. | Média |

**Prioridade:** Alta / Média / Baixa.

### Requisitos Não Funcionais

| ID | Descrição | Prioridade |
| :--- | :--- | :--- |
| RNF- 01 | A busca por materiais deve retornar resultados em menos de 2 segundos. | Média |
| RNF- 02 | O sistema deve suportar arquivos de até 50MB por upload (limite para PDFs/Slides). | Alta |
| RNF- 03 | A interface deve ser responsiva, adaptando-se a dispositivos móveis e desktops. | Alta |
| RNF- 04 | O sistema deve permitir que o usuário exclua seus dados permanentemente, seguindo a Lei Geral de Proteção de Dados. | Alta |
| RNF- 05 | O usuário deve ter a opção de tornar seu perfil "Privado", ocultando sua lista de materiais favoritos de outros usuários. | Média |
| RNF- 06 | A plataforma deve oferecer um "Modo Noturno" (Dark Mode) nativo para reduzir o cansaço visual durante longas horas de estudo. | Baixa |
| RNF- 07 | As páginas de materiais públicos devem ser otimizadas para busca, garantindo que títulos de documentos apareçam nos resultados de pesquisa externa. | Média |

**Prioridade:** Alta / Média / Baixa.


## Restrições

O projeto está restrito pelos itens apresentados na tabela a seguir.

|ID| Restrição                                             |
|--|-------------------------------------------------------|
|01| O projeto deverá ser entregue até o final do semestre |
|02| Não pode ser desenvolvido um módulo de backend        |


