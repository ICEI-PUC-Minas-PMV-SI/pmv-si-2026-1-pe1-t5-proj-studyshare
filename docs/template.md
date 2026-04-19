# Template padrão do site

Layout padrão do site (HTML e CSS) que será utilizado em todas as páginas com a definição de identidade visual, aspectos de responsividade e iconografia.

>Estas diretrizes devem ser seguidas rigorosamente em todas as etapas de desenvolvimento da interface (UI) e implementação de componentes para garantir a consistência da marca.

## Design

<p align="center">
  <img src="https://github.com/ICEI-PUC-Minas-PMV-SI/pmv-si-2026-1-pe1-t5-proj-studyshare/blob/main/docs/img/designprincipal_studyshare.png" alt="Página inicial do StudyShare" width="600">
  <br>
  <em>Figura 1: Página inicial do StudyShare</em>
</p>

Interface baseada em **Cabeçalho + Menu Principal**, com conteúdo central organizado em **campo de busca e cards de publicações de conteúdos educacionais**.

## Logo da Aplicação

<table align="center">
  <tr>
    <td align="center">
      <img src="https://github.com/ICEI-PUC-Minas-PMV-SI/pmv-si-2026-1-pe1-t5-proj-studyshare/blob/main/docs/img/logostudyshare.png" alt="Logo do StudyShare" width="150">
      <br>
      <em>Figura 2: Logo da StudyShare</em>
    </td>
    <td>
      <table>
        <tr><th>Propriedade</th><th>Descrição</th></tr>
        <tr><td><b>Localização</b></td><td>Canto superior esquerdo, dentro do menu lateral</td></tr>
        <tr><td><b>Nome exibido</b></td><td>StudyShare</td></tr>
        <tr><td><b>Função</b></td><td>Redireciona para a tela inicial</td></tr>
        <tr><td><b>Acessibilidade</b></td><td><code>alt="Logo StudyShare - Início"</code></td></tr>
      </table>
    </td>
  </tr>
</table>

## Cores

