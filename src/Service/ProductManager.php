<?php
/**
 * Created by PhpStorm.
 * User: fle
 * Date: 06/05/2021
 * Time: 11:38
 */

namespace App\Service;


use App\Entity\PostalCode;
use App\Entity\Product;
use App\Entity\ProductLabel;
use App\Entity\QuoteRequest;
use App\Entity\QuoteRequestLine;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductManager
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

    public function get($product)
    {
        $id = $product;
        if ($product instanceof Product) {
            $id = $product->getId();
        }
        try {

            $product = $this->em->getRepository('App:Product')->find($id);

            /**
             * Vérification que le produit existe ou ne soit pas supprimé
             */
            if ($product === null || $this->isDeleted($product)) {
                throw new EntityNotFoundException('productNotFound');
            }


            return $product;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérifie qu'à ce jour, le produit ce soit pas supprimé
     *
     * @param Product $product
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(Product $product, $throwException = false)
    {
        $now = new \DateTime();

        if ($product->getDeleted() !== null && $product->getDeleted() instanceof \DateTime && $product->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('productNotFound');
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
                ->getRepository(Product::class)
                ->createQueryBuilder('p')
                ->innerJoin('App:ProductCategory', 'pc', \Doctrine\ORM\Query\Expr\Join::WITH,
                    'p.id = pc.product')
                ->where('pc.category = :category')
                ->orderBy('pc.position', 'ASC')
                ->setParameter("category", $categoryId);

            $products = $query->getQuery()->getResult();


            $productsPostalCodeMatch = array();


            // On parcourt tous les produits DI pour récupérer ceux  qui possèdent le postalCode
            foreach ($products as $product) {
                $postalCodes = str_replace(' ', '', $product->getAvailablePostalCodes());
                $postalCodesArray = explode(',', $postalCodes);
                foreach ($postalCodesArray as $pC) {
                    //on teste juste les deux premiers caractères pour avoir le code du département
                    if (substr($pC, 0, 2) == substr($postalCode, 0, 2)) {
                        $productsPostalCodeMatch[] = $product;
                    }
                }
            }

            return $productsPostalCodeMatch;

        } catch (ORMException $e) {
            throw new Exception('unableToGetProducts', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Fonction calculant le prix d'un produit en fonction de sa quantité, du code postal
     * Utilisée dans le calcul du montant d'un Cart et dans le calcul du montant d'une ligne ProductQuoteLine
     * Si le calcul est modifiée, il faudra donc le modifier uniquement ici
     *
     * @param QuoteRequestLine $quoteRequestLine
     * @return float|int
     */
    public function calculatePrice(QuoteRequestLine $quoteRequestLine)
    {
        return $this->calculatePriceByFieldName($quoteRequestLine, 'totalAmount');
    }

    public function calculatePriceByFieldName($quoteRequestLine, $fieldName, $returnNormalizedNumber = false)
    {
        $numberManager = $this->numberManager;

        $frequencyIntervalValue = $this->calculateFrequencyCoeff($quoteRequestLine);

        $quantity = $quoteRequestLine->getQuantity();

        $product = $quoteRequestLine->getProduct();
        $calculationFormula = $product->getCalculationFormula();

        $result = 0;

        if ($product->getCatalog() === 'MATERIAL') {

            if ($fieldName === 'totalAmount') {

                $result = $quantity * $numberManager->denormalize($product->getMaterialUnitPrice());
            }

        } else {


            if (!$calculationFormula || $calculationFormula === 'REGULAR') {
                if ($fieldName === 'editableRentalUnitPrice') {
                    /**
                     * PU Location du Produit * Coefficient Location du CP de l’adresse à collecter
                     */
                    if ($quoteRequestLine->getEditableRentalUnitPrice() > 0) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableRentalUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getRentalRate());
                    }

                } elseif ($fieldName === 'editableTreatmentUnitPrice') {
                    /**
                     * Traitement : (PU Traitement du Produit * Coefficient Traitement du CP de l’adresse à collecter)
                     */
                    if ($quoteRequestLine->getEditableTreatmentUnitPrice() !== null) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableTreatmentUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTreatmentRate());
                    }
                } elseif ($fieldName === 'editableTransportUnitPrice') {
                    /**
                     * Budget mensuel Transport et Traitement = Nombre de passage par mois * PU transport * Coefficient CP
                     */
                    if ($quoteRequestLine->getEditableTransportUnitPrice() > 0) {
                        $result = $frequencyIntervalValue * $numberManager->denormalize($quoteRequestLine->getEditableTransportUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTransportRate());
                    }

                } elseif ($fieldName === 'editableTraceabilityUnitPrice') {
                    /**
                     * Budget mensuel Matériel Additionnel = Nombre de passage par mois * (Quantité de Produit saisie – 1) * PU Matériel Additionnel * Coefficient CP
                     */
                    if ($quoteRequestLine->getEditableTraceabilityUnitPrice() > 0) {
                        $result = $frequencyIntervalValue * ($quantity - 1) * $numberManager->denormalize($quoteRequestLine->getEditableTraceabilityUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTraceabilityRate());
                    }

                } elseif ($fieldName === 'treatmentCollectPrice') {
                    /**
                     * PU Collecte
                     * (Budget mensuel Transport et Traitement + Budget mensuel Matériel Additionnel) / (Nombre de passage par mois * Quantité de Produit saisie)
                     */
                    $transport = $this->calculatePriceByFieldName($quoteRequestLine, 'editableTransportUnitPrice');
                    $traceability = $this->calculatePriceByFieldName($quoteRequestLine,
                        'editableTraceabilityUnitPrice');

                    if ($frequencyIntervalValue * $quantity > 0) {
                        $result = ($transport + $traceability) / ($frequencyIntervalValue * $quantity);
                    }

                } elseif ($fieldName === 'totalAmount') {

                    /**
                     * Budget Mensuel par produit  (calcul) :
                     * quantité * PU Location +
                     * Budget mensuel Transport et Traitement +
                     * Budget mensuel Matériel Additionnel +
                     * quantité * PU Traitement
                     */
                    $rental = $quantity * $this->calculatePriceByFieldName($quoteRequestLine,
                            'editableRentalUnitPrice');
                    $transport = $this->calculatePriceByFieldName($quoteRequestLine, 'editableTransportUnitPrice');
                    $traceability = $this->calculatePriceByFieldName($quoteRequestLine,
                        'editableTraceabilityUnitPrice');
                    $treatment = $quantity * $this->calculatePriceByFieldName($quoteRequestLine,
                            'editableTreatmentUnitPrice');
                    $result = $rental + $transport + $traceability + $treatment;
                }

            } elseif ($calculationFormula === 'PACKAGE') {
                if ($fieldName === 'editableRentalUnitPrice') {
                    /**
                     * PU Location : PU Location * coeff CP
                     */
                    if ($quoteRequestLine->getEditableRentalUnitPrice() > 0) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableRentalUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getRentalRate());
                    }

                } elseif ($fieldName === 'editableTreatmentUnitPrice') {
                    /**
                     * PU traitement :  PU Traitement * coeff CP
                     */
                    if ($quoteRequestLine->getEditableTreatmentUnitPrice() > 0) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableTreatmentUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTreatmentRate());
                    }

                } elseif ($fieldName === 'editableTransportUnitPrice') {
                    /**
                     * PU transport : PU Transport * coeff CP
                     */
                    if ($quoteRequestLine->getEditableTransportUnitPrice() > 0) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableTransportUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTransportRate());
                    }

                } elseif ($fieldName === 'editableTraceabilityUnitPrice') {

                    /**
                     * PU Mat additionnel * Coeff
                     */
                    if ($quoteRequestLine->getEditableTraceabilityUnitPrice() > 0) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableTraceabilityUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTraceabilityRate());
                    }

                } elseif ($fieldName === 'treatmentCollectPrice') {
                } elseif ($fieldName === 'totalAmount') {

                    /**
                     * Budget Mensuel par produit  (calcul)
                     * (PU Loc * Coef Loc + PU Transport * Coeff + PU Traitement * Coeff + PU Mat additionnel * Coeff
                     */
                    $rental = $this->calculatePriceByFieldName($quoteRequestLine, 'editableRentalUnitPrice');
                    $transport = $this->calculatePriceByFieldName($quoteRequestLine, 'editableTransportUnitPrice');
                    $traceability = $this->calculatePriceByFieldName($quoteRequestLine,
                        'editableTraceabilityUnitPrice');
                    $treatment = $this->calculatePriceByFieldName($quoteRequestLine, 'editableTreatmentUnitPrice');
                    $result = $quantity * ($rental + $transport + $traceability + $treatment);
                }

            } elseif ($calculationFormula === 'UNIT_PRICE') {
                if ($fieldName === 'editableRentalUnitPrice') {
                    /**
                     * PU Location : PU Location * coeff CP
                     */
                    if ($quoteRequestLine->getEditableRentalUnitPrice() > 0) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableRentalUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getRentalRate());
                    }

                } elseif ($fieldName === 'editableTreatmentUnitPrice') {
                    /**
                     * PU traitement :  PU Traitement * coeff CP
                     */
                    if ($quoteRequestLine->getEditableTreatmentUnitPrice() > 0) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableTreatmentUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTreatmentRate());
                    }

                } elseif ($fieldName === 'editableTransportUnitPrice') {
                    /**
                     * PU transport : PU Transport * coeff CP
                     */
                    if ($quoteRequestLine->getEditableTransportUnitPrice() > 0) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableTransportUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTransportRate());
                    }

                } elseif ($fieldName === 'editableTraceabilityUnitPrice') {

                    /**
                     * PU Mat additionnel * Coeff
                     */
                    if ($quoteRequestLine->getEditableTraceabilityUnitPrice() > 0) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableTraceabilityUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTraceabilityRate());
                    }


                } elseif ($fieldName === 'treatmentCollectPrice') {
                    /**
                     * PU Collecte
                     */
                    if ($quoteRequestLine->getEditableTransportUnitPrice() > 0) {
                        $result = $numberManager->denormalize($quoteRequestLine->getEditableTransportUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTransportRate());
                    }

                } elseif ($fieldName === 'totalAmount') {

                    /**
                     * Budget Mensuel par produit  (calcul)
                     * (PU Loc * Coef Loc + PU Transport * Coeff + PU Traitement * Coeff + PU Mat additionnel * Coeff
                     */
                    $rental = $this->calculatePriceByFieldName($quoteRequestLine, 'editableRentalUnitPrice');
                    $transport = $this->calculatePriceByFieldName($quoteRequestLine, 'editableTransportUnitPrice');
                    $traceability = $this->calculatePriceByFieldName($quoteRequestLine,
                        'editableTraceabilityUnitPrice');
                    $treatment = $this->calculatePriceByFieldName($quoteRequestLine, 'editableTreatmentUnitPrice');
                    $result = $rental + $transport + $traceability + $treatment;
                }
            }
        }

        if ($returnNormalizedNumber) {
            $result = $numberManager->normalize($result);
        }

        return $result;
    }

    public function calculateFrequencyCoeff($quoteRequestLine){
        $numberManager = $this->numberManager;

        $frequencyIntervalValue = 1;
        if (strtoupper($quoteRequestLine->getFrequency()) === 'REGULAR') {
//            $monthlyCoefficientValues = $this->container->getParameter('paprec.frequency_interval.monthly_coefficients');
            $frequencyInterval = strtolower($quoteRequestLine->getFrequencyInterval());
            $periodValue = 1;
            if(strtoupper($frequencyInterval) === 'WEEK'){
                $periodValue = 52;
            }elseif(strtoupper($frequencyInterval) === 'MONTH'){
                $periodValue = 12;

            }elseif(strtoupper($frequencyInterval) === 'BIMESTRE'){
                $periodValue = 6;

            }elseif(strtoupper($frequencyInterval) === 'QUARTER'){
                $periodValue = 4;

            }
            $frequencyIntervalValue = ((int)$quoteRequestLine->getFrequencyTimes() * $periodValue) / 12;

//            if (strtoupper($frequencyInterval) !== 'MONTH' && array_key_exists($frequencyInterval, $monthlyCoefficientValues)) {
//                $frequencyIntervalValue = $monthlyCoefficientValues[$frequencyInterval] * $quoteRequestLine->getFrequencyTimes();
//            }
//            if(strtoupper($frequencyInterval) === 'MONTH'){
//                $frequencyIntervalValue = (int)$quoteRequestLine->getFrequencyTimes();
//            }
        }

        return $frequencyIntervalValue;
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

    public function getProductLabels($product)
    {
        $id = $product;
        if ($product instanceof Product) {
            $id = $product->getId();
        }
        try {

            $productLabels = $this->em->getRepository('App:ProductLabel')->findBy(array(
                    'product' => $product,
                    'deleted' => null
                )
            );

            /**
             * Vérification que le produit existe ou ne soit pas supprimé
             */
            if (empty($productLabels)) {
                throw new EntityNotFoundException('productLabelsNotFound');
            }


            return $productLabels;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

    }

    public function getProductLabelByProductAndLocale(Product $product, $language)
    {

        $id = $product;
        if ($product instanceof Product) {
            $id = $product->getId();
        }
        try {

            $product = $this->em->getRepository('App:Product')->find($id);

            /**
             * Vérification que le produit existe ou ne soit pas supprimé
             */
            if ($product === null || $this->isDeleted($product)) {
                throw new EntityNotFoundException('productNotFound');
            }

            $productLabel = $this->em->getRepository('App:ProductLabel')->findOneBy(array(
                'product' => $product,
                'language' => $language
            ));

            /**
             * Si il y'en a pas dans la langue de la locale, on en prend un au hasard
             */
            if ($productLabel === null || $this->IsDeletedProductLabel($productLabel)) {
                $productLabel = $this->em->getRepository('App:ProductLabel')->findOneBy(array(
                    'product' => $product
                ));

                if ($productLabel === null || $this->IsDeletedProductLabel($productLabel)) {
                    throw new EntityNotFoundException('productLabelNotFound');
                }
            }


            return $productLabel;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérifie qu'à ce jour, le libellé produit ne soit pas supprimé
     *
     * @param ProductLabel $productLabel
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeletedProductLabel(ProductLabel $productLabel, $throwException = false)
    {
        $now = new \DateTime();

        if ($productLabel->getDeleted() !== null && $productLabel->getDeleted() instanceof \DateTime && $productLabel->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('productLabelNotFound');
            }

            return true;

        }
        return false;
    }

    public function getAvailableProducts(string $catalog)
    {
        $queryBuilder = $this->em->getRepository(Product::class)->createQueryBuilder('p');

        $queryBuilder->select(array('p'))
            ->where('p.deleted IS NULL')
            ->andWhere('p.catalog = :catalog')
            ->andWhere('p.isEnabled = 1')
            ->orderBy('p.position')
            ->setParameter('catalog', $catalog);

        return $queryBuilder->getQuery()->getResult();
    }


}
