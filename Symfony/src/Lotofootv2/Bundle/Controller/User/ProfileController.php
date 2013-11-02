<?php

namespace Lotofootv2\Bundle\Controller\User;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ProfileController extends Controller
{
	/**
     * @Route("/profile", name="_profile")
     */
    public function indexAction(Request $request)
    {    	
    	return $this->render('Lotofootv2Bundle:User:profile.html.twig', array('u' => $request->query->has('u')));
    }
    
	/**
     * @Route("/profile/mail", name="_profile_mail")
     */
    public function changeMail(Request $request)
    {
    	$mail = $request->request->get('mail');
    	
   		if($mail == null || trim($mail) == ''){
    		$error = "Email obligatoire";
    		return $this->render('Lotofootv2Bundle:User:profile.html.twig', array('error' => $error));
    	}
    	
    	$this->get('account_service')->updateMail($this->getUser(), $mail);
    	$this->getUser()->setEmail($mail);
    	
    	return $this->redirect($this->generateUrl('_profile', array('u' => 1)));
    }
}