<?php

namespace App\Controller;

use App\Entity\Agency;
use App\Entity\FollowUp;
use App\Entity\MissionSheet;
use App\Entity\MissionSheetLine;
use App\Entity\MissionSheetProduct;
use App\Entity\Product;
use App\Entity\QuoteRequest;
use App\Entity\QuoteRequestFile;
use App\Entity\QuoteRequestLine;
use App\Entity\Range;
use App\Form\FollowUpType;
use App\Form\MissionSheetLineType;
use App\Form\MissionSheetQuoteRequestLineEditType;
use App\Form\MissionSheetType;
use App\Form\QuoteRequestFileType;
use App\Form\QuoteRequestLineAddType;
use App\Form\QuoteRequestLineEditType;
use App\Form\QuoteRequestManagementFeeType;
use App\Form\QuoteRequestType;
use App\Service\NumberManager;
use App\Service\ProductManager;
use App\Service\QuoteRequestManager;
use App\Service\UserManager;
use App\Tools\DataTable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Respect\Validation\Validator as V;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuoteRequestController extends AbstractController
{
    private $em;
    private $numberManager;
    private $userManager;
    private $quoteRequestManager;
    private $translator;
    private $productManager;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        NumberManager $numberManager,
        UserManager $userManager,
        QuoteRequestManager $quoteRequestManager,
        ProductManager $productManager
    ) {
        $this->em = $em;
        $this->userManager = $userManager;
        $this->numberManager = $numberManager;
        $this->quoteRequestManager = $quoteRequestManager;
        $this->productManager = $productManager;
        $this->translator = $translator;
    }

    /**
     * @Route("", name="paprec_quoteRequest_index")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function indexAction(Request $request)
    {

        $periodStartDate = $request->get('periodStartDate');
        $periodEndDate = $request->get('periodEndDate');
        $selectedStatus = $request->get('selectedStatus');
        $selectedCatalog = $request->get('selectedCatalog');
        $userIds = $request->get('userIds');

        $status = array();
        foreach ($this->getParameter('paprec_quote_status') as $s) {
            $status[$s] = $s;
        }

        return $this->render('quoteRequest/index.html.twig', array(
            'status' => $status,
            'periodStartDate' => $periodStartDate,
            'periodEndDate' => $periodEndDate,
            'selectedStatus' => $selectedStatus,
            'selectedCatalog' => $selectedCatalog,
            'userIds' => $userIds
        ));
    }

    /**
     * @Route("/loadList", name="paprec_quoteRequest_loadList")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function loadListAction(Request $request, DataTable $dataTable, PaginatorInterface $paginator)
    {

        $systemUser = $this->getUser();
        $isManager = in_array('ROLE_MANAGER_COMMERCIAL', $systemUser->getRoles(), true);
        $isCommercialMultiSite = in_array('ROLE_COMMERCIAL_MULTISITES', $systemUser->getRoles(), true);
        $isCommercial = in_array('ROLE_COMMERCIAL', $systemUser->getRoles(), true);

        $return = [];

        /**
         * Récupération des filtres
         */
        $catalog = $request->get('selectedCatalog');
        $status = $request->get('selectedStatus');
        $lastUpdateDate = $request->get('lastUpdateDate');

        $periodStartDate = $request->get('periodStartDate');
        $periodEndDate = $request->get('periodEndDate');
        $userIds = $request->get('userIds');

        $filters = $request->get('filters');
        $pageSize = $request->get('length');
        $start = $request->get('start');
        $orders = $request->get('order');
        $search = $request->get('search');
        $columns = $request->get('columns');
        $rowPrefix = $request->get('rowPrefix');

        $cols['id'] = array('label' => 'id', 'id' => 'q.id', 'method' => array('getId'));
        $cols['reference'] = array(
            'label' => 'reference',
            'id' => 'q.reference',
            'method' => array('getReference')
        );
        $cols['catalog'] = array(
            'label' => 'catalog',
            'id' => 'q.catalog',
            'method' => array('getCatalog')
        );
        $cols['businessName'] = array(
            'label' => 'businessName',
            'id' => 'q.businessName',
            'method' => array('getBusinessName')
        );
        $cols['isMultisite'] = array(
            'label' => 'isMultisite',
            'id' => 'q.isMultisite',
            'method' => array('getIsMultisite')
        );
        $cols['totalAmount'] = array(
            'label' => 'totalAmount',
            'id' => 'q.totalAmount',
            'method' => array('getTotalAmount')
        );
        $cols['quoteStatus'] = array(
            'label' => 'quoteStatus',
            'id' => 'q.quoteStatus',
            'method' => array('getQuoteStatus')
        );
        $cols['dateCreation'] = array(
            'label' => 'dateCreation',
            'id' => 'q.dateCreation',
            'method' => array('getDateCreation'),
            'filter' => array(array('name' => 'format', 'args' => array('Y-m-d H:i:s')))
        );
        $cols['userInCharge'] = array(
            'label' => 'userInCharge',
            'id' => 'q.userInCharge',
            'method' => array('getUserInCharge', '__toString')
        );

        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder->select(array('q'))
            ->from('App:QuoteRequest', 'q')
            ->where('q.deleted IS NULL');

        /**
         * Application des filtres
         */
        if ($catalog !== null && $catalog !== '#') {
            $queryBuilder
                ->andWhere('q.catalog = :catalog')
                ->setParameter('catalog', $catalog);
        }
        if ($status !== null && $status !== '#') {

            $status = explode(',', $status);

            $queryBuilder
                ->andWhere('q.quoteStatus IN (:status)')
                ->setParameter('status', $status);
        }
        if ($lastUpdateDate !== null && $lastUpdateDate !== '') {
            $day = new \DateTime($lastUpdateDate);
            $dayAfter = new \DateTime($lastUpdateDate);
            $dayAfter->add(new \DateInterval('P1D'));

            $queryBuilder
                ->andWhere('q.dateUpdate >= :day')
                ->andWhere('q.dateUpdate < :dayAfter')
                ->setParameter('day', $day->format('Y-m-d') . ' 00:00:00')
                ->setParameter('dayAfter', $dayAfter->format('Y-m-d') . ' 23:59:59');
        }

        if ($periodStartDate && $periodEndDate) {
            $periodStartDate = new \DateTime($periodStartDate);
            $periodEndDate = new \DateTime($periodEndDate);

            $queryBuilder
                ->andWhere('q.dateCreation >= :start')
                ->andWhere('q.dateCreation < :end')
                ->setParameter('start', $periodStartDate->format('Y-m-d') . ' 00:00:00')
                ->setParameter('end', $periodEndDate->format('Y-m-d') . ' 23:59:59');
        }

        if (!empty($userIds)) {
            $userIds = explode(',', $userIds);

            $queryBuilder
                ->andWhere('q.userInCharge IN (:userIds)')
                ->setParameter('userIds', $userIds);
        }

        /**
         * Si l'utilisateur est commercial multisite, on récupère uniquement les quoteRequests multisites
         */
        if ($isCommercialMultiSite) {
            $queryBuilder
                ->andWhere('q.isMultisite = true');
        }
        /**
         * Si l'utilisateur est manager, on récupère uniquement les quoteRequest liés à ses subordonnés
         */
        if ($isManager) {
            $commercials = $this->userManager->getCommercialsFromManager($systemUser->getId());
            $commercialIds = array();
            if ($commercials && count($commercials)) {
                foreach ($commercials as $commercial) {
                    $commercialIds[] = $commercial->getId();
                }
            }
            $queryBuilder
                ->andWhere('q.userInCharge IN (:commercialIds)')
                ->setParameter('commercialIds', $commercialIds);
        }
        /**
         * Si l'utilisateur est commercial, o,n récupère uniquement les quoteRequests qui lui sont associés
         */
        if ($isCommercial) {
            $queryBuilder
                ->andWhere('q.userInCharge = :userInChargeId')
                ->setParameter('userInChargeId', $systemUser->getId());
        }

        /**
         * TODO : Si manager, récupéré toutes les quotesRequests auxquelles ses commerciaux sont rattachés
         */

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('q.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('q.id', '?1'),
                    $queryBuilder->expr()->like('q.number', '?1'),
                    $queryBuilder->expr()->like('q.reference', '?1'),
                    $queryBuilder->expr()->like('q.catalog', '?1'),
                    $queryBuilder->expr()->like('q.businessName', '?1'),
                    $queryBuilder->expr()->like('q.totalAmount', '?1'),
                    $queryBuilder->expr()->like('q.quoteStatus', '?1'),
                    $queryBuilder->expr()->like('q.dateCreation', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }


        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);

        // Reformatage de certaines données
        $tmp = array();
        foreach ($dt['data'] as $data) {
            $line = $data;

            $line['catalog'] = $data['catalog'] ? $this->translator->trans('Commercial.QuoteRequest.Catalog.' . strtoupper($line['catalog'])) : '';
            $line['isMultisite'] = $data['isMultisite'] ? $this->translator->trans('General.1') : $this->translator->trans('General.0');
            $line['totalAmount'] = $this->numberManager->formatAmount($data['totalAmount'], null,
                $request->getLocale());
            $line['quoteStatus'] = $this->translator->trans("Commercial.QuoteStatusList." . $data['quoteStatus']);
            $tmp[] = $line;
        }
        $dt['data'] = $tmp;

        $return['recordsTotal'] = $dt['recordsTotal'];
        $return['recordsFiltered'] = $dt['recordsTotal'];
        $return['data'] = $dt['data'];
        $return['resultCode'] = 1;
        $return['resultDescription'] = "success";

        return new JsonResponse($return);

    }

    /**
     * @Route("/export/{status}/{dateStart}/{dateEnd}", defaults={"status"=null, "dateStart"=null, "dateEnd"=null}, name="paprec_quoteRequest_export")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function exportAction(Request $request, $dateStart, $dateEnd, $status)
    {

        $systemUser = $this->getUser();
        $isManager = in_array('ROLE_MANAGER_COMMERCIAL', $systemUser->getRoles(), true);
        $isCommercialMultiSite = in_array('ROLE_COMMERCIAL_MULTISITES', $systemUser->getRoles(), true);
        $isCommercial = in_array('ROLE_COMMERCIAL', $systemUser->getRoles(), true);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder->select(array('q'))
            ->from('App:QuoteRequest', 'q')
            ->where('q.deleted IS NULL');
        if ($status != null && !empty($status)) {
            $queryBuilder->andWhere('q.quoteStatus = :status')
                ->setParameter('status', $status);
        }
        if ($dateStart != null && $dateEnd != null && !empty($dateStart) && !empty($dateEnd)) {
            $queryBuilder->andWhere('q.dateCreation BETWEEN :dateStart AND :dateEnd')
                ->setParameter('dateStart', $dateStart)
                ->setParameter('dateEnd', $dateEnd);
        }

        /**
         * Si l'utilisateur est commercial multisite, on récupère uniquement les quoteRequests multisites
         */
        if ($isCommercialMultiSite) {
            $queryBuilder
                ->andWhere('q.isMultisite = true');
        }
        /**
         * Si l'utilisateur est manager, on récupère uniquement les quoteRequest liés à ses subordonnés
         */
        if ($isManager) {
            $commercials = $this->userManager->getCommercialsFromManager($systemUser->getId());
            $commercialIds = array();
            if ($commercials && count($commercials)) {
                foreach ($commercials as $commercial) {
                    $commercialIds[] = $commercial->getId();
                }
            }
            $queryBuilder
                ->andWhere('q.userInCharge IN (:commercialIds)')
                ->setParameter('commercialIds', $commercialIds);
        }
        /**
         * Si l'utilisateur est commercial, o,n récupère uniquement les quoteRequests qui lui sont associés
         */
        if ($isCommercial) {
            $queryBuilder
                ->andWhere('q.userInCharge = :userInChargeId')
                ->setParameter('userInChargeId', $systemUser->getId());
        }

        /** @var QuoteRequest[] $quoteRequests */
        $quoteRequests = $queryBuilder->getQuery()->getResult();

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()->setCreator("Paprec Easy Recyclage")
            ->setLastModifiedBy("EasyRecyclageShop")
            ->setTitle("Paprec Easy Recyclage- Devis")
            ->setSubject("Extraction");

        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Devis');

        // Labels
        $sheetLabels = [
            'ID',
            'Date créa.',
            'Dernière modification',
            'Langue',
            'Numéro offre du devis',
            'Nom société',
            'Civilité',
            'Nom',
            'Prénom',
            'Email',
            'Téléphone',
            'Prestation multi-sites',
            'Staff',
            'Accès',
            'Adresse',
            'Ville',
            'Remarques',
            'Statut',
            'Montant total (€)',
//            'Ajustement prix (+/- en %)',
            'Commentaire commercial',
            'Budget annuel',
            'Numéro client',
            'Référence de l\'offre',
            'Commercial en charge',
            'Code postal',
            'Date de fin de la prestation'
        ];

        $xAxe = 'A';
        foreach ($sheetLabels as $label) {
            $sheet->setCellValue($xAxe . 1, $label);
            $xAxe++;
        }

        $yAxe = 2;
        foreach ($quoteRequests as $quoteRequest) {

            $getters = [
                $quoteRequest->getId(),
                $quoteRequest->getDateCreation()->format('Y-m-d'),
                $quoteRequest->getDateUpdate() ? $quoteRequest->getDateUpdate()->format('Y-m-d') : '',
                $quoteRequest->getLocale(),
                $quoteRequest->getNumber(),
                $quoteRequest->getBusinessName(),
                $quoteRequest->getCivility(),
                $quoteRequest->getLastName(),
                $quoteRequest->getFirstName(),
                $quoteRequest->getEmail(),
                $quoteRequest->getPhone(),
                $quoteRequest->getIsMultisite() ? 'true' : 'false',
                $this->translator->trans('Commercial.StaffList.' . $quoteRequest->getStaff()),
                $quoteRequest->getAccess(),
                $quoteRequest->getAddress(),
                $quoteRequest->getCity(),
                $quoteRequest->getComment(),
                $quoteRequest->getQuoteStatus(),
                $this->numberManager->denormalize($quoteRequest->getTotalAmount()),
//                $this->numberManager->denormalize($quoteRequest->getOverallDiscount()) . '%',
                $quoteRequest->getSalesmanComment(),
                $this->numberManager->denormalize($quoteRequest->getAnnualBudget()),
                $quoteRequest->getCustomerId(),
                $quoteRequest->getReference(),
                $quoteRequest->getUserInCharge() ? $quoteRequest->getUserInCharge()->getFirstName() . " " . $quoteRequest->getUserInCharge()->getLastName() : '',
                $quoteRequest->getPostalCode() ? $quoteRequest->getPostalCode()->getCode() : '',
                $quoteRequest->getServiceEndDate() ? $quoteRequest->getServiceEndDate()->format('Y-m-d') : '',
            ];

            $xAxe = 'A';
            foreach ($getters as $getter) {
                $sheet->setCellValue($xAxe . $yAxe, (string)$getter);
                $xAxe++;
            }
            $yAxe++;
        }


        // Resize columns
        for ($i = 'A'; $i != $sheet->getHighestDataColumn(); $i++) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }

        $fileName = 'EasyRecyclageShop-Extraction-Devis--' . date('Y-m-d') . '.xlsx';

        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $streamedResponse->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $streamedResponse->headers->set('Pragma', 'public');
        $streamedResponse->headers->set('Cache-Control', 'maxage=1');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename=' . $fileName);

        return $streamedResponse->send();
    }

    /**
     * @Route("/view/{id}", name="paprec_quote_request_view")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function viewAction(Request $request, QuoteRequest $quoteRequest)
    {

        $systemUser = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $systemUser->getRoles(), true);
        $isManager = in_array('ROLE_MANAGER_COMMERCIAL', $systemUser->getRoles(), true);
        $isCommercialMultiSite = in_array('ROLE_COMMERCIAL_MULTISITES', $systemUser->getRoles(), true);
        $isCommercial = in_array('ROLE_COMMERCIAL', $systemUser->getRoles(), true);

        if (!$isAdmin) {
            $returnError = true;

            /**
             * Si l'utilisateur est commercial multisite, on récupère uniquement les quoteRequests multisites
             */
            if ($isCommercialMultiSite && $quoteRequest->getIsMultisite() === true) {
                $returnError = false;
            }

            /**
             * Si l'utilisateur est manager, on récupère uniquement les quoteRequest liés à ses commerciaux
             */
            if ($isManager && $quoteRequest->getUserInCharge() && ($quoteRequest->getUserInCharge()->getId() === $systemUser->getId() || $quoteRequest->getUserInCharge()->getManager()->getId() === $systemUser->getId())) {
                $returnError = false;
            }
            /**
             * Si l'utilisateur est commercial, on récupère uniquement les quoteRequests qui lui sont associés
             */
            if ($isCommercial && $quoteRequest->getUserInCharge() && $quoteRequest->getUserInCharge()->getId() === $systemUser->getId()) {
                $returnError = false;
            }

            if ($returnError) {
                throw $this->createNotFoundException('Not found');
            }
        }

        $this->quoteRequestManager->isDeleted($quoteRequest, true);

        $isAbleToSendContractEmail = $this->quoteRequestManager->isAbleToSendContractEmail($quoteRequest);

        $quoteRequestFile = new QuoteRequestFile();

        $formAddQuoteRequestFile = $this->createForm(QuoteRequestFileType::class, $quoteRequestFile, array());

        $hasContract = false;
        if (count($quoteRequest->getQuoteRequestFiles())) {
            foreach ($quoteRequest->getQuoteRequestFiles() as $qRF) {
                if (strtoupper($qRF->getType()) === 'CONTRACT') {
                    $hasContract = true;
                }
            }
        }

        $monthlyCoefficientValues = $this->getParameter('paprec.frequency_interval.monthly_coefficients');

        /**
         * On calcule le prix sans les réductions
         */
        $total = 0;
        if (is_iterable($quoteRequest->getQuoteRequestLines()) && count($quoteRequest->getQuoteRequestLines())) {
            foreach ($quoteRequest->getQuoteRequestLines() as $quoteRequestLine) {

                $frequencyIntervalValue = $this->productManager->calculateFrequencyCoeff($quoteRequestLine);

                $quantity = $quoteRequestLine->getQuantity();

                /**
                 * PU Location du Produit * Quantité * Coefficient Location du CP de l’adresse à collecter
                 */
                if ($quoteRequestLine->getRentalUnitPrice() > 0) {
                    $total += $this->numberManager->denormalize($quoteRequestLine->getRentalUnitPrice()) * $quoteRequestLine->getQuantity() * $this->numberManager->denormalize15($quoteRequestLine->getRentalRate());
                }

                /**
                 * Traitement : (PU Traitement du Produit * Coefficient Traitement du CP de l’adresse à collecter)
                 */
                if ($quoteRequestLine->getTreatmentUnitPrice() !== null) {
                    $total += $this->numberManager->denormalize($quoteRequestLine->getTreatmentUnitPrice()) * $this->numberManager->denormalize15($quoteRequestLine->getTreatmentRate());
                }
                /**
                 * Budget mensuel Transport et Traitement = Nombre de passage par mois * PU transport * Coefficient CP
                 */
                if ($quoteRequestLine->getTransportUnitPrice() > 0) {
                    $total += $frequencyIntervalValue * $this->numberManager->denormalize($quoteRequestLine->getTransportUnitPrice()) * $this->numberManager->denormalize15($quoteRequestLine->getTransportRate());
                }

                /**
                 * Budget mensuel Matériel Additionnel = Nombre de passage par mois * (Quantité de Produit saisie – 1) * PU Matériel Additionnel * Coefficient CP
                 */
                if ($quoteRequestLine->getTraceabilityUnitPrice() > 0) {
                    $total += $frequencyIntervalValue * ($quantity - 1) * $this->numberManager->denormalize($quoteRequestLine->getTraceabilityUnitPrice()) * $this->numberManager->denormalize15($quoteRequestLine->getTraceabilityRate());
                }
            }
            $total = $this->numberManager->normalize($total);
        }

        /**
         * Frais de gestion
         */
        $quoteRequest->setManagementFeeAmount1($this->numberManager->denormalize($quoteRequest->getManagementFeeAmount1()));
        $quoteRequest->setManagementFeeAmount2($this->numberManager->denormalize($quoteRequest->getManagementFeeAmount2()));
        $quoteRequest->setManagementFeeAmount3($this->numberManager->denormalize($quoteRequest->getManagementFeeAmount3()));
        $quoteRequest->setManagementFeeAmount4($this->numberManager->denormalize($quoteRequest->getManagementFeeAmount4()));
        $formAddManagementFee = $this->createForm(QuoteRequestManagementFeeType::class, $quoteRequest, []);

        return $this->render('quoteRequest/view.html.twig', array(
            'quoteRequest' => $quoteRequest,
            'hasContract' => $hasContract,
            'isAbleToSendContractEmail' => $isAbleToSendContractEmail,
            'formAddQuoteRequestFile' => $formAddQuoteRequestFile->createView(),
            'formAddManagementFee' => $formAddManagementFee->createView(),
            'monthlyCoefficientValues' => $monthlyCoefficientValues,
            'total' => $total
        ));
    }

    /**
     * @Route("/add", name="paprec_quoteRequest_add")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $quoteRequest = $this->quoteRequestManager->add(false);

        $status = array();
        foreach ($this->getParameter('paprec_quote_status') as $s) {
            $status[$s] = $s;
        }

        $locales = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $locales[$language] = strtolower($language);
        }

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

        $form = $this->createForm(QuoteRequestType::class, $quoteRequest, array(
            'status' => $status,
            'locales' => $locales,
            'access' => $access,
            'staff' => $staff,
            'floorNumber' => $floorNumber,
            'catalog' => 'REGULAR',
            'civility' => 'M'
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $quoteRequest = $form->getData();

            $postalCodeString = $form->get('postalCodeString')->getData();
            $billingPostalCodeString = $form->get('billingPostalCodeString')->getData();


            if ($quoteRequest->getPostalCode() && $quoteRequest->getPostalCode()->getCode() !== $postalCodeString) {
                $form->get('postalCode')->addError(new FormError('Le code postal ne correspond pas à celui de l\'adresse.'));
            }

            if ($quoteRequest->getBillingPostalCode() !== $billingPostalCodeString) {
                $form->get('billingPostalCode')->addError(new FormError('Le code postal ne correspond pas à celui de l\'adresse.'));
            }

            if ($form->isValid()) {
//                $quoteRequest->setOverallDiscount($this->numberManager->normalize($quoteRequest->getOverallDiscount()));
                $quoteRequest->setAnnualBudget($this->numberManager->normalize($quoteRequest->getAnnualBudget()));

                $quoteRequest->setOrigin('BO');
                $quoteRequest->setUserCreation($user);

                $reference = $this->quoteRequestManager->generateReference($quoteRequest);
                $quoteRequest->setReference($reference);

                $this->em->persist($quoteRequest);
                $this->em->flush();

                return $this->redirectToRoute('paprec_quote_request_view', array(
                    'id' => $quoteRequest->getId()
                ));

            }
        }

        return $this->render('quoteRequest/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="paprec_quoteRequest_edit")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Exception
     */
    public function editAction(Request $request, QuoteRequest $quoteRequest)
    {
        $user = $this->getUser();

        $this->quoteRequestManager->isDeleted($quoteRequest, true);

        $status = array();
        foreach ($this->getParameter('paprec_quote_status') as $s) {
            $status[$s] = $s;
        }

        $locales = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $locales[$language] = strtolower($language);
        }

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

