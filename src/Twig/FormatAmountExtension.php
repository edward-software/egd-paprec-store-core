<?php
declare(strict_types=1);

namespace App\Twig;

use App\Service\NumberManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FormatAmountExtension extends AbstractExtension
{

    private $numberManager;
    
    
    public function __construct(NumberManager $numberManager)
    {
        $this->numberManager = $numberManager;
    }
    
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('formatAmount', [$this, 'formatAmount']),
        ];
    }
    
    /**
     * @param $amount
     * @param $locale
     * @param null $currency
     * @param null $type
     *
     * @return string
     */
    public function formatAmount($amount, $locale, $currency = null, $type = null)
    {
        
        if ($type === 'PERCENTAGE') {
            $currency = 'PERCENTAGE';
        }
        
        if ($type === 'FORMAT15') {
            return $this->numberManager->formatAmount15($amount, $locale);
        }

        if ($type === 'DEC2') {
            $amount = str_replace(',', '.', $amount);
            return  number_format((float)$amount, 2);
        }

        return $this->numberManager->formatAmount($amount, $currency, $locale);
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return 'formatAmount';
    }
}
