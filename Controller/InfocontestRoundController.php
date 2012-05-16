<?php

namespace Eotvos\EjtvBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Eotvos\VersenyrBundle\Entity\Submission;
use Eotvos\VersenyrBundle\Entity\UploadRoundSecurityToken;

use Eotvos\EjtvBundle\Form\InfoFileForm;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\HttpFoundation\Response;



/**
 * Controller for rounds requiring a single file upload.
 *
 * This class is used as a service, indetified by "eotvos.versenyr.round.upload"
 *
 * @todo describe the process
 * @todo move general route parts here
 *
 * @todo check round type at many places!
 *
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class InfocontestRoundController extends ContainerAware
{
    /**
     * Rendes a template, copied from Controller.
     * 
     * @param mixed $view 
     * @param array $parameters 
     * @param Response $response 
     * 
     * @return rendered template
     */
    protected function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->container->get('templating')->renderResponse($view, $parameters, $response);
    }


    protected function runTest($rootD, $task, $no, $userF, $extra='')
    {
        $o = array();
        $r = 999;
        $extraF = ''; $extraN = null;
        if ($extra!='') {
            $extraN = $userF.$extra;
            $extraF = $this->container->get('kernel')->getRootDir().'/../web/2011/letoltes/informatika/extra/'.$userF.$extra;
        }
        $command = $this->container->get('kernel')->getRootDir().'/../'.$rootD.'/'.$task->short_name.'/'.$task->tester;
        $input = $this->container->get('kernel')->getRootDir().'/../'.$rootD.'/'.$task->short_name.'/input'.$no.'.txt';
        $output = $this->container->get('kernel')->getRootDir().'/../'.$rootD.'/'.$task->short_name.'/output'.$no.'.txt';
        $user = $this->container->get('kernel')->getRootDir().'/../infouploads/'.$userF;
        $cstr = $command.' '.$input.' '.$output.' '.$user.' '.$extraF;
        $val = exec($cstr, $o, $r);
        if ($r==0) {
            return array(
                'number' => $no,
                'points' => (int)$val, 
                'message' => "Sikeres teszt. Pontszám: ".(int)$val,
                'servererror' => false,
                'usererror' => false,
                'data' => $user,
                'extra' => $extraN,
                'command' => $cstr,
            );
        }
        if (strstr($val, 'USERERROR')!==false) {
            return array(
                'number' => $no,
                'points' => 0, 
                'message' => $val,
                'servererror' => false,
                'usererror' => true,
                'data' => $user,
                'extra' => $extraN,
                'command' => $cstr,
            );
        } else {
            return array(
                'number' => $no,
                'points' => 0, 
                'message' => $val,
                'servererror' => true,
                'usererror' => false,
                'data' => $user,
                'extra' => $extraN,
                'command' => $cstr,
            );
        }
    }

    protected function saveSubmission($taskName, $taskCaseNo, $userSubmit)
    {
        foreach ($this->config->tasks as $key => $tasks) {
            if ($tasks->short_name == $taskName) {
                $task = $tasks;
                $taskNumber = $key;
                break;
            }
        }
        $extra = '';
        if (isset($task->extra) && $task->extra!='') $extra = $task->extra;

        $run = $this->runTest($this->config->directory, $task, $taskCaseNo, $userSubmit, $extra);

        $subm = new Submission();

        $subm->setData(json_encode($run));

        $subm->setCategory($task->short_name.$taskCaseNo);
        $subm->setPoints($run['points']);
        $subm->setUser($this->user);
        $subm->setRound($this->roundRec->getRound());

        $this->eM->persist($subm);
        $this->eM->flush();
        $subm->setUser($this->user);
        $subm->setRound($this->roundRec->getRound());
        $this->eM->flush();

        return true;
    }

    protected function handleGenericTags($term, $sectionSlug, $roundSlug) {
        $this->user = $this->container->get('security.context')->getToken()->getUser();
        if (!$this->container->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('Az oldal eléréséhez be kell jelentkezned!');
        }

        $tpRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyrBundle:TextPage');

        $this->eM = $this->container->get('doctrine')->getEntityManager();

        $this->roundRec = $tpRep->getForTermWithSlug($term, $roundSlug);
        if (!$this->roundRec) {
            throw new \Exception('Round error!');
        }

        $now = new \DateTime();
        if ($this->roundRec->getRound()->getStart() > $now) {
            throw new \Exception('Round not started yet!');
        }
        if ($this->roundRec->getRound()->getStop() < $now) {
            throw new \Exception('Round ended!');
        }

        $this->sectionRec = $tpRep->getForTermWithSlug($term, $sectionSlug);
        if (!$this->sectionRec) {
            throw new \Exception('Round error!');
        }

        $this->config = json_decode($this->roundRec->getRound()->getConfig());

        if (!is_object($this->config)) {
            throw new \Exception('Round error!');
        }

        $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyrBundle:Submission');
        $this->submissions = $sr->getByUserAndRound($this->user, $this->roundRec->getRound());

        $this->standing = array();

        foreach ($this->config->tasks as $task) {
            $st = array('sn' => $task->short_name, 'ln' => $task->long_name, 'children' => array(), 'type' => $task->type );
            for($i=0;$i<$task->testcases;$i++) {
                $ch = array('value' => null, 'name' => $task->short_name.($i+1), 'type' => $task->type, 'submitted' => 0, 'error' => 0, 'cansubmit' => true );
                // start from ONE, not zero!
                $st['children'][ $i+1 ] = $ch;
            }
            $this->standing[$task->short_name] = $st;
        }

        $now = new \DateTime();
        $now->sub(new \DateInterval('PT5M'));

        foreach ($this->submissions as $submission) {
            $category = $submission->getCategory();

            $data = $submission->getDecodedData();
            $points = $submission->getPoints();
            $servererror = $data->servererror;

            if ($servererror) continue; //servererrorral nem foglalkozunk, az eltunik

            if ($category!='??') {
                $cref =& $this->standing[ $category[0] ];
                $childref =& $cref['children'][ $category[1].@$category[2] ];
                $childref['submitted']++;

                if ($childref['value']==null && $childref['cansubmit']) {
                    $childref['cansubmit'] = $now > $submission->getSubmittedAt();
                    if ($points==10 && $cref['type']!='scaled') $childref['cansubmit'] = false;
                    if ($cref['type'] == 'hidden') $childref['cansubmit'] = false;
                }


                if ($cref['type']=='scaled' && $data->usererror) $childref['error']++;
                if ($cref['type']!='scaled' && $points!=10) $childref['error']++;

                if ($points!=0) {
                    if ($cref['type']=='scaled') {
                        // get max and be linear
                        $max = $sr->getMaxFor($this->roundRec->getRound(), $category);
                        $max = $max[1];
                        if ($max>0) {
                            $points = (int)floor(10.0*( (float)$points / (float)$max ));
                        } else {
                            $points = (int)floor(10.0*( 1.0 /( (float)$points / (float)$max )));
                        }
                        if ($points<1) $points = 1;
                        if ($data->usererror) $points = 0;
                        //$submission->setPoints($points); // don't save!
                    }
                    if ($childref['value'] === null) {
                        $childref['value'] = $points;
                    }
                }
            }
        }
    }

    /**
     * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/infocontest/upload/{task}/{id}", name = "competition_round_infocontest_upload" )
     * @Template()
     */
    public function uploadAction($term, $sectionSlug, $roundSlug, $task, $id) {
        $this->handleGenericTags($term, $sectionSlug, $roundSlug);

        $termRec = $this->container->get('doctrine')->getRepository('EotvosVersenyrBundle:Term')
            ->findOneByName($term);

        if (!isset($this->standing[$task])) {
            throw new Exception("bad task");
        }

        $taskRef  =& $this->standing[$task];
        if (!array_key_exists($id, $taskRef['children'])) {
            throw new Exception("bad case id");
        }

        $childRef =& $taskRef['children'][$id];
        if ($childRef['value'] == 10 && $taskRef['type'] != 'scaled') {
            throw new Exception("perfect resubmit");
        }

        $formBuilder = new InfoFileForm();
        $form = $formBuilder->buildForm($this->container);

        if ($this->container->get('request')->getMethod() === 'POST') {
            if ($newname = $formBuilder->getNewName($form, $this->container->get('request'), $this->user, $task, $id)) {
                // run tests
                $this->saveSubmission($task, $id, $newname);
            }

            return new RedirectResponse( ($this->container->get('router')->generate('competition_round_infocontest_index', array( 'term' => $term, 'sectionSlug' => $this->sectionRec->getSlug(), 'roundSlug' => $this->roundRec->getSlug() ))), 302);
        }

        return array(
            'config' => $this->config,
            'round' => $this->roundRec->getRound(),
            'section' => $this->sectionRec->getSection(),
            'until' => $this->roundRec->getRound()->getStop(),
            'standing' => $this->standing,
            'task' => $task,
            'taskid' => $id,
            'form' => $form->createView(),
            'term' => $termRec,
        );

    }

    /**
     * Index action for the programming contest
     *
     * @param int $term
     * @param string $selectionSlug
     * @param string $roundSlug
     *
     * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
     * @since   2011-11-03
     * @version 2011-11-03
     *
     * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/infocontest/index", name = "competition_round_infocontest_index" )
     * @Template()
     */
    public function contestAction($term, $sectionSlug, $roundSlug)
    {
        $termRec = $this->container->get('doctrine')->getRepository('EotvosVersenyrBundle:Term')
            ->findOneByName($term);

        $this->handleGenericTags($term, $sectionSlug, $roundSlug);

        return array(
            'config' => $this->config,
            'round' => $this->roundRec->getRound(),
            'section' => $this->sectionRec->getSection(),
            'until' => $this->roundRec->getRound()->getStop(),
            'standing' => $this->standing,
            'submissions' => $this->submissions,
            'term' => $termRec,
        );
    }

    /**
     * Returns the template name for the round description panel.
     *
     * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
     * @since   2011-11-11
     * @version 2011-11-11
     *
     * @return string
     */
    public function getTemplateName()
    {
        return "eotvos.versenyr.round.info:activeDescriptionAction";
    }

    /**
     * Renders the content for the round description panel.
     *
     * @param Round $round
     *
     * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
     * @since   2011-11-11
     * @version 2011-11-11
     *
     * @todo out: submission checks
     *
     * @Template()
     */
    public function activeDescriptionAction($round)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        return array('round' => $round->getRound(), 'spec' => json_decode($round->getRound()->getConfig()), 'user' => $user );
    }

}
