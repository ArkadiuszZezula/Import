<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use App\Service\ProductTbl;

class StockImportCommand extends Command
{

    protected static $defaultName = 'stock:import';

    /**
     * StockImportCommand constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Import products')
            ->addArgument(
                'mode',
                InputArgument::OPTIONAL,
                'Normal or Test mode'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        $products = $serializer->decode(
            file_get_contents('public/import/stock.csv'),
            'csv'
        );
        $counterError = 0;
        $counterNotImported = 0;

        foreach ($products as $product) {
            $productTbl = new ProductTbl($product);
            $errors = $productTbl->validate();
            $isImported = $productTbl->isImported();
            if (!empty($errors)) {
                $counterError++;
                $output->writeln(
                    $product['Product Name']
                    . ' not Inserted. Errors:'
                    . implode($errors, ', ')
                );
            } else if (!$isImported) {
                $counterNotImported++;
                $output->writeln(
                    $product['Product Name']
                    . ' not Inserted. Product does not meet the requirements.'
                );
            } else {
                $parsedProduct = $productTbl->parse();
                $this->em->persist($parsedProduct);
            }
        }

        $output->writeln('Items processed: ' . count($products));
        $output->writeln('Items were successful: '
            . (count($products)
                - $counterError
                - $counterNotImported
            ));
        $output->writeln('Items were skiped: '
            . ($counterError + $counterNotImported));

        if ($input->getArgument('mode') != 'test') {
            $this->em->flush();
        }
    }
}
