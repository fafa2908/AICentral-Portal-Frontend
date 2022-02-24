<?php

namespace App\Controller\ProjectDashboard;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use \Pimcore\Model\DataObject;
use Carbon\Carbon;

class ProjectDashboardController extends FrontendController
{
    private $openProjectURL = 'http://192.168.1.2:8081/';
    private $bookStackURL = 'http://192.168.1.2:6875/';
    private $bookStackTokenID = '6jNXc2vIJ8uZEJr0hTWTv29P9eLvBrOS'; //ADMIN
    private $bookStackTokenSecret = 'K88uqpGUPzZJMfQwm0y4GwoamMDh8DWU'; //ADMIN
    
    private function triggerAPI_OP(String $urlEndPoint){
        // EndPoint Documentation:  https://www.openproject.org/docs/api/endpoints/
        // OpenProject server IP
        $url = $this->openProjectURL;
        
        // String concatination
        $url .= $urlEndPoint;
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        // Basic Auth (username:password/API Key) [OpenPorject Admin User]
        // "apikey: 9717809fbe46c8378ed4da240c730a745965d29477881c2fecf61b77eab8cf1b"
        //
        $headers = array(
           "Authorization: Basic YXBpa2V5Ojk3MTc4MDlmYmU0NmM4Mzc4ZWQ0ZGEyNDBjNzMwYTc0NTk2NWQyOTQ3Nzg4MWMyZmVjZjYxYjc3ZWFiOGNmMWI=",
        );
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $resp = curl_exec($curl);
        curl_close($curl);
        
        // var_dump($resp);
        
        return json_decode($resp) ; 
        // return $resp ; 
    }
    
    private function API_post_Bookstack(String $urlEndPoint, String $json_file){
        $url_api     = $this->bookStackURL.$urlEndPoint;
        $arrHeader  = Array();
        // Authorization: Token <token_id>:<token_secret>
        $arrHeader[] = "Authorization: Token ".$this->bookStackTokenID.':'.$this->bookStackTokenSecret;
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
        return json_decode($result);
    }
    
    private function API_put_Bookstack(String $urlEndPoint, String $json_file){
        $url_api     = $this->bookStackURL.$urlEndPoint;
        $arrHeader  = Array();
        // Authorization: Token <token_id>:<token_secret>
        $arrHeader[] = "Authorization: Token ".$this->bookStackTokenID.':'.$this->bookStackTokenSecret;
        $arrHeader[] = "Content-Type: application/json";
        
        $curl = curl_init();
        // curl_setopt($curl, CURLOPT_PROXY, $proxy);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $arrHeader);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        // curl_setopt($curl, CURLOPT_PUT, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_file); 
        curl_setopt($curl, CURLOPT_URL, $url_api);
        
        $result = curl_exec($curl);
        if(curl_errno($curl)){ echo 'Request Error:' . curl_error($curl); }
        // echo json_encode(curl_getinfo($curl));
        // print_r($result);
        
        curl_close($curl);
        return json_decode($result);
    }
    
