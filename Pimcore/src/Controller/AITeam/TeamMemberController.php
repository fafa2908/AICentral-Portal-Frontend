<?php

namespace App\Controller\AITeam;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use \Pimcore\Model\DataObject;
use \Pimcore\Model\User; 

class TeamMemberController extends FrontendController
{
    private function getPimcoreUser(string $Username){
         $allUsers = new User\Listing();
         
         foreach ($allUsers as $user){
             if ($user->getUsername() == $Username ){
                 return $user;
             }
         }
    }
    
    /**
     * @Route("/AITeam/TeamMember", name="TeamMemberPage")
     * @Route("/AITeam", name="AITeamPage")
     */    
    public function defaultAction(Request $request){
        $employeeList = new DataObject\Employee\Listing();
        $employeeList->setOrderKey('Position');
        // $employeeList 
        
        $activeEmployeeList = [];
        
        foreach($employeeList as $employee){
            $employee->setPosition(substr($employee->getPosition(), strpos($employee->getPosition(), "_") + 1)); 
            $user = $this->getPimcoreUser($employee->getStaff_ID());
            if ($user->getActive()){
                array_push($activeEmployeeList,$employee);
            }
        }
        
        return $this->render('AITeam/TeamMember.html.twig', [
            "employeeList" => $employeeList,
            "activeEmployeeList" => $activeEmployeeList,
        ]);
    }
    
    

}

