<?php
/**
 * Created by PhpStorm.
 * User: fle
 * Date: 06/05/2021
 * Time: 11:38
 */

namespace App\Service;


use App\Entity\PostalCode;
use App\Entity\MissionSheetProduct;
use App\Entity\MissionSheetProductLabel;
use App\Entity\QuoteRequest;
use App\Entity\QuoteRequestLine;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MissionSheetProductManager
{

    private $em;
    private $container;
    private $numberManager;

    public function __construct(
        EntityManagerInterface $em,
        NumberManager $numberManager,
        ContainerInterface $container
    ) {
        $this->em = $em;
        $this->numberManager = $numberManager;
        $this->container = $container;
    }

    public function get($missionSheetProduct)
    {
        $id = $missionSheetProduct;
        if ($missionSheetProduct instanceof MissionSheetProduct) {
            $id = $missionSheetProduct->getId();
        }
        try {

            $missionSheetProduct = $this->em->getRepository('App:MissionSheetProduct')->find($id);

            /**
             * Vérification que le produit existe ou ne soit pas supprimé
             */
            if ($missionSheetProduct === null || $this->isDeleted($missionSheetProduct)) {
                throw new EntityNotFoundException('missionSheetProductNotFound');
            }


            return $missionSheetProduct;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérifie qu'à ce jour, le produit ce soit pas supprimé
     *
     * @param MissionSheetProduct $missionSheetProduct
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(MissionSheetProduct $missionSheetProduct, $throwException = false)
    {
        $now = new \DateTime();

        if ($missionSheetProduct->getDeleted() !== null && $missionSheetProduct->getDeleted() instanceof \DateTime && $missionSheetProduct->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('missionSheetProductNotFound');
            }

            return true;

        }
        return false;
    }

    /**
     * On passe en paramètre les options Category et PostalCode, retourne les produits qui appartiennent à la catégorie
     * et qui sont disponibles dans le postalCode
     * @param $options
     * @return array
     * @throws Exception
     */
    public function findAvailables($options)
    {
        $categoryId = $options['category'];
        $postalCode = $options['postalCode'];

        try {
            $query = $this->em
                ->getRepository(MissionSheetProduct::class)
                ->createQueryBuilder('p')
                ->innerJoin('App:MissionSheetProductCategory', 'pc', \Doctrine\ORM\Query\Expr\Join::WITH,
                    'p.id = pc.missionSheetProduct')
                ->where('pc.category = :category')
                ->orderBy('pc.position', 'ASC')
                ->setParameter("category", $categoryId);

            $missionSheetProducts = $query->getQuery()->getResult();


            $missionSheetProductsPostalCodeMatch = array();


            // On parcourt tous les produits DI pour récupérer ceux  qui possèdent le postalCode
            foreach ($missionSheetProducts as $missionSheetProduct) {
                $postalCodes = str_replace(' ', '', $missionSheetProduct->getAvailablePostalCodes());
                $postalCodesArray = explode(',', $postalCodes);
                foreach ($postalCodesArray as $pC) {
                    //on teste juste les deux premiers caractères pour avoir le code du département
                    if (substr($pC, 0, 2) == substr($postalCode, 0, 2)) {
                        $missionSheetProductsPostalCodeMatch[] = $missionSheetProduct;
                    }
                }
            }

            return $missionSheetProductsPostalCodeMatch;

        } catch (ORMException $e) {
            throw new Exception('unableToGetMissionSheetProducts', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Fonction calculant le prix d'un produit en fonction de sa quantité, du code postal
     * Utilisée dans le calcul du montant d'un Cart et dans le calcul du montant d'une ligne MissionSheetProductQuoteLine
     * Si le calcul est modifiée, il faudra donc le modifier uniquement ici
     *
     * @param QuoteRequestLine $quoteRequestLine
     * @return float|int
     */
    public function calculatePrice(QuoteRequestLine $quoteRequestLine)
    {
        $numberManager = $this->numberManager;

        $frequencyIntervalValue = 1;
        if (strtoupper($quoteRequestLine->getFrequency()) === 'REGULAR') {
            $monthlyCoefficientValues = $this->container->getParameter('paprec.frequency_interval.monthly_coefficients');
            $frequencyInterval = strtolower($quoteRequestLine->getFrequencyInterval());
            if (array_key_exists($frequencyInterval, $monthlyCoefficientValues)) {
                $frequencyIntervalValue = $monthlyCoefficientValues[$frequencyInterval] * $quoteRequestLine->getFrequencyTimes();
            }
        }

        $quantity = $quoteRequestLine->getQuantity();

        /**
         * Nombre de Produit * PU Location du Produit * Coefficient Location du CP de l’adresse à collecter
         */
        $editableRentalUnitPrice = 0;
        if ($quoteRequestLine->getEditableRentalUnitPrice() > 0) {
            $editableRentalUnitPrice = $quantity * $numberManager->denormalize($quoteRequestLine->getEditableRentalUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getRentalRate());
        }

        /**
         * Transport : Nombre de passage par mois (fonction de la fréquence) * Cout Transport * Coefficient Transport précisé dans le champ produit du CP
         */
        $editableTransportUnitPrice = 0;
        if ($quoteRequestLine->getEditableTransportUnitPrice() > 0) {
            $editableTransportUnitPrice = $frequencyIntervalValue * $numberManager->denormalize($quoteRequestLine->getEditableTransportUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTransportRate());
        }

        /**
         * Traitement :  (Nombre de Produit) * (PU Traitement du Produit * Coefficient Traitement du CP de l’adresse à collecter)
         */
        $editableTreatmentUnitPrice = 0;
        if ($quoteRequestLine->getEditableTreatmentUnitPrice() !== null) {
            $editableTreatmentUnitPrice = $quantity * $numberManager->denormalize($quoteRequestLine->getEditableTreatmentUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTreatmentRate());
        }

        /**
         * Matériel Additionnel :  (Nombre de Produit -1) * (PU Matériel Additionnel du Produit * Coefficient Matériel Additionnel du CP de l’adresse à collecter)
         */
        $editableTraceabilityUnitPrice = 0;
        if ($quoteRequestLine->getEditableTraceabilityUnitPrice() > 0) {
            $editableTraceabilityUnitPrice = ($quantity - 1) * $numberManager->denormalize($quoteRequestLine->getEditableTraceabilityUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTraceabilityRate());
        }

        return $editableRentalUnitPrice + $editableTransportUnitPrice + $editableTreatmentUnitPrice + $editableTraceabilityUnitPrice;
    }

    /**
     * Renvoi un prix fixe en fonction des conditions d'accès
     *
     * @param QuoteRequestLine $quoteRequestLine
     * @return int|mixed
     */
    public function getAccesPrice(QuoteRequest $quoteRequest)
    {
        if ($quoteRequest && $quoteRequest->getAccess()) {
            $prices = array();
            foreach ($this->container->getParameter('paprec_quote_access_price') as $p => $value) {
                $prices[$p] = $value;
            }
            switch ($quoteRequest->getAccess()) {
                case 'STAIRS':
                    return $prices['STAIRS'];
                    break;
                case 'ELEVATOR':
                    return $prices['ELEVATOR'];
                    break;
                case 'GROUND':
                    return $prices['GROUND'];
                    break;
                default:
                    return 0;
            }
        }
        return 0;
    }

    public function getMissionSheetProductLabels($missionSheetProduct)
    {
        $id = $missionSheetProduct;
        if ($missionSheetProduct instanceof MissionSheetProduct) {
            $id = $missionSheetProduct->getId();
        }
        try {

            $missionSheetProductLabels = $this->em->getRepository('App:MissionSheetProductLabel')->findBy(array(
                    'missionSheetProduct' => $missionSheetProduct,
                    'deleted' => null
                )
            );

            /**
             * Vérification que le produit existe ou ne soit pas supprimé
             */
            if (empty($missionSheetProductLabels)) {
                throw new EntityNotFoundException('missionSheetProductLabelsNotFound');
            }


            return $missionSheetProductLabels;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

    }

    public function getMissionSheetProductLabelByMissionSheetProductAndLocale(MissionSheetProduct $missionSheetProduct, $language)
    {

        $id = $missionSheetProduct;
        if ($missionSheetProduct instanceof MissionSheetProduct) {
            $id = $missionSheetProduct->getId();
        }
        try {

            $missionSheetProduct = $this->em->getRepository('App:MissionSheetProduct')->find($id);

            /**
             * Vérification que le produit existe ou ne soit pas supprimé
             */
            if ($missionSheetProduct === null || $this->isDeleted($missionSheetProduct)) {
                throw new EntityNotFoundException('missionSheetProductNotFound');
            }

            $missionSheetProductLabel = $this->em->getRepository('App:MissionSheetProductLabel')->findOneBy(array(
                'missionSheetProduct' => $missionSheetProduct,
                'language' => $language
            ));

            /**
             * Si il y'en a pas dans la langue de la locale, on en prend un au hasard
             */
            if ($missionSheetProductLabel === null || $this->IsDeletedMissionSheetProductLabel($missionSheetProductLabel)) {
                $missionSheetProductLabel = $this->em->getRepository('App:MissionSheetProductLabel')->findOneBy(array(
                    'missionSheetProduct' => $missionSheetProduct
                ));

                if ($missionSheetProductLabel === null || $this->IsDeletedMissionSheetProductLabel($missionSheetProductLabel)) {
                    throw new EntityNotFoundException('missionSheetProductLabelNotFound');
                }
            }


            return $missionSheetProductLabel;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérifie qu'à ce jour, le libellé produit ne soit pas supprimé
     *
     * @param MissionSheetProductLabel $missionSheetProductLabel
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeletedMissionSheetProductLabel(MissionSheetProductLabel $missionSheetProductLabel, $throwException = false)
    {
        $now = new \DateTime();

        if ($missionSheetProductLabel->getDeleted() !== null && $missionSheetProductLabel->getDeleted() instanceof \DateTime && $missionSheetProductLabel->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('missionSheetProductLabelNotFound');
            }

            return true;

        }
        return false;
    }

    public function getAvailableMissionSheetProducts(string $catalog)
    {
        $queryBuilder = $this->em->getRepository(MissionSheetProduct::class)->createQueryBuilder('p');

        $queryBuilder->select(array('p'))
            ->where('p.deleted IS NULL')
            ->andWhere('p.catalog = :catalog')
            ->andWhere('p.isEnabled = 1')
            ->orderBy('p.position')
            ->setParameter('catalog', $catalog);

        return $queryBuilder->getQuery()->getResult();
    }


}
