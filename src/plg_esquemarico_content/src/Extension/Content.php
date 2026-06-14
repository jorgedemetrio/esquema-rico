<?php

/**
 * @package     Esquema Rico
 * @subpackage  plg_esquemarico_content
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\Esquemarico\Content\Extension;

use Esquemarico\Core\Cache;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Component\Esquemarico\Administrator\Helper\MappingOptions;
use Joomla\Component\Esquemarico\Administrator\Plugin\PluginBaseArtigo;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Integração com o conteúdo nativo do Joomla (com_content).
 *
 * Extrai os dados do artigo da página atual (título, texto, imagens, datas,
 * autor, categoria, avaliação e campos personalizados) e os entrega como
 * payload para o motor de schema.
 */
final class Content extends PluginBaseArtigo
{
    protected function passContext(): bool
    {
        if (!$this->app()->getInput()->getInt('id')) {
            return false;
        }

        // Ignora pré-visualizações de construtores de página.
        return parent::passContext() && !$this->app()->getInput()->get('customizer');
    }

    /**
     * Payload do artigo atual.
     */
    public function viewArticle(): ?array
    {
        $factory = Factory::getApplication()->bootComponent('com_content')->getMVCFactory();

        /** @var \Joomla\Component\Content\Site\Model\ArticleModel $model */
        $model = $factory->createModel('Article', 'Site', ['ignore_request' => true]);
        $model->setState('article.id', $this->getThingID());
        $model->setState('params', $this->app()->getParams());

        $item = $model->getItem();

        if (!\is_object($item)) {
            return null;
        }

        $image = new Registry($item->images);

        $item->text = !empty($item->introtext) ? $item->introtext : ($item->fulltext ?? '');

        if ($this->params->get('preparecontent', 0)) {
            $this->prepareItem($item);
        }

        $payload = [
            'id'               => $item->id,
            'alias'            => $item->alias,
            'headline'         => $item->title,
            'description'      => $item->text,
            'introtext'        => $item->introtext,
            'fulltext'         => $item->fulltext,
            'image_intro'      => $image->get('image_intro'),
            'image_full'       => $image->get('image_fulltext'),
            'image'            => $image->get('image_intro') ?: $image->get('image_fulltext'),
            'imagetext'        => EsquemaRicoHelper::getFirstImageFromString(($item->introtext ?? '') . ($item->fulltext ?? '')),
            'created_by'       => $item->created_by,
            'created_by_alias' => $item->created_by_alias,
            'created'          => $item->created,
            'modified'         => $item->modified,
            'publish_up'       => $item->publish_up,
            'publish_down'     => $item->publish_down,
            'ratingValue'      => $item->rating ?? null,
            'reviewCount'      => $item->rating_count ?? null,
            'metakey'          => $item->metakey,
            'metadesc'         => $item->metadesc,
            'category.id'      => $item->catid,
            'category.title'   => $item->category_title ?? null,
            'category.alias'   => $item->category_alias ?? null,
        ];

        if ($this->params->get('load_custom_fields', 1)) {
            $this->attachCustomFields($item, $payload);
        }

        return $payload;
    }

    /**
     * Acrescenta os campos personalizados ao payload (prefixo cf.).
     */
    private function attachCustomFields(object $article, array &$payload, string $prefix = 'cf.'): void
    {
        $fields = $this->getCustomFields($article);

        if (!\is_array($fields) || \count($fields) === 0) {
            return;
        }

        foreach ($fields as $field) {
            $path  = $prefix . strtolower($field->name);
            $value = $field->value;

            if (!empty($field->rawvalue) && $field->value !== $field->rawvalue) {
                $value = $field->rawvalue;
            }

            if ($field->type === 'media') {
                $decoded = json_decode((string) $value, true);
                $value   = $decoded['imagefile'] ?? $value;
            }

            $payload[$path] = \is_array($value) ? implode(', ', $value) : $value;
        }
    }

    /**
     * Injeta o XML de condições no item de marcação E o formulário de edição
     * rápida no editor de artigos.
     */
    public function onContentPrepareForm($form, $data): void
    {
        // Comportamento padrão: condições no formulário do item.
        parent::onContentPrepareForm($form, $data);

        if (!($form instanceof Form) || $form->getName() !== 'com_content.article') {
            return;
        }

        if (!$this->params->get('fastedit', 1)) {
            return;
        }

        $user = Factory::getApplication()->getIdentity();

        if (!$user || !$user->authorise('core.manage', 'com_esquemarico')) {
            return;
        }

        $data = (array) $data;

        if (empty($data['id'])) {
            return;
        }

        $form->loadFile(__DIR__ . '/../../form/form.xml', false);
    }

    /**
     * Acrescenta opções de mapeamento próprias (imagens, categoria, campos).
     */
    public function onMapOptions(string $plugin, array &$options): void
    {
        if ($plugin !== $this->_name) {
            return;
        }

        MappingOptions::add($options, [
            'image_intro' => 'PLG_ESQUEMARICO_CONTENT_INTRO_IMAGE',
            'image_full'  => 'PLG_ESQUEMARICO_CONTENT_FULL_IMAGE',
        ], 'ESR_GROUP_INTEGRATION', 'gsd.item.');

        MappingOptions::add($options, [
            'category.id'    => 'PLG_ESQUEMARICO_CONTENT_CAT_ID',
            'category.alias' => 'PLG_ESQUEMARICO_CONTENT_CAT_ALIAS',
            'category.title' => 'PLG_ESQUEMARICO_CONTENT_CAT_TITLE',
        ], 'ESR_GROUP_INTEGRATION', 'gsd.item.');

        if (!$this->params->get('load_custom_fields', 1)) {
            return;
        }

        if ($fields = $this->getCustomFields()) {
            $opts = [];

            foreach ($fields as $field) {
                $opts[$field->name] = $field->title;
            }

            MappingOptions::add($options, $opts);
        }
    }

    /**
     * Carrega os campos personalizados de artigos (com cache).
     *
     * @return array<int, object>|null
     */
    private function getCustomFields(?object $article = null): ?array
    {
        return Cache::memo('cf.content.' . ($article->id ?? 'all'), function () use ($article) {
            if (!class_exists(FieldsHelper::class)) {
                return null;
            }

            return FieldsHelper::getFields('com_content.article', $article, true);
        });
    }

    /**
     * Prepara o artigo com os plugins de conteúdo do Joomla.
     */
    private function prepareItem(object $item): void
    {
        $params = new Registry();
        PluginHelper::importPlugin('content');
        $this->app()->triggerEvent('onContentPrepare', ['com_content.article', &$item, &$params, 0]);
    }
}
