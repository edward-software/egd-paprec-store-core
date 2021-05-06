<?php

namespace App\Controller;

use Doctrine\ORM\QueryBuilder;
use App\Service\NumberManager;
use App\Entity\QuoteRequest;
use App\Entity\QuoteRequestLine;
use App\Form\QuoteRequestLineAddType;
use App\Form\QuoteRequestLineEditType;
use App\Form\QuoteRequestType;
use PHPExcel_Style_Alignment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\Translator;

class QuoteRequestController extends Controller
{

    /**
     * @Route("/quoteRequest", name="paprec_quoteRequest_index")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
     */
    public function indexAction()
    {
        return $this->render('PaprecCommercialBundle:QuoteRequest:index.html.twig');
    }

    /**
     * @Route("/quoteRequest/loadList", name="paprec_quoteRequest_loadList")
     * @Security("has_role('ROLE_COMMERCIAL')")
     */
    public function loadListAction(Request $request)
    {

        $systemUser = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $systemUser->getRoles());

        $numberManager = $this->get('paprec_catalog.number_manager');
        $return = array();

        $filters = $request->get('filters');
        $pageSize = $request->get('length');
        $start = $request->get('start');
        $orders = $request->get('order');
        $search = $request->get('search');
        $columns = $request->get('columns');

