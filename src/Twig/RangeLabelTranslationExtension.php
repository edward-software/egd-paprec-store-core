<?php

namespace App\Twig;


use App\Entity\Range;
use App\Service\RangeManager;
use Symfony\Component\DependencyInjection\Container;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RangeLabelTranslationExtension extends AbstractExtension
{

    private $rangeManager;

    public function __construct(
        RangeManager $rangeManager
    )
    {
        $this->rangeManager = $rangeManager;
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('rangeLabelTranslation', array($this, 'rangeLabelTranslation')),
        );
    }

    public function rangeLabelTranslation($range, $lang, $attr = null)
    {
        $returnLabel = '';
        try {
            $range = $this->rangeManager->get($range);
            switch ($attr) {
                case 'shortDescription':
                    $returnLabel = $this->rangeManager->getRangeLabelByRangeAndLocale($range, $lang)->getShortDescription();
                    break;
                default:
                    $returnLabel = $this->rangeManager->getRangeLabelByRangeAndLocale($range, $lang)->getName();
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
