<?php

namespace Eotvos\VersenyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for school related tasks.
 *
 * @uses Controller
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class SchoolController extends Controller
{
    /**
     * Generates a JSON list from schools starting with prefix and active in the given year, with a minimum prefix length of 2. 
     *
     * Optionally a GET parameter named "q" can be used in place of prefix for existing jQuery plugins like jquery.autocomplete, and q has a greater precedence.
     *
     * @param int $year Year
     * @param string $prefix Prefix with minimum length of 2
     *
     * @return string json
     *
     * @Route("/{year}/ajax/school/{prefix}", name = "schoolselect", defaults = { "_format" = "json", "prefix" = "" } )
     */
    public function getAction($year, $prefix)
    {
        $request = $this->getRequest();
        $q = $request->query->get('q');
        if($q) $prefix = $q;
        if(strlen($prefix)>=2){
            $pmp = $this->getDoctrine()->getRepository('\EotvosVersenyBundle:School');
            $results = $pmp->getWithPrefix($prefix);
        }else{
            $results = array();
        }
        $response = new Response(json_encode(array( 'prefix' => $prefix, 'results' =>  $results)));
        return $response;
    }
}
