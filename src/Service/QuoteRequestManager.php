<?php

namespace App\Service;


use App\Entity\MissionSheet;
use App\Entity\Product;
use App\Entity\QuoteRequest;
use App\Entity\QuoteRequestLine;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\ORMException;
use Exception;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use iio\libmergepdf\Merger;
use Knp\Snappy\Pdf;
use Swift_Message;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use function PHPUnit\Framework\isEmpty;

class QuoteRequestManager
{

    private $em;
    private $container;
    private $numberManager;
    private $productManager;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        ContainerInterface $container,
        TranslatorInterface $translator,
        NumberManager $numberManager,
        ProductManager $productManager
    ) {
        $this->em = $em;
        $this->container = $container;
        $this->translator = $translator;
        $this->numberManager = $numberManager;
        $this->productManager = $productManager;
    }

    public function get($quoteRequest, $throwException = true)
    {
        $id = $quoteRequest;
        if ($quoteRequest instanceof QuoteRequest) {
            $id = $quoteRequest->getId();
        }
        try {

            $quoteRequest = $this->em->getRepository('App:QuoteRequest')->find($id);

            /**
             * Vérification que le quoteRequest existe ou ne soit pas supprimé
             */
            if ($quoteRequest === null || $this->isDeleted($quoteRequest)) {
                throw new EntityNotFoundException('quoteRequestNotFound');
            }


            return $quoteRequest;

        } catch (Exception $e) {
            if ($throwException) {
                throw new Exception($e->getMessage(), $e->getCode());
            } else {
                return null;
            }
        }
    }

    /**
     * Création d'une QuoteRequest avec un Token
     *
     * @param bool $doFlush
     * @return QuoteRequest
     * @throws Exception
     */
    public function add($doFlush = true)
    {
        try {

            /**
             * Génération d'un token
             */
            $token = $this->generateToken();

            $quoteRequest = new QuoteRequest();
            $this->em->persist($quoteRequest);

            $quoteRequest->setToken($token);

            if ($doFlush) {
                $this->em->flush();
            }

            return $quoteRequest;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Generate token with $size length
     *
     * @param $size
     *
     * @return string
     */
    public function generateToken($size = 32)
    {
        $generator = new ComputerPasswordGenerator();

        $generator
            ->setUppercase()
            ->setLowercase()
            ->setNumbers()
            ->setSymbols(false)
            ->setLength($size);

        return $generator->generatePassword();

    }

    public function getCountByReference($reference)
    {
        $qb = $this->em->getRepository('App:QuoteRequest')->createQueryBuilder('qr')
            ->select('count(qr)')
            ->where('qr.reference LIKE :ref')
            ->andWhere('qr.deleted IS NULL')
            ->setParameter('ref', $reference . '%');

        $count = $qb->getQuery()->getSingleScalarResult();

        if ($count != null) {
            return (int)$count + 1;
        }

        return 1;
    }

    /**
     * Récupération d'une QuoteRequest valide par l'id et le token
     *
     * @param $quoteRequest
     * @param $token
     * @return object|QuoteRequest
     * @throws Exception
     */
    public function getActiveByIdAndToken($quoteRequest, $token)
    {
        $id = $quoteRequest;
        if ($quoteRequest instanceof QuoteRequest) {
            $id = $quoteRequest->getId();
        }
        try {

            $quoteRequest = $this->em->getRepository('App:QuoteRequest')->findOneBy(
                array(
                    'id' => $id,
                    'token' => $token
                ));

            /**
             * Vérification que le quoteRequest existe ou ne soit pas supprimée
             */
            if ($quoteRequest === null || $this->isDeleted($quoteRequest)) {
                throw new EntityNotFoundException('quoteRequestNotFound');
            }


            return $quoteRequest;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());

        }
    }

    /**
     * Vérifie qu'à ce jour, le quoteRequest ce soit pas supprimée
     *
     * @param QuoteRequest $quoteRequestl
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(QuoteRequest $quoteRequest, $throwException = false)
    {
        $now = new \DateTime();

        if ($quoteRequest->getDeleted() !== null && $quoteRequest->getDeleted() instanceof \DateTime && $quoteRequest->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('quoteRequestNotFound');
            }

            return true;

        }
        return false;
    }

    /**
     * Vérifie si un produit d'une quoteRequest à la fréquence unknown
     * Si c'est le cas, alors il faut que le bouton pour envoyer un email avec le contrat au client soit désactivé
     *
     * @param QuoteRequest $quoteRequest
     * @return bool
     */
    public function isAbleToSendContractEmail(QuoteRequest $quoteRequest)
    {
        $quoteRequestLines = $quoteRequest->getQuoteRequestLines();

        if (!empty($quoteRequestLines) && count($quoteRequestLines)) {
            foreach ($quoteRequestLines as $quoteRequestLine) {
                if (strtoupper($quoteRequestLine->getFrequency()) === 'UNKNOWN') {
                    return false;
                }
            }
        }

        return true;

    }


    /**
     * Ajoute une quoteRequestLine à un quoteRequest
     * @param QuoteRequest $quoteRequest
     * @param QuoteRequestLine $quoteRequestLine
     * @param null $user
     * @throws Exception
     */
    public function addLine(
        QuoteRequest $quoteRequest,
        QuoteRequestLine $quoteRequestLine,
        $user = null,
        $doFlush = true
    ) {

        // On check s'il existe déjà une ligne pour ce produit, pour l'incrémenter
        $currentQuoteLine = $this->em->getRepository('App:QuoteRequestLine')->findOneBy(
            array(
                'quoteRequest' => $quoteRequest,
                'product' => $quoteRequestLine->getProduct()
            )
        );

        if ($currentQuoteLine) {
            $quantity = $quoteRequestLine->getQuantity() + $currentQuoteLine->getQuantity();
            $currentQuoteLine->setQuantity($quantity);

            /**
             * On recalcule le montant total de la ligne ainsi que celui du devis complet
             */
            $totalLine = $this->calculateTotalLine($currentQuoteLine);
            $currentQuoteLine->setTotalAmount($totalLine);
            $treatmentCollectPrice = $this->calculateTreatmentCollectPrice($quoteRequestLine);
            $quoteRequestLine->setTreatmentCollectPrice($treatmentCollectPrice);
            $this->em->flush();
        } else {
            $quoteRequestLine->setQuoteRequest($quoteRequest);
            $quoteRequest->addQuoteRequestLine($quoteRequestLine);

            $quoteRequestLine->setRentalUnitPrice($quoteRequestLine->getProduct()->getRentalUnitPrice());
            $quoteRequestLine->setTransportUnitPrice($quoteRequestLine->getProduct()->getTransportUnitPrice());
            $quoteRequestLine->setTreatmentUnitPrice($quoteRequestLine->getProduct()->getTreatmentUnitPrice());
            $quoteRequestLine->setTraceabilityUnitPrice($quoteRequestLine->getProduct()->getTraceabilityUnitPrice());

            $quoteRequestLine->setEditableTransportUnitPrice($quoteRequestLine->getProduct()->getTransportUnitPrice());
            $quoteRequestLine->setEditableRentalUnitPrice($quoteRequestLine->getProduct()->getRentalUnitPrice());
            $quoteRequestLine->setEditableTreatmentUnitPrice($quoteRequestLine->getProduct()->getTreatmentUnitPrice());
            $quoteRequestLine->setEditableTraceabilityUnitPrice($quoteRequestLine->getProduct()->getTraceabilityUnitPrice());
            $quoteRequestLine->setFrequency($quoteRequestLine->getFrequency());
            $quoteRequestLine->setFrequencyInterval($quoteRequestLine->getFrequencyInterval());
            $quoteRequestLine->setFrequencyTimes($quoteRequestLine->getFrequencyTimes());
            $quoteRequestLine->setProductName($quoteRequestLine->getProduct()->getId());

            /**
             * Si codePostal, on récupère tous les coefs de celui-ci et on les affecte au quoteRequestLine
             */
            if ($quoteRequest->getPostalCode()) {
                $quoteRequestLine->setRentalRate($quoteRequest->getPostalCode()->getRentalRate());
                switch ($quoteRequestLine->getProduct()->getTransportType()) {
                    case 'CBR_REG' :
                    {
                        $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getCbrRegTransportRate());
                        break;
                    }
                    case 'CBR_PONCT' :
                    {
                        $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getCbrPonctTransportRate());
                        break;
                    }
                    case 'VL_PL_CFS_REG' :
                    {
                        $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getVlPlCfsRegTransportRate());
                        break;
                    }
                    case 'VL_PL_CFS_PONCT' :
                    {
                        $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getVlPlCfsPonctTransportRate());
                        break;
                    }
                    case 'VL_PL' :
                    {
                        $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getVlPlTransportRate());
                        break;
                    }
                    case 'BOM' :
                    {
                        $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getBomTransportRate());
                        break;
                    }
                    case 'PL_PONCT' :
                    {
                        $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getPlPonctTransportRate());
                        break;
                    }
                    default :
                    {
                        $quoteRequestLine->setTransportRate($this->numberManager->normalize15(1));
                        break;
                    }
                }
                $quoteRequestLine->setTreatmentRate($quoteRequest->getPostalCode()->getTreatmentRate());
                $quoteRequestLine->setTraceabilityRate($quoteRequest->getPostalCode()->getTraceabilityRate());
            } else {
                /**
                 * Si pas de code postal, on met tous les coefs à 1 par défaut
                 */
                $quoteRequestLine->setRentalRate($this->numberManager->normalize15(1));
                $quoteRequestLine->setTransportRate($this->numberManager->normalize15(1));
                $quoteRequestLine->setTreatmentRate($this->numberManager->normalize15(1));
                $quoteRequestLine->setTraceabilityRate($this->numberManager->normalize15(1));
            }

            /**
             * Si il y a une condition d'accès, on l'affecte au quoteRequestLine
             */
            if ($quoteRequest->getAccess()) {
                $quoteRequestLine->setAccessPrice($this->numberManager->normalize($this->productManager->getAccesPrice($quoteRequest)));
            } else {
                /**
                 * Sinon on lui met à 0 par défaut
                 */
                $quoteRequestLine->setAccessPrice(0);
            }

            $this->em->persist($quoteRequestLine);

            /**
             * On recalcule le montant total de la ligne ainsi que celui du devis complet
             */
            $totalLine = 0 + $this->calculateTotalLine($quoteRequestLine);
            $quoteRequestLine->setTotalAmount($totalLine);

            $treatmentCollectPrice = $this->calculateTreatmentCollectPrice($quoteRequestLine);
            $quoteRequestLine->setTreatmentCollectPrice($treatmentCollectPrice);
            $this->em->flush();
        }

        $total = $this->calculateTotal($quoteRequest);
        $quoteRequest->setTotalAmount($total);
        $quoteRequest->setDateUpdate(new \DateTime());
        $quoteRequest->setUserUpdate($user);
        if ($doFlush) {
            $this->em->flush();
        }
    }


    /**
     * Pour ajouter une QuoteRequestLine depuis le Cart, il faut d'abord retrouver le Product
     * @param $productId
     * @param $qtty
     * @throws Exception
     */
    public function addLineFromCart(
        QuoteRequest $quoteRequest,
        $productId,
        $qtty,
        $frequency,
        $frequencyTimes,
        $frequencyInterval,
        $doFlush = true
    ) {
        try {
            $product = $this->productManager->get($productId);
            $quoteRequestLine = new QuoteRequestLine();

            $quoteRequestLine->setProduct($product);
            $quoteRequestLine->setQuantity($qtty);
            $quoteRequestLine->setFrequency($frequency);
            if (!empty($frequencyTimes)) {
                $quoteRequestLine->setFrequencyTimes($frequencyTimes);
            }
            if (!empty($frequencyInterval)) {
                $quoteRequestLine->setFrequencyInterval($frequencyInterval);
            }
            $this->addLine($quoteRequest, $quoteRequestLine, null, $doFlush);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }


    }

    /**
     * Met à jour les montants totaux après l'édition d'une ligne
     * @param QuoteRequest $quoteRequest
     * @param QuoteRequestLine $quoteRequestLine
     * @param $user
     * @param bool $doFlush
     * @param $overallDiscount
     * @throws Exception
     */
    public function editLine(
        QuoteRequest $quoteRequest,
        QuoteRequestLine $quoteRequestLine,
        $user,
        $doFlush = true,
        $editQuoteRequest = true,
        $overallDiscount = null
    ) {
        $now = new \DateTime();

        if ($overallDiscount !== null) {

            $newRentalUnitPrice = $this->numberManager->normalize((($overallDiscount * $this->numberManager->denormalize($quoteRequestLine->getEditableRentalUnitPrice())) / 100))
                + $quoteRequestLine->getEditableRentalUnitPrice();

            $newTransportUnitPrice = $this->numberManager->normalize((($overallDiscount * $this->numberManager->denormalize($quoteRequestLine->getEditableTransportUnitPrice())) / 100))
                + $quoteRequestLine->getEditableTransportUnitPrice();

            $newTreatmentUnitPrice = $this->numberManager->normalize((($overallDiscount * $this->numberManager->denormalize($quoteRequestLine->getEditableTreatmentUnitPrice())) / 100))
                + $quoteRequestLine->getEditableTreatmentUnitPrice();

            $newTraceabilityUnitPrice = $this->numberManager->normalize((($overallDiscount * $this->numberManager->denormalize($quoteRequestLine->getEditableTraceabilityUnitPrice())) / 100))
                + $quoteRequestLine->getEditableTraceabilityUnitPrice();

            $quoteRequestLine->setEditableTransportUnitPrice($newTransportUnitPrice);
            $quoteRequestLine->setEditableRentalUnitPrice($newRentalUnitPrice);
            $quoteRequestLine->setEditableTreatmentUnitPrice($newTreatmentUnitPrice);
            $quoteRequestLine->setEditableTraceabilityUnitPrice($newTraceabilityUnitPrice);

            $quoteRequest->setOverallDiscount($this->numberManager->normalize($overallDiscount));
        } else {

            $quoteRequestLine->setEditableTransportUnitPrice($quoteRequestLine->getEditableTransportUnitPrice());
            $quoteRequestLine->setEditableRentalUnitPrice($quoteRequestLine->getEditableRentalUnitPrice());
            $quoteRequestLine->setEditableTreatmentUnitPrice($quoteRequestLine->getEditableTreatmentUnitPrice());
            $quoteRequestLine->setEditableTraceabilityUnitPrice($quoteRequestLine->getEditableTraceabilityUnitPrice());

        }

        $quoteRequestLine->setFrequency($quoteRequestLine->getFrequency());
        $quoteRequestLine->setFrequencyInterval($quoteRequestLine->getFrequencyInterval());
        $quoteRequestLine->setFrequencyTimes($quoteRequestLine->getFrequencyTimes());

        if ($quoteRequest->getPostalCode()) {
            $quoteRequestLine->setRentalRate($quoteRequest->getPostalCode()->getRentalRate());
            switch ($quoteRequestLine->getProduct()->getTransportType()) {
                case 'CBR_REG' :
                {
                    $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getCbrRegTransportRate());
                    break;
                }
                case 'CBR_PONCT' :
                {
                    $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getCbrPonctTransportRate());
                    break;
                }
                case 'VL_PL_CFS_REG' :
                {
                    $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getVlPlCfsRegTransportRate());
                    break;
                }
                case 'VL_PL_CFS_PONCT' :
                {
                    $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getVlPlCfsPonctTransportRate());
                    break;
                }
                case 'VL_PL' :
                {
                    $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getVlPlTransportRate());
                    break;
                }
                case 'BOM' :
                {
                    $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getBomTransportRate());
                    break;
                }
                case 'PL_PONCT' :
                {
                    $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getPlPonctTransportRate());
                    break;
                }
                default :
                {
                    $quoteRequestLine->setTransportRate($this->numberManager->normalize15(1));
                    break;
                }
            }
            $quoteRequestLine->setTreatmentRate($quoteRequest->getPostalCode()->getTreatmentRate());
            $quoteRequestLine->setTraceabilityRate($quoteRequest->getPostalCode()->getTraceabilityRate());
        } else {
            /**
             * Si pas de code postal, on met tous les coefs à 1 par défaut
             */
            $quoteRequestLine->setRentalRate($this->numberManager->normalize15(1));
            $quoteRequestLine->setTransportRate($this->numberManager->normalize15(1));
            $quoteRequestLine->setTreatmentRate($this->numberManager->normalize15(1));
            $quoteRequestLine->setTraceabilityRate($this->numberManager->normalize15(1));
        }

        $totalLine = 0 + $this->calculateTotalLine($quoteRequestLine);
