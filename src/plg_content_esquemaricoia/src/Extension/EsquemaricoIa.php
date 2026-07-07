<?php
/**
 * @package     Esquema Rico
 * @subpackage  plg_content_esquemaricoia
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Plugin\Content\EsquemaricoIa\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * Adiciona o botão de geração de matéria por IA ao editor de artigos.
 */
final class EsquemaricoIa extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return ['onContentPrepareForm' => 'aoPrepararFormulario'];
    }

    public function aoPrepararFormulario($event): void
    {
        $app = Factory::getApplication();

        if (!$app->isClient('administrator')) {
            return;
        }

        $form = method_exists($event, 'getForm') ? $event->getForm() : $event->getArgument('subject');

        if (!($form instanceof Form) || $form->getName() !== 'com_content.article') {
            return;
        }

        $form->loadFile(\dirname(__DIR__, 2) . '/form/ia.xml', false);
    }
}
