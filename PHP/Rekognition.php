
<?php
require '../vendor/autoload.php';
use Aws\Rekognition\RekognitionClient;
?>

<?php
/* 作業スペース */

?>
<?php
/*
    serchFace : 事前にS3に保存した画像をコレクションから検索する
    addImageToIndex : 顔を新たにコレクションに追加する。
    indexedFaceList : コレクションに追加されている顔のリストを表示する
    deleteIndex : コレクションからメタデータを削除する
    faceValidation : 顔認証を行い、80%以上の一致率である場合、該当する写真の出力ID(学生ユーザーID)を返す
*/
?>

<?php
//変数宣言スペース
//
//認証情報のため、gitには上げていません。
?>

<?php

function serchFace($imageName){
    global $rekognition;
    global $collectionID;
    global $validateBucketName;

    $result = $rekognition->searchFacesByImage([
        'CollectionId' => $collectionID,
        'FaceMatchThreshold' => 0.8,
        'Image' => [
            'S3Object' => [
                'Bucket' => $validateBucketName,
                'Name' => $imageName,
                'version' => "0",
            ],
        ],
        'MaxFaces' => 1,
        'QualityFilter' => 'NONE',
    ]);
    return ['Similarity' => $result['FaceMatches']['Face']['Similarity'], 'ExternalImageID' => $result['FaceMatches']['Face']['ExternalImageId']];
}

function addImageToIndex($ExternalImageID, $imageS3Name){
    global $rekognition;
    global $collectionID;
    global $indexBucketName;

    //既に利用されているExternalImageID一覧を取得し、ExternalImageIDの重複登録を防止する。
    $usedExternalImageIDList = [];
    $IndexedList = indexedFaceList()['Faces'];
    foreach ($IndexedList as $row) {
        $usedExternalImageIDList[] = $row['ExternalImageId'];
    }
    if(in_array($ExternalImageID, $usedExternalImageIDList)){
        //指定したExternalImageIDが既に利用されている場合、エラーを返す。
        return '指定したExternalImageIDは既に利用されています';
    }

    $result = $rekognition->indexFaces([
        'CollectionId' => $collectionID, // REQUIRED
        'DetectionAttributes' => ['DEFAULT'],
        'ExternalImageId' => $ExternalImageID,
        'Image' => [ // REQUIRED
            'S3Object' => [
                'Bucket' => $indexBucketName,
                'Name' => $imageS3Name,
                'version' => "0",
            ],
        ],
        'MaxFaces' => 1,
        'QualityFilter' => 'NONE',
    ]);
    return $result;
}

function indexedFaceList(){
    global $rekognition;
    global $collectionID;

    $result = $rekognition->listFaces([
        'CollectionId' => $collectionID,
        'MaxResults' => 100,
        'NextToken' => '',
    ]);
    return $result;
}

//一つのExternalImageIDが重複して登録されることは、基本的には起こらないが、
//万が一、重複して登録されているExternalImageIDを指定した場合、最後の一件の削除に対するresultのみがreturnされる
function deleteIndex($ExternalImageID){
    global $rekognition;
    global  $collectionID;

    $result = '指定した$ExternalImageIDのメタデータは存在しません';
    $IndexedList = indexedFaceList()['Faces'];
    foreach ($IndexedList as $row) {
        if($row['ExternalImageId'] == $ExternalImageID){
            $result = $rekognition->deleteFaces([
                'CollectionId' => $collectionID,
                'FaceIds' => [$row['FaceId']],
            ]);
        }
    }
    return $result;
}

//顔認証を行い、80%以上の一致率である場合、該当する写真の出力ID(学生ユーザーID)を返す
function faceValidation($imageName){
    $validationResult = serchFace($imageName);
    if($validationResult['Similarity'] > 80){
        //一致率80%以上の場合認証成功と捉え、[本人の学生ユーザーID,学生ユーザー名,合計出席率,当月の出席率] を返す
        return $validationResult['ExternalImageID'];
    }
    return false;
}

////コレクションを作成する。
//function createCollection($collectionName){
//    global $rekognition;
//
//    $result = $rekognition-> createCollection([
//        'CollectionId' => $collectionName
//    ]);
//    return $result;
//}

////filesで送信した画像をコレクションから検索する
//    $file = $_FILES['']['tmp_name'];
//    if (!is_uploaded_file($file)) {
//        return;
//    }
//    $result = $rekognition->searchFacesByImage([
//        'CollectionId' => ,
//        'FaceMatchThreshold' => 0.8,
//        'Image' => [
//            'Bytes' => file_get_contents($file)
//        ],
//        'MaxFaces' => 1,
//        'QualityFilter' => 'NONE',
//    ]);
?>
