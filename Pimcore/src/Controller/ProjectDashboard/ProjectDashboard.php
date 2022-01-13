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

    private function triggerAPI(String $urlEndPoint)
    {
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

        return json_decode($resp);
        // return $resp ; 
    }

    function flatten(array $array)
    {
        $flatten_array = array();
        array_walk_recursive($array, function ($a) use (&$flatten_array) {
            $flatten_array[] = $a;
        });
        return array_filter($flatten_array);;
    }

    /**
     * @Template()
     */
    public function defaultAction(Request $request)
    {

        $allProjects = $this->triggerAPI("/api/v3/projects");
        // [Sample] http://192.168.1.2:8081//api/v3/projects

        $allProjectInfo = [];
        foreach ($allProjects->_embedded->elements as $project) {

            $projectInfo = $this->triggerAPI($project->_links->self->href);
            // [Sample] http://192.168.1.2:8081//api/v3/projects/6

            // Get project members data
            $projectMemberships = $this->triggerAPI($projectInfo->_links->memberships->href);
            // [Sample] http://192.168.1.2:8081//api/v3/memberships?filters=%5B%7B%22project%22%3A%7B%22operator%22%3A%22%3D%22%2C%22values%22%3A%5B%226%22%5D%7D%7D%5D

            // Get recent project task data
            $projectWorkPackages = $this->triggerAPI($projectInfo->_links->workPackages->href . '?filters=[]&pageSize=1000');
            // [Sample] http://192.168.1.2:8081//api/v3/projects/6/work_packages?filters=[]&pageSize=1000

            // $projectWorkPackages = $this->triggerAPI( $projectInfo->_links->workPackages->href);
            // [Sample] http://192.168.1.2:8081//api/v3/projects/6/work_packages

            // Get project members name and their role
            $memberInfo = [];
            foreach ($projectMemberships->_embedded->elements as $member) {
                if ($member->_links->self->title <> 'System') {
                    array_push($memberInfo, [
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

            foreach ($projectWorkPackages->_embedded->elements as $task) {
                if (property_exists($task, "date")) {
                    $date = [
                        "date" => $task->date,
                    ];
                } else {
                    $date = [
                        "startDate" => $task->startDate,
                        "dueDate" =>  $task->dueDate,
                    ];
                }
                array_push($allDate, $date);

                if (in_array($task->_links->status->title, $closedCategory)) {
                    $countCompletedTask += 1;
                }

                array_push($taskInfo, [
                    "taskName" => $task->subject,
                    "description" =>  $task->description->raw,
                    "createdAt" => $task->createdAt,
                    "author" =>  $task->_links->author->title,
                    "category" => in_array($task->_links->status->title, $closedCategory) ? "Closed" : "Open",
                    "type" =>  $task->_links->type->title,
                    "version" =>  property_exists($task->_links->version, "title") ? $task->_links->version->title : "None",
                    "status" =>  property_exists($task->_links->status, "title") ? $task->_links->status->title : "None",
                    "priority" =>  $task->_links->priority->title,
                    "date" => $date,
                    "estimatedTime" => $task->estimatedTime,
                ]);
            }

            $allDate = $this->flatten($allDate);
            // echo "Latest Date: ". max($allDate)."\n";
            // echo "Earliest Date: ". min($allDate)."\n";

            $activeStatus = ($countCompletedTask == $projectWorkPackages->total and !property_exists($projectInfo->_links->status, "title")) ? "Completed" : "Ongoing";

            $info = [
                "name" => $projectInfo->name,
                "openProjectURL" => $this->openProjectURL . 'projects/' . $projectInfo->identifier,
                "status" =>  property_exists($projectInfo->_links->status, "title") ? $projectInfo->_links->status->title : "None",
                "active" => $activeStatus,
                "startDate" => min($allDate),
                "endDate" => max($allDate),
                "projectDescription" => $projectInfo->description->raw,
                "statusDescription" => $projectInfo->statusExplanation->raw,
                "memberInfo" => $memberInfo,
                "taskInfo" => $taskInfo,
                "countCompletedTask" => $countCompletedTask,
                "totalTask" => $projectWorkPackages->total,
            ];

            // Save the information for each project 
            array_push($allProjectInfo, $info);

            // Save and edit pimcore object 
            $projectList = new DataObject\Project\Listing();
            // Get ALL probjet Object (publish and not publish)
            $projectList->setUnpublished(true);
            $projectList->load();

            $projectList->filterByProjectName($projectInfo->name);

            // echo count($projectList);

            if (count($projectList)) {
                // Have Object : Update the object's information
                // echo json_encode($projectList);
                foreach ($projectList as $project) {
                    // echo ($project);
                    $project->setProjectName($projectInfo->name);
                    $project->setProjectDescription($projectInfo->description->raw);
                    $project->setProjectActive($activeStatus);
                    $project->setCompletedTask($countCompletedTask);
                    $project->setTotalTask($projectWorkPackages->total);
                    $project->setStartDate(Carbon::parse(min($allDate)));
                    $project->setEndDate(Carbon::parse(max($allDate)));
                    $project->setOpenProjectURL($this->openProjectURL . 'projects/' . $projectInfo->identifier);
                    $project->setPublished(true);

                    $project->save();
                }
            } else {
                // No Object : Create and write information
                $newProject = new DataObject\Project();
                $newProject->setKey(\Pimcore\Model\Element\Service::getValidKey($projectInfo->name, 'object'));
                $newProject->setParentId(111);                      //Project folder id=111
                $newProject->setProjectName($projectInfo->name);
                $newProject->setProjectDescription($projectInfo->description->raw);
                $newProject->setProjectActive($activeStatus);
                $newProject->setCompletedTask($countCompletedTask);
                $newProject->setTotalTask($projectWorkPackages->total);
                $newProject->setStartDate(Carbon::parse(min($allDate)));
                $newProject->setEndDate(Carbon::parse(max($allDate)));
                $newProject->setOpenProjectURL($this->openProjectURL . 'projects/' . $projectInfo->identifier);
                $newProject->setPublished(true);
                $newProject->save();
            }
        }

        // Sort by active status amd project end date
        array_multisort(
            array_column($allProjectInfo, 'active'),
            SORT_DESC,
            array_column($allProjectInfo, 'endDate'),
            SORT_ASC,
            $allProjectInfo
        );

        // Clean up not used object in pimcore (project not exist in OpenProject) [Condition: created and deleted OP Project]
        $projectList = new DataObject\Project\Listing();
        $projectList->setOrderKey('ProjectActive');
        $projectList->setOrder('desc');
        foreach ($projectList as $pimcoreProject) {
            if (!in_array($pimcoreProject->getProjectName(), array_column($allProjectInfo, 'name'))) {
                // Remove pimcore object 
                $pimcoreProject->delete();
                // echo $pimcoreProject->getProjectName();
                // echo "<br>";    
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

        return $this->render('ProjectDashboard/ProjectDashboard.html.twig', [
            "projectList" => $projectList,
        ]);
    }
}
