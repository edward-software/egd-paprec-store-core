<?php
/**
 * Created by PhpStorm.
 * User: frede
 * Date: 30/11/2018
 * Time: 16:42
 */

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use App\Entity\CustomArea;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomAreaManager
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function get($customArea)
    {
        $id = $customArea;
        if ($customArea instanceof CustomArea) {
            $id = $customArea->getId();
        }
        try {

            $customArea = $this->em->getRepository('App:CustomArea')->find($id);

            if ($customArea === null || $this->isDeleted($customArea)) {
                throw new EntityNotFoundException('customAreaNotFound');
            }

            return $customArea;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérification qu'à ce jour le customArea  n'est pas supprimée
     *
     * @param CustomArea $customArea
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(CustomArea $customArea, $throwException = false)
    {
        $now = new \DateTime();

        if ($customArea->getDeleted() !== null && $customArea->getDeleted() instanceof \DateTime && $customArea->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('customAreaNotFound');
            }
            return true;
        }
        return false;
    }

    /**
     * @param $code
     * @return object|CustomArea|null
     * @throws Exception
     */
    public function getByCodeLocale($code, $locale)
    {
        try {

            $customizableArea = $this->em->getRepository('App:CustomArea')->findOneBy(array(
                'code' => $code,
                'language' => $locale,
                'isDisplayed' => true,
                'deleted' => null
            ));

            if ($customizableArea === null) {
                return null;
            }

            return $customizableArea;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}
