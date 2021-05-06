<?php

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\ORMException;
use Exception;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use iio\libmergepdf\Merger;
use Knp\Snappy\Pdf;
use App\Entity\QuoteRequest;
use App\Entity\QuoteRequestLine;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuoteRequestManager
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function get($quoteRequest, $throwException = true)
    {
        $id = $quoteRequest;
        if ($quoteRequest instanceof QuoteRequest) {
            $id = $quoteRequest->getId();
        }
        try {

            $quoteRequest = $this->em->getRepository('PaprecCommercialBundle:QuoteRequest')->find($id);

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
        $qb = $this->em->getRepository('PaprecCommercialBundle:QuoteRequest')->createQueryBuilder('qr')
            ->select('count(qr)')
            ->where('qr.reference LIKE :ref')
            ->andWhere('qr.deleted IS NULL')
            ->setParameter('ref', $reference . '%');

        $count = $qb->getQuery()->getSingleScalarResult();

        if ($count != null) {
            return intval($count) + 1;
        } else {
            return 1;
        }
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

            $quoteRequest = $this->em->getRepository('PaprecCommercialBundle:QuoteRequest')->findOneBy(
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
        $numberManager = $this->container->get('paprec_catalog.number_manager');
        $productManager = $this->container->get('paprec_catalog.product_manager');

        // On check s'il existe déjà une ligne pour ce produit, pour l'incrémenter
        $currentQuoteLine = $this->em->getRepository('PaprecCommercialBundle:QuoteRequestLine')->findOneBy(
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
            $this->em->flush();
        } else {
            $quoteRequestLine->setQuoteRequest($quoteRequest);
            $quoteRequest->addQuoteRequestLine($quoteRequestLine);

            $quoteRequestLine->setRentalUnitPrice($quoteRequestLine->getProduct()->getRentalUnitPrice());
            $quoteRequestLine->setTransportUnitPrice($quoteRequestLine->getProduct()->getTransportUnitPrice());
            $quoteRequestLine->setTreatmentUnitPrice($quoteRequestLine->getProduct()->getTreatmentUnitPrice());
            $quoteRequestLine->setTraceabilityUnitPrice($quoteRequestLine->getProduct()->getTraceabilityUnitPrice());
            $quoteRequestLine->setProductName($quoteRequestLine->getProduct()->getId());

            /**
             * Si codePostal, on récupère tous les coefs de celui-ci et on les affecte au quoteRequestLine
             */
            if ($quoteRequest->getPostalCode()) {
                $quoteRequestLine->setRentalRate($quoteRequest->getPostalCode()->getRentalRate());
                $quoteRequestLine->setTransportRate($quoteRequest->getPostalCode()->getTransportRate());
                $quoteRequestLine->setTreatmentRate($quoteRequest->getPostalCode()->getTreatmentRate());
                $quoteRequestLine->setTraceabilityRate($quoteRequest->getPostalCode()->getTraceabilityRate());
            } else {
                /**
                 * Si pas de code postal, on met tous les coefs à 1 par défaut
                 */
                $quoteRequestLine->setRentalRate($numberManager->normalize15(1));
                $quoteRequestLine->setTransportRate($numberManager->normalize15(1));
                $quoteRequestLine->setTreatmentRate($numberManager->normalize15(1));
                $quoteRequestLine->setTraceabilityRate($numberManager->normalize15(1));
            }

            /**
             * Si il y a une condition d'accès, on l'affecte au quoteRequestLine
             */
            if ($quoteRequest->getAccess()) {
                $quoteRequestLine->setAccessPrice($numberManager->normalize($productManager->getAccesPrice($quoteRequest)));
            } else {
                /**
                 * Sinon on lui met à 0 par défaut
                 */
                $quoteRequestLine->setAccessPrice(0);
            }

            /**
             * si le transportUnitPrice de la quoteRequestLine est inférieur à minTransportUnitPrice de la quoteRequest
             * alors on met le transportUnitPrice comme minTransportUnitPrice
             */
            if ($quoteRequest->getMinTransportUnitPrice() === 0 || $quoteRequestLine->getTransportUnitPrice() > $quoteRequest->getMinTransportUnitPrice()) {
                $quoteRequest->setMinTransportUnitPrice($quoteRequestLine->getTransportUnitPrice());
            }

            $this->em->persist($quoteRequestLine);

            /**
             * On recalcule le montant total de la ligne ainsi que celui du devis complet
             */
            $totalLine = 0 + $this->calculateTotalLine($quoteRequestLine);
            $quoteRequestLine->setTotalAmount($totalLine);
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
    public function addLineFromCart(QuoteRequest $quoteRequest, $productId, $qtty, $doFlush = true)
    {
        $productManager = $this->container->get('paprec_catalog.product_manager');

        try {
            $product = $productManager->get($productId);
            $quoteRequestLine = new QuoteRequestLine();

            $quoteRequestLine->setProduct($product);
            $quoteRequestLine->setQuantity($qtty);
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
     * @throws Exception
     */
    public function editLine(
        QuoteRequest $quoteRequest,
        QuoteRequestLine $quoteRequestLine,
        $user,
        $doFlush = true,
        $editQuoteRequest = true
    ) {
        $numberManager = $this->container->get('paprec_catalog.number_manager');
        $productManager = $this->container->get('paprec_catalog.product_manager');

        $now = new \DateTime();

        $totalLine = 0 + $this->calculateTotalLine($quoteRequestLine);
        $quoteRequestLine->setTotalAmount($totalLine);
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
            $quoteRequestLine->setAccessPrice($numberManager->normalize($productManager->getAccesPrice($quoteRequest)));
        } else {
            /**
             * Sinon on lui met à 0 par défaut
             */
            $quoteRequestLine->setAccessPrice(0);
        }

        /**
         * si le transportUnitPrice de la quoteRequestLine est inférieur à minTransportUnitPrice de la quoteRequest
         * alors on met le transportUnitPrice comme minTransportUnitPrice
         */
        if ($quoteRequest->getMinTransportUnitPrice() === 0 || $quoteRequestLine->getTransportUnitPrice() > $quoteRequest->getMinTransportUnitPrice()) {
            $quoteRequest->setMinTransportUnitPrice($quoteRequestLine->getTransportUnitPrice());
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
        $numberManager = $this->container->get('paprec_catalog.number_manager');
        $productManager = $this->container->get('paprec_catalog.product_manager');

        return $numberManager->normalize(
            $productManager->calculatePrice($quoteRequestLine)
        );
    }

    /**
     * Calcule le montant total d'un QuoteRequest
     * @param QuoteRequest $quoteRequest
     * @return float|int
     */
    public function calculateTotal(QuoteRequest $quoteRequest)
    {
        $numberManager = $this->container->get('paprec_catalog.number_manager');

        /**
         * A chaque fois que l'on calcule on recheck le transportUnitPrice minimum parmis tous les produits
         */

        $totalAmount = 0;
        if ($quoteRequest->getQuoteRequestLines() && count($quoteRequest->getQuoteRequestLines())) {

            foreach ($quoteRequest->getQuoteRequestLines() as $quoteRequestLine) {
                $totalAmount += $quoteRequestLine->getTotalAmount();
            }
        }
        /**
         * On ajoute ensuite le prix de transport
         */
        $totalAmount += $numberManager->normalize($numberManager->denormalize($quoteRequest->getMinTransportUnitPrice()) * $numberManager->denormalize15($quoteRequest->getTransportRate()) * (1 + $numberManager->denormalize($quoteRequest->getOverallDiscount() / 100)));

        return $totalAmount;
    }


    /**
     * Envoie un mail à la personne ayant fait une demande de devis
     * @throws Exception
     */
    public function sendConfirmRequestEmail(QuoteRequest $quoteRequest)
    {

        try {
            $from = $this->container->getParameter('paprec_email_sender');
            $this->get($quoteRequest);

            $rcptTo = $quoteRequest->getEmail();

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

            if (!$quoteRequest->getPostalCode()) {
                return false;
            }

            $locale = 'FR';

            $translator = $this->container->get('translator');

            $message = \Swift_Message::newInstance()
                ->setSubject($translator->trans('Commercial.ConfirmEmail.Object',
                    array(), 'messages', strtolower($locale)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        '@PaprecCommercial/QuoteRequest/emails/confirmQuoteEmail.html.twig',
                        array(
                            'quoteRequest' => $quoteRequest,
                            'locale' => strtolower($locale),
                            'salesman' => $quoteRequest->getPostalCode()->getAgency()->getSalesman(),
                            'assistant' => $quoteRequest->getPostalCode()->getAgency()->getAssistant()
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
     * Envoie un mail au commercial associé lui indiquant la nouvelle demande de devis créée
     * @throws Exception
     */
    public function sendNewRequestEmail(QuoteRequest $quoteRequest)
    {

        try {
            $from = $this->container->getParameter('paprec_email_sender');
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
                    $rcptTo = $this->container->getParameter('privacia_salesman_multisite_email');
                } else {
                    // TODO
//                    $rcptTo = $quoteRequest->getPostalCode()->getRegion()->getEmail();
                }
            }

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

            $locale = 'FR';

            $message = \Swift_Message::newInstance()
                ->setSubject(
                    $translator->trans(
                        'Commercial.NewQuoteEmail.Object',
                        array('%number%' => $quoteRequest->getId()), 'messages', strtolower($locale)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        '@PaprecCommercial/QuoteRequest/emails/newQuoteEmail.html.twig',
                        array(
                            'quoteRequest' => $quoteRequest,
                            'locale' => strtolower($locale)
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
            $from = $this->container->getParameter('paprec_email_sender');


            $rcptTo = $quoteRequest->getEmail();

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

            $localeFilename = 'FR';

            $pdfFilename = $quoteRequest->getReference() . '-' . $this->container->get('translator')->trans('Commercial.GeneratedQuoteEmail.FileName',
                    array(), 'messages', strtolower($localeFilename)) . '-' . $quoteRequest->getBusinessName() . '.pdf';

            $pdfFile = $this->generatePDF($quoteRequest, strtolower($localeFilename), false);

            if (!$pdfFile) {
                return false;
            }

            $attachment = \Swift_Attachment::newInstance(file_get_contents($pdfFile), $pdfFilename, 'application/pdf');

            $translator = $this->container->get('translator');

            /**
             * Génération du Token de la quoteRequest s'il n'y en a pas
             */
            if (!$quoteRequest->getToken()) {
                $token = $this->generateToken();
                $quoteRequest->setToken($token);
                $this->em->flush();
            }

            $message = \Swift_Message::newInstance()
                ->setSubject($translator->trans('Commercial.GeneratedQuoteEmail.Object',
                    array(), 'messages', strtolower($localeFilename)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        '@PaprecCommercial/QuoteRequest/emails/generatedQuoteEmail.html.twig',
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
            $from = $this->container->getParameter('paprec_email_sender');


            $rcptTo = $quoteRequest->getEmail();

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

            $localeFilename = 'FR';

            $pdfFilename = $quoteRequest->getReference() . '-' . $this->container->get('translator')->trans('Commercial.GeneratedContractEmail.FileName',
                    array(), 'messages', strtolower($localeFilename)) . '-' . $quoteRequest->getBusinessName() . '.pdf';

            $pdfFile = $this->generatePDF($quoteRequest, strtolower($localeFilename), true);

            if (!$pdfFile) {
                return false;
            }

            $attachment = \Swift_Attachment::newInstance(file_get_contents($pdfFile), $pdfFilename, 'application/pdf');

            $translator = $this->container->get('translator');

            $message = \Swift_Message::newInstance()
                ->setSubject($translator->trans('Commercial.GeneratedContractEmail.Object',
                    array(), 'messages', strtolower($localeFilename)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        '@PaprecCommercial/QuoteRequest/emails/generatedContractEmail.html.twig',
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
     * Envoi du contrat généré au commercial pour l'informer que l'utilisateur a reçu son contrat
     *
     * @param QuoteRequest $quoteRequest
     * @return bool
     * @throws Exception
     */
    public function sendNewContractEmail(QuoteRequest $quoteRequest)
    {
        try {
            $from = $this->container->getParameter('paprec_email_sender');


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
                    $rcptTo = $this->container->getParameter('privacia_salesman_multisite_email');
                } else {
                    // TODO
//                    $rcptTo = $quoteRequest->getPostalCode()->getRegion()->getEmail();
                }
            }

            if ($rcptTo == null || $rcptTo == '') {
                return false;
            }

            $localeFilename = 'FR';

            $pdfFilename = $quoteRequest->getReference() . '-' . $this->container->get('translator')->trans('Commercial.NewContractEmail.FileName',
                    array(), 'messages', strtolower($localeFilename)) . '-' . $quoteRequest->getBusinessName() . '.pdf';

            $pdfFile = $this->generatePDF($quoteRequest, strtolower($localeFilename), true);

            if (!$pdfFile) {
                return false;
            }

            $attachment = \Swift_Attachment::newInstance(file_get_contents($pdfFile), $pdfFilename, 'application/pdf');

            $translator = $this->container->get('translator');

            $message = \Swift_Message::newInstance()
                ->setSubject($translator->trans('Commercial.NewContractEmail.Object',
                    array('%number%' => $quoteRequest->getId()), 'messages', strtolower($localeFilename)))
                ->setFrom($from)
                ->setTo($rcptTo)
                ->setBody(
                    $this->container->get('templating')->render(
                        '@PaprecCommercial/QuoteRequest/emails/newContractEmail.html.twig',
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

            $pdfTmpFolder = $this->container->getParameter('paprec_commercial.data_tmp_directory');

            if (!is_dir($pdfTmpFolder)) {
                if (!mkdir($pdfTmpFolder, 0755, true) && !is_dir($pdfTmpFolder)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $pdfTmpFolder));
                }
            }


            $filename = $pdfTmpFolder . '/' . md5(uniqid('', true)) . '.pdf';
            $filenameOffer = $pdfTmpFolder . '/' . md5(uniqid('', true)) . '.pdf';
            $filenameContract = $pdfTmpFolder . '/' . md5(uniqid('', true)) . '.pdf';


            $dateEndOffer = clone(($quoteRequest->getDateUpdate()) ?: $quoteRequest->getDateCreation());
            $dateEndOffer = $dateEndOffer->modify('+1 year');
            $dateEndOffer = $dateEndOffer->modify('-1 day');

            $today = new \DateTime();


            $snappy = new Pdf($this->container->getParameter('wkhtmltopdf_path'));
            $snappy->setOption('javascript-delay', 3000);
            $snappy->setTimeout(600);
            $snappy->setOption('dpi', 72);
            $snappy->setOption('zoom', 0.92);

//            $snappy->setOption('footer-html', $this->container->get('templating')->render('@PaprecCommercial/QuoteRequest/PDF/fr/_footer.html.twig'));

            $templateDir = '@PaprecCommercial/QuoteRequest/PDF/';


            if (!isset($templateDir) || !$templateDir || is_null($templateDir)) {
                return false;
            }

            if ($quoteRequest->getType() === 'REGULAR') {
                $templateDir .= 'regular/';
            } else {
                $templateDir .= 'ponctual/';
            }

            $productManager = $this->container->get('paprec_catalog.product_manager');
            $products = $productManager->getAvailableProducts();

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
                            'products' => $products
                        )
                    )
                ),
                $filenameOffer
            );

            /**
             * Concaténation des fichiers
             */
            $pdfArray = array();

            $ponctualFileNames = $this->container->getParameter('paprec_commercial.file_names');
            $ponctualFileCovers = $this->container->getParameter('paprec_commercial.cover_file_names');
            $ponctualFileDirectory = $this->container->getParameter('paprec_commercial.files_directory');

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

            if ($addContract) {
                /**
                 * On génère la page de contract
                 */
                $snappy->generateFromHtml(
                    array(
                        $this->container->get('templating')->render(
                            $templateDir . '/printQuoteContract.html.twig',
                            array(
                                'quoteRequest' => $quoteRequest,
                                'date' => $today,
                                'dateEndOffer' => $dateEndOffer,
                                'products' => $products
                            )
                        )
                    ),
                    $filenameContract
                );

                $pdfArray[] = $filenameContract;

            }


            if (count($pdfArray)) {
                $merger = new Merger();
                $merger->addIterator($pdfArray);
                file_put_contents($filename, $merger->merge());
            }

            if (file_exists($filenameContract)) {
                unlink($filenameContract);
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

}
