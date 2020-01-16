<?php
require_once dirname(__FILE__).'/../../vendor/autoload.php';
require_once dirname(__FILE__).'/awsCredentials.php';
use Aws\Rekognition\RekognitionClient;
?>
<?php //function
/*
    serchFace : 事前にS3に保存した画像をコレクションから検索する
    addImageToIndex : 顔を新たにコレクションに追加する。
    indexedFaceList : コレクションに追加されている顔のリストを表示する
    deleteIndex : コレクションからメタデータを削除する
    faceValidation : 顔認証を行い、80%以上の一致率である場合、該当する写真の出力ID(学生ユーザーID)を返す
    createCollection : 新たにコレクションを作成する
*/
?>
<?php
//変数宣言スペース
//
$collectionID = 'mm-project-';
$indexBucketName = 'mm-face-log';
$registerBucketName = 'mm-face-register';
$logBucketName = 'mm-face-log';

$rekognition = new RekognitionClient($awsCredentialsOptions);
?>
<?php
/* 作業スペース */
?>

<?php
function serchFace($imageName,$class_id){
    global $rekognition;
    global $collectionID;
    global $logBucketName;

    $result = $rekognition->searchFacesByImage([
        'CollectionId' => $collectionID.$class_id,
        'FaceMatchThreshold' => 0.8,
        'Image' => [
            'S3Object' => [
                'Bucket' => $logBucketName,
                'Name' => $imageName,
                'version' => "0",
            ],
        ],
        'MaxFaces' => 1,
        'QualityFilter' => 'NONE',
    ]);
    return ['Similarity' => $result['FaceMatches']['Face']['Similarity'], 'ExternalImageID' => $result['FaceMatches']['Face']['ExternalImageId']];
}

function addImageToIndex($ExternalImageID, $imageS3Name, $class_id){
    global $rekognition;
    global $collectionID;
    global $registerBucketName;

    //既に利用されているExternalImageID一覧を取得し、ExternalImageIDの重複登録を防止する。
    $usedExternalImageIDList = [];
    $IndexedList = indexedFaceList($class_id)['Faces'];
    foreach ($IndexedList as $row) {
        $usedExternalImageIDList[] = $row['ExternalImageId'];
    }
    if(in_array($ExternalImageID, $usedExternalImageIDList)){
        //指定したExternalImageIDが既に利用されている場合、エラーを返す。
        return '指定したExternalImageIDは既に利用されています';
    }

    $result = $rekognition->indexFaces([
        'CollectionId' => $collectionID.$class_id, // REQUIRED
        'DetectionAttributes' => ['DEFAULT'],
        'ExternalImageId' => $ExternalImageID,
        'Image' => [ // REQUIRED
            'S3Object' => [
                'Bucket' => $registerBucketName,
                'Name' => $imageS3Name,
                'version' => "0",
            ],
        ],
        'MaxFaces' => 1,
        'QualityFilter' => 'NONE',
    ]);
    return $result;
}

function indexedFaceList($class_id){
    global $rekognition;
    global $collectionID;

    $result = $rekognition->listFaces([
        'CollectionId' => $collectionID.$class_id,
        'MaxResults' => 100,
        'NextToken' => '',
    ]);
    return $result;
}

//一つのExternalImageIDが重複して登録されることは、基本的には起こらないが、
//万が一、重複して登録されているExternalImageIDを指定した場合、最後の一件の削除に対するresultのみがreturnされる
function deleteIndex($ExternalImageID, $class_id){
    global $rekognition;
    global  $collectionID;

    $IndexedList = indexedFaceList($class_id)['Faces'];
    foreach ($IndexedList as $row) {
        if($row['ExternalImageId'] == $ExternalImageID){
            $result = $rekognition->deleteFaces([
                'CollectionId' => $collectionID.$class_id,
                'FaceIds' => [$row['FaceId']],
            ]);
        }
    }
    return $result;
}

//顔認証を行い、80%以上の一致率である場合、該当する写真の出力ID(学生ユーザーID)を返す
function faceValidation($imageName,$class_id){
    $validationResult = serchFace($imageName,$class_id);
    if($validationResult['Similarity'] > 80){
        //一致率80%以上の場合認証成功と捉え、[本人の学生ユーザーID,学生ユーザー名,合計出席率,当月の出席率] を返す
        return $validationResult['ExternalImageID'];
    }
    return false;
}

//コレクションを作成する。
function createCollection($class_id){
    global $rekognition;
    global $collectionID;

    $result = $rekognition-> createCollection([
        'CollectionId' => $collectionID.$class_id
    ]);
    return $result;
}
?>
