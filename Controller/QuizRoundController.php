<?php
/**
 * Declaration of UploadRoundController class.
 *
 * @category EotvosVerseny
 * @package Controller
 * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
 * @copyright 2011, Cancellar Informatikai Bt
 * @license http://www.opensource.org/licenses/BSD-2-Clause
 */

namespace Eotvos\VersenyBundle\Controller;

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


use Eotvos\VersenyBundle\Form\SimpleFileForm;
use Eotvos\VersenyBundle\Extension\ExtToMime;

/**
 * Controller for rounds requiring a single file upload.
 *
 * ### ChangeLog:
 * #### 2011-10-11: Zsolt Parragi <zsolt.parragi@cancellar.hu>
 * * Added flashUploadAction
 *
 * This class is used as a service, indetified by "eotvos_verseny.round.upload"
 *
 * @todo describe the process
 * @todo move general route parts here
 *
 * @todo check round type at many places!
 *
 * @category EotvosVerseny
 * @package Controller
 * @license http://www.opensource.org/licenses/BSD-2-Clause
 * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
 * @since   2011-09-26
 * @version 2011-10-11
 */
class QuizRoundController extends ContainerAware
{

  public function orderStanding($standing){
    $standing2 =array();
    foreach($standing as $k => $v){
      $standing2 []= array($k, array_sum($v));
    }
    return $standing2;
  }

  /**
    * Renders a view.
    *
    * Copied from symfony's Controller class.
    *
    * @param string   $view The view name
    * @param array    $parameters An array of parameters to pass to the view
    * @param Response $response A response instance
    *
    * @return Response A Response instance
    *
    * @author Fabien Potencier <fabien@symfony.com>
    * @version 2011-11-03
    * @since   2011-11-03
    */
  protected function render($view, array $parameters = array(), Response $response = null)
  {
      return $this->container->get('templating')->renderResponse($view, $parameters, $response);
  }

  /**
   * Allows the user to do the quiz
   *
   * @param int $term
   * @param string $selectionSlug
   * @param string $roundSlug
   *
   * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
   * @since   2011-11-03
   * @version 2011-11-03
   *
   * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/quiz/submit", name = "competition_round_quiz_submit", defaults = { "_format" = "json" } )
   */
  public function submitAction($term, $sectionSlug, $roundSlug)
  {
    $user = $this->container->get('security.context')->getToken()->getUser();
    if(!$this->container->get('security.context')->isGranted('ROLE_USER')){
      return new Response(json_encode(array('success' => false)));
    }

    $tpRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:TextPage');

    $dEm = $this->container->get('doctrine')->getEntityManager();

    $roundRec = $tpRep->getForTermWithSlug($term, $roundSlug);
    if(!$roundRec){
      return new Response(json_encode(array('success' => false)));
    }

    $sectionRec = $tpRep->getForTermWithSlug($term, $sectionSlug);
    if(!$sectionRec){
      return new Response(json_encode(array('success' => false)));
    }

    $config = json_decode($roundRec->getRound()->getConfig());

    if(!is_object($config)){
      return new Response(json_encode(array('success' => false)));
    }

    $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:Submission');
    $submission = $sr->getLastByUserAndRound($user, $roundRec->getRound());
    if($submission == null){
      return new Response(json_encode(array('success' => false)));
    }
    $time_until = $submission->getSubmittedAt()->add(new \DateInterval('PT'.$config->time.'M'));

    $request = $this->container->get('request');
    $data = $request->request->all();

    $now = new \DateTime();
    if($now > $time_until){
      return new Response(json_encode(array('success' => false)));
    }

    $data['last-modified'] = $now;

    $f = fopen('/tmp/'.$user->getId().'-'.$submission->getId().'-'.time().'.json', 'w');
    fwrite($f, json_encode($data));
    fclose($f);

    $submission->setData(json_encode($data));

    $dEm->persist($submission);
    $dEm->flush();

    return new Response(json_encode(array('success' => true)));

  }

