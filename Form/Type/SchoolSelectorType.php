<?php

namespace Eotvos\VersenyBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\Extension\Core\DataTransformer\ScalarToChoiceTransformer;

/**
 * School selector field.
 *
 * Generates an ajax-autocomplete input with a hidden field. 
 * 
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class SchoolSelectorType extends \Symfony\Component\Form\AbstractType
{

    private $em; // doctrine entity manager

    /**
     * Constructor.
     * 
     * @param EntityManager $em doctrine entity manager.
     * 
     * @return void
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Returns the name of the field type.
     * 
     * @return string
     */
    public function getName()
    {
        return 'schoolselector';
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
     * Builds the form logic.
     * 
     * @param FormView      $view form view
     * @param FormInterface $form form
     * 
     * @return void
     */
    public function buildViewBottomUp(FormView $view, FormInterface $form)
    {
        $value = $view->get('value');
        if (is_object($value)) {
            $view->set('value', $value->getId());
        }
        if ($value) {
            $pmp = $this->em->getRepository('\EotvosVersenyBundle:School');
            $result = $pmp->findOneById((int) $value->getId());
            if ($result) {
                $view->set('value_name', $result->getName().', '.$result->getCity());
                $view->set('box_name', $result->getName());
                $view->set('box_omid', $result->getOmid());
                $view->set('box_place', $result->getPostalcode().' '.$result->getcity());
                $view->set('box_addr', $result->getAddress());

            } else {
                $view->set('value_name', "");
                $view->set('cities_selected', '');
                $view->set('box_name', '');
                $view->set('box_omid', '');
                $view->set('box_place', '');
                $view->set('box_addr', '');
            }
        } else {
            $view->set('value_name', "");
            $view->set('cities_selected', '');
            $view->set('box_name', '');
            $view->set('box_omid', '');
            $view->set('box_place', '');
            $view->set('box_addr', '');
        }
    }
}
