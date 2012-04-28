<?php

namespace Eotvos\VersenyBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

/**
 * Custom for type for postal code based city selection.
 * 
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class PostalcodeType extends \Symfony\Component\Form\AbstractType
{

    private $em; // entitymanager

    /**
     * Constructor.
     * 
     * @param EntityManager $em doctrine entity manager
     * 
     * @return void
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * getParent
     * 
     * @param array $options form options
     * 
     * @return string
     */
    public function getParent(array $options)
    {
        return 'field';
    }

    /**
     * Returns the name of the field.
     * 
     * @return string
     */
    public function getName()
    {
        return 'postalcode';
    }

    /**
     * Generates the neccessary field logic.
     * 
     * @param FormView      $view view
     * @param FormInterface $form form specification
     * 
     * @return void
     */
    public function buildViewBottomUp(FormView $view, FormInterface $form)
    {
        $value = $view->get('value');
        if ($value) {
            $view->set('value', substr($value, 0, 4));

            $pmp = $this->em->getRepository('\EotvosVersenyBundle:Postalcode');
            $results = $pmp->getWithPrefix(substr($value, 0, 4));
            $view->set('cities', $results);
            $view->set('cities_selected', $value);
        } else {
            $view->set('cities', array());
            $view->set('cities_selected', '');
        }
    }

}