  /**
   * Allows the admin to do view quiz
   *
   * @param int $term
   * @param string $selectionSlug
   * @param string $roundSlug
   *
   * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
   * @since   2011-11-03
   * @version 2011-11-03
   *
   * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/quiz/read/{uid}", name = "competition_round_quiz_reader" )
   * @Template()
   */
  public function readAction($term, $sectionSlug, $roundSlug, $uid)
  {

    $uRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:User');
    $writer = $uRep->findOneById($uid);

    $tpRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:TextPage');

    $dEm = $this->container->get('doctrine')->getEntityManager();

    $roundRec = $tpRep->getForTermWithSlug($term, $roundSlug);
    if(!$roundRec){
      return $this->render('EotvosVersenyBundle::error.twig.html', array(
        'code' => 404,
      ));
    }

    $sectionRec = $tpRep->getForTermWithSlug($term, $sectionSlug);
    if(!$sectionRec){
      return $this->render('EotvosVersenyBundle::error.twig.html', array(
        'code' => 404,
      ));
    }

    $config = json_decode($roundRec->getRound()->getConfig());

    if(!is_object($config)){
      return $this->render('EotvosVersenyBundle::error.twig.html', array(
        'code' => 500,
      ));
    }

    $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:Submission');
    $submission = $sr->getLastByUserAndRound($writer, $roundRec->getRound());
    if($submission == null){
      throw new Exception("No submission!");
    }
    $time_until = $submission->getSubmittedAt()->add(new \DateInterval('PT'.$config->time.'M'));


    $data = json_decode($submission->getData(), true);

    $now = new \DateTime();

    foreach($config->tasks as $tk => $task){
      if($task->type=="freetext"){
        $arr = explode('%@%', $task->text);
        $str = ''; $i = 0;
        foreach($arr as $elem){
          if($str!=''){
            $v = '';
            $v = ''; if(isset($data[$tk.'_'.$i])) $v = $data[$tk.'_'.$i];
            $str .= '<input type="text" name="'.$tk.'_'.$i.'" disabled="disabled" value="'.$v.'" />';
            $i++;
          }
          $str .= $elem;
        }
        $arr = explode('%@@@%', $str);
        $str = '';
        foreach($arr as $ii => $elem){
          if($ii!=0){
            $v = ''; if(isset($data[$tk.'_'.$i])) $v = $data[$tk.'_'.$i];
            $str .= '<p style="border: #777 2px solid; background: #eee; padding: 3px;" name="'.$tk.'_'.$i.'">'.$v.'</p>';
            $i++;
          }
          $str .= $elem;
        }
        $task->text = $str;
      }
    }

    return array(
      'config' => $config,
      'data' => $data,
      'writer' => $writer,
      'round' => $roundRec->getRound(),
      'section' => $sectionRec->getSection(),
      'until' => $time_until
    );
  }

