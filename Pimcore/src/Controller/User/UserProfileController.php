<?php

namespace App\Controller\User;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use \Pimcore\Model\DataObject;
use \Pimcore\Model\Asset; 
use \Pimcore\Model\Asset\Image; 
use \Pimcore\Model\User; 
use \Pimcore\Tool\Authentication;
use Carbon\Carbon;

class UserProfileController extends FrontendController
{
    // EndPoint Documentation:  https://www.openproject.org/docs/api/endpoints/
    // OpenProject server IP
    private $openProjectURL = 'http://192.168.1.2:8081/';
    
    // Basic Auth (username:password/API Key) [OpenPorject Admin User]
    // "apikey: 9717809fbe46c8378ed4da240c730a745965d29477881c2fecf61b77eab8cf1b"
    // 
    private $token = '9717809fbe46c8378ed4da240c730a745965d29477881c2fecf61b77eab8cf1b';
    
    private function alert($message="Sorry, An Error Occured"){
        echo '<script language="javascript">';
        echo 'alert("Error Message: '.$message.'")';
        echo '</script>';
    }
    
    private function API_get(String $urlEndPoint){
        $url     = $this->openProjectURL.$urlEndPoint;
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        $arrHeader  = Array();
        $arrHeader[] = "Authorization: Basic ".base64_encode('apikey:'.$this->token);
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $arrHeader);
        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $resp = curl_exec($curl);
        curl_close($curl);
        
