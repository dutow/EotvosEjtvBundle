<?php

namespace Eotvos\EjtvBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Eotvos\EjtvBundle\Entity as Entity;

/**
 * Downloads the hungarian school list from kir.hu.
 *
 * This list can be accessed using HTTP GET on the following urls:
 * http://www.kir.hu/intezmeny/kereses.asp?l=1&old=<PAGE_ID>&ker1=&ker2=&ker3=
 *
 * At the moment (2011. september) there are ~570 pages on the site, with ten school on one page.
 * The command goes until it finds an empty page, then it stops.
 *
 * Since it uses a specific hungarian page, this command cannot be generalized, but it can be used as an example for
 * other school database importers.
 * 
 * @uses ContainerAwareCommand
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class SchoolImportCommand extends ContainerAwareCommand
{
    /**
     * Command configuration.
     * 
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('ecv:schoolimport')
            ->setDescription('Import or update hungarian school list from kir.hu')
            ;
    }

    /**
     * Downloads and parses one page from the site.
     *
     * The returned array contains information about the school on the page, with the keys:
     *
     * * omid: the OM identifier
     * * type: type of the school
     * * name: name of the scool
     * * tel:  phone number(s) of the school
     * * postalcode
     * * city
     * * address
     * 
     * @param int $pno Page number to be processed
     * 
     * @return array list of the schools on the page
     */
    private function getOnePage($pno)
    {
        $contentLines = file("http://www.kir.hu/intezmeny/kereses.asp?l=1&old=".((int) ($pno))."&ker1=&ker2=&ker3=");

        $schoolsOnPage = array();

        $actualSchool = array();
        foreach ($contentLines as $line) {
            $line = trim(iconv("iso-8859-2", "utf-8", $line));
            if ($line=="") {
                continue;
            }

            // 1: find OM identifier
            $matches = array();
            if (preg_match("/<td valign=\"top\" width=\"35%\"><b>([0-9]*)<\/b><\/td>/", $line, $matches)) {
                if (array_key_exists('omid', $actualSchool)) {
                    $schoolsOnPage[]= $actualSchool;
                }
                $actualSchool = array();
                $actualSchool['omid'] = $matches[1];
                continue;
            }

            // 2: type of scool
            if (count($actualSchool)==1 && preg_match("/^([-a-zA-Z;,öüóőúéáűjíÖÜÓŐÚÉÁŰÍ ]*)$/", trim($line), $matches)) {
                $actualSchool['type'] = trim($matches[1]);

                // TODO: delete unless it's a high school
                //if (strpos($actualSchool['type'], "szakközép")===false && strpos($actualSchool['type'], "gimn")===false) {
                //  $actualSchool = array();
                //}

                continue;
            }

            // 3: Name
            if (preg_match("/<td valign=\"top\"><a href=\"intreszl[^\"]*\">([^<]*)<\/a><\/td>/", $line, $matches)) {
                $actualSchool['name'] = trim($matches[1]);
                continue;
            }

            // 4: Address
            if (preg_match("/<td valign=\"top\">([0-9]*)&nbsp;([^,]*),&nbsp;([^<]*)<\/td>/", $line, $matches)) {
                $actualSchool['postalcode'] = trim($matches[1]);
                $actualSchool['city'] = trim($matches[2]);
                $actualSchool['address'] = trim($matches[3]);
                continue;
            }

            // 5: Phone number
            // TODO: convert them to a standard form. Most are a same, with a few differences, and some school
            // have multiple numbers.
            if (preg_match("%<td valign=\"top\">([,()\\+0-9/ -]*)</td>%", $line, $matches)) {
                $actualSchool['telephone'] = trim($matches[1]);
                continue;
            }
        }

        // if we found an OM identifier, we found a school => add to the list
        if (array_key_exists('omid', $actualSchool)) {
            $schoolsOnPage[]= $actualSchool;
        }

        return $schoolsOnPage;
    }

    /**
     * Executes the school import tasks.
     *
     * This repeatedly calls the getOnePage function until it returns anything.
     *
     * @param InputInterface  $input  Standard input
     * @param OutputInterface $output Standard output
     * 
     * @return void
     *
     * @todo Implement update functionality.
     * @todo transaction
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $i = 0;
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        while (true) {
            $schoolsOnPage = $this->getOnePage($i);
            var_dump($schoolsOnPage);
            if (count($schoolsOnPage)==0) {
                break;
            }
            foreach ($schoolsOnPage as $school) {

                // ez itt egy quixfix FIXME rajonni mi okozza ezt a hibat
                if (!array_key_exists('type', $school)) {
                    $school['type'] = 'unknown';
                }

                if (count($school)<7) {
                    continue;
                }

                $schoolObj = new Entity\School();
                $schoolObj->setOmid($school['omid']);
                $schoolObj->setEductype($school['type']);
                $schoolObj->setName($school['name']);
                $schoolObj->setPostalcode($school['postalcode']);
                $schoolObj->setCity($school['city']);
                $schoolObj->setAddress($school['address']);
                // a few special cases are not handled yet
                $schoolObj->setTelephone(@$school['telephone']);
                $schoolObj->setActive(true); // school can be choosen

                $em->persist($schoolObj);
            }
            $em->flush();
            $i++;
        }
    }

}
