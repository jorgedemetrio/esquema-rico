<?php

/**
 * @package     Esquema Rico
 * @subpackage  Testes
 *
 * Testes do analisador de SEO (estilo Yoast).
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

declare(strict_types=1);

use Joomla\Component\Esquemarico\Administrator\Seo\SeoAnalyzer;
use PHPUnit\Framework\TestCase;

final class SeoAnalyzerTest extends TestCase
{
    private SeoAnalyzer $a;

    protected function setUp(): void
    {
        $this->a = new SeoAnalyzer();
    }

    /**
     * Status de uma verificação pelo id, ou null se ausente.
     */
    private function status(array $result, string $id): ?string
    {
        foreach ($result['checks'] as $c) {
            if ($c['id'] === $id) {
                return $c['status'];
            }
        }

        return null;
    }

    public function testArtigoVazioPontuaBaixoERuim(): void
    {
        $r = $this->a->analyze([]);

        $this->assertLessThan(30, $r['score']);
        $this->assertSame('bad', $r['rating']);
        $this->assertSame('bad', $this->status($r, 'fk_set'));   // sem palavra-chave de foco
        $this->assertNull($this->status($r, 'fk_in_title'));     // checks de FK não rodam
    }

    public function testTituloTamanhoIdeal(): void
    {
        $r = $this->a->analyze(['title' => str_repeat('a', 50)]);
        $this->assertSame('good', $this->status($r, 'title_length'));
    }

    public function testTituloCurtoOuLongo(): void
    {
        $this->assertSame('bad', $this->status($this->a->analyze(['title' => 'Curto']), 'title_length'));
        $this->assertSame('bad', $this->status($this->a->analyze(['title' => str_repeat('a', 90)]), 'title_length'));
        $this->assertSame('ok', $this->status($this->a->analyze(['title' => str_repeat('a', 35)]), 'title_length'));
    }

    public function testMetadescricao(): void
    {
        $this->assertSame('good', $this->status($this->a->analyze(['metadesc' => str_repeat('a', 140)]), 'metadesc'));
        $this->assertSame('ok', $this->status($this->a->analyze(['metadesc' => str_repeat('a', 80)]), 'metadesc'));
        $this->assertSame('bad', $this->status($this->a->analyze(['metadesc' => '']), 'metadesc'));
    }

    public function testMetakeywords(): void
    {
        $this->assertSame('good', $this->status($this->a->analyze(['metakey' => 'cafe, graos']), 'metakeywords'));
        $this->assertSame('bad', $this->status($this->a->analyze(['metakey' => '']), 'metakeywords'));
    }

    public function testVolumeDeConteudo(): void
    {
        $this->assertSame('good', $this->status($this->a->analyze(['text' => str_repeat('palavra ', 700)]), 'content_length'));
        $this->assertSame('ok', $this->status($this->a->analyze(['text' => str_repeat('palavra ', 350)]), 'content_length'));
        $this->assertSame('bad', $this->status($this->a->analyze(['text' => str_repeat('palavra ', 100)]), 'content_length'));
    }

    public function testImagensSemAlt(): void
    {
        $semAlt = $this->a->analyze(['text' => '<img src="a.jpg"><img src="b.jpg" alt="ok">']);
        $this->assertSame('bad', $this->status($semAlt, 'images_alt'));

        $comAlt = $this->a->analyze(['text' => '<img src="a.jpg" alt="x"><img src="b.jpg" alt="y">']);
        $this->assertSame('good', $this->status($comAlt, 'images_alt'));

        $sem = $this->a->analyze(['text' => 'sem imagens aqui']);
        $this->assertSame('ok', $this->status($sem, 'images_alt'));
    }

    public function testLinksESubtitulos(): void
    {
        $r = $this->a->analyze(['text' => '<h2>Seção</h2> texto <a href="/loja">loja</a>']);
        $this->assertSame('good', $this->status($r, 'links'));
        $this->assertSame('good', $this->status($r, 'subheadings'));

        $r2 = $this->a->analyze(['text' => 'texto simples sem nada']);
        $this->assertSame('bad', $this->status($r2, 'links'));
        $this->assertSame('bad', $this->status($r2, 'subheadings'));
    }

    public function testPalavraChaveNoTituloEDescricao(): void
    {
        $r = $this->a->analyze([
            'title'         => 'Tudo sobre cafe especial',
            'metadesc'      => 'Aprenda sobre cafe especial aqui.',
            'focus_keyword' => 'cafe especial',
        ]);

        $this->assertSame('good', $this->status($r, 'fk_in_title'));
        $this->assertSame('good', $this->status($r, 'fk_in_metadesc'));
    }

    public function testPalavraChaveNaUrl(): void
    {
        $bom = $this->a->analyze(['alias' => 'guia-cafe-especial', 'focus_keyword' => 'cafe especial']);
        $this->assertSame('good', $this->status($bom, 'fk_in_url'));

        $ruim = $this->a->analyze(['alias' => 'outro-assunto', 'focus_keyword' => 'cafe especial']);
        $this->assertSame('bad', $this->status($ruim, 'fk_in_url'));
    }

    public function testDensidadeDaPalavraChave(): void
    {
        // 198 "palavra" + "cafe cafe" => 200 palavras, 2 ocorrências => 1.0% (ideal).
        $r = $this->a->analyze([
            'text'          => str_repeat('palavra ', 198) . 'cafe cafe',
            'focus_keyword' => 'cafe',
        ]);
        $this->assertSame('good', $this->status($r, 'fk_density'));

        // Sem ocorrências => densidade 0 => ruim.
        $r0 = $this->a->analyze([
            'text'          => str_repeat('palavra ', 200),
            'focus_keyword' => 'cafe',
        ]);
        $this->assertSame('bad', $this->status($r0, 'fk_density'));
    }

    public function testArtigoBemOtimizadoPontuaAlto(): void
    {
        $texto = '<h2>O cafe especial em casa</h2>'
            . '<p>Tudo sobre cafe especial para quem ama cafe especial.</p>'
            . '<img src="g.jpg" alt="graos de cafe">'
            . '<a href="/loja">nossa loja</a>'
            . '<p>' . str_repeat('palavra ', 600) . ' cafe especial cafe especial</p>';

        $r = $this->a->analyze([
            'title'         => 'Guia do cafe especial para iniciantes em casa',
            'alias'         => 'guia-cafe-especial',
            'metadesc'      => str_repeat('Aprenda sobre cafe especial em casa. ', 4),
            'metakey'       => 'cafe, especial, graos',
            'text'          => $texto,
            'focus_keyword' => 'cafe especial',
        ]);

        $this->assertGreaterThanOrEqual(70, $r['score']);
        $this->assertSame('good', $r['rating']);
    }
}
