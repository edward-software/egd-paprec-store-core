<?php

namespace App\Command;

use App\Service\PostalCodeManager;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePostalCodeCommand extends Command
{
    private $postalCodeManager;
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        PostalCodeManager $postalCodeManager
    ) {
        parent::__construct();

        $this->em = $em;
        $this->postalCodeManager = $postalCodeManager;
    }

    protected function configure()
    {
        $this
            ->setName('egd:update-postal-code')
            ->addArgument(
                'filePath',
                InputArgument::REQUIRED,
                ''
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /**
         * A décommenter pour les tests en local
         */
//        ini_set("memory_limit", "-1");
//        ini_set("max_execution_time", "600");

        $filePath = $input->getArgument('filePath');

        /**
         * Chargement du fichier à importer
         */
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $reader->setLoadAllSheets();
        $excelObject = $reader->load($filePath);

        $postalCodeList = $this->postalCodeManager->getList(true);

        $postalCodes = [];

        if (!empty($postalCodeList) && count($postalCodeList)) {
            foreach ($postalCodeList as $postalCode) {
                $postalCodes[$postalCode->getCode()][$postalCode->getCity()] = $postalCode;
            }
        }

        /**
         * Importation des postalCodes
         */
        $worksheet = $excelObject->getSheet(0);
        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator();

            $code = $cellIterator->current()->getValue();
            $cellIterator->next();

            $city = $cellIterator->current()->__toString();
            $cellIterator->next();

            $agencyName = $cellIterator->current()->getValue();
            $cellIterator->next();

            $commercialEmail = $cellIterator->current()->getValue();
            $cellIterator->next();

            $rentalRate = $cellIterator->current()->getValue();
            $cellIterator->next();

            $cbrRegTransportRate = $cellIterator->current()->getValue();
            $cellIterator->next();

            $cbrPonctTransportRate = $cellIterator->current()->getValue();
            $cellIterator->next();

            $vlPlCfsRegTransportRate = $cellIterator->current()->getValue();
            $cellIterator->next();

            $vlPlCfsPonctTransportRate = $cellIterator->current()->getValue();
            $cellIterator->next();

            $vlPlTransportRate = $cellIterator->current()->getValue();
            $cellIterator->next();

            $bomTransportRate = $cellIterator->current()->getValue();
            $cellIterator->next();

            $plPonctTransportRate = $cellIterator->current()->getValue();
            $cellIterator->next();

            $treatmentRate = $cellIterator->current()->getValue();
            $cellIterator->next();

            $traceabilityRate = $cellIterator->current()->getValue();
            $cellIterator->next();

            if (array_key_exists($code, $postalCodes) && array_key_exists((string)$city, $postalCodes[$code])) {
                $this->postalCodeManager->update(
                    $postalCodes[$code][$city],
                    $code,
                    $city,
                    $agencyName,
                    $commercialEmail,
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
                    false
                );
            } else {
                $postalCode = $this->postalCodeManager->add(
                    $code,
                    $city,
                    $agencyName,
                    $commercialEmail,
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
                    false
                );
                $this->em->persist($postalCode);
            }

        }

        $this->em->flush();

    }
}
