<?php

namespace Lotofootv2\Bundle\Controller\Admin;

use Sabre\VObject\Component\VCalendar;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use \DateTime;

use Lotofootv2\Bundle\Entity\LeagueMatch;
use Lotofootv2\Bundle\LotofootUtil;

class LeagueDayController extends Controller
{
	/**
     * @Route("/admin/league/day", name="_admin_league_day")
     */
    public function indexAction()
    {	
    	if($this->get('league_service')->getRunningLeague() == null){
    		return $this->redirect($this->generateUrl('_admin_league'));
    	}
    	
		$leagueDay = $this->get('league_service')->getNotCorrectedLeagueDay();
		
		$closed = true;
		$matches = null;
		$hasNotVoted = null;
		$hasVoted = null;
		
		if($leagueDay != null){
			$closed = ($leagueDay->getDeadline() < new DateTime());
			$matches = $this->get('league_service')->getLeagueDayMatches($leagueDay->getId());
			
			if(!$closed){
				$hasNotVoted = $this->get('league_service')->getHasNotVoted($leagueDay->getId());
				$hasVoted = $this->get('league_service')->getHasVoted($leagueDay->getId());
			}
		}
		
    	return $this->render('Lotofootv2Bundle:Admin:league_day.html.twig', 
    		array('leagueDay' => $leagueDay, 'closed' => $closed, 'matches' => $matches, 'hasNotVoted' => $hasNotVoted, 'hasVoted' => $hasVoted)
    	);
    }

    /**
     * @Route("/admin/league/day/update", name="_admin_league_day_update")
     */
    public function updateAction(Request $request)
    {

        $leagueDay = $this->get('league_service')->getNotCorrectedLeagueDay();
        $matches = $this->get('league_service')->getLeagueDayMatches($leagueDay->getId());

        for($i=0;$i<count($matches);$i++){
            $match = $matches[$i];

            $deadline = DateTime::createFromFormat("d/m/Y H:i", $request->request->get('deadline_'.$match->getId()).' '.$request->request->get('deadlineh_'.$match->getId()));
            $dl_errors = DateTime::getLastErrors();
            if (!empty($dl_errors['warning_count'])) {
                return $this->render('Lotofootv2Bundle:Admin:league_day.html.twig', array('error' => 'Date incorrecte'));
            }

            if($deadline == null){
                return $this->render('Lotofootv2Bundle:Admin:league_day.html.twig', array('error' => 'Date incorrecte'));
            }

            $match->setDeadline($deadline);
        }

        $this->get('league_service')->updateLeagueDay($leagueDay, $matches);

        return $this->redirect($this->generateUrl('_admin_league_day'));
    }
    
	/**
     * @Route("/admin/league/day/new", name="_admin_league_day_new")
     */
    public function newAction(Request $request)
    {	
    	
    	$matches = array();
    	
    	$isbonus = false;
    	
    	$firstdeadline = null;
    	$lastdeadline = null;
    	
		for($i=1;$i<=13;$i++){
			$error = null;
			
			$home = $request->request->get('home_'.$i);
			$visitor = $request->request->get('visitor_'.$i);
			$bonus = $request->request->get('bonus_'.$i);

			$deadline = DateTime::createFromFormat("d/m/Y H:i", $request->request->get('deadline_'.$i).' '.$request->request->get('deadlineh_'.$i));
	        $dl_errors = DateTime::getLastErrors();
	        if (!empty($dl_errors['warning_count'])) {
	            return $this->render('Lotofootv2Bundle:Admin:league_day.html.twig', array('error' => 'Date incorrecte'));
	        }
	        
	        if($deadline == null){
	            return $this->render('Lotofootv2Bundle:Admin:league_day.html.twig', array('error' => 'Date incorrecte'));
	        }
	        
	        if($deadline < new DateTime()){
	            return $this->render('Lotofootv2Bundle:Admin:league_day.html.twig', array('error' => 'Date dans le passé !!'));
	        }
			
			if($home == null || $home == ''){
				$error = 'L\'équipe Domicile '.$i.' n\'est pas remplie';
			}
			if($visitor == null || $visitor == ''){
				$error = 'L\'équipe Extérieur '.$i.' n\'est pas remplie';
			}
			
			if($bonus != null && $bonus == 'on'){
				$isbonus = true;
			}
			
			if($error != null){
				return $this->render('Lotofootv2Bundle:Admin:league_day.html.twig', array('error' => $error));
			}
			
			$match = new LeagueMatch();
			$match->setTeamHome($home);
			$match->setTeamVisitor($visitor);
			$match->setBonus($bonus == 'on');
			$match->setNumber($i);
			$match->setDeadline($deadline);
			
			array_push($matches, $match);
			
			//date du 1er match à parier
			if($firstdeadline == null || $deadline < $firstdeadline){
				$firstdeadline = $deadline;
			}
		//date du 1er match à parier
            if($lastdeadline == null || $deadline > $lastdeadline){
                $lastdeadline = $deadline;
            }
		}
    	
	    $dateSort = function($a, $b) {
		    return $a->getDeadline()->getTimestamp() - $b->getDeadline()->getTimestamp();
		};
		
		usort($matches, $dateSort);
		
		//reorder matches
		for($j=0;$j<count($matches);$j++){
			$matches[$j]->setNumber($j+1);
		}
		
		$this->get('league_service')->newLeagueDay($matches, $lastdeadline);
		
		$this->mailNewDay($firstdeadline);
		
    	return $this->redirect($this->generateUrl('_admin_league_day'));
    }
    
