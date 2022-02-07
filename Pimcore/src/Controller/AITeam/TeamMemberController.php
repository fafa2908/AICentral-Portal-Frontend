<?php

namespace App\Controller\AITeam;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use \Pimcore\Model\DataObject;

class TeamMemberController extends FrontendController
{
    /**
     * @Template()
     * @Route("/AITeam/TeamMember", name="TeamMemberPage")
     * @Route("/AITeam", name="AITeamPage")
     */    
    public function defaultAction(Request $request){
        $employeeList = new DataObject\Employee\Listing();
        $employeeList->setOrderKey('Position');
        // $employeeList 
        
        foreach($employeeList as $employee){
            
            $employee->setPosition(substr($employee->getPosition(), strpos($employee->getPosition(), "_") + 1));  
        }
        
        return $this->render('AITeam/TeamMember.html.twig', [
            "employeeList" => $employeeList
        ]);
    }
    
    

}
