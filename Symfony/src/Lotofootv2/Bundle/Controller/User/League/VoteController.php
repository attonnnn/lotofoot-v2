<?php

namespace Lotofootv2\Bundle\Controller\User\League;

use Lotofootv2\Bundle\Entity\LeagueVote;

use Lotofootv2\Bundle\Entity\LeagueMatch;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use \DateTime;

class VoteController extends Controller
{
	/**
     * @Route("/league/vote", name="_league_vote")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
		$day = $this->get('league_service')->getOpenedLeagueDay();
		
		$accountId = $this->getUser()->getId();
		
		if($day != null){
			$closed = ($day->getDeadline() < new DateTime());
			
			$matches = $this->get('league_service')->getLeagueDayMatches($day->getId());
			$votes = $this->get('league_service')->getLeagueDayVotes($day->getId(), $accountId);
			
			for($i=0;$i<count($votes);$i++){
				$request->request->set('score_'.$votes[$i]->getLeagueMatchId(), $votes[$i]->getScore());
				$request->request->set('result_'.$votes[$i]->getLeagueMatchId(), $votes[$i]->getResult());
			}
			
			return $this->render('Lotofootv2Bundle:User\League:vote.html.twig', 
				array(
				'leagueDay' => $day,
				'closed' => $closed,
				'matches' => $matches
				)
			);
		}
		
		return $this->render('Lotofootv2Bundle:User\League:vote.html.twig', array('leagueDay' => $day));
    }
    
    /**
     * @Route("/league/vote", name="_league_vote_post")
     * @Method("POST")
     */
    public function voteAction(Request $request)
    {	$day = $this->get('league_service')->getOpenedLeagueDay();
    

		$closed = ($day->getDeadline() < new DateTime());			
		$matches = $this->get('league_service')->getLeagueDayMatches($day->getId());
    
		$votes = array();
		
		for($i=0;$i<count($matches);$i++){
			$vote = new LeagueVote();	
			$vote->setDate(new DateTime());
			$vote->setAccountId($this->getUser()->getId());
			$vote->setLeagueMatchId($matches[$i]->getId());
			$vote->setResult($request->request->get('result_'.$matches[$i]->getId()));
			$vote->setScore($request->request->get('score_'.$matches[$i]->getId()));
			
			array_push($votes, $vote);
		}
		
		$this->get('league_service')->voteLeagueDay($votes);
		
    	return $this->redirect($this->generateUrl('_league_vote'));
    }
}
