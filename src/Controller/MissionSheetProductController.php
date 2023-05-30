<?php

namespace App\Controller;

use App\Entity\FollowUp;
use App\Entity\Picture;
use App\Entity\MissionSheetProduct;
use App\Entity\MissionSheetProductLabel;
use App\Entity\Range;
use App\Form\PictureMissionSheetProductType;
use App\Form\MissionSheetProductLabelType;
use App\Form\MissionSheetProductMaterialType;
use App\Form\MissionSheetProductType;
use App\Form\SettingType;
use App\Service\NumberManager;
use App\Service\PictureManager;
use App\Service\MissionSheetProductLabelManager;
use App\Service\MissionSheetProductManager;
use App\Tools\DataTable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class MissionSheetProductController extends AbstractController
{

    private $em;
    private $numberManager;
    private $missionSheetProductManager;
    private $missionSheetProductLabelManager;
    private $translator;
    private $pictureManager;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        NumberManager $numberManager,
        MissionSheetProductManager $missionSheetProductManager,
        MissionSheetProductLabelManager $missionSheetProductLabelManager,
        PictureManager $pictureManager
    ) {
        $this->em = $em;
        $this->numberManager = $numberManager;
        $this->missionSheetProductManager = $missionSheetProductManager;
        $this->missionSheetProductLabelManager = $missionSheetProductLabelManager;
        $this->translator = $translator;
        $this->pictureManager = $pictureManager;
    }

    /**
     * @Route("", name="paprec_mission_sheet_product_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(Range::class)->createQueryBuilder('r');

        $queryBuilder->select('r')
            ->leftJoin('r.rangeLabels', 'rL')
            ->where('r.deleted IS NULL')
            ->andWhere('rL.language = :language')
            ->setParameter('language', 'FR');

        $ranges = $queryBuilder->getQuery()->getResult();

        return $this->render('missionSheetProduct/index.html.twig', [
            'ranges' => $ranges
        ]);
    }

    /**
     * @Route("/loadList", name="paprec_mission_sheet_product_loadList")
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

        $selectedCatalog = $request->get('selectedCatalog');
        $selectedRange = $request->get('selectedRange');
        $isEnabled = $request->get('isEnabled');

        $cols['id'] = array('label' => 'id', 'id' => 'p.id', 'method' => array('getId'));
        $cols['name'] = array(
            'label' => 'name',
            'id' => 'pL.name',
            'method' => array(array('getMissionSheetProductLabels', 0), 'getName')
        );
        $cols['range'] = array(
            'label' => 'range',
            'id' => 'rL.name',
            'method' => array('getRange', array(array('getRangeLabels', 0), 'getName'))
        );
        $cols['catalog'] = array(
            'label' => 'catalog',
            'id' => 'p.catalog',
            'method' => ['getCatalog']
        );
        $cols['catalogLabel'] = array(
            'label' => 'catalogLabel',
            'id' => 'p.catalog',
            'method' => ['getCatalog']
        );
        $cols['isEnabled'] = array('label' => 'isEnabled', 'id' => 'p.isEnabled', 'method' => array('getIsEnabled'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(MissionSheetProduct::class)->createQueryBuilder('p');

        $queryBuilder->select(array('p', 'pL', 'r', 'rL'))
            ->leftJoin('p.missionSheetProductLabels', 'pL')
            ->leftJoin('p.range', 'r')
            ->leftJoin('r.rangeLabels', 'rL')
            ->where('p.deleted IS NULL')
            ->andWhere('pL.language = :language')
            ->setParameter('language', 'FR');

        /**
         * Application des filtres
         */
        if ($selectedCatalog !== null && $selectedCatalog !== '#') {
            $queryBuilder
                ->andWhere('p.catalog = :catalog')
                ->setParameter('catalog', $selectedCatalog);
        }
        if ($selectedRange !== null && $selectedRange !== '#') {
            $queryBuilder
                ->andWhere('r.id = :rangeId')
                ->setParameter('rangeId', $selectedRange);
        }
        if ($isEnabled !== null && $isEnabled !== '#') {
            $queryBuilder
                ->andWhere('p.isEnabled = :isEnabled')
                ->setParameter('isEnabled', $isEnabled);
        }

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('p.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('pL.name', '?1'),
                    $queryBuilder->expr()->like('p.dimensions', '?1'),
                    $queryBuilder->expr()->like('p.isEnabled', '?1'),
                    $queryBuilder->expr()->like('rL.name', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);

        // Reformatage de certaines données
        $tmp = array();
        foreach ($dt['data'] as $data) {
            $line = $data;
            $line['isEnabled'] = $data['isEnabled'] ? $this->translator->trans('General.1') : $this->translator->trans('General.0');
            if ($line['catalog']) {
                $line['catalog'] = $this->translator->trans('Catalog.MissionSheetProduct.Catalog.' . strtoupper($line['catalog']));
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
     * @Route("/export",  name="paprec_mission_sheet_product_export")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function exportAction(Request $request)
    {
        $language = $request->getLocale();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder->select(array('p'))
            ->from('App:MissionSheetProduct', 'p')
            ->where('p.deleted IS NULL');

        $missionSheetProducts = $queryBuilder->getQuery()->getResult();

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()->setCreator("EasyRecyclageShop")
            ->setLastModifiedBy("EasyRecyclageShop")
            ->setTitle("EasyRecyclageShop - MissionSheetProducts")
            ->setSubject("Extract");

        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('MissionSheetProducts');

        // Labels
        $sheetLabels = [
            'P. ID',
            'Creation date',
            'Update date',
            'Deleted',
            'Capacity',
            'Capacity unit',
            'Dimensions',
            'is Enabled',
            'Rental unit price',
            'Transport UP',
            'Treatment UP',
            'Traceability UP',
            'Position',
            'User creation ID',
            'User update ID',
            'PL. ID',
            'Name',
            'Short desc.',
            'Language',
        ];

        $xAxe = 'A';
        foreach ($sheetLabels as $label) {
            $sheet->setCellValue($xAxe . 1, $label);
            $xAxe++;
        }

        $yAxe = 2;

        /** @var MissionSheetProduct $missionSheetProduct */
        foreach ($missionSheetProducts as $missionSheetProduct) {

            /** @var MissionSheetProductLabel $missionSheetProductLabel */
            $missionSheetProductLabel = $this->missionSheetProductManager->getMissionSheetProductLabelByMissionSheetProductAndLocale($missionSheetProduct, strtoupper($language));

            // Getters
            $getters = [
                $missionSheetProduct->getId(),
                $missionSheetProduct->getDateCreation()->format('Y-m-d'),
                ($missionSheetProduct->getDateUpdate() == null) ? null : $missionSheetProduct->getDateUpdate()->format('Y-m-d'),
                $missionSheetProduct->getDeleted() ? 'true' : 'false',
                $missionSheetProduct->getCapacity(),
                $missionSheetProduct->getCapacityUnit(),
                $missionSheetProduct->getDimensions(),
                $missionSheetProduct->getIsEnabled(),
                $this->numberManager->denormalize($missionSheetProduct->getRentalUnitPrice()),
                $this->numberManager->denormalize($missionSheetProduct->getTransportUnitPrice()),
                $this->numberManager->denormalize($missionSheetProduct->getTreatmentUnitPrice()),
                $this->numberManager->denormalize($missionSheetProduct->getTraceabilityUnitPrice()),
                $missionSheetProduct->getPosition(),
                $missionSheetProduct->getUserCreation(),
                $missionSheetProduct->getUserUpdate(),
                $missionSheetProductLabel->getId(),
                $missionSheetProductLabel->getName(),
                $missionSheetProductLabel->getShortDescription(),
                $missionSheetProductLabel->getLanguage(),
            ];

            $xAxe = 'A';
            foreach ($getters as $getter) {
                $sheet->setCellValue($xAxe . $yAxe, (string)$getter);
                $xAxe++;
            }
            $yAxe++;
        }


        // Resize columns
        for ($i = 'A'; $i <= $sheet->getHighestDataColumn(); $i++) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }

        $fileName = 'EasyRecyclageShop-Extraction-MissionSheetProducts-' . date('Y-m-d') . '.xlsx';

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
     * @Route("/view/{id}",  name="paprec_mission_sheet_product_view")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function viewAction(Request $request, MissionSheetProduct $missionSheetProduct)
    {

        $this->missionSheetProductManager->isDeleted($missionSheetProduct, true);

        $language = $request->getLocale();
        $missionSheetProductLabel = $this->missionSheetProductManager->getMissionSheetProductLabelByMissionSheetProductAndLocale($missionSheetProduct, strtoupper($language));

        $otherMissionSheetProductLabels = $this->missionSheetProductManager->getMissionSheetProductLabels($missionSheetProduct);

        $tmp = array();
        foreach ($otherMissionSheetProductLabels as $pL) {
            if ($pL->getId() != $missionSheetProductLabel->getId()) {
                $tmp[] = $pL;
            }
        }
        $otherMissionSheetProductLabels = $tmp;


        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $picture = new Picture();

        $formAddPicture = $this->createForm(PictureMissionSheetProductType::class, $picture, array(
            'types' => $types
        ));

        $formEditPicture = $this->createForm(PictureMissionSheetProductType::class, $picture, array(
            'types' => $types
        ));


        return $this->render('missionSheetProduct/view.html.twig', array(
            'missionSheetProduct' => $missionSheetProduct,
            'missionSheetProductLabel' => $missionSheetProductLabel,
            'formAddPicture' => $formAddPicture->createView(),
            'formEditPicture' => $formEditPicture->createView(),
            'otherMissionSheetProductLabels' => $otherMissionSheetProductLabels
        ));
    }

    /**
     * @Route("/add",  name="paprec_mission_sheet_product_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $transportTypes = array();
        foreach ($this->getParameter('paprec_transport_types') as $transportType) {
            $transportTypes[$transportType] = $transportType;
        }

        $qb = $this->getDoctrine()->getManager()->getRepository(Range::class)->createQueryBuilder('r');

        $qb
            ->select(array('r', 'rL'))
            ->leftJoin('r.rangeLabels', 'rL')
            ->where('r.deleted IS NULL')
            ->andWhere('rL.language = :language')
            ->orderBy('r.position', 'ASC')
            ->setParameter('language', 'FR')
            ->andWhere('r.catalog != :catalog')
            ->setParameter('catalog', 'MATERIAL');
        $ranges = $qb->getQuery()->getResult();

        $rangesByCatalog = [];
        if (is_array($ranges) && count($ranges)) {
            foreach ($ranges as $range) {
                if (is_iterable($range->getRangeLabels()) && $range->getRangeLabels()[0]) {
                    $rangesByCatalog[$range->getCatalog()][] = [
                        'id' => $range->getId(),
                        'name' => $range->getRangeLabels()[0]->getName()
                    ];
                }
            }
        }

        $missionSheetProduct = new MissionSheetProduct();
        $missionSheetProductLabel = new MissionSheetProductLabel();

        $form1 = $this->createForm(MissionSheetProductType::class, $missionSheetProduct, array(
            'transportTypes' => $transportTypes,
            'defaultFrequencyTimes' => '5'
        ));
        $form2 = $this->createForm(MissionSheetProductLabelType::class, $missionSheetProductLabel, array(
            'languages' => $languages,
            'language' => 'FR'
        ));

        $form1->handleRequest($request);
        $form2->handleRequest($request);
        if ($form1->isSubmitted()) {
            $hideCapacity = $form1->get('hideCapacity')->getData();
            $hideDimension = $form1->get('hideDimension')->getData();
            $capacity = $form1->get('capacity')->getData();
            $dimensions = $form1->get('dimensions')->getData();

            $errors = [];
            if (!$capacity && ($hideCapacity === null || $hideCapacity === 0)) {
                $errors['capacity'] = array(
                    'code' => 400,
                    'message' => 'Cette valeur ne doit pas être vide.'
                );
            }
            if (!$dimensions && ($hideDimension === null || $hideDimension === 0)) {
                $errors['dimensions'] = array(
                    'code' => 400,
                    'message' => 'Cette valeur ne doit pas être vide.'
                );
            }

            if ($errors && count($errors)) {
                foreach ($errors as $key => $error) {
                    $form1->get($key)->addError(new FormError('Cette valeur ne doit pas être vide.'));
                }
            }

            if ($form1->isValid() && $form2->isSubmitted() && $form2->isValid()) {

                $missionSheetProduct = $form1->getData();


                if($missionSheetProduct->getHideFrequency() === 1){
                    $missionSheetProduct->setFrequency('UNKNOWN');
                }

                if($missionSheetProduct->getHideCapacity() === 1){
                    $missionSheetProduct->setCapacity('');
                }

                if($missionSheetProduct->getHideDimension() === 1){
                    $missionSheetProduct->setDimensions('');
                }

                $missionSheetProduct->setRentalUnitPrice($this->numberManager->normalize($missionSheetProduct->getRentalUnitPrice()));
                $missionSheetProduct->setTransportUnitPrice($this->numberManager->normalize($missionSheetProduct->getTransportUnitPrice()));
                $missionSheetProduct->setTreatmentUnitPrice($this->numberManager->normalize($missionSheetProduct->getTreatmentUnitPrice()));
                $missionSheetProduct->setTraceabilityUnitPrice($this->numberManager->normalize($missionSheetProduct->getTraceabilityUnitPrice()));

                $missionSheetProduct->setDateCreation(new \DateTime);
                $missionSheetProduct->setUserCreation($user);

                $this->em->persist($missionSheetProduct);
                $this->em->flush();

                $missionSheetProductLabel = $form2->getData();
                $missionSheetProductLabel->setDateCreation(new \DateTime);
                $missionSheetProductLabel->setUserCreation($user);
                $missionSheetProductLabel->setMissionSheetProduct($missionSheetProduct);

                $this->em->persist($missionSheetProductLabel);
                $this->em->flush();

                return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
                    'id' => $missionSheetProduct->getId()
                ));

            }
        }

        return $this->render('missionSheetProduct/add.html.twig', array(
            'form1' => $form1->createView(),
            'form2' => $form2->createView(),
            'rangesByCatalog' => $rangesByCatalog,
            'defaultCatalog' => 'REGULAR',
            'defaultRangeId' => null
        ));
    }

    /**
     * @Route("/addMaterial",  name="paprec_mission_sheet_product_material_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addMaterialAction(Request $request)
    {
        $user = $this->getUser();

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $missionSheetProduct = new MissionSheetProduct();
        $missionSheetProductLabel = new MissionSheetProductLabel();
        $missionSheetProduct->setCatalog('MATERIAL');
        $missionSheetProduct->setTransportType(null);

        $form1 = $this->createForm(MissionSheetProductMaterialType::class, $missionSheetProduct);
        $form2 = $this->createForm(MissionSheetProductLabelType::class, $missionSheetProductLabel, array(
            'languages' => $languages,
            'language' => 'FR'
        ));

        $form1->handleRequest($request);
        $form2->handleRequest($request);

        if ($form1->isSubmitted()) {
            $hideCapacity = $form1->get('hideCapacity')->getData();
            $hideDimension = $form1->get('hideDimension')->getData();
            $capacity = $form1->get('capacity')->getData();
            $dimensions = $form1->get('dimensions')->getData();

            $errors = [];
            if (!$capacity && ($hideCapacity === null || $hideCapacity === 0)) {
                $errors['capacity'] = array(
                    'code' => 400,
                    'message' => 'Cette valeur ne doit pas être vide.'
                );
            }
            if (!$dimensions && ($hideDimension === null || $hideDimension === 0)) {
                $errors['dimensions'] = array(
                    'code' => 400,
                    'message' => 'Cette valeur ne doit pas être vide.'
                );
            }

            if ($errors && count($errors)) {
                foreach ($errors as $key => $error) {
                    $form1->get($key)->addError(new FormError('Cette valeur ne doit pas être vide.'));
                }
            }
            if ($form1->isValid() && $form2->isSubmitted() && $form2->isValid()) {

                $missionSheetProduct = $form1->getData();


                if($missionSheetProduct->getHideFrequency() === 1){
                    $missionSheetProduct->setFrequency('UNKNOWN');
                }

                if($missionSheetProduct->getHideCapacity() === 1){
                    $missionSheetProduct->setCapacity('');
                }

                if($missionSheetProduct->getHideDimension() === 1){
                    $missionSheetProduct->setDimensions('');
                }

                $missionSheetProduct->setRentalUnitPrice($this->numberManager->normalize($missionSheetProduct->getRentalUnitPrice()));
                $missionSheetProduct->setTransportUnitPrice($this->numberManager->normalize($missionSheetProduct->getTransportUnitPrice()));
                $missionSheetProduct->setTreatmentUnitPrice($this->numberManager->normalize($missionSheetProduct->getTreatmentUnitPrice()));
                $missionSheetProduct->setTraceabilityUnitPrice($this->numberManager->normalize($missionSheetProduct->getTraceabilityUnitPrice()));
                $missionSheetProduct->setMaterialUnitPrice($this->numberManager->normalize($missionSheetProduct->getMaterialUnitPrice()));

                $missionSheetProduct->setDateCreation(new \DateTime);
                $missionSheetProduct->setUserCreation($user);

                $this->em->persist($missionSheetProduct);
                $this->em->flush();

                $missionSheetProductLabel = $form2->getData();
                $missionSheetProductLabel->setDateCreation(new \DateTime);
                $missionSheetProductLabel->setUserCreation($user);
                $missionSheetProductLabel->setMissionSheetProduct($missionSheetProduct);

                $this->em->persist($missionSheetProductLabel);
                $this->em->flush();

                return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
                    'id' => $missionSheetProduct->getId()
                ));

            }
        }

        return $this->render('missionSheetProduct/addMaterial.html.twig', array(
            'form1' => $form1->createView(),
            'form2' => $form2->createView()
        ));
    }

    /**
     * @Route("/edit/{id}",  name="paprec_mission_sheet_product_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Exception
     */
    public function editAction(Request $request, MissionSheetProduct $missionSheetProduct)
    {
        $this->missionSheetProductManager->isDeleted($missionSheetProduct, true);

        $user = $this->getUser();

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }
        $transportTypes = array();
        foreach ($this->getParameter('paprec_transport_types') as $transportType) {
            $transportTypes[$transportType] = $transportType;
        }

        $qb = $this->getDoctrine()->getManager()->getRepository(Range::class)->createQueryBuilder('r');

        $qb
            ->select(array('r', 'rL'))
            ->leftJoin('r.rangeLabels', 'rL')
            ->where('r.deleted IS NULL')
            ->andWhere('rL.language = :language')
            ->orderBy('r.position', 'ASC')
            ->setParameter('language', 'FR')
            ->andWhere('r.catalog != :catalog')
            ->setParameter('catalog', 'MATERIAL');
        $ranges = $qb->getQuery()->getResult();

        $rangesByCatalog = [];
        if (is_array($ranges) && count($ranges)) {
            foreach ($ranges as $range) {
                if (is_iterable($range->getRangeLabels()) && $range->getRangeLabels()[0]) {
                    $rangesByCatalog[$range->getCatalog()][] = [
                        'id' => $range->getId(),
                        'name' => $range->getRangeLabels()[0]->getName()
                    ];
                }
            }
        }

        $language = $request->getLocale();
        $missionSheetProductLabel = $this->missionSheetProductManager->getMissionSheetProductLabelByMissionSheetProductAndLocale($missionSheetProduct, strtoupper($language));

        $missionSheetProduct->setRentalUnitPrice($this->numberManager->denormalize($missionSheetProduct->getRentalUnitPrice()));
        $missionSheetProduct->setTransportUnitPrice($this->numberManager->denormalize($missionSheetProduct->getTransportUnitPrice()));
        $missionSheetProduct->setTreatmentUnitPrice($this->numberManager->denormalize($missionSheetProduct->getTreatmentUnitPrice()));
        $missionSheetProduct->setTraceabilityUnitPrice($this->numberManager->denormalize($missionSheetProduct->getTraceabilityUnitPrice()));


        $form1 = $this->createForm(MissionSheetProductType::class, $missionSheetProduct, array(
            'transportTypes' => $transportTypes,
            'defaultFrequencyTimes' => $missionSheetProduct->getFrequencyTimes()
        ));
        $form2 = $this->createForm(MissionSheetProductLabelType::class, $missionSheetProductLabel, array(
            'languages' => $languages,
            'language' => $missionSheetProductLabel->getLanguage()
        ));

        $form1->handleRequest($request);
        $form2->handleRequest($request);

//        if ($form1->isSubmitted() && $form1->isValid() && $form2->isSubmitted() && $form2->isValid()) {
        if ($form1->isSubmitted()) {
            $hideCapacity = $form1->get('hideCapacity')->getData();
            $hideDimension = $form1->get('hideDimension')->getData();
            $capacity = $form1->get('capacity')->getData();
            $dimensions = $form1->get('dimensions')->getData();

            $errors = [];
            if (!$capacity && ($hideCapacity === null || $hideCapacity === 0)) {
                $errors['capacity'] = array(
                    'code' => 400,
                    'message' => 'Cette valeur ne doit pas être vide.'
                );
            }
            if (!$dimensions && ($hideDimension === null || $hideDimension === 0)) {
                $errors['dimensions'] = array(
                    'code' => 400,
                    'message' => 'Cette valeur ne doit pas être vide.'
                );
            }

            if ($errors && count($errors)) {
                foreach ($errors as $key => $error) {
                    $form1->get($key)->addError(new FormError('Cette valeur ne doit pas être vide.'));
                }
            }
            if ($form1->isValid() && $form2->isSubmitted()) {

                $missionSheetProduct = $form1->getData();

                if($missionSheetProduct->getHideFrequency() === 1){
                    $missionSheetProduct->setFrequency('UNKNOWN');
                }

                if($missionSheetProduct->getHideCapacity() === 1){
                    $missionSheetProduct->setCapacity('');
                }

                if($missionSheetProduct->getHideDimension() === 1){
                    $missionSheetProduct->setDimensions('');
                }

                $missionSheetProduct->setRentalUnitPrice($this->numberManager->normalize($missionSheetProduct->getRentalUnitPrice()));
                $missionSheetProduct->setTransportUnitPrice($this->numberManager->normalize($missionSheetProduct->getTransportUnitPrice()));
                $missionSheetProduct->setTreatmentUnitPrice($this->numberManager->normalize($missionSheetProduct->getTreatmentUnitPrice()));
                $missionSheetProduct->setTraceabilityUnitPrice($this->numberManager->normalize($missionSheetProduct->getTraceabilityUnitPrice()));


                $missionSheetProduct->setDateUpdate(new \DateTime);
                $missionSheetProduct->setUserUpdate($user);
                $this->em->flush();

                $missionSheetProductLabel = $form2->getData();
                $missionSheetProductLabel->setDateUpdate(new \DateTime);
                $missionSheetProductLabel->setUserUpdate($user);
                $missionSheetProductLabel->setMissionSheetProduct($missionSheetProduct);

                $this->em->flush();

                return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
                    'id' => $missionSheetProduct->getId()
                ));
            }
        }

        $defaultRangeId = null;
        if ($missionSheetProduct->getRange()) {
            $defaultRangeId = $missionSheetProduct->getRange()->getId();
        }
        return $this->render('missionSheetProduct/edit.html.twig', array(
            'form1' => $form1->createView(),
            'form2' => $form2->createView(),
            'missionSheetProduct' => $missionSheetProduct,
            'missionSheetProductLabel' => $missionSheetProductLabel,
            'rangesByCatalog' => $rangesByCatalog,
            'defaultCatalog' => $missionSheetProduct->getCatalog(),
            'defaultRangeId' => $defaultRangeId
        ));
    }

    /**
     * @Route("/editMaterial/{id}",  name="paprec_mission_sheet_product_material_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Exception
     */
    public function editMaterialAction(Request $request, MissionSheetProduct $missionSheetProduct)
    {
        $this->missionSheetProductManager->isDeleted($missionSheetProduct, true);

        $user = $this->getUser();

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $language = $request->getLocale();
        $missionSheetProductLabel = $this->missionSheetProductManager->getMissionSheetProductLabelByMissionSheetProductAndLocale($missionSheetProduct, strtoupper($language));

        $missionSheetProduct->setRentalUnitPrice($this->numberManager->denormalize($missionSheetProduct->getRentalUnitPrice()));
        $missionSheetProduct->setTransportUnitPrice($this->numberManager->denormalize($missionSheetProduct->getTransportUnitPrice()));
        $missionSheetProduct->setTreatmentUnitPrice($this->numberManager->denormalize($missionSheetProduct->getTreatmentUnitPrice()));
        $missionSheetProduct->setTraceabilityUnitPrice($this->numberManager->denormalize($missionSheetProduct->getTraceabilityUnitPrice()));
        $missionSheetProduct->setMaterialUnitPrice($this->numberManager->denormalize($missionSheetProduct->getMaterialUnitPrice()));


        $form1 = $this->createForm(MissionSheetProductMaterialType::class, $missionSheetProduct);
        $form2 = $this->createForm(MissionSheetProductLabelType::class, $missionSheetProductLabel, array(
            'languages' => $languages,
            'language' => $missionSheetProductLabel->getLanguage()
        ));

        $form1->handleRequest($request);
        $form2->handleRequest($request);

//        if ($form1->isSubmitted() && $form1->isValid() && $form2->isSubmitted() && $form2->isValid()) {
        if ($form1->isSubmitted()) {
            $hideCapacity = $form1->get('hideCapacity')->getData();
            $hideDimension = $form1->get('hideDimension')->getData();
            $capacity = $form1->get('capacity')->getData();
            $dimensions = $form1->get('dimensions')->getData();

            $errors = [];
            if (!$capacity && ($hideCapacity === null || $hideCapacity === 0)) {
                $errors['capacity'] = array(
                    'code' => 400,
                    'message' => 'Cette valeur ne doit pas être vide.'
                );
            }
            if (!$dimensions && ($hideDimension === null || $hideDimension === 0)) {
                $errors['dimensions'] = array(
                    'code' => 400,
                    'message' => 'Cette valeur ne doit pas être vide.'
                );
            }

            if ($errors && count($errors)) {
                foreach ($errors as $key => $error) {
                    $form1->get($key)->addError(new FormError('Cette valeur ne doit pas être vide.'));
                }
            }
            if ($form1->isValid() && $form2->isSubmitted()) {

                $missionSheetProduct = $form1->getData();

                if($missionSheetProduct->getHideFrequency() === 1){
                    $missionSheetProduct->setFrequency('UNKNOWN');
                }

                if($missionSheetProduct->getHideCapacity() === 1){
                    $missionSheetProduct->setCapacity('');
                }

                if($missionSheetProduct->getHideDimension() === 1){
                    $missionSheetProduct->setDimensions('');
                }

                $missionSheetProduct->setRentalUnitPrice($this->numberManager->normalize($missionSheetProduct->getRentalUnitPrice()));
                $missionSheetProduct->setTransportUnitPrice($this->numberManager->normalize($missionSheetProduct->getTransportUnitPrice()));
                $missionSheetProduct->setTreatmentUnitPrice($this->numberManager->normalize($missionSheetProduct->getTreatmentUnitPrice()));
                $missionSheetProduct->setTraceabilityUnitPrice($this->numberManager->normalize($missionSheetProduct->getTraceabilityUnitPrice()));
                $missionSheetProduct->setMaterialUnitPrice($this->numberManager->normalize($missionSheetProduct->getMaterialUnitPrice()));


                $missionSheetProduct->setDateUpdate(new \DateTime);
                $missionSheetProduct->setUserUpdate($user);
                $this->em->flush();

                $missionSheetProductLabel = $form2->getData();
                $missionSheetProductLabel->setDateUpdate(new \DateTime);
                $missionSheetProductLabel->setUserUpdate($user);
                $missionSheetProductLabel->setMissionSheetProduct($missionSheetProduct);

                $this->em->flush();

                return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
                    'id' => $missionSheetProduct->getId()
                ));
            }
        }

        return $this->render('missionSheetProduct/editMaterial.html.twig', array(
            'form1' => $form1->createView(),
            'form2' => $form2->createView(),
            'missionSheetProduct' => $missionSheetProduct,
            'missionSheetProductLabel' => $missionSheetProductLabel
        ));
    }

    /**
     * @Route("/remove/{id}", name="paprec_mission_sheet_product_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, MissionSheetProduct $missionSheetProduct)
    {
        /*
         * Suppression des images
         */
        foreach ($missionSheetProduct->getPictures() as $picture) {
            $this->removeFile($this->getParameter('paprec.missionSheetProduct.picto_path') . '/' . $picture->getPath());
            $missionSheetProduct->removePicture($picture);
        }

        $missionSheetProduct->setDeleted(new \DateTime);
        $missionSheetProduct->setIsEnabled(false);
        $this->em->flush();

        return $this->redirectToRoute('paprec_mission_sheet_product_index');
    }

    /**
     * @Route("/removeMany/{ids}", name="paprec_mission_sheet_product_removeMany")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $missionSheetProducts = $this->em->getRepository('App:MissionSheetProduct')->findById($ids);
            foreach ($missionSheetProducts as $missionSheetProduct) {
                foreach ($missionSheetProduct->getPictures() as $picture) {
                    $this->removeFile($this->getParameter('paprec.missionSheetProduct.picto_path') . '/' . $picture->getPath());
                    $missionSheetProduct->removePicture($picture);
                }

                $missionSheetProduct->setDeleted(new \DateTime());
                $missionSheetProduct->setIsEnabled(false);
            }
            $this->em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }

    /**
     * @Route("/enableMany/{ids}", name="paprec_mission_sheet_product_enableMany")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function enableManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $missionSheetProducts = $this->em->getRepository('App:MissionSheetProduct')->findById($ids);
            foreach ($missionSheetProducts as $missionSheetProduct) {
                $missionSheetProduct->setIsEnabled(true);
            }
            $this->em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }

    /**
     * @Route("/disableMany/{ids}", name="paprec_mission_sheet_product_disableMany")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function disableManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $missionSheetProducts = $this->em->getRepository('App:MissionSheetProduct')->findById($ids);
            foreach ($missionSheetProducts as $missionSheetProduct) {
                $missionSheetProduct->setIsEnabled(false);
            }
            $this->em->flush();
        }
        return new JsonResponse([
            'resultCode' => 1
        ]);
    }

    /**
     * @Route("/{id}/addMissionSheetProductLabel",  name="paprec_mission_sheet_product_addMissionSheetProductLabel")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addMissionSheetProductLabelAction(Request $request, MissionSheetProduct $missionSheetProduct)
    {
        $user = $this->getUser();

        $this->missionSheetProductManager->isDeleted($missionSheetProduct, true);

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }
        $missionSheetProductLabel = new MissionSheetProductLabel();

        $form = $this->createForm(MissionSheetProductLabelType::class, $missionSheetProductLabel, array(
            'languages' => $languages,
            'language' => strtoupper($request->getLocale())
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $missionSheetProductLabel = $form->getData();
            $missionSheetProductLabel->setDateCreation(new \DateTime);
            $missionSheetProductLabel->setUserCreation($user);
            $missionSheetProductLabel->setMissionSheetProduct($missionSheetProduct);

            $this->em->persist($missionSheetProductLabel);
            $this->em->flush();

            return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
                'id' => $missionSheetProduct->getId()
            ));

        }

        return $this->render('missionSheetProduct/missionSheetProductLabel/add.html.twig', array(
            'form' => $form->createView(),
            'missionSheetProduct' => $missionSheetProduct,
        ));
    }

    /**
     * @Route("/{id}/editMissionSheetProductLabel/{missionSheetProductLabelId}",  name="paprec_mission_sheet_product_editMissionSheetProductLabel")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param MissionSheetProduct $missionSheetProduct
     * @param $missionSheetProductLabelId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function editMissionSheetProductLabelAction(Request $request, MissionSheetProduct $missionSheetProduct, $missionSheetProductLabelId)
    {
        $user = $this->getUser();


        $this->missionSheetProductManager->isDeleted($missionSheetProduct, true);

        $missionSheetProductLabel = $this->missionSheetProductLabelManager->get($missionSheetProductLabelId);

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(MissionSheetProductLabelType::class, $missionSheetProductLabel, array(
            'languages' => $languages,
            'language' => $missionSheetProductLabel->getLanguage()
        ));

        $form->handleRequest($request);

//        if ($form->isSubmitted() && $form->isValid()) {
        if ($form->isSubmitted()) {

            $missionSheetProductLabel = $form->getData();
            $missionSheetProductLabel->setDateUpdate(new \DateTime);
            $missionSheetProductLabel->setUserUpdate($user);

//            $this->em->merge($missionSheetProductLabel);
            $this->em->flush();

            return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
                'id' => $missionSheetProduct->getId()
            ));

        }

        return $this->render('missionSheetProduct/missionSheetProductLabel/edit.html.twig', array(
            'form' => $form->createView(),
            'missionSheetProduct' => $missionSheetProduct
        ));
    }

    /**
     * @Route("/{id}/removeMissionSheetProductLabel/{missionSheetProductLabelId}",  name="paprec_mission_sheet_product_removeMissionSheetProductLabel")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param MissionSheetProduct $missionSheetProduct
     * @param $missionSheetProductLabelId
     */
    public function removeMissionSheetProductLabelAction(Request $request, MissionSheetProduct $missionSheetProduct, $missionSheetProductLabelId)
    {


        $this->missionSheetProductManager->isDeleted($missionSheetProduct, true);

        $missionSheetProductLabel = $this->missionSheetProductLabelManager->get($missionSheetProductLabelId);
        $this->em->remove($missionSheetProductLabel);

        $this->em->flush();

        return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
            'id' => $missionSheetProduct->getId()
        ));
    }

    /**
     * Supprimme un fichier du sytème de fichiers
     *
     * @param $path
     */
    public function removeFile($path)
    {
        $fs = new Filesystem();
        try {
            $fs->remove($path);
        } catch (IOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @Route("/addPicture/{id}/{type}", name="paprec_mission_sheet_product_addPicture")
     * @Method("POST")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addPictureAction(Request $request, MissionSheetProduct $missionSheetProduct)
    {
        $picture = new Picture();
        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureMissionSheetProductType::class, $picture, array(
            'types' => $types
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $missionSheetProduct->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.missionSheetProduct.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $picture->setType($request->get('type'));
                $picture->setMissionSheetProduct($missionSheetProduct);
                $missionSheetProduct->addPicture($picture);
                $this->em->persist($picture);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
                'id' => $missionSheetProduct->getId()
            ));
        }
        return $this->render('missionSheetProduct/view.html.twig', array(
            'missionSheetProduct' => $missionSheetProduct,
            'formAddPicture' => $form->createView()
        ));
    }

    /**
     * @Route("/editPicture/{id}/{pictureID}", name="paprec_mission_sheet_product_editPicture")
     * @Method("POST")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editPictureAction(Request $request, MissionSheetProduct $missionSheetProduct)
    {

        $pictureID = $request->get('pictureID');
        $picture = $this->pictureManager->get($pictureID);
        $oldPath = $picture->getPath();

        $em = $this->getDoctrine()->getManager();

        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureMissionSheetProductType::class, $picture, array(
            'types' => $types
        ));


        $form->handleRequest($request);
        if ($form->isValid()) {
            $missionSheetProduct->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.missionSheetProduct.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $this->removeFile($this->getParameter('paprec.missionSheetProduct.picto_path') . '/' . $oldPath);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
                'id' => $missionSheetProduct->getId()
            ));
        }
        return $this->render('missionSheetProduct/view.html.twig', array(
            'missionSheetProduct' => $missionSheetProduct,
            'formEditPicture' => $form->createView()
        ));
    }


    /**
     * @Route("/removePicture/{id}/{pictureID}", name="paprec_mission_sheet_product_removePicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removePictureAction(Request $request, MissionSheetProduct $missionSheetProduct)
    {

        $pictureID = $request->get('pictureID');

        $pictures = $missionSheetProduct->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $missionSheetProduct->setDateUpdate(new \DateTime());
                $this->removeFile($this->getParameter('paprec.missionSheetProduct.picto_path') . '/' . $picture->getPath());
                $this->em->remove($picture);
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
            'id' => $missionSheetProduct->getId()
        ));
    }

    /**
     * @Route("/setPilotPicture/{id}/{pictureID}", name="paprec_mission_sheet_product_setPilotPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function setPilotPictureAction(Request $request, MissionSheetProduct $missionSheetProduct)
    {

        $pictureID = $request->get('pictureID');
        $pictures = $missionSheetProduct->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $missionSheetProduct->setDateUpdate(new \DateTime());
                $picture->setType('PILOTPICTURE');
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
            'id' => $missionSheetProduct->getId()
        ));
    }

    /**
     * @Route("/setPicture/{id}/{pictureID}", name="paprec_mission_sheet_product_setPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function setPictureAction(Request $request, MissionSheetProduct $missionSheetProduct)
    {

        $pictureID = $request->get('pictureID');
        $pictures = $missionSheetProduct->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $missionSheetProduct->setDateUpdate(new \DateTime());
                $picture->setType('PICTURE');
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_mission_sheet_product_view', array(
            'id' => $missionSheetProduct->getId()
        ));
    }

}
