<?php

namespace Eotvos\VersenyBundle\Form;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Eotvos\VersenyBundle\Entity\Submission;
use Eotvos\VersenyBundle\Entity\UploadRoundSecurityToken;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\HttpFoundation\Response;

/**
 * Form for simple one-file uploads for the informatic contests.
 *
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 *
 * @todo Make it containeraware?
 * @todo generalize
 */
class InfoFileForm
{

    /**
     * Generates form fields.
     * 
     * @param mixed $container Service container
     * 
     * @return Form
     */
    public function buildForm($container)
    {

        $conArr = array(
            'file' => new File(array(
                'maxSize' => '1024k',
                'maxSizeMessage' => 'Túl nagy file',
            ))
        );
        $constraints = new Collection($conArr);

        // we don't need csrf because this is used with a secret token anyways
        $form = $container
            ->get('form.factory')
            ->createBuilder('form', null, array('validation_constraint' => $constraints, 'csrf_protection' => false))
            ->add('file', 'file')
            ->getForm()
            ;

        return $form;
    }


    /**
     * Moves a file into a new position, and returns it's name.
     * 
     * @param Form   $form form specification
     * @param array  $data form data
     * @param User   $user uploader user
     * @param string $task task name
     * @param int    $id   upload id
     * 
     * @return string if valid, or null.
     *
     * @todo refactor infouploads fix directory
     */
    public function getNewName($form, $data, $user, $task, $id)
    {
        $form->bindRequest($data);
        if ($form->isValid()) {

            $file = $form['file']->getData();

            $fn = $file->getClientOriginalName();
            $newname = $user->getId().'-'.$task.$id.'-'.time().'.'.$file->guessExtension();
            $file->move('../infouploads', $newname);

            return $newname;
        }

        return null;
    }


}
