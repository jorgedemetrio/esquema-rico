# 06 — Condições de publicação

As **condições de publicação** (também chamadas de *atribuições*) determinam **em quais
páginas/contextos** um item de marcação é renderizado. Elas evitam que um schema apareça
onde não deveria e permitem segmentar a marcação.

## Onde se aplicam

Cada item de marcação carrega, em seus parâmetros, um conjunto de condições. No fluxo de
renderização (ver [07 — Fluxo](07-fluxo-de-renderizacao.md)), depois de selecionar os
itens candidatos (por integração, *view*, idioma e estado publicado), o motor avalia as
condições de cada item; só os que **passam** são emitidos.

Além das condições por item, há filtros mais baratos resolvidos antes:

- **Estado**: o item precisa estar publicado (`state = 1`).
- **Idioma**: o idioma do item precisa casar com o idioma ativo, ou ser `*` (todos).
- **View / Integração**: o item precisa pertencer à integração e à *view* atuais.

## Modelo conceitual

Uma condição é descrita por:

```
{
  "alias":     "menu",          // qual tipo de condição
  "operator":  "includes",      // includes | not_includes
  "value":     [12, 15, 20],    // a seleção do usuário
  "params":    { ... }          // opções específicas da condição
}
```

As condições são organizadas em **grupos**. Dentro de um grupo, a combinação pode ser
**E (all)** ou **OU (any)**; entre grupos, a combinação é **OU**. Na prática, o produto
usa por padrão o modo "todas as condições do grupo precisam passar" (E lógico).

A avaliação é feita pelo motor de condições da biblioteca `Esquemarico\Core`
(`ConditionsHelper` → `Condition`): cada condição tem uma classe que implementa o método
`pass()`, comparando a **seleção do usuário** com o **contexto atual** segundo o operador.

## Tipos de condição

### Essenciais (incluídos no núcleo)

| Condição | Pergunta que responde |
|----------|------------------------|
| **Menu** | A página atual corresponde a um destes itens de menu (com opção de incluir filhos)? |
| **Idioma** | O idioma ativo está entre os selecionados? |
| **Grupo de usuário** | O usuário pertence a um destes grupos? |
| **ID de usuário** | O usuário é um destes? |
| **Nível de acesso** | O conteúdo está em um destes níveis de acesso? |
| **Data** | A data atual está dentro do intervalo? |
| **Dia da semana / Mês / Hora** | Restrições temporais recorrentes |
| **Dispositivo** | Desktop, tablet ou celular? |
| **Componente** | O componente ativo é um destes? |

### Avançadas (acessórias / planejadas)

Navegador, sistema operacional, faixa de IP, geolocalização (país/região/cidade/
continente), visitante novo/recorrente, número de visualizações, tempo no site, cookie,
referenciador, *query string*. Essas exigem rastreamento do visitante e são opcionais.

## Operadores

- **Inclui** (`includes`): passa se o contexto atual estiver entre os valores
  selecionados.
- **Não inclui** (`not_includes`): passa se o contexto atual **não** estiver entre os
  valores selecionados.

## Definição via XML

Cada condição tem um XML que descreve sua interface (o campo de seleção, o operador e os
parâmetros extras). Exemplo conceitual (`menu`):

```xml
<form>
  <fieldset name="general">
    <field name="operator" type="comparator" />
    <field name="value" type="menuitem" multiple="true" />
    <fields name="params">
      <field name="include_children" type="radio" default="0">
        <option value="0">Não</option>
        <option value="1">Sim</option>
      </field>
    </fields>
  </fieldset>
</form>
```

As integrações também fornecem condições específicas via seus `form/assignments.xml`
(ex.: "produtos desta categoria", "incluir subcategorias").

## Comportamento padrão

- Um item **sem condições** é renderizado sempre que os filtros básicos (estado, idioma,
  view, integração) passarem.
- Condições com seleção vazia são ignoradas (não bloqueiam).
- A avaliação é registrada no log de depuração quando o modo *debug* está ligado, para
  facilitar o diagnóstico de "por que meu schema não aparece".
