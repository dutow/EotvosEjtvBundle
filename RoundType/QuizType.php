<?php

namespace Eotvos\EjtvBundle\RoundType;

/**
 * Basic type for offline finals
 * 
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class QuizType
{
    /**
     * Returns the list of the links displayed on the right.
     *
     * @return array of links (url => display name)
     */
    public function getRoundLinks($round)
    {
        $methods = array();

        $user = $this->container->get('security.context')->getToken()->getUser();
        if(!$user || !is_object($user)){
            return array();
        }

        $term = $round->getTerm()->getName();
        $section = $round->getSection();

        $sr = $this->container->get('doctrine')->getRepository('EotvosVersenyrBundle:Submission');

        $config = json_decode($round->getConfig());

        $submissions = $sr->getByUserAndRound($user, $round);
        $notFinalized = true;
        $submitted =false;
        foreach($submissions as $submission){
            $submitted = true;
            if($submission->getFinalized()){
                $notFinalized = false;
            }
        }

        if($notFinalized){
            $methods[$config->name.' kitöltése'] = $this->container->get('router')->generate('competition_round_quiz_write', array( 'term' => $term, 'sectionSlug' => $section->getPage()->getSlug(), 'roundSlug' => $round->getPage()->getSlug() ));
        }

        return $methods;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Returns the name of the action added to section boxes.
     * 
     * @return string action name
     */
    public function getDescriptionAction()
    {
        return 'eotvos.versenyr.round.quiz:activeDescriptionAction';
    }

    /**
     * Returns an URL for viewing a specific submission
     *
     * @param Submission $submission object
     * 
     * @return string URL
     */
    public function getAdminUrlForSubmission($submission)
    {
        return null;
    }

    /**
     * Returns an URL for configuring a given round
     *
     * @param Round $round object
     * 
     * @return string URL
     */
    public function getConfigurationUrl($round)
    {
        //return 'round_quiz_configure';
        return null;
    }

    /**
     * Reorders the given array of [ user, [ submissions ], nullpoints ] into a descending list.
     * 
     * @param mixed $standing unordered user list
     * 
     * @return array ordered standing
     */
    public function orderStanding($standing)
    {
        foreach ($standing as $k => $v) {
            $sum = 0;
            foreach ($v[1] as $subm) {
                $sum += $subm->getPoints();
            }

            $standing[$k][2] = $sum;
        }
        $rs = $standing;

        uasort($rs, function($a,$b) {
            return (($a[2]==$b[2]) ? 0 : ($a[2] < $b[2] ? -1 : 1));
        });

        return $rs;
    }

    /**
     * Returns a user friendly name of the type
     * 
     * @return string
     */
    public function getDisplayName()
    {
        return 'QuizType';
    }

}

