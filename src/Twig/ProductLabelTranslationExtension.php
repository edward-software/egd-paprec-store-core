<?php

namespace App\Twig;


use App\Entity\Product;
use App\Service\ProductManager;
use Symfony\Component\DependencyInjection\Container;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ProductLabelTranslationExtension extends AbstractExtension
{

    private $productManager;

    public function __construct(
        ProductManager $productManager
    )
    {
        $this->productManager = $productManager;
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('productLabelTranslation', array($this, 'productLabelTranslation')),
        );
    }

    public function productLabelTranslation($product, $lang, $attr = null)
    {
        $returnLabel = '';
        try {
            $product = $this->productManager->get($product);
            switch ($attr) {
                case 'shortDescription':
                    $returnLabel = $this->productManager->getProductLabelByProductAndLocale($product, $lang)->getShortDescription();
                    break;
                case 'version':
                    $returnLabel = $this->productManager->getProductLabelByProductAndLocale($product, $lang)->getVersion();
                    break;
                case 'lockType':
                    $returnLabel = $this->productManager->getProductLabelByProductAndLocale($product, $lang)->getLockType();
                    break;
                default:
                    $returnLabel = $this->productManager->getProductLabelByProductAndLocale($product, $lang)->getName();
            }
        } catch (\Exception $e) {
        }

        return $returnLabel;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'formatId';
    }
}
