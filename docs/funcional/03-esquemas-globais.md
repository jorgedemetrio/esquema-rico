# 03 — Esquemas globais

Esquemas globais são marcações que **não dependem de um conteúdo específico**. São
configurados uma única vez na tela de Configurações do componente e renderizados
automaticamente pelo plugin de sistema, sem necessidade de criar um item de marcação.

Cada esquema global tem uma regra de **onde aparece**:

| Esquema | Onde aparece | `@type` Schema.org |
|---------|--------------|--------------------|
| Nome do site (WebSite) | Apenas na página inicial | `WebSite` |
| Caixa de pesquisa de sitelinks | Apenas na página inicial | `WebSite` + `SearchAction` |
| Logo | Apenas na página inicial | `Organization` (`url` + `logo`) |
| Perfis sociais | Apenas na página inicial | `Organization`/`Person` + `sameAs` |
| Negócio local | Apenas na página inicial | `LocalBusiness` (e subtipos) |
| Breadcrumbs | Todas as páginas (exceto a home) | `BreadcrumbList` |
| Código personalizado global | Todas as páginas | (livre) |

## Nome do site (WebSite)

Informa à Pesquisa o nome preferido do site, exibido nos resultados. Campos:

- **Nome do site**: nome preferido (se vazio, usa o nome configurado no Joomla).
- **Nome alternativo**: o Google pode escolher entre o preferido e o alternativo.
- **URL do site**: URL base canônica.

Gera `{"@type":"WebSite","url":…,"name":…,"alternateName":…}`.

## Caixa de pesquisa de sitelinks

Habilita a caixa de busca dentro do resultado do site. O usuário escolhe o método:

1. **Componente de busca** (`com_search`) — usa a rota nativa.
2. **Busca inteligente** (`com_finder`) — usa a rota do Finder.
3. **URL personalizada** — o usuário informa um padrão contendo `{search_term}`
   (validado para conter esse marcador).

Acrescenta ao WebSite um `potentialAction` do tipo `SearchAction` com `target` e
`query-input` (`required name=search_term`).

## Logo

Define a imagem do logo da organização para o Painel de Conhecimento. Campos: arquivo de
imagem (campo de mídia). Gera `{"@type":"Organization","url":…,"logo":…}`.

> A imagem é normalizada para URL absoluta e tem eventuais metadados do campo de mídia do
> Joomla removidos do caminho.

## Perfis sociais

Lista os perfis oficiais (Facebook, X/Twitter, Instagram, YouTube, LinkedIn, Pinterest,
SoundCloud, Tumblr e outros, um por linha). O usuário escolhe se a entidade é uma
**Organização** ou **Pessoa**. Gera um objeto com `name`, `url` e a lista `sameAs`. URLs
vazias e espaços são removidos.

## Negócio local

Marca o site como um estabelecimento. Campos: subtipo de negócio (ex.: `Restaurant`,
`Store`, `LocalBusiness`), endereço completo (rua, cidade, região, CEP, país), telefone,
faixa de preço, coordenadas (lat/lng), tipo de cozinha (`servesCuisine`) e o **horário de
funcionamento**.

O horário de funcionamento tem três modos:
- **0 — Não especificado**: nenhum horário é emitido.
- **1 — Sempre aberto**: um único `OpeningHoursSpecification` 00:00–23:59 para todos os
  dias.
- **2 — Horários específicos**: por dia da semana, com suporte a dois intervalos
  (ex.: manhã e tarde). Dias habilitados sem horário assumem 24 h.

Inclui também `geo` (GeoCoordinates), `review` e `aggregateRating` quando informados.

## Breadcrumbs (BreadcrumbList)

Gera a trilha de navegação a partir do *pathway* do Joomla. Campos:

- **Habilitar**: liga/desliga.
- **Incluir página inicial**: adiciona a home como primeiro item.
- **Texto da página inicial**: rótulo do item inicial (padrão configurável por idioma).

Regras de montagem (ver Helper de breadcrumbs):
- Cada item vira um `ListItem` com `position`, `name` e `item` (URL absoluta).
- Nomes têm tags HTML e *shortcodes* `[icon]…[/icon]` removidos.
- O último item (página atual), que o Joomla às vezes devolve sem link, recebe a URL
  corrente para satisfazer os validadores do Google.
- URLs relativas são convertidas em absolutas, respeitando a configuração de SSL forçado.

> Como muitos templates já emitem breadcrumbs em microdados, recomenda-se ativar a
> **remoção de duplicados** para `BreadcrumbList` (ligada por padrão).

## Código personalizado global

Um campo de texto livre onde o usuário cola qualquer bloco `<script type="application/
ld+json">`. É emitido em todas as páginas, após a substituição de SmartTags. Há um aviso
de que o conteúdo é de responsabilidade do usuário.

## Ordem de emissão

O plugin de sistema monta os esquemas globais nesta ordem e os concatena ao resultado das
integrações: WebSite → Logo → Perfis Sociais → Negócio Local → Código Personalizado →
Breadcrumbs → (itens de marcação via integrações). Blocos vazios são descartados.
