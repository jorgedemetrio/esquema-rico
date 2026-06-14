<?php

/**
 * @package     Esquema Rico
 * @subpackage  com_esquemarico
 *
 * @copyright   Copyright (C) 2026 Esquema Rico. Todos os direitos reservados.
 * @license     GNU GPL v3 ou posterior <https://www.gnu.org/licenses/gpl-3.0.html>
 */

namespace Joomla\Component\Esquemarico\Administrator\Engine;

use Joomla\Component\Esquemarico\Administrator\Helper\EsquemaRicoHelper;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Motor de geração de JSON-LD.
 *
 * Recebe os dados já preparados (mapeamentos resolvidos, SmartTags substituídas,
 * propriedades normalizadas) e produz o bloco
 *   <script type="application/ld+json" data-type="esr"> ... </script>.
 *
 * Cada tipo de conteúdo tem um método construtor contentType<Tipo>() que devolve
 * o array do schema. A serialização final remove propriedades vazias e prepende
 * o @context.
 */
final class GeradorJsonLd
{
    /**
     * Dados do tipo de conteúdo atual.
     */
    private Registry $data;

    /**
     * Tipos de conteúdo (schemas) disponíveis.
     *
     * @var string[]
     */
    private array $contentTypes = [
        'article', 'book', 'course', 'event', 'product', 'movie', 'recipe',
        'review', 'factcheck', 'video', 'jobposting', 'faq', 'howto',
        'localbusiness', 'service', 'person', 'organization',
        'qapage', 'softwareapplication', 'custom_code',
    ];

    public function __construct(array|Registry|null $data = null)
    {
        $this->setData($data);
    }

    public function setData(array|Registry|null $data): static
    {
        $this->data = $data instanceof Registry ? $data : new Registry($data ?? []);

        return $this;
    }

    /**
     * Retorna a lista ordenada de tipos de conteúdo (Código Personalizado por último).
     *
     * @return string[]
     */
    public function getContentTypes(): array
    {
        $types = $this->contentTypes;
        sort($types);

        if (($i = array_search('custom_code', $types, true)) !== false) {
            unset($types[$i]);
            $types[] = 'custom_code';
        }

        return array_values($types);
    }

    /**
     * Gera o bloco <script> JSON-LD para o tipo de conteúdo atual.
     */
    public function generate(): ?string
    {
        $content = $this->buildContent(strtolower((string) $this->data->get('contentType')));

        if (!$content) {
            return null;
        }

        // Código personalizado: devolve a string como está.
        if (\is_string($content)) {
            return $content;
        }

        EsquemaRicoHelper::event('onEsquemaRicoSchemaBeforeGenerate', [&$content, $this->data]);

        if (!$content) {
            return null;
        }

        // Remove propriedades nulas/vazias (preservando o zero).
        $content = $this->clean($content);

        // Prepende sempre o @context.
        $content = ['@context' => 'https://schema.org'] + $content;

        try {
            $json = json_encode(
                $content,
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            EsquemaRicoHelper::log('Erro de codificação JSON: ' . $e->getMessage());

            return null;
        }

        return "\n" . '<script type="application/ld+json" data-type="esr">' . "\n" . $json . "\n" . '</script>';
    }

    /**
     * Remove recursivamente propriedades null, false e string vazia (mantém 0),
     * além de descartar objetos órfãos que só contêm @type.
     */
    private function clean(array $input): array
    {
        foreach ($input as &$value) {
            if (\is_array($value)) {
                $value = $this->clean($value);
            }
        }
        unset($value);

        return array_filter($input, static function ($value) {
            if (\is_array($value) && \count($value) === 1 && isset($value['@type'])) {
                return false;
            }

            return $value !== null && $value !== false && $value !== '';
        });
    }

    /**
     * Despacha para o construtor do tipo de conteúdo.
     *
     * Allowlist explícita: o nome do método nunca é derivado diretamente do
     * valor de entrada (evita construção de nome de método a partir de dado
     * controlável). Tipo desconhecido devolve null.
     */
    private function buildContent(string $type): array|string|null
    {
        return match ($type) {
            'article'        => $this->contentTypeArticle(),
            'book'           => $this->contentTypeBook(),
            'course'         => $this->contentTypeCourse(),
            'event'          => $this->contentTypeEvent(),
            'product'        => $this->contentTypeProduct(),
            'movie'          => $this->contentTypeMovie(),
            'recipe'         => $this->contentTypeRecipe(),
            'review'         => $this->contentTypeReview(),
            'factcheck'      => $this->contentTypeFactCheck(),
            'video'          => $this->contentTypeVideo(),
            'jobposting'     => $this->contentTypeJobPosting(),
            'faq'            => $this->contentTypeFAQ(),
            'howto'          => $this->contentTypeHowTo(),
            'localbusiness'  => $this->contentTypeLocalBusiness(),
            'service'        => $this->contentTypeService(),
            'person'         => $this->contentTypePerson(),
            'organization'        => $this->contentTypeOrganization(),
            'qapage'              => $this->contentTypeQAPage(),
            'softwareapplication' => $this->contentTypeSoftwareApplication(),
            'custom_code'         => $this->contentTypeCustom_Code(),
            'website'        => $this->contentTypeWebsite(),
            'logo'           => $this->contentTypeLogo(),
            'socialprofiles' => $this->contentTypeSocialProfiles(),
            'breadcrumbs'    => $this->contentTypeBreadcrumbs(),
            default          => null,
        };
    }

    /* ===================================================================
     *  Construtores por tipo de conteúdo
     * =================================================================== */

    private function contentTypeArticle(): array
    {
        $content = [
            '@type'            => $this->data->get('type', 'Article'),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $this->data->get('url'),
            ],
            'headline'    => $this->data->get('title'),
            'description' => $this->data->get('description'),
            'image'       => [
                '@type' => 'ImageObject',
                'url'   => $this->data->get('image'),
            ],
        ];

        if ($this->data->get('publisherName')) {
            $content['publisher'] = [
                '@type' => 'Organization',
                'name'  => $this->data->get('publisherName'),
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => $this->data->get('publisherLogo'),
                ],
            ];
        }

