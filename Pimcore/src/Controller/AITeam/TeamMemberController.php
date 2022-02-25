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
        $employeeListing = new DataObject\Employee\Listing();
        // $employeeList->setOrderKey('Position');
        
        $activeEmployeeList = [];
        $employeeList=[];
        foreach($employeeListing as $employee){
            array_push($employeeList,$employee);
            $user = $this->getPimcoreUser($employee->getStaff_ID());
            if ($user->getActive()){
                array_push($activeEmployeeList,$employee);
            }
        }
        
        usort($employeeList, function($a,$b){
            return strcmp($a->getPosition()->getPositionRank(),$b->getPosition()->getPositionRank());
        });
        
        usort($activeEmployeeList, function($a,$b){
            return strcmp($a->getPosition()->getPositionRank(),$b->getPosition()->getPositionRank());
        });
        
        return $this->render('AITeam/TeamMember.html.twig', [
            "employeeList" => $employeeList,
            "activeEmployeeList" => $activeEmployeeList,
        ]);
    }
    
    

}

