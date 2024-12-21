<?php

declare(strict_types=1);

namespace Web200\Seo\Block\MicroData;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Header\Logo as HtmlLogo;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Variable\Model\Variable;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\Country;

/**
 * Class Organization
 *
 * @package   Web200\Seo\Block\MicroData
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Organization extends Template
{
    /**
     * Json
     *
     * @var Json $serialize
     */
    protected $serialize;

    /**
     * Store manager interface
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Scope configuration interface
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var Variable
     */
    private Variable $variable;

    /**
     * @var Region
     */
    private Region $region;

    /**
     * @var Country
     */
    private Country $country;

    /**
     * Organization constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Json                  $serialize
     * @param Context               $context
     * @param ScopeConfigInterface  $scopeConfig
     * @param Variable              $variable
     * @param Region                $region
     * @param Country               $country
     * @param mixed[]               $data
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Json $serialize,
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Variable $variable,
        Region $region,
        Country $country,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->serialize    = $serialize;
        $this->storeManager = $storeManager;
        $this->scopeConfig  = $scopeConfig;
        $this->variable     = $variable;
        $this->region       = $region;
        $this->country      = $country;
    }

    /**
     * @inheritdoc
     */
    public function getCacheLifetime()
    {
        return 86400;
    }

    /**
     * @inheritdoc
     */
    public function getCacheKeyInfo()
    {
        return [
            $this->_storeManager->getStore()->getId(),
            'microdata_logo',
        ];
    }

    /**
     * Display
     *
     * @return bool
     * @throws LocalizedException
     */
    public function display(): bool
    {
        return (bool)$this->getLayout()->getBlock('logo');
    }

    /**
     * Retrieve configuration value
     *
     * @param string $path
     * @param string $scopeType
     * @param int|null $storeId
     * @return string|null
     */
    private function getConfigValue(string $path, string $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE, ?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue($path, $scopeType, $storeId);
    }

    /**
     * Get Custom Variable Value
     *
     * @param string $code
     * @return string|null
     */
    public function getCustomVariableValue(string $code, string $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE, ?int $storeId = null): ?string
    {
        try {
            $variable = $this->variable->loadByCode($code);
            $plainValue = $variable->getPlainValue();
            $htmlValue = $variable->getHtmlValue();
            if (empty($plainValue) && empty($htmlValue)) {
                return null;
            }
            return $plainValue ?: $htmlValue;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get region name by region ID
     *
     * @param string|null $regionId
     * @return string
     */
    private function getRegionName(?string $regionId): string
    {
        if (!empty($regionId)) {
            $region = $this->region->load($regionId);
            return $region->getName() ?: '';
        }
        return '';
    }

    /**
     * Get country name by country ID
     *
     * @param string|null $countryId
     * @return string
     */
    private function getCountryName(?string $countryId): string
    {
        if (!empty($countryId)) {
            $country = $this->country->loadByCode($countryId);
            return $country->getName() ?: '';
        }
        return '';
    }

    /**
     * Render Json
     *
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function renderJson(): string
    {
        /** @var HtmlLogo $logoBlock */
        $logoBlock = $this->getLayout()->getBlock('logo');
        if ($logoBlock) {
            /** @var string $websiteUrl */
            $websiteUrl = $this->storeManager->getStore()->getBaseUrl();
            /** @var string $storeName */
            $storeName = $this->getConfigValue('general/store_information/name');
            $alternateName = $this->getCustomVariableValue('structured-data-alternateName');
            $legalName = $this->getCustomVariableValue('structured-data-legalName');
            $description = $this->getCustomVariableValue('structured-data-description');
            $image = $this->getCustomVariableValue('structured-data-image');
            $senderEmail = $this->getConfigValue('trans_email/ident_general/email');
            $storeTelephone = $this->getConfigValue('general/store_information/phone');
            $vatId = $this->getConfigValue('general/store_information/merchant_vat_number');
            $taxId = $this->getConfigValue('general/store_information/merchant_vat_number');
            $iso6523Code = $this->getCustomVariableValue('structured-data-iso6523Code');
            $duns = $this->getCustomVariableValue('structured-data-duns');
            $globalLocationNumber = $this->getCustomVariableValue('structured-data-globalLocationNumber');
            $foundingDate = $this->getCustomVariableValue('structured-data-foundingDate');
            $allowCountries = $this->getConfigValue('general/country/allow');
            $allowedCountryNames = [];
            if (!empty($allowCountries)) {
                $countryIds = explode(',', $allowCountries);
                foreach ($countryIds as $id) {
                    $countryName = $this->getCountryName($id);
                    if (!empty($countryName)) {
                        $allowedCountryNames[] = $countryName;
                    }
                }
            }
            $availableLanguage = $this->getConfigValue('general/locale/code');
            $streetAddressLine1 = $this->getConfigValue('general/store_information/street_line1');
            $streetAddressLine2 = $this->getConfigValue('general/store_information/street_line2');
            $streetAddress = trim(($streetAddressLine1 ?: '') . ' ' . ($streetAddressLine2 ?: ''));
            $addressLocality = $this->getConfigValue('general/store_information/city');
            $postalCode = $this->getConfigValue('general/store_information/postcode');
            $regionId = $this->getConfigValue('general/store_information/region_id');
            $countryId = $this->getConfigValue('general/store_information/country_id');

            $addressRegion = $this->getRegionName($regionId);
            $addressCountry = $this->getCountryName($countryId);

            $employeesMinValue = $this->getCustomVariableValue('structured-data-fnumberOfEmployees-minValue');
            $employeesMaxValue = $this->getCustomVariableValue('structured-data-fnumberOfEmployees-maxValue');
            $sameAs = $this->getCustomVariableValue('structured-data-sameAs');
            $aggregateRatingUrl = $this->getCustomVariableValue('structured-data-aggregateRating-url');


            /** @var string[] $final */
            $final = [
                '@context' => 'https://schema.org',
                '@type' => 'OnlineStore',
            ];

            if (!empty($storeName)) {
                $final["name"] = $storeName;
            }
            if (!empty($alternateName)) {
                $final["alternateName"] = $alternateName;
            }
            if (!empty($legalName)) {
                $final["legalName"] = $legalName;
            }
            if (!empty($description)) {
                $final["description"] = $description;
            }
            if (!empty($websiteUrl)) {
                $final['url'] = $websiteUrl;
            }
            if (!empty($logoBlock->getLogoSrc())) {
                $final['logo'] = $logoBlock->getLogoSrc();
            }
            if (!empty($image)) {
                $final["image"] = $image;
            }
            if (!empty($senderEmail)) {
                $final['email'] = $senderEmail;
            }
            if (!empty($storeTelephone)) {
                $final['telephone'] = $storeTelephone;
            }
            if (!empty($vatId)) {
                $final['vatID'] = $vatId;
            }
            if (!empty($taxId)) {
                $final['taxID'] = $taxId;
            }
            if (!empty($iso6523Code)) {
                $final["iso6523Code"] = $iso6523Code;
            }
            if (!empty($duns)) {
                $final["duns"] = $duns;
            }
            if (!empty($globalLocationNumber)) {
                $final["globalLocationNumber"] = $globalLocationNumber;
            }
            if (!empty($foundingDate)) {
                $final["foundingDate"] = $foundingDate;
            }

            $final['contactPoint']['@type'] = 'ContactPoint';
            $final['contactPoint']['contactType'] = 'customer service';
            if (!empty($storeTelephone)) {
                $final['contactPoint']['telephone'] = $storeTelephone;
            }
            if (!empty($senderEmail)) {
                $final['contactPoint']['email'] = $senderEmail;
            }
            if (!empty($allowCountries)) {
                $final['contactPoint']['areaServed'] = $allowedCountryNames ?: [];
            }
            if (!empty($availableLanguage)) {
                $final['contactPoint']['availableLanguage'] = $availableLanguage;
            }

            $final['address']['@type'] = 'PostalAddress';
            if (!empty($streetAddress)) {
                $final['address']['streetAddress'] = $streetAddress;
            }
            if (!empty($addressLocality)) {
                $final['address']['addressLocality'] = $addressLocality;
            }
            if (!empty($postalCode)) {
                $final['address']['postalCode'] = $postalCode;
            }
            if (!empty($addressRegion)) {
                $final['address']['addressRegion'] = $addressRegion;
            }
            if (!empty($addressCountry)) {
                $final['address']['addressCountry'] = $addressCountry;
            }

            $final['areaServed']['@type'] = 'Country';
            if (!empty($allowCountries)) {
                $final['areaServed']['name'] = $allowedCountryNames ?: [];
            }

            $final['numberOfEmployees']['@type'] = 'QuantitativeValue';
            if (!empty($employeesMinValue)) {
                $final['numberOfEmployees']['minValue'] = $employeesMinValue;
            }
            if (!empty($employeesMaxValue)) {
                $final['numberOfEmployees']['maxValue'] = $employeesMaxValue;
            }

            if (!empty($sameAs)) {
                $sameAsArray = array_filter(array_map('trim', explode(',', $sameAs)));
                $final["sameAs"] = $sameAsArray;
            }

            $final["aggregateRating"] = [
                "@type" => "AggregateRating",
                "bestRating" => "5",
                "worstRating" => "1",
                "ratingValue" => "4.5",
                "reviewCount" => "4010",
            ];

            if (!empty($aggregateRatingUrl)) {
                $final['aggregateRating']['url'] = $aggregateRatingUrl;
            }


            return $this->serialize->serialize($final);
        }

        return '';
    }
}
