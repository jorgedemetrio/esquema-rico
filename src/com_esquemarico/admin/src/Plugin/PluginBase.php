<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Plugin;

use Esquemarico\Core\Conditions\ConditionsHelper;
use Esquemarico\Core\Extension as ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Esquemarico\Administrator\Engine\GeradorJsonLd;
use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Component\Esquemarico\Administrator\Helper\MappingOptions;
use Joomla\Component\Esquemarico\Administrator\Schema\SchemaHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Classe-base dos plugins de integração.
 *
 * Implementa o ciclo de vida padrão: ao receber o evento de renderização,
 * valida o contexto, obtém o payload da fonte, busca os itens de marcação
 * aplicáveis, avalia as condições de publicação e gera o JSON-LD.
 *
 * As integrações concretas implementam, no mínimo, um método view<Nome>().
 */
abstract class PluginBase extends CMSPlugin
{
    protected $autoloadLanguage = true;

    /**
     * Nome da variável de query com o ID do item da fonte.
     */
    protected string $thingRequestIdName = 'id';

    /**
     * Nome da variável de query com a view ativa da fonte.
     */
    protected string $thingRequestViewVar = 'view';

    /**
     * Aplicação em execução. Usamos o Factory diretamente para não depender
     * do caminho de instanciação do plugin (legado vs. provider/DI).
     */
    protected function app(): \Joomla\CMS\Application\CMSApplicationInterface
    {
        return Factory::getApplication();
    }

    /**
     * Publica as views suportadas (introspecção dos métodos view<Nome>).
     *
     * @return array<string, string>
     */
    public function advertiseSupportedViews(): array
    {
        $views = [];

        foreach (get_class_methods($this) as $method) {
            if (!str_starts_with($method, 'view')) {
                continue;
            }

            $view = strtolower(substr($method, 4));

            if ($view === '') {
                continue;
            }

            $views[$view] = Text::_('PLG_ESQUEMARICO_' . strtoupper($this->_name) . '_VIEW_' . strtoupper($view));
        }

        return $views;
    }

    /**
     * Evento usado pelos dropdowns do backend para listar integrações.
     *
     * @return array{name: string, alias: string}|null
     */
    public function onEsquemaRicoGetType(bool $mustBeInstalled = true): ?array
    {
        if ($mustBeInstalled && !ExtensionHelper::isInstalled($this->_name, 'component', null) && !$this->fonteInstalada()) {
            return null;
        }

        return [
            'name'  => Text::_('PLG_ESQUEMARICO_' . strtoupper($this->_name) . '_ALIAS'),
            'alias' => $this->_name,
        ];
    }

    /**
     * Evento principal: contribui com os blocos JSON-LD desta integração.
     *
     * @param  array<int, string>  $data
     */
    public function onEsquemaRicoBeforeRender(array &$data): void
    {
        if (!$this->passContext()) {
            return;
        }

        $payload = $this->getPayload();

        if ($payload === null) {
            return;
        }

        $snippets = $this->getSnippets();

        if (!$snippets) {
            $this->log('Nenhum item válido encontrado.');

            return;
        }

        foreach ($snippets as $snippet) {
            $jsonData = $this->preparePayload($snippet, $payload);
            $json     = (new GeradorJsonLd($jsonData))->generate();

            if ($json) {
                $data[] = $json;
            }
        }
    }

    /**
     * Injeta o XML de condições no formulário do item de marcação (backend).
     */
    public function onContentPrepareForm($form, $data): void
    {
        if (!$this->app()->isClient('administrator') || $form->getName() !== 'com_esquemarico.item') {
            return;
        }

        $tmp = (object) $data;

        if (($tmp->plugin ?? null) !== $this->_name) {
            return;
        }

        $view    = $tmp->appview ?? '';
        $base    = JPATH_PLUGINS . '/esquemarico/' . $this->_name . '/form/';
        $xmlName = ($view !== '' && $view !== '*') ? $view : 'assignments';
        $xml     = $base . $xmlName . '.xml';

        if (!is_file($xml)) {
            $xml = $base . 'assignments.xml';

            if (!is_file($xml)) {
                return;
            }
        }

        $form->loadFile($xml, false);
    }

    /**
     * Valida rapidamente se o plugin deve atuar nesta página.
     */
    protected function passContext(): bool
    {
        return EsquemaRicoHelper::getComponentAlias() === $this->_name;
    }

