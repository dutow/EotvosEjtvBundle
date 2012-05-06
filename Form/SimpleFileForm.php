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
 * Form for simple one file uploads.
 *
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 *
 * @todo Make it ContainerAware?
 */
class SimpleFileForm
{

    /**
     * buildForm
     * 
     * @param ContainerInterface $container service container
     * @param Configuration      $config    configuration
     * 
     * @return void
     */
    public function buildForm($container, $config)
    {

        $conArr = array(
            'file' => new File(array(
                'maxSize' => $config->maxfilesize.'k',
                'maxSizeMessage' => 'Túl nagy file',
            ))
        );
        $constraints = new Collection($conArr);

        // we don't need csrf because this is used with a secret token anyways
        $form = $container
            ->get('form.factory')
            ->createBuilder('form', null, array('validation_constraint' => $constraints, 'csrf_protection' => false))
            ->add('file', 'file')
            ->getForm();

        return $form;
    }


    /**
     * creates a file based on the form
     * 
     * @param EntityManager $entityManager doctrine entity manager
     * @param Configuration $config        configuration
     * @param User          $user          uploader user
     * @param int           $round         target round
     * @param Form          $form          form specification
     * @param array         $data          form data
     * 
     * @return string file name
     *
     * @todo generalize the fix directory
     */
    public function createFile($entityManager, $config, $user, $round, $form, $data)
    {

        $category = $data->request->get('category');
        if (isset($config->categories) && !$category) {
            return false;
        }
        $form->bindRequest($data);
        if ($form->isValid()) {

            $file = $form['file']->getData();


            $fn = $file->getClientOriginalName();

            $submit = new Submission();
            $submit->setUserId($user);
            $submit->setRoundId($round);
            $submit->setData(json_encode(array('filename' => $fn)));
            if ($category!="") {
                $submit->setCategory($category);
            }
            $entityManager->persist($submit);
            $entityManager->flush();

            $newname = $submit->getId().'.'.$file->guessExtension();
            $file->move('../uploads', $newname);

            return true;
        }

        return false;
    }


}
