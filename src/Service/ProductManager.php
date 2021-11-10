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
     * @param PostalCode $pC
     * @param Product $product
     * @param $qtty
     * @return float|int
     * @throws Exception
     */
    public function calculatePrice(QuoteRequestLine $quoteRequestLine)
    {
        $numberManager = $this->numberManager;

        $frequencyIntervalValue = 1;
        if ($quoteRequestLine->getFrequency() === 'regular') {
            $monthlyCoefficientValues = $this->container->getParameter('paprec.frequency_interval.monthly_coefficients');
            $frequencyInterval = $quoteRequestLine->getFrequencyInterval();
            if (array_key_exists($frequencyInterval, $monthlyCoefficientValues)) {
                $frequencyIntervalValue = $monthlyCoefficientValues[$frequencyInterval] * $quoteRequestLine->getFrequencyTimes();
            }
        }

        return
                (($quoteRequestLine->getEditableRentalUnitPrice() == 0) ? 0 : $numberManager->denormalize($quoteRequestLine->getEditableRentalUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getRentalRate()) * $quoteRequestLine->getQuantity())
                + (
                (($quoteRequestLine->getEditableTransportUnitPrice() == 0) ? 0 : $numberManager->denormalize($quoteRequestLine->getEditableTransportUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTransportRate()))
                + (
                    (($quoteRequestLine->getEditableTreatmentUnitPrice() == 0) ? 0 : $numberManager->denormalize($quoteRequestLine->getEditableTreatmentUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTreatmentRate()))
                + (($quoteRequestLine->getEditableTraceabilityUnitPrice() == 0) ? 0 : $numberManager->denormalize($quoteRequestLine->getEditableTraceabilityUnitPrice()) * $numberManager->denormalize15($quoteRequestLine->getTraceabilityRate()))
                )
                * $quoteRequestLine->getQuantity()
                )
            * ($frequencyIntervalValue);

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
                case 'stairs':
                    return $prices['stairs'];
                    break;
                case 'elevator':
                    return $prices['elevator'];
                    break;
                case 'ground':
                    return $prices['ground'];
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
