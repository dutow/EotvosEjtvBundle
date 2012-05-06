<?php

namespace Eotvos\VersenyBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Annotation for the school validator.
 *
 * Because of symfony2 internals (?) it must be set on the class itself.
 *
 * @Annotation
 * 
 * @uses Constraint
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 *
 * @todo make it a property annotation
 */
class School extends Constraint
{
    public $message = 'Az iksola megadása kötelező!';
    public $field;

    /**
     * Can be set on a class.
     * 
     * @return void
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

}
