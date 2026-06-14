# 02 — Tipos de schema (tipos de conteúdo)

Um **item de marcação** sempre tem um **tipo de conteúdo**, que determina qual tipo do
Schema.org será gerado e quais campos o formulário de edição oferece. Abaixo estão os
tipos suportados, mapeados para a documentação de Resultados Avançados do Google.

> Cada tipo tem: (a) um **formulário XML** (`forms/contenttypes/<tipo>.xml`) que define os
> campos exibidos no editor e seus mapeamentos padrão; (b) uma **classe de preparação**
> (`src/Schema/Tipos/<Tipo>.php`) que normaliza os valores; (c) um **construtor no motor**
> (`src/Engine/GeradorJsonLd.php`) que monta o array final.

## Resumo dos tipos

| Tipo (UI) | `@type` Schema.org | Caso de uso | Referência Google |
|-----------|--------------------|-------------|-------------------|
| Artigo | `Article` / `NewsArticle` / `BlogPosting` | Notícias, posts de blog | [Article](https://developers.google.com/search/docs/appearance/structured-data/article) |
| Produto | `Product` (+ `Offer`/`AggregateOffer`) | Lojas, catálogos | [Product](https://developers.google.com/search/docs/appearance/structured-data/product) |
| Evento | `Event` (+ `Place`/`VirtualLocation`/`Offer`) | Shows, cursos, webinars | [Event](https://developers.google.com/search/docs/appearance/structured-data/event) |
| Receita | `Recipe` | Sites de culinária | [Recipe](https://developers.google.com/search/docs/appearance/structured-data/recipe) |
| FAQ | `FAQPage` (+ `Question`/`Answer`) | Páginas de perguntas frequentes | [FAQ](https://developers.google.com/search/docs/appearance/structured-data/faqpage) |
| Como fazer | `HowTo` (+ `HowToStep`/`HowToTool`/`HowToSupply`) | Tutoriais passo a passo | [HowTo](https://developers.google.com/search/docs/appearance/structured-data/how-to) |
| Negócio local | `LocalBusiness` (e subtipos) | Lojas físicas, restaurantes | [Local Business](https://developers.google.com/search/docs/appearance/structured-data/local-business) |
| Organização | `Organization` (e subtipos) | Empresas, instituições | [Organization](https://developers.google.com/search/docs/appearance/structured-data/organization) |
| Pessoa | `Person` | Perfis, equipe, autores | [Schema.org/Person](https://schema.org/Person) |
| Curso | `Course` (+ `CourseInstance`) | Plataformas de ensino | [Course](https://developers.google.com/search/docs/appearance/structured-data/course) |
| Livro | `Book` (+ `workExample`) | Editoras, livrarias | [Book](https://developers.google.com/search/docs/appearance/structured-data/book) |
| Filme | `Movie` | Catálogos de filmes | [Movie](https://developers.google.com/search/docs/appearance/structured-data/movie) |
| Avaliação | `Review` (+ `itemReviewed`) | Resenhas | [Review snippet](https://developers.google.com/search/docs/appearance/structured-data/review-snippet) |
| Vaga de emprego | `JobPosting` | Portais de vagas | [Job Posting](https://developers.google.com/search/docs/appearance/structured-data/job-posting) |
| Serviço | `Service` | Prestadores de serviço | [Schema.org/Service](https://schema.org/Service) |
| Vídeo | `VideoObject` | Páginas com vídeo | [Video](https://developers.google.com/search/docs/appearance/structured-data/video) |
| Checagem de fatos | `ClaimReview` | Agências de checagem | [Fact Check](https://developers.google.com/search/docs/appearance/structured-data/factcheck) |
| Código personalizado | (livre) | JSON-LD escrito à mão | — |

## Detalhamento por tipo

A seguir, as propriedades-chave que cada construtor produz. Campos vazios são removidos
automaticamente antes da serialização (ver [07 — Fluxo](07-fluxo-de-renderizacao.md)).

### Artigo
`@type` (Article/NewsArticle/BlogPosting), `headline`, `description`, `image` (ImageObject),
`mainEntityOfPage` (WebPage `@id`), `author` (Person/Organization), `publisher`
(Organization + logo), `datePublished`, `dateCreated`, `dateModified`.

### Produto
`name`, `image`, `description`, `sku`, `mpn`, `gtin`, `productID`, `brand` (Brand),
`weight` (QuantitativeValue), `offers` — `Offer` (preço único) ou `AggregateOffer`
(`lowPrice`/`highPrice`/`offerCount`) com `priceCurrency`, `availability`,
`itemCondition`, `priceValidUntil`, `url` — `aggregateRating` (AggregateRating) e
`review` (lista de Review).

### Evento
`name`, `description`, `image`, `url`, `startDate`, `endDate`, `eventStatus`,
`eventAttendanceMode`, `location` (Place com PostalAddress e/ou VirtualLocation),
`offers` (Offer com preço, moeda, disponibilidade, `inventoryLevel`), `performer`,
`organizer`.

### Receita
`name`, `image`, `description`, `prepTime`, `cookTime`, `totalTime` (durações ISO 8601),
`keywords`, `recipeCuisine`, `recipeCategory`, `recipeYield`, `recipeIngredient` (lista),
`recipeInstructions`, `nutrition` (NutritionInformation/`calories`), `video`
(VideoObject), `author`, `aggregateRating`, datas.

### FAQ
`FAQPage` com `mainEntity`: lista de `Question` contendo `name` (pergunta) e
`acceptedAnswer` (`Answer`/`text`). Origem dos pares pergunta/resposta: subform repetível
ou campo personalizado.

### Como fazer (HowTo)
`name`, `image`, `totalTime`, `estimatedCost` (MonetaryAmount), `supply` (lista de
HowToSupply), `tool` (lista de HowToTool), `step` (lista de HowToStep).

### Negócio local
`@type` (subtipo, ex.: `Restaurant`, `Store`), `@id`, `name`, `image`, `url`, `telephone`,
`priceRange`, `address` (PostalAddress), `geo` (GeoCoordinates),
`openingHoursSpecification` (lista), `servesCuisine`, `menu`, `review`, `aggregateRating`.

### Organização
`@type` (subtipo), `@id`, `name`, `alternateName`, `legalName`, `description`, `email`,
`url`, `telephone`, `foundingDate`, identificadores fiscais (`taxID`, `vatID`, `duns`,
`leiCode`, `naics`, `iso6523Code`), `numberOfEmployees`, `logo`, `address`, `sameAs`
(perfis), `aggregateRating`.

### Pessoa
`@id`, `url`, `name`, `description`, prefixos/sufixos honoríficos, nomes (given/family/
additional/alternate), `address`, `nationality`, `email`, `telephone`, `gender`,
`birthDate`, `image`, `jobTitle`, `worksFor` (Organization), `affiliation`, `alumniOf`,
`award`, `knowsAbout`, `hasOccupation` (Occupation com salário estimado), `sameAs`.

### Curso
`name`, `description`, `courseCode`, `provider` (Organization), `hasCourseInstance`
(CourseInstance com `courseMode`, datas, `location`, `performer`, `courseWorkload`),
`offers` (Offer com categoria/preço/moeda), `aggregateRating`, datas.

### Livro
`Book` com `name`, `image`, `sameAs`, `inLanguage`, `author`, e `workExample` (edição
específica) com `bookFormat`, `isbn`, `bookEdition`, `datePublished`, `potentialAction`
(ReadAction com EntryPoint) e `identifier` (OCLC/LCCN/JP-E-CODE como PropertyValue).

### Filme
`name`, `url`, `description`, `image`, `dateCreated`, `duration`, `genre` (lista),
`creator`/`director`/`actor` (listas de Person), `trailer` (VideoObject),
`aggregateRating`, `review`.

### Avaliação
`Review` com `itemReviewed` polimórfico (LocalBusiness, Movie, Book, Product…),
`reviewRating` (Rating), `author`, `datePublished`, `publisher`, `inLanguage`. Para
`Product` revisado, inclui `offers`, `brand`, `aggregateRating` e lista de `review`.

### Vaga de emprego
`title`, `description`, `datePosted`, `validThrough`, `employmentType`, `industry`,
`educationRequirements`, `jobLocation` (Place/PostalAddress), `hiringOrganization`
(Organization com logo), `baseSalary` (MonetaryAmount com QuantitativeValue — valor único
ou faixa min/max).

### Serviço
`name`, `serviceType`, `description`, `image`, `url`, `provider` (tipo, nome, imagem,
telefone, endereço), `offers` (Offer com moeda/preço).

### Vídeo
`VideoObject` com `name`, `description`, `thumbnailUrl`, `uploadDate`, `contentUrl`,
`embedUrl`, `transcript`. Requer `contentUrl` **ou** `embedUrl`.

### Checagem de fatos
`ClaimReview` com `url`, `claimReviewed`, `itemReviewed` (CreativeWork + autor da
alegação), `author` (Organization), `reviewRating` (Rating com `alternateName`), datas.

### Código personalizado
Permite ao usuário colar um bloco JSON-LD próprio. O conteúdo é emitido como está (após
substituição de SmartTags), sem nenhuma transformação. Útil para tipos ainda não
cobertos por um construtor dedicado.

## Como adicionar um novo tipo

1. Criar `forms/contenttypes/<tipo>.xml` com os campos e mapeamentos.
2. Criar `src/Schema/Tipos/<Tipo>.php` estendendo `Base` (normalizações específicas).
3. Adicionar um método `contentType<Tipo>()` em `src/Engine/GeradorJsonLd.php`.
4. Registrar o tipo na lista de tipos disponíveis e adicionar as strings de idioma.
