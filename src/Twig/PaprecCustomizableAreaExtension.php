<?php

namespace App\Twig;


use App\Entity\CustomArea;
use App\Service\CustomAreaManager;
use Exception;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PaprecCustomizableAreaExtension extends AbstractExtension
{

    private $customAreaManager;

    public function __construct(
        CustomAreaManager $customAreaManager
    )
    {
        $this->customAreaManager = $customAreaManager;
    }

    /**
     * @return array|\Twig_Function[]
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('paprec_customizable_area', array($this, 'customizableArea')),
        );
    }

    /**
     * @param $code
     * @return CustomArea|object
     * @throws Exception
     */
    public function customizableArea($code, $locale)
    {
        try {
            $locale = strtoupper($locale);

            return $this->customAreaManager->getByCodeLocale($code, $locale);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }


    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'paprec_customizable_area';
    }
}