  /**
   * Allows the user to do the quiz
   *
   * @param int $term
   * @param string $selectionSlug
   * @param string $roundSlug
   *
   * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
   * @since   2011-11-03
   * @version 2011-11-03
   *
   * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/quiz/write", name = "competition_round_quiz_write" )
   * @Template()
   */
  public function quizAction($term, $sectionSlug, $roundSlug)
  {

    $user = $this->container->get('security.context')->getToken()->getUser();
    if(!$this->container->get('security.context')->isGranted('ROLE_USER')){
      throw new AccessDeniedException('Az oldal eléréséhez be kell jelentkezned!');
    }

    $tpRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:TextPage');

    $dEm = $this->container->get('doctrine')->getEntityManager();

    $roundRec = $tpRep->getForTermWithSlug($term, $roundSlug);
    if(!$roundRec){
      return $this->render('EotvosVersenyBundle::error.twig.html', array(
        'code' => 404,
      ));
    }

    $sectionRec = $tpRep->getForTermWithSlug($term, $sectionSlug);
    if(!$sectionRec){
      return $this->render('EotvosVersenyBundle::error.twig.html', array(
        'code' => 404,
      ));
    }

    $config = json_decode($roundRec->getRound()->getConfig());

    if(!is_object($config)){
      return $this->render('EotvosVersenyBundle::error.twig.html', array(
        'code' => 500,
      ));
    }

    $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:Submission');
    $submission = $sr->getLastByUserAndRound($user, $roundRec->getRound());
    if($submission == null){
      $submission = new Submission();
      $submission->setRoundId($roundRec->getRound());
      $submission->setUserId($user);
      $data  =array('last-modified' => new \DateTime());
      foreach($config->tasks as $tk => $task){
        if(isset($task->defaults)) foreach($task->defaults as $k => $v){
          $data[ $tk . '_' . $k ] = $v;
        }
      }
      $submission->setData(json_encode($data));
      $dEm->persist($submission);
      $dEm->flush();
    }
    $time_until = $submission->getSubmittedAt()->add(new \DateInterval('PT'.$config->time.'M'));


    $data = json_decode($submission->getData(), true);

    $now = new \DateTime();
    if($now > $time_until){
      return $this->render('EotvosVersenyBundle:QuizRound:lejartido.html.twig', array( 'section' => $sectionRec->getSection() ));
    }

    foreach($config->tasks as $tk => $task){
      if($task->type=="freetext"){
        $arr = explode('%@%', $task->text);
        $str = ''; $i = 0;
        foreach($arr as $elem){
          if($str!=''){
            $v = '';
            $v = ''; if(isset($data[$tk.'_'.$i])) $v = $data[$tk.'_'.$i];
            $str .= '<input type="text" name="'.$tk.'_'.$i.'" value="'.$v.'" />';
            $i++;
          }
          $str .= $elem;
        }
        $arr = explode('%@@@%', $str);
        $str = '';
        foreach($arr as $ii => $elem){
          if($ii!=0){
            $v = ''; if(isset($data[$tk.'_'.$i])) $v = $data[$tk.'_'.$i];
            $str .= '<textarea name="'.$tk.'_'.$i.'" />'.$v.'</textarea>';
            $i++;
          }
          $str .= $elem;
        }
        $task->text = $str;
      }
    }

    return array(
      'config' => $config,
      'data' => $data,
      'round' => $roundRec->getRound(),
      'section' => $sectionRec->getSection(),
      'until' => $time_until
    );
  }

  /**
   * Returns the template name for the round description panel.
   *
   * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
   * @since   2011-11-03
   * @version 2011-11-03
   *
   * @return string
   */
  public function getTemplateName(){
    return "eotvos_verseny.round.quiz:activeDescriptionAction";
  }

  /**
   * Renders the content for the round description panel.
   *
   * @param Round $round
   *
   * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
   * @since   2011-11-03
   * @version 2011-11-03
   *
   * @todo out: submission checks
   *
   * @Template()
   */
  public function activeDescriptionAction($round){
    $user = $this->container->get('security.context')->getToken()->getUser();
    $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:Submission');
    $submissions = $sr->getByUserAndRound($user, $round);
    $notFinalized = true;
    $submitted =false;
    foreach($submissions as $submission){
      $submitted = true;
      if($submission->getFinalized()){
        $notFinalized = false;
      }
    }

    return array('round' => $round->getRound(), 'spec' => json_decode($round->getRound()->getConfig()), 'user' => $user, 'finalized' => !$notFinalized, 'submitted' => $submitted );
  }

  /**
   * Returns the links for participating in the section.
   *
   * @param int $term
   * @param Section $selection
   * @param Round $round
   *
   * @author Zsolt Parragi <zsolt.parragi@cancellar.hu>
   * @since   2011-11-03
   * @version 2011-11-03
   */
  public function getRoundLinks($term, $section, $round){
    $methods = array();

    $user = $this->container->get('security.context')->getToken()->getUser();
    if(!$user || !is_object($user)){
      return array();
    }

    $found = false;
    foreach($user->getSections() as $userSec){
      if($userSec->getId() == $section->getId()){
        $found = true;
      }
    }
    if(!$found) return array();

    $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyBundle:Submission');

    $now = new \DateTime();
    $ended = $round->getStop() < $now;
    $started = $round->getStart() < $now;

    $config = json_decode($round->getConfig());

    if(!$started) return array();

    $submissions = $sr->getByUserAndRound($user, $round);
    $notFinalized = true;
    $submitted =false;
    foreach($submissions as $submission){
      $submitted = true;
      if($submission->getFinalized()){
        $notFinalized = false;
      }
    }

    if(!$ended && $notFinalized){
      $methods[$config->name.' kitöltése'] = $this->container->get('router')->generate('competition_round_quiz_write', array( 'term' => $term, 'sectionSlug' => $section->getPage()->getSlug(), 'roundSlug' => $round->getPage()->getSlug() ));
    }

    return $methods;
  }

}