        $cols['id'] = array('label' => 'id', 'id' => 'q.id', 'method' => array('getId'));
        $cols['reference'] = array(
            'label' => 'reference',
            'id' => 'q.reference',
            'method' => array('getReference')
        );
        $cols['type'] = array(
            'label' => 'type',
            'id' => 'q.type',
            'method' => array('getType')
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
            'method' => array('getUserInCharge', 'getFullName')
        );


        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder->select(array('q'))
            ->from('PaprecCommercialBundle:QuoteRequest', 'q')
            ->where('q.deleted IS NULL');

//        /**
//         * Si l'utilisateur n'est pas administrateur, alors on récupère uniquement les devis qui lui sont rattachés
//         */
//        if (!$isAdmin) {
//            $queryBuilder
//                ->andWhere('q.userInCharge = :userId')
//                ->setParameter('userId', $systemUser->getId());
//        }

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) == '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('q.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('q.id', '?1'),
                    $queryBuilder->expr()->like('q.number', '?1'),
                    $queryBuilder->expr()->like('q.reference', '?1'),
                    $queryBuilder->expr()->like('q.type', '?1'),
                    $queryBuilder->expr()->like('q.businessName', '?1'),
                    $queryBuilder->expr()->like('q.totalAmount', '?1'),
                    $queryBuilder->expr()->like('q.quoteStatus', '?1'),
                    $queryBuilder->expr()->like('q.dateCreation', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $datatable = $this->get('goondi_tools.datatable')->generateTable($cols, $queryBuilder, $pageSize, $start,
            $orders, $columns, $filters);
        // Reformatage de certaines données
        $tmp = array();
        foreach ($datatable['data'] as $data) {
            $line = $data;
            $line['type'] = $data['type'] ? $this->get('translator')->trans('Commercial.QuoteRequest.Type.' . ucfirst(strtolower($line['type']))) : '';
            $line['isMultisite'] = $data['isMultisite'] ? $this->get('translator')->trans('General.1') : $this->get('translator')->trans('General.0');
            $line['totalAmount'] = $numberManager->formatAmount($data['totalAmount'], 'EUR', $request->getLocale());
            $line['quoteStatus'] = $this->container->get('translator')->trans("Commercial.QuoteStatusList." . $data['quoteStatus']);
            $tmp[] = $line;
        }
        $datatable['data'] = $tmp;

        $return['recordsTotal'] = $datatable['recordsTotal'];
        $return['recordsFiltered'] = $datatable['recordsTotal'];
        $return['data'] = $datatable['data'];
        $return['resultCode'] = 1;
        $return['resultDescription'] = "success";

        return new JsonResponse($return);

    }

    /**
     * @Route("/quoteRequest/export/{status}/{dateStart}/{dateEnd}", defaults={"status"=null, "dateStart"=null, "dateEnd"=null}, name="paprec_quoteRequest_export")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
     */
    public function exportAction(Request $request, $dateStart, $dateEnd, $status)
    {
        /** @var NumberManager $numberManager */
        $numberManager = $this->get('paprec_catalog.number_manager');

        /** @var Translator $translator */
        $translator = $this->get('translator');

        /** @var \PHPExcel $phpExcelObject */
        $phpExcelObject = $this->container->get('phpexcel')->createPHPExcelObject();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder->select(array('q'))
            ->from('PaprecCommercialBundle:QuoteRequest', 'q')
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

        /** @var QuoteRequest[] $quoteRequests */
        $quoteRequests = $queryBuilder->getQuery()->getResult();

        $phpExcelObject->getProperties()->setCreator("Paprec Privacia")
            ->setLastModifiedBy("Privacia Shop")
            ->setTitle("Paprec Privacia - Devis")
            ->setSubject("Extraction");

        $sheet = $phpExcelObject->setActiveSheetIndex();
        $sheet->setTitle('Devis');

        // Labels
        $sheetLabels = [
            'ID',
            'Creation date',
            'Update date',
            'Deleted',
            'User creation',
            'User update',
            'Locale',
            'Number',
            'Business name',
            'Civility',
            'Last name',
            'First name',
            'Email',
            'Phone',
            'is Multisite',
            'Staff',
            'Access',
            'Address',
            'City',
            'Customer comment',
            'Status',
            'Total amount',
            'Overall Discount',
            'Salesman Comment',
            'Annual Budget',
            'Frequency',
            'Frequency Times',
            'Frequency Interval',
            'Customer ID',
            'Reference',
            'User in charge',
            'Postal Code',
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
                $quoteRequest->getDeleted() ? 'true' : 'false',
                $quoteRequest->getUserCreation(),
                $quoteRequest->getUserUpdate(),
                $quoteRequest->getLocale(),
                $quoteRequest->getNumber(),
                $quoteRequest->getBusinessName(),
                $quoteRequest->getCivility(),
                $quoteRequest->getLastName(),
                $quoteRequest->getFirstName(),
                $quoteRequest->getEmail(),
                $quoteRequest->getPhone(),
                $quoteRequest->getIsMultisite() ? 'true' : 'false',
                $translator->trans('Commercial.StaffList.' . $quoteRequest->getStaff()),
                $quoteRequest->getAccess(),
                $quoteRequest->getAddress(),
                $quoteRequest->getCity(),
                $quoteRequest->getComment(),
                $quoteRequest->getQuoteStatus(),
                $numberManager->denormalize($quoteRequest->getTotalAmount()),
                $numberManager->denormalize($quoteRequest->getOverallDiscount()) . '%',
                $quoteRequest->getSalesmanComment(),
                $numberManager->denormalize($quoteRequest->getAnnualBudget()),
                $quoteRequest->getFrequency(),
                $quoteRequest->getFrequencyTimes(),
                $quoteRequest->getFrequencyInterval(),
                $quoteRequest->getCustomerId(),
                $quoteRequest->getReference(),
                $quoteRequest->getUserInCharge() ? $quoteRequest->getUserInCharge()->getFirstName() . " " . $quoteRequest->getUserInCharge()->getLastName() : '',
                $quoteRequest->getPostalCode() ? $quoteRequest->getPostalCode()->getCode() : '',
            ];

            $xAxe = 'A';
            foreach ($getters as $getter) {
                $sheet->setCellValue($xAxe . $yAxe, (string)$getter);
                $xAxe++;
            }
            $yAxe++;
        }

        // Format
        $sheet->getStyle(
            "A1:" . $sheet->getHighestDataColumn() . 1)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        );
        $sheet->getStyle(
            "A2:" . $sheet->getHighestDataColumn() . $sheet->getHighestDataRow())->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT
        );

        // Resize columns
        for ($i = 'A'; $i != $sheet->getHighestDataColumn(); $i++) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }

        $writer = $this->container->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');

        $fileName = 'PrivaciaShop-Extraction-Devis--' . date('Y-m-d') . '.xlsx';

        // create the response
        $response = $this->container->get('phpexcel')->createStreamedResponse($writer);

        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * @Route("/quoteRequest/view/{id}", name="paprec_quoteRequest_view")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function viewAction(Request $request, QuoteRequest $quoteRequest)
    {
        $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');
        $quoteRequestManager->isDeleted($quoteRequest, true);


        return $this->render('PaprecCommercialBundle:QuoteRequest:view.html.twig', array(
            'quoteRequest' => $quoteRequest,
        ));
    }

    /**
     * @Route("/quoteRequest/add", name="paprec_quoteRequest_add")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $numberManager = $this->get('paprec_catalog.number_manager');
        $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');

        $quoteRequest = $quoteRequestManager->add(false);

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

        $destructionType = array();
        foreach ($this->getParameter('paprec_quote_destruction_type') as $d) {
            $destructionType[$d] = $d;
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
            'destructionType' => $destructionType,
            'floorNumber' => $floorNumber
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $quoteRequest = $form->getData();

            $quoteRequest->setOverallDiscount($numberManager->normalize($quoteRequest->getOverallDiscount()));
            $quoteRequest->setTollFees($numberManager->normalize($quoteRequest->getTollFees()));
            $quoteRequest->setAnnualBudget($numberManager->normalize($quoteRequest->getAnnualBudget()));

            if ($quoteRequest->getPostalCode()) {
                $quoteRequest->setTransportRate($quoteRequest->getPostalCode()->getTransportRate());
            } else {
                /**
                 * Si pas de code postal, on met le à 1 par défaut
                 */
                $quoteRequest->setTransportRate($numberManager->normalize15(1));
            }

            $quoteRequest->setUserCreation($user);

            $reference = $quoteRequestManager->generateReference($quoteRequest);
            $quoteRequest->setReference($reference);


            $em = $this->getDoctrine()->getManager();
            $em->persist($quoteRequest);
            $em->flush();

            return $this->redirectToRoute('paprec_quoteRequest_view', array(
                'id' => $quoteRequest->getId()
            ));

        }

        return $this->render('PaprecCommercialBundle:QuoteRequest:add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/quoteRequest/edit/{id}", name="paprec_quoteRequest_edit")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Exception
     */
    public function editAction(Request $request, QuoteRequest $quoteRequest)
    {
        $user = $this->getUser();

        $numberManager = $this->get('paprec_catalog.number_manager');
        $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');
        $quoteRequestManager->isDeleted($quoteRequest, true);

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

        $destructionType = array();
        foreach ($this->getParameter('paprec_quote_destruction_type') as $d) {
            $destructionType[$d] = $d;
        }

        $floorNumber = array();
        foreach ($this->getParameter('paprec_quote_floor_number') as $f) {
            $floorNumber[$f] = $f;
        }

        $quoteRequest->setOverallDiscount($numberManager->denormalize($quoteRequest->getOverallDiscount()));
        $quoteRequest->setTollFees($numberManager->denormalize($quoteRequest->getTollFees()));
        $quoteRequest->setAnnualBudget($numberManager->denormalize($quoteRequest->getAnnualBudget()));

        $form = $this->createForm(QuoteRequestType::class, $quoteRequest, array(
            'status' => $status,
            'locales' => $locales,
            'access' => $access,
            'staff' => $staff,
            'destructionType' => $destructionType,
            'floorNumber' => $floorNumber
        ));

        $savedCommercial = $quoteRequest->getUserInCharge();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quoteRequest = $form->getData();
            $quoteRequest->setOverallDiscount($numberManager->normalize($quoteRequest->getOverallDiscount()));
            $quoteRequest->setTollFees($numberManager->normalize($quoteRequest->getTollFees()));
            $quoteRequest->setAnnualBudget($numberManager->normalize($quoteRequest->getAnnualBudget()));

            if ($quoteRequest->getQuoteRequestLines()) {
                foreach ($quoteRequest->getQuoteRequestLines() as $line) {
                    $quoteRequestManager->editLine($quoteRequest, $line, $user, false, false);
                }
            }
            $quoteRequest->setTotalAmount($quoteRequestManager->calculateTotal($quoteRequest));

            $quoteRequest->setDateUpdate(new \DateTime());
            $quoteRequest->setUserUpdate($user);

            /**
             * Si le commercial en charge a changé, alors on envoie un mail au nouveau commercial
             */
            if ($quoteRequest->getUserInCharge() && ((!$savedCommercial && $quoteRequest->getUserInCharge())
                    || ($savedCommercial && $savedCommercial->getId() !== $quoteRequest->getUserInCharge()->getId()))) {
                $quoteRequestManager->sendNewRequestEmail($quoteRequest);
                $this->get('session')->getFlashBag()->add('success', 'newUserInChargeWarned');
            }


            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('paprec_quoteRequest_view', array(
                'id' => $quoteRequest->getId()
            ));

        }

        return $this->render('PaprecCommercialBundle:QuoteRequest:edit.html.twig', array(
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest
        ));
    }

    /**
     * @Route("/quoteRequest/remove/{id}", name="paprec_quoteRequest_remove")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
     */
    public function removeAction(Request $request, QuoteRequest $quoteRequest)
    {
        $em = $this->getDoctrine()->getManager();

        $quoteRequest->setDeleted(new \DateTime());
        $em->flush();

        return $this->redirectToRoute('paprec_quoteRequest_index');
    }

    /**
     * @Route("/quoteRequest/removeMany/{ids}", name="paprec_quoteRequest_removeMany")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
     */
    public function removeManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $quoteRequests = $em->getRepository('PaprecCommercialBundle:QuoteRequest')->findById($ids);
            foreach ($quoteRequests as $quoteRequest) {
                $quoteRequest->setDeleted(new \DateTime);
            }
            $em->flush();
        }

        return $this->redirectToRoute('paprec_quoteRequest_index');
    }

    /**
     * @Route("/quoteRequest/{id}/addLine", name="paprec_quoteRequest_addLine")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
     */
    public function addLineAction(Request $request, QuoteRequest $quoteRequest)
    {

        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        if ($quoteRequest->getDeleted() !== null) {
            throw new NotFoundHttpException();
        }

        $quoteRequestLine = new QuoteRequestLine();

        $form = $this->createForm(QuoteRequestLineAddType::class, $quoteRequestLine);


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');

            $quoteRequestLine = $form->getData();
            $quoteRequestManager->addLine($quoteRequest, $quoteRequestLine, $user);

            return $this->redirectToRoute('paprec_quoteRequest_view', array(
                'id' => $quoteRequest->getId()
            ));

        }

        return $this->render('PaprecCommercialBundle:QuoteRequestLine:add.html.twig', array(
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
        ));
    }

    /**
     * @Route("/quoteRequest/{id}/editLine/{quoteLineId}", name="paprec_quoteRequest_editLine")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
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

        $form = $this->createForm(QuoteRequestLineEditType::class, $quoteRequestLine);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');

            $quoteRequestManager->editLine($quoteRequest, $quoteRequestLine, $user);

            return $this->redirectToRoute('paprec_quoteRequest_view', array(
                'id' => $quoteRequest->getId()
            ));
        }

        return $this->render('PaprecCommercialBundle:QuoteRequestLine:edit.html.twig', array(
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
            'quoteRequestLine' => $quoteRequestLine
        ));
    }

    /**
     * @Route("/quoteRequest/{id}/removeLine/{quoteLineId}", name="paprec_quoteRequest_removeLine")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
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


        $em = $this->getDoctrine()->getManager();

        $em->remove($quoteRequestLine);
        $em->flush();

        $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');
        $total = $quoteRequestManager->calculateTotal($quoteRequest);
        $quoteRequest->setTotalAmount($total);
        $em->flush();


        return $this->redirectToRoute('paprec_quoteRequest_view', array(
            'id' => $quoteRequest->getId()
        ));
    }

    /**
     * @Route("/quoteRequest/{id}/sendGeneratedQuote", name="paprec_quoteRequest_sendGeneratedQuote")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Exception
     */
    public function sendGeneratedQuoteAction(QuoteRequest $quoteRequest)
    {
        $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');
        $quoteRequestManager->isDeleted($quoteRequest, true);

        if ($quoteRequest->getPostalCode()) {
            $sendQuote = $quoteRequestManager->sendGeneratedQuoteEmail($quoteRequest);
            if ($sendQuote) {
                $this->get('session')->getFlashBag()->add('success', 'generatedQuoteSent');
            } else {
                $this->get('session')->getFlashBag()->add('error', 'generatedQuoteNotSent');
            }
        }

        $quoteRequest->setQuoteStatus('PROCESSING');
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->redirectToRoute('paprec_quoteRequest_view', array(
            'id' => $quoteRequest->getId()
        ));
    }

    /**
     * @Route("/quoteRequest/{id}/sendGeneratedContract", name="paprec_quoteRequest_sendGeneratedContract")
     * @Security("has_role('ROLE_COMMERCIAL') or (has_role('ROLE_DIVISION') and 'DI' in user.getDivisions())")
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Exception
     */
    public function sendGeneratedContractAction(QuoteRequest $quoteRequest)
    {
        $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');
        $quoteRequestManager->isDeleted($quoteRequest, true);

        if ($quoteRequest->getPostalCode()) {
            $sendContract = $quoteRequestManager->sendGeneratedContractEmail($quoteRequest);
            if ($sendContract) {
                $this->get('session')->getFlashBag()->add('success', 'generatedContractSent');
            } else {
                $this->get('session')->getFlashBag()->add('error', 'generatedContractNotSent');
            }
        }

        return $this->redirectToRoute('paprec_quoteRequest_view', array(
            'id' => $quoteRequest->getId()
        ));
    }

    /**
     * @Route("/quoteRequest/{id}/downloadQuote", name="paprec_quote_request_download")
     * @Security("has_role('ROLE_COMMERCIAL')")
     * @throws \Exception
     */
    public function downloadAssociatedInvoiceAction(QuoteRequest $quoteRequest)
    {

        /**
         * On commence par pdf générés (seulement ceux générés dans le BO  pour éviter de supprimer un PDF en cours d'envoi pour un utilisateur
         */
        $pdfFolder = $this->container->getParameter('paprec_commercial.data_tmp_directory');
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

        $quoteRequestManager = $this->get('paprec_commercial.quote_request_manager');
        $user = $this->getUser();
        $pdfTmpFolder = $pdfFolder . '/';

        $locale = 'fr';

        $file = $quoteRequestManager->generatePDF($quoteRequest, $locale, false);

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
            $quoteRequest->getReference() . '-' . $this->get('translator')->trans('Commercial.QuoteRequest.DownloadedQuoteName',
                array(), 'messages', $locale) . '-' . $quoteRequest->getBusinessName() . '.pdf'
        );

        return $response;
    }
}
