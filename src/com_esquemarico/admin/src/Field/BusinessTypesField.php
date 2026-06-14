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
 * Lista de subtipos de Negócio Local do Schema.org mais comuns.
 *
 * https://schema.org/LocalBusiness
 */
class BusinessTypesField extends ListField
{
    protected $type = 'BusinessTypes';

    /**
     * Subtipos: valor Schema.org => rótulo em português.
     *
     * @var array<string, string>
     */
    private const TIPOS = [
        'LocalBusiness'        => 'Negócio local (genérico)',
        'Restaurant'           => 'Restaurante',
        'CafeOrCoffeeShop'     => 'Café',
        'BarOrPub'             => 'Bar',
        'Bakery'               => 'Padaria',
        'Store'                => 'Loja',
        'ClothingStore'        => 'Loja de roupas',
        'GroceryStore'         => 'Mercado',
        'ElectronicsStore'     => 'Loja de eletrônicos',
        'Hotel'                => 'Hotel',
        'LodgingBusiness'      => 'Hospedagem',
        'HealthAndBeautyBusiness' => 'Saúde e beleza',
        'BeautySalon'          => 'Salão de beleza',
        'MedicalBusiness'      => 'Estabelecimento médico',
        'Dentist'              => 'Consultório odontológico',
        'LegalService'         => 'Serviço jurídico',
        'AccountingService'    => 'Contabilidade',
        'RealEstateAgent'      => 'Imobiliária',
        'AutomotiveBusiness'   => 'Automotivo',
        'AutoRepair'           => 'Oficina mecânica',
        'ProfessionalService'  => 'Serviço profissional',
        'EntertainmentBusiness' => 'Entretenimento',
        'SportsActivityLocation' => 'Local de atividade esportiva',
        'TravelAgency'         => 'Agência de viagens',
        'FinancialService'     => 'Serviço financeiro',
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