        $this->addAuthor($content);

        return $this->addDate($content);
    }

    private function contentTypeProduct(): array
    {
        $content = [
            '@type'       => 'Product',
            'productID'   => $this->data->get('mpn'),
            'name'        => $this->data->get('title'),
            'image'       => $this->data->get('image'),
            'description' => $this->data->get('description'),
            'sku'         => $this->data->get('sku'),
            'mpn'         => $this->data->get('mpn'),
            'gtin'        => $this->data->get('gtin'),
        ];

        if ($this->data->get('weight')) {
            $content['weight'] = [
                '@type'    => 'QuantitativeValue',
                'value'    => $this->data->get('weight'),
                'unitText' => $this->data->get('weightUnit'),
            ];
        }

        if ($this->data->get('brand')) {
            $content['brand'] = [
                '@type' => 'Brand',
                'name'  => $this->data->get('brand'),
            ];
        }

        if ($price = $this->data->get('offerPrice')) {
            $offerCommon = [
                'priceCurrency'   => $this->data->get('currency'),
                'url'             => $this->data->get('url'),
                'itemCondition'   => $this->data->get('offerItemCondition'),
                'availability'    => $this->data->get('offerAvailability'),
                'priceValidUntil' => $this->data->get('priceValidUntil'),
            ];

            if (\is_array($price)) {
                $offer = [
                    '@type'      => 'AggregateOffer',
                    'offerCount' => $this->data->get('offerCount', 1),
                    'lowPrice'   => $price[0] ?? null,
                    'highPrice'  => $price[1] ?? null,
                ];
            } else {
                $offer = ['@type' => 'Offer', 'price' => $price];
            }

            $content['offers'] = array_merge($offer, $offerCommon);
        }

        $this->addReview($content);
        $this->addRating($content);

        return $content;
    }

    private function contentTypeEvent(): array
    {
        $content = [
            '@type'               => 'Event',
            'name'                => $this->data->get('title'),
            'image'               => $this->data->get('image'),
            'description'         => $this->data->get('description'),
            'url'                 => $this->data->get('url'),
            'startDate'           => $this->data->get('startDate'),
            'endDate'             => $this->data->get('endDate'),
            'eventStatus'         => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => $this->data->get('eventAttendanceMode', 'https://schema.org/OfflineEventAttendanceMode'),
            'offers'              => [
                '@type'         => 'Offer',
                'url'           => $this->data->get('url'),
                'availability'  => $this->data->get('offerAvailability'),
                'validFrom'     => $this->data->get('startDateTime'),
                'price'         => $this->data->get('price'),
                'priceCurrency' => $this->data->get('offerCurrency'),
            ],
        ];

        if ($this->data->get('locationName')) {
            $content['location'] = [
                '@type'   => 'Place',
                'name'    => $this->data->get('locationName'),
                'address' => $this->getPostalAddress(),
            ];
        }

        if ($onlineUrl = $this->data->get('online_url')) {
            $online = ['@type' => 'VirtualLocation', 'url' => $onlineUrl];

            $content['location'] = isset($content['location'])
                ? [$content['location'], $online]
                : $online;
        }

        if ($this->data->get('performerName')) {
            $content['performer'] = [
                '@type' => $this->data->get('performerType', 'PerformingGroup'),
                'name'  => $this->data->get('performerName'),
                'url'   => $this->data->get('performerURL'),
            ];
        }

        if ($this->data->get('organizerName')) {
            $content['organizer'] = [
                '@type' => $this->data->get('organizerType', 'Organization'),
                'name'  => $this->data->get('organizerName'),
                'url'   => $this->data->get('organizerURL'),
            ];
        }

        return $content;
    }

    private function contentTypeRecipe(): array
    {
        $content = [
            '@type'              => 'Recipe',
            'name'               => $this->data->get('title'),
            'image'              => $this->data->get('image'),
            'description'        => $this->data->get('description'),
            'prepTime'           => $this->data->get('prepTime'),
            'cookTime'           => $this->data->get('cookTime'),
            'totalTime'          => $this->data->get('totalTime'),
            'keywords'           => $this->data->get('keywords'),
            'recipeCuisine'      => $this->data->get('cuisine'),
            'recipeCategory'     => $this->data->get('category'),
            'recipeYield'        => $this->data->get('yield'),
            'recipeIngredient'   => $this->data->get('ingredient'),
            'recipeInstructions' => $this->data->get('instructions'),
        ];

        if ($this->data->get('calories')) {
            $content['nutrition'] = [
                '@type'    => 'NutritionInformation',
                'calories' => $this->data->get('calories'),
            ];
        }

        if ($this->data->get('video')) {
            $content['video'] = [
                '@type'        => 'VideoObject',
                'name'         => $this->data->get('title'),
                'description'  => $this->data->get('description'),
                'thumbnailUrl' => $this->data->get('image'),
                'contentUrl'   => $this->data->get('video'),
                'uploadDate'   => $this->data->get('datePublished'),
            ];
        }

        $this->addAuthor($content);
        $this->addRating($content);

        return $this->addDate($content);
    }

    private function contentTypeFAQ(): ?array
    {
        $faq = (array) $this->data->get('faqs');

        if (\count($faq) === 0) {
            return null;
        }

        $entities = [];

        foreach ($faq as $item) {
            $item = (array) $item;

            $entities[] = [
                '@type'          => 'Question',
                'name'           => $item['question'] ?? '',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $item['answer'] ?? '',
                ],
            ];
        }

        return ['@type' => 'FAQPage', 'mainEntity' => $entities];
    }

    private function contentTypeHowTo(): array
    {
        $steps = array_map(
            static fn ($step): array => array_merge(['@type' => 'HowToStep'], (array) $step),
            (array) $this->data->get('step')
        );

        $tools = array_map(
            static fn ($tool): array => ['@type' => 'HowToTool', 'name' => $tool],
            (array) $this->data->get('tool')
        );

        $supply = array_map(
            static fn ($s): array => ['@type' => 'HowToSupply', 'name' => $s],
            (array) $this->data->get('supply')
        );

        return [
            '@type'         => 'HowTo',
            'image'         => ['@type' => 'ImageObject', 'url' => $this->data->get('image')],
            'name'          => $this->data->get('name'),
            'totalTime'     => $this->data->get('totalTime'),
            'estimatedCost' => [
                '@type'    => 'MonetaryAmount',
                'currency' => $this->data->get('estimatedCostCurrency'),
                'value'    => $this->data->get('estimatedCost'),
            ],
            'supply' => $supply,
            'tool'   => $tools,
            'step'   => $steps,
        ];
    }

    private function contentTypeLocalBusiness(): array
    {
        $content = [
            '@type'      => $this->data->get('type', 'LocalBusiness'),
            '@id'        => $this->data->get('id'),
            'name'       => $this->data->get('name'),
            'image'      => $this->data->get('image'),
            'url'        => $this->data->get('url'),
            'telephone'  => $this->data->get('telephone'),
            'priceRange' => $this->data->get('priceRange'),
            'address'    => $this->getPostalAddress(),
        ];

        $coords = (array) $this->data->get('geo');
        if (\count($coords) === 2) {
            $content['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $coords[0],
                'longitude' => $coords[1],
            ];
        }

        if ($hours = $this->data->get('openinghours')) {
            $content = array_merge($content, $this->getOpeningHours($hours));
        }

        $content['servesCuisine'] = $this->data->get('servesCuisine');
        $content['menu']          = $this->data->get('menu');

        $this->addReview($content);
        $this->addRating($content);

        return $content;
    }

    private function contentTypeOrganization(): array
    {
        $content = [
            '@type'             => $this->data->get('type', 'Organization'),
            '@id'               => $this->data->get('id'),
            'name'              => $this->data->get('name'),
            'alternateName'     => $this->data->get('alternateName'),
            'legalName'         => $this->data->get('legalName'),
            'description'       => $this->data->get('description'),
            'email'             => $this->data->get('email'),
            'url'               => $this->data->get('url'),
            'telephone'         => $this->data->get('telephone'),
            'foundingDate'      => $this->data->get('foundingDate'),
            'taxID'             => $this->data->get('taxID'),
            'vatID'             => $this->data->get('vatID'),
            'iso6523Code'       => $this->data->get('iso6523Code'),
            'duns'              => $this->data->get('duns'),
            'leiCode'           => $this->data->get('leiCode'),
            'naics'             => $this->data->get('naics'),
            'numberOfEmployees' => $this->data->get('numberOfEmployees'),
            'logo'              => $this->data->get('logo'),
            'address'           => $this->getPostalAddress(),
        ];

        if ($sameAs = $this->normalizeSameAs($this->data->get('sameAs', []))) {
            $content['sameAs'] = $sameAs;
        }

        $this->addRating($content);

        return $content;
    }

    private function contentTypePerson(): array
    {
        $content = [
            '@type'           => 'Person',
            '@id'             => $this->data->get('id'),
            'url'             => $this->data->get('url'),
            'name'            => $this->data->get('title'),
            'description'     => $this->data->get('description'),
            'honorificPrefix' => $this->data->get('honorificPrefix'),
            'honorificSuffix' => $this->data->get('honorificSuffix'),
            'alternateName'   => $this->data->get('alternateName'),
            'additionalName'  => $this->data->get('additionalName'),
            'givenName'       => $this->data->get('givenName'),
            'familyName'      => $this->data->get('familyName'),
            'address'         => $this->getPostalAddress(),
            'nationality'     => $this->data->get('nationality'),
            'email'           => $this->data->get('email'),
            'telephone'       => $this->data->get('telephone'),
            'gender'          => $this->data->get('gender'),
            'birthDate'       => $this->data->get('birthDate'),
            'image'           => $this->data->get('image'),
            'jobTitle'        => $this->data->get('jobTitle'),
            'award'           => $this->data->get('award'),
            'knowsAbout'      => $this->data->get('knowsAbout'),
        ];

        if ($this->data->get('type') && $this->data->get('type') !== 'Person') {
            $content['additionalType'] = $this->data->get('type');
        }

        if ($worksFor = $this->data->get('worksFor')) {
            $content['worksFor'] = ['@type' => 'Organization', 'name' => $worksFor];
        }

        if ($sameAs = $this->normalizeSameAs($this->data->get('sameAs', []))) {
            $content['sameAs'] = $sameAs;
        }

        return $content;
    }

    private function contentTypeCourse(): array
    {
        $content = [
            '@type'             => 'Course',
            'name'              => $this->data->get('title'),
            'description'       => $this->data->get('description'),
            'courseCode'        => $this->data->get('course_code'),
            'provider'          => [
                '@type' => 'Organization',
                'name'  => $this->data->get('sitename'),
            ],
            'hasCourseInstance' => [
                '@type'          => 'CourseInstance',
                'name'           => $this->data->get('title'),
                'description'    => $this->data->get('description'),
                'courseMode'     => $this->data->get('course_mode'),
                'startDate'      => $this->data->get('startDate'),
                'endDate'        => $this->data->get('endDate'),
                'location'       => [
                    '@type'   => 'Place',
                    'name'    => $this->data->get('place_name'),
                    'address' => $this->getPostalAddress(),
                ],
                'courseWorkload' => $this->data->get('courseWorkload'),
            ],
        ];

        if ($this->data->get('price')) {
            $content['offers'] = [[
                '@type'         => 'Offer',
                'category'      => $this->data->get('offerCategory', 'Free'),
                'url'           => $this->data->get('url'),
                'availability'  => $this->data->get('availability'),
                'price'         => $this->data->get('price'),
                'priceCurrency' => $this->data->get('priceCurrency'),
                'validFrom'     => $this->data->get('validFrom'),
            ]];
        }

        $this->addRating($content);

        return $this->addDate($content);
    }

    private function contentTypeBook(): array
    {
        $workExample = [
            '@type'         => 'Book',
            '@id'           => $this->data->get('id'),
            'bookFormat'    => $this->data->get('bookFormat'),
            'inLanguage'    => $this->data->get('inLanguage'),
            'isbn'          => $this->data->get('isbn'),
            'url'           => $this->data->get('url'),
            'bookEdition'   => $this->data->get('edition'),
            'datePublished' => $this->data->get('datePublished'),
        ];

        $this->addAuthor($workExample);

        $content = [
            '@type'       => 'Book',
            '@id'         => $this->data->get('id'),
            'url'         => $this->data->get('url'),
            'name'        => $this->data->get('title'),
            'image'       => $this->data->get('image'),
            'sameAs'      => $this->data->get('referenceURL'),
            'inLanguage'  => $this->data->get('inLanguage'),
            'workExample' => $workExample,
        ];

        $this->addAuthor($content);

        return $content;
    }

    private function contentTypeMovie(): array
    {
        $content = [
            '@type'       => 'Movie',
            'url'         => $this->data->get('url'),
            'name'        => $this->data->get('title'),
            'description' => $this->data->get('description'),
            'image'       => $this->data->get('image'),
            'dateCreated' => $this->data->get('datePublished'),
            'duration'    => $this->data->get('duration'),
        ];

        foreach (['creators' => 'creator', 'directors' => 'director', 'actors' => 'actor'] as $src => $dest) {
            foreach ((array) $this->data->get($src) as $person) {
                $person = (object) $person;

                if (!empty($person->name)) {
                    $content[$dest][] = ['@type' => 'Person', 'name' => trim((string) $person->name)];
                }
            }
        }

        foreach ((array) $this->data->get('genre') as $g) {
            $g = (object) $g;

            if (!empty($g->name)) {
                $content['genre'][] = trim((string) $g->name);
            }
        }

        if ($this->data->get('trailerUrl')) {
            $content['trailer'] = [
                '@type'        => 'VideoObject',
                'embedUrl'     => $this->data->get('trailerUrl'),
                'name'         => $this->data->get('title'),
                'thumbnailUrl' => $this->data->get('image'),
                'description'  => $this->data->get('description'),
                'uploadDate'   => $this->data->get('datePublished'),
            ];
        }

        $this->addRating($content);
        $this->addReview($content);

        return $content;
    }

    private function contentTypeReview(): array
    {
        $content = [
            '@type'         => 'Review',
            'description'   => $this->data->get('description'),
            'url'           => $this->data->get('url'),
            'datePublished' => $this->data->get('datePublished'),
            'publisher'     => [
                '@type'  => 'Organization',
                'name'   => $this->data->get('sitename'),
                'sameAs' => $this->data->get('siteurl'),
            ],
            'inLanguage'    => $this->data->get('language_code'),
            'itemReviewed'  => [
                '@type'  => $this->data->get('itemReviewedType', 'Thing'),
                'name'   => $this->data->get('title'),
                'image'  => $this->data->get('image'),
                'sameAs' => $this->data->get('itemReviewedURL'),
            ],
        ];

        $this->addAuthor($content);

        if ($this->data->get('ratingValue')) {
            $content['reviewRating'] = [
                '@type'       => 'Rating',
                'ratingValue' => $this->data->get('ratingValue'),
                'worstRating' => $this->data->get('worstRating', 0),
                'bestRating'  => $this->data->get('bestRating', 5),
            ];
        }

        return $content;
    }

    private function contentTypeJobPosting(): array
    {
        $json = [
            '@type'                 => 'JobPosting',
            'title'                 => $this->data->get('title'),
            'description'           => $this->data->get('description'),
            'datePosted'            => $this->data->get('datePublished'),
            'educationRequirements' => $this->data->get('educationRequirements'),
            'employmentType'        => $this->data->get('employmenttype'),
            'industry'              => $this->data->get('industry'),
            'jobLocation'           => [
                '@type'   => 'Place',
                'address' => $this->getPostalAddress(),
            ],
            'hiringOrganization'    => [
                '@type'  => 'Organization',
                'name'   => $this->data->get('hiring_organization_name'),
                'sameAs' => $this->data->get('hiring_organization_url'),
                'logo'   => $this->data->get('hiring_organization_logo'),
            ],
            'validThrough'          => $this->data->get('valid_through'),
        ];

        $salary = $this->data->get('salary');

        if ($salary) {
            if (\is_array($salary) && \count($salary) > 1) {
                $value = ['minValue' => trim((string) $salary[0]), 'maxValue' => trim((string) $salary[1])];
            } else {
                $single = \is_array($salary) ? ($salary[0] ?? null) : $salary;
                $value  = ['value' => $single];
            }

            $json['baseSalary'] = [
                '@type'    => 'MonetaryAmount',
                'currency' => $this->data->get('currency'),
                'value'    => array_merge(
                    ['@type' => 'QuantitativeValue', 'unitText' => $this->data->get('salary_unit')],
                    $value
                ),
            ];
        }

        return $json;
    }

    private function contentTypeService(): array
    {
        $content = [
            '@type'       => 'Service',
            'name'        => $this->data->get('title'),
            'serviceType' => $this->data->get('title'),
            'description' => $this->data->get('description'),
            'image'       => $this->data->get('image'),
            'url'         => $this->data->get('url'),
            'provider'    => [
                '@type'     => $this->data->get('provider_type', 'Organization'),
                'name'      => $this->data->get('provider_name'),
                'image'     => $this->data->get('provider_image'),
                'telephone' => $this->data->get('phone'),
                'address'   => $this->getPostalAddress(),
            ],
        ];

        if ((float) $this->data->get('offerPrice') > 0) {
            $content['offers'] = [
                '@type'         => 'Offer',
                'priceCurrency' => $this->data->get('currency', 'BRL'),
                'price'         => $this->data->get('offerPrice'),
            ];
        }

        return $content;
    }

    private function contentTypeVideo(): ?array
    {
        if (!$this->data->get('contentUrl') && !$this->data->get('embedUrl')) {
            return null;
        }

        return [
            '@type'        => 'VideoObject',
            'name'         => $this->data->get('name'),
            'description'  => $this->data->get('description'),
            'thumbnailUrl' => $this->data->get('thumbnailUrl'),
            'uploadDate'   => $this->data->get('uploadDate'),
            'contentUrl'   => $this->data->get('contentUrl'),
            'embedUrl'     => $this->data->get('embedUrl'),
            'transcript'   => $this->data->get('transcript'),
        ];
    }

    private function contentTypeFactCheck(): array
    {
        $content = [
            '@type'         => 'ClaimReview',
            'url'           => $this->data->get('factcheckURL'),
            'itemReviewed'  => [
                '@type'         => 'CreativeWork',
                'author'        => [
                    '@type'  => $this->data->get('claimAuthorType', 'Organization'),
                    'name'   => $this->data->get('claimAuthorName'),
                    'sameAs' => $this->data->get('claimURL'),
                ],
                'datePublished' => $this->data->get('claimDatePublished'),
            ],
            'claimReviewed' => $this->data->get('title'),
            'author'        => [
                '@type' => 'Organization',
                'name'  => $this->data->get('sitename'),
            ],
            'reviewRating'  => [
                '@type'         => 'Rating',
                'ratingValue'   => $this->data->get('factcheckRating'),
                'bestRating'    => $this->data->get('bestFactcheckRating'),
                'worstRating'   => $this->data->get('worstFactcheckRating'),
                'alternateName' => $this->data->get('alternateName'),
            ],
        ];

        return $this->addDate($content);
    }

    private function contentTypeCustom_Code(): string
    {
        return (string) $this->data->get('custom_code', '');
    }

    /* ===================================================================
     *  Esquemas globais (usados pelo plugin de sistema)
     * =================================================================== */

    private function contentTypeQAPage(): ?array
    {
        $question = trim((string) $this->data->get('question'));

        if ($question === '') {
            return null;
        }

        $mainEntity = [
            '@type' => 'Question',
            'name'  => $question,
            'text'  => $this->data->get('question_detail'),
        ];

        $accepted = trim((string) $this->data->get('accepted_answer'));

        if ($accepted !== '') {
            $mainEntity['acceptedAnswer'] = [
                '@type'       => 'Answer',
                'text'        => $accepted,
                'upvoteCount' => $this->data->get('accepted_answer_upvotes'),
                'url'         => $this->data->get('accepted_answer_url'),
            ];
        }

        $suggested = [];

        foreach ((array) $this->data->get('suggested_answers') as $ans) {
            $ans  = (array) $ans;
            $text = trim((string) ($ans['text'] ?? ''));

            if ($text !== '') {
                $suggested[] = [
                    '@type'       => 'Answer',
                    'text'        => $text,
                    'upvoteCount' => $ans['upvotes'] ?? null,
                ];
            }
        }

        if ($suggested !== []) {
            $mainEntity['suggestedAnswer'] = $suggested;
        }

        $mainEntity['answerCount'] = ($accepted !== '' ? 1 : 0) + \count($suggested);

        return ['@type' => 'QAPage', 'mainEntity' => $mainEntity];
    }

    private function contentTypeSoftwareApplication(): array
    {
        $content = [
            '@type'               => $this->data->get('type', 'SoftwareApplication'),
            'name'                => $this->data->get('name'),
            'operatingSystem'     => $this->data->get('operatingSystem'),
            'applicationCategory' => $this->data->get('applicationCategory'),
        ];

        $price = $this->data->get('price');

        if ($price !== null && $price !== '') {
            $content['offers'] = [
                '@type'         => 'Offer',
                'price'         => $price,
                'priceCurrency' => $this->data->get('priceCurrency'),
            ];
        }

        $this->addRating($content);

        return $content;
    }

    private function contentTypeWebsite(): array
    {
        $content = ['@type' => 'WebSite', 'url' => $this->data->get('site_url')];

        if ($this->data->get('site_name_enabled')) {
            $content['name']          = $this->data->get('site_name');
            $content['alternateName'] = $this->data->get('site_name_alt');
        }

        if ($search = $this->data->get('site_links_search')) {
            $content['potentialAction'] = [
                '@type'       => 'SearchAction',
                'target'      => $search,
                'query-input' => 'required name=search_term',
            ];
        }

        return $content;
    }

    private function contentTypeLogo(): array
    {
        return [
            '@type' => 'Organization',
            'url'   => $this->data->get('url'),
            'logo'  => $this->data->get('logo'),
        ];
    }

    private function contentTypeSocialProfiles(): array
    {
        return [
            '@type'  => $this->data->get('type', 'Organization'),
            'name'   => $this->data->get('sitename'),
            'url'    => $this->data->get('siteurl'),
            'sameAs' => array_values((array) $this->data->get('links')),
        ];
    }

    private function contentTypeBreadcrumbs(): ?array
    {
        $crumbs = $this->data->get('crumbs');

        if (!\is_array($crumbs)) {
            return null;
        }

        $items = [];

        foreach ($crumbs as $key => $value) {
            $value   = (object) $value;
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $key + 1,
                'name'     => $value->name ?? '',
                'item'     => $value->link ?? '',
            ];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /* ===================================================================
     *  Auxiliares compartilhados
     * =================================================================== */

    private function getPostalAddress(): array
    {
        return [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $this->data->get('streetAddress'),
            'addressCountry'  => $this->data->get('addressCountry'),
            'addressLocality' => $this->data->get('addressLocality'),
            'addressRegion'   => $this->data->get('addressRegion'),
            'postalCode'      => $this->data->get('postalCode'),
        ];
    }

    private function getOpeningHours(object|array $openingHours): array
    {
        $openingHours = (object) $openingHours;

        // O modo usa a chave "mode" (não "option") para não colidir com a
        // semântica de mapeamento do MappingOptions::prepare().
        $option = (int) ($openingHours->mode ?? $openingHours->option ?? 0);

        if ($option === 0) {
            return [];
        }

        unset($openingHours->mode, $openingHours->option);
        $weekdays = array_map('ucfirst', array_keys((array) $openingHours));

        if ($option === 1) {
            return ['openingHoursSpecification' => [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => $weekdays,
                'opens'     => '00:00',
                'closes'    => '23:59',
            ]];
        }

        $spec = [];

        foreach ($openingHours as $day => $opts) {
            $opts = (object) $opts;
            $day  = ucfirst($day);

            if (empty($opts->enabled)) {
                continue;
            }

            if (empty($opts->start) && empty($opts->end)) {
                $spec[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $day, 'opens' => '00:00', 'closes' => '23:59'];

                continue;
            }

            $spec[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $day, 'opens' => $opts->start, 'closes' => $opts->end];

            if (!empty($opts->start1) && !empty($opts->end1)) {
                $spec[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $day, 'opens' => $opts->start1, 'closes' => $opts->end1];
            }
        }

        return $spec ? ['openingHoursSpecification' => $spec] : [];
    }

    private function addAuthor(array &$content): void
    {
        if ($this->data->get('authorName')) {
            $content['author'] = [
                '@type' => $this->data->get('authorType', 'Person'),
                'name'  => $this->data->get('authorName'),
                'url'   => $this->data->get('authorUrl'),
            ];
        }
    }

    private function addRating(array &$content): void
    {
        if (!$this->data->get('ratingValue') || !$this->data->get('reviewCount')) {
            return;
        }

        $content['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => $this->data->get('ratingValue'),
            'reviewCount' => $this->data->get('reviewCount'),
            'worstRating' => $this->data->get('worstRating', 0),
            'bestRating'  => $this->data->get('bestRating', 5),
        ];
    }

    private function addReview(array &$content): void
    {
        $reviews = $this->data->get('reviews');

        if (!$reviews || !$this->data->get('reviewCount')) {
            return;
        }

        $best  = $this->data->get('bestRating', 5);
        $worst = $this->data->get('worstRating', 0);
        $out   = [];

        foreach ((array) $reviews as $review) {
            $review = (array) $review;

            $out[] = [
                '@type'         => 'Review',
                'author'        => ['@type' => 'Person', 'name' => $review['author'] ?? ''],
                'datePublished' => $review['datePublished'] ?? null,
                'description'   => $review['description'] ?? null,
                'reviewRating'  => [
                    '@type'       => 'Rating',
                    'bestRating'  => $best,
                    'ratingValue' => $review['rating'] ?? null,
                    'worstRating' => $worst,
                ],
            ];
        }

        $content['review'] = $out;
    }

    private function addDate(array $content): array
    {
        return array_merge($content, [
            'datePublished' => $this->data->get('datePublished'),
            'dateCreated'   => $this->data->get('dateCreated'),
            'dateModified'  => $this->data->get('dateModified'),
        ]);
    }

    /**
     * Normaliza o campo sameAs (string, lista simples ou repetidor name=>valor).
     *
     * @return string[]
     */
    private function normalizeSameAs(mixed $sameAs): array
    {
        $sameAs = (array) $sameAs;

        $values = array_values($sameAs);

        if (isset($values[0]) && \is_object($values[0]) && isset($values[0]->name)) {
            return array_column($values, 'name');
        }

        return array_filter(array_map('strval', $values));
    }
}
