<?php
/**
 * Created by PhpStorm.
 * User: frede
 * Date: 30/11/2018
 * Time: 16:42
 */

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use App\Entity\PostalCode;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PostalCodeManager
{

    private $em;
    private $container;
    private $numberManager;
    private $agencyManager;
    private $userManager;

    public function __construct(
        EntityManagerInterface $em,
        ContainerInterface $container,
        NumberManager $numberManager,
        AgencyManager $agencyManager,
        UserManager $userManager
    ) {
        $this->em = $em;
        $this->container = $container;
        $this->numberManager = $numberManager;
        $this->agencyManager = $agencyManager;
        $this->userManager = $userManager;
    }

    public function get(
        $postalCode,
        $returnException = true
    ) {
        try {

            $postalCode = $this->em->getRepository('App:PostalCode')->find($postalCode);

            if ($postalCode === null || $this->isDeleted($postalCode)) {
                if ($returnException) {
                    throw new EntityNotFoundException('postalCodeNotFound');
                }
                return null;
            }

            return $postalCode;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getList(
        $returnException = true
    ) {

        try {

            $postalCodes = $this->em->getRepository('App:PostalCode')->findBy([
                'deleted' => null
            ]);

            if (empty($postalCodes)) {
                if ($returnException) {
                    throw new EntityNotFoundException('postalCodesNotFound');
                }
                return null;
            }

            return $postalCodes;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getByCodeAndCity(
        $code,
        $city,
        $returnException = true
    ) {
        try {

            $postalCode = $this->em->getRepository(PostalCode::class)->findOneBy([
                'code' => $code,
                'city' => $city
            ]);

            if ($postalCode === null || $this->isDeleted($postalCode)) {
                if ($returnException) {
                    throw new EntityNotFoundException('postalCodeNotFound');
                }
                return null;
            }

            return $postalCode;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérification qu'à ce jour le postalCode  n'est pas supprimé
     *
     * @param PostalCode $postalCode
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(PostalCode $postalCode, $throwException = false)
    {
        $now = new \DateTime();

        if ($postalCode->getDeleted() !== null && $postalCode->getDeleted() instanceof \DateTime && $postalCode->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('postalCodeNotFound');
            }
            return true;
        }
        return false;
    }


    /**
     * Retourne les codes postaux donc le code commence par le term en param
     *
     * @param $code
     * @return mixed
     * @throws Exception
     */
    public function getActivesFromCode($code)
    {

        try {

            return $this->em->getRepository(PostalCode::class)->createQueryBuilder('pC')
                ->where('pC.code LIKE :code OR pC.city LIKE :code')
                ->andWhere('pC.deleted is NULL')
                ->setParameter('code', '%' . $code . '%')
                ->getQuery()
                ->getResult();

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Ajout d'un code postal
     *
     * @param $code
     * @param $city
     * @param $agencyName
     * @param $commercialName
     * @param $rentalRate
     * @param $cbrRegTransportRate
     * @param $cbrPonctTransportRate
     * @param $vlPlCfsRegTransportRate
     * @param $vlPlCfsPonctTransportRate
     * @param $vlPlTransportRate
     * @param $bomTransportRate
     * @param $plPonctTransportRate
     * @param $treatmentRate
     * @param $traceabilityRate
     * @param bool $doFlush
     * @return PostalCode
     * @throws Exception
     */
    public function add(
        $code,
        $city,
        $agencyName,
        $commercialName,
        $rentalRate,
        $cbrRegTransportRate,
        $cbrPonctTransportRate,
        $vlPlCfsRegTransportRate,
        $vlPlCfsPonctTransportRate,
        $vlPlTransportRate,
        $bomTransportRate,
        $plPonctTransportRate,
        $treatmentRate,
        $traceabilityRate,
        $doFlush = true
    ) {
        try {

            $postalCode = new PostalCode();

            $postalCode
                ->setCode($code)
                ->setCity($city)
                ->setRentalRate($this->numberManager->normalize15($rentalRate))
                ->setCbrRegTransportRate($this->numberManager->normalize15($cbrRegTransportRate))
                ->setCbrPonctTransportRate($this->numberManager->normalize15($cbrPonctTransportRate))
                ->setVlPlCfsRegTransportRate($this->numberManager->normalize15($vlPlCfsRegTransportRate))
                ->setVlPlCfsPonctTransportRate($this->numberManager->normalize15($vlPlCfsPonctTransportRate))
                ->setVlPlTransportRate($this->numberManager->normalize15($vlPlTransportRate))
                ->setBomTransportRate($this->numberManager->normalize15($bomTransportRate))
                ->setPlPonctTransportRate($this->numberManager->normalize15($plPonctTransportRate))
                ->setTreatmentRate($this->numberManager->normalize15($treatmentRate))
                ->setTraceabilityRate($this->numberManager->normalize15($traceabilityRate))
                ->setZone('1')
                ->setUserCreation($this->userManager->getByUsername('admin', true));

            $agency = $this->agencyManager->getByName($agencyName, false);

            if ($agency !== null) {
                $postalCode
                    ->setAgency($agency);
            }

            $commercialNames = explode(" ", $commercialName);
            $firstName = $commercialNames[0];
            $lastName = $commercialNames[1];

            $commercial = $this->userManager->getByFirstNameAndLastName($firstName, $lastName, false);

            if ($commercial !== null) {
                $postalCode
                    ->setUserInCharge($commercial);
            }

            if ($doFlush) {
                $this->em->flush();
            }

            return $postalCode;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Modification d'un code postal
     *
     * @param $postalCode
     * @param $code
     * @param $city
     * @param $agencyName
     * @param $commercialName
     * @param $rentalRate
     * @param $cbrRegTransportRate
     * @param $cbrPonctTransportRate
     * @param $vlPlCfsRegTransportRate
     * @param $vlPlCfsPonctTransportRate
     * @param $vlPlTransportRate
     * @param $bomTransportRate
     * @param $plPonctTransportRate
     * @param $treatmentRate
     * @param $traceabilityRate
     * @param bool $doFlush
     * @return PostalCode|object|null
     * @throws Exception
     */
    public function update(
        $postalCode,
        $code,
        $city,
        $agencyName,
        $commercialName,
        $rentalRate,
        $cbrRegTransportRate,
        $cbrPonctTransportRate,
        $vlPlCfsRegTransportRate,
        $vlPlCfsPonctTransportRate,
        $vlPlTransportRate,
        $bomTransportRate,
        $plPonctTransportRate,
        $treatmentRate,
        $traceabilityRate,
        $doFlush = true
    ) {
        try {
            $postalCode = $this->get($postalCode);

            if ($postalCode !== null) {
                $postalCode
                    ->setCity($city)
                    ->setRentalRate($this->numberManager->normalize15($rentalRate))
                    ->setCbrRegTransportRate($this->numberManager->normalize15($cbrRegTransportRate))
                    ->setCbrPonctTransportRate($this->numberManager->normalize15($cbrPonctTransportRate))
                    ->setVlPlCfsRegTransportRate($this->numberManager->normalize15($vlPlCfsRegTransportRate))
                    ->setVlPlCfsPonctTransportRate($this->numberManager->normalize15($vlPlCfsPonctTransportRate))
                    ->setVlPlTransportRate($this->numberManager->normalize15($vlPlTransportRate))
                    ->setBomTransportRate($this->numberManager->normalize15($bomTransportRate))
                    ->setPlPonctTransportRate($this->numberManager->normalize15($plPonctTransportRate))
                    ->setTreatmentRate($this->numberManager->normalize15($treatmentRate))
                    ->setTraceabilityRate($this->numberManager->normalize15($traceabilityRate))
                    ->setDateUpdate(new \DateTime());

                $agency = $this->agencyManager->getByName($agencyName, false);

                if ($agency !== null) {
                    $postalCode
                        ->setAgency($agency);
                }

                $commercialNames = explode(" ", $commercialName);
                $firstName = $commercialNames[0];
                $lastName = $commercialNames[1];

                $commercial = $this->userManager->getByFirstNameAndLastName($firstName, $lastName, false);

                if ($commercial !== null) {
                    $postalCode
                        ->setUserInCharge($commercial);
                }

                $quoteRequests = $postalCode->getQuoteRequests();

                if (!empty($quoteRequests) && count($quoteRequests)) {
                    foreach ($quoteRequests as $quoteRequest) {
                        if ($quoteRequest->getPostalCode() !== null) {
                            $quoteRequest->setCity($city);
                        }
                        if ($quoteRequest->getIsSameAddress()) {
                            $quoteRequest
                                ->setBillingCity($city);
                        }

                        $quoteRequestLines = $quoteRequest->getQuoteRequestLines();

                        if (!empty($quoteRequestLines) && count($quoteRequestLines)) {
                            foreach ($quoteRequestLines as $quoteRequestLine) {
                                $quoteRequestLine
                                    ->setRentalRate($this->numberManager->normalize15($rentalRate))
                                    ->setTreatmentRate($this->numberManager->normalize15($treatmentRate))
                                    ->setTraceabilityRate($this->numberManager->normalize15($traceabilityRate));
                            }
                        }
                    }
                }
            }

            if ($doFlush) {
                $this->em->flush();
            }

            return $postalCode;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}
