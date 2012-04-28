<?php

namespace Eotvos\VersenyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements postal code related functions.
 *
 * @uses Controller
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class PostalcodeController extends Controller
{
    /**
     * Returns a JSON response with a list containing postal codes starting with given code.
     *
     * @param strng $code Prefix for postal code
     *
     * @return string json
     *
     * @Route("/ajax/postalcode/{code}", name = "postalcode", defaults = { "_format" = "json" } )
     */
    public function ajaxQueryAction($code)
    {
        $pmp = $this->getDoctrine()->getRepository('\EotvosVersenyBundle:Postalcode');
        $results = $pmp->getWithPrefix($code);
        $response = new Response(json_encode(array('prefix' => $code, 'results' => $results)));

        return $response;
    }
}
