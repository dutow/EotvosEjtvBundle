<?php

namespace Eotvos\VersenyBundle\Form\Type;

use Eotvos\VersenyBundle\Entity as Entity;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
 * Form type for user registration and data modification.
 *
 * @uses AbstractType
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class UserType extends AbstractType
{

    /**
     * Builds the form.
     * 
     * @param FormBuilder $builder builder
     * @param array       $options form options
     * 
     * @return void
     *
     * @todo Add option for registration/change (no section editing)
     * @todo I18N
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add(
            'email',
            'email',
            array(
                'label' => 'E-mail cím*',
                'attr' => array( 'help' => 'Erre a címre küldjük el a belépéshez szükséges jelszót')
            ))
            ;

        $builder->add('firstname', 'text', array('label' => 'Keresztnév*'));

        $builder->add('year', 'hidden');

        $builder->add('lastname', 'text', array('label' => 'Vezetéknév*'));

        $builder->add('country', 'entity', array('label' => 'Ország*', 'class' => 'EotvosVersenyBundle:Country'));

        $builder->add(
            'postalcode',
            'postalcode',
            array (
                'label' => 'Település',
                'required' => false,
                'attr' => array( 'help' => 'Írd be irányítószámod, majd válaszd ki a hozzá tartozó települést' )
            ))
            ;

        $builder->add(
            'address',
            'text',
            array ( 'label' => 'Cím', 'required' => false, 'attr' => array( 'help' => 'Utca házszám (emelet ajtó)' ) ))
            ;

        $builder->add(
            'other_city',
            'text',
            array('label' => 'Település', 'required' => false, 'attr' => array('style' => 'display:none')))
            ;

        $builder->add(
            'school',
            'schoolselector',
            array(
                'label' => 'Iskola neve*',
                'attr' => array( 'help' => 'Kezdd el gépelni a nevét, majd válassz a listából'), 'required' => false
            ))
            ;

        $builder->add(
            'other_school',
            'text',
            array('label' => 'Iskola neve*', 'required' => false, 'attr' => array('style' => 'display:none')));

        $builder->add('school_teacher', 'text', array('label' => 'Felkészítő tanár*'));

        $builder->add(
            'school_teacher_contact',
            'text',
            array (
                'label' => 'A tanár elérhetőségei',
                'required' => false,
                'attr' => array('help' => 'Telefonszám vagy e-mail cím')
            ))
            ;

        $builder->add(
            'school_year',
            'choice',
            array('label' => 'Évfolyam*', 'empty_value' => 'Válassz', 'choices' => array(
                '9' => '9. évfolyam',
                '10' => '10. évfolyam',
                '11' => '11. évfolyam',
                '12' => '12. évfolyam',
                '13' => '13. évfolyam',
            )))
            ;

        $builder->add('sections', 'entity', array(
            'class' => 'Eotvos\VersenyBundle\Entity\Section',
            'multiple' => true,
            'expanded' => true,
            'required' => true,
            'query_builder' => function(Entity\SectionRepository $er){
                $now = new \DateTime();
                $now->sub(new \DateInterval('P1D'));

                return $er->createQueryBuilder('s')->where('s.registration_until > :now')->setParameter('now', $now);
            }
        ));

    }

    /**
     * Returns the name of the form type.
     * 
     * @return string
     */
    public function getName()
    {
        return 'user';
    }

    /**
     * Default options.
     * 
     * @param array $options form options
     * 
     * @return array options
     */
    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Eotvos\VersenyBundle\Entity\User',
        );
    }

}

