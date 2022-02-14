<?php

namespace App\Controller\User;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use \Pimcore\Model\DataObject;
use \Pimcore\Model\Asset\Image; 
use \Pimcore\Model\User; 
use \Pimcore\Tool\Authentication;
use Carbon\Carbon;


class CreateUserController extends FrontendController
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
    
        // print_r($result);
        
        curl_close($curl);
    }
    
    private function create_pimcore_employee(){
        $Employee_image_path = 25;
        $Employee_object_path = 28;
        
        if ($_FILES['fileToUpload']['size'] == 0 && $_FILES['fileToUpload']['error'] == 0){
            $filename = $_FILES["fileToUpload"]["name"];
            $tempname = $_FILES["fileToUpload"]["tmp_name"];    
            $imageFileType = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
            // CREATE NEW FILE
            $profile_Image = new Image();
            $profile_Image->setFilename($filename);
            $profile_Image->setData(file_get_contents($tempname));
            $profile_Image->setParentId($Employee_image_path);
            $profile_Image->save();
        }
        else{
            $profile_Image = Image::getById(89);
        }

        $fullname = trim($_POST['inputFirstName'])." ".trim($_POST['inputLastName']);
        $fullname = strtoupper($fullname);
        
        $newObject = new DataObject\Employee(); 
        $newObject->setKey(\Pimcore\Model\Element\Service::getValidKey($_POST['inputStaffID'], 'object'));
        $newObject->setParentId($Employee_object_path);
        $newObject->setName($fullname);
        $newObject->setGender($_POST['inputGender']);
        $newObject->setPhone($_POST['inputPhone']);
        $newObject->setAddress($_POST['inputAddress']);
        $newObject->setBirthday(Carbon::parse($_POST['inputBirthday']));
        $newObject->setStaff_ID($_POST['inputStaffID']);
        $newObject->setEmail($_POST['inputEmail']);
        $newObject->setPosition($_POST['inputPosition']);
        $newObject->setJoin_date(Carbon::parse($_POST['inputJoin_date']));
        $newObject->setProfile_Image($profile_Image);
        $newObject->setGithub($_POST['inputGithub']);
        $newObject->setLinkedIn($_POST['inputLinkedin']);
        $newObject->setFacebook($_POST['inputFacebook']);
        $newObject->setUsername($_POST['inputUsername']);
        $newObject->setUserPassword($_POST['inputPassword']);
        
        $newObject->setPublished(true);
        $newObject->save();
    }
    
    private function create_pimcore_user(){
        $user = new User();
        $user->setparentId(0);
        $user->setUsername($_POST["inputUsername"]);
        $user->setPassword(Authentication::getPasswordHash($_POST["inputUsername"], $_POST["inputPassword"]));
        $user->setFirstname($_POST['inputFirstName']);
        $user->setLastname($_POST['inputLastName']);
        $user->setEmail($_POST['inputEmail']);
        $user->setAdmin($_POST["inputAdmin"]=="yes" ? true: false);
        $user->setActive(true);
        $user->save();
    }
    
    private function create_openproject_user(){
        // echo json_encode($this->API_get("/api/v3/users"));
        // echo json_encode($this->API_get("/api/v3/memberships"));
        
        // USER DATA PREPARATION
        $isAdmin_bool = $_POST["inputAdmin"]=="yes" ? true: false;
        $isAdmin_string = $isAdmin_bool ? 'true' : 'false';
        
        $user_json = '{
            "admin": '.$isAdmin_string.',
            "email": "'.$_POST["inputEmail"].'",
            "firstName": "'.$_POST["inputFirstName"].'",
            "lastName": "'.$_POST["inputLastName"].'",
            "login": "'.$_POST["inputUsername"].'", 
            "password": "'.$_POST["inputPassword"].'",
            "language": "en",
            "status": "active"
        }';
        // echo $user_json;
        
        // OPEN PROJECT USER CREATION 
        $this->API_post('/api/v3/users',$user_json);
        
        // OPEN PROJECT USER -GET ID
        $allUser = $this->API_get("/api/v3/users");
        $userArray = array();
        foreach($allUser->_embedded->elements as $user){
            $userArray[$user->login] = $user->id;
        }
        // echo $userArray[$_POST["inputUsername"]];
        $openProject_user_id = $userArray[$_POST["inputUsername"]]; 

        // OPEN PROJECT GRANT ACCESS AS "Staff and projects manager"
        $membership_json = '{
            "_links": {
                "schema": {
                    "href": "/api/v3/memberships/schema"
                },
                "project": {
                    "href": null
                },
                "principal": {
                    "href": "/api/v3/users/'.$openProject_user_id.'"
                },
                "roles": [
                    {
                        "href": "/api/v3/roles/6"
                    }
                ]
            }
		}';
// 		echo $membership_json;
        $this->API_post('/api/v3/memberships',$membership_json);
    }
    
    private function new_user(){
        $employeeList = new DataObject\Employee\Listing();

        foreach($employeeList as $employee){
            echo $employee->getStaff_ID() . "<br>";
            if ($employee->getStaff_ID() == $_POST['inputStaffID']) {
                return false;
            }
        }
        return true; 
    }
    
    /**
     * @Template()
     * @Route("/CreateUser", name="CreateUserPage")
     */
    public function defaultAction(Request $request){
        $currentUser = \Pimcore\Tool\Admin::getCurrentUser();

            $isAdmin = $currentUser ? $currentUser->isAdmin() : false;

        
        return $this->render('User/CreateUser.html.twig',[
            "isAdmin" => $isAdmin
            ]);
    }
    
    /**
     * @Route("/CreateUser/submit", name="CreateUserFormPage")
     */ 
    public function processForm(Request $request){
        // echo json_encode($_POST);
        // echo json_encode($_FILES);
        
        if($_POST['buttonAction']=='Create' and $this->new_user()){
            $this->create_pimcore_employee();
            $this->create_pimcore_user();
            $this->create_openproject_user();
        }
        else{
            $this->alert("USER EXIST!");
        }
        
        return $this->redirectToRoute('CreateUserPage',[],301);
    }
}