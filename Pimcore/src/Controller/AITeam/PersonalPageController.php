<?php

namespace App\Controller\AITeam;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use \Pimcore\Model\DataObject;
use Carbon\Carbon;

class PersonalPageController extends FrontendController
{
    /**
     * @Template()
     * @Route("/PersonalPage/{obj_ID}", name="PersonalPage_Root")
     * @Route("/AITeam/PersonalPage/{obj_ID}", name="AITeamPersonalPage")
     */    
    public function defaultAction(Request $request, string $obj_ID = "106"){
        // GET Employee Object 
        $employeeObj = DataObject\Employee::getById($obj_ID);
        $employeeObj->setPosition(substr($employeeObj->getPosition(), strpos($employeeObj->getPosition(), "_") + 1));

        // GET Enrollment List for the Employee Object
        $enrollAllList = new DataObject\Enrollment\Listing();
        $enrollList = $enrollAllList->filterByEmpEnroll($employeeObj);
        
        // GET WorkExp List for the Employee Object
        $workExpAllList = new DataObject\WorkExperience\Listing();
        $workExpList = $workExpAllList->filterByEmpWork($employeeObj);
        
        // GET SkillProficency List for the Employee Object
        $skillProfAllList = new DataObject\SkillProficiency\Listing();
        $skillProfList = $skillProfAllList->filterByEmpSkill($employeeObj);
        
        
        $enrollList ->setOrderKey('EnrolEndDate');
        $enrollList ->setOrder('desc');
        
        $workExpList -> setOrderKey('WorkEndDate');
        $workExpList -> setOrder('desc');
        
        $skillProfList -> setOrderKey('SkillLevel');
        $skillProfList -> setOrder('desc');
        
        $employeeObj->getBirthday()->modify('+8 hours');
        $employeeObj->getJoin_date()->modify('+8 hours');
        
        foreach($enrollList as $item){
            $item->getEnrolStartDate()->modify('+8 hours');
            $item->getEnrolEndDate()->modify('+8 hours');
        }
        
        foreach($workExpList as $item){
            $item->getWorkStartDate()->modify('+8 hours');
            $item->getWorkEndDate()->modify('+8 hours');
        }
        
        
        $educationAllList = new DataObject\Education\Listing();
        $companyAllList = new DataObject\Company\Listing();
        
        // $workExpAllList = new DataObject\WorkExperience\Listing();
        // $workExpList = $workExpAllList->filterByEmpWork($employeeObj);
        
        return $this->render('AITeam/PersonalPage.html.twig', [
           "employee" => $employeeObj,
           "enrollList" => $enrollList,
           "workExpList" => $workExpList,
           "skillProfList" => $skillProfList,
           "educationAllList" => $educationAllList, 
           "companyAllList" => $companyAllList, 
        ]);
    }
    
    /**
     * @Route("/AITeam/PersonalPage", name="PersonalPageFail")
     */    
     public function fallback(Request $request){
         return $this->render('default/default.html.twig');
     }
     
        /**
     * @Route("/AITeam/PersonalPage/forms/Company/{obj_ID}", name="CompanyFormPost")
     * @Route("/PersonalPage/forms/Company/{obj_ID}", name="CompanyFormPost_Root")
     */
     public function CompanyForm(Request $request, string $obj_ID = "106"){
        
        $companyObj = DataObject\Company::getById($_POST['CompanyObjectList']);
        // echo $_POST['CompanyObjectList'];
        // echo $_POST['CompanyName'];
        // echo $_POST['CompanyAddress'];
        // echo $_POST['CompanyPhone'];
        // echo $_POST['CompanyDescription'];
        
        // echo json_encode($companyObj);
        if ($_POST['buttonAction'] == "Save" and $_SERVER["REQUEST_METHOD"] == "POST"){
            if($_POST['CompanyObjectList'] == "Create New Company Object"){
                // Create new Object
                $newObject = new DataObject\Company(); 
                $newObject->setKey(\Pimcore\Model\Element\Service::getValidKey($_POST['CompanyName'], 'object'));
                $newObject->setParentId(65);            //root of folder
                $newObject->setCompany($_POST['CompanyName']);
                $newObject->setAddress($_POST['CompanyAddress']);
                $newObject->setDescription($_POST['CompanyDescription']);
                $newObject->setPhone($_POST['CompanyPhone']);
                $newObject->setPublished(true);
                $newObject->save();
            }
            else{
                // Update new Object
                $companyObj->setCompany($_POST['CompanyName']);
                $companyObj->setAddress($_POST['CompanyAddress']);
                $companyObj->setDescription($_POST['CompanyDescription']);
                $companyObj->setPhone($_POST['CompanyPhone']);
                $companyObj->setPublished(true);
                $companyObj->save();
            }
        }
        else if ($_POST['buttonAction'] == "Delete" and $_SERVER["REQUEST_METHOD"] == "POST"){
                $workExperienceList = new DataObject\WorkExperience\Listing();
                $utilisedCompany = [];
                foreach($workExperienceList as $work){
                    array_push($utilisedCompany, $work->getCompanyWork()->geto_Id());
                }
                
                if(! in_array($_POST['CompanyObjectList'],$utilisedCompany)){
                    $companyObj->delete();
                }
                else{
                    echo "UNABLE TO BE DELETED <br> THIS OBJECT USED BY OTHER PEOPLE";
                }
            }
        
        return $this->redirectToRoute('AITeamPersonalPage',['obj_ID'=>$obj_ID],301);
        

     }
     
