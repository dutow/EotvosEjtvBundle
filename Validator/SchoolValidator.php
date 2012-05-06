<?php

namespace Eotvos\VersenyBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\HttpFoundation\Request;
use Comways\Component\Form\RecaptchaField;

/**
 * Validates a school field - it is valid, if a user selected a school and it exists in the database.
 * 
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class SchoolValidator extends \Symfony\Component\Validator\ConstraintValidator
{

    /**
     * Validates a school field - it is valid, if a user selected a school and it exists in the database.
     * 
     * @param Value      $value      field value
     * @param Constraint $constraint form constraint container
     * 
     * @return void
     */
    public function isValid($value, Constraint $constraint)
    {
        if (($value->getSchool()==null) && ($value->getSchool()->getId()!=0) && $value->getOtherSchool()=="") {
            $this->setMessage($constraint->message, array());

            return false;
        }

        return true;
    }

}
