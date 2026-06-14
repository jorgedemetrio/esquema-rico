# Guia do usuário — Esquema Rico

Guia prático para quem vai **usar** o Esquema Rico no dia a dia. Para detalhes
conceituais, veja a [documentação funcional](funcional/README.md).

## O que o Esquema Rico faz por você

Adiciona aos seus artigos, produtos, eventos e páginas a **marcação de dados estruturados**
que o Google entende, tornando-os elegíveis a **Resultados Avançados** (estrelas de
avaliação, preço, FAQ, datas de eventos, trilha de navegação, etc.). A aparência do seu
site não muda — a marcação é invisível ao visitante.

## 1. Instalação

1. Baixe/gere o pacote `pkg_esquemarico.zip`.
2. Painel do Joomla → **Sistema → Instalar → Enviar Pacote** → selecione o ZIP.
3. Pronto: o instalador já habilita os plugins necessários.

> Requisitos: Joomla 6, PHP 8.3, MySQL 5.7+ (ou MariaDB 10.4+).

## 2. Configurações globais (faça uma vez)

Vá em **Componentes → Esquema Rico → Configurações** e preencha o que se aplica ao seu site:

- **Nome do site** — como o Google deve exibir o nome nos resultados.
- **Logo** — imagem do logo da organização (aparece no Painel de Conhecimento).
- **Perfis sociais** — links do Facebook, Instagram, etc. (um por linha em "Outros").
- **Breadcrumbs** — deixe ligado para a trilha de navegação.
- **Negócio local** — se você tem um estabelecimento físico, preencha endereço, telefone e
  horário de funcionamento.
- **Avançado → Remover schemas duplicados** — deixe `BreadcrumbList` ligado se o seu
  template já gera breadcrumbs (evita duplicidade).

Clique em **Salvar**.

## 3. Criar uma marcação para o seu conteúdo

Exemplo: marcar os **artigos** como `Article`.

1. **Esquema Rico → Itens → Novo**.
2. **Integração**: "Conteúdo do Joomla".
3. **Tipo de conteúdo**: "Artigo".
4. **Título**: um nome para você identificar (ex.: "Artigos do blog").
5. **Salvar**. (Ao salvar, as abas de Mapeamento e Condições aparecem.)
6. Aba **Mapeamento**: cada propriedade já vem ligada a uma origem sensata
   (título → título do artigo, imagem → imagem do artigo, autor → autor). Ajuste se quiser:
   - **Opção de origem**: puxa o valor do conteúdo automaticamente.
   - **Valor fixo**: você digita um valor igual para todos.
   - **Personalizado**: combine textos e variáveis, ex.: `{gsd.item.headline} — Meu Site`.
7. Aba **Condições**: defina onde a marcação aparece. Para todos os artigos, deixe sem
   condição. Para restringir, ative por exemplo **Itens de menu** e selecione os menus.
8. **Salvar**. Verifique que o item está **Publicado**.

> Repita para outros tipos: Produto (loja), Evento (agenda), FAQ (página de perguntas),
> Receita, etc. Para uma página específica (home, landing page), use a integração
> **Itens de menu**.

## 4. Conferir o resultado

1. Abra uma página do seu site (ex.: um artigo).
2. Veja o código-fonte (Ctrl+U) e procure por `application/ld+json` — a marcação está lá.
3. Valide no **Teste de Resultados Avançados** do Google:
   https://search.google.com/test/rich-results (cole a URL ou o código).

Se algo não aparecer, ative **Modo de depuração** no plugin "Sistema - Esquema Rico"
(Sistema → Plugins). Ao visitar a página como administrador, um painel ao final mostra
quais itens foram avaliados e por quê.

## 5. Dicas

- **Avaliações com estrelas**: só aparecem se o conteúdo tiver nota e número de avaliações
  reais — não invente.
- **Imagens**: use URLs acessíveis e imagens de boa qualidade.
- **Datas**: o Esquema Rico já converte para o formato exigido (ISO 8601) com o seu fuso.
- **Não duplique**: se o template já emite um schema, use "Remover schemas duplicados" para
  o tipo correspondente, deixando apenas o do Esquema Rico.

## 6. Integrações disponíveis

Conteúdo nativo do Joomla, Itens de menu, K2, VirtueMart, HikaShop, JEvents e DPCalendar.
Cada integração só aparece na lista se a extensão de origem estiver instalada.

## 7. Problemas comuns

| Sintoma | Causa provável |
|---------|----------------|
| Nada aparece no frontend | Plugin "Sistema - Esquema Rico" desabilitado, ou item despublicado |
| Schema só na home | É um esquema global (WebSite/Logo) — correto |
| Marcação não bate com a página | Condições não casaram — confira a aba Condições e o Modo de depuração |
| Estrelas não aparecem | Falta nota/contagem de avaliações no conteúdo |
| Schema duplicado no teste do Google | Template/extensão também emite — ative a remoção de duplicados |
