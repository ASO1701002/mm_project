<?php
require_once dirname(__FILE__).'/../../vendor/autoload.php';
require_once dirname(__FILE__).'/awsCredentials.php';
use Aws\S3\S3Client;
?>
<?php
function s3PutObject($fileName){
    $registerBucketName = 'mm-face-register';
    global $awsCredentialsOptions;

    // S3クライアントを作成
    $s3 = new S3Client($awsCredentialsOptions);

    // S3バケットに画像をアップロード
    $result = $s3->putObject(array(
        'Bucket' => $registerBucketName,
        'Key' => $fileName, //ファイルのキー
        'Body' => fopen('./face/'.$fileName.'.png', 'rb'),
        'ACL' => 'public-read', //公開
        'ContentType' => mime_content_type('./face/'.$fileName.'.png') //ファイルの種類を識別
    ));
}
?>