<?php

namespace App\Command;

use App\Service\PostalCodeManager;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

        $filePath = $input->getArgument('filePath');

        /**
         * Chargement du fichier à importer
         */
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $reader->setLoadAllSheets();
        $excelObject = $reader->load($filePath);

        /**
         * Importation des postalCodes
         */
        $worksheet = $excelObject->getSheet(0);
        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator();

            $code = $cellIterator->current()->getValue();
            $cellIterator->next();

            $city = $cellIterator->current()->getValue();
            $cellIterator->next();

            $agencyName = $cellIterator->current()->getValue();
            $cellIterator->next();

            $commercialName = $cellIterator->current()->getValue();
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

            $postalCode = $this->postalCodeManager->update(
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
                false
            );

            if ($postalCode === null) {
                $postalCode = $this->postalCodeManager->add(
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
                    false
                );
                $this->em->persist($postalCode);
            }

        }

        $this->em->flush();

    }
}
