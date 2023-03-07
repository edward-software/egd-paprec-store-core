<?php

namespace App\Controller;

use App\Entity\FollowUp;
use App\Entity\Picture;
use App\Entity\Product;
use App\Entity\ProductLabel;
use App\Entity\QuoteRequest;
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

class DashboardController extends AbstractController
{

    private $em;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->translator = $translator;
    }

    /**
     * @Route("/activity", name="paprec_dashboard_activity_index")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function activityIndexAction(Request $request)
    {
        $user = $this->getUser();
        $userIds = [];

        $columns = [
            'M_4_12',
            'M_3',
            'M_2',
            'M_1',
            'M',
            'TOTAL'
        ];

        $lines = [
            'qr_number',
            'qr_number_opened',
            'qr_number_closed_won',
            'qr_number_closed_not_won',
            'qr_number_total',
            'qr_number_signature',
            'qr_turnover',
            'qr_turnover_opened',
            'qr_turnover_closed_won',
            'qr_turnover_closed_not_won',
            'qr_turnover_total',
            'qr_turnover_signature'
        ];

        $datas = [];
        if ($this->isGranted('ROLE_COMMERCIAL') || $this->isGranted('ROLE_COMMERCIAL_MULTISITES')) {
            $userIds[] = $user->getId();
            $datas[0]['name'] = $user->getFirstName() . ' ' . $user->getLastName();
            foreach ($lines as $line){
                $datas[0][$line] = [];
                foreach ($columns as $column){
                    $datas[0][$line][$column] = '';
                }
            }
        }

        $queryBuilder = $this->em->getRepository(QuoteRequest::class)->createQueryBuilder('qR');

        $queryBuilder->select(array('qR'))
            ->where('qR.deleted IS NULL')
            ->andWhere('qR.userInCharge IN (:userIds)')
            ->setParameter('userIds', $userIds)
        ;

        $quoteRequests = $queryBuilder->getQuery()->getResult();

        return $this->render('dashboard/activity/index.html.twig', [
            'quoteRequests' => $quoteRequests,
            'user' => $user,
            'datas' => $datas
        ]);
    }
}