Apresente a paleta de cores que será utilizada. Uma ferramenta interessante para a criação de palestas de cores é o *Adobe Color* ([https://color.adobe.com/pt/create/color-wheel](https://color.adobe.com/pt/create/color-wheel)).


## Menu Lateral
|![menulateral](https://github.com/ICEI-PUC-Minas-PMV-SI/pmv-si-2026-1-pe1-t5-proj-studyshare/blob/main/docs/img/menulateral-conteudoprinciapal/menulateral.png)|
|:------------------------------------------------------------------------------------------------:|
| **Figura 1:** Menu lateral da StudyShare |

| Elemento                  | Função                            |
|---------------------------|-----------------------------------|
| Botão Feed             | Direciona o usuário para a página inicial |
| Botão Meus materiais                   | Exibe os arquivos e materiais enviados pelo próprio usuário |
| Botão Explorar                   |  Permite descobrir e pesquisar materiais compartilhados pela comunidade |
| Botão Salvos                    | Lista os materiais que o usuário marcou para salvar/favoritar |
| Botão Configurações                     |Abre as opções de configuração da conta e do sistema |
| Botão Novo material                     |Permite ao usuário enviar e compartilhar novos materiais de estudo na plataforma |

## Área de Conteúdo Principal

| ![conteudoprinciapal](https://github.com/ICEI-PUC-Minas-PMV-SI/pmv-si-2026-1-pe1-t5-proj-studyshare/blob/main/docs/img/menulateral-conteudoprinciapal/conteudoprincipal.png) |
|:----------------------------------------------------------------------------------------------------------:|
| **Figura 2:** Área de conteúdo principal da StudyShare |
- Interface baseada em cards para exibição dos materiais de estudo compartilhados pelos usuários
- Cada card apresenta informações essenciais como autor, título, tipo de conteúdo e pré-visualização
- Utilização de indicadores de engajamento (visualizações, curtidas e comentários) para destacar a relevância dos materiais
- Organização em layout responsivo em formato de grade, permitindo melhor distribuição e leitura dos conteúdos
- Uso de elementos visuais como cores e tags para facilitar a identificação rápida dos diferentes tipos de materiais

## Tipografia

A tipografia utilizada na plataforma será a Inter, uma fonte sem serifa altamente otimizada para interfaces digitais. Ela foi projetada para oferecer excelente legibilidade em diferentes tamanhos e densidades de informação, sendo especialmente adequada para dashboards, sistemas baseados em cards e navegação estruturada.

---

## Hierarquia e Função

| Elemento                     | Estilo Tipográfico                                      | Função principal                                                                 |
|----------------------------|----------------------------------------------------------|----------------------------------------------------------------------------------|
| Título de Página            | Inter, 28px, Semi-Bold (600)                             | Indicar o tema principal da página                                              |
| Título de Seção             | Inter, 22px, Medium (500)                                | Organizar e destacar subdivisões do conteúdo                                    |
| Navegação (abas/menu)       | Inter, 14px, Medium (500)                                | Permitir alternância entre categorias (Feed, Explorar, etc.)                    |
| Título em cards             | Inter, 16px, Semi-Bold (600)                             | Destacar informações principais dentro dos cards                                |
| Descrição em cards          | Inter, 13px, Regular (400)                               | Complementar informações exibidas nos cards                                     |
| Rótulos de Componentes      | Inter, 14px, Medium (500)                                | Identificar campos, botões e elementos interativos                              |
| Corpo de Texto              | Inter, 14px, Regular (400), line-height 1.5              | Apresentar conteúdos descritivos, instruções e materiais                        |
| Metadados (likes, views)    | Inter, 12px, Regular (400)                               | Exibir informações secundárias nos cards                                        |

---

## Observações de Uso

- Priorizar os pesos **Regular (400)** e **Medium (500)** para a maior parte da interface, aproveitando a clareza natural da Inter.
- Utilizar **Semi-Bold (600)** para títulos e elementos que exigem maior destaque, evitando o uso excessivo de Bold (700), já que a Inter possui contraste suficiente em pesos intermediários.
- Manter **line-height entre 1.5 e 1.6** para blocos de texto e descrições, garantindo boa leitura em ambientes densos como feeds.
- Aproveitar a boa renderização da Inter em tamanhos pequenos (12px–14px), ideal para metadados e informações secundárias nos cards.
- Garantir consistência tipográfica entre navegação lateral, topo e conteúdo para reforçar a hierarquia visual do sistema.
- Evitar misturar muitos pesos diferentes na mesma tela — a força da Inter está na sutileza das variações.

## Iconografia

A iconografia do sistema utiliza **Material Symbols (Google)** para garantir consistência, escalabilidade e flexibilidade visual.

Os ícones são baseados em fonte variável, permitindo ajustes dinâmicos de estilo como preenchimento, espessura e tamanho óptico.

---

## Importação dos Ícones

Utilize o Google Fonts para carregar apenas os ícones necessários (melhor performance):

```
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&icon_names=home,search,settings,person" rel="stylesheet" />
```

> Evite carregar todos os ícones — isso pode aumentar o tamanho do bundle desnecessariamente. ([Google for Developers][1])

---

## Classe Base

```
.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-weight: 400;
    font-style: normal;
    font-size: 24px;
    display: inline-block;
    line-height: 1;
    vertical-align: middle;
}
```

---

## Eixos de Customização (Material Symbols)

Os ícones permitem customização via CSS:

```
.icon {
    font-variation-settings:
        'FILL' 0,   /* 0 = outline | 1 = preenchido */
        'wght' 400, /* espessura (100–700) */
        'GRAD' 0,   /* ajuste fino de contraste */
        'opsz' 24;  /* tamanho óptico (20–48) */
}
```

### Boas práticas

* Use `FILL = 1` para estados ativos/selecionados
* Use `wght` maior para dar destaque
* Use `GRAD` para ajuste fino em temas escuros
* Use `opsz` conforme o tamanho do ícone

---

## Exemplo de Uso

```
<span class="material-symbols-outlined icon">home</span>
```

---

## Tabela de Ícones

| Ícone | Nome | Função |
|------|------|--------|
| <img src="./icons/home.svg" width="20"/> | `home` | Dashboard |
| <img src="./icons/book2.svg" width="20"/> | `book2` | Materiais |
| <img src="./icons/explore.svg" width="20"/> | `explore` | Explorar |
| <img src="./icons/bookmark.svg" width="20"/> | `bookmark` | Salvos |
| <img src="./icons/person.svg" width="20"/> | `person` | Perfil |
| <img src="./icons/settings.svg" width="20"/> | `settings` | Configurações |
| <img src="./icons/search.svg" width="20"/> | `search` | Busca |
| <img src="./icons/upload.svg" width="20"/> | `upload` | Upload |
| <img src="./icons/add_circle.svg" width="20"/> | `add_circle` | Novo |
| <img src="./icons/edit_square.svg" width="20"/> | `edit_square` | Editar |
| <img src="./icons/delete.svg" width="20"/> | `delete` | Excluir |
| <img src="./icons/download.svg" width="20"/> | `download` | Download |
| <img src="./icons/ios_share.svg" width="20"/> | `ios_share` | Compartilhar |
| <img src="./icons/filter_list.svg" width="20"/> | `filter_list` | Filtro |
| <img src="./icons/sell.svg" width="20"/> | `sell` | Categoria |
| <img src="./icons/grid_view.svg" width="20"/> | `grid_view` | Grade |
| <img src="./icons/view_list.svg" width="20"/> | `view_list` | Lista |
| <img src="./icons/visibility.svg" width="20"/> | `visibility` | Visualizar |
| <img src="./icons/favorite.svg" width="20"/> | `favorite` | Curtir |
| <img src="./icons/chat_bubble.svg" width="20"/> | `chat_bubble` | Comentário |
| <img src="./icons/warning.svg" width="20"/> | `warning` | Alerta |
| <img src="./icons/close.svg" width="20"/> | `close` | Fechar |
| <img src="./icons/arrow_forward.svg" width="20"/> | `arrow_forward` | Navegação |

---

## Variações de Tamanho

```
.icon-sm { font-size: 16px; }
.icon-md { font-size: 24px; }
.icon-lg { font-size: 32px; }
```

---

## Variações de Cor

```
.icon-primary   { color: #1D4ED8; }
.icon-secondary { color: #64748B; }
.icon-success   { color: #22C55E; }
.icon-danger    { color: #EF4444; }
.icon-muted     { color: #CBD5F5; }
```

---

## Estados de Interação

```
.icon-active {
    font-variation-settings: 'FILL' 1;
}

.icon-hover:hover {
    opacity: 0.8;
}
```

---

## Diretrizes de Uso

* Utilize preferencialmente o estilo **Outlined**
* Use `FILL` para indicar seleção/estado ativo
* Mantenha consistência de tamanho (`24px` padrão Material) ([Google for Developers][1])
* Evite misturar estilos (outlined, rounded, sharp)
* Use ícones com texto em menus
* Use ícones isolados apenas em ações claras

---

## Semântica de Cores

* 🟢 Verde → sucesso
* 🔴 Vermelho → erro
* 🔵 Azul → ação principal
* ⚪ Cinza → neutro/inativo

---

## Boas Práticas de Performance

* Carregue apenas os ícones usados (`icon_names`)
* Evite múltiplas famílias de ícones
* Prefira fonte ao invés de SVG quando possível
* Utilize cache do Google Fonts

---

## Observações

* Material Symbols possui mais de **2.500 ícones** e múltiplas variações ([Google for Developers][1])
* Permite animações via CSS (ex: transição de `FILL`)
* Substitui o antigo Material Icons com mais flexibilidade

[1]: https://developers.google.com/fonts/docs/material_symbols?utm_source=chatgpt.com "Material Symbols guide  |  Google Fonts  |  Google for Developers"