    private function API_get_Bookstack(String $urlEndPoint){
        $url     = $this->bookStackURL.$urlEndPoint;
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        $arrHeader  = Array();
        $arrHeader[] = "Authorization: Token ".$this->bookStackTokenID.':'.$this->bookStackTokenSecret;
        
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
    
    private function API_delete_Bookstack(String $urlEndPoint){
        $url_api     = $this->bookStackURL.$urlEndPoint;
        
        $arrHeader  = Array();
        $arrHeader[] = "Authorization: Token ".$this->bookStackTokenID.':'.$this->bookStackTokenSecret;
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
    
    function flatten(array $array) {
        $flatten_array = array();
        array_walk_recursive($array, function($a) use (&$flatten_array) { $flatten_array[] = $a; });
        return array_filter($flatten_array);;
    }

    private function alert($message="Sorry, An Error Occured"){
        echo '<script language="javascript">';
        echo 'alert("Error Message: \n '.$message.'")';
        echo '</script>';
    }
    /**
     * @Template()
     * @Route("/ProjectDashboard", name="ProjectPage")
     */    
    public function defaultAction(Request $request){
        
        $allProjects = $this->triggerAPI_OP("/api/v3/projects");
        // [Sample] http://192.168.1.2:8081//api/v3/projects

        $allProjectInfo = [];
        foreach($allProjects->_embedded->elements as $project){
            
            $projectInfo = $this->triggerAPI_OP($project->_links->self->href);
            // [Sample] http://192.168.1.2:8081//api/v3/projects/6
            
            // Get project members data
            $projectMemberships = $this->triggerAPI_OP($projectInfo->_links->memberships->href);
            // [Sample] http://192.168.1.2:8081//api/v3/memberships?filters=%5B%7B%22project%22%3A%7B%22operator%22%3A%22%3D%22%2C%22values%22%3A%5B%226%22%5D%7D%7D%5D
            
            // Get recent project task data
            $projectWorkPackages = $this->triggerAPI_OP( $projectInfo->_links->workPackages->href .'?filters=[]&pageSize=1000');
            // [Sample] http://192.168.1.2:8081//api/v3/projects/6/work_packages?filters=[]&pageSize=1000
            
            // Get project members name and their role
            $memberInfo = [];
            foreach($projectMemberships->_embedded->elements as $member){
                if ($member->_links->self->title <> 'System'){
                    array_push($memberInfo,[
                        "memberName" => $member->_links->self->title,
                        "memberRole" => array_values($member->_links->roles)[0]->title,
                    ]);
                }
            }
            
            // Get important project's task details
            $taskInfo = [];
            $allDate = [];
            $countCompletedTask = 0;
            $closedCategory = ["Closed", "Rejected"];
            
            foreach($projectWorkPackages->_embedded->elements as $task){
                if(property_exists($task,"date")){
                    $date = [
                        "date"=> $task->date,
                    ]; 
                }
                else{
                    $date = [
                            "startDate" => $task->startDate,
                            "dueDate" =>  $task->dueDate,
                        ];
                }
                array_push($allDate,$date);
                
                if(in_array($task->_links->status->title,$closedCategory)){
                    $countCompletedTask += 1; 
                }
                
                array_push($taskInfo,[
                        "taskName" => $task->subject,
                        "description" =>  $task->description->raw,
                        "createdAt" => $task -> createdAt, 
                        "author" =>  $task->_links->author->title,
                        "category" => in_array($task->_links->status->title,$closedCategory)? "Closed" : "Open",
                        "type" =>  $task->_links->type->title,
                        "version" =>  property_exists($task->_links->version,"title") ? $task->_links->version->title : "None",
                        "status" =>  property_exists($task->_links->status,"title") ? $task->_links->status->title : "None",
                        "priority" =>  $task->_links->priority->title,
                        "date" => $date,
                        "estimatedTime" => $task->estimatedTime,
                    ]);
            }
            
            $allDate = $this->flatten($allDate);
            // echo "Latest Date: ". max($allDate)."\n";
            // echo "Earliest Date: ". min($allDate)."\n";
            
            // Project Status
            // $activeStatus = ($countCompletedTask == $projectWorkPackages->total and !property_exists($projectInfo->_links->status,"title")) ? "Completed": "Ongoing";
            $activeStatus = ($countCompletedTask == $projectWorkPackages->total) ? "Completed": "Ongoing";
            
            if (count($allDate) == 0 or count($allDate) == 1){
                // $projectInfo->name
                $this->alert("Project Name: ". (String)$projectInfo->name ." \\n ". "Please create at least 2 task. (Start and End task)");
            }
            
            $info =[
                    "name" => $projectInfo->name,
                    "openProjectURL" => $this->openProjectURL.'projects/'.$projectInfo->identifier,
                    "status" =>  property_exists($projectInfo->_links->status,"title") ? $projectInfo->_links->status->title : "None",
                    "active" => $activeStatus, 
                    "startDate" => min($allDate),
                    "endDate" => max($allDate),
                    "projectDescription" => $projectInfo->description->raw,
                    "statusDescription" => $projectInfo->statusExplanation->raw,
                    "memberInfo" => $memberInfo, 
                    "taskInfo" => $taskInfo,
                    "countCompletedTask" => $countCompletedTask,
                    "totalTask" => $projectWorkPackages-> total,
                ];
                
            // Save the information for each project 
            array_push($allProjectInfo, $info);
            
            // Save and edit pimcore object 
            $projectList = new DataObject\Project\Listing();
            // Get ALL probjet Object (publish and not publish)
            $projectList->setUnpublished(true);
            $projectList->load();
            
            $projectList -> filterByProjectName($projectInfo->name);
            
            // echo count($projectList);
            
            if (count($projectList)) {
                // Have Object : Update the object's information
                // echo json_encode($projectList);
                foreach($projectList as $project){
                    // echo ($project);
                    $project->setProjectName($projectInfo->name);
                    $project->setProjectDescription($projectInfo->description->raw);
                    $project->setProjectActive($activeStatus);
                    $project->setCompletedTask($countCompletedTask);
                    $project->setTotalTask($projectWorkPackages-> total);
                    $project->setStartDate( Carbon::parse(min($allDate)));
                    $project->setEndDate( Carbon::parse(max($allDate)));
                    $project->setOpenProjectURL($this->openProjectURL.'projects/'.$projectInfo->identifier);
                    
                    // PIMCORE - BOOKSTACK
                    $shelfID = $project->getBookStack_ShelfID();
                    $currentShelf = $this->API_get_Bookstack('api/shelves/'.$shelfID);
                    
                    $currentBook_ID = [];
                    foreach($currentShelf->books as $book){
                        array_push($currentBook_ID,$book->id);
                    }
                    
                    $putShelvesJSON ='{
                        "name": "'.$projectInfo->name.'",
                        "description": "'.$projectInfo->description->raw.'",
                        "books": '.json_encode($currentBook_ID).'
                    }';
                    $this->API_put_Bookstack('api/shelves/'.$shelfID, $putShelvesJSON);
                    
                    $project->setPublished(true);
                    $project->save();
                }
            }
            else{
                // No Object : Create and write information
                    // PIMCORE - OPENPROJECT
                    $newProject = new DataObject\Project(); 
                    $newProject->setKey(\Pimcore\Model\Element\Service::getValidKey($projectInfo->name, 'object'));
                    $newProject->setParentId(111);                      //Project folder id=111
                    $newProject->setProjectName($projectInfo->name);
                    $newProject->setProjectDescription($projectInfo->description->raw);
                    $newProject->setProjectActive($activeStatus);
                    $newProject->setCompletedTask($countCompletedTask);
                    $newProject->setTotalTask($projectWorkPackages-> total);
                    $newProject->setStartDate( Carbon::parse(min($allDate)));
                    $newProject->setEndDate( Carbon::parse(max($allDate)));
                    $newProject->setOpenProjectURL($this->openProjectURL.'projects/'.$projectInfo->identifier);
                    
                    // PIMCORE - BOOKSTACK
                    $createShelvesJSON = '{
                        "name": "'.$projectInfo->name.'",
                        "description": "'.$projectInfo->description->raw.'",
                        "books": []
                    }';
                    $createShelfResponse = $this->API_post_Bookstack('api/shelves',$createShelvesJSON);
                    // $createShelfResponse = json_decode($createShelfResponse, TRUE);
                    $newProject->setBookStack_ShelfID($createShelfResponse->id);
                    $newProject->setBookStackURL($this->bookStackURL.'/shelves/'.$createShelfResponse->slug);
                    $newProject->setPublished(true);
                    $newProject->save();
            }
        }
        
        // Sort by active status amd project end date
        array_multisort(array_column($allProjectInfo, 'active'),  SORT_DESC,
                array_column($allProjectInfo, 'endDate'), SORT_ASC,
                $allProjectInfo);
        
        // Clean up not used object in pimcore (project not exist in OpenProject) [Condition: created and deleted OP Project]
        $projectList = new DataObject\Project\Listing();
        $projectList->setOrderKey('ProjectActive');
        $projectList->setOrder('desc');
        foreach($projectList as $pimcoreProject){
            if (! in_array($pimcoreProject->getProjectName(), array_column($allProjectInfo, 'name')) ){
                // Remove pimcore object 
                // echo $pimcoreProject->getProjectName();
                // echo "<br>";    
                
                $shelfID = $pimcoreProject->getBookStack_ShelfID();
                $this->API_delete_Bookstack('api/shelves/'.$shelfID);
                
                $pimcoreProject -> delete();
            }
        }
        
        // echo json_encode(array_column($allProjectInfo, 'name'));
        
        // foreach($allProjectInfo as $projectInfo){
        //     echo $projectInfo['name'];
        //     echo "<br>";
        //     echo(json_encode($projectInfo));
        //     echo "<br>";
        //     echo "<br>";
        // }
        // echo json_encode($allProjectInfo);
        
        return $this->render('ProjectDashboard/ProjectDashboard.html.twig',[
            "projectList" => $projectList,
            ]);
    }
    
    /**
     * @Route("/ProjectDashboard/Forms/Project", name="ProjectFormPost")
     */
    public function projectForm(Request $request){
        
        // echo "hello";
        
        $projectObj = DataObject\Project::getById($_POST['ProjectList']);
        $projectObj->setSharePointURL($_POST['SharePointURL']);
        $projectObj->setPublished(true);
        $projectObj->save();
                
        return $this->redirectToRoute('ProjectPage',[],301);
    }
    
}