<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Schema;

use Esquemarico\Core\Cache;
use Esquemarico\Core\Functions;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Classe-base de preparação de um tipo de schema.
 *
 * Antes de o motor montar o JSON-LD, esta classe normaliza propriedades comuns:
 *   - renomeia propriedades declaradas com nome divergente no XML;
 *   - converte datas para ISO 8601 (com o fuso do site);
 *   - converte URLs/imagens para caminhos absolutos;
 *   - limpa HTML e espaços em branco;
 *   - quebra campos multivalor / faixas de preço.
 */
class Base
{
    /**
     * Propriedades do schema.
     */
    protected Registry $data;

    /**
     * Tags HTML permitidas ao limpar texto (null = nenhuma).
     */
    protected ?string $allowedHtmlTags = null;

    /**
     * Renomeações de propriedade: nome no XML => nome esperado pelo motor.
     *
     * @var array<string, string>
     */
    protected array $renameProperties = [];

    public function __construct(Registry|array|null $data = null)
    {
        $this->setData($data instanceof Registry ? $data : new Registry($data ?? []));
    }

    public function setData(Registry $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Devolve as propriedades já preparadas e limpas.
     */
    public function get(): Registry
    {
        $this->initProps();
        $this->cleanProps();

        return $this->data;
    }

    /**
     * Gancho temporário usado pelo tipo "código personalizado".
     */
    public function onPayloadPrepare(Registry &$payload): void
    {
    }

    /**
     * Prepara as propriedades comuns. Subclasses podem sobrescrever e chamar parent.
     */
    protected function initProps(): void
    {
        $this->renamePropertiesNow();
        $this->fixMultivalueProperties();
        $this->fixPriceRangeProperties();

        // Datas dentro do array de reviews.
        if ($reviews = $this->data->get('reviews')) {
            foreach ($reviews as &$review) {
                if (isset($review['datePublished'])) {
                    $review['datePublished'] = Functions::dateToISO8601($review['datePublished']);
                }
            }
            unset($review);

            $this->data->set('reviews', $reviews);
        }

        // Preserva o tipo já definido no item; só recorre ao nome da classe
        // quando ausente (tipos com classe de preparação dedicada).
        $name = (string) $this->data->get('contentType', $this->getName());

        $common = [
            'contentType'   => $name,
            'id'            => Uri::current() . '#' . $name . $this->data->get('snippet_id'),
            'title'         => $this->data->get('headline'),
            'description'   => $this->data->get('description'),
            'image'         => EsquemaRicoHelper::cleanImage(EsquemaRicoHelper::absUrl($this->data->get('image'))),

            'authorType'    => 'Person',
            'authorName'    => $this->data->get('author'),
            'authorUrl'     => $this->data->get('authorUrl', Uri::current()),

            'ratingValue'   => $this->data->get('rating_value'),
            'reviewCount'   => $this->data->get('review_count'),

            'datePublished' => Functions::dateToISO8601($this->data->get('publish_up')),
            'dateCreated'   => Functions::dateToISO8601($this->data->get('created')),
            'dateModified'  => Functions::dateToISO8601($this->data->get('modified')),

            'url'           => $this->data->get('url', Uri::current()),
            'siteurl'       => EsquemaRicoHelper::getSiteURL(),
            'sitename'      => EsquemaRicoHelper::getSiteName(),
        ];

        $this->data->merge(new Registry($common));
    }

    /**
     * Limpeza textual recursiva de todas as propriedades.
     */
    protected function cleanProps(): void
    {
        $props = $this->data->toArray();

        array_walk_recursive($props, function (&$prop) {
            if ($prop !== null && \is_string($prop)) {
                $this->cleanProp($prop);
            }
        });

        $this->data = new Registry($props);
    }

    /**
     * Torna um texto seguro para uso no JSON-LD.
     */
    protected function cleanProp(string &$prop): void
    {
        $prop = (string) preg_replace('#<script(.*?)>(.*?)</script>#is', '', $prop);

        if ($prop === '') {
            return;
        }

        $prop = strip_tags($prop, $this->allowedHtmlTags ?? '');
        $prop = htmlspecialchars($prop, ENT_QUOTES, 'UTF-8');
        $prop = (string) preg_replace('/\s+/s', ' ', $prop);
        $prop = trim($prop);
    }

    /**
     * Nome do tipo (em minúsculas) derivado do nome curto da classe.
     */
    protected function getName(): string
    {
        return strtolower((new \ReflectionClass($this))->getShortName());
    }

    /**
     * Resolve o alias do tipo: preferindo o valor já presente nos dados
     * (definido pelo item), recorrendo ao nome da classe quando ausente.
     */
    protected function tipoNome(): string
    {
        return (string) $this->data->get('contentType', $this->getName());
    }

    private function renamePropertiesNow(): void
    {
        foreach ($this->renameProperties as $old => $new) {
            if ($this->data->exists($old)) {
                $this->data->set($new, $this->data->get($old));
                $this->data->remove($old);
            }
        }
    }

    /**
     * Quebra faixas de preço "10-20" no array [10, 20] (campos real_type=pricerange).
     */
    private function fixPriceRangeProperties(): void
    {
        foreach ($this->getXmlFields() as $key => $field) {
            if (($field['real_type'] ?? '') !== 'pricerange') {
                continue;
            }

            if (!$current = $this->data->get($key)) {
                continue;
            }

            $parts = explode('-', (string) $current, 2);

            $this->data->set($key, \count($parts) === 1 ? [$current, $current] : $parts);
        }
    }

    /**
     * Transforma campos multivalor (uma entrada por linha) em arrays.
     */
    private function fixMultivalueProperties(): void
    {
        foreach ($this->getXmlFields() as $key => $field) {
            if (!isset($field['custom_value_multiple'])) {
                continue;
            }

            $value = Functions::makeArray($this->data->get($key));

            if (\count($value) > 1) {
                $this->data->set($key, $value);
            }
        }
    }

    /**
     * Lê os campos declarados no XML do tipo (com cache por requisição).
     *
     * @return array<string, array<string, string>>
     */
    private function getXmlFields(): array
    {
        $name = $this->tipoNome();

        return Cache::memo('xmlFields.' . $name, function () use ($name): array {
            $path = JPATH_ADMINISTRATOR . '/components/com_esquemarico/forms/contenttypes/' . $name . '.xml';

            if (!is_file($path)) {
                return [];
            }

            $xml    = simplexml_load_file($path);
            $fields = [];

            if ($xml && isset($xml->fieldset->fields->field)) {
                foreach ($xml->fieldset->fields->field as $field) {
                    $attrs = (array) $field->attributes();
                    $attrs = $attrs['@attributes'] ?? [];

                    if (isset($attrs['name'])) {
                        $fields[$attrs['name']] = $attrs;
                    }
                }
            }

            return $fields;
        });
    }
}
