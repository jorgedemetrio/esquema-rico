# 10 — Glossário

| Termo | Definição |
|-------|-----------|
| **Dados estruturados** | Marcação que descreve o significado do conteúdo de uma página para mecanismos de busca. |
| **JSON-LD** | *JavaScript Object Notation for Linked Data*. Formato recomendado pelo Google para dados estruturados, embutido em `<script type="application/ld+json">`. |
| **Schema.org** | Vocabulário público e colaborativo de tipos e propriedades (Article, Product, Event…) usado pelos principais buscadores. |
| **Resultados Avançados** (*Rich Results*) | Apresentações enriquecidas na Pesquisa Google (estrelas, preço, FAQ, etc.) habilitadas por dados estruturados. |
| **Microdados** | Forma alternativa de marcação embutida nos atributos HTML (`itemscope`, `itemprop`). A extensão a remove quando duplica o JSON-LD. |
| **Item de marcação** | Configuração salva de um schema (tipo + mapeamentos + condições). Unidade gerenciável no painel. |
| **Tipo de conteúdo** (*content type*) | O tipo de schema gerado por um item (Artigo, Produto…). |
| **Integração** (*app*) | Plugin que lê dados de uma fonte (conteúdo nativo ou extensão de terceiros). |
| **Payload** | Dados brutos extraídos do item da página atual pela integração. |
| **View** | A "tela" ativa da fonte (ex.: `article`, `product`). Determina qual `view<Nome>()` é chamado. |
| **Mapeamento** (*mapping*) | Ligação entre uma propriedade do schema e a origem do seu valor. |
| **SmartTag** | Variável dinâmica `{namespace.chave}` substituída em tempo de renderização. |
| **Condição de publicação** (*assignment*) | Regra que decide em quais contextos um item é renderizado. |
| **Esquema global** | Marcação não vinculada a conteúdo (WebSite, Logo, Perfis Sociais, Negócio Local, Breadcrumbs). |
| **Breadcrumbs** | Trilha de navegação; gera `BreadcrumbList`. |
| **Negócio local** | `LocalBusiness` e subtipos (Restaurant, Store…). |
| **Remoção de duplicados** | Eliminação de schemas concorrentes gerados por templates/extensões. |
| **Painel** | Tela inicial do componente no backend (dashboard). |
| **Edição rápida** (*fast edit*) | Aba embutida no editor da fonte (ex.: artigo) para criar/editar o item de marcação sem sair da tela. |
| **Biblioteca compartilhada** | O plugin `plg_system_esquemaricocore`, namespace `Esquemarico\Core`, com utilitários comuns. |
| **ESR** | Prefixo de código da extensão (constantes `ESR_*`). |
| **`@type` / `@context` / `@id`** | Palavras-chave do JSON-LD: tipo do nó, vocabulário (`https://schema.org`) e identificador único do nó. |
| **ISO 8601** | Formato de data/hora (`2026-06-13T10:00:00-03:00`) exigido pelos schemas de data. |
| **`data-type="esr"`** | Atributo que marca os `<script>` gerados pela extensão, protegendo-os da remoção de duplicados. |