    /**
     * ID do item da fonte na página atual.
     */
    protected function getThingID(): int
    {
        return (int) $this->app()->getInput()->getInt($this->thingRequestIdName);
    }

    /**
     * View ativa da fonte.
     */
    protected function getView(): string
    {
        return (string) $this->app()->getInput()->get($this->thingRequestViewVar);
    }

    /**
     * Pede à integração o payload conforme a view ativa.
     */
    protected function getPayload(): ?Registry
    {
        $view   = $this->getView();
        $method = 'view' . ucfirst($view);

        if ($view === '' || !method_exists($this, $method)) {
            $this->log('View "' . $view . '" não suportada.');

            return null;
        }

        $payload = $this->$method();

        if (!\is_array($payload)) {
            $this->log('Payload inválido.');

            return null;
        }

        // Converte objetos aninhados em arrays associativos.
        $payload = json_decode(json_encode($payload), true);

        return new Registry($payload);
    }

    /**
     * Busca os itens de marcação aplicáveis e filtra pelas condições.
     *
     * @return array<int, Registry>
     */
    protected function getSnippets(): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__esquemarico'))
            ->where($db->quoteName('state') . ' = 1')
            ->where($db->quoteName('plugin') . ' = :plugin')
            ->bind(':plugin', $this->_name);

        $view = $this->getView();
        $query->whereIn($db->quoteName('appview'), [$view, '*'], \Joomla\Database\ParameterType::STRING);

        if (Multilanguage::isEnabled()) {
            $tag = Factory::getApplication()->getLanguage()->getTag();
            $query->whereIn($db->quoteName('language'), [$tag, '*'], \Joomla\Database\ParameterType::STRING);
        }

        $query->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        $items     = [];
        $conditions = new ConditionsHelper();

        foreach ($rows as $row) {
            $params = new Registry($row->params ?? '{}');

            // Avalia as condições de publicação.
            if (!$this->passAssignments($params, $conditions, (int) $row->id)) {
                continue;
            }

            $contentType     = $row->contenttype;
            $contentTypeData = $params->get($contentType, []);

            $snippet = new Registry($contentTypeData);
            $snippet->set('contentType', $contentType);
            $snippet->set('snippet_id', (int) $row->id);

            $this->log('Item #' . $row->id . ' aplicável.');

            $items[] = $snippet;
        }

        return $items;
    }

    /**
     * Avalia as condições de publicação armazenadas em params->assignments.
     */
    protected function passAssignments(Registry $params, ConditionsHelper $conditions, int $id): bool
    {
        $assignments = $params->get('assignments');

        if (empty($assignments)) {
            return true;
        }

        $rules = [];

        foreach ((array) $assignments as $alias => $assignment) {
            $assignment = (object) $assignment;

            if (($assignment->assignment_state ?? '0') === '0' || str_contains((string) $alias, '@')) {
                continue;
            }

            if (!isset($assignment->selection)) {
                continue;
            }

            $rules[] = [
                'alias'    => $alias,
                'operator' => $assignment->operator ?? 'includes',
                'value'    => $assignment->selection,
                'params'   => $assignment->params ?? [],
            ];
        }

        $pass = $conditions->passSet($rules, 'all');

        if (!$pass) {
            $this->log('Item #' . $id . ' não passou nas condições.');
        }

        return $pass;
    }

    /**
     * Mescla mapeamentos + SmartTags e prepara as propriedades do tipo.
     */
    protected function preparePayload(Registry $snippet, Registry $payload): Registry
    {
        $schema = SchemaHelper::getInstance((string) $snippet->get('contentType'));
        $schema->onPayloadPrepare($payload);

        MappingOptions::prepare($snippet);

        $merged = (clone $payload)->merge($snippet, false);
        $merged = MappingOptions::replace($merged, $payload);

        return $schema->setData($merged)->get();
    }

    /**
     * A extensão de origem desta integração está instalada?
     * Subclasses específicas podem sobrescrever.
     */
    protected function fonteInstalada(): bool
    {
        return true;
    }

    protected function log(string $message): void
    {
        EsquemaRicoHelper::log(Text::_('PLG_ESQUEMARICO_' . strtoupper($this->_name) . '_ALIAS') . ' - ' . $message);
    }
}
