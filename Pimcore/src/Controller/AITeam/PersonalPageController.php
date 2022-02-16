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
     * @Route("/AITeam/PersonalPage/{obj_ID}", name="AITeamPersonalPage")
     */    
    public function defaultAction(Request $request, string $obj_ID = "106"){
        $currentUser = \Pimcore\Tool\Admin::getCurrentUser();
        $isadmin = $currentUser ? $currentUser->isadmin(): false;
        
        // GET Employee Object 
        $employeeObj = DataObject\Employee::getById($obj_ID);
        $employeeObj->setPosition(substr($employeeObj->getPosition(), strpos($employeeObj->getPosition(), "_") + 1));

        // GET Enrollment List for the Employee Object
        $enrollAllList = new DataObject\Enrollment\Listing();
        $enrollList = $enrollAllList->filterByEmpEnroll($employeeObj);
        $enrollList ->setOrderKey('EnrolEndDate');
        $enrollList ->setOrder('desc');
        
        // GET WorkExp List for the Employee Object
        $workExpAllList = new DataObject\WorkExperience\Listing();
        $workExpList = $workExpAllList->filterByEmpWork($employeeObj);
        $workExpList -> setOrderKey('WorkEndDate');
        $workExpList -> setOrder('desc');
        
        // GET SkillProficency List for the Employee Object
        $skillProfAllList = new DataObject\SkillProficiency\Listing();
        $skillProfList = $skillProfAllList->filterByEmpSkill($employeeObj);
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
        $skillAllList = new DataObject\Skills\Listing();
        $skillTrainingAllList = new DataObject\SkillTraining\Listing();
        
        // $workExpAllList = new DataObject\WorkExperience\Listing();
        // $workExpList = $workExpAllList->filterByEmpWork($employeeObj);
        
        return $this->render('AITeam/PersonalPage.html.twig', [
             "isadmin" => $isadmin,
            "currentUser" => $currentUser, 
            "employee" => $employeeObj,
            "enrollList" => $enrollList,
            "workExpList" => $workExpList,
            "skillProfList" => $skillProfList,
            "educationAllList" => $educationAllList, 
            "companyAllList" => $companyAllList, 
            "skillAllList"=>$skillAllList,
            "skillTrainingAllList"=>$skillTrainingAllList,
        ]);
    }
    
    /**
     * @Route("/AITeam/PersonalPage", name="PersonalPageFail")
     */    
     public function fallback(Request $request){
         return $this->render('default/default.html.twig');
     }
     
    private function alert($message="Sorry, An Error Occured"){
        echo '<script language="javascript">';
        echo 'alert("Error Message: '.$message.'")';
        echo '</script>';
    }
    
    /**
     * @Route("/AITeam/PersonalPage/forms/Company/{obj_ID}", name="CompanyFormPost")
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
                    $this->alert("Company Object Unable to be Deleted (Used by Other user)");
                }
            }
        return $this->redirectToRoute('AITeamPersonalPage',['obj_ID'=>$obj_ID],301);
     }
     
       /**
     * @Route("/AITeam/PersonalPage/forms/WorkExperience/{obj_ID}", name="WorkExperienceFormPost")
     */
     public function WorkExperienceForm(Request $request, string $obj_ID = "106"){
         $employeeObj = DataObject\Employee::getById($obj_ID);
         $companyObj = DataObject\Company::getById($_POST['Work_CompanyObjectList']);
         $workExperienceObj = DataObject\WorkExperience::getById($_POST['WorkObjectList']);
         
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
    
    /**
     * @Route("/AITeam/PersonalPage/forms/SkillCategory/{obj_ID}", name="SkillCategoryFormPost")
     */
    public function SkillCategory(Request $request, string $obj_ID = "106"){
        // echo ("hello");
        // echo $_POST['inputSkillCategory'];
        // echo $_POST['SkillCategoryObjectList']; 
        $skillObj = DataObject\Skills::getById($_POST['SkillCategoryObjectList']); 
        
        if ($_POST['buttonAction'] == "Save" and $_SERVER["REQUEST_METHOD"] == "POST"){
            if($_POST['SkillCategoryObjectList'] == "Create New Skill Catagory"){
                // Create new Object
                $newObject = new DataObject\Skills(); 
                $newObject->setKey(\Pimcore\Model\Element\Service::getValidKey($_POST['inputSkillCategory'], 'object'));
                $newObject->setParentId(77);            //root of folder
                $newObject->setSkillCategory($_POST['inputSkillCategory']);
                $newObject->setPublished(true);
                $newObject->save();
            }
            else{
                // Update new Object
                $skillObj->setSkillCategory($_POST['inputSkillCategory']);
                $skillObj->setPublished(true);
                $skillObj->save();
            }
        }
        else if ($_POST['buttonAction'] == "Delete" and $_SERVER["REQUEST_METHOD"] == "POST"){
                $subSkillsList = new DataObject\SkillTraining\Listing();
                $utilisedSkills = [];
                foreach($subSkillsList as $skill){
                    array_push($utilisedSkills, $skill->getSkillsCategoryDetails()->geto_Id());
                }
                
                if(! in_array($_POST['SkillCategoryObjectList'],$utilisedSkills)){
                    $skillObj->delete();
                }
                else{
                    $this->alert("Skill Category Object unable to be deleted (Used by Other user)");
                }
            }
        return $this->redirectToRoute('AITeamPersonalPage',['obj_ID'=>$obj_ID],301);
    }
    
    /**
     * @Route("/AITeam/PersonalPage/forms/SkillSubCategory/{obj_ID}", name="SkillSubCategoryFormPost")
     */
    public function SubSkillCategory(Request $request, string $obj_ID = "106"){
        // echo ("hello");
        echo json_encode($_POST);
        // echo $_POST['subSkillCategoryObjectList']; 
        
        
        $skillObj = DataObject\SkillTraining::getById($_POST['skillObjectList']); 
        $skillCateObj = DataObject\Skills::getById($_POST['SkillCateObjectList']); 
        
        if ($_POST['buttonAction'] == "Save" and $_SERVER["REQUEST_METHOD"] == "POST"){
            if($_POST['skillObjectList'] == "Create New Skill"){
                // Create new Object
                $newObject = new DataObject\SkillTraining(); 
                $newObject->setKey(\Pimcore\Model\Element\Service::getValidKey($_POST['inputTrainingTitle'], 'object'));
                $newObject->setParentId(303);            //root of folder
                $newObject->setSkillSubCategory($_POST['inputSubCategory']);
                $newObject->setTrainingTitle($_POST['inputTrainingTitle']);
                $newObject->setExamName($_POST['inputExamName']);
                $newObject->setSkillsCategoryDetails($skillCateObj);
                $newObject->setPublished(true);
                $newObject->save();
            }
            else{
                // Update new Object
                $skillObj->setSkillSubCategory($_POST['inputSubCategory']);
                $skillObj->setTrainingTitle($_POST['inputTrainingTitle']);
                $skillObj->setExamName($_POST['inputExamName']);
                $skillObj->setSkillsCategoryDetails($skillCateObj);
                $skillObj->setPublished(true);
                $skillObj->save();
            }
        }
        else if ($_POST['buttonAction'] == "Delete" and $_SERVER["REQUEST_METHOD"] == "POST"){
                $profList = new DataObject\SkillProficiency\Listing();
                $utilisedSkills = [];
                foreach($profList as $prof){
                    array_push($utilisedSkills, $prof->getSkillDetails()->geto_Id());
                }
                
                if(! in_array($_POST['skillObjectList'],$utilisedSkills)){
                    $skillObj->delete();
                }
                else{
                    $this->alert("Skill Object unable to be deleted (Used by Other user)");
                }
                
            }
            
        return $this->redirectToRoute('AITeamPersonalPage',['obj_ID'=>$obj_ID],301);
    }
    
        /**
     * @Route("/AITeam/PersonalPage/forms/SkillProficiency/{obj_ID}", name="SkillProficiencyFormPost")
     */
    public function SkillProficiency(Request $request, string $obj_ID = "106"){
        // echo ("hello");
        echo json_encode($_POST);
        // echo $_POST['subSkillCategoryObjectList']; 
        
        $employeeObj = DataObject\Employee::getById($obj_ID);
        $profObj = DataObject\SkillProficiency::getById($_POST['proficiencyObjectList']); 
        $skillObj = DataObject\SkillTraining::getById($_POST['skillObject']); 
        
        $levelStatus = array_key_exists('proficiencyLevelList',$_POST) ? $_POST['proficiencyLevelList'] : "Level 0";
        if ($_POST['buttonAction'] == "Save" and $_SERVER["REQUEST_METHOD"] == "POST"){
            if($_POST['proficiencyObjectList'] == "Create New Proficiency"){
                // Create new Object
                $ObjName = $employeeObj->getName() . " - " . $skillObj->getTrainingTitle();
                
                $newObject = new DataObject\SkillProficiency(); 
                $newObject->setKey(\Pimcore\Model\Element\Service::getValidKey($ObjName, 'object'));
                $newObject->setParentId(74);            //root of folder
                $newObject->setStatus($_POST['proficiencyStatusList']);
                $newObject->setSkillLevel($levelStatus);
                $newObject->setSkillYear(Carbon::parse($_POST['proficiencyDate']));
                $newObject->setEmpSkill($employeeObj);
                $newObject->setSkillDetails($skillObj);
                $newObject->setPublished(true);
                $newObject->save();
            }
            else{
                // Update new Object
                $profObj->setStatus($_POST['proficiencyStatusList']);
                $profObj->setSkillLevel($levelStatus);
                $profObj->setSkillYear(Carbon::parse($_POST['proficiencyDate']));
                $profObj->setEmpSkill($employeeObj);
                $profObj->setSkillDetails($skillObj);
                $profObj->setPublished(true);
                $profObj->save();
            }
        }
        else if ($_POST['buttonAction'] == "Delete" and $_SERVER["REQUEST_METHOD"] == "POST"){
                $profObj->delete();
            }
            
        return $this->redirectToRoute('AITeamPersonalPage',['obj_ID'=>$obj_ID],301);
    }
    
    
    
}