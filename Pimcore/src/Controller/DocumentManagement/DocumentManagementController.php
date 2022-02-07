<?php

namespace App\Controller\DocumentManagement;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use \Pimcore\Model\Asset; 
use \Pimcore\Model\Asset\Folder;
use Carbon\Carbon;

class DocumentManagementController extends FrontendController
{
    /**
     * @Template()
     * @Route("/DocumentManagement/{currentFolderID}", name="DocumentManagementPage")
     */    
    public function defaultAction(Request $request,int $currentFolderID= 42){

        $currentAsset = Asset::getById($currentFolderID);
        $assetListAll = $currentAsset->getChildren();
        $parentId = $currentAsset->getparentId();
        
        $currentPath = str_replace("/DocumentManagementAsset","root",$currentAsset->getFullPath());
        
        // echo json_encode(Asset::$types);
        // echo json_encode($assetList);
        // echo "<br>";
        $assetList = []; 
        $folderList = [];
        $wantedType = ['folder', 'image', 'audio', 'video', 'document', 'archive'];
        
        foreach($assetListAll as $asset){
            
            $asset->{'modifyDate'} = Carbon::parse($asset->getmodificationDate()); 
            if ($asset->getType() == 'folder'){
                array_push($folderList,$asset);
            }
            elseif (in_array($asset->getType(), $wantedType)){
                array_push($assetList,$asset);
                
                // echo $asset->getFilename();
                // echo "<br>";
                // echo $asset->getType();
                // echo "<br>";
                // echo $asset->getRealFullPath();
                // echo "<br>";
                // echo "<br>";
            }
        }
        
        return $this->render('DocumentManagement/DocumentManagement.html.twig', [
            "PIMCORE_URL" => \Pimcore\Tool::getHostUrl(),
            "currentFolderID" => $currentFolderID,
            "parentId" => $parentId, 
            "folderList" => $folderList,
            "assetList" => $assetList,
            "currentPath" => $currentPath,
        ]);
    }
    
         /**
      * @param string $key
      * @param AbstractObject|null $parent
      * @return Folder
      */
    public function createObjectFolder($key, $parentId = null){
         $currentUser = \Pimcore\Tool\Admin::getCurrentUser();
        //  echo $currentUser->getName();
         $asset = Asset::create($parentId, array(
                    "filename" => $key,
                    "type" => "folder",
                    "userOwner" => $currentUser->getId(),
                    "userModification" => $currentUser->getId()
                ));
        return $asset;
     }
     
    private function alert($message="Sorry, An Error Occured"){
        echo '<script language="javascript">';
        echo 'alert("Error Message: '.$message.'")';
        echo '</script>';
    }
    
    /**
     * @Route("/DocumentManagement/{currentFolderID}/File/Upload", name="UploadFile")
     */    
    public function uploadFile(Request $request){
        // $image_type = ["jpeg","png","svg","tiff","gif","jpg"];
        $currentFolderID = (int)$_POST['currentFolderID'];
        $filename = $_FILES["fileToUpload"]["name"];
        $tempname = $_FILES["fileToUpload"]["tmp_name"];    
        $imageFileType = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
        
        if($_POST['buttonAction']=='Upload'){
            if ($tempname){
                $currentAsset = Asset::getById($currentFolderID);
                $assetListAll = $currentAsset->getChildren();
                foreach($assetListAll as $asset){
                    // UPDATE FILE
                    if($filename == $asset->getFilename()){
                        $asset->setData(file_get_contents($tempname));
                        // $asset->setPublished(true);
                        $asset->save();
                        return $this->redirectToRoute('DocumentManagementPage',[$currentFolderID],301);
                    }
                }
                // CREATE NEW FILE
                $newAsset = new Asset();
                $newAsset->setFilename($filename);
                $newAsset->setData(file_get_contents($tempname));
                $newAsset->setParentId($currentFolderID);
                // $newAsset->setPublished(true);
                $newAsset->save();
            }
            else{
                $this->alert("Invalid File");
            }
        }
        else if ($_POST['buttonAction']=='Delete'){
            $currentAsset = Asset::getById($_POST['DeleteFileList']);
            if($currentAsset){
                $currentAsset->delete();    
            }
            else{
                $this->alert("No File Selected");
            }
        }
        return $this->redirectToRoute('DocumentManagementPage',[$currentFolderID],301);
    }
    
    /**
     * @Route("/DocumentManagement/{currentFolderID}/Folder/Edit", name="EditFolder")
     */
     public function editFolder(Request $request){
         $currentFolderID = (int)$_POST['currentFolderID'];

        if($_POST['buttonAction'] == 'Create'){
            if($_POST['folderName']){
                $this->createObjectFolder($_POST['folderName'],$currentFolderID);
            }
            else{
                $this->alert("Invalid Folder Name");
            }
        }
        else if($_POST['buttonAction'] == 'Delete'){
            $currentAsset = Asset::getById($_POST['DeleteFolderList']);
            if ($currentAsset and count($currentAsset->getChildren())==0){
                $currentAsset->delete();    
            }
            else{
                $this->alert("The Folder is not empty/invalid. Please check&delete the sub-files/sub-folders first.");
            }
        }
        return $this->redirectToRoute('DocumentManagementPage',[$currentFolderID],301);
     }
}
