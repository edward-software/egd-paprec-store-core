<?php

namespace App\Controller;

use App\Entity\PostalCode;
use App\Form\PostalCodeType;
use App\Service\NumberManager;
use App\Service\PostalCodeManager;
use App\Service\ProductLabelManager;
use App\Service\ProductManager;
use App\Tools\DataTable;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PostalCodeController extends AbstractController
{

    private $em;
    private $numberManager;
    private $postalCodeManager;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        NumberManager $numberManager,
        PostalCodeManager $postalCodeManager
    ) {
        $this->em = $em;
        $this->numberManager = $numberManager;
        $this->postalCodeManager = $postalCodeManager;
        $this->translator = $translator;
    }


    /**
     * @Route("/postalCode", name="paprec_postalCode_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('postalCode/index.html.twig');
    }

    /**
     * @Route("/postalCode/loadList", name="paprec_postalCode_loadList")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function loadListAction(Request $request, DataTable $dataTable, PaginatorInterface $paginator)
    {
        $return = [];

        $filters = $request->get('filters');
        $pageSize = $request->get('length');
        $start = $request->get('start');
        $orders = $request->get('order');
        $search = $request->get('search');
        $columns = $request->get('columns');
        $rowPrefix = $request->get('rowPrefix');

        $cols['id'] = array('label' => 'id', 'id' => 'pC.id', 'method' => array('getId'));
        $cols['code'] = array('label' => 'code', 'id' => 'pC.code', 'method' => array('getCode'));
        $cols['city'] = array('label' => 'city', 'id' => 'pC.city', 'method' => array('getCity'));
        $cols['agency'] = array('label' => 'agency', 'id' => 'pC.agency', 'method' => array('getAgency', 'getName'));
        $cols['zone'] = array('label' => 'zone', 'id' => 'pC.zone', 'method' => array('getZone'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(PostalCode::class)->createQueryBuilder('pC');


        $queryBuilder->select(array('pC'))
            ->where('pC.deleted IS NULL');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) == '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('pC.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('pC.code', '?1'),
                    $queryBuilder->expr()->like('pC.zone', '?1'),
                    $queryBuilder->expr()->like('pC.city', '?1')
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
     * @Route("/postalCode/export", name="paprec_postalCode_export")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function exportAction(Request $request)
    {
        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(PostalCode::class)->createQueryBuilder('pC');

        $queryBuilder->select(array('pC'))
            ->where('pC.deleted IS NULL');

        $postalCodes = $queryBuilder->getQuery()->getResult();

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()->setCreator("Privacia Shop")
            ->setLastModifiedBy("Privacia Shop")
            ->setTitle("Privacia Shop - Postal codes")
            ->setSubject("Extract");

        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Postal Codes');

        $sheet
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', 'Code')
            ->setCellValue('C1', 'Commune')
            ->setCellValue('D1', 'Tariff zone')
            ->setCellValue('E1', 'Rental rate')
            ->setCellValue('F1', 'Transport rate')
            ->setCellValue('G1', 'Treatment rate')
            ->setCellValue('H1', 'Treacability rate')
            ->setCellValue('I1', 'Salesman in charge');

        $i = 2;
        foreach ($postalCodes as $postalCode) {

            $sheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $postalCode->getId())
                ->setCellValue('B' . $i, $postalCode->getCode())
                ->setCellValue('C' . $i, $postalCode->getCity())
                ->setCellValue('D' . $i, $postalCode->getZone())
                ->setCellValue('E' . $i, $this->numberManager->denormalize15($postalCode->getRentalRate()))
                ->setCellValue('F' . $i, $this->numberManager->denormalize15($postalCode->getTransportRate()))
                ->setCellValue('G' . $i, $this->numberManager->denormalize15($postalCode->getTreatmentRate()))
                ->setCellValue('H' . $i, $this->numberManager->denormalize15($postalCode->getTraceabilityRate()))
                ->setCellValue('I' . $i, ($postalCode->getUserInCharge()) ? $postalCode->getUserInCharge()->getEmail() : '');
            $i++;
        }


        $fileName = 'PrivaciaShop-Extract-Postal-Codes-' . date('Y-m-d') . '.xlsx';


        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($spreadsheet) {
            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $streamedResponse->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $streamedResponse->headers->set('Pragma', 'public');
        $streamedResponse->headers->set('Cache-Control', 'maxage=1');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename=' . $fileName);

        return $streamedResponse->send();

    }

    /**
     * @Route("/postalCode/view/{id}", name="paprec_postalCode_view")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function viewAction(Request $request, PostalCode $postalCode)
    {
        $this->postalCodeManager->isDeleted($postalCode, true);

        return $this->render('postalCode/view.html.twig', array(
            'postalCode' => $postalCode
        ));
    }

    /**
     * @Route("/postalCode/add", name="paprec_postalCode_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $postalCode = new PostalCode();

        $form = $this->createForm(PostalCodeType::class, $postalCode);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $postalCode = $form->getData();
            $postalCode->setRentalRate($this->numberManager->normalize15($postalCode->getRentalRate()));
            $postalCode->setTransportRate($this->numberManager->normalize15($postalCode->getTransportRate()));
            $postalCode->setTreatmentRate($this->numberManager->normalize15($postalCode->getTreatmentRate()));
            $postalCode->setTraceabilityRate($this->numberManager->normalize15($postalCode->getTraceabilityRate()));

            $postalCode->setDateCreation(new \DateTime);
            $postalCode->setUserCreation($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($postalCode);
            $em->flush();

            return $this->redirectToRoute('paprec_postalCode_view', array(
                'id' => $postalCode->getId()
            ));

        }

        return $this->render('postalCode/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/postalCode/edit/{id}", name="paprec_postalCode_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function editAction(Request $request, PostalCode $postalCode)
    {
        $user = $this->getUser();

        $this->postalCodeManager->isDeleted($postalCode, true);

        $postalCode->setRentalRate($this->numberManager->denormalize15($postalCode->getRentalRate()));
        $postalCode->setTransportRate($this->numberManager->denormalize15($postalCode->getTransportRate()));
        $postalCode->setTreatmentRate($this->numberManager->denormalize15($postalCode->getTreatmentRate()));
        $postalCode->setTraceabilityRate($this->numberManager->denormalize15($postalCode->getTraceabilityRate()));

        $form = $this->createForm(PostalCodeType::class, $postalCode);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $postalCode = $form->getData();

            $postalCode->setRentalRate($this->numberManager->normalize15($postalCode->getRentalRate()));
            $postalCode->setTransportRate($this->numberManager->normalize15($postalCode->getTransportRate()));
            $postalCode->setTreatmentRate($this->numberManager->normalize15($postalCode->getTreatmentRate()));
            $postalCode->setTraceabilityRate($this->numberManager->normalize15($postalCode->getTraceabilityRate()));

            $postalCode->setDateUpdate(new \DateTime);
            $postalCode->setUserUpdate($user);

            $em = $this->getDoctrine()->getManager();
            $em->flush();
            
            return $this->redirectToRoute('paprec_postalCode_view', array(
                'id' => $postalCode->getId()
            ));

        }
        
        return $this->render('postalCode/edit.html.twig', array(
            'form' => $form->createView(),
            'postalCode' => $postalCode
        ));
    }

    /**
     * @Route("/postalCode/remove/{id}", name="paprec_postalCode_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, PostalCode $postalCode)
    {
        $em = $this->getDoctrine()->getManager();

        $postalCode->setDeleted(new \DateTime());
        $em->flush();

        return $this->redirectToRoute('paprec_postalCode_index');
    }

    /**
     * @Route("/postalCode/removeMany/{ids}", name="paprec_postalCode_removeMany")
     * @Security("has_role('ROLE_ADMIN')")
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
            $postalCodes = $em->getRepository('App:PostalCode')->findById($ids);
            foreach ($postalCodes as $postalCode) {
                $postalCode->setDeleted(new \DateTime);
            }
            $em->flush();
        }

        return $this->redirectToRoute('paprec_postalCode_index');
    }

    /**
     * @Route("/postalCode/autocomplete", name="paprec_postalCode_autocomplete")
     * @throws \Exception
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

}