       /**
     * @Route("/AITeam/PersonalPage/forms/WorkExperience/{obj_ID}", name="WorkExperienceFormPost")
     * @Route("/PersonalPage/forms/WorkExperience/{obj_ID}", name="WorkExperienceFormPost_Root")
     */
     public function WorkExperienceForm(Request $request, string $obj_ID = "106"){
         $employeeObj = DataObject\Employee::getById($obj_ID);
         $companyObj = DataObject\Company::getById($_POST['Work_CompanyObjectList']);
         $workExperienceObj = DataObject\WorkExperience::getById($_POST['WorkObjectList']);
         
        // echo $_POST['WorkObjectList'];
        // echo $_POST['workTitle'];
        // echo $_POST['workTypeList'];
        // echo $_POST['workStartDate'];
        // echo $_POST['workEndDate'];
        // echo $_POST['WorkDesc'];
        // echo $_POST['Work_CompanyObjectList'];
        
         if ($_POST['buttonAction'] == "Save" and $_SERVER["REQUEST_METHOD"] == "POST"){
             
             $workObjName = $employeeObj->getName() . " - " . $companyObj->getCompany();
             
             if($_POST['WorkObjectList'] == "Create New Work Experience Object"){
                // Create new Object
                $newObject = new DataObject\WorkExperience(); 
                $newObject->setKey(\Pimcore\Model\Element\Service::getValidKey($workObjName, 'object'));
                $newObject->setParentId(27);            //root of folder
                $newObject->setTitle($_POST['workTitle']);
                $newObject->setEmploymentType($_POST['workTypeList']);
                $newObject->setWorkStartDate(Carbon::parse($_POST['workStartDate']));
                $newObject->setWorkEndDate(Carbon::parse($_POST['workEndDate']));
                $newObject->setWorkDesc($_POST['WorkDesc']);
                $newObject->setEmpWork($employeeObj);
                $newObject->setCompanyWork($companyObj);
                $newObject->setPublished(true);
                $newObject->save();
             }
             else{
                // Update new Object
                $workExperienceObj->setTitle($_POST['workTitle']);
                $workExperienceObj->setEmploymentType($_POST['workTypeList']);
                $workExperienceObj->setWorkStartDate(Carbon::parse($_POST['workStartDate']));
                $workExperienceObj->setWorkEndDate(Carbon::parse($_POST['workEndDate']));
                $workExperienceObj->setWorkDesc($_POST['WorkDesc']);
                $workExperienceObj->setEmpWork($employeeObj);
                $workExperienceObj->setCompanyWork($companyObj);
                $workExperienceObj->setPublished(true);
                $workExperienceObj->save();
            }
         }
         else if ($_POST['buttonAction'] == "Delete" and $_SERVER["REQUEST_METHOD"] == "POST"){
                $workExperienceObj->delete();
            }
         return $this->redirectToRoute('AITeamPersonalPage',['obj_ID'=>$obj_ID],301);
     }
     
