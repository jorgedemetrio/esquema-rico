<?php
/**
 * @package     Esquema Rico
 * @subpackage  plg_content_esquemaricoredirect
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\Content\Esquemaricoredirect\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Event\SubscriberInterface;

/**
 * Plugin que monitora a mudança de alias do artigo e cria redirecionamento 301 automático.
 */
final class Esquemaricoredirect extends CMSPlugin implements SubscriberInterface
{
    /**
     * Armazena temporariamente o alias antigo do artigo sendo editado.
     *
     * @var array
     */
    private static array $aliasesAntigos = [];

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentBeforeSave' => 'aoAntesDeSalvar',
            'onContentAfterSave'  => 'aoAposSalvar',
        ];
    }

    /**
     * Executa antes de salvar o artigo para ler o alias atual gravado no banco.
     *
     * @param   string  $context  O contexto do evento.
     * @param   object  $article  O objeto do artigo sendo salvo.
     * @param   bool    $isNew    Indica se é um registro novo.
     * @return  void
     */
    public function aoAntesDeSalvar($context, $article, $isNew): void
    {
        if ($context !== 'com_content.article' || $isNew || empty($article->id)) {
            return;
        }

        try {
            $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('alias'))
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('id') . ' = ' . (int) $article->id);
            $db->setQuery($query);
            $aliasAntigo = $db->loadResult();

            if ($aliasAntigo) {
                self::$aliasesAntigos[$article->id] = $aliasAntigo;
            }
        } catch (\Exception $e) {
            // Silencia para não quebrar a gravação do artigo em caso de erros no banco
        }
    }

    /**
     * Executa após salvar o artigo. Se o alias mudou, cria o redirecionamento 301 no core com_redirects.
     *
     * @param   string  $context  O contexto do evento.
     * @param   object  $article  O objeto do artigo que foi salvo.
     * @param   bool    $isNew    Indica se é um registro novo.
     * @return  void
     */
    public function aoAposSalvar($context, $article, $isNew): void
    {
        if ($context !== 'com_content.article' || $isNew || empty($article->id) || empty($article->alias)) {
            return;
        }

        $id = $article->id;

        if (!isset(self::$aliasesAntigos[$id])) {
            return;
        }

        $aliasAntigo = self::$aliasesAntigos[$id];
        $aliasNovo   = $article->alias;

        if ($aliasAntigo === $aliasNovo) {
            return;
        }

        try {
            // Calcula URLs
            $newUrlAbsolute = Route::_('index.php?option=com_content&view=article&id=' . $id, true);
            $oldUrlAbsolute = str_replace($aliasNovo, $aliasAntigo, $newUrlAbsolute);

            $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

            // Verifica se a tabela #__redirect_links existe
            $tables    = $db->getTableList();
            $prefix    = $db->getPrefix();
            $tableName = $prefix . 'redirect_links';

            if (!\in_array($tableName, $tables, true)) {
                return;
            }

            // Verifica se já existe um redirecionamento cadastrado para essa old_url
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__redirect_links'))
                ->where($db->quoteName('old_url') . ' = ' . $db->quote($oldUrlAbsolute));
            $db->setQuery($query);
            $exists = $db->loadResult();

            if (!$exists) {
                $columns = ['old_url', 'new_url', 'referer', 'comment', 'published', 'created_date'];
                $values  = [
                    $db->quote($oldUrlAbsolute),
                    $db->quote($newUrlAbsolute),
                    $db->quote(''),
                    $db->quote('Criado automaticamente pelo Esquema Rico'),
                    1,
                    $db->quote(Factory::getDate()->toSql())
                ];

                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__redirect_links'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(',', $values));
                $db->setQuery($query);
                $db->execute();
            }
        } catch (\Exception $e) {
            // Silencia para não atrapalhar o fluxo principal do Joomla
        }
    }
}
