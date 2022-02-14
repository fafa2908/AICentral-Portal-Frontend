<?php

namespace App\Controller\UserAuth;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use \Pimcore\Model\DataObject;
use Pimcore\Model\User;
use Pimcore\Tool\Authentication;
use Pimcore\Tool\Session;

class UserAuthenticationController extends FrontendController
{
    
    protected function findUser(String $username){
        $user = User::getByName($username);
        return $user;
    }
    
    /**
     * @Template()
     * @Route("/login", name="loginPage")
     */    
    public function defaultAction(AuthenticationUtils $authenticationUtils){
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        echo $lastUsername;
        return $this->render('UserAuth/UserAuthentication.html.twig', [
            "last_username" => $lastUsername,
            "error" => $error
            
        ]);
    }

    /**
     * @Route("/logout", name="logoutPage", methods={"GET"})
     */
    public function logoutAction()
    {
        // this route will never be matched, but will be handled by the logout handler
    }


}

