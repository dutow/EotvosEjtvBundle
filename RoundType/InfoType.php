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
class InfoType
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
        if (!$user || !is_object($user)) {
            return array();
        }

        $term = $round->getTerm()->getName();
        $section = $round->getSection();

        $sr = $this->container->get('doctrine')->getRepository('EotvosVersenyrBundle:Submission');

        $now = new \DateTime();
        $ended = $round->getStop() < $now;
        $started = $round->getStart() < $now;

        $config = json_decode($round->getConfig());

        $methods['Verseny'] = $this->container->get('router')->generate('competition_round_infocontest_index', array( 'term' => $term, 'sectionSlug' => $section->getPage()->getSlug(), 'roundSlug' => $round->getPage()->getSlug() ));

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
        return 'eotvos.versenyr.round.info:activeDescriptionAction';
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
     * Returns an URL for configuring a given infocontest
     *
     * @param infocontest $infocontest object
     * 
     * @return string URL
     */
    public function getConfigurationUrl($infocontest)
    {
        //return 'infocontest_quiz_configure';
        return null;
    }

    /**
     * Reorders the given array of [ user, [ submissions ], nullpoints ] into a descending list.
     * 
     * @param mixed $standing unordered user list
     * 
     * @return array ordered standing
     */
    public function orderStanding($standing, $round)
    {
        $config = json_decode($round->getConfig(), true);

        foreach ($config["tasks"] as $task) {
            if ($task["type"]=="scaled") { // this is a scaled problem => normalzie it!

                // A. find max
                $max = 0;

                foreach ($standing as $k => $v) {
                    foreach ($v[1] as $s) {
                        $f = $s->getCategory();
                        if ($f[0]==$task['short_name'] && $s->getPoints() > $max) {

                        }
                    }
                    $standing[$k][3] = array();
                }

                // B. modify values
                foreach ($standing as $k => $v) {
                    foreach ($v[1] as $s) {
                        $f = $s->getCategory();
                        if ($f[0]==$task['short_name']) {
                            $p  = $s->getPoints();
                            if ($max != 0) {
                                $tmp = 0;
                                if ($max > 0) {
                                    $tmp += (int) floor(10.0*( (float) $p / (float) $max ));
                                } else {
                                    $tmp += (int) floor(10.0*( (float) $p / (float) $max ));
                                }
                                if ($p <= 0) {
                                    $tmp = 1;
                                }
                                $standing[$k][2] += $tmp;
                                $standing[$k][3][$f] = $tmp;
                            }
                        }
                    }
                }
            }
        }

        // C. from now on, just sum


        foreach ($standing as $k => $v) {

            $sum = $v[2];
            foreach ($v[1] as $subm) {
                if (!isset($v[3][$subm->getCategory()])) {
                    $sum += $subm->getPoints();
                    $standing[$k][3][$subm->getCategory()] = $subm->getPoints();
                }
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
        return 'InfoType';
    }

}

