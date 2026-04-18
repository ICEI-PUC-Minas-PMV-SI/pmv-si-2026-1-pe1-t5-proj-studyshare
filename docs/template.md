# Template padrão do site

Layout padrão do site (HTML e CSS) que será utilizado em todas as páginas com a definição de identidade visual, aspectos de responsividade e iconografia.

Explique as guias de estilo utilizadas no seu projeto.

## Design

Detalhe os layouts que serão utilizados. Apresente onde será colocado o logo do sistema. Defina os menus padrões, entre outras coisas.


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

A tipografia utilizada na plataforma será a Inria Sans, uma fonte sem serifa moderna, reconhecida por sua excelente legibilidade em interfaces digitais e pela sua capacidade de construir hierarquias visuais claras em sistemas baseados em componentes como cards e navegação por abas.

---

## Hierarquia e Função

| Elemento                     | Estilo Tipográfico                                      | Função principal                                                                 |
|----------------------------|----------------------------------------------------------|----------------------------------------------------------------------------------|
| Título de Página            | Inria Sans, 28px, Bold (700)                             | Indicar o tema principal da página                                              |
| Título de Seção             | Inria Sans, 22px, Semi-Bold (600)                        | Organizar e destacar subdivisões do conteúdo                                    |
| Navegação (abas)            | Inria Sans, 14px, Medium (500)                           | Permitir alternância entre categorias (Feed, Artigos, etc.)                     |
| Título em cards             | Inria Sans, 16px, Semi-Bold (600)                        | Destacar informações principais dentro dos cards                                |
| Descrição em cards          | Inria Sans, 13px, Regular (400)                          | Complementar informações exibidas nos cards                                     |
| Rótulos de Componentes      | Inria Sans, 14px, Medium (500)                           | Identificar campos, botões e elementos interativos                              |
| Corpo de Texto              | Inria Sans, 14px, Regular (400), line-height 1.5         | Apresentar conteúdos descritivos, instruções e materiais                        |

---

## Observações de Uso

- Utilizar no máximo dois pesos principais na maior parte da interface (Regular e Semi-Bold) para manter consistência visual.
- Reservar o peso Bold para títulos principais e pontos de maior destaque.
- Garantir espaçamento adequado entre linhas (line-height entre 1.5 e 1.6) para melhor legibilidade, especialmente em conteúdos educacionais.
- Manter consistência tipográfica em todos os componentes para reforçar a identidade visual da plataforma.




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


> **Links Úteis**:
>
> -  [Como criar um guia de estilo de design da Web](https://edrodrigues.com.br/blog/como-criar-um-guia-de-estilo-de-design-da-web/#)
> - [CSS Website Layout (W3Schools)](https://www.w3schools.com/css/css_website_layout.asp)
> - [Website Page Layouts](http://www.cellbiol.com/bioinformatics_web_development/chapter-3-your-first-web-page-learning-html-and-css/website-page-layouts/)
> - [Perfect Liquid Layout](https://matthewjamestaylor.com/perfect-liquid-layouts)
> - [How and Why Icons Improve Your Web Design](https://usabilla.com/blog/how-and-why-icons-improve-you-web-design/)
