<?php

namespace App\Controller;

use App\Entity\Setting;
use App\Form\SettingType;
use App\Service\SettingManager;
use App\Tools\DataTable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SettingController extends AbstractController
{

    private $em;
    private $translator;
    private $settingManager;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        SettingManager $settingManager
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->settingManager = $settingManager;
    }

    /**
     * @Route("", name="paprec_setting_index")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function indexAction()
    {
        return $this->render('setting/index.html.twig');
    }

    /**
     * @Route("/loadList", name="paprec_setting_loadList")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
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

        $cols['id'] = array('label' => 'id', 'id' => 's.id', 'method' => array('getId'));
        $cols['keyName'] = array('label' => 'keyName', 'id' => 's.keyName', 'method' => array('getKeyName'));
        $cols['value'] = array('label' => 'value', 'id' => 's.value', 'method' => array('getValue'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(Setting::class)->createQueryBuilder('s');

        $queryBuilder->select(array('s'))
            ->select(array('s'))
            ->where('s.deleted is NULL')
            ->andWhere('s.deleted is NULL');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('s.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('s.keyName', '?1'),
                    $queryBuilder->expr()->like('s.value', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);

        $tmp = [];
        foreach ($dt['data'] as $data) {
            $line = $data;
            $line['keyName'] = $this->translator->trans('Commercial.Setting.KeyName.' . ucfirst($data['keyName']));
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
     * @Route("/view/{id}", name="paprec_setting_view")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @param Request $request
     * @param Setting $setting
     * @return Response
     * @throws EntityNotFoundException
     */
    public function viewAction(Request $request, Setting $setting): Response
    {
        $this->settingManager->isDeleted($setting, true);

        return $this->render('setting/view.html.twig', array(
            'setting' => $setting
        ));
    }

    /**
     * @Route("/add", name="paprec_setting_add")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $setting = new Setting();

        $form = $this->createForm(SettingType::class, $setting, [
            'keys' => $this->getParameter('paprec.setting.keys')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $formData = $form->all();
            $keyName = null;
            if (is_array($formData) && count($formData)) {
                foreach ($formData as $key => $value) {
                    $parameters[$key] = $value->getData();
                }
            }

            if (is_array($parameters) && count($parameters)) {
                foreach ($parameters as $key => $value) {
                    $$key = $value;
                }
            }

            $errors = [];
            if (isset($keyName)) {
                $count = $this->getDoctrine()->getManager()->getRepository(Setting::class)->count([
                    'keyName' => $keyName
                ]);

                if ($this->getParameter('paprec.setting.max_by_key')[$keyName] !== -1 && $count >= $this->getParameter('paprec.setting.max_by_key')[$keyName]) {
                    $errors['keyName'] = array(
                        'code' => 400,
                        'message' => 'Le nombre de valeur max pour cette clé est atteinte.'
                    );
                }
            }

            if ($errors && count($errors)) {
                $form = $this->createForm(SettingType::class, $setting, [
                    'keys' => $this->getParameter('paprec.setting.keys')
                ]);

                if ($errors['keyName']) {
                    $form->get('keyName')->addError(new FormError('Le nombre de valeur max pour cette clé est atteinte.'));
                }

                return $this->render('setting/add.html.twig', array(
                    'form' => $form->createView(),
                    'errors' => $errors,
                ));
            }

            if ($form->isValid()) {

                $setting = $form->getData();

                $setting->setDateCreation(new DateTime);
                $setting->setUserCreation($user);

                $this->em->persist($setting);
                $this->em->flush();

                return $this->redirectToRoute('paprec_setting_view', array(
                    'id' => $setting->getId()
                ));

            }
        }

        return $this->render('setting/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="paprec_setting_edit")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @param Request $request
     * @param Setting $setting
     * @return RedirectResponse|Response
     * @throws EntityNotFoundException
     */
    public function editAction(Request $request, Setting $setting)
    {
        $user = $this->getUser();

        $this->settingManager->isDeleted($setting, true);

        $form = $this->createForm(SettingType::class, $setting, [
            'keys' => $this->getParameter('paprec.setting.keys')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $formData = $form->all();
            $keyName = null;
            if (is_array($formData) && count($formData)) {
                foreach ($formData as $key => $value) {
                    $parameters[$key] = $value->getData();
                }
            }

            if (is_array($parameters) && count($parameters)) {
                foreach ($parameters as $key => $value) {
                    $$key = $value;
                }
            }

            $errors = [];
            if (isset($keyName)) {
                $count = $this->getDoctrine()->getManager()->getRepository(Setting::class)->count([
                    'keyName' => $keyName
                ]);

                if ($this->getParameter('paprec.setting.max_by_key')[$keyName] !== -1 && $count >= $this->getParameter('paprec.setting.max_by_key')[$keyName]) {
                    $errors['keyName'] = array(
                        'code' => 400,
                        'message' => 'Le nombre de valeur max pour cette clé est atteinte.'
                    );
                }
            }

            if ($errors && count($errors)) {
                $form = $this->createForm(SettingType::class, $setting, [
                    'keys' => $this->getParameter('paprec.setting.keys')
                ]);

                if ($errors['keyName']) {
                    $form->get('keyName')->addError(new FormError('Le nombre de valeur max pour cette clé est atteinte.'));
                }

                return $this->render('setting/edit.html.twig', array(
                    'form' => $form->createView(),
                    'errors' => $errors,
                    'setting' => $setting
                ));
            }

            if ($form->isValid()) {

                $setting = $form->getData();

                $setting->setDateUpdate(new DateTime);
                $setting->setUserUpdate($user);

                $this->em->flush();

                return $this->redirectToRoute('paprec_setting_view', array(
                    'id' => $setting->getId()
                ));

            }
        }

        return $this->render('setting/edit.html.twig', array(
            'form' => $form->createView(),
            'setting' => $setting
        ));
    }

    /**
     * @Route("/remove/{id}", name="paprec_setting_remove")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function removeAction(Request $request, Setting $setting): RedirectResponse
    {
        $setting->setDeleted(new DateTime());

        $this->em->flush();

        return $this->redirectToRoute('paprec_setting_index');
    }

    /**
     * @Route("/removeMany/{ids}", name="paprec_setting_removeMany")
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
            $settings = $this->em->getRepository('App:Setting')->findById($ids);
            foreach ($settings as $setting) {
                $setting->setDeleted(new \DateTime());
            }
            $this->em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }
}
