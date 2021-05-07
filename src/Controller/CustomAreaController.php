<?php

namespace App\Controller;

use App\Entity\CustomArea;
use App\Entity\Picture;
use App\Form\CustomAreaType;
use App\Form\PictureCustomAreaType;
use App\Form\PictureProductType;
use App\Service\CustomAreaManager;
use App\Service\PictureManager;
use App\Tools\DataTable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomAreaController extends AbstractController
{

    private $em;
    private $customAreaManager;
    private $pictureManager;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        PictureManager $pictureManager,
        CustomAreaManager $customAreaManager
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->customAreaManager = $customAreaManager;
        $this->pictureManager = $pictureManager;
    }

    /**
     * @Route("", name="paprec_custom_area_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('customArea/index.html.twig');
    }

    /**
     * @Route("/loadList", name="paprec_custom_area_loadList")
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

        $cols['id'] = array('label' => 'id', 'id' => 'r.id', 'method' => array('getId'));
        $cols['code'] = array('label' => 'code', 'id' => 'r.code', 'method' => array('getCode'));
        $cols['isDisplayed'] = array(
            'label' => 'isDisplayed',
            'id' => 'r.isDisplayed',
            'method' => array('getIsDisplayed')
        );
        $cols['language'] = array('label' => 'language', 'id' => 'r.language', 'method' => array('getLanguage'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(CustomArea::class)->createQueryBuilder('r');


        $queryBuilder->select(array('r'))
            ->where('r.deleted IS NULL');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('r.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('r.code', '?1'),
                    $queryBuilder->expr()->like('r.isDisplayed', '?1'),
                    $queryBuilder->expr()->like('r.language', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);
        // Reformatage de certaines données
        $tmp = array();
        foreach ($dt['data'] as $data) {
            $line = $data;
            $line['isDisplayed'] = $data['isDisplayed'] ? $this->translator->trans('General.1') : $this->translator->trans('General.0');
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
     * @Route("/view/{id}", name="paprec_custom_area_view")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param CustomArea $customArea
     * @return Response
     * @throws EntityNotFoundException
     */
    public function viewAction(Request $request, CustomArea $customArea): Response
    {
        $this->customAreaManager->isDeleted($customArea, true);

        foreach ($this->getParameter('paprec_custom_area_types_picture') as $type) {
            $types[$type] = $type;
        }

        $picture = new Picture();

        $formAddPicture = $this->createForm(PictureCustomAreaType::class, $picture, array(
            'types' => $types
        ));

        $formEditPicture = $this->createForm(PictureCustomAreaType::class, $picture, array(
            'types' => $types
        ));

        return $this->render('customArea/view.html.twig', array(
            'customArea' => $customArea,
            'formAddPicture' => $formAddPicture->createView(),
            'formEditPicture' => $formEditPicture->createView()
        ));
    }

    /**
     * @Route("/add", name="paprec_custom_area_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $customArea = new CustomArea();

        $codes = array();
        foreach ($this->getParameter('paprec_custom_area_codes') as $code) {
            $codes[$code] = $code;
        }

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(CustomAreaType::class, $customArea, array(
            'languages' => $languages,
            'language' => strtoupper($request->getLocale()),
            'codes' => $codes
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $customArea = $form->getData();

            $customArea->setDateCreation(new DateTime);
            $customArea->setUserCreation($user);

            $this->em->persist($customArea);
            $this->em->flush();

            return $this->redirectToRoute('paprec_custom_area_view', array(
                'id' => $customArea->getId()
            ));

        }

        return $this->render('customArea/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="paprec_custom_area_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param CustomArea $customArea
     * @return RedirectResponse|Response
     * @throws EntityNotFoundException
     */
    public function editAction(Request $request, CustomArea $customArea)
    {
        $user = $this->getUser();

        $this->customAreaManager->isDeleted($customArea, true);

        $codes = array();
        foreach ($this->getParameter('paprec_custom_area_codes') as $code) {
            $codes[$code] = $code;
        }

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(CustomAreaType::class, $customArea, array(
            'languages' => $languages,
            'codes' => $codes,
            'language' => strtoupper($customArea->getLanguage())
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $customArea = $form->getData();

            $customArea->setDateUpdate(new DateTime);
            $customArea->setUserUpdate($user);

            $this->em->flush();

            return $this->redirectToRoute('paprec_custom_area_view', array(
                'id' => $customArea->getId()
            ));

        }

        return $this->render('customArea/edit.html.twig', array(
            'form' => $form->createView(),
            'customArea' => $customArea
        ));
    }

    /**
     * @Route("/remove/{id}", name="paprec_custom_area_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, CustomArea $customArea): RedirectResponse
    {
        $customArea->setDeleted(new DateTime());
        /*
        * Suppression des images
         */
        foreach ($customArea->getPictures() as $picture) {
            $this->removeFile($this->getParameter('paprec_catalog.product.di.picto_path') . '/' . $picture->getPath());
            $customArea->removePicture($picture);
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_custom_area_index');
    }

    /**
     * @Route("/removeMany/{ids}", name="paprec_custom_area_removeMany")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeManyAction(Request $request): RedirectResponse
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $customAreas = $this->em->getRepository('App:CustomArea')->findById($ids);
            foreach ($customAreas as $customArea) {
                foreach ($customArea->getPictures() as $picture) {
                    $this->removeFile($this->getParameter('paprec.custom_area.picto_path') . '/' . $picture->getPath());
                    $customArea->removePicture($picture);
                }

                $customArea->setDeleted(new \DateTime());
                $customArea->setIsDisplayed(false);
            }
            $this->em->flush();
        }

        return $this->redirectToRoute('paprec_custom_area_index');
    }

    /**
     * @Route("/addPicture/{id}/{type}", name="paprec_custom_area_addPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addPictureAction(Request $request, CustomArea $customArea)
    {
        $picture = new Picture();

        foreach ($this->getParameter('paprec_custom_area_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $customArea->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.custom_area.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $picture->setType($request->get('type'));
                $picture->setCustomArea($customArea);
                $customArea->addPicture($picture);
                $this->em->persist($picture);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_custom_area_view', array(
                'id' => $customArea->getId()
            ));
        }
        return $this->render('customArea/view.html.twig', array(
            'customArea' => $customArea,
            'formAddPicture' => $form->createView()
        ));
    }


    /**
     * @Route("/editPicture/{id}/{pictureID}", name="paprec_custom_area_editPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editPictureAction(Request $request, CustomArea $customArea)
    {
        $pictureID = $request->get('pictureID');
        $picture = $this->pictureManager->get($pictureID);
        $oldPath = $picture->getPath();


        foreach ($this->getParameter('paprec_custom_area_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));


        $form->handleRequest($request);
        if ($form->isValid()) {
            $customArea->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.custom_area.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $this->removeFile($this->getParameter('paprec.custom_area.picto_path') . '/' . $oldPath);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_custom_area_view', array(
                'id' => $customArea->getId()
            ));
        }
        return $this->render('customArea/view.html.twig', array(
            'customArea' => $customArea,
            'formEditPicture' => $form->createView()
        ));
    }

    /**
     * @Route("/removePicture/{id}/{pictureID}", name="paprec_custom_area_removePicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removePictureAction(Request $request, CustomArea $customArea)
    {

        $pictureID = $request->get('pictureID');

        $pictures = $customArea->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $customArea->setDateUpdate(new \DateTime());
                $this->removeFile($this->getParameter('paprec.custom_area.picto_path') . '/' . $picture->getPath());
                $this->em->remove($picture);
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_custom_area_view', array(
            'id' => $customArea->getId()
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


}
