<?php

namespace App\Controller;

use App\Entity\FollowUp;
use App\Entity\Picture;
use App\Entity\Product;
use App\Entity\ProductLabel;
use App\Form\PictureProductType;
use App\Form\ProductLabelType;
use App\Form\ProductMaterialType;
use App\Form\ProductType;
use App\Service\NumberManager;
use App\Service\PictureManager;
use App\Service\ProductLabelManager;
use App\Service\ProductManager;
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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductController extends AbstractController
{

    private $em;
    private $numberManager;
    private $productManager;
    private $productLabelManager;
    private $translator;
    private $pictureManager;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        NumberManager $numberManager,
        ProductManager $productManager,
        ProductLabelManager $productLabelManager,
        PictureManager $pictureManager
    ) {
        $this->em = $em;
        $this->numberManager = $numberManager;
        $this->productManager = $productManager;
        $this->productLabelManager = $productLabelManager;
        $this->translator = $translator;
        $this->pictureManager = $pictureManager;
    }

    /**
     * @Route("", name="paprec_product_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('product/index.html.twig');
    }

    /**
     * @Route("/loadList", name="paprec_product_loadList")
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

        $cols['id'] = array('label' => 'id', 'id' => 'p.id', 'method' => array('getId'));
        $cols['name'] = array(
            'label' => 'name',
            'id' => 'pL.name',
            'method' => array(array('getProductLabels', 0), 'getName')
        );
        $cols['range'] = array(
            'label' => 'range',
            'id' => 'p.range',
            'method' => array('getRange', array(array('getRangeLabels', 0), 'getName'))
        );
        $cols['isEnabled'] = array('label' => 'isEnabled', 'id' => 'p.isEnabled', 'method' => array('getIsEnabled'));


        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(Product::class)->createQueryBuilder('p');

        $queryBuilder->select(array('p', 'pL', 'r', 'rL'))
            ->leftJoin('p.productLabels', 'pL')
            ->leftJoin('p.range', 'r')
            ->leftJoin('r.rangeLabels', 'rL')
            ->where('p.deleted IS NULL')
            ->andWhere('pL.language = :language')
            ->setParameter('language', 'FR');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('p.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('pL.name', '?1'),
                    $queryBuilder->expr()->like('p.dimensions', '?1'),
                    $queryBuilder->expr()->like('p.isEnabled', '?1')
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
     * @Route("/export",  name="paprec_product_export")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function exportAction(Request $request)
    {
        $language = $request->getLocale();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder->select(array('p'))
            ->from('App:Product', 'p')
            ->where('p.deleted IS NULL');

        $products = $queryBuilder->getQuery()->getResult();

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()->setCreator("Privacia Shop")
            ->setLastModifiedBy("Privacia Shop")
            ->setTitle("Privacia Shop - Products")
            ->setSubject("Extract");

        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Products');

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

        /** @var Product $product */
        foreach ($products as $product) {

            /** @var ProductLabel $productLabel */
            $productLabel = $this->productManager->getProductLabelByProductAndLocale($product, strtoupper($language));

            // Getters
            $getters = [
                $product->getId(),
                $product->getDateCreation()->format('Y-m-d'),
                ($product->getDateUpdate() == null) ? null : $product->getDateUpdate()->format('Y-m-d'),
                $product->getDeleted() ? 'true' : 'false',
                $product->getCapacity(),
                $product->getCapacityUnit(),
                $product->getDimensions(),
                $product->getIsEnabled(),
                $this->numberManager->denormalize($product->getRentalUnitPrice()),
                $this->numberManager->denormalize($product->getTransportUnitPrice()),
                $this->numberManager->denormalize($product->getTreatmentUnitPrice()),
                $this->numberManager->denormalize($product->getTraceabilityUnitPrice()),
                $product->getPosition(),
                $product->getUserCreation(),
                $product->getUserUpdate(),
                $productLabel->getId(),
                $productLabel->getName(),
                $productLabel->getShortDescription(),
                $productLabel->getLanguage(),
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

        $fileName = 'PrivaciaShop-Extraction-Products-' . date('Y-m-d') . '.xlsx';

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
     * @Route("/view/{id}",  name="paprec_product_view")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function viewAction(Request $request, Product $product)
    {

        $this->productManager->isDeleted($product, true);

        $language = $request->getLocale();
        $productLabel = $this->productManager->getProductLabelByProductAndLocale($product, strtoupper($language));

        $otherProductLabels = $this->productManager->getProductLabels($product);

        $tmp = array();
        foreach ($otherProductLabels as $pL) {
            if ($pL->getId() != $productLabel->getId()) {
                $tmp[] = $pL;
            }
        }
        $otherProductLabels = $tmp;


        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $picture = new Picture();

        $formAddPicture = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));

        $formEditPicture = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));


        return $this->render('product/view.html.twig', array(
            'product' => $product,
            'productLabel' => $productLabel,
            'formAddPicture' => $formAddPicture->createView(),
            'formEditPicture' => $formEditPicture->createView(),
            'otherProductLabels' => $otherProductLabels
        ));
    }

    /**
     * @Route("/add",  name="paprec_product_add")
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

        $product = new Product();
        $productLabel = new ProductLabel();

        $form1 = $this->createForm(ProductType::class, $product, array(
            'transportTypes' => $transportTypes
        ));
        $form2 = $this->createForm(ProductLabelType::class, $productLabel, array(
            'languages' => $languages,
            'language' => 'FR'
        ));

        $form1->handleRequest($request);
        $form2->handleRequest($request);

        if ($form1->isSubmitted() && $form1->isValid() && $form2->isSubmitted() && $form2->isValid()) {

            $product = $form1->getData();

            $product->setRentalUnitPrice($this->numberManager->normalize($product->getRentalUnitPrice()));
            $product->setTransportUnitPrice($this->numberManager->normalize($product->getTransportUnitPrice()));
            $product->setTreatmentUnitPrice($this->numberManager->normalize($product->getTreatmentUnitPrice()));
            $product->setTraceabilityUnitPrice($this->numberManager->normalize($product->getTraceabilityUnitPrice()));

            $product->setDateCreation(new \DateTime);
            $product->setUserCreation($user);

            $this->em->persist($product);
            $this->em->flush();

            $productLabel = $form2->getData();
            $productLabel->setDateCreation(new \DateTime);
            $productLabel->setUserCreation($user);
            $productLabel->setProduct($product);

            $this->em->persist($productLabel);
            $this->em->flush();

            return $this->redirectToRoute('paprec_product_view', array(
                'id' => $product->getId()
            ));

        }

        return $this->render('product/add.html.twig', array(
            'form1' => $form1->createView(),
            'form2' => $form2->createView()
        ));
    }

    /**
     * @Route("/addMaterial",  name="paprec_product_material_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addMaterialAction(Request $request)
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
        $transportTypes['MATERIAL'] = 'MATERIAL';

        $product = new Product();
        $productLabel = new ProductLabel();

        $form1 = $this->createForm(ProductMaterialType::class, $product, array(
            'transportTypes' => $transportTypes
        ));
        $form2 = $this->createForm(ProductLabelType::class, $productLabel, array(
            'languages' => $languages,
            'language' => 'FR'
        ));

        $form1->handleRequest($request);
        $form2->handleRequest($request);

        if ($form1->isSubmitted() && $form1->isValid() && $form2->isSubmitted() && $form2->isValid()) {

            $product = $form1->getData();

            $product->setRentalUnitPrice($this->numberManager->normalize($product->getRentalUnitPrice()));
            $product->setTransportUnitPrice($this->numberManager->normalize($product->getTransportUnitPrice()));
            $product->setTreatmentUnitPrice($this->numberManager->normalize($product->getTreatmentUnitPrice()));
            $product->setTraceabilityUnitPrice($this->numberManager->normalize($product->getTraceabilityUnitPrice()));

            $product->setDateCreation(new \DateTime);
            $product->setUserCreation($user);

            $this->em->persist($product);
            $this->em->flush();

            $productLabel = $form2->getData();
            $productLabel->setDateCreation(new \DateTime);
            $productLabel->setUserCreation($user);
            $productLabel->setProduct($product);

            $this->em->persist($productLabel);
            $this->em->flush();

            return $this->redirectToRoute('paprec_product_view', array(
                'id' => $product->getId()
            ));

        }

        return $this->render('product/addMaterial.html.twig', array(
            'form1' => $form1->createView(),
            'form2' => $form2->createView()
        ));
    }

    /**
     * @Route("/edit/{id}",  name="paprec_product_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Exception
     */
    public function editAction(Request $request, Product $product)
    {
        $this->productManager->isDeleted($product, true);

        $user = $this->getUser();

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }
        $transportTypes = array();
        foreach ($this->getParameter('paprec_transport_types') as $transportType) {
            $transportTypes[$transportType] = $transportType;
        }

        $language = $request->getLocale();
        $productLabel = $this->productManager->getProductLabelByProductAndLocale($product, strtoupper($language));

        $product->setRentalUnitPrice($this->numberManager->denormalize($product->getRentalUnitPrice()));
        $product->setTransportUnitPrice($this->numberManager->denormalize($product->getTransportUnitPrice()));
        $product->setTreatmentUnitPrice($this->numberManager->denormalize($product->getTreatmentUnitPrice()));
        $product->setTraceabilityUnitPrice($this->numberManager->denormalize($product->getTraceabilityUnitPrice()));


        $form1 = $this->createForm(ProductType::class, $product, array(
            'transportTypes' => $transportTypes,
        ));
        $form2 = $this->createForm(ProductLabelType::class, $productLabel, array(
            'languages' => $languages,
            'language' => $productLabel->getLanguage()
        ));

        $form1->handleRequest($request);
        $form2->handleRequest($request);

