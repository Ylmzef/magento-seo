<?php

namespace Web200\Seo\Block\MicroData;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Review\Model\Review\SummaryFactory;
use Magento\Review\Model\Review\Summary;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;

class CategoryProduct extends Template
{
    protected $serialize;
    protected $layerResolver;
    protected $productCollectionFactory;
    protected $categoryRepository;
    protected $reviewCollectionFactory;
    protected $reviewSummaryFactory;

    public function __construct(
        Context $context,
        Json $serialize,
        Resolver $layerResolver,
        CollectionFactory $productCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        ReviewCollectionFactory $reviewCollectionFactory,
        SummaryFactory $reviewSummaryFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->serialize = $serialize;
        $this->layerResolver = $layerResolver;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->reviewSummaryFactory = $reviewSummaryFactory;
    }

    public function display(): bool
    {
        $layer = $this->layerResolver->get();
        $category = $layer->getCurrentCategory();
        return $category !== null;
    }

    public function renderJson(): string
    {
        $layer = $this->layerResolver->get();
        $category = $layer->getCurrentCategory();

        if (!$category) {
            return '';
        }

        // Get current page number from the request
        $currentPage = (int)$this->getRequest()->getParam('p', 1); // Default to page 1 if 'p' is not set

        // Set page size and current page
        $pageSize = 12; // Number of products per page
        $productCollection = $this->productCollectionFactory->create()
            ->addCategoryFilter($category)
            ->addAttributeToSelect(['name', 'price', 'image', 'media_gallery', 'url_key', 'short_description', 'sku'])
            ->setPageSize($pageSize)
            ->setCurPage($currentPage);

        $products = [];
        $position = ($currentPage - 1) * $pageSize + 1; // Calculate the starting position for the current page
        foreach ($productCollection as $product) {
            $productData = [
                '@type' => 'ListItem',
                'position' => $position,
                'item' => [
                    "@type" => "Product",
                    'name' => $product->getName(),
                    'image' => $this->getImageUrls($product),
                ]
            ];

            $offer = [
                '@type' => 'Offer',
                'price' => round($product->getFinalPrice(), 2),
                'priceCurrency' => $this->_storeManager->getStore()->getCurrentCurrencyCode(),
            ];

            // Check if the product is configurable
            if ($product->getTypeId() === 'configurable') {
                $childProducts = $product->getTypeInstance()->getUsedProducts($product);
                $prices = [];
                foreach ($childProducts as $childProduct) {
                    $prices[] = $childProduct->getFinalPrice();
                }
                $aggregateOffer = [
                    '@type' => 'AggregateOffer',
                    'lowPrice' => min($prices),
                    'highPrice' => max($prices),
                    'priceCurrency' => $this->_storeManager->getStore()->getCurrentCurrencyCode(),
                ];
                $productData['item']['offers'] = $aggregateOffer;
            } else {
                $productData['item']['offers'] = [$offer];
            }

            // Aggregate Rating
            $totalRatingValue = 0;
            $reviewCount = 0;
            $reviewCollection = $this->reviewCollectionFactory->create()
                ->addEntityFilter('product', $product->getId())
                ->load();
            $reviewCollection->load()->addRateVotes();
            foreach ($reviewCollection as $review) {
                $ratingValue = 0;
                $ratingSteps = 5;
                foreach ($review->getRatingVotes() as $vote) {
                    $rating = $vote->getPercent();
                    $ratingValue += is_numeric($rating) ? floor($rating / 100 * $ratingSteps) : 0;
                }
                $totalRatingValue += $ratingValue;
                $reviewCount++;
            }

            if ($product->getTypeId() === 'configurable' && $reviewCount > 0) {
                $productData['item']['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'bestRating' => '5',
                    'worstRating' => '1',
                    'ratingValue' => round($totalRatingValue / $reviewCount, 1),
                    'reviewCount' => $reviewCount,
                ];
            }

            $productData['item']['url'] = $product->getProductUrl();

            $products[] = $productData;
            $position++;
        }

        $final = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $products,
        ];

        return $this->serialize->serialize($final);
    }

    /**
     * Get URLs of all images of the product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string[]
     */
    private function getImageUrls($product): array
    {
        $images = [];
        $loadedProduct = $product->load($product->getId());
        $mediaGalleryImages = $loadedProduct->getMediaGalleryImages();
        if ($mediaGalleryImages) {
            foreach ($mediaGalleryImages as $image) {
                $images[] = $image->getUrl();
            }
        }

        return $images;
    }
}
