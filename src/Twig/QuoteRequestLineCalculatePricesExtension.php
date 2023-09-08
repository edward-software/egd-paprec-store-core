<?php

namespace App\Twig;

use App\Service\NumberManager;
use App\Service\ProductManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class QuoteRequestLineCalculatePricesExtension extends AbstractExtension
{

    private $productManager;
    private $numberManager;

    public function __construct(
        ProductManager $productManager,
        NumberManager $numberManager
    ) {
        $this->productManager = $productManager;
        $this->numberManager = $numberManager;
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('quoteRequestLineCalculatePrices', array($this, 'quoteRequestLineCalculatePrices')),
        );
    }

    public function quoteRequestLineCalculatePrices(
        $quoteRequestLine,
        $fieldName,
        $editableRentalUnitPrice = null,
        $editableTreatmentUnitPrice = null,
        $editableTransportUnitPrice = null,
        $editableTraceabilityUnitPrice = null
    ) {
        if ($editableRentalUnitPrice !== null) {
            $normalizedEditableValue = $this->numberManager->normalize($editableRentalUnitPrice);
            $quoteRequestLine->setEditableRentalUnitPrice($normalizedEditableValue);
        }

        if ($editableTreatmentUnitPrice !== null) {
            $normalizedEditableValue = $this->numberManager->normalize($editableTreatmentUnitPrice);
            $quoteRequestLine->setEditableTreatmentUnitPrice($normalizedEditableValue);
        }

        if ($editableTransportUnitPrice !== null) {
            $normalizedEditableValue = $this->numberManager->normalize($editableTransportUnitPrice);
            $quoteRequestLine->setEditableTransportUnitPrice($normalizedEditableValue);
        }

        if ($editableTraceabilityUnitPrice !== null) {
            $normalizedEditableValue = $this->numberManager->normalize($editableTraceabilityUnitPrice);
            $quoteRequestLine->setEditableTraceabilityUnitPrice($normalizedEditableValue);
        }


        return $this->productManager->calculatePriceByFieldName($quoteRequestLine, $fieldName, true);
    }
}