	private function mailNewDay($deadline)
    {
    	$from = $this->container->getParameter('mailer_from');
    	
    	$accounts = $this->get('league_service')->getRunningLeagueAccounts();
    	
    	$email = function($a)
		{
		    return $a->getEmail();
		};
		
		//$arr = array('attonnnn@gmail.com', 'tom.lann10@gmail.com');//test purpose
		$arr = array_map($email, $accounts);
    	
    	$message = \Swift_Message::newInstance()
	        ->setSubject('[Lotofoot] Nouvelle journée')
	        ->setFrom($from)
	        ->setTo($arr)
	        ->setBody($this->renderView('Lotofootv2Bundle:mails:new_league_day.txt.twig', 
	        	array('deadline' => $deadline)));
    	
	    //Create calendar invite with Sabre VOject lib
        $cal = new VCalendar();
         
        //Create a meeting invite that lasts 2 hours on the same day
        $vevent = $cal->add ( 'VEVENT', array(
        'summary' => 'Journée Lotofoot',
        'location' => 'http://www.topich.fr/lotofoot/',
        'dtstart' => $deadline,
        'dtend' => $deadline,
        'method' => 'PUBLISH'//,
        //'organizer' => 'CN=LotoFoot Team:mailto:'.$from//ne marche pas
        ) );
         
        //Make ical
        $data = $cal->serialize ();
         
        $filename = "lotofoot.ics";
         
        //Attach the calendar invite to the mail
        $attachment = \Swift_Attachment::newInstance ()
        ->setFilename ( $filename )
        ->setContentType ( 'text/calendar' )
        ->setBody ( $data );
        
        $message->attach ( $attachment );
        
	        	
	    $this->get('mailer')->send($message);
    }
    
	/**
     * @Route("/admin/league/day/mark", name="_admin_league_day_mark")
     */
    public function markAction(Request $request)
    {	
    	$leagueDay = $this->get('league_service')->getNotCorrectedLeagueDay();
    	
    	if($leagueDay == null){
    		return $this->redirect($this->generateUrl('_admin_league'));
    	}
    	
    	$matches = $this->get('league_service')->getLeagueDayMatches($leagueDay->getId());
    	
    	for($i=1;$i<=13;$i++){
    		
    		if($request->request->get('score_'.$matches[$i-1]->getId()) == 'X-X'){
    			continue;
    		}
    		
    		$error = null;
    		if(! LotofootUtil::validScore($request->request->get('score_'.$matches[$i-1]->getId()))){
    			return $this->render('Lotofootv2Bundle:Admin:league_day.html.twig', 
    			array('leagueDay' => $leagueDay, 'closed' => true, 'matches' => $matches, 'error' => 'Score incorrect pour le match : '.$i));
    		}
    	}
    	
	    for($i=1;$i<=13;$i++){	
    		$score = LotofootUtil::clearSpaces($request->request->get('score_'.$matches[$i-1]->getId()));
    		$matches[$i-1]->setScore($score);
    		
    		$result = 'X';
    		
    		if($score == 'X-X'){
    			$result = 'X';
    		}else{
    			$score = preg_split("/-/", $score);
                $result = (intval($score[0]) > intval($score[1]))? '1' :
                    (intval($score[0]) < intval($score[1]) ? '2' : 'N');    
    		}
    		
    		$matches[$i-1]->setResult($result);
    	}

    	$this->get('league_service')->processLeagueDay($matches);

        $this->mailPunchliner($leagueDay);

    	return $this->redirect($this->generateUrl('_admin_league'));
    }


    private function mailPunchliner($leagueDay)
    {
        if($leagueDay == null){
            return ;
        }

        $histories = $this->get('league_service')->getLeagueDayHistories($leagueDay->getId());

        if($histories == null || count($histories)==0){
            return ;
        }

        $punchliner = $this->get('account_service')->findById($histories[0]->getAccountId());

        $from = $this->container->getParameter('mailer_from');

        $message = \Swift_Message::newInstance()
            ->setSubject('[Lotofoot] MVP journée '.$leagueDay->getNumber().'!')
            ->setFrom($from)
            ->setTo($punchliner->getEmail())
            ->setBody($this->renderView('Lotofootv2Bundle:mails:punchliner.txt.twig'));

        $this->get('mailer')->send($message);
    }

    /**
     * @Route("/admin/league/day/test", name="_admin_league_day_test")
     */
    public function testRewardAction(Request $request){
    	$this->get('league_service')->test();
    	
    	return $this->redirect($this->generateUrl('_admin_league'));
    }
}
