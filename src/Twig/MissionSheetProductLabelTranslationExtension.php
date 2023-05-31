<?php

namespace App\Twig;


use App\Entity\MissionSheetProduct;
use App\Service\MissionSheetProductManager;
use Symfony\Component\DependencyInjection\Container;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MissionSheetProductLabelTranslationExtension extends AbstractExtension
{

    private $missionSheetProductManager;

    public function __construct(
        MissionSheetProductManager $missionSheetProductManager
    )
    {
        $this->missionSheetProductManager = $missionSheetProductManager;
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('missionSheetProductLabelTranslation', array($this, 'missionSheetProductLabelTranslation')),
        );
    }

    public function missionSheetProductLabelTranslation($missionSheetProduct, $lang, $attr = null)
    {
        $returnLabel = '';
        try {
            $missionSheetProduct = $this->missionSheetProductManager->get($missionSheetProduct);
            switch ($attr) {
                case 'shortDescription':
                    $returnLabel = $this->missionSheetProductManager->getMissionSheetProductLabelByMissionSheetProductAndLocale($missionSheetProduct, $lang)->getShortDescription();
                    break;
                case 'version':
                    $returnLabel = $this->missionSheetProductManager->getMissionSheetProductLabelByMissionSheetProductAndLocale($missionSheetProduct, $lang)->getVersion();
                    break;
                case 'lockType':
                    $returnLabel = $this->missionSheetProductManager->getMissionSheetProductLabelByMissionSheetProductAndLocale($missionSheetProduct, $lang)->getLockType();
                    break;
                default:
                    $returnLabel = $this->missionSheetProductManager->getMissionSheetProductLabelByMissionSheetProductAndLocale($missionSheetProduct, $lang)->getName();
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
