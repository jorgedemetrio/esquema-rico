<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Schema\Tipos;

use Joomla\Component\Esquemarico\Administrator\Schema\Base;

\defined('_JEXEC') or die;

/**
 * Preparação do tipo Código Personalizado.
 *
 * O usuário fornece o JSON-LD bruto; portanto NÃO aplicamos as normalizações
 * comuns (datas, URLs) nem a limpeza de HTML, que corromperiam o script. As
 * SmartTags já foram substituídas antes desta etapa.
 */
final class Custom_Code extends Base
{
    protected function initProps(): void
    {
        // Apenas garante o tipo para o dispatch do gerador.
        $this->data->set('contentType', 'custom_code');
    }

    protected function cleanProps(): void
    {
        // Não limpar: preservar o conteúdo bruto fornecido pelo usuário.
    }
}
