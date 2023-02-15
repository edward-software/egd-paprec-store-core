<?php

namespace App\Controller;

use App\Entity\BillingUnit;
use App\Form\BillingUnitType;
use App\Service\BillingUnitManager;
use App\Tools\DataTable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class BillingUnitController extends AbstractController
{

    private $em;
    private $billingUnitManager;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        BillingUnitManager $billingUnitManager
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->billingUnitManager = $billingUnitManager;
    }

    /**
     * @Route("", name="paprec_billing_unit_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('billingUnit/index.html.twig');
    }

    /**
     * @Route("/loadList", name="paprec_billing_unit_loadList")
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

        $cols['id'] = array('label' => 'id', 'id' => 'bU.id', 'method' => array('getId'));
        $cols['code'] = array('label' => 'code', 'id' => 'bU.code', 'method' => array('getCode'));
        $cols['name'] = array('label' => 'name', 'id' => 'bU.name', 'method' => array('getName'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(BillingUnit::class)->createQueryBuilder('bU');


        $queryBuilder->select(array('bU'))
            ->where('bU.deleted IS NULL');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('bU.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('bU.code', '?1'),
                    $queryBuilder->expr()->like('bU.name', '?1')
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
     * @Route("/view/{id}", name="paprec_billing_unit_view")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param BillingUnit $billingUnit
     * @return Response
     * @throws EntityNotFoundException
     */
    public function viewAction(Request $request, BillingUnit $billingUnit): Response
    {
        $this->billingUnitManager->isDeleted($billingUnit, true);

        return $this->render('billingUnit/view.html.twig', array(
            'billingUnit' => $billingUnit
        ));
    }

    /**
     * @Route("/add", name="paprec_billing_unit_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $billingUnit = new BillingUnit();

        $form = $this->createForm(BillingUnitType::class, $billingUnit, []);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $billingUnit = $form->getData();

            $billingUnit->setDateCreation(new DateTime);
            $billingUnit->setUserCreation($user);

            $this->em->persist($billingUnit);
            $this->em->flush();

            return $this->redirectToRoute('paprec_billing_unit_view', array(
                'id' => $billingUnit->getId()
            ));

        }

        return $this->render('billingUnit/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="paprec_billing_unit_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param BillingUnit $billingUnit
     * @return RedirectResponse|Response
     * @throws EntityNotFoundException
     */
    public function editAction(Request $request, BillingUnit $billingUnit)
    {
        $user = $this->getUser();

        $this->billingUnitManager->isDeleted($billingUnit, true);

        $form = $this->createForm(BillingUnitType::class, $billingUnit, []);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $billingUnit = $form->getData();

            $billingUnit->setDateUpdate(new DateTime);
            $billingUnit->setUserUpdate($user);

            $this->em->flush();

            return $this->redirectToRoute('paprec_billing_unit_view', array(
                'id' => $billingUnit->getId()
            ));

        }

        return $this->render('billingUnit/edit.html.twig', array(
            'form' => $form->createView(),
            'billingUnit' => $billingUnit
        ));
    }

    /**
     * @Route("/remove/{id}", name="paprec_billing_unit_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, BillingUnit $billingUnit): RedirectResponse
    {
        $billingUnit->setDeleted(new DateTime());

        $this->em->flush();

        return $this->redirectToRoute('paprec_billing_unit_index');
    }

    /**
     * @Route("/removeMany/{ids}", name="paprec_billing_unit_removeMany")
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
            $billingUnits = $this->em->getRepository('App:BillingUnit')->findById($ids);
            foreach ($billingUnits as $billingUnit) {
                $billingUnit->setDeleted(new \DateTime());
            }
            $this->em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }
}
