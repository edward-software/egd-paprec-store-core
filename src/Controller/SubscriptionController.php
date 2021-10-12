<?php

namespace App\Controller;


use App\Form\QuoteRequestPublicType;
use App\Service\CartManager;
use App\Service\OtherNeedManager;
use App\Service\PostalCodeManager;
use App\Service\ProductManager;
use App\Service\QuoteRequestManager;
use App\Service\RangeManager;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubscriptionController extends AbstractController
{

    private $em;
    private $cartManager;
    private $productManager;
    private $rangeManager;
    private $otherNeedManager;
    private $quoteRequestManager;
    private $postalCodeManager;
    private $userManager;

    public function __construct(
        EntityManagerInterface $em,
        CartManager $cartManager,
        ProductManager $productManager,
        RangeManager $rangeManager,
        OtherNeedManager $otherNeedManager,
        QuoteRequestManager $quoteRequestManager,
        PostalCodeManager $postalCodeManager,
        UserManager $userManager
    ) {
        $this->em = $em;
        $this->cartManager = $cartManager;
        $this->productManager = $productManager;
        $this->rangeManager = $rangeManager;
        $this->otherNeedManager = $otherNeedManager;
        $this->postalCodeManager = $postalCodeManager;
        $this->quoteRequestManager = $quoteRequestManager;
        $this->userManager = $userManager;
    }

    /**
     * @Route("/", name="paprec_public_devis_index")
     * @Route("/{locale}/step0", name="paprec_public_devis_step0_index")
     * @return RedirectResponse
     */
    public function redirectToIndex0Action(Request $request): RedirectResponse
    {
        return $this->redirectToRoute('paprec_public_devis_home', array('locale' => 'fr'));
    }

//    /**
//     * @Route("/{locale}", name="paprec_public_devis_home")
//     * @param Request $request
//     * @return \Symfony\Component\HttpFoundation\RedirectResponse
//     */
//    public function redirectToIndexAction(Request $request, $locale)
//    {
//        return $this->redirectToRoute('paprec_public_type_index', array('locale' => 'fr'));
//
//    }

    /**
     * Page de sélection du type de besoin: Régulier ou archivage unique
     *
     * @Route("/{locale}", name="paprec_public_devis_home")
     * @param Request $request
     * @param $locale
     * @return RedirectResponse|Response
     */
    public function typeSelectionAction(Request $request, $locale)
    {
        if ($locale !== 'fr') {
            return $this->redirectToRoute('paprec_public_devis_home', array('locale' => 'fr'));
        }
        return $this->render('public/type.html.twig', array(
            'locale' => $locale
        ));
    }

    /**
     * Endpoint de définition du type de besoin,
     * En fonction du type :
     * - Regular => redirige vers la page Catalog
     * - Single => redirige vers la page Contact
     *
     * @Route("/{locale}/defineType/{type}", name="paprec_public_define_type")
     * @param Request $request
     * @param $locale
     * @param $type
     * @return RedirectResponse|Response
     */
    public function defineTypeAction(Request $request, $locale, $type)
    {
        try {
            $cart = $this->cartManager->create(90);
            $this->em->persist($cart);
            $cart->setType($type);
            $this->em->flush();

            if ($type === 'PONCTUAL') {
                return $this->redirectToRoute('paprec_public_ponctual_catalog_index', array(
                    'locale' => 'fr',
                    'cartUuid' => $cart->getId()
                ));
            }

            return $this->redirectToRoute('paprec_public_regular_catalog_index', array(
                'locale' => 'fr',
                'cartUuid' => $cart->getId()
            ));

        } catch (Exception $e) {
            return new JsonResponse(array('error' => $e->getMessage()), 400);
        }
    }


    /**
     * @Route("/{locale}/regulier/catalogue/{cartUuid}", defaults={"cartUuid"=null}, name="paprec_public_regular_catalog_index")
     * @Route("/{locale}/ponctuel/catalogue/{cartUuid}", defaults={"cartUuid"=null}, name="paprec_public_ponctual_catalog_index")
     * @param Request $request
     * @param $locale
     * @param $cartUuid
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function catalogAction(Request $request, $locale, $cartUuid)
    {
        /**
         * Si pas de cart id, on redirige vers la home
         */
        if (!$cartUuid) {
            return $this->redirectToRoute('paprec_public_devis_home', array(
                'locale' => $locale
            ));
        }

        /**
         * Si cart id mais le cart est introuvable ou n'a pas de type défini
         * on redirige vers la home
         */
        $cart = $this->cartManager->get($cartUuid);

        if (!$cart || !$cart->getType()) {
            return $this->redirectToRoute('paprec_public_devis_home', array(
                'locale' => $locale
            ));
        }

//        $products = $this->productManager->getAvailableProducts($cart->getType());
        $rangesQb = $this->rangeManager->getAvailableRanges(false, $cart->getType());
        $ranges = $this->rangeManager->addAvailableProducts($rangesQb, true);


        $otherNeeds = $this->otherNeedManager->getByLocaleAndCatalog($locale, $cart->getType());

        if ($cart->getType() === 'REGULAR') {


            return $this->render('public/catalog-regular.html.twig', array(
                'locale' => $locale,
                'cart' => $cart,
                'ranges' => $ranges,
                'otherNeeds' => $otherNeeds
            ));
        } else {
            return $this->render('public/catalog-ponctual.html.twig', array(
                'locale' => $locale,
                'cart' => $cart,
                'ranges' => $ranges,
                'otherNeeds' => $otherNeeds
            ));
        }
    }

    /**
     * @Route("/{locale}/regulier/catalogue/contact/{cartUuid}",  name="paprec_public_contact_regulier_index")
     * @Route("/{locale}/ponctuel/contact/{cartUuid}",  name="paprec_public_contact_ponctuel_index")
     * @param Request $request
     * @param $locale
     * @param $cartUuid
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function contactDetailAction(Request $request, $locale, $cartUuid)
    {

        $cart = $this->cartManager->get($cartUuid);

        $access = array();
        foreach ($this->getParameter('paprec_quote_access') as $a) {
            $access[$a] = $a;
        }

        $staff = array();
        foreach ($this->getParameter('paprec_quote_staff') as $s) {
            $staff[$s] = $s;
        }

        $floorNumber = array();
        foreach ($this->getParameter('paprec_quote_floor_number') as $f) {
            $floorNumber[$f] = $f;
        }

        $quoteRequest = $this->quoteRequestManager->add(false);

        $form = $this->createForm(QuoteRequestPublicType::class, $quoteRequest, array(
            'access' => $access,
            'staff' => $staff,
            'floorNumber' => $floorNumber,
            'locale' => $locale
        ));

        $form->handleRequest($request);

        /**
         * TODO problème avec la clé du captcha
         */
        //if ($form->isSubmitted() && $form->isValid() && $this->captchaVerify($request->get('g-recaptcha-response'))) {
        if ($form->isSubmitted() && $form->isValid()) {
            $quoteRequest = $form->getData();
            $quoteRequest->setQuoteStatus('QUOTE_CREATED');
            $quoteRequest->setOrigin('SHOP');
            $quoteRequest->setLocale($locale);
            $quoteRequest->setType($cart->getType());
            $quoteRequest->setPonctualDate($cart->getPonctualDate());
            $quoteRequest->setNumber($this->quoteRequestManager->generateNumber($quoteRequest));
            /**
             * Set Signatory if isSameSignatory
             */
            if ($quoteRequest->getIsSameSignatory()) {
                $quoteRequest->setSignatoryLastName1($quoteRequest->getLastName());
                $quoteRequest->setSignatoryFirstName1($quoteRequest->getFirstName());
                $quoteRequest->setSignatoryTitle1($quoteRequest->getCivility());
            }

            /**
             * Set BillingAddress if isSameAddress
             */
            if ($quoteRequest->getIsSameAddress() && !$quoteRequest->getIsMultisite()) {
                $quoteRequest->setBillingAddress($quoteRequest->getAddress());
                $quoteRequest->setBillingPostalCode($quoteRequest->getPostalCode()->getCode());
                $quoteRequest->setBillingCity($quoteRequest->getPostalCode()->getCity());
            }

            if ($cart->getOtherNeeds() && count($cart->getOtherNeeds())) {
                foreach ($cart->getOtherNeeds() as $otherNeed) {
                    if (strtolower($otherNeed->getLanguage()) === strtolower($locale)) {
                        $quoteRequest->addOtherNeed($otherNeed);
                        $otherNeed->addQuoteRequest($quoteRequest);
                    }
                }
            }

            $reference = $this->quoteRequestManager->generateReference($quoteRequest);
            $quoteRequest->setReference($reference);


            if ($quoteRequest->getIsMultisite()) {
                $quoteRequest->setUserInCharge(null);
            } else {
                $quoteRequest->setUserInCharge($this->userManager->getUserInChargeByPostalCode($quoteRequest->getPostalCode()));
                $quoteRequest->setCity($quoteRequest->getPostalCode()->getCity());
            }

            $this->em->persist($quoteRequest);


            /**
             * On récupère tous les produits ajoutés au Cart
             */
            if ($cart->getContent() !== null) {
                foreach ($cart->getContent() as $item) {
                    $this->quoteRequestManager->addLineFromCart($quoteRequest, $item['pId'], $item['qtty'], $item['frequencyTimes'], $item['frequencyInterval'], false);
                }
            }
            $this->em->flush();

            /**
             * On envoie le mail de confirmation à l'utilisateur
             */
            $sendConfirmEmail = $this->quoteRequestManager->sendConfirmRequestEmail($quoteRequest);
            $sendNewRequestEmail = $this->quoteRequestManager->sendNewRequestEmail($quoteRequest);

            if ($quoteRequest->getType() === 'ponctual') {
                return $this->redirectToRoute('paprec_public_confirm_ponctuel_index', array(
                    'locale' => $locale,
                    'cartUuid' => $cart->getId(),
                    'quoteRequestId' => $quoteRequest->getId()
                ));
            } else {
                return $this->redirectToRoute('paprec_public_confirm_regulier_index', array(
                    'locale' => $locale,
                    'cartUuid' => $cart->getId(),
                    'quoteRequestId' => $quoteRequest->getId()
                ));
            }


//            if ($sendConfirmEmail && $sendNewRequestEmail) {
//                if ($quoteRequest->getType() === 'ponctual') {
//                    return $this->redirectToRoute('paprec_public_confirm_ponctuel_index', array(
//                        'locale' => $locale,
//                        'cartUuid' => $cart->getId(),
//                        'quoteRequestId' => $quoteRequest->getId()
//                    ));
//                } else {
//                    return $this->redirectToRoute('paprec_public_confirm_regulier_index', array(
//                        'locale' => $locale,
//                        'cartUuid' => $cart->getId(),
//                        'quoteRequestId' => $quoteRequest->getId()
//                    ));
//                }
//            }
        }

        return $this->render('public/contact.html.twig', array(
            'locale' => $locale,
            'cart' => $cart,
            'form' => $form->createView()
        ));
    }

    /**
     * A partir du token reCaptcha récupéré dans le formulaire
     * On fait une requête vers google pour vérifier la validité du Captcha
     *
     * @param $recaptchaToken
     * @return mixed
     */
    private function captchaVerify($recaptchaToken)
    {

        $url = "https://www.google.com/recaptcha/api/siteverify";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            "secret" => $_ENV['RECAPTCHA_SECRET_KEY'],
            "response" => $recaptchaToken
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response);

        return $data->success;
    }

    /**
     * @Route("/postalCode/autocomplete", name="paprec_public_postalCode_autocomplete")
     * @throws Exception
     */
    public function autocompleteAction(Request $request)
    {
        $codes = array();
        $code = trim(strip_tags($request->get('term')));

        $entities = $this->postalCodeManager->getActivesFromCode($code);

        foreach ($entities as $entity) {
            $codes[] = $entity->getCode() . ' - ' . $entity->getCity();
        }

        $response = new JsonResponse();
        $response->setData($codes);

        return $response;
    }

    /**
     * @Route("/{locale}/ponctuel/contact/valide/{cartUuid}/{quoteRequestId}", name="paprec_public_confirm_ponctuel_index")
     * @Route("/{locale}/regulier/catalogue/contact/valide/{cartUuid}/{quoteRequestId}", name="paprec_public_confirm_regulier_index")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function confirmAction(Request $request, $locale, $cartUuid, $quoteRequestId)
    {

        $cart = $this->cartManager->get($cartUuid);
        $quoteRequest = $this->quoteRequestManager->get($quoteRequestId);

        return $this->render('public/confirm.html.twig', array(
            'locale' => $locale,
            'quoteRequest' => $quoteRequest,
            'cart' => $cart,
        ));
    }


    /**
     * @Route("/{locale}/addContent/{cartUuid}", defaults={"cartUuid"=null}, name="paprec_public_catalog_addContent", condition="request.isXmlHttpRequest()")
     */
    public function addContentAction(Request $request, $locale, $cartUuid)
    {
        $productId = $request->get('productId');

        $quantity = $request->get('quantity');

        try {
            $product = $this->productManager->get($productId);
            $cart = $this->cartManager->addContent($cartUuid, $product, $quantity);


            return $this->render('public/partials/quoteLine.html.twig', array(
                'locale' => $locale,
                'product' => $product,
                'quantity' => $quantity
            ));

        } catch (\Exception $e) {
            return new JsonResponse(null, 400);
        }
    }

    /**
     * @Route("/{locale}/addFrequency/{cartUuid}", defaults={"cartUuid"=null}, name="paprec_public_catalog_addFrequency", condition="request.isXmlHttpRequest()")
     */
    public function addFrequencyAction(Request $request, $locale, $cartUuid)
    {
        $frequency = $request->get('frequency');
        $frequencyTimes = $request->get('frequency_times');
        $frequencyInterval = $request->get('frequency_interval');

        try {
            $this->cartManager->addFrequency($cartUuid, $frequency, $frequencyTimes, $frequencyInterval);
            $content = json_encode(array('message' => 'frequency_added'));
            return new JsonResponse($content, 204);

        } catch (\Exception $e) {
            return new JsonResponse(array('error' => $e->getMessage()), 400);
        }
    }

    /**
     * Augmente la quantité d'un produit dans le panier de 1
     * L'ajoute au panier si produit non présent
     *
     * @Route("/{locale}/addOneContent/{cartUuid}/{productId}", name="paprec_public_catalog_addOneContent", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function addOneProductAction(Request $request, $locale, $cartUuid, $productId)
    {
        try {
            $product = $this->productManager->get($productId);
            // On ajoute ou on supprime le produit sélecionné au tableau des displayedCategories du Cart
            $qtty = $this->cartManager->addOneProduct($cartUuid, $product);


            return $this->render('public/partials/quoteLine.html.twig', array(
                'locale' => $locale,
                'product' => $product,
                'quantity' => $qtty
            ));

        } catch (\Exception $e) {
            return new JsonResponse(null, 400);
        }
    }

    /**
     * Diminue la quantité d'un produit dans le panier de 1
     * Le supprime du panier si quantité = 0
     *
     * @Route("/{locale}/removeOneContent/{cartUuid}/{productId}", name="paprec_public_catalog_removeOneContent", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function removeOneProductAction(Request $request, $locale, $cartUuid, $productId)
    {
        try {
            $product = $this->productManager->get($productId);
            // On ajoute ou on supprime le produit sélecionné au tableau des displayedCategories du Cart
            $qtty = $this->cartManager->removeOneProduct($cartUuid, $product);

            if ($qtty > 0) {
                return $this->render('public/partials/quoteLine.html.twig', array(
                    'locale' => $locale,
                    'product' => $product,
                    'quantity' => $qtty
                ));
            } else {
                return new JsonResponse(null, 200);
            }


        } catch (\Exception $e) {
            return new JsonResponse(null, 400);
        }
    }

    /**
     * Augmente la quantité d'un produit dans le panier de 1
     * L'ajoute au panier si produit non présent
     *
     * @Route("/{locale}/editProductFrequency/{cartUuid}/{productId}", name="paprec_public_catalog_editProductFrequency", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function editProductFrequencyAction(Request $request, $locale, $cartUuid, $productId)
    {
        try {
            $product = $this->productManager->get($productId);

            /**
             * Récupération des infos du body
             */
            $frequency = $request->get('frequency');
            $frequencyTimes = $request->get('frequency_times');
            $frequencyInterval = $request->get('frequency_interval');

            // On ajoute ou on supprime le produit sélectionné au tableau des displayedCategories du Cart
            $qtty = $this->cartManager->editProductFrequency($cartUuid, $product, $frequency, $frequencyTimes,
                $frequencyInterval);


            return $this->render('public/partials/quoteLine.html.twig', array(
                'locale' => $locale,
                'product' => $product,
                'quantity' => $qtty
            ));

        } catch (\Exception $e) {
            return new JsonResponse(null, 400);
        }
    }


    /**
     * Met à jour la date de collecte souhaitée pour le ctalogue PONCTUAL
     *
     * @Route("/{locale}/editPonctualDate/{cartUuid}", name="paprec_public_catalog_editPonctualDate", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function editPonctualDateAction(Request $request, $locale, $cartUuid)
    {
        try {

            /**
             * Récupération des infos du body
             */
            $ponctualDate = $request->get('ponctual_date');

            $ponctualDate = \DateTime::createFromFormat('Y-m-d', $ponctualDate);

            // On ajoute ou on supprime le produit sélectionné au tableau des displayedCategories du Cart
            $cart = $this->cartManager->editPonctualDate($cartUuid, $ponctualDate);


            return new JsonResponse(null, 200);


        } catch (\Exception $e) {
            return new JsonResponse(null, 400);
        }
    }


    /**
     * Ajout ou suppression d'un OtherNeed au Cart
     *
     * @Route("/{locale}/addRemoveOtherNeed/{cartUuid}/{otherNeedId}", name="paprec_public_catalog_addRemoveOtherNeed", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function addOrRemoveOtherNeedAction(Request $request, $locale, $cartUuid, $otherNeedId)
    {
        try {
            $otherNeed = $this->otherNeedManager->get($otherNeedId);

            // On ajoute ou supprime l'OtherNeed au cart
            $this->cartManager->addOrRemoveOtherNeed($cartUuid, $otherNeed);

            return new JsonResponse(null, 200);


        } catch (\Exception $e) {
            return new JsonResponse(null, 400);
        }
    }


    /**
     * @Route("/{locale}/signatory/{quoteRequestId}/{token}",  name="paprec_public_signatory_index")
     * @param Request $request
     * @param $locale
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function signatoryAction(Request $request, $locale, $quoteRequestId, $token)
    {
        try {
            $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');

            $quoteRequest = $quoteRequestManager->get($quoteRequestId);

            $quoteRequestManager->getActiveByIdAndToken($quoteRequestId, $token);

            $form = $this->createForm(QuoteRequestSignatoryType::class, $quoteRequest, array(
                'locale' => $locale
            ));

            $form->handleRequest($request);


            if ($form->isSubmitted() && $form->isValid()) {
                $quoteRequest = $form->getData();
                $quoteRequest->setQuoteStatus('QUOTE_CREATED');

                $this->em->persist($quoteRequest);

                $this->em->flush();

                /**
                 * On envoie le mail de confirmation à l'utilisateur
                 */
                $sendConfirmEmail = $quoteRequestManager->sendGeneratedContractEmail($quoteRequest);
                $sendNewRequestEmail = $quoteRequestManager->sendNewContractEmail($quoteRequest);


                if ($sendConfirmEmail && $sendNewRequestEmail) {
                    return $this->redirectToRoute('paprec_public_signatory_confirm_index', array(
                        'locale' => $locale,
                        'quoteRequestId' => $quoteRequest->getId(),
                        'token' => $token
                    ));
                }
                exit;
            }


            return $this->render('public/signatory.html.twig', array(
                'locale' => $locale,
                'form' => $form->createView()
            ));
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Not found');
        }
    }

    /**
     * @Route("/{locale}/signatory/confirm/{quoteRequestId}/{token}",  name="paprec_public_signatory_confirm_index")
     * @param Request $request
     * @param $locale
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function signatoryConfirmAction(Request $request, $locale, $quoteRequestId, $token)
    {
        try {
            $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');

            $quoteRequest = $quoteRequestManager->get($quoteRequestId);

            $quoteRequestManager->getActiveByIdAndToken($quoteRequestId, $token);

            return $this->render('public/signatory-confirm.html.twig', array(
                'locale' => $locale,
                'quoteRequest' => $quoteRequest
            ));

        } catch (\Exception $e) {
            throw $this->createNotFoundException('Not found');
        }
    }

//    /**
//     * @Route("/{locale}/offer/{quoteId}", name="paprec_public_offer_show")
//     */
//    public function showOffer(Request $request, $quoteId, $locale)
//    {
//        $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');
//        $quoteRequest = $quoteRequestManager->get($quoteId);
//        $productManager = $this->container->get('paprec_catalog.product_manager');
//        $products = $productManager->getAvailableProducts();
//        return $this->render('@PaprecCommercial/QuoteRequest/PDF/ponctual/printQuoteOffer.html.twig', array(
//            'quoteRequest' => $quoteRequest,
//            'products' => $products,
//            'date' => new \DateTime(),
//            'locale' => $locale,
//        ));
//    }
}
