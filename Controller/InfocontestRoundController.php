<?php

namespace Eotvos\VersenyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Eotvos\VersenyBundle\Entity\Submission;
use Eotvos\VersenyBundle\Entity\UploadRoundSecurityToken;

use Eotvos\VersenyBundle\Form\InfoFileForm;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\HttpFoundation\Response;


use Eotvos\VersenyBundle\Form\SimpleFileForm;
use Eotvos\VersenyBundle\Extension\ExtToMime;

/**
 * Controller for rounds requiring a single file upload.
 *
 * This class is used as a service, indetified by "eotvos_verseny.round.upload"
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
     * Returns the ordered list of the standings.
     * 
     * @param mixed $standing without ordering
     * @param mixed $round    round
     * 
     * @return array oredered standing
     *
     * @todo move this to somewhere else
     */
    public function orderStanding($standing, $round)
    {
        $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:Submission');
        $max = array();
        for ($i=1; $i<=10; $i++) {
            $max[$i] = $sr->getMaxFor($round, 'A'.$i);
            $max[$i] = $max[$i][1];
        }

        $standing2 =array();
        foreach ($standing as $k => $v) {
            for ($i=1; $i<=10; $i++) {
                if (isset($v['A'.$i])) {
                    if ($max[$i]!=0) {
                        if (((int) $max[$i])>0) {
                            $v['A'.$i] = (int) floor(10.0*( (float) $v['A'.$i] / (float) $max[$i] ));
                        } else {
                            $v['A'.$i] = (int) floor(10.0*( 1.0/((float) $v['A'.$i] / (float) $max[$i] )));
                        }
                        if ($v['A'.$i]<=0) {
                            $v['A'.$i] = 1;
                        }

                    }
                }
            }
            $standing2[]= array($k, array_sum($v));
        }

        return $standing2;
    }


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
        $subm->setUserId($this->user);
        $subm->setRoundId($this->roundRec->getRound());

        $this->eM->persist($subm);
        $this->eM->flush();

        return true;
    }

    /**
     * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/infocontest/almafa/stat", name = "competition_round_infocontest_stat" )
     * @Template()
     */
    public function statgenAction($term, $sectionSlug, $roundSlug) {

        $this->user = $this->container->get('security.context')->getToken()->getUser();
        if (!$this->container->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('Az oldal eléréséhez be kell jelentkezned!');
        }

        $tpRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:TextPage');

        $this->eM = $this->container->get('doctrine')->getEntityManager();

        $this->roundRec = $tpRep->getForTermWithSlug($term, $roundSlug);
        if (!$this->roundRec) {
            throw new \Exception('Round error!');
        }

        if ($this->roundRec->getRound()->getRoundType()!='infocontest') {
            throw new \Exception('Round type error!');
        }
        $now = new \DateTime();
        if ($this->roundRec->getRound()->getStart() > $now) {
            throw new \Exception('Round not started yet!');
        }

        $this->sectionRec = $tpRep->getForTermWithSlug($term, $sectionSlug);
        if (!$this->sectionRec) {
            throw new \Exception('Round error!');
        }

        $this->config = json_decode($this->roundRec->getRound()->getConfig());

        if (!is_object($this->config)) {
            throw new \Exception('Round error!');
        }

        $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:Submission');
        $this->submissions = $sr->getByRound($this->roundRec->getRound());

        $this->standing = array();

        $this->users = array();

        foreach ($this->config->tasks as $task) {
            $st = array('sn' => $task->short_name, 'ln' => $task->long_name, 'children' => array(), 'type' => $task->type );
            for($i=0;$i<$task->testcases;$i++) {
                $ch = array('values' => array(), 'summ' => '', 'usererr' => 0, 'servererr' => 0, 'name' => $task->short_name.($i+1), 'type' => $task->type );
                // start from ONE, not zero!
                $st['children'][ $i+1 ] = $ch;
            }
            $this->standing[$task->short_name] = $st;
        }

        $max = array();
        $min = array();

        foreach ($this->submissions as $submission) {
            $category = $submission->getCategory();

            if (!isset($this->users[$submission->getUserId()->getId()])) {
                $a = array('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'sum' => 0, 'servererr' => 0, 'usererr' => 0, 'WTF' => 0, 'subm' => array(), 'points' => 0, 'id' => $submission->getUserId()->getId() );
                $this->users[$submission->getUserId()->getId()] = $a;
                $uid = $submission->getUserId()->getId();
            }
            $ua =& $this->users[$submission->getUserId()->getId()] ;

            $data = $submission->getDecodedData();
            $points = $submission->getPoints();
            $servererror = $data->servererror;

            if ($category!='??') {
                $cref =& $this->standing[ $category[0] ];
                $childref =& $cref['children'][ $category[1].@$category[2] ];
                if (!isset($childref['values'][ $uid ])) {
                    //          $childref['values'][$uid] 
                }
            } else {
                $ua['WTF']++;
            }

            if ($servererror) {
                $childref['servererr'] ++;
                $ua['servererr'] ++;
                continue; //servererrorral nem foglalkozunk, az eltunik
            }

            if ($category!='??') {

                if ($cref['type']=='scaled' && $data->usererror) { $childref['usererr']++; $ua['usererr']++; }
                    if ($cref['type']=='hidden' && $data->usererror) { $childref['usererr']++; $ua['usererr']++; }
                        if ($cref['type']=='linear' && $points!=10) { $childref['usererr']++; $ua['usererr']++; }
                            // hidden tasks do not add errors unless specified

                            if ($points!=0) {
                                if ($cref['type']=='scaled') {
                                    // get max and be linear
                                    if (!isset($max[ $category ] )) {
                                        $max[$category] = $sr->getMaxFor($this->roundRec->getRound(), $category);
                                        $max[$category] = $max[$category][1];
                                    }
                                    if ($max[$category]!=0) {
                                        if (((int)$max[$category])>0) {
                                            $points = (int)floor(10.0*( (float)$points / (float)$max[$category] ));
                                        } else {
                                            $points = (int)floor(10.0*( 1.0/((float)$points / (float)$max[$category] )));
                                        }
                                        //              $points = (int)floor(10.0*( (float)$points / (float)$max[$category] ));
                                    }
                                    if ($points<1) $points = 1;
                                }
                                if ($data->usererror) $points = 0;
                                if (!isset($ua['subm'][$category])) {
                                    $ua['subm'][$category] = $points;
                                    $ua[ $category[0] ] += $points;
                                    $ua['points'] += $points;
                                    $childref['values'][$uid] = $points;
                                }
                            }
            }
        }

        foreach ($this->config->tasks as $tk => $task) {
            $sum = 0;
            for($i=0;$i<$task->testcases;$i++) {
                $ref =& $this->standing[ $task->short_name ]['children'][ $i+1 ];

                $ref['summ'] = count($ref['values']).' db, össz '.array_sum($ref['values']).'p, átl '.($ref['values'] == array() ? 0 : number_format(array_sum($ref['values'])/count($ref['values']), 2));

                $sum += array_sum($ref['values']);
            }
            $this->standing[ $task->short_name ]['mmm'] = $sum;
        }

        usort($this->users, function($a, $b) {
            if ($a['points'] == $b['points']) return 0;
            if ($a['points'] < $b['points']) return 1;
            return -1;
        });

        return array( 'ua' => $this->users, 'st'=> $this->standing, 'user' => $this->user );
    }

    protected function handleGenericTags($term, $sectionSlug, $roundSlug) {
        $this->user = $this->container->get('security.context')->getToken()->getUser();
        if (!$this->container->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('Az oldal eléréséhez be kell jelentkezned!');
        }

        $tpRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:TextPage');

        $this->eM = $this->container->get('doctrine')->getEntityManager();

        $this->roundRec = $tpRep->getForTermWithSlug($term, $roundSlug);
        if (!$this->roundRec) {
            throw new \Exception('Round error!');
        }

        if ($this->roundRec->getRound()->getRoundType()!='infocontest') {
            throw new \Exception('Round type error!');
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

        $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:Submission');
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
     * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/infocontest/hidden/{hname}/{answer}", name = "competition_round_infocontest_hidden_answer", defaults = { "answer" = "" } )
     * @Template()
     */
    public function hiddenAction($term, $sectionSlug, $roundSlug, $hname, $answer) {
        try{
            $this->handleGenericTags($term, $sectionSlug, $roundSlug);
        }catch(\Exception $e) {
            return ((array( 'hname' => $hname, 'answer' =>  $answer, 'message' => 'Nagyon rossz helyen jarsz!')));
        }

        $ertek  = -1;
        $task = null;
        $id = null;
        $task = null;
        $message = '';
        foreach ($this->config->tasks as $taskR) {
            if ($taskR->type=='hidden') {
                foreach ($taskR->subs as $k => $v) {
                    if ($k == $hname) {
                        $ertek = $v->base;
                        $task = $taskR->short_name;
                        $id = $v->no;
                        $message = $v->text;
                        if ($answer==$v->answer) {
                            $ertek = 10;
                            $message = $v->suctext;
                        }
                        break;
                    }
                }
            }
        }

        $subm = new Submission();

        $run = array(
            'hidden' => true,
            'hname' => $hname,
            'answer' => $answer,
            'number' => $id,
            'points' => $ertek>0 ? (int)$ertek : 0, 
            'message' => ($ertek!=-1) ? "Sikeres teszt. Pontszám: ".(int)$ertek : "USERERROR: Ez sajnos nem nyert!",
            'servererror' => false,
            'usererror' => $ertek==-1,
        );
        $subm->setData(json_encode($run));

        if ($task!=null) {
            $subm->setCategory($task.$id);
        } else {
            $subm->setCategory('??');
        }
        $subm->setPoints($ertek > 0 ? $ertek : 0);
        $subm->setUserId($this->user);
        $subm->setRoundId($this->roundRec->getRound());

        $this->eM->persist($subm);
        $this->eM->flush();
        if ($ertek==-1) {
            return ((array( 'hname' => $hname, 'answer' =>  $answer, 'message' => 'Sajnos nem nyert!', 'round' => $this->roundRec , 'section' => $this->sectionRec)));
        }

        if (!isset($this->standing[$task])) {
            return ((array( 'hname' => $hname, 'answer' =>  $answer, 'message' => 'Sajnos nem nyert!', 'round' => $this->roundRec , 'section' => $this->sectionRec)));
        }

        $taskRef  =& $this->standing[$task];
        if (!array_key_exists($id, $taskRef['children'])) {
            return ((array( 'hname' => $hname, 'answer' =>  $answer, 'message' => 'Sajnos nem nyert!', 'round' => $this->roundRec , 'section' => $this->sectionRec)));
        }

        //$childRef =& $taskRef['children'][$id];
        //if ($childRef['value'] == 10 && $taskRef['type'] != 'scaled') {
        //  return new Response(json_encode(array( 'hname' => $hname, 'answer' =>  $answer, 'message' => 'Ezt mintha mar megoldottad volna')));
        //}

        if ($ertek!=-1) {
            return ((array( 'hname' => $hname, 'answer' =>  $answer, 'message' => $message, 'points' => $ertek, 'section' => $this->sectionRec, 'round' => $this->roundRec )));
        }


        return ((array( 'hname' => $hname, 'answer' =>  $answer, 'message' => $message)));

    }

    /**
     * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/infocontest/upload/{task}/{id}", name = "competition_round_infocontest_upload" )
     * @Template()
     */
    public function uploadAction($term, $sectionSlug, $roundSlug, $task, $id) {
        $this->handleGenericTags($term, $sectionSlug, $roundSlug);

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

        $formBuilder = new InfoFileform();
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
        $this->handleGenericTags($term, $sectionSlug, $roundSlug);

        return array(
            'config' => $this->config,
            'round' => $this->roundRec->getRound(),
            'section' => $this->sectionRec->getSection(),
            'until' => $this->roundRec->getRound()->getStop(),
            'standing' => $this->standing,
            'submissions' => $this->submissions,
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
        return "eotvos_verseny.round.infocontest:activeDescriptionAction";
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

    /**
     * Returns the links for participating in the section.
     *
     * @param int $term
     * @param Section $selection
     * @param Round $round
     *
     * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
     * @since   2011-11-11
     * @version 2011-11-11
     */
    public function getRoundLinks($term, $section, $round)
    {
        $methods = array();

        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!$user || !is_object($user)) {
            return array();
        }

        $found = false;
        foreach ($user->getSections() as $userSec) {
            if ($userSec->getId() == $section->getId()) {
                $found = true;
            }
        }
        if (!$found) {
            return array();
        }

        $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:Submission');

        $now = new \DateTime();
        $ended = $round->getStop() < $now;
        $started = $round->getStart() < $now;

        $config = json_decode($round->getConfig());

        if (!$started) {
            return array();
        }

        if (!$ended) {
            $methods['Verseny'] = $this->container->get('router')->generate('competition_round_infocontest_index', array( 'term' => $term, 'sectionSlug' => $section->getPage()->getSlug(), 'roundSlug' => $round->getPage()->getSlug() ));
        }

        return $methods;
    }

}
