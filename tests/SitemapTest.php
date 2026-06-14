<?php

/**
 * @package     Esquema Rico
 * @subpackage  Testes
 *
 * Testes do cálculo de prioridade por recência e da montagem do XML do sitemap.
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

declare(strict_types=1);

use Joomla\Component\Esquemarico\Site\Helper\SitemapBuilder;
use Joomla\Component\Esquemarico\Site\Helper\SitemapPriority;
use PHPUnit\Framework\TestCase;

final class SitemapTest extends TestCase
{
    private int $now;

    protected function setUp(): void
    {
        // "Agora" fixo para determinismo: 2026-06-14 12:00:00 UTC.
        $this->now = mktime(12, 0, 0, 6, 14, 2026);
    }

    public function testPrioridadeMaximaParaItemDeHoje(): void
    {
        $this->assertSame(1.0, SitemapPriority::calculate(0));
    }

    public function testPrioridadeMinimaForaDaJanela(): void
    {
        $this->assertSame(0.1, SitemapPriority::calculate(365));
        $this->assertSame(0.1, SitemapPriority::calculate(1000)); // clamp no mínimo
    }

    public function testDecaimentoLinearNoMeioDaJanela(): void
    {
        // metade da janela (182,5d) -> ~0.55 -> arredonda para 0.6 ou 0.5.
        $p = SitemapPriority::calculate(183);
        $this->assertGreaterThan(0.1, $p);
        $this->assertLessThan(1.0, $p);
    }

    public function testRecenteMaisImportanteQueAntigo(): void
    {
        $recente = SitemapPriority::calculate(10);
        $antigo  = SitemapPriority::calculate(300);
        $this->assertGreaterThan($antigo, $recente);
    }

    public function testJanelaCustomizada(): void
    {
        // Janela de 30 dias: 30 dias -> mínimo; 0 -> máximo.
        $this->assertSame(0.1, SitemapPriority::calculate(30, ['window' => 30]));
        $this->assertSame(1.0, SitemapPriority::calculate(0, ['window' => 30]));
    }

    public function testFromDatesUsaDataMaisRecente(): void
    {
        // modificado hoje, criado há muito tempo -> deve usar o mais recente (hoje).
        $hoje = date('Y-m-d H:i:s', $this->now);
        $p = SitemapPriority::fromDates($hoje, '2010-01-01 00:00:00', $this->now);
        $this->assertSame(1.0, $p);
    }

    public function testFromDatesSemDataUsaPadrao(): void
    {
        $this->assertSame(0.5, SitemapPriority::fromDates(null, null, $this->now));
        $this->assertSame(0.7, SitemapPriority::fromDates(null, null, $this->now, ['default' => 0.7]));
        $this->assertSame(0.5, SitemapPriority::fromDates('0000-00-00 00:00:00', null, $this->now));
    }

    public function testChangefreqPorIdade(): void
    {
        $this->assertSame('daily', SitemapPriority::changefreq(0));
        $this->assertSame('weekly', SitemapPriority::changefreq(20));
        $this->assertSame('monthly', SitemapPriority::changefreq(100));
        $this->assertSame('yearly', SitemapPriority::changefreq(400));
        $this->assertSame('monthly', SitemapPriority::changefreq(null));
    }

    public function testAgeDays(): void
    {
        $tresDias = date('Y-m-d H:i:s', $this->now - 3 * 86400);
        $this->assertSame(3, SitemapPriority::ageDays($tresDias, null, $this->now));
        $this->assertNull(SitemapPriority::ageDays(null, null, $this->now));
    }

    public function testUrlsetGeraXmlValido(): void
    {
        $xml = SitemapBuilder::urlset([
            ['loc' => 'https://site/artigo-1', 'lastmod' => '2026-06-14T12:00:00+00:00', 'changefreq' => 'daily', 'priority' => 1.0],
            ['loc' => 'https://site/artigo-2', 'priority' => 0.3],
        ]);

        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        $this->assertStringContainsString('<loc>https://site/artigo-1</loc>', $xml);
        $this->assertStringContainsString('<priority>1.0</priority>', $xml);
        $this->assertStringContainsString('<priority>0.3</priority>', $xml);
        $this->assertStringContainsString('<changefreq>daily</changefreq>', $xml);

        // XML bem-formado.
        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($xml));
    }

    public function testUrlsetEscapaAmpersand(): void
    {
        $xml = SitemapBuilder::urlset([
            ['loc' => 'https://site/index.php?a=1&b=2', 'priority' => 0.5],
        ]);

        $this->assertStringContainsString('a=1&amp;b=2', $xml);
        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($xml));
    }

    public function testIndexGeraSitemapindex(): void
    {
        $xml = SitemapBuilder::index([
            ['loc' => 'https://site/sitemap-content.xml', 'lastmod' => '2026-06-14T12:00:00+00:00'],
            ['loc' => 'https://site/sitemap-tags.xml'],
        ]);

        $this->assertStringContainsString('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        $this->assertStringContainsString('<loc>https://site/sitemap-content.xml</loc>', $xml);
        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($xml));
    }
}
