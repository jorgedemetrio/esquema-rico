<?php

/**
 * @package     Esquema Rico
 * @subpackage  Testes
 *
 * Testes unitários do motor de geração de JSON-LD.
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

declare(strict_types=1);

use Joomla\Component\Esquemarico\Administrator\Engine\GeradorJsonLd;
use PHPUnit\Framework\TestCase;

final class GeradorJsonLdTest extends TestCase
{
    /**
     * Gera e decodifica o JSON-LD de um conjunto de dados.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function gerar(array $data): array
    {
        $html = (new GeradorJsonLd($data))->generate();

        $this->assertIsString($html, 'O gerador deveria retornar uma string.');
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('data-type="esr"', $html);

        $inner = preg_replace('#</?script[^>]*>#', '', $html);
        $json  = json_decode(trim((string) $inner), true);

        $this->assertIsArray($json, 'O conteúdo do <script> deveria ser um JSON válido.');
        $this->assertSame('https://schema.org', $json['@context'] ?? null);

        return $json;
    }

    public function testArtigo(): void
    {
        $json = $this->gerar([
            'contentType'   => 'article',
            'type'          => 'Article',
            'title'         => 'Meu Título',
            'description'   => 'Minha descrição.',
            'image'         => 'https://site/imagem.jpg',
            'authorName'    => 'Maria Silva',
            'datePublished' => '2026-01-01T10:00:00-03:00',
        ]);

        $this->assertSame('Article', $json['@type']);
        $this->assertSame('Meu Título', $json['headline']);
        $this->assertSame('Minha descrição.', $json['description']);
        $this->assertSame('ImageObject', $json['image']['@type']);
        $this->assertSame('https://site/imagem.jpg', $json['image']['url']);
        $this->assertSame('Maria Silva', $json['author']['name']);
        $this->assertSame('2026-01-01T10:00:00-03:00', $json['datePublished']);
    }

    public function testProdutoOfertaSimples(): void
    {
        $json = $this->gerar([
            'contentType'       => 'product',
            'title'             => 'Tênis de Corrida',
            'image'             => 'https://site/tenis.jpg',
            'description'       => 'Leve e respirável.',
            'sku'               => 'TN-42',
            'offerPrice'        => '299.90',
            'currency'          => 'BRL',
            'offerAvailability' => 'https://schema.org/InStock',
            'url'               => 'https://site/produto',
        ]);

        $this->assertSame('Product', $json['@type']);
        $this->assertSame('Tênis de Corrida', $json['name']);
        $this->assertSame('TN-42', $json['sku']);
        $this->assertSame('Offer', $json['offers']['@type']);
        $this->assertSame('299.90', $json['offers']['price']);
        $this->assertSame('BRL', $json['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $json['offers']['availability']);
    }

    public function testProdutoOfertaAgregada(): void
    {
        $json = $this->gerar([
            'contentType' => 'product',
            'title'       => 'Camiseta',
            'offerPrice'  => ['39.90', '59.90'],
            'offerCount'  => 5,
            'currency'    => 'BRL',
        ]);

        $this->assertSame('AggregateOffer', $json['offers']['@type']);
        $this->assertSame('39.90', $json['offers']['lowPrice']);
        $this->assertSame('59.90', $json['offers']['highPrice']);
        $this->assertSame(5, $json['offers']['offerCount']);
    }

    public function testProdutoComAvaliacao(): void
    {
        $json = $this->gerar([
            'contentType' => 'product',
            'title'       => 'Livro',
            'ratingValue' => '4.5',
            'reviewCount' => '120',
        ]);

        $this->assertSame('AggregateRating', $json['aggregateRating']['@type']);
        $this->assertSame('4.5', $json['aggregateRating']['ratingValue']);
        $this->assertSame('120', $json['aggregateRating']['reviewCount']);
    }

    public function testAvaliacaoOmitidaSemContagem(): void
    {
        $json = $this->gerar([
            'contentType' => 'product',
            'title'       => 'Livro',
            'ratingValue' => '4.5',
            // sem reviewCount
        ]);

        $this->assertArrayNotHasKey('aggregateRating', $json);
    }

    public function testEvento(): void
    {
        $json = $this->gerar([
            'contentType'  => 'event',
            'title'        => 'Show de Rock',
            'startDate'    => '2026-12-01T20:00:00-03:00',
            'endDate'      => '2026-12-01T23:00:00-03:00',
            'locationName' => 'Arena',
            'price'        => '150',
            'offerCurrency' => 'BRL',
        ]);

        $this->assertSame('Event', $json['@type']);
        $this->assertSame('Show de Rock', $json['name']);
        $this->assertSame('2026-12-01T20:00:00-03:00', $json['startDate']);
        $this->assertSame('Place', $json['location']['@type']);
        $this->assertSame('Arena', $json['location']['name']);
        $this->assertSame('150', $json['offers']['price']);
    }

    public function testFaq(): void
    {
        $json = $this->gerar([
            'contentType' => 'faq',
            'faqs'        => [
                ['question' => 'Como instalar?', 'answer' => 'Pelo gerenciador.'],
                ['question' => 'É grátis?',      'answer' => 'Sim.'],
            ],
        ]);

        $this->assertSame('FAQPage', $json['@type']);
        $this->assertCount(2, $json['mainEntity']);
        $this->assertSame('Question', $json['mainEntity'][0]['@type']);
        $this->assertSame('Como instalar?', $json['mainEntity'][0]['name']);
        $this->assertSame('Answer', $json['mainEntity'][0]['acceptedAnswer']['@type']);
        $this->assertSame('Pelo gerenciador.', $json['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function testBreadcrumbs(): void
    {
        $json = $this->gerar([
            'contentType' => 'breadcrumbs',
            'crumbs'      => [
                ['name' => 'Início', 'link' => 'https://site/'],
                ['name' => 'Categoria', 'link' => 'https://site/categoria'],
            ],
        ]);

        $this->assertSame('BreadcrumbList', $json['@type']);
        $this->assertCount(2, $json['itemListElement']);
        $this->assertSame(1, $json['itemListElement'][0]['position']);
        $this->assertSame('Início', $json['itemListElement'][0]['name']);
        $this->assertSame('https://site/', $json['itemListElement'][0]['item']);
        $this->assertSame(2, $json['itemListElement'][1]['position']);
    }

    public function testLimpezaRemovePropriedadesVazias(): void
    {
        $json = $this->gerar([
            'contentType' => 'product',
            'title'       => 'Produto',
            'description' => '',   // vazio: deve ser removido
            'sku'         => '0',  // zero: deve ser preservado
        ]);

        $this->assertArrayNotHasKey('description', $json);
        $this->assertSame('0', $json['sku']);
    }

    public function testCodigoPersonalizadoPassaDireto(): void
    {
        $codigo = '<script type="application/ld+json">{"@context":"https://schema.org","@type":"Thing","name":"X"}</script>';

        $html = (new GeradorJsonLd([
            'contentType' => 'custom_code',
            'custom_code' => $codigo,
        ]))->generate();

        // Código personalizado é devolvido como está, sem reembrulhar.
        $this->assertSame($codigo, $html);
    }

    public function testNegocioLocalHorariosEspecificos(): void
    {
        $json = $this->gerar([
            'contentType'  => 'localbusiness',
            'type'         => 'Restaurant',
            'name'         => 'Cantina',
            'openinghours' => [
                'mode'   => '2',
                'monday' => ['enabled' => '1', 'start' => '09:00', 'end' => '18:00'],
                'sunday' => ['enabled' => '0'],
            ],
        ]);

        $this->assertSame('Restaurant', $json['@type']);
        $this->assertSame('Cantina', $json['name']);
        $this->assertArrayHasKey('openingHoursSpecification', $json);
        $spec = $json['openingHoursSpecification'];
        $this->assertSame('OpeningHoursSpecification', $spec[0]['@type']);
        $this->assertSame('Monday', $spec[0]['dayOfWeek']);
        $this->assertSame('09:00', $spec[0]['opens']);
        $this->assertSame('18:00', $spec[0]['closes']);
    }

    public function testNegocioLocalSempreAberto(): void
    {
        $json = $this->gerar([
            'contentType'  => 'localbusiness',
            'type'         => 'Store',
            'name'         => 'Loja 24h',
            'openinghours' => ['mode' => '1', 'monday' => ['enabled' => '1']],
        ]);

        $this->assertSame('00:00', $json['openingHoursSpecification']['opens']);
        $this->assertSame('23:59', $json['openingHoursSpecification']['closes']);
    }

    public function testNegocioLocalSemHorario(): void
    {
        $json = $this->gerar([
            'contentType'  => 'localbusiness',
            'type'         => 'Store',
            'name'         => 'Loja',
            'openinghours' => ['mode' => '0'],
        ]);

        $this->assertArrayNotHasKey('openingHoursSpecification', $json);
    }

    public function testTipoInvalidoRetornaNulo(): void
    {
        $html = (new GeradorJsonLd(['contentType' => 'inexistente']))->generate();

        $this->assertNull($html);
    }

    public function testListaDeTiposDisponiveis(): void
    {
        $tipos = (new GeradorJsonLd())->getContentTypes();

        $this->assertContains('article', $tipos);
        $this->assertContains('product', $tipos);
        $this->assertContains('event', $tipos);
        $this->assertContains('qapage', $tipos);
        $this->assertContains('softwareapplication', $tipos);
        // Código personalizado deve ser o último da lista.
        $this->assertSame('custom_code', end($tipos));
    }

    public function testQAPage(): void
    {
        $json = $this->gerar([
            'contentType'             => 'qapage',
            'question'                => 'Como instalar o componente?',
            'question_detail'         => 'Passo a passo da instalação.',
            'accepted_answer'         => 'Baixe o pacote e instale pelo gestor de extensões.',
            'accepted_answer_upvotes' => '10',
            'suggested_answers'       => [
                ['text' => 'Use o instalador via URL.', 'upvotes' => '3'],
            ],
        ]);

        $this->assertSame('QAPage', $json['@type']);
        $this->assertSame('Question', $json['mainEntity']['@type']);
        $this->assertSame('Como instalar o componente?', $json['mainEntity']['name']);
        $this->assertSame('Answer', $json['mainEntity']['acceptedAnswer']['@type']);
        $this->assertSame('Baixe o pacote e instale pelo gestor de extensões.', $json['mainEntity']['acceptedAnswer']['text']);
        $this->assertSame(2, $json['mainEntity']['answerCount']);
        $this->assertSame('Use o instalador via URL.', $json['mainEntity']['suggestedAnswer'][0]['text']);
    }

    public function testSoftwareApplication(): void
    {
        $json = $this->gerar([
            'contentType'         => 'softwareapplication',
            'type'                => 'WebApplication',
            'name'                => 'Meu App',
            'operatingSystem'     => 'Android, iOS',
            'applicationCategory' => 'BusinessApplication',
            'price'               => '0',
            'priceCurrency'       => 'BRL',
            'ratingValue'         => '4.5',
            'reviewCount'         => '120',
        ]);

        $this->assertSame('WebApplication', $json['@type']);
        $this->assertSame('Meu App', $json['name']);
        $this->assertSame('Offer', $json['offers']['@type']);
        $this->assertSame('0', $json['offers']['price']);
        $this->assertSame('AggregateRating', $json['aggregateRating']['@type']);
        $this->assertSame('4.5', $json['aggregateRating']['ratingValue']);
    }
}
