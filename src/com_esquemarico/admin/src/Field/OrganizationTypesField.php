<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;

\defined('_JEXEC') or die;

/**
 * Lista de subtipos de Organização do Schema.org mais comuns.
 *
 * https://schema.org/Organization
 */
class OrganizationTypesField extends ListField
{
    protected $type = 'OrganizationTypes';

    /**
     * @var array<string, string>
     */
    private const TIPOS = [
        'Organization'            => 'Organização (genérica)',
        'Corporation'             => 'Empresa / Corporação',
        'NGO'                     => 'ONG',
        'NonprofitOrganization'   => 'Organização sem fins lucrativos',
        'EducationalOrganization' => 'Instituição de ensino',
        'GovernmentOrganization'  => 'Órgão governamental',
        'NewsMediaOrganization'   => 'Veículo de imprensa',
        'SportsOrganization'      => 'Organização esportiva',
        'MedicalOrganization'     => 'Organização médica',
        'Airline'                 => 'Companhia aérea',
        'Consortium'              => 'Consórcio',
        'LibrarySystem'           => 'Sistema de bibliotecas',
        'FundingScheme'           => 'Programa de financiamento',
    ];

    protected function getOptions(): array
    {
        $options = parent::getOptions();

        foreach (self::TIPOS as $value => $label) {
            $options[] = (object) ['value' => $value, 'text' => $label, 'disable' => false];
        }

        return $options;
    }
}