//        if ($quoteRequest->getOverallDiscount() !== null) {
//            $totalLine *= (1 + $quoteRequest->getOverallDiscount() / 10000);
//        }
        $quoteRequestLine->setTotalAmount($totalLine);
        $treatmentCollectPrice = $this->calculateTreatmentCollectPrice($quoteRequestLine);
        $quoteRequestLine->setTreatmentCollectPrice($treatmentCollectPrice);
        $quoteRequestLine->setDateUpdate($now);

        if ($editQuoteRequest) {
            $total = $this->calculateTotal($quoteRequest);
            $quoteRequest->setTotalAmount($total);
            $quoteRequest->setDateUpdate($now);
            $quoteRequest->setUserUpdate($user);
        }

        /**
         * Si il y a une condition d'accès, on l'affecte au quoteRequestLine
         */
        if ($quoteRequest->getAccess()) {
            $quoteRequestLine->setAccessPrice($this->numberManager->normalize($this->productManager->getAccesPrice($quoteRequest)));
        } else {
            /**
             * Sinon on lui met à 0 par défaut
             */
            $quoteRequestLine->setAccessPrice(0);
        }

        if ($doFlush) {
            $this->em->flush();
        }
    }

    /**
     * Retourne le montant total d'une QuoteRequestLine
     * @param QuoteRequestLine $quoteRequestLine
     * @return float|int
     * @throws Exception
     */
    public function calculateTotalLine(QuoteRequestLine $quoteRequestLine)
    {
        return $this->numberManager->normalize(
            $this->productManager->calculatePrice($quoteRequestLine)
        );
    }

    /**
     * Calcule le montant total d'un QuoteRequest
     * @param QuoteRequest $quoteRequest
     * @return float|int
     */
    public function calculateTotal(QuoteRequest $quoteRequest)
    {
        /**
         * A chaque fois que l'on calcule on recheck le transportUnitPrice minimum parmis tous les produits
         */

        $totalAmount = 0;
        if ($quoteRequest->getQuoteRequestLines() && count($quoteRequest->getQuoteRequestLines())) {

            foreach ($quoteRequest->getQuoteRequestLines() as $quoteRequestLine) {
                $totalAmount += $quoteRequestLine->getTotalAmount();
            }
        }

        return $totalAmount;
    }

    /**
     * Fonction calculant le prix unitaire de collecte et traitement par contenant (PUct)
     * Si le calcul est modifiée, il faudra donc le modifier uniquement ici
     *
     * @param QuoteRequestLine $quoteRequestLine
     * @return float|int
     * @throws Exception
     */
    public function calculateTreatmentCollectPrice(QuoteRequestLine $quoteRequestLine)
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

        return $numberManager->normalize($editableTransportUnitPrice + $editableTreatmentUnitPrice + $editableTraceabilityUnitPrice);

    }


    /**
     * Envoie un mail à la personne ayant fait une demande de devis
     * @throws Exception
     */
    public function sendConfirmRequestEmail(QuoteRequest $quoteRequest)
    {

        try {
            $from = $_ENV['PAPREC_EMAIL_SENDER'];
            $this->get($quoteRequest);

            $rcptTo = $quoteRequest->getEmail();

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

//            if (!$quoteRequest->getPostalCode()) {
//                return false;
//            }

            $locale = 'FR';

            $message = new Swift_Message();
            $message
                ->setSubject($this->translator->trans('Commercial.ConfirmEmail.Object',
                    array(), 'messages', strtolower($locale)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        'public/emails/confirmQuoteEmail.html.twig',
                        array(
                            'quoteRequest' => $quoteRequest,
                            'quoteRequestLines' => $quoteRequest->getQuoteRequestLines(),
                            'locale' => strtolower($locale),
//                            'salesman' => $quoteRequest->getPostalCode()->getUserInCharge()
                        )
                    ),
                    'text/html'
                );
            if ($this->container->get('mailer')->send($message)) {
                return true;
            }
            return false;

        } catch (ORMException $e) {
            throw new Exception('unableToSendConfirmQuoteRequest', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Envoie un mail à la personne ayant fait une demande d'aide pour la création d'un devis
     * @throws Exception
     */
    public function sendConfirmContactRequestEmail(QuoteRequest $quoteRequest, $locale)
    {

        try {
            $from = $_ENV['PAPREC_EMAIL_SENDER'];

            $rcptTo = $quoteRequest->getEmail();

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

            $message = new Swift_Message();
            $message
                ->setSubject($this->translator->trans('Commercial.ConfirmContactRequestEmail.Object',
                    array(), 'messages', strtolower($locale)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        'public/emails/confirmContactRequestEmail.html.twig',
                        array(
                            'locale' => strtolower($locale),
                            'quoteRequest' => $quoteRequest
                        )
                    ),
                    'text/html'
                );
            if ($this->container->get('mailer')->send($message)) {
                return true;
            }
            return false;

        } catch (ORMException $e) {
            throw new Exception('unableToSendConfirmContactRequest', Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Envoie un mail au service commercial suite à une demande d'aide d'un client pour la création d'un devis
     * @throws Exception
     */
    public function sendNewContactRequestEmail(QuoteRequest $quoteRequest, $locale)
    {

        try {
            $from = $_ENV['PAPREC_EMAIL_SENDER'];

            /**
             * Si la quoteRequest est associé à un commercial, on lui envoie le mail d'information de la création d'une nouvelle demande
             * Sinon => à spécifier
             */
            $rcptTo = null;
            if ($quoteRequest->getUserInCharge()) {
                $rcptTo = $quoteRequest->getUserInCharge()->getEmail();
            }

            if ($rcptTo === null || $rcptTo === '') {
                return false;
            }

            $message = new Swift_Message();
            $message
                ->setSubject($this->translator->trans('Commercial.NewContactRequestEmail.Object',
                    array(), 'messages', strtolower($locale)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        'public/emails/newContactRequestEmail.html.twig',
                        array(
                            'locale' => strtolower($locale),
                            'quoteRequest' => $quoteRequest
                        )
                    ),
                    'text/html'
                );
            if ($this->container->get('mailer')->send($message)) {
                return true;
            }
            return false;

        } catch (ORMException $e) {
            throw new Exception('unableToSendNewContactRequest', Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Envoie un mail au commercial associé lui indiquant la nouvelle demande de devis créée
     * @throws Exception
     */
    public function sendNewRequestEmail(QuoteRequest $quoteRequest)
    {

        try {
            $from = $_ENV['PAPREC_EMAIL_SENDER'];
            $this->get($quoteRequest);

            $translator = $this->container->get('translator');

            /**
             * Si la quoteRequest est associé à un commercial, on lui envoie le mail d'information de la création d'une nouvelle demande
             * Sinon,
             *      si la demande est multisite alors on envoie au mail générique des demandes multisites
             *          sinon on envoie au mail générique de la région associée au code postal de la demande
             */
            $rcptTo = null;
            if ($quoteRequest->getUserInCharge()) {
                $rcptTo = $quoteRequest->getUserInCharge()->getEmail();
            } else {
                if ($quoteRequest->getIsMultisite()) {
                    $rcptTo = $_ENV['PAPREC_SALESMAN_MULTISITE_EMAIL'];
                } else {
                    // TODO
//                    $rcptTo = $quoteRequest->getPostalCode()->getRegion()->getEmail();
                }
            }

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

            $locale = 'FR';

            $message = new Swift_Message();
            $message
                ->setSubject(
                    $this->translator->trans(
                        'Commercial.NewQuoteEmail.Object',
                        array('%number%' => $quoteRequest->getId()), 'messages', strtolower($locale)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        'public/emails/newQuoteEmail.html.twig',
                        array(
                            'quoteRequest' => $quoteRequest,
                            'locale' => strtolower($locale),
                            'quoteRequestLines' => $quoteRequest->getQuoteRequestLines()
                        )
                    ),
                    'text/html'
                );


            if ($this->container->get('mailer')->send($message)) {
                return true;
            }
            return false;

        } catch (ORMException $e) {
            throw new Exception('unableToSendNewQuoteRequest', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Génération du numéro de l'offre
     * @param QuoteRequest $quoteRequest
     * @return int
     */
    public function generateNumber(QuoteRequest $quoteRequest)
    {
        return time();
    }

    /**
     * Génération de la référence de l'offre
     * @param QuoteRequest $quoteRequest
     */
    public function generateReference(QuoteRequest $quoteRequest)
    {
        $regionName = 'FR';

        $reference = strtoupper($regionName) . $quoteRequest->getDateCreation()->format('ymd');
        $reference .= '-' . str_pad($this->getCountByReference($reference), 2, '0', STR_PAD_LEFT);

        return $reference;
    }

    /**
     * Envoi de l'offre généré au client
     *
     * @param QuoteRequest $quoteRequest
     * @return bool
     * @throws Exception
     */
    public function sendGeneratedQuoteEmail(QuoteRequest $quoteRequest)
    {
        try {
            $from = $_ENV['PAPREC_EMAIL_SENDER'];

            $rcptTo = $quoteRequest->getEmail();

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

            $localeFilename = 'FR';

            $pdfFilename = $quoteRequest->getReference() . '-' . $this->translator->trans('Commercial.GeneratedQuoteEmail.FileName',
                    array(), 'messages', strtolower($localeFilename)) . '-' . $quoteRequest->getBusinessName() . '.pdf';

            $pdfFile = $this->generatePDF($quoteRequest, strtolower($localeFilename), false);

            if (!$pdfFile) {
                return false;
            }

            $attachment = new Swift_Message(file_get_contents($pdfFile), $pdfFilename, 'application/pdf');

            $translator = $this->container->get('translator');

            /**
             * Génération du Token de la quoteRequest s'il n'y en a pas
             */
            if (!$quoteRequest->getToken()) {
                $token = $this->generateToken();
                $quoteRequest->setToken($token);
                $this->em->flush();
            }

            $message = new Swift_Message();
            $message
                ->setSubject($this->translator->trans('Commercial.GeneratedQuoteEmail.Object',
                    array(), 'messages', strtolower($localeFilename)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        'public/emails/generatedQuoteEmail.html.twig',
                        array(
                            'quoteRequest' => $quoteRequest,
                            'locale' => strtolower($localeFilename)
                        )
                    ),
                    'text/html'
                )
                ->attach($attachment);

            if ($this->container->get('mailer')->send($message)) {
                if (file_exists($pdfFile)) {
                    unlink($pdfFile);
                }

                return true;
            }
            return false;

        } catch (ORMException $e) {
            throw new Exception('unableToSendGeneratedQuote', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Envoi du contrat généré au client
     *
     * @param QuoteRequest $quoteRequest
     * @return bool
     * @throws Exception
     */
    public function sendGeneratedContractEmail(QuoteRequest $quoteRequest)
    {
        try {
            $from = $_ENV['PAPREC_EMAIL_SENDER'];

            $rcptTo = [];
            $rcptTo[] = $quoteRequest->getEmail();

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

            if($quoteRequest->getUserInCharge()){
                $rcptTo[] = $quoteRequest->getUserInCharge()->getEmail();
            }

            $localeFilename = 'FR';

            $pdfFilename = $quoteRequest->getReference() . '-' . $this->translator->trans('Commercial.GeneratedContractEmail.FileName',
                    array(), 'messages', strtolower($localeFilename)) . '-' . $quoteRequest->getBusinessName() . '.pdf';

            $pdfFile = $this->generatePDF($quoteRequest, strtolower($localeFilename), true);

            if (!$pdfFile) {
                return false;
            }

            $attachment = new \Swift_Attachment(file_get_contents($pdfFile), $pdfFilename, 'application/pdf');

            $translator = $this->container->get('translator');

            $message = new Swift_Message();
            $message
                ->setSubject($this->translator->trans('Commercial.GeneratedContractEmail.Object',
                    array(), 'messages', strtolower($localeFilename)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        'public/emails/generatedContractEmail.html.twig',
                        array(
                            'quoteRequest' => $quoteRequest,
                            'locale' => strtolower($localeFilename)
                        )
                    ),
                    'text/html'
                )
                ->attach($attachment);

            if (!empty($quoteRequest->getQuoteRequestFiles()) && is_iterable($quoteRequest->getQuoteRequestFiles())) {

                $quoteRequestFileDirectory = $this->container->getParameter('paprec.quote_request_file.directory');

                foreach ($quoteRequest->getQuoteRequestFiles() as $quoteRequestFile) {

                    $quoteRequestFilePath = $quoteRequestFileDirectory . '/' . $quoteRequestFile->getSystemName();

                    if (file_exists($quoteRequestFilePath)) {
                        $message->attach(new \Swift_Attachment(file_get_contents($quoteRequestFilePath),
                            $quoteRequestFile->getOriginalFileName(), $quoteRequestFile->getMimeType()));
                    }

                }

            }

            if ($this->container->get('mailer')->send($message)) {
                if (file_exists($pdfFile)) {
                    unlink($pdfFile);
                }
                return true;
            }
            return false;

        } catch (ORMException $e) {
            throw new Exception('unableToSendGeneratedContract', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Envoi du contrat généré au commercial pour l'informer que l'utilisateur a reçu son contrat
     *
     * @param QuoteRequest $quoteRequest
     * @return bool
     * @throws Exception
     */
    public function sendNewContractEmail(QuoteRequest $quoteRequest)
    {
        try {
            $from = $_ENV['PAPREC_EMAIL_SENDER'];

            /**
             * Si la quoteRequest est associé à un commercial, on lui envoie le mail
             * Sinon,
             *      si la demande est multisite alors on envoie au mail générique des demandes multisites
             *          sinon on envoie au mail générique de la région associée au code postal de la demande
             */
            $rcptTo = null;
            if ($quoteRequest->getUserInCharge()) {
                $rcptTo = $quoteRequest->getUserInCharge()->getEmail();
            } else {
                if ($quoteRequest->getIsMultisite()) {
                    $rcptTo = $_ENV['PAPREC_SALESMAN_MULTISITE_EMAIL'];
                } else {
                    // TODO
//                    $rcptTo = $quoteRequest->getPostalCode()->getRegion()->getEmail();
                }
            }

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

            $localeFilename = 'FR';

            $pdfFilename = $quoteRequest->getReference() . '-' . $this->translator->trans('Commercial.NewContractEmail.FileName',
                    array(), 'messages', strtolower($localeFilename)) . '-' . $quoteRequest->getBusinessName() . '.pdf';

            $pdfFile = $this->generatePDF($quoteRequest, strtolower($localeFilename), true);

            if (!$pdfFile) {
                return false;
            }

            $attachment = new Swift_Message(file_get_contents($pdfFile), $pdfFilename, 'application/pdf');

            $translator = $this->container->get('translator');

            $message = new Swift_Message();
            $message
                ->setSubject($this->translator->trans('Commercial.NewContractEmail.Object',
                    array('%number%' => $quoteRequest->getId()), 'messages', strtolower($localeFilename)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        'public/emails/newContractEmail.html.twig',
                        array(
                            'quoteRequest' => $quoteRequest,
                            'locale' => strtolower($localeFilename)
                        )
                    ),
                    'text/html'
                )
                ->attach($attachment);

            if ($this->container->get('mailer')->send($message)) {
                if (file_exists($pdfFile)) {
                    unlink($pdfFile);
                }
                return true;
            }
            return false;

        } catch (ORMException $e) {
            throw new Exception('unableToSendGeneratedContract', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Génère le devis au format PDF et retoune le nom du fichier généré (placé dans /data/tmp)
     *
     * @param QuoteRequest $quoteRequest
     * @return bool|string
     * @throws Exception
     */
    public function generatePDF(QuoteRequest $quoteRequest, $locale, $addContract = true)
    {
        try {

            $query = $this->em
                ->getRepository(QuoteRequest::class)
                ->createQueryBuilder('qR')
                ->select('qR', 'uC', 'a')
                ->leftJoin('qR.userInCharge', 'uC')
                ->leftJoin('uC.agency', 'a')
                ->where('qR = :quoteRequest')
                ->setParameter("quoteRequest", $quoteRequest);

            $quoteRequest = $query->getQuery()->getOneOrNullResult();

            $pdfTmpFolder = $this->container->getParameter('paprec.data_tmp_directory');

            if (!is_dir($pdfTmpFolder)) {
                if (!mkdir($pdfTmpFolder, 0755, true) && !is_dir($pdfTmpFolder)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $pdfTmpFolder));
                }
            }

            $filename = $pdfTmpFolder . '/' . md5(uniqid('', true)) . '.pdf';
            $filenameOffer = $pdfTmpFolder . '/' . md5(uniqid('', true)) . '.pdf';

            $dateEndOffer = clone(($quoteRequest->getDateUpdate()) ?: $quoteRequest->getDateCreation());
            $dateEndOffer = $dateEndOffer->modify('+1 year');
            $dateEndOffer = $dateEndOffer->modify('-1 day');

            $today = new \DateTime();

            $snappy = new Pdf($_ENV['WKHTMLTOPDF_PATH']);
            $snappy->setOption('javascript-delay', 3000);
            $snappy->setTimeout(600);
            $snappy->setOption('dpi', 72);
            $snappy->setOption('zoom', 1);

            $agency = null;
            if ($quoteRequest->getUserInCharge() && $quoteRequest->getUserInCharge()->getAgency()) {
                $agency = $quoteRequest->getUserInCharge()->getAgency();
            }

            $templateDir = 'public/PDF';

            $snappy->setOption('header-html',
                $this->container->get('templating')->render($templateDir . '/header.html.twig', [
                    'agency' => $agency
                ]));

            $snappy->setOption('footer-html',
                $this->container->get('templating')->render($templateDir . '/footer.html.twig', [
                    'agency' => $agency
                ]));

            if (!isset($templateDir) || !$templateDir || is_null($templateDir)) {
                return false;
            }

            $products = $this->productManager->getAvailableProducts($quoteRequest->getCatalog());

            $monthlyCoefficientValues = $this->container->getParameter('paprec.frequency_interval.monthly_coefficients');

            /**
             * On génère la page d'offre
             */
            $snappy->generateFromHtml(
                array(
                    $this->container->get('templating')->render(
                        $templateDir . '/printQuoteOffer.html.twig',
                        array(
                            'quoteRequest' => $quoteRequest,
                            'date' => $today,
                            'locale' => $locale,
                            'products' => $products,
                            'monthlyCoefValues' => $monthlyCoefficientValues
                        )
                    )
                ),
                $filenameOffer
            );

            /**
             * Concaténation des fichiers
             */
            $pdfArray = array();

            $ponctualFileNames = $this->container->getParameter('paprec.file_names');
            $ponctualFileCovers = $this->container->getParameter('paprec.cover_file_names');
            $ponctualFileDirectory = $this->container->getParameter('paprec.files_directory');

            /**
             * Ajout de(s) page(s) de garde
             */
            if (is_array($ponctualFileCovers) && count($ponctualFileCovers)) {
                foreach ($ponctualFileCovers as $ponctualFileCover) {
                    $noticeFilename = $ponctualFileDirectory . '/' . $ponctualFileCover . '.pdf';
                    if (file_exists($noticeFilename)) {
                        $pdfArray[] = $noticeFilename;
                    }
                }
            }

            /**
             * Ajout de l'offre généré
             */
            $pdfArray[] = $filenameOffer;


            if (is_array($ponctualFileNames) && count($ponctualFileNames)) {
                foreach ($ponctualFileNames as $ponctualFileName) {
                    $noticeFilename = $ponctualFileDirectory . '/' . $ponctualFileName . '.pdf';
                    if (file_exists($noticeFilename)) {
                        $pdfArray[] = $noticeFilename;
                    }
                }
            }

            if (count($pdfArray)) {
                $merger = new Merger();
                $merger->addIterator($pdfArray);
                file_put_contents($filename, $merger->merge());
            }

            if (!file_exists($filename)) {
                return false;
            }

            return $filename;

        } catch (ORMException $e) {
            throw new Exception('unableToGenerateProductQuote', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Génère la feuille de mission et l'envoi
     *
     * @param QuoteRequest $quoteRequest
     * @param MissionSheet $missionSheet
     * @return bool|string
     * @throws Exception
     */
    public function sendGenerateMissionSheetPDF(QuoteRequest $quoteRequest, MissionSheet $missionSheet, $locale)
    {
        try {
            $from = $_ENV['PAPREC_EMAIL_SENDER'];

            $agencyMail = null;
            $commercialMail = null;
            if ($quoteRequest->getUserInCharge()) {
                $commercialMail = $quoteRequest->getUserInCharge()->getEmail();
                if ($quoteRequest->getUserInCharge()->getAgency()) {
                    $agencyMail = $quoteRequest->getUserInCharge()->getAgency()->getDestinationEmailMission();
                } else {
                    return false;
                }
            } else {
                return false;
            }

            if ($agencyMail == null || $agencyMail == '' || $commercialMail == null || $commercialMail == '') {
                return false;
            }

            /**
             * Contenu du mail
             */
            $message = new Swift_Message();
            $message
                ->setSubject($this->translator->trans('Commercial.GeneratedMissionSheetEmail.Object',
                    array(), 'messages', strtolower($locale)))
                ->setFrom($from)
                ->setTo($agencyMail)
                ->setCc($commercialMail)
                ->setBody(
                    $this->container->get('templating')->render(
                        'public/emails/generatedMissionSheetEmail.html.twig',
                        array(
                            'quoteRequest' => $quoteRequest,
                            'locale' => strtolower($locale)
                        )
                    ),
                    'text/html'
                );

            $pdfTmpFolder = $this->container->getParameter('paprec.data_tmp_directory');

            if (!is_dir($pdfTmpFolder)) {
                if (!mkdir($pdfTmpFolder, 0755, true) && !is_dir($pdfTmpFolder)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $pdfTmpFolder));
                }
            }

            $pdfTmpFolder .= '/' . 'missionSheet';

            $filename = $pdfTmpFolder . '/' . md5(uniqid('', true)) . '.pdf';
            $filenameMissionSheet = $pdfTmpFolder . '/' . md5(uniqid('', true)) . '.pdf';

            $today = new \DateTime();

            $snappy = new Pdf($_ENV['WKHTMLTOPDF_PATH']);
            $snappy->setOption('zoom', 0.78);

            $templateDir = 'public/PDF/missionSheet';

            $snappy->setOption('header-html',
                $this->container->get('templating')->render($templateDir . '/header.html.twig'));
            $snappy->setOption('footer-html',
                $this->container->get('templating')->render($templateDir . '/footer.html.twig'));

            if (!isset($templateDir) || !$templateDir || is_null($templateDir)) {
                return false;
            }

            /**
             * Concaténation des fichiers
             */
            $pdfArray = [];

            /**
             * On génère la page CREATION
             */
            $snappy->generateFromHtml(
                array(
                    $this->container->get('templating')->render(
                        $templateDir . '/printMissionSheet.html.twig',
                        array(
                            'quoteRequest' => $quoteRequest,
                            'quoteRequestLines' => $quoteRequest->getQuoteRequestLines(),
                            'missionSheet' => $missionSheet,
                            'date' => $today,
                            'locale' => $locale
                        )
                    )
                ),
                $filenameMissionSheet
            );
            $downloadedFileName = 'FM';
            $downloadedFileName .= ' ' . $today->format('o');
            $downloadedFileName .= ' ' . $today->format('m');
            $downloadedFileName .= ' ' . $today->format('d');
            if ($quoteRequest->getUserInCharge()) {
                $downloadedFileName .= ' ' . $quoteRequest->getUserInCharge()->getNickname();
            }
            if ($quoteRequest->getMissionSheet()) {
                if ($quoteRequest->getMissionSheet()->getMnemonicNumber()) {
                    $downloadedFileName .= ' ' . $quoteRequest->getMissionSheet()->getMnemonicNumber();
                }
                if ($quoteRequest->getMissionSheet()->getContractNumber()) {
                    $downloadedFileName .= ' ' . $quoteRequest->getMissionSheet()->getContractNumber();
                }
            }
            $downloadedFileName .= '.pdf';

            $message->attach(new \Swift_Attachment(file_get_contents($filenameMissionSheet), $downloadedFileName,
                'application/pdf'));

            $quoteRequestLinesByAgency = [];
            $agenciesById = [];
            if (count($quoteRequest->getQuoteRequestLines())) {
                foreach ($quoteRequest->getQuoteRequestLines() as $qRL) {
                    if ($qRL->getAgency()) {
                        if (!array_key_exists($qRL->getAgency()->getId(), $quoteRequestLinesByAgency)) {
                            $quoteRequestLinesByAgency[$qRL->getAgency()->getId()] = [];
                            $agenciesById[$qRL->getAgency()->getId()] = $qRL->getAgency();
                        }
                        $quoteRequestLinesByAgency[$qRL->getAgency()->getId()][] = $qRL;
                    }
                }

                foreach ($quoteRequestLinesByAgency as $agencyId => $quoteRequestLines) {
                    $agency = $agenciesById[$agencyId];
                    if (file_exists($this->container->get('kernel')->getProjectDir() . '/templates/' . $templateDir . '/exploitation')) {

//                        $rcptTo = $agency->getDestinationEmailMission();
//                        $message = new Swift_Message();
//                        $message
//                            ->setSubject($this->translator->trans('Commercial.GeneratedMissionSheetEmail.Object',
//                                array(), 'messages', strtolower($locale)))
//                            ->setFrom($from)
//                            ->setTo($rcptTo)
//                            ->setBody(
//                                $this->container->get('templating')->render(
//                                    'public/emails/generatedMissionSheetEmail.html.twig',
//                                    array(
//                                        'quoteRequest' => $quoteRequest,
//                                        'locale' => strtolower($locale)
//                                    )
//                                ),
//                                'text/html'
//                            );

                        $dir = $templateDir . '/exploitation';
                        $actualFilename = $pdfTmpFolder . '/exploitation/' . md5(uniqid('', true)) . '.pdf';

                        $sheetName = '';
                        $pdfExploitName = 'Exploitation';
                        $logoName = '';
                        if ($agency->getTemplate() === 'LCB') {
                            $sheetName = 'Exploitation LCB';
                            $logoName = 'logo-lcb.jpg';
                        } elseif ($agency->getTemplate() === 'CFS') {
                            $sheetName = 'Exploitation CFS';
                            $logoName = 'logo-confidentialys.jpg';
                        } elseif ($agency->getTemplate() === 'LPP') {
                            $sheetName = 'Navette LPP';
                            $pdfExploitName = 'Navette';
                            $logoName = 'logo-lpp.png';
                        } elseif ($agency->getTemplate() === 'PAPREC') {
                            $sheetName = 'Navette IDF';
                            $pdfExploitName = 'Navette';
                            $logoName = 'logo-paprec.png';
                        } elseif ($agency->getTemplate() === 'IN_SITU') {
                            $sheetName = 'Navette In Situ';
                            $pdfExploitName = 'Navette';
                            $logoName = 'logo-insitu.png';
                        }

                        $snappy->generateFromHtml(
                            array(
                                $this->container->get('templating')->render(
                                    $dir . '/printMissionSheet.html.twig',
                                    array(
                                        'quoteRequest' => $quoteRequest,
                                        'logoName' => $logoName,
                                        'sheetName' => $sheetName,
                                        'quoteRequestLines' => $quoteRequestLines,
                                        'missionSheet' => $missionSheet,
                                        'date' => $today,
                                        'locale' => $locale
                                    )
                                )
                            ),
                            $actualFilename
                        );

//                        echo $this->container->get('templating')->render(
//                            $dir . '/printMissionSheet.html.twig',
//                            array(
//                                'quoteRequest' => $quoteRequest,
//                                'sheetName' => $sheetName,
//                                'quoteRequestLines' => $quoteRequest->getQuoteRequestLines(),
//                                'missionSheet' => $missionSheet,
//                                'date' => $today,
//                                'locale' => $locale
//                            )
//                        );
//                        exit;


                        $downloadedFileName = 'FM';
                        $downloadedFileName .= ' ' . $today->format('o');
                        $downloadedFileName .= ' ' . $today->format('m');
                        $downloadedFileName .= ' ' . $today->format('d');
                        if ($quoteRequest->getUserInCharge()) {
                            $downloadedFileName .= ' ' . $quoteRequest->getUserInCharge()->getNickname();
                        }
                        if ($quoteRequest->getMissionSheet()) {
                            if ($quoteRequest->getMissionSheet()->getMnemonicNumber()) {
                                $downloadedFileName .= ' ' . $quoteRequest->getMissionSheet()->getMnemonicNumber();
                            }
                            if ($quoteRequest->getMissionSheet()->getContractNumber()) {
                                $downloadedFileName .= ' ' . $quoteRequest->getMissionSheet()->getContractNumber();
                            }
                        }
                        $downloadedFileName .= ' ' . $pdfExploitName;
                        $downloadedFileName .= ' ' . $agency->getName();
                        $downloadedFileName .= '.pdf';

                        $message->attach(new \Swift_Attachment(file_get_contents($actualFilename), $downloadedFileName,
                            'application/pdf'));
                    }
                }
            }

            $pdfFilename = $quoteRequest->getReference() . '-' . $this->translator->trans('Commercial.NewContractEmail.FileName',
                    array(), 'messages', strtolower($locale)) . '-' . $quoteRequest->getBusinessName() . '.pdf';

            $pdfFile = $this->generatePDF($quoteRequest, strtolower($locale), true);

            $message->attach(new \Swift_Attachment(file_get_contents($pdfFile), $pdfFilename,
                'application/pdf'));

            $return = true;
            if (!$this->container->get('mailer')->send($message)) {
                $return = false;
            }

            return $return;

        } catch (ORMException $e) {
            throw new Exception('unableToGenerateProductQuote', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Génère la feuille de mission
     *
     * @param QuoteRequest $quoteRequest
     * @param MissionSheet $missionSheet
     * @return bool|string
     * @throws Exception
     */
    public function generateMissionSheetPDF(QuoteRequest $quoteRequest, MissionSheet $missionSheet, $locale)
    {
        try {

            $pdfTmpFolder = $this->container->getParameter('paprec.data_tmp_directory');

            if (!is_dir($pdfTmpFolder)) {
                if (!mkdir($pdfTmpFolder, 0755, true) && !is_dir($pdfTmpFolder)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $pdfTmpFolder));
                }
            }

            $pdfTmpFolder .= '/' . 'missionSheet';
            $filenameMissionSheet = $pdfTmpFolder . '/' . md5(uniqid('', true)) . '.pdf';

            $today = new \DateTime();

            $snappy = new Pdf($_ENV['WKHTMLTOPDF_PATH']);
            $snappy->setOption('zoom', 0.78);

            $templateDir = 'public/PDF/missionSheet';

            $snappy->setOption('header-html',
                $this->container->get('templating')->render($templateDir . '/header.html.twig'));
            $snappy->setOption('footer-html',
                $this->container->get('templating')->render($templateDir . '/footer.html.twig'));

            if (!isset($templateDir) || !$templateDir || is_null($templateDir)) {
                return false;
            }

            $pdfs = [];
            /**
             * On génère la page CREATION
             */
            $pdfs[] = $this->container->get('templating')->render(
                $templateDir . '/printMissionSheet.html.twig',
                array(
                    'quoteRequest' => $quoteRequest,
                    'quoteRequestLines' => $quoteRequest->getQuoteRequestLines(),
                    'missionSheet' => $missionSheet,
                    'date' => $today,
                    'locale' => $locale
                )
            );

            $quoteRequestLinesByAgency = [];
            $agenciesById = [];
            if (count($quoteRequest->getQuoteRequestLines())) {
                foreach ($quoteRequest->getQuoteRequestLines() as $qRL) {
                    if ($qRL->getAgency()) {
                        if (!array_key_exists($qRL->getAgency()->getId(), $quoteRequestLinesByAgency)) {
                            $quoteRequestLinesByAgency[$qRL->getAgency()->getId()] = [];
                            $agenciesById[$qRL->getAgency()->getId()] = $qRL->getAgency();
                        }
                        $quoteRequestLinesByAgency[$qRL->getAgency()->getId()][] = $qRL;
                    }
                }

                foreach ($quoteRequestLinesByAgency as $agencyId => $quoteRequestLines) {
                    $agency = $agenciesById[$agencyId];
                    if (file_exists($this->container->get('kernel')->getProjectDir() . '/templates/' . $templateDir . '/exploitation')) {
                        $dir = $templateDir . '/exploitation';
                        $actualFilename = $pdfTmpFolder . '/exploitation/' . md5(uniqid('', true)) . '.pdf';

                        $sheetName = '';
                        $pdfExploitName = 'Exploitation';
                        $logoName = '';
                        if ($agency->getTemplate() === 'LCB') {
                            $sheetName = 'Exploitation LCB';
                            $logoName = 'logo-lcb.jpg';
                        } elseif ($agency->getTemplate() === 'CFS') {
                            $sheetName = 'Exploitation CFS';
                            $logoName = 'logo-confidentialys.jpg';
                        } elseif ($agency->getTemplate() === 'LPP') {
                            $sheetName = 'Navette LPP';
                            $pdfExploitName = 'Navette';
                            $logoName = 'logo-lpp.png';
                        } elseif ($agency->getTemplate() === 'PAPREC') {
                            $sheetName = 'Navette IDF';
                            $pdfExploitName = 'Navette';
                            $logoName = 'logo-paprec.png';
                        } elseif ($agency->getTemplate() === 'IN_SITU') {
                            $sheetName = 'Navette In Situ';
                            $pdfExploitName = 'Navette';
                            $logoName = 'logo-insitu.png';
                        }

                        $pdfs[] = $this->container->get('templating')->render(
                            $dir . '/printMissionSheet.html.twig',
                            array(
                                'quoteRequest' => $quoteRequest,
                                'logoName' => $logoName,
                                'sheetName' => $sheetName,
                                'quoteRequestLines' => $quoteRequestLines,
                                'missionSheet' => $missionSheet,
                                'date' => $today,
                                'locale' => $locale
                            )
                        );
                    }
                }
            }

            $snappy->generateFromHtml(
                $pdfs,
                $filenameMissionSheet
            );

            if (!file_exists($filenameMissionSheet)) {
                return false;
            }

            return $filenameMissionSheet;

        } catch (ORMException $e) {
            throw new Exception('unableToGenerateProductQuote', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getMissionSheet($missionSheet, $throwException = true)
    {
        $id = $missionSheet;
        if ($missionSheet instanceof MissionSheet) {
            $id = $missionSheet->getId();
        }
        try {

            $missionSheet = $this->em->getRepository('App:MissionSheet')->find($id);

            /**
             * Vérification que le quoteRequest existe ou ne soit pas supprimé
             */
            if ($missionSheet === null || $this->missionSheetIsDeleted($missionSheet)) {
                throw new EntityNotFoundException('missionSheetNotFound');
            }


            return $missionSheet;

        } catch (Exception $e) {
            if ($throwException) {
                throw new Exception($e->getMessage(), $e->getCode());
            } else {
                return null;
            }
        }
    }

    /**
     * Vérifie qu'à ce jour, le missionSheet ce soit pas supprimée
     *
     * @param MissionSheet $missionSheet
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function missionSheetIsDeleted(MissionSheet $missionSheet, $throwException = false)
    {
        $now = new \DateTime();

        if ($missionSheet->getDeleted() !== null && $missionSheet->getDeleted() instanceof \DateTime && $missionSheet->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('missionSheetNotFound');
            }

            return true;

        }
        return false;
    }

}
