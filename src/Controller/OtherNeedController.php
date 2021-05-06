<?php

namespace App\Controller;

use App\Entity\OtherNeed;
use App\Entity\Picture;
use App\Form\OtherNeedType;
use App\Form\PictureProductType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class OtherNeedController extends Controller
{

    /**
     * @Route("/otherNeed", name="paprec_other_need_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('otherNeed/index.html.twig');
    }

    /**
     * @Route("/otherNeed/loadList", name="paprec_other_need_loadList")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function loadListAction(Request $request)
    {

        $return = array();

        $filters = $request->get('filters');
        $pageSize = $request->get('length');
        $start = $request->get('start');
        $orders = $request->get('order');
        $search = $request->get('search');
        $columns = $request->get('columns');

        $cols['id'] = array('label' => 'id', 'id' => 'o.id', 'method' => array('getId'));
        $cols['name'] = array('label' => 'name', 'id' => 'o.name', 'method' => array('getName'));
        $cols['isDisplayed'] = array('label' => 'isDisplayed', 'id' => 'o.isDisplayed', 'method' => array('getIsDisplayed'));
        $cols['language'] = array('label' => 'language', 'id' => 'o.language', 'method' => array('getLanguage'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(OtherNeed::class)->createQueryBuilder('o');


        $queryBuilder->select(array('o'))
            ->where('o.deleted IS NULL');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) == '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('o.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('o.name', '?1'),
                    $queryBuilder->expr()->like('o.isDisplayed', '?1'),
                    $queryBuilder->expr()->like('o.language', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $datatable = $this->get('goondi_tools.datatable')->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters);

        // Reformatage de certaines données
        $tmp = array();
        foreach ($datatable['data'] as $data) {
            $line = $data;
            $line['isDisplayed'] = $data['isDisplayed'] ? $this->get('translator')->trans('General.1') : $this->get('translator')->trans('General.0');
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
     * @Route("/otherNeed/view/{id}", name="paprec_other_need_view")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param OtherNeed $otherNeed
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function viewAction(Request $request, OtherNeed $otherNeed)
    {
        $otherNeedManager = $this->get('paprec_catalog.other_need_manager');
        $otherNeedManager->isDeleted($otherNeed, true);

        foreach ($this->getParameter('paprec_other_need_types_picture') as $type) {
            $types[$type] = $type;
        }

        $picture = new Picture();

        $formAddPicture = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));

        $formEditPicture = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));

        return $this->render('otherNeed/view.html.twig', array(
            'otherNeed' => $otherNeed,
            'formAddPicture' => $formAddPicture->createView(),
            'formEditPicture' => $formEditPicture->createView()
        ));
    }

    /**
     * @Route("/otherNeed/add", name="paprec_other_need_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $otherNeed = new OtherNeed();

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(OtherNeedType::class, $otherNeed, array(
            'languages' => $languages,
            'language' => strtoupper($request->getLocale())
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $otherNeed = $form->getData();

            $otherNeed->setDateCreation(new \DateTime);
            $otherNeed->setUserCreation($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($otherNeed);
            $em->flush();

            return $this->redirectToRoute('paprec_other_need_view', array(
                'id' => $otherNeed->getId()
            ));

        }

        return $this->render('otherNeed/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/otherNeed/edit/{id}", name="paprec_other_need_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param OtherNeed $otherNeed
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function editAction(Request $request, OtherNeed $otherNeed)
    {
        $user = $this->getUser();

        $otherNeedManager = $this->get('paprec_catalog.other_need_manager');
        $otherNeedManager->isDeleted($otherNeed, true);

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(OtherNeedType::class, $otherNeed, array(
            'languages' => $languages,
            'language' => strtoupper($otherNeed->getLanguage())
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $otherNeed = $form->getData();

            $otherNeed->setDateUpdate(new \DateTime);
            $otherNeed->setUserUpdate($user);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('paprec_other_need_view', array(
                'id' => $otherNeed->getId()
            ));

        }

        return $this->render('otherNeed/edit.html.twig', array(
            'form' => $form->createView(),
            'otherNeed' => $otherNeed
        ));
    }

    /**
     * @Route("/otherNeed/remove/{id}", name="paprec_other_need_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, OtherNeed $otherNeed)
    {
        $em = $this->getDoctrine()->getManager();

        $otherNeed->setDeleted(new \DateTime());
        /*
        * Suppression des images
         */
        foreach ($otherNeed->getPictures() as $picture) {
            $this->removeFile($this->getParameter('paprec_catalog.product.di.picto_path') . '/' . $picture->getPath());
            $otherNeed->removePicture($picture);
        }
        $em->flush();

        return $this->redirectToRoute('paprec_other_need_index');
    }

    /**
     * @Route("/otherNeed/removeMany/{ids}", name="paprec_other_need_removeMany")
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
            $otherNeeds = $em->getRepository('App:OtherNeed')->findById($ids);
            foreach ($otherNeeds as $otherNeed) {
                foreach ($otherNeed->getPictures() as $picture) {
                    $this->removeFile($this->getParameter('paprec_catalog.product.picto_path') . '/' . $picture->getPath());
                    $otherNeed->removePicture($picture);
                }

                $otherNeed->setDeleted(new \DateTime());
                $otherNeed->setIsDisplayed(false);
            }
            $em->flush();
        }

        return $this->redirectToRoute('paprec_other_need_index');
    }

    /**
     * @Route("/otherNeed/addPicture/{id}/{type}", name="paprec_other_need_addPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addPictureAction(Request $request, OtherNeed $otherNeed)
    {
        $picture = new Picture();

        foreach ($this->getParameter('paprec_other_need_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));

        $em = $this->getDoctrine()->getManager();

        $form->handleRequest($request);
        if ($form->isValid()) {
            $otherNeed->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec_catalog.product.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $picture->setType($request->get('type'));
                $picture->setOtherNeed($otherNeed);
                $otherNeed->addPicture($picture);
                $em->persist($picture);
                $em->flush();
            }

            return $this->redirectToRoute('paprec_other_need_view', array(
                'id' => $otherNeed->getId()
            ));
        }
        return $this->render('otherNeed/view.html.twig', array(
            'otherNeed' => $otherNeed,
            'formAddPicture' => $form->createView()
        ));
    }


    /**
     * @Route("/otherNeed/editPicture/{id}/{pictureID}", name="paprec_other_need_editPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editPictureAction(Request $request, OtherNeed $otherNeed)
    {
        $otherNeedManager = $this->get('paprec_catalog.other_need_manager');
        $pictureManager = $this->get('paprec_catalog.picture_manager');

        $em = $this->getDoctrine()->getManager();
        $pictureID = $request->get('pictureID');
        $picture = $pictureManager->get($pictureID);
        $oldPath = $picture->getPath();

        $em = $this->getDoctrine()->getEntityManager();

        foreach ($this->getParameter('paprec_other_need_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));


        $form->handleRequest($request);
        if ($form->isValid()) {
            $otherNeed->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid()) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec_catalog.product.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $this->removeFile($this->getParameter('paprec_catalog.product.picto_path') . '/' . $oldPath);
                $em->flush();
            }

            return $this->redirectToRoute('paprec_other_need_view', array(
                'id' => $otherNeed->getId()
            ));
        }
        return $this->render('otherNeed/view.html.twig', array(
            'otherNeed' => $otherNeed,
            'formEditPicture' => $form->createView()
        ));
    }

    /**
     * @Route("/otherNeed/removePicture/{id}/{pictureID}", name="paprec_other_need_removePicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removePictureAction(Request $request, OtherNeed $otherNeed)
    {

        $em = $this->getDoctrine()->getManager();

        $pictureID = $request->get('pictureID');

        $pictures = $otherNeed->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $otherNeed->setDateUpdate(new \DateTime());
                $this->removeFile($this->getParameter('paprec_catalog.product.picto_path') . '/' . $picture->getPath());
                $em->remove($picture);
                continue;
            }
        }
        $em->flush();

        return $this->redirectToRoute('paprec_other_need_view', array(
            'id' => $otherNeed->getId()
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
