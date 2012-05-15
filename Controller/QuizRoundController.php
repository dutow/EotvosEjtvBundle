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

namespace Eotvos\EjtvBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Eotvos\VersenyrBundle\Entity\Submission;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\HttpFoundation\Response;


/**
 * Controller for rounds requiring a single file upload.
 *
 * This class is used as a service, indetified by "eotvos_verseny.round.upload"
 *
 */
class QuizRoundController extends ContainerAware
{

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
     * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/quiz/submit", name = "competition_round_quiz_submit", defaults = { "_format" = "json" } )
     */
    public function submitAction($term, $sectionSlug, $roundSlug)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if(!$this->container->get('security.context')->isGranted('ROLE_USER')){
            return new Response(json_encode(array('success' => false)));
        }

        $tpRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyrBundle:TextPage');

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

        $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyrBundle:Submission');
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
     * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/quiz/read/{uid}", name = "competition_round_quiz_reader" )
     * @Template()
     */
    public function readAction($term, $sectionSlug, $roundSlug, $uid)
    {

        $uRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyrBundle:User');
        $writer = $uRep->findOneById($uid);

        $tpRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyrBundle:TextPage');

        $dEm = $this->container->get('doctrine')->getEntityManager();

        $roundRec = $tpRep->getForTermWithSlug($term, $roundSlug);
        if(!$roundRec){
            return $this->render('EotvosVersenyrBundle::error.twig.html', array(
                'code' => 404,
            ));
        }

        $sectionRec = $tpRep->getForTermWithSlug($term, $sectionSlug);
        if(!$sectionRec){
            return $this->render('EotvosVersenyrBundle::error.twig.html', array(
                'code' => 404,
            ));
        }

        $config = json_decode($roundRec->getRound()->getConfig());

        if(!is_object($config)){
            return $this->render('EotvosVersenyrBundle::error.twig.html', array(
                'code' => 500,
            ));
        }

        $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyrBundle:Submission');
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
     * @Route("/{term}/szekcio/{sectionSlug}/fordulo/{roundSlug}/quiz/write", name = "competition_round_quiz_write" )
     * @Template()
     */
    public function quizAction($term, $sectionSlug, $roundSlug)
    {

        $user = $this->container->get('security.context')->getToken()->getUser();
        if(!$this->container->get('security.context')->isGranted('ROLE_USER')){
            throw new AccessDeniedException('Az oldal eléréséhez be kell jelentkezned!');
        }

        $tpRep = $this->container->get('doctrine')->getRepository('\EotvosVersenyrBundle:TextPage');

        $dEm = $this->container->get('doctrine')->getEntityManager();

        $roundRec = $tpRep->getForTermWithSlug($term, $roundSlug);
        if(!$roundRec){
            return $this->render('EotvosVersenyrBundle::error.twig.html', array(
                'code' => 404,
            ));
        }

        $sectionRec = $tpRep->getForTermWithSlug($term, $sectionSlug);
        if(!$sectionRec){
            return $this->render('EotvosVersenyrBundle::error.twig.html', array(
                'code' => 404,
            ));
        }

        $config = json_decode($roundRec->getRound()->getConfig());

        if(!is_object($config)){
            return $this->render('EotvosVersenyrBundle::error.twig.html', array(
                'code' => 500,
            ));
        }

        $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyrBundle:Submission');
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
            return $this->render('EotvosEjtvBundle:QuizRound:lejartido.html.twig', array( 'section' => $sectionRec->getSection() ));
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
     * Renders the content for the round description panel.
     *
     * @param Round $round
     *
     * @Template()
     */
    public function activeDescriptionAction($round){
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sr = $this->container->get('doctrine')->getRepository('\EotvosVersenyrBundle:Submission');
        $submissions = $sr->getByUserAndRound($user, $round);
        $notFinalized = true;
        $submitted =false;
        foreach($submissions as $submission){
            $submitted = true;
            if($submission->getFinalized()){
                $notFinalized = false;
            }
        }

        return array(
            'round' => $round->getRound(),
            'spec' => json_decode($round->getRound()->getConfig()),
            'user' => $user,
            'finalized' => !$notFinalized,
            'submitted' => $submitted );
    }

}
