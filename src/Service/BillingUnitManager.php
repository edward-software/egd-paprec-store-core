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
use App\Entity\BillingUnit;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BillingUnitManager
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function get($billingUnit)
    {
        $id = $billingUnit;
        if ($billingUnit instanceof BillingUnit) {
            $id = $billingUnit->getId();
        }
        try {

            $billingUnit = $this->em->getRepository('App:BillingUnit')->find($id);

            if ($billingUnit === null || $this->isDeleted($billingUnit)) {
                throw new EntityNotFoundException('billingUnitNotFound');
            }

            return $billingUnit;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérification qu'à ce jour le billingUnit  n'est pas supprimée
     *
     * @param BillingUnit $billingUnit
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(BillingUnit $billingUnit, $throwException = false)
    {
        $now = new \DateTime();

        if ($billingUnit->getDeleted() !== null && $billingUnit->getDeleted() instanceof \DateTime && $billingUnit->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('billingUnitNotFound');
            }
            return true;
        }
        return false;
    }

    /**
     * @param $code
     * @return object|BillingUnit|null
     * @throws Exception
     */
    public function getByCodeLocale($code, $locale)
    {
        try {

            $customizableArea = $this->em->getRepository('App:BillingUnit')->findOneBy(array(
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