        // var_dump($resp);
        // return $resp ; 
        return json_decode($resp) ; 
    }
    
    private function API_post(String $urlEndPoint, String $json_file){
        $url_api     = $this->openProjectURL.$urlEndPoint;
        
        $arrHeader  = Array();
        $arrHeader[] = "Authorization: Basic ".base64_encode('apikey:'.$this->token);
        $arrHeader[] = "Content-Type: application/json";
        
        $curl = curl_init();
        // curl_setopt($curl, CURLOPT_PROXY, $proxy);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $arrHeader);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_file); 
        curl_setopt($curl, CURLOPT_URL, $url_api);
        
        $result = curl_exec($curl);
        if(curl_errno($curl)){ echo 'Request Error:' . curl_error($curl); }
        // echo json_encode(curl_getinfo($curl));
        // print_r($result);
        
        curl_close($curl);
    }
    
    private function API_patch(String $urlEndPoint, String $json_file){
        $url_api     = $this->openProjectURL.$urlEndPoint;
        
        $arrHeader  = Array();
        $arrHeader[] = "Authorization: Basic ".base64_encode('apikey:'.$this->token);
        $arrHeader[] = "Content-Type: application/json";
        
        $curl = curl_init();
        // curl_setopt($curl, CURLOPT_PROXY, $proxy);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $arrHeader);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
        // curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_file); 
        curl_setopt($curl, CURLOPT_URL, $url_api);
        
        $result = curl_exec($curl);
        if(curl_errno($curl)){ echo 'Request Error:' . curl_error($curl); }
        // echo json_encode(curl_getinfo($curl));
        // print_r($result);
        
        curl_close($curl);
    }
    
    private function API_delete(String $urlEndPoint){
        $url_api     = $this->openProjectURL.$urlEndPoint;
        
        $arrHeader  = Array();
        $arrHeader[] = "Authorization: Basic ".base64_encode('apikey:'.$this->token);
        $arrHeader[] = "Content-Type: application/json";
        
        $curl = curl_init();
        // curl_setopt($curl, CURLOPT_PROXY, $proxy);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $arrHeader);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_URL, $url_api);
        
        $result = curl_exec($curl);
        if(curl_errno($curl)){ echo 'Request Error:' . curl_error($curl); }
        // echo json_encode(curl_getinfo($curl));
        // print_r($result);
        
        curl_close($curl);
    }
    
    private function get_OP_user(String $userID){
        $OP_users = $this->API_get("/api/v3/users");
        
        foreach($OP_users->_embedded->elements as $user){
            if ($userID == $user->login){
                return $user->_links->self->href;
            }
        }
        return null; 
    }
    
    /**
     * @Route("/UserProfile", name="UserProfilePage")
     */    
    public function defaultAction(Request $request){
        $currentUser = \Pimcore\Tool\Admin::getCurrentUser();
        $isAdmin = $currentUser ? $currentUser->isAdmin() : false;
        
        $userID =  $currentUser->getName();
        $employee = DataObject\Employee::getByStaff_ID($currentUser->getName(),1);
        $employee->setPosition(substr($employee->getPosition(), strpos($employee->getPosition(), "_") + 1)); 
        
        // echo $userID; 
        // echo $employee->getName();
        
        $OP_user = $this->API_get($this->get_OP_user($userID));
        $employeeList = new DataObject\Employee\Listing();
        
        $allUsers = new User\Listing();
        $adminList=[];
        $inactiveList=[];
        foreach ($allUsers as $user){
            $temp_emp = DataObject\Employee::getByStaff_ID($user->getName(),1);
            if ($user->getAdmin() and $temp_emp){
                    array_push($adminList,$temp_emp);     
            }
            if ( !$user->getActive() and $temp_emp){
                    array_push($inactiveList,$temp_emp);     
            }
        }
        
        return $this->render('User/UserProfile.html.twig', [
            "isAdmin" => $isAdmin,
            "employee" => $employee,
            "OP_user" => $OP_user,
            "employeeList" => $employeeList,
            "adminList"=>$adminList,
            "inactiveList"=>$inactiveList,
        ]);
    }
    
    private function update_pimcore_employee(){
        $Employee_image_path = 25;
        $Employee_object_path = 28;
        $Employee_image_bin_path = 95;
        // echo $_POST['inputStaffID'];
        $employee = DataObject\Employee::getByStaff_ID($_POST['inputStaffID'],1); 
        
        $current_img = $employee->getProfile_Image(); 
        $previousPath =  $current_img->getFullPath();
        
        // echo json_encode($_FILES);
        // echo $previousPath;
        
        if ($_FILES['fileToUpload']['size'] != 0 && $_FILES['fileToUpload']['error'] == 0){
            $filename = $_FILES["fileToUpload"]["name"];
            $tempname = $_FILES["fileToUpload"]["tmp_name"];    
            $filename = str_replace(" ","_",$filename);
            $imageFileType = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
            // CREATE NEW FILE
            $profile_Image = new Image();
            $profile_Image->setFilename($filename);
            $profile_Image->setData(file_get_contents($tempname));
            $profile_Image->setParentId($Employee_image_path);
            $profile_Image->save();
            $employee->setProfile_Image($profile_Image);
            
            if ($previousPath != '/Employee_Image/_default_picture.jpg'){
                $to_delete = Asset::getByPath($previousPath);
                $to_delete->setParentId($Employee_image_bin_path);
                $to_delete->save();
            }
        }
        else{
            $profile_Image = Image::getById(89);
        }
        
        $fullname = trim($_POST['inputFirstName'])." ".trim($_POST['inputLastName']);
        $fullname = strtoupper($fullname);
        
        $employee->setName($fullname);
        $employee->setGender($_POST['inputGender']);
        $employee->setPhone($_POST['inputPhone']);
        $employee->setAddress($_POST['inputAddress']);
        $employee->setBirthday(Carbon::parse($_POST['inputBirthday']));
        $employee->setStaff_ID($_POST['inputStaffID']);
        $employee->setEmail($_POST['inputEmail']);
        $employee->setPosition($_POST['inputPosition']);
        $employee->setJoin_date(Carbon::parse($_POST['inputJoin_date']));
        $employee->setGithub($_POST['inputGithub']);
        $employee->setLinkedIn($_POST['inputLinkedin']);
        $employee->setFacebook($_POST['inputFacebook']);
        if ($_POST['inputPassword']){
            $employee->setUserPassword($_POST['inputPassword']);    
        }
        $employee->setPublished(true);
        $employee->save();
    }
    
    private function update_pimcore_user(){
        $currentUser = \Pimcore\Tool\Admin::getCurrentUser();
        if ($_POST['inputPassword']){
            $currentUser->setPassword(Authentication::getPasswordHash($_POST["inputUsername"], $_POST["inputPassword"]));
        }
        $currentUser->setFirstname($_POST['inputFirstName']);
        $currentUser->setLastname($_POST['inputLastName']);
        $currentUser->setEmail($_POST['inputEmail']);
        $currentUser->setActive(true);
        $currentUser->save();
    }
    
    private function update_openproject_user(){
        // echo json_encode($this->API_get("/api/v3/users"));
        $urlEndPoint =  $this->get_OP_user($_POST["inputUsername"]);
        
        $currentUser = \Pimcore\Tool\Admin::getCurrentUser();
        $isAdmin = $currentUser ? $currentUser->isAdmin() : false;
        $isAdmin_string = $isAdmin ? 'true' : 'false';
        
        $user_json = '{
            "admin": '.$isAdmin_string.',
            "email": "'.$_POST["inputEmail"].'",
            "firstName": "'.strtoupper(trim($_POST["inputFirstName"])).'",
            "language": "en", 
            "lastName": "'.strtoupper(trim($_POST["inputLastName"])).'",
            "login": "'.$_POST["inputUsername"].'"
        }';
        $this->API_patch($urlEndPoint, $user_json);
    }
    
    /**
     * @Route("/UpdateProfile/submit", name="UpdateProfilePage")
     */  
    public function updateProfile(){
        $this->update_pimcore_employee();
        $this->update_pimcore_user();
        $this->update_openproject_user();
        
        return $this->redirectToRoute('UserProfilePage',[],301);
    }
    
    
    private function getPimcoreUser(string $Username){
         $allUsers = new User\Listing();
         
         foreach ($allUsers as $user){
             if ($user->getUsername() == $Username ){
                 return $user;
             }
         }
    }
    
    /**
     * @Route("/forms/ResetPassword", name="resetPassswordFormPost")
     */
     public function resetPassword(){
        //  echo json_encode($_POST);
        $staffID = $_POST['employeeList'];

        if ($_POST['buttonAction'] == "Reset" and $_SERVER["REQUEST_METHOD"] == "POST"){
            $employee = DataObject\Employee::getByStaff_ID($staffID,1); 
            $pimcore_User = $this -> getPimcoreUser($staffID);
            
            if ($_POST['inputPassword'] and $employee and $pimcore_User){
                // Employee Object
                $employee->setUserPassword($_POST['inputPassword']);
                $employee->setPublished(true);
                $employee->save();
                
                // User Object
                $pimcore_User->setPassword(Authentication::getPasswordHash($staffID, $_POST["inputPassword"]));
                $pimcore_User->save();
            }
            else{
                $this->alert('No Password/No Employee/No Pimcore User');
            }
        }
        return $this->redirectToRoute('UserProfilePage',[],301);
     }
     
    /**
     * @Route("/forms/manageAdmin", name="manageAdminFormPost")
     */
    public function manageAdmin(){
        echo json_encode($_POST);
        $staffID = $_POST['employeeList'];
        $urlEndPoint = $this->get_OP_user($staffID);
        $pimcore_User = $this -> getPimcoreUser($staffID);
        
        if ($_POST['buttonAction'] == "update" and $_SERVER["REQUEST_METHOD"] == "POST"){
            $admin_flag = $_POST['inputAdmin']=='yes' ? true : false;
            $admin_flag_string = $admin_flag ? 'true' : 'false';
            
            // echo $pimcore_User->getemail();
            
            if($_POST['inputAdmin'] and $pimcore_User and $urlEndPoint){
                // // Pimcore User
                $pimcore_User->setAdmin($admin_flag);
                $pimcore_User->save();
                
                // OpenProject User
                // echo $urlEndPoint;
                $user_json = '{
                    "admin": '.$admin_flag_string.',
                    "email": "'.$pimcore_User->getEmail().'",
                    "firstName": "'.$pimcore_User->getFirstname().'",
                    "language": "en", 
                    "lastName": "'.$pimcore_User->getLastname().'",
                    "login": "'.$staffID.'"
                }';
                $this->API_patch($urlEndPoint, $user_json);
            }
            else{
                $this->alert('No input/No Pimcore User/No OpenProject User');
            }
        }
        return $this->redirectToRoute('UserProfilePage',[],301);
    }
    
    /**
     * @Route("/forms/userActivation", name="userActivationFormPost")
     */
    public function userActivation(){
        echo json_encode($_POST);
        $staffID = $_POST['employeeList'];
        $urlEndPoint = $this->get_OP_user($staffID);
        $pimcore_User = $this -> getPimcoreUser($staffID);
        if ($_POST['buttonAction'] == "update" and $_SERVER["REQUEST_METHOD"] == "POST"){
            $active_flag = $_POST['inputUserActivation']=='active' ? true : false;
            $active_flag_string = $active_flag ? 'active' : 'inactive';
            
            if($_POST['inputUserActivation'] and $pimcore_User and $urlEndPoint){
                // Pimcore User
                $pimcore_User->setActive($active_flag);
                $pimcore_User->save();
                
                // OpenProject User
                echo $urlEndPoint;
                if($active_flag){
                    // Activate account/Unlock account
                    echo "DELETE";
                    $this->API_delete($urlEndPoint.'/lock');
                }
                else{
                    // Inactivate account/Lock account
                    echo "POST";
                    $json_file ='';
                    $this->API_post($urlEndPoint.'/lock',$json_file);
                }
            }
            else{
                $this->alert('No input/No Pimcore User/No OpenProject User');
            }
        }
        return $this->redirectToRoute('UserProfilePage',[],301);
    }
    
    /**
     * @Route("/forms/deleteUser", name="deleteUserFormPost")
     */
     public function deleteUser(){
        //  echo "hi";
        //  echo json_encode($_POST);
         
         $staffID = $_POST['employeeList'];
         $Employee_image_bin_path = 95;
         
         if ($_POST['buttonAction'] == "delete" and $_SERVER["REQUEST_METHOD"] == "POST"){
             $employee = DataObject\Employee::getByStaff_ID($staffID,1); 
             $pimcore_User = $this -> getPimcoreUser($staffID);
             $urlEndPoint = $this->get_OP_user($staffID);
             
            //  echo $employee->getemail();
            //  echo $pimcore_User->getemail();
            //  echo $urlEndPoint ; 
             if($employee and $pimcore_User and $urlEndPoint){
                //  Mobe Image to Bin
                 $current_img = $employee->getProfile_Image(); 
                 $previousPath =  $current_img->getFullPath();
                 if ($previousPath != '/Employee_Image/_default_picture.jpg'){
                    $to_delete = Asset::getByPath($previousPath);
                    $to_delete->setParentId($Employee_image_bin_path);
                    $to_delete->save();
                }
                 
                //  REMOVE ANY ASSOCIATED OBJECT RELATED TO USERS
                 $enrollmentList = new DataObject\Enrollment\Listing();
                 foreach($enrollmentList as $enrollment){
                     if($staffID == $enrollment->getEmpEnroll()->getStaff_ID()){
                         echo $enrollment->getEmpEnroll()->getStaff_ID();
                         $enrollment->delete();
                     }
                 }
                 
                 $workExptList = new DataObject\WorkExperience\Listing();
                 foreach($workExptList as $workExp){
                     if($staffID == $workExp->getEmpWork()->getStaff_ID()){
                         echo $workExp->getEmpWork()->getStaff_ID();
                         $workExp->delete();
                     }
                 }
                
                 $skillProfList = new DataObject\SkillProficiency\Listing();
                 foreach($skillProfList as $skillProf){
                     if($staffID == $skillProf->getEmpSkill()->getStaff_ID()){
                         echo $skillProf->getEmpSkill()->getStaff_ID();
                         $skillProf->delete();
                     }
                 }
                
                // DELETE EMPLOYEE
                $employee->delete();
                
                // DELETE PIMCORE USER
                $pimcore_User->delete();
                
                // DELETE PIMCORE USER
                $this->API_delete($urlEndPoint);
             }
             else{
                 $this->alert('No Employee/No Pimcore User/No OpenProject User');
             }
         }
         
         return $this->redirectToRoute('UserProfilePage',[],301);
     }
}

