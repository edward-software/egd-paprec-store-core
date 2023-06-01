<?php

namespace App\Twig;

use App\Service\ProductManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class QuoteRequestLineCalculatePricesExtension extends AbstractExtension
{

    private $productManager;

    public function __construct(
        ProductManager $productManager
    ) {
        $this->productManager = $productManager;
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('quoteRequestLineCalculatePrices', array($this, 'quoteRequestLineCalculatePrices')),
        );
    }

    public function quoteRequestLineCalculatePrices($quoteRequestLine, $fieldName)
    {
        return $this->productManager->calculatePriceByFieldName($quoteRequestLine, $fieldName, true);
    }
}
