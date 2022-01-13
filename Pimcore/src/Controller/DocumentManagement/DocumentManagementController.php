<?php

namespace App\Controller\DocumentManagement;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use \Pimcore\Model\Asset;
use Carbon\Carbon;

class DocumentManagementController extends FrontendController
{
    /**
     * @Template()
     * @Route("/DocumentManagement/{currentFolderID}", name="DocumentManagementPage")
     */
    public function defaultAction(Request $request, int $currentFolderID = 42)
    {

        $currentAsset = Asset::getById($currentFolderID);
        $assetListAll = $currentAsset->getChildren();
        $parentId = $currentAsset->getparentId();


        // echo json_encode(Asset::$types);
        // echo json_encode($assetList);
        // echo "<br>";
        $assetList = [];
        $folderList = [];
        $wantedType = ['folder', 'image', 'audio', 'video', 'document', 'archive'];

        foreach ($assetListAll as $asset) {

            $asset->{'modifyDate'} = Carbon::parse($asset->getmodificationDate());
            if ($asset->getType() == 'folder') {
                array_push($folderList, $asset);
            } elseif (in_array($asset->getType(), $wantedType)) {
                array_push($assetList, $asset);

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

        ]);
    }
}