//        $quoteRequest->setOverallDiscount($this->numberManager->denormalize($quoteRequest->getOverallDiscount()));
        $quoteRequest->setAnnualBudget($this->numberManager->denormalize($quoteRequest->getAnnualBudget()));

        $postalCode = null;
        if ($quoteRequest->getPostalCode()) {
            $postalCode = $quoteRequest->getPostalCode()->getCode();
        }

        $form = $this->createForm(QuoteRequestType::class, $quoteRequest, array(
            'status' => $status,
            'locales' => $locales,
            'access' => $access,
            'staff' => $staff,
            'floorNumber' => $floorNumber,
            'catalog' => $quoteRequest->getCatalog(),
            'civility' => $quoteRequest->getCivility(),
            'postalCodeString' => $postalCode,
            'billingPostalCodeString' => $quoteRequest->getBillingPostalCode()
        ));

        $savedCommercial = $quoteRequest->getUserInCharge();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $quoteRequest = $form->getData();

            $postalCodeString = $form->get('postalCodeString')->getData();
            $billingPostalCodeString = $form->get('billingPostalCodeString')->getData();

            if ($quoteRequest->getPostalCode() && $quoteRequest->getPostalCode()->getCode() !== $postalCodeString) {
                $form->get('postalCode')->addError(new FormError('Le code postal ne correspond pas à celui de l\'adresse.'));
            }

            if ($quoteRequest->getBillingPostalCode() !== $billingPostalCodeString) {
                $form->get('billingPostalCode')->addError(new FormError('Le code postal ne correspond pas à celui de l\'adresse.'));
            }

            if ($form->isValid()) {
                $quoteRequest = $form->getData();

//                $overallDiscount = $quoteRequest->getOverallDiscount();
                $overallDiscount = null;
                $quoteRequest->setAnnualBudget($this->numberManager->normalize($quoteRequest->getAnnualBudget()));

                if ($quoteRequest->getQuoteRequestLines()) {
                    foreach ($quoteRequest->getQuoteRequestLines() as $line) {
                        $this->quoteRequestManager->editLine($quoteRequest, $line, $user, false, false,
                            $overallDiscount);
                    }
                }
                $quoteRequest->setTotalAmount($this->quoteRequestManager->calculateTotal($quoteRequest));
//                $quoteRequest->setOverallDiscount($this->numberManager->normalize($overallDiscount));

                $quoteRequest->setDateUpdate(new \DateTime());
                $quoteRequest->setUserUpdate($user);

                /**
                 * Si le commercial en charge a changé, alors on envoie un mail au nouveau commercial
                 */
                if ($quoteRequest->getUserInCharge() && ((!$savedCommercial && $quoteRequest->getUserInCharge())
                        || ($savedCommercial && $savedCommercial->getId() !== $quoteRequest->getUserInCharge()->getId()))) {
                    $this->quoteRequestManager->sendNewRequestEmail($quoteRequest);
                    $this->get('session')->getFlashBag()->add('success', 'newUserInChargeWarned');
                }


                $this->em->flush();

                return $this->redirectToRoute('paprec_quote_request_view', array(
                    'id' => $quoteRequest->getId()
                ));

            }
        }

        return $this->render('quoteRequest/edit.html.twig', array(
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest
        ));
    }

    /**
     * @Route("/remove/{id}", name="paprec_quoteRequest_remove")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function removeAction(Request $request, QuoteRequest $quoteRequest)
    {
        $quoteRequest->setDeleted(new \DateTime());
        $this->em->flush();

        return $this->redirectToRoute('paprec_quoteRequest_index');
    }

    /**
     * @Route("/duplicate/{id}", name="paprec_quoteRequest_duplicate")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function duplicateAction(Request $request, QuoteRequest $quoteRequest)
    {

        $newQuoteRequest = clone $quoteRequest;
        $this->em->persist($newQuoteRequest);
        $newQuoteRequest->setDateUpdate(null);
        $newQuoteRequest->setUserUpdate(null);
        $newQuoteRequest->setDateCreation(new \DateTime());
        $newQuoteRequest->setParent($quoteRequest);
        $newQuoteRequest->setMissionSheet(null);
        $newQuoteRequest->setQuoteStatus('QUOTE_CREATED');

        if (is_iterable($quoteRequest->getQuoteRequestLines()) && count($quoteRequest->getQuoteRequestLines())) {
            foreach ($quoteRequest->getQuoteRequestLines() as $qL) {
                $newQuoteRequestLine = clone $qL;
                $this->em->persist($newQuoteRequestLine);

                $newQuoteRequestLine->setQuoteRequest($newQuoteRequest);
                $newQuoteRequestLine->setDateUpdate(null);
                $newQuoteRequestLine->setDateCreation(new \DateTime());
                $newQuoteRequest->addQuoteRequestLine($newQuoteRequestLine);
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_quote_request_view', array(
            'id' => $newQuoteRequest->getId()
        ));
    }

    /**
     * @Route("/{id}/addFollowUp", name="paprec_quote_request_follow_up_add")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function addFollowUpAction(Request $request, QuoteRequest $quoteRequest)
    {
        $user = $this->getUser();

        $followUp = new FollowUp();
        $followUp->setQuoteRequest($quoteRequest);

        $status = [];
        foreach ($this->getParameter('paprec.follow_up.status') as $s) {
            $status[$s] = $s;
        }

        $form = $this->createForm(FollowUpType::class, $followUp, [
            'quoteRequestId' => $quoteRequest->getId(),
            'status' => $status
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $followUp = $form->getData();

            $followUp->setDateCreation(new \DateTime());
            $followUp->setUserCreation($user);
            $followUp->setQuoteRequest($quoteRequest);

            $this->em->persist($followUp);
            $this->em->flush();

            return $this->redirectToRoute('paprec_quote_request_view', array(
                'id' => $quoteRequest->getId()
            ));

        }

        return $this->render('followUp/add.html.twig', array(
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest
        ));
    }

    /**
     * @Route("/{id}/followUpLoadList", name="paprec_quote_request_follow_up_loadList")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function followUpLoadListAction(
        Request $request,
        DataTable $dataTable,
        PaginatorInterface $paginator,
        QuoteRequest $quoteRequest
    ) {
        $return = [];

        $filters = $request->get('filters');
        $pageSize = $request->get('length');
        $start = $request->get('start');
        $orders = $request->get('order');
        $search = $request->get('search');
        $columns = $request->get('columns');
        $rowPrefix = $request->get('rowPrefix');

        $cols['id'] = array('label' => 'id', 'id' => 'fU.id', 'method' => array('getId'));
        $cols['status'] = array('label' => 'status', 'id' => 'fU.status', 'method' => array('getStatus'));
        $cols['content'] = array('label' => 'content', 'id' => 'fU.content', 'method' => array('getContent'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(FollowUp::class)->createQueryBuilder('fU');

        $queryBuilder->select(array('fU'))
            ->select(array('fU', 'qR'))
            ->where('fU.deleted is NULL')
            ->leftJoin('fU.quoteRequest', 'qR')
            ->andWhere('qR.deleted is NULL')
            ->andWhere('qR.id = :quoteRequestId')
            ->setParameter('quoteRequestId', $quoteRequest->getId());

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('fU.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('fU.content', '?1'),
                    $queryBuilder->expr()->like('fU.status', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);

        // Reformatage de certaines données
        $tmp = [];
        foreach ($dt['data'] as $data) {
            $line = $data;
            $line['status'] = $this->translator->trans('Commercial.FollowUp.Status.' . $line['status']);
            $tmp[] = $line;
        }
        $dt['data'] = $tmp;

        $return['recordsTotal'] = $dt['recordsTotal'];
        $return['recordsFiltered'] = $dt['recordsTotal'];
        $return['data'] = $dt['data'];
        $return['resultCode'] = 1;
        $return['resultDescription'] = "success";

        return new JsonResponse($return);

    }


    /**
     * @Route("/removeMany/{ids}", name="paprec_quoteRequest_removeMany")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function removeManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $quoteRequests = $this->em->getRepository('App:QuoteRequest')->findById($ids);
            foreach ($quoteRequests as $quoteRequest) {
                $quoteRequest->setDeleted(new \DateTime);
            }
            $this->em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }

    /**
     * @Route("/{id}/addLine", name="paprec_quoteRequest_addLine")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function addLineAction(Request $request, QuoteRequest $quoteRequest)
    {

        $selectedCatalog = $request->get('selectedCatalog');
        $selectedRangeId = $request->get('selectedRangeId');

        $user = $this->getUser();

        if ($quoteRequest->getDeleted() !== null) {
            throw new NotFoundHttpException();
        }

        $quoteRequestLine = new QuoteRequestLine();

        if ($selectedCatalog === '') {
            $selectedCatalog = null;
        }

        if ($selectedRangeId === '') {
            $selectedRangeId = null;
        }

        $form = $this->createForm(QuoteRequestLineAddType::class, $quoteRequestLine, [
            'quoteRequestCatalog' => $selectedCatalog,
            'rangeId' => $selectedRangeId
        ]);

        $form->handleRequest($request);

        /**
         * TODO $form->isValid() ne fonctionne pas
         */
        if ($form->isSubmitted() && $form->isValid()) {
            $quoteRequestLine = $form->getData();

            if ($quoteRequestLine->getFrequency() !== 'REGULAR') {
                $quoteRequestLine->setFrequencyTimes(null);
                $quoteRequestLine->setFrequencyInterval(null);
            }

            if ($quoteRequestLine->getProduct()) {

                if ($quoteRequestLine->getProduct()->getHideFrequency() === true) {
                    $quoteRequestLine->setFrequency(null);
                    $quoteRequestLine->setFrequencyTimes(null);
                    $quoteRequestLine->setFrequencyInterval(null);
                }

                $this->quoteRequestManager->addLine($quoteRequest, $quoteRequestLine, $user);
            }

            return $this->redirectToRoute('paprec_quote_request_view', array(
                'id' => $quoteRequest->getId()
            ));

        }

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(Range::class)->createQueryBuilder('r');

        $queryBuilder->select('r')
            ->leftJoin('r.rangeLabels', 'rL')
            ->where('r.deleted IS NULL')
            ->andWhere('rL.language = :language')
            ->setParameter('language', 'FR');

        $ranges = $queryBuilder->getQuery()->getResult();


        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(Product::class)->createQueryBuilder('p');

        $queryBuilder->select('p')
            ->where('p.deleted IS NULL');

        $products = $queryBuilder->getQuery()->getResult();

        $hideFrequencyById = [];
        if (is_array($products) && count($products)) {
            foreach ($products as $product) {
                $hideFrequencyById[$product->getId()] = $product->getHideFrequency();
            }
        }

        return $this->render('quoteRequestLine/add.html.twig', array(
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
            'selectedCatalog' => $selectedCatalog,
            'selectedRangeId' => $selectedRangeId,
            'ranges' => $ranges,
            'hideFrequencyById' => $hideFrequencyById
        ));
    }

    /**
     * @Route("/{id}/editLine/{quoteLineId}", name="paprec_quoteRequest_editLine")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @ParamConverter("quoteRequest", options={"id" = "id"})
     * @ParamConverter("quoteRequestLine", options={"id" = "quoteLineId"})
     */
    public function editLineAction(Request $request, QuoteRequest $quoteRequest, QuoteRequestLine $quoteRequestLine)
    {
        if ($quoteRequest->getDeleted() !== null) {
            throw new NotFoundHttpException();
        }

        if ($quoteRequestLine->getQuoteRequest() !== $quoteRequest) {
            throw new NotFoundHttpException();
        }

        $user = $this->getUser();

        $quoteRequestLine->setEditableTransportUnitPrice($this->numberManager->denormalize($quoteRequestLine->getEditableTransportUnitPrice()));
        $quoteRequestLine->setEditableRentalUnitPrice($this->numberManager->denormalize($quoteRequestLine->getEditableRentalUnitPrice()));
        $quoteRequestLine->setEditableTreatmentUnitPrice($this->numberManager->denormalize($quoteRequestLine->getEditableTreatmentUnitPrice()));
        $quoteRequestLine->setEditableTraceabilityUnitPrice($this->numberManager->denormalize($quoteRequestLine->getEditableTraceabilityUnitPrice()));

        $form = $this->createForm(QuoteRequestLineEditType::class, $quoteRequestLine, [
            'productCatalog' => $quoteRequestLine->getProduct()->getCatalog()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $quoteRequestLine = $form->getData();

            $quoteRequestLine->setEditableTransportUnitPrice($this->numberManager->normalize($quoteRequestLine->getEditableTransportUnitPrice()));
            $quoteRequestLine->setEditableRentalUnitPrice($this->numberManager->normalize($quoteRequestLine->getEditableRentalUnitPrice()));
            $quoteRequestLine->setEditableTreatmentUnitPrice($this->numberManager->normalize($quoteRequestLine->getEditableTreatmentUnitPrice()));
            $quoteRequestLine->setEditableTraceabilityUnitPrice($this->numberManager->normalize($quoteRequestLine->getEditableTraceabilityUnitPrice()));

            $this->quoteRequestManager->editLine($quoteRequest, $quoteRequestLine, $user);

            return $this->redirectToRoute('paprec_quote_request_view', array(
                'id' => $quoteRequest->getId()
            ));
        }

        return $this->render('quoteRequestLine/edit.html.twig', array(
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
            'quoteRequestLine' => $quoteRequestLine
        ));
    }


    /**
     * @Route("/{id}/removeLine/{quoteLineId}", name="paprec_quoteRequest_removeLine")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @ParamConverter("quoteRequest", options={"id" = "id"})
     * @ParamConverter("quoteRequestLine", options={"id" = "quoteLineId"})
     */
    public function removeLineAction(Request $request, QuoteRequest $quoteRequest, QuoteRequestLine $quoteRequestLine)
    {
        if ($quoteRequest->getDeleted() !== null) {
            throw new NotFoundHttpException();
        }

        if ($quoteRequestLine->getQuoteRequest() !== $quoteRequest) {
            throw new NotFoundHttpException();
        }

        $this->em->remove($quoteRequestLine);
        $this->em->flush();

        $total = $this->quoteRequestManager->calculateTotal($quoteRequest);
        $quoteRequest->setTotalAmount($total);
        $this->em->flush();


        return $this->redirectToRoute('paprec_quote_request_view', array(
            'id' => $quoteRequest->getId()
        ));
    }

    /**
     * @Route("/{id}/sendGeneratedQuote", name="paprec_quoteRequest_sendGeneratedQuote")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Exception
     */
    public function sendGeneratedQuoteAction(QuoteRequest $quoteRequest)
    {
        $this->quoteRequestManager->isDeleted($quoteRequest, true);

        if ($quoteRequest->getPostalCode()) {
            $sendQuote = $this->quoteRequestManager->sendGeneratedQuoteEmail($quoteRequest, $this->getUser());
            if ($sendQuote) {
                $this->get('session')->getFlashBag()->add('success', 'generatedQuoteSent');
            } else {
                $this->get('session')->getFlashBag()->add('error', 'generatedQuoteNotSent');
            }
        }

        $quoteRequest->setQuoteStatus('QUOTE_SENT');
        $this->em->flush();

        return $this->redirectToRoute('paprec_quote_request_view', array(
            'id' => $quoteRequest->getId()
        ));
    }

    /**
     * @Route("/{id}/sendGeneratedContract", name="paprec_quoteRequest_sendGeneratedContract")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Exception
     */
    public function sendGeneratedContractAction(QuoteRequest $quoteRequest)
    {
        $this->quoteRequestManager->isDeleted($quoteRequest, true);

//        if ($quoteRequest->getPostalCode()) {
            $sendContract = $this->quoteRequestManager->sendGeneratedContractEmail($quoteRequest);
            if ($sendContract) {

                $quoteRequest->setQuoteStatus('CONTRACT_SENT');
                $this->em->flush();

                $this->get('session')->getFlashBag()->add('success', 'generatedContractSent');
            } else {
                $this->get('session')->getFlashBag()->add('error', 'generatedContractNotSent');
            }
//        }

        return $this->redirectToRoute('paprec_quote_request_view', array(
            'id' => $quoteRequest->getId()
        ));
    }

    /**
     * @Route("/{id}/downloadQuote", name="paprec_quote_request_download")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @throws \Exception
     */
    public function downloadAssociatedInvoiceAction(QuoteRequest $quoteRequest)
    {
        /**
         * On commence par pdf générés (seulement ceux générés dans le BO  pour éviter de supprimer un PDF en cours d'envoi pour un utilisateur
         */
        $pdfFolder = $this->getParameter('paprec.data_tmp_directory');

        /**
         * Si le dossier n'existe pas, on le créé
         */
        if (!is_dir($pdfFolder)) {
            if (!mkdir($pdfFolder, 0777, true)) {
                return false;
            }
        }

        $finder = new Finder();

        $finder->files()->in($pdfFolder);

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
//                $fileNameWithExtension = $file->getRelativePathname();
                if (file_exists($absoluteFilePath)) {
                    unlink($absoluteFilePath);
                }
            }
        }

        $user = $this->getUser();
        $pdfTmpFolder = $pdfFolder . '/';

        $locale = 'fr';

        $file = $this->quoteRequestManager->generatePDF($quoteRequest, $locale, false);

        $filename = substr($file, strrpos($file, '/') + 1);

        // This should return the file to the browser as response
        $response = new BinaryFileResponse($pdfTmpFolder . $filename);

        // To generate a file download, you need the mimetype of the file
        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

        // Set the mimetype with the guesser or manually
        if ($mimeTypeGuesser->isSupported()) {
            // Guess the mimetype of the file according to the extension of the file
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess($pdfTmpFolder . $filename));
        } else {
            // Set the mimetype of the file manually, in this case for a text file is text/plain
            $response->headers->set('Content-Type', 'application/pdf');
        }

        // Set content disposition inline of the file
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $quoteRequest->getReference() . '-' . $this->translator->trans('Commercial.QuoteRequest.DownloadedQuoteName',
                array(), 'messages', $locale) . '-' . $quoteRequest->getBusinessName() . '.pdf'
        );

        return $response;
    }

    /**
     * @Route("/editManagementFee/{quoteRequestId}", name="paprec_quote_request_edit_quote_request_management_fee")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function editManagementFeeAction(Request $request, $quoteRequestId)
    {
        $quoteRequest = $this->quoteRequestManager->get($quoteRequestId, true);

        $form = $this->createForm(QuoteRequestManagementFeeType::class, $quoteRequest, array());

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $quoteRequest = $form->getData();

            $quoteRequest->setManagementFeeAmount1($this->numberManager->normalize($quoteRequest->getManagementFeeAmount1()));
            $quoteRequest->setManagementFeeAmount2($this->numberManager->normalize($quoteRequest->getManagementFeeAmount2()));
            $quoteRequest->setManagementFeeAmount3($this->numberManager->normalize($quoteRequest->getManagementFeeAmount3()));
            $quoteRequest->setManagementFeeAmount4($this->numberManager->normalize($quoteRequest->getManagementFeeAmount4()));

            if ($form->isValid()) {
                $quoteRequest->setDateUpdate(new \DateTime());

                $this->em->flush();

                return $this->redirectToRoute('paprec_quote_request_view', array(
                    'id' => $quoteRequest->getId()
                ));
            }
        }

        return $this->redirectToRoute('paprec_quote_request_view', array(
            'id' => $quoteRequest->getId()
        ));
    }

    /**
     * @Route("/addQuoteRequestFile/{quoteRequestId}", name="paprec_quote_request_add_quote_request_file")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function addQuoteRequestFileAction(Request $request, $quoteRequestId)
    {
        $quoteRequest = $this->quoteRequestManager->get($quoteRequestId, true);

        $quoteRequestFile = new QuoteRequestFile();

        $form = $this->createForm(QuoteRequestFileType::class, $quoteRequestFile, array());

        $form->handleRequest($request);
        if ($form->isValid()) {
            $quoteRequest->setDateUpdate(new \DateTime());
            $quoteRequestFile = $form->getData();

            if ($quoteRequestFile->getSystemPath() instanceof UploadedFile) {
                $quoteRequestFilePath = $quoteRequestFile->getSystemPath();

                $quoteRequestFileSize = $quoteRequestFile->getSystemPath()->getClientSize();

                $fileMaxSize = $this->getParameter('paprec.quote_request_file.max_file_size');

                if ($quoteRequestFileSize > $fileMaxSize) {
                    $this->get('session')->getFlashBag()->add('error', 'generatedQuoteRequestFileNotAdded');

                    return $this->redirectToRoute('paprec_quote_request_view', array(
                        'id' => $quoteRequest->getId()
                    ));
                }

                $quoteRequestFileSystemName = md5(uniqid('', true)) . '.' . $quoteRequestFilePath->guessExtension();

                $quoteRequestFilePath->move($this->getParameter('paprec.quote_request_file.directory'),
                    $quoteRequestFileSystemName);

                $quoteRequestFileOriginalName = $quoteRequestFile->getSystemPath()->getClientOriginalName();
                $quoteRequestFileMimeType = $quoteRequestFile->getSystemPath()->getClientMimeType();

                $quoteRequestFile
                    ->setSystemName($quoteRequestFileSystemName)
                    ->setOriginalFileName($quoteRequestFileOriginalName)
                    ->setMimeType($quoteRequestFileMimeType)
                    ->setSystemSize($quoteRequestFileSize)
                    ->setQuoteRequest($quoteRequest);
                $quoteRequest->addQuoteRequestFile($quoteRequestFile);
                $this->em->persist($quoteRequestFile);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_quote_request_view', array(
                'id' => $quoteRequest->getId()
            ));
        }

        return $this->redirectToRoute('paprec_quote_request_view', array(
            'id' => $quoteRequest->getId()
        ));
    }

    /**
     * @Route("removeQuoteRequestFile/{quoteRequestId}/{quoteRequestFileId}", name="paprec_quote_request_remove_quote_request_file")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @ParamConverter("quoteRequestFile", options={"id" = "quoteRequestFileId"})
     */
    public function removeQuoteRequestFileAction(Request $request, $quoteRequestId, QuoteRequestFile $quoteRequestFile)
    {
        $quoteRequest = $this->quoteRequestManager->get($quoteRequestId, true);

        $this->em->remove($quoteRequestFile);
        $this->em->flush();

        $quoteRequestFileFolder = $this->getParameter('paprec.quote_request_file.directory');
        $quoteRequestFilePath = $quoteRequestFileFolder . '/' . $quoteRequestFile->getSystemName();

        if (file_exists($quoteRequestFilePath)) {
            unlink($quoteRequestFilePath);
        }

        return $this->redirectToRoute('paprec_quote_request_view', array(
            'id' => $quoteRequest->getId()
        ));
    }

    /**
     * @Route("/downloadQuoteRequestFile/{id}", name="paprec_quote_request_download_quote_request_file")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function downloadQuoteRequestFileAction(Request $request, QuoteRequestFile $quoteRequestFile)
    {
        $quoteRequestFileFolder = $this->getParameter('paprec.quote_request_file.directory');
        $quoteRequestFilePath = $quoteRequestFileFolder . '/' . $quoteRequestFile->getSystemName();
        if (file_exists($quoteRequestFilePath)) {
            $response = new BinaryFileResponse($quoteRequestFilePath);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $quoteRequestFile->getOriginalFileName());

            return $response;
        }

        return $this->redirectToRoute('paprec_quote_request_view', array(
            'id' => $quoteRequestFile->getQuoteRequest()->getId()
        ));

    }

    /**
     * @Route("/{id}/addMissionSheet", name="paprec_quote_request_mission_sheet_add")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function addMissionSheetAction(Request $request, QuoteRequest $quoteRequest)
    {
        $user = $this->getUser();

        $missionSheet = new MissionSheet();
        $missionSheet->addQuoteRequest($quoteRequest);
        $quoteRequest->setMissionSheet($missionSheet);

        $missionSheet->setMyPaprecAccess(0);
        $missionSheet->setWasteTrackingRegisterAccess(0);
        $missionSheet->setReportingAccess(0);
        $missionSheet->setDateCreation(new \DateTime());
        $missionSheet->setUserCreation($user);
        $missionSheet->setStatus('NOT_VALIDATED');
        $missionSheet->setContractNumber(null);
        $missionSheet->setContractType('CREATION');
        $missionSheet->setMnemonicNumber('');
        $missionSheet->setBillingType('GLOBAL');

        $this->em->persist($missionSheet);
        $this->em->flush();


        return $this->redirectToRoute('paprec_quote_request_mission_sheet_edit', [
            'id' => $quoteRequest->getId(),
            'missionSheetId' => $missionSheet->getId()
        ]);


//        $monthlyCoefficientValues = $this->getParameter('paprec.frequency_interval.monthly_coefficients');
//
//        $agencies = $this->getDoctrine()->getManager()->getRepository(Agency::class)->findBy([
//            'deleted' => null
//        ]);
//        $agencyById = [];
//        if (is_array($agencies) && count($agencies)) {
//            foreach ($agencies as $a) {
//                $agencyById[$a->getId()] = $a;
//            }
//        }
//
//        $missionSheet = new MissionSheet();
//        $missionSheet->addQuoteRequest($quoteRequest);
//        $quoteRequest->setMissionSheet($missionSheet);
//
//        $missionSheet->setMyPaprecAccess(0);
//        $missionSheet->setWasteTrackingRegisterAccess(0);
//        $missionSheet->setReportingAccess(0);
//        $form = $this->createForm(MissionSheetType::class, $missionSheet, [
//        ]);
//
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted()) {
//            $formData = $form->all();
//            if (is_array($formData) && count($formData)) {
//                foreach ($formData as $key => $value) {
//                    $parameters[$key] = $value->getData();
//                }
//            }
//
//            $contractType = null;
//            $mnemonicNumber = null;
//            $contractNumber = null;
//            if (is_array($parameters) && count($parameters)) {
//                foreach ($parameters as $key => $value) {
//                    $$key = $value;
//                }
//            }
//
//            $errors = [];
//            if (isset($contractType) && strtoupper($contractType) === 'MODIFICATION') {
////                if (!$mnemonicNumber) {
////                    $errors['mnemonicNumber'] = array(
////                        'code' => 400,
////                        'message' => 'La valeur ne doit pas être nulle'
////                    );
////                }
//                if (!$contractNumber) {
//                    $errors['contractNumber'] = array(
//                        'code' => 400,
//                        'message' => 'La valeur ne doit pas être nulle'
//                    );
//                }
//            }
//
//            if ($errors && count($errors)) {
//                $form = $this->createForm(MissionSheetType::class, $missionSheet, [
//                ]);
//
//                if ($errors['contractNumber']) {
//                    $form->get('contractNumber')->addError(new FormError('La valeur ne doit pas être nulle'));
//                }
//
////                if ($errors['mnemonicNumber']) {
////                    $form->get('mnemonicNumber')->addError(new FormError('La valeur ne doit pas être nulle'));
////                }
//
//                return $this->render('quoteRequest/missionSheet/add.html.twig', array(
//                    'form' => $form->createView(),
//                    'quoteRequest' => $quoteRequest,
//                    'errors' => $errors,
//                    'agencies' => $agencies,
//                    'monthlyCoefficientValues' => $monthlyCoefficientValues
//                ));
//            }
//
//            if ($form->isValid()) {
//
//                $missionSheet = $form->getData();
//
//                $missionSheet->setDateCreation(new \DateTime());
//                $missionSheet->setUserCreation($user);
//                $missionSheet->setStatus('NOT_VALIDATED');
//
//                if (strtoupper($missionSheet->getContractType()) === 'CREATION') {
////                    $missionSheet->setMnemonicNumber(null);
//                    $missionSheet->setContractNumber(null);
//                }
//
//                $this->em->persist($missionSheet);
//
//                /**
//                 * On ajoute l'agence dans les quoteRequestLine
//                 */
//                $quoteRequestLineAgencies = $request->get('quoteRequestLineAgency');
//
//                if (count($quoteRequest->getQuoteRequestLines())) {
//                    foreach ($quoteRequest->getQuoteRequestLines() as $quoteRequestLine) {
//                        if (array_key_exists($quoteRequestLine->getId(), $quoteRequestLineAgencies)
//                            && array_key_exists((int)$quoteRequestLineAgencies[$quoteRequestLine->getId()],
//                                $agencyById)) {
//                            $quoteRequestLine->setAgency($agencyById[(int)$quoteRequestLineAgencies[$quoteRequestLine->getId()]]);
//                        }
//                    }
//                }
//
//                $this->em->flush();
//
//                return $this->redirectToRoute('paprec_quote_request_view', array(
//                    'id' => $quoteRequest->getId(),
//                    'monthlyCoefficientValues' => $monthlyCoefficientValues
//                ));
//
//            }
//        }
//
//        return $this->render('quoteRequest/missionSheet/add.html.twig', array(
//            'form' => $form->createView(),
//            'quoteRequest' => $quoteRequest,
//            'agencies' => $agencies,
//            'monthlyCoefficientValues' => $monthlyCoefficientValues
//        ));
    }

    /**
     * @Route("/{id}/editMissionSheet/{missionSheetId}", name="paprec_quote_request_mission_sheet_edit")
     * @Security("has_role('ROLE_MANAGER_COMMERCIAL')")
     */
    public function editMissionSheetAction(Request $request, QuoteRequest $quoteRequest, $missionSheetId)
    {
        $missionSheet = $this->quoteRequestManager->getMissionSheet($missionSheetId, true);
        $monthlyCoefficientValues = $this->getParameter('paprec.frequency_interval.monthly_coefficients');
        $user = $this->getUser();

        $agencies = $this->getDoctrine()->getManager()->getRepository(Agency::class)->findBy([
            'deleted' => null
        ]);
        $agencyById = [];
        if (is_array($agencies) && count($agencies)) {
            foreach ($agencies as $a) {
                $agencyById[$a->getId()] = $a;
            }
        }

        $form = $this->createForm(MissionSheetType::class, $missionSheet, [
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $formData = $form->all();
            if (is_array($formData) && count($formData)) {
                foreach ($formData as $key => $value) {
                    $parameters[$key] = $value->getData();
                }
            }

            $contractType = null;
            $mnemonicNumber = null;
            $contractNumber = null;
            if (is_array($parameters) && count($parameters)) {
                foreach ($parameters as $key => $value) {
                    $$key = $value;
                }
            }

            $errors = [];
            if (isset($contractType) && strtoupper($contractType) === 'MODIFICATION') {
//                if (!$mnemonicNumber) {
//                    $errors['mnemonicNumber'] = array(
//                        'code' => 400,
//                        'message' => 'La valeur ne doit pas être nulle'
//                    );
//                }
                if (!$contractNumber) {
                    $errors['contractNumber'] = array(
                        'code' => 400,
                        'message' => 'La valeur ne doit pas être nulle'
                    );
                }
            }

            if ($errors && count($errors)) {
                $form = $this->createForm(MissionSheetType::class, $missionSheet, [
                ]);

                if ($errors['contractNumber']) {
                    $form->get('contractNumber')->addError(new FormError('La valeur ne doit pas être nulle'));
                }

//                if ($errors['mnemonicNumber']) {
//                    $form->get('mnemonicNumber')->addError(new FormError('La valeur ne doit pas être nulle'));
//                }

                return $this->render('quoteRequest/missionSheet/edit.html.twig', array(
                    'form' => $form->createView(),
                    'quoteRequest' => $quoteRequest,
                    'missionSheet' => $missionSheet,
                    'errors' => $errors,
                    'agencies' => $agencies,
                    'monthlyCoefficientValues' => $monthlyCoefficientValues
                ));
            }

            if ($form->isValid()) {

                $missionSheet = $form->getData();

                $missionSheet->setDateUpdate(new \DateTime());
                $missionSheet->setUserUpdate($user);
                $missionSheet->setStatus('NOT_VALIDATED');

                if (strtoupper($missionSheet->getContractType()) === 'CREATION') {
//                    $missionSheet->setMnemonicNumber(null);
                    $missionSheet->setContractNumber(null);
                }

//                /**
//                 * On ajoute l'agence dans les quoteRequestLine
//                 */
//                $quoteRequestLineAgencies = $request->get('quoteRequestLineAgency');
//
//                if (count($quoteRequest->getQuoteRequestLines())) {
//                    foreach ($quoteRequest->getQuoteRequestLines() as $quoteRequestLine) {
//                        if (array_key_exists($quoteRequestLine->getId(), $quoteRequestLineAgencies)
//                            && array_key_exists((int)$quoteRequestLineAgencies[$quoteRequestLine->getId()],
//                                $agencyById)) {
//                            $quoteRequestLine->setAgency($agencyById[(int)$quoteRequestLineAgencies[$quoteRequestLine->getId()]]);
//                        }
//                    }
//                }

                $this->em->flush();

                return $this->redirectToRoute('paprec_quote_request_view', array(
                    'id' => $quoteRequest->getId(),
                    'monthlyCoefficientValues' => $monthlyCoefficientValues
                ));

            }
        }

        return $this->render('quoteRequest/missionSheet/edit.html.twig', array(
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
            'missionSheet' => $missionSheet,
            'agencies' => $agencies,
            'monthlyCoefficientValues' => $monthlyCoefficientValues
        ));
    }

    /**
     * @Route("/{id}/validateMissionSheet/{missionSheetId}", name="paprec_quote_request_mission_sheet_validate")
     * @Security("has_role('ROLE_MANAGER_COMMERCIAL')")
     */
    public function validateMissionSheetAction(Request $request, QuoteRequest $quoteRequest, $missionSheetId)
    {
        $missionSheet = $this->quoteRequestManager->getMissionSheet($missionSheetId, true);
        $missionSheet->setStatus('VALIDATED');
        $this->em->flush();

        return $this->redirectToRoute('paprec_quote_request_view', [
            'id' => $quoteRequest->getId()
        ]);
    }

    /**
     * @Route("/{id}/sendMissionSheet", name="paprec_quote_request_mission_sheet_send")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @throws \Exception
     */
    public function sendMissionSheetAction(QuoteRequest $quoteRequest)
    {
        /**
         * On commence par pdf générés (seulement ceux générés dans le BO  pour éviter de supprimer un PDF en cours d'envoi pour un utilisateur
         */
        $pdfFolder = $this->getParameter('paprec.data_tmp_directory');

        /**
         * Si le dossier n'existe pas, on le créé
         */
        if (!is_dir($pdfFolder)) {
            if (!mkdir($pdfFolder, 0777, true)) {
                return false;
            }
        }

        $finder = new Finder();

        $finder->files()->in($pdfFolder);

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
//                $fileNameWithExtension = $file->getRelativePathname();
                if (file_exists($absoluteFilePath)) {
                    unlink($absoluteFilePath);
                }
            }
        }

        $locale = 'fr';

        $wasSent = $this->quoteRequestManager->sendGenerateMissionSheetPDF($quoteRequest,
            $quoteRequest->getMissionSheet(),
            $locale);
        if ($wasSent) {
            $this->get('session')->getFlashBag()->add('success', 'generatedMissionSheetSent');
        } else {
            $this->get('session')->getFlashBag()->add('error', 'generatedMissionSheetNotSent');
        }

        return $this->redirectToRoute('paprec_quote_request_view', [
            'id' => $quoteRequest->getId()
        ]);

    }

    /**
     * @Route("/{id}/downloadMissionSheet", name="paprec_quote_request_mission_sheet_download")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @throws \Exception
     */
    public function downloadMissionSheetAction(QuoteRequest $quoteRequest)
    {
        /**
         * On commence par pdf générés (seulement ceux générés dans le BO  pour éviter de supprimer un PDF en cours d'envoi pour un utilisateur
         */
        $pdfFolder = $this->getParameter('paprec.data_tmp_directory');

        /**
         * Si le dossier n'existe pas, on le créé
         */
        if (!is_dir($pdfFolder)) {
            if (!mkdir($pdfFolder, 0777, true)) {
                return false;
            }
        }

        $finder = new Finder();

        $finder->files()->in($pdfFolder);

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
//                $fileNameWithExtension = $file->getRelativePathname();
                if (file_exists($absoluteFilePath)) {
                    unlink($absoluteFilePath);
                }
            }
        }

        $locale = 'fr';

        $fileName = $this->quoteRequestManager->generateMissionSheetPDF($quoteRequest, $quoteRequest->getMissionSheet(),
            $locale);

        // This should return the file to the browser as response
        $response = new BinaryFileResponse($fileName);

        // To generate a file download, you need the mimetype of the file
        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

        // Set the mimetype with the guesser or manually
        if ($mimeTypeGuesser->isSupported()) {
            // Guess the mimetype of the file according to the extension of the file
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess($fileName));
        } else {
            // Set the mimetype of the file manually, in this case for a text file is text/plain
            $response->headers->set('Content-Type', 'application/pdf');
        }

        // Set content disposition inline of the file
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $quoteRequest->getReference() . '-' . $this->translator->trans('Commercial.QuoteRequest.DownloadedMissionSheetName',
                array(), 'messages', $locale) . '-' . $quoteRequest->getBusinessName() . '.pdf'
        );

        return $response;

    }

    /**
     * @Route("/{id}/missionSheet/{missionSheetId}/loadQuoteRequestLineList", name="paprec_quote_request_mission_sheet_load_quote_request_line_list")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function loadMissionSheetQuoteRequestLineListAction(
        Request $request,
        DataTable $dataTable,
        PaginatorInterface $paginator,
        QuoteRequest $quoteRequest,
        $missionSheetId
    ) {
        $return = [];

        $filters = $request->get('filters');
        $pageSize = $request->get('length');
        $start = $request->get('start');
        $orders = $request->get('order');
        $search = $request->get('search');
        $columns = $request->get('columns');
        $rowPrefix = $request->get('rowPrefix');

        $cols['productCode'] = array(
            'label' => 'productCode',
            'id' => 'p.code',
            'method' => ['getProduct', 'getCode']
        );
        $cols['productRange'] = array(
            'label' => 'productRange',
            'id' => 'rL.name',
            'method' => ['getProduct', 'getRange', ['getRangeLabels', 0], 'getName']
        );
        $cols['productName'] = array(
            'label' => 'productName',
            'id' => 'pL.name',
            'method' => ['getProduct', ['getProductLabels', 0], 'getName']
        );
        $cols['productBillingUnit'] = array(
            'label' => 'productBillingUnit',
            'id' => 'bU.name',
            'method' => ['getProduct', 'getBillingUnit', 'getName']
        );
        $cols['quantity'] = array(
            'label' => 'quantity',
            'id' => 'qRL.quantity',
            'method' => ['getQuantity']
        );
        $cols['passingFrequency'] = array(
            'label' => 'passingFrequency',
            'id' => 'qRL.frequency',
            'method' => ['getFrequency']
        );
        $cols['rentalPrice'] = array(
            'label' => 'rentalPrice',
            'id' => 'qRL.rentalRate',
            'method' => ['getRentalRate']
        );
        $cols['treatmentCollect'] = array(
            'label' => 'treatmentCollect',
            'id' => 'qRL.treatmentCollectPrice',
            'method' => ['getTreatmentCollectPrice']
        );
        $cols['treatment'] = array(
            'label' => 'treatment',
            'id' => 'qRL.treatmentRate',
            'method' => ['getTreatmentRate']
        );
        $cols['movement'] = array(
            'label' => 'movement',
            'id' => 'qRL.movement',
            'method' => ['getMovement']
        );
        $cols['agencyName'] = array(
            'label' => 'agencyName',
            'id' => 'a.name',
            'method' => array('getAgency', 'getName')
        );
        $cols['comment'] = array(
            'label' => 'comment',
            'id' => 'qRL.comment',
            'method' => ['getComment']
        );
        $cols['sellingPrice'] = array(
            'label' => 'sellingPrice',
            'id' => 'p.materialUnitPrice',
            'method' => ['getProduct', 'getMaterialUnitPrice']
        );
        $cols['id'] = array('label' => 'id', 'id' => 'mSP.id', 'method' => array('getId'));
        $cols['frequencyInterval'] = array(
            'label' => 'frequencyInterval',
            'id' => 'qRL.frequencyInterval',
            'method' => ['getFrequencyInterval']
        );
        $cols['frequencyTimes'] = array(
            'label' => 'frequencyTimes',
            'id' => 'qRL.frequencyTimes',
            'method' => ['getFrequencyTimes']
        );
        $cols['editableRentalUnitPrice'] = array(
            'label' => 'editableRentalUnitPrice',
            'id' => 'qRL.editableRentalUnitPrice',
            'method' => ['getEditableRentalUnitPrice']
        );
        $cols['editableTreatmentUnitPrice'] = array(
            'label' => 'editableTreatmentUnitPrice',
            'id' => 'qRL.editableTreatmentUnitPrice',
            'method' => ['getEditableTreatmentUnitPrice']
        );

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(QuoteRequestLine::class)->createQueryBuilder('qRL');

        $queryBuilder->select(array('qRL', 'qR', 'p', 'pL', 'a', 'mS'))
            ->leftJoin('qRL.agency', 'a')
            ->leftJoin('qRL.quoteRequest', 'qR')
            ->leftJoin('qR.missionSheet', 'mS')
            ->leftJoin('qRL.product', 'p')
            ->leftJoin('p.billingUnit', 'bU')
            ->leftJoin('p.productLabels', 'pL')
            ->leftJoin('p.range', 'r')
            ->leftJoin('r.rangeLabels', 'rL')
            ->where('qRL.deleted IS NULL')
            ->andWhere('pL.language = :language')
            ->setParameter('language', 'FR')
            ->andWhere('mS.id = :id')
            ->setParameter('id', $missionSheetId);

        $quoteRequestLines = $queryBuilder->getQuery()->getResult();

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('qRL.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('p.code', '?1'),
                    $queryBuilder->expr()->like('p.name', '?1'),
                    $queryBuilder->expr()->like('a.name', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);

        $quoteRequestLinesById = [];
        if (is_array($quoteRequestLines) && count($quoteRequestLines)) {
            foreach ($quoteRequestLines as $quoteRequestLine) {
                $quoteRequestLinesById[$quoteRequestLine->getId()] = $quoteRequestLine;
            }
        }

        // Reformatage de certaines données
        $tmp = [];
        foreach ($dt['data'] as $data) {
            $line = $data;

            if (strtoupper($line['passingFrequency']) != 'REGULAR') {
                $line['passingFrequency'] = $this->translator->trans('General.Frequency.' . $line['passingFrequency']);
            } else {
                if (!empty($line['frequencyInterval'])) {
                    $frequencyInterval = $this->translator->trans('General.Frequency.' . $line['frequencyInterval']);

                    $line['passingFrequency'] = $line['frequencyTimes'] . ' x/ ' . $frequencyInterval;
                }
            }

            $qRL = $quoteRequestLinesById[$line['id']];
            $product = $qRL->getProduct();

            if ($product->getCalculationFormula() === 'PACKAGE') {
                $line['rentalPrice'] = null;
            } elseif ($product->getCalculationFormula() === 'UNIT_PACKAGE') {
                $line['rentalPrice'] = null;

            } else {
                $line['rentalPrice'] = $this->numberManager->formatAmount($this->productManager->calculatePriceByFieldName($qRL,
                    'editableRentalUnitPrice', true), null, $request->getLocale());
            }

            if ($line['sellingPrice']) {
                $line['sellingPrice'] = $this->numberManager->formatAmount($line['sellingPrice'], null,
                    $request->getLocale());
            }

            if ($product->getCalculationFormula() === 'PACKAGE') {
                $line['treatment'] = null;

            } elseif ($product->getCalculationFormula() === 'UNIT_PACKAGE') {
                $line['treatment'] = null;

            } else {
                $line['treatment'] = $this->numberManager->formatAmount($this->productManager->calculatePriceByFieldName($qRL,
                    'editableTreatmentUnitPrice', true), null, $request->getLocale());
            }
            if ($product->getCalculationFormula() === 'PACKAGE') {
                $line['treatmentCollect'] = null;

            } elseif ($product->getCalculationFormula() === 'UNIT_PACKAGE') {
                $line['treatmentCollect'] = null;

            } else {
                $line['treatmentCollect'] = $this->numberManager->formatAmount($this->productManager->calculatePriceByFieldName($qRL,
                    'treatmentCollectPrice', true), null, $request->getLocale());
            }

            $tmp[] = $line;
        }
        $dt['data'] = $tmp;


        $return['recordsTotal'] = $dt['recordsTotal'];
        $return['recordsFiltered'] = $dt['recordsTotal'];
        $return['data'] = $dt['data'];
        $return['resultCode'] = 1;
        $return['resultDescription'] = "success";

        return new JsonResponse($return);

    }

    /**
     * @Route("/{id}/missionSheet/{missionSheetId}/quoteRequestLine/{quoteRequestLineId}/edit", name="paprec_quote_request_mission_sheet_quote_request_line_edit")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @ParamConverter("id", options={"id" = "id"})
     * @ParamConverter("missionSheet", options={"id" = "missionSheetId"})
     * @ParamConverter("quoteRequestLine", options={"id" = "quoteRequestLineId"})
     */
    public function missionSheetEditQuoteRequestLineAction(
        Request $request,
        QuoteRequest $quoteRequest,
        MissionSheet $missionSheet,
        QuoteRequestLine $quoteRequestLine
    ) {
        if ($missionSheet->getDeleted() !== null) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(MissionSheetQuoteRequestLineEditType::class, $quoteRequestLine);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $quoteRequestLine = $form->getData();
                $quoteRequestLine->setDateUpdate(new \DateTime());
                $this->em->flush();
                return new JsonResponse(array(
                    'id' => $quoteRequestLine->getId(),
                    'resultCode' => 1
                ));

            }

            return new JsonResponse(array(
                'error' => true,
                'data' => $this->renderView('quoteRequest/missionSheet/editQuoteRequestLineModal.html.twig',
                    array(
                        'form' => $form->createView()
                    )),
                'resultCode' => 0
            ));
        }

        return $this->render('quoteRequest/missionSheet/editQuoteRequestLineModal.html.twig', array(
            'form' => $form->createView()
        ));
    }


    /**
     * @Route("/{id}/missionSheet/{missionSheetId}/loadLineList", name="paprec_quote_request_mission_sheet_loadLineList")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function loadMissionSheetLineListAction(
        Request $request,
        DataTable $dataTable,
        PaginatorInterface $paginator,
        QuoteRequest $quoteRequest,
        $missionSheetId
    ) {
        $return = [];

        $filters = $request->get('filters');
        $pageSize = $request->get('length');
        $start = $request->get('start');
        $orders = $request->get('order');
        $search = $request->get('search');
        $columns = $request->get('columns');
        $rowPrefix = $request->get('rowPrefix');

        $cols['id'] = array('label' => 'id', 'id' => 'mSP.id', 'method' => array('getId'));
        $cols['quantity'] = array(
            'label' => 'quantity',
            'id' => 'mSL.quantity',
            'method' => array('getQuantity')
        );
        $cols['code'] = array(
            'label' => 'code',
            'id' => 'mSP.code',
            'method' => ['getMissionSheetProduct', 'getCode']
        );
        $cols['name'] = array(
            'label' => 'name',
            'id' => 'mSPL.name',
            'method' => array('getMissionSheetProduct', array('getMissionSheetProductLabels', 0), 'getName')
        );
        $cols['agencyName'] = array(
            'label' => 'agencyName',
            'id' => 'a.name',
            'method' => array('getAgency', 'getName')
        );

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(MissionSheetLine::class)->createQueryBuilder('mSL');

        $queryBuilder->select(array('mSL', 'mSP', 'mSPL'))
            ->leftJoin('mSL.missionSheetProduct', 'mSP')
            ->leftJoin('mSP.missionSheetProductLabels', 'mSPL')
            ->leftJoin('mSL.missionSheet', 'mS')
            ->leftJoin('mSL.agency', 'a')
            ->where('mSP.deleted IS NULL')
            ->andWhere('mSPL.language = :language')
            ->setParameter('language', 'FR')
            ->andWhere('mS.id = :id')
            ->setParameter('id', $missionSheetId);

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('mSL.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('mSPL.name', '?1'),
                    $queryBuilder->expr()->like('mSP.dimensions', '?1'),
                    $queryBuilder->expr()->like('a.name', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);

        $return['recordsTotal'] = $dt['recordsTotal'];
        $return['recordsFiltered'] = $dt['recordsTotal'];
        $return['data'] = $dt['data'];
        $return['resultCode'] = 1;
        $return['resultDescription'] = "success";

        return new JsonResponse($return);

    }


    /**
     * @Route("/{id}/missionSheet/{missionSheetId}/addLine", name="paprec_quote_request_mission_sheet_addLine")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @ParamConverter("id", options={"id" = "id"})
     * @ParamConverter("missionSheet", options={"id" = "missionSheetId"})
     */
    public function addMissionSheetLineAction(Request $request, QuoteRequest $quoteRequest, MissionSheet $missionSheet)
    {
        if ($missionSheet->getDeleted() !== null) {
            throw new NotFoundHttpException();
        }

        $missionSheetLine = new MissionSheetLine();

        $form = $this->createForm(MissionSheetLineType::class, $missionSheetLine);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $missionSheetLine = $form->getData();
                $missionSheetLine->setDateCreation(new \DateTime());
                $missionSheetLine->setMissionSheet($missionSheet);

                $this->em->persist($missionSheetLine);
                $this->em->flush();

                return new JsonResponse(array(
                    'id' => $missionSheetLine->getId(),
                    'resultCode' => 1
                ));

            }

            return new JsonResponse(array(
                'error' => true,
                'data' => $this->renderView('quoteRequest/missionSheet/addLineModal.html.twig',
                    array(
                        'form' => $form->createView(),
                        'quoteRequest' => $quoteRequest,
                        'missionSheet' => $missionSheet
                    )),
                'resultCode' => 0
            ));
        }

        return $this->render('quoteRequest/missionSheet/addLineModal.html.twig', array(
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
            'missionSheet' => $missionSheet
        ));
    }

    /**
     * @Route("/{id}/missionSheet/{missionSheetId}/editLine/{missionSheetLineId}", name="paprec_quote_request_mission_sheet_editLine")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @ParamConverter("id", options={"id" = "id"})
     * @ParamConverter("missionSheet", options={"id" = "missionSheetId"})
     * @ParamConverter("missionSheetLine", options={"id" = "missionSheetLineId"})
     */
    public function editMissionSheetLineAction(
        Request $request,
        QuoteRequest $quoteRequest,
        MissionSheet $missionSheet,
        MissionSheetLine $missionSheetLine
    ) {
        if ($missionSheet->getDeleted() !== null) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(MissionSheetLineType::class, $missionSheetLine);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $missionSheetLine = $form->getData();
                $missionSheetLine->setDateUpdate(new \DateTime());
                $this->em->flush();
                return new JsonResponse(array(
                    'id' => $missionSheetLine->getId(),
                    'resultCode' => 1
                ));

            }

            return new JsonResponse(array(
                'error' => true,
                'data' => $this->renderView('quoteRequest/missionSheet/editLineModal.html.twig',
                    array(
                        'form' => $form->createView(),
                        'quoteRequest' => $quoteRequest,
                        'missionSheet' => $missionSheet
                    )),
                'resultCode' => 0
            ));
        }

        return $this->render('quoteRequest/missionSheet/editLineModal.html.twig', array(
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
            'missionSheet' => $missionSheet
        ));
    }

    /**
     * @Route("/{id}/missionSheet/{missionSheetId}/removeLine/{missionSheetLineId}", name="paprec_quote_request_mission_sheet_removeLine")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @ParamConverter("id", options={"id" = "id"})
     * @ParamConverter("missionSheet", options={"id" = "missionSheetId"})
     * @ParamConverter("missionSheetLine", options={"id" = "missionSheetLineId"})
     */
    public function removeMissionSheetLineAction(
        Request $request,
        QuoteRequest $quoteRequest,
        MissionSheet $missionSheet,
        MissionSheetLine $missionSheetLine
    ) {
        if ($missionSheet->getDeleted() !== null) {
            throw new NotFoundHttpException();
        }

        if ($missionSheetLine->getMissionSheet() !== $missionSheet) {
            throw new NotFoundHttpException();
        }

        $this->em->remove($missionSheetLine);
        $this->em->flush();

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }
}