          /**
     * @Route("/AITeam/PersonalPage/forms/Education/{obj_ID}", name="EducationFormPost")
     * @Route("/PersonalPage/forms/Education/{obj_ID}", name="EducationFormPost_Root")
     */    
     public function EducationForm(Request $request, string $obj_ID = "106"){
         $object = DataObject\Education::getById($_POST['EducationObjectList']); 
         
         if ($_POST['buttonAction'] == "Save" and $_SERVER["REQUEST_METHOD"] == "POST"){
            // collect value of input field
            $EducationObjName = $_POST['InstitutionName'] . " - ". $_POST['StudyFieldName'];
            
            if($_POST['EducationObjectList'] == "Create New Institution Object"){
                // Create new Object
                $newObject = new DataObject\Education(); 
                $newObject->setKey(\Pimcore\Model\Element\Service::getValidKey($EducationObjName, 'object'));
                $newObject->setParentId(26);            //root of folder
                $newObject->setInstitution($_POST['InstitutionName']);
                $newObject->setCourseName($_POST['StudyFieldName']);
                $newObject->setEducationLevel($_POST['EducationLevelList']);
                $newObject->setEducationDesc($_POST['EducationDescription']);
                $newObject->setPublished(true);
                $newObject->save();
            }
            else{
                // Update new Object
                // $object = DataObject\Education::getById($_POST['EducationObjectList']); 
                $object->setInstitution($_POST['InstitutionName']);
                $object->setCourseName($_POST['StudyFieldName']);
                $object->setEducationLevel($_POST['EducationLevelList']);
                $object->setEducationDesc($_POST['EducationDescription']);
                $object->setPublished(true);
                $object->save();
            }
         }
         else if ($_POST['buttonAction'] == "Delete" and $_SERVER["REQUEST_METHOD"] == "POST"){
                $enrollmentList = new DataObject\Enrollment\Listing();
                $utilisedEnrollment = [];
                foreach($enrollmentList as $enrollment){
                    array_push($utilisedEnrollment, $enrollment->getEduEnroll()->geto_Id());
                }
                
                if(! in_array($_POST['EducationObjectList'],$utilisedEnrollment)){
                    $object->delete();
                }
                else{
                    echo "UNABLE TO BE DELETED <br> THIS OBJECT USED BY OTHER PEOPLE";
                }
            //  $object->delete();
         }
         
         return $this->redirectToRoute('AITeamPersonalPage',['obj_ID'=>$obj_ID],301);
     }
     
    /**
     * @Route("/AITeam/PersonalPage/forms/Enrollment/{obj_ID}", name="EnrollmentFormPost")
     * @Route("/PersonalPage/forms/Enrollment/{obj_ID}", name="EnrollmentFormPost_Root")
     */
     public function EnrollmentForm(Request $request, string $obj_ID = "106"){
        // echo ("hello");
        // echo $_POST['startDate'];
        // echo $_POST['endDate'];
        // echo $_POST['enroll_educationList'];
        // echo $_POST['EnrollmentObjectList'];
        
        $employeeObj = DataObject\Employee::getById($obj_ID);
        $educationObj = DataObject\Education::getById($_POST['enroll_educationList']);
        $enrollObj = DataObject\Enrollment::getById($_POST['EnrollmentObjectList']); 
        // echo json_encode($enrollObj);
        if ($_POST['buttonAction'] == "Save" and $_SERVER["REQUEST_METHOD"] == "POST"){
            
            $enrollObjName = $employeeObj->getName() . " - " . $educationObj->getCourseName();
            // echo $enrollObjName;
            if($_POST['EnrollmentObjectList'] == "Create New Enrollment Object"){
                // Create new Object
                $newObject = new DataObject\Enrollment(); 
                $newObject->setKey(\Pimcore\Model\Element\Service::getValidKey($enrollObjName, 'object'));
                $newObject->setParentId(72);            //root of folder
                $newObject->setEnrolStartDate(Carbon::parse($_POST['startDate']));
                $newObject->setEnrolEndDate(Carbon::parse($_POST['endDate']));
                $newObject->setEmpEnroll($employeeObj);
                $newObject->setEduEnroll($educationObj);
                $newObject->setPublished(true);
                $newObject->save();
            }
            else{
                // Update new Object
                $enrollObj->setEnrolStartDate(Carbon::parse($_POST['startDate']));
                $enrollObj->setEnrolEndDate(Carbon::parse($_POST['endDate']));
                $enrollObj->setEmpEnroll($employeeObj);
                $enrollObj->setEduEnroll($educationObj);
                $enrollObj->setPublished(true);
                $enrollObj->save();
            }
        }
        else if ($_POST['buttonAction'] == "Delete" and $_SERVER["REQUEST_METHOD"] == "POST"){
                $enrollObj->delete();
            }
        
        return $this->redirectToRoute('AITeamPersonalPage',['obj_ID'=>$obj_ID],301);
        
    }
    
}