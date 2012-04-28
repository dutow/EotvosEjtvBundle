<?php

namespace Eotvos\VersenyBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

use Eotvos\VersenyBundle\Entity as Entity;

/**
 * Imports postal codes from a csv given by the only argument.
 *
 * The file must be a comma separated file with the following columns:
 * * Postal code (5 characters)
 * * City name
 * * Chief town id
 * * Country id
 *
 * Currently only the first two columns are used by the application, but the
 * importer tries to load everything.
 *
 * @uses ContainerAwareCommand
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu>
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 *
 * @todo Internatiozation somehow? This is currently hungarian-specific
 */
class PostalImportCommand extends ContainerAwareCommand
{

    /**
     * Task configuration.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('ecv:postalimport')
            ->setDescription('Imports five character postal codes from a csv file')
            ->addArgument('csv', InputArgument::REQUIRED, 'Source file')
            ;
    }


    /**
     * Reads the given CSV line by line, and inserts the records into the database.
     *
     * @param InputInterface  $input  Standard input
     * @param OutputInterface $output Standard output
     *
     * @return void
     *
     * @todo Enclose this function in a transaction
     * @todo Customizable source character encoding
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $rm = $em->getRepository('EotvosVersenyBundle:Postalcode');

        $csvfn = $input->getArgument('csv');

        if (($handle = fopen($csvfn, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                $node = $rm->findByCode($data[0]);
                if ($node==null) {
                    $node = new Entity\Postalcode();
                    $node->setCode($data[0]);
                }
                $node->setName($data[1]);
                $node->setChieftown($data[2]);
                $node->setCountyId($data[3]);
                $em->persist($node);
            }
            $em->flush();
            fclose($handle);
        }
        // todo: delete some?
    }

}
