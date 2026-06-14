<?php

/**
 * @package     Esquema Rico
 * @subpackage  Biblioteca Esquemarico\Core
 *
 * Registra o namespace PSR-4 da biblioteca compartilhada para que qualquer
 * artefato da família (componente, plugin de sistema, integrações) consiga
 * usar as classes `Esquemarico\Core\*`.
 *
 * Uso pelos consumidores:
 *   @include_once JPATH_PLUGINS . '/system/esquemaricocore/autoload.php';
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

\defined('_JEXEC') or die;

// Evita registro duplicado.
if (!\class_exists('Esquemarico\\Core\\Functions', false)) {
    \JLoader::registerNamespace('Esquemarico\\Core', __DIR__ . '/Core', false, false, 'psr4');
}
