<?php

namespace App\Controller\AITeam;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use \Pimcore\Model\DataObject;
use \Pimcore\Model\DataObject\Folder;
use Carbon\Carbon;

class TeamCalendarController extends FrontendController
{
    /**
     * @Template()
     * @Route("/AITeam/TeamCalendar", name="TeamCalendar")
     */    
      public function defaultAction(Request $request){
          $eventList = new DataObject\CalendarEvents\Listing();
          
          // DATE TIME (GMT +8)
          foreach($eventList as $event){
            //   echo $event->getStartDateTime();
              $event->getStartDateTime()->modify('+8 hours');
              $event->getEndDateTime()->modify('+8 hours'); 
          }
        //   echo json_encode($eventList);
          return $this->render('AITeam/TeamCalendar.html.twig',[
            "eventList" => $eventList
            ]);
    }

    
    /**
     * @Route("/AITeam/TeamCalendar", name="TeamCalendarFail")
     */    
      public function fallback(Request $request){
         return $this->render('default/default.html.twig');
     }
    
     /**
      * @param string $key
      * @param AbstractObject|null $parent
      * @return Folder
      */
     public function createObjectFolder($key, $parent = null){
        //  https://hotexamples.com/examples/pimcore.model.object/Folder/create/php-folder-create-method-examples.html
         if ($parent instanceof AbstractObject) {
             $parent = $parent->getId();
         }
         $folder = Folder::create(array('o_parentId' => $parent !== null ? $parent : 1, 'o_creationDate' => time(), 'o_userOwner' => 2 , 'o_userModification' => 2, 'o_key' => $key, 'o_published' => true));
         return $folder;
     }
 
    public function getFolderID($path){
        foreach(Folder::getList() as $start){
            if ($start->getFullPath() == $path){
                return $start->getId();
            }
        }
    }
    
    /**
     * @Route("/AITeam/forms/Event", name="EventFormPost")
     */
     public function EventForm(Request $request){
        
        // $companyObj = DataObject\Company::getById($_POST['CompanyObjectList']);
        // echo $_POST['EventID'];
        // echo $_POST['EventName'];
        // echo $_POST['EventType'];
        // echo $_POST['AllDayEvent'];
        // echo $_POST['EventStartDate'];
        // echo $_POST['EventEndDate'];
        
        $folderID = array(
            "Trainings" => 94,
            "CompanyEvent" => 97,
            "Holiday" => 96, 
            "OutOfOffice" => 98,
            "Others" => 99
            );
            
        $EventType_color = [
            "brown" => "Trainings",
            "crimson" => "CompanyEvent",
            "green" => "Holiday",
            "grey" => "OutOfOffice",
            "sapphire" => "Others"];
       
        $EventTypeNames_color = [
            "brown" => "Trainings/Workshops",
            "crimson" => "Company Event",
            "green" => "Holiday",
            "grey" => "Out Of Office",
            "sapphire" => "Others"];
            
        // CREATE FOLDER YEARLY+Monthly for OutofOffice
        $monthOfYears = ['01-January', '02-February', '03-March', '04-April', '05-May', '06-June', '07-July',  '08-August', '09-September', '10-October', '11-November', '12-December'];
        
        $parentFolderPath = "/CalenderEvents/".$EventType_color[$_POST['EventType']]."/".date("Y");
        // echo $parentFolderPath;
        // echo $EventType_color[$_POST['EventType']];
        // echo $folderID[$EventType_color[$_POST['EventType']]];
        
        // echo $parentFolderPath;
        $folderPath = $parentFolderPath."/".$monthOfYears[(int)date('m')-1];
        
        if (date("d-m")=="13-01" and !Folder::getByPath($parentFolderPath)){
            //CREATE YEAR FOLDER
            $this->createObjectFolder(date("Y"),$folderID[$EventType_color[$_POST['EventType']]]);
            
            // Create MONTH folder
            foreach($monthOfYears as $month){
                $this->createObjectFolder($month, $this->getFolderID($parentFolderPath));
            }
        }
        
        $object = DataObject::getById($_POST['EventID']);
        
        // echo $_POST['buttonAction'];
        // echo $_POST['EventType']."---<br>";
        
        if ($_POST['buttonAction'] == "Create" and $_SERVER["REQUEST_METHOD"] == "POST"){
                $objName = $_POST['EventName']." - ".$_POST['EventStartDate']."-".$_POST['EventEndDate'];
                
                // echo "create";
                // Create new Object
                $newObject = new DataObject\CalendarEvents(); 
                $newObject->setKey(\Pimcore\Model\Element\Service::getValidKey($objName, 'object'));
                $newObject->setParentId($this->getFolderID($folderPath));            //root of folder
                $newObject->setEventName($_POST['EventName']);
                $newObject->setStartDateTime(Carbon::parse($_POST['EventStartDate']));
                $newObject->setEndDateTime(Carbon::parse($_POST['EventEndDate']));
                $newObject->setEventType(trim($_POST['EventType']));
                $newObject->setAllDayEvent($_POST['AllDayEvent']);
                $newObject->setPublished(true);
                $newObject->save();
        }
        else if($_POST['buttonAction'] == "Save" and $_SERVER["REQUEST_METHOD"] == "POST"){
            $object->setParentId($this->getFolderID($folderPath));            //root of folder
            $object->setEventName($_POST['EventName']);
            $object->setStartDateTime(Carbon::parse($_POST['EventStartDate']));
            $object->setEndDateTime(Carbon::parse($_POST['EventEndDate']));
            $object->setEventType(trim($_POST['EventType']));
            $object->setAllDayEvent($_POST['AllDayEvent']);
            $object->setPublished(true);
            $object->save();
        }
        else if($_POST['buttonAction'] == "Delete" and $_SERVER["REQUEST_METHOD"] == "POST"){
            $object->delete();
        }
        
        return $this->redirectToRoute('TeamCalendar',[],301);
        

     }
     
     
}