//        if ($form1->isSubmitted() && $form1->isValid() && $form2->isSubmitted() && $form2->isValid()) {
        if ($form1->isSubmitted() && $form1->isValid() && $form2->isSubmitted()) {

            $product = $form1->getData();

            $product->setRentalUnitPrice($this->numberManager->normalize($product->getRentalUnitPrice()));
            $product->setTransportUnitPrice($this->numberManager->normalize($product->getTransportUnitPrice()));
            $product->setTreatmentUnitPrice($this->numberManager->normalize($product->getTreatmentUnitPrice()));
            $product->setTraceabilityUnitPrice($this->numberManager->normalize($product->getTraceabilityUnitPrice()));


            $product->setDateUpdate(new \DateTime);
            $product->setUserUpdate($user);
            $this->em->flush();

            $productLabel = $form2->getData();
            $productLabel->setDateUpdate(new \DateTime);
            $productLabel->setUserUpdate($user);
            $productLabel->setProduct($product);

            $this->em->flush();

            return $this->redirectToRoute('paprec_product_view', array(
                'id' => $product->getId()
            ));
        }
        return $this->render('product/edit.html.twig', array(
            'form1' => $form1->createView(),
            'form2' => $form2->createView(),
            'product' => $product,
            'productLabel' => $productLabel
        ));
    }

    /**
     * @Route("/remove/{id}", name="paprec_product_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, Product $product)
    {
        /*
         * Suppression des images
         */
        foreach ($product->getPictures() as $picture) {
            $this->removeFile($this->getParameter('paprec.product.picto_path') . '/' . $picture->getPath());
            $product->removePicture($picture);
        }

        $product->setDeleted(new \DateTime);
        $product->setIsEnabled(false);
        $this->em->flush();

        return $this->redirectToRoute('paprec_product_index');
    }

    /**
     * @Route("/removeMany/{ids}", name="paprec_product_removeMany")
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
            $products = $this->em->getRepository('App:Product')->findById($ids);
            foreach ($products as $product) {
                foreach ($product->getPictures() as $picture) {
                    $this->removeFile($this->getParameter('paprec.product.picto_path') . '/' . $picture->getPath());
                    $product->removePicture($picture);
                }

                $product->setDeleted(new \DateTime());
                $product->setIsEnabled(false);
            }
            $this->em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }

    /**
     * @Route("/enableMany/{ids}", name="paprec_product_enableMany")
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
            $products = $this->em->getRepository('App:Product')->findById($ids);
            foreach ($products as $product) {
                $product->setIsEnabled(true);
            }
            $this->em->flush();
        }
        return $this->redirectToRoute('paprec_product_index');
    }

    /**
     * @Route("/disableMany/{ids}", name="paprec_product_disableMany")
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
            $products = $this->em->getRepository('App:Product')->findById($ids);
            foreach ($products as $product) {
                $product->setIsEnabled(false);
            }
            $this->em->flush();
        }
        return $this->redirectToRoute('paprec_product_index');
    }

    /**
     * @Route("/{id}/addProductLabel",  name="paprec_product_addProductLabel")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addProductLabelAction(Request $request, Product $product)
    {
        $user = $this->getUser();

        $this->productManager->isDeleted($product, true);

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }
        $productLabel = new ProductLabel();

        $form = $this->createForm(ProductLabelType::class, $productLabel, array(
            'languages' => $languages,
            'language' => strtoupper($request->getLocale())
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $productLabel = $form->getData();
            $productLabel->setDateCreation(new \DateTime);
            $productLabel->setUserCreation($user);
            $productLabel->setProduct($product);

            $this->em->persist($productLabel);
            $this->em->flush();

            return $this->redirectToRoute('paprec_product_view', array(
                'id' => $product->getId()
            ));

        }

        return $this->render('product/productLabel/add.html.twig', array(
            'form' => $form->createView(),
            'product' => $product,
        ));
    }

    /**
     * @Route("/{id}/editProductLabel/{productLabelId}",  name="paprec_product_editProductLabel")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param Product $product
     * @param $productLabelId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function editProductLabelAction(Request $request, Product $product, $productLabelId)
    {
        $user = $this->getUser();


        $this->productManager->isDeleted($product, true);

        $productLabel = $this->productLabelManager->get($productLabelId);

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(ProductLabelType::class, $productLabel, array(
            'languages' => $languages,
            'language' => $productLabel->getLanguage()
        ));

        $form->handleRequest($request);

//        if ($form->isSubmitted() && $form->isValid()) {
        if ($form->isSubmitted()) {

            $productLabel = $form->getData();
            $productLabel->setDateUpdate(new \DateTime);
            $productLabel->setUserUpdate($user);

//            $this->em->merge($productLabel);
            $this->em->flush();

            return $this->redirectToRoute('paprec_product_view', array(
                'id' => $product->getId()
            ));

        }

        return $this->render('product/productLabel/edit.html.twig', array(
            'form' => $form->createView(),
            'product' => $product
        ));
    }

    /**
     * @Route("/{id}/removeProductLabel/{productLabelId}",  name="paprec_product_removeProductLabel")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param Product $product
     * @param $productLabelId
     */
    public function removeProductLabelAction(Request $request, Product $product, $productLabelId)
    {


        $this->productManager->isDeleted($product, true);

        $productLabel = $this->productLabelManager->get($productLabelId);
        $this->em->remove($productLabel);

        $this->em->flush();

        return $this->redirectToRoute('paprec_product_view', array(
            'id' => $product->getId()
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
     * @Route("/addPicture/{id}/{type}", name="paprec_product_addPicture")
     * @Method("POST")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addPictureAction(Request $request, Product $product)
    {
        $picture = new Picture();
        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $product->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.product.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $picture->setType($request->get('type'));
                $picture->setProduct($product);
                $product->addPicture($picture);
                $this->em->persist($picture);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_product_view', array(
                'id' => $product->getId()
            ));
        }
        return $this->render('product/view.html.twig', array(
            'product' => $product,
            'formAddPicture' => $form->createView()
        ));
    }

    /**
     * @Route("/editPicture/{id}/{pictureID}", name="paprec_product_editPicture")
     * @Method("POST")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editPictureAction(Request $request, Product $product)
    {

        $pictureID = $request->get('pictureID');
        $picture = $this->pictureManager->get($pictureID);
        $oldPath = $picture->getPath();

        $em = $this->getDoctrine()->getManager();

        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));


        $form->handleRequest($request);
        if ($form->isValid()) {
            $product->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.product.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $this->removeFile($this->getParameter('paprec.product.picto_path') . '/' . $oldPath);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_product_view', array(
                'id' => $product->getId()
            ));
        }
        return $this->render('product/view.html.twig', array(
            'product' => $product,
            'formEditPicture' => $form->createView()
        ));
    }


    /**
     * @Route("/removePicture/{id}/{pictureID}", name="paprec_product_removePicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removePictureAction(Request $request, Product $product)
    {

        $pictureID = $request->get('pictureID');

        $pictures = $product->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $product->setDateUpdate(new \DateTime());
                $this->removeFile($this->getParameter('paprec.product.picto_path') . '/' . $picture->getPath());
                $this->em->remove($picture);
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_product_view', array(
            'id' => $product->getId()
        ));
    }

    /**
     * @Route("/setPilotPicture/{id}/{pictureID}", name="paprec_product_setPilotPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function setPilotPictureAction(Request $request, Product $product)
    {

        $pictureID = $request->get('pictureID');
        $pictures = $product->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $product->setDateUpdate(new \DateTime());
                $picture->setType('PILOTPICTURE');
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_product_view', array(
            'id' => $product->getId()
        ));
    }

    /**
     * @Route("/setPicture/{id}/{pictureID}", name="paprec_product_setPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function setPictureAction(Request $request, Product $product)
    {

        $pictureID = $request->get('pictureID');
        $pictures = $product->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $product->setDateUpdate(new \DateTime());
                $picture->setType('PICTURE');
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_product_view', array(
            'id' => $product->getId()
        ));
    }

}
