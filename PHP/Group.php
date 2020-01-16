<?php
require_once 'functions.php';
require_once './function/db.php';
require_once './function/rekognition.php';
require_once './function/s3.php';
require_once './function/csv.php';

require_logined_session();
?>

<?php
if(isset($_SESSION['current_class_id']) and isset($_SESSION['current_class_name'])){
    $class_id = $_SESSION['current_class_id'];
    $class_name = $_SESSION['current_class_name'];
}else{
    header('Location: index.php');
}

//グループ作成
if (isset($_POST['gname']) and isset($_FILES['csvFile']) and isset($_FILES['faceImage'])) {
    $result = addGrope();
    if($result != 'sccsess'){

    }
}

function addGrope(){
    //csvファイルのアクセス権限を変更する
    $csvFilePath = './csv/' . $_FILES["csvFile"]["name"];
    chmod($_FILES['csvFile']['tmp_name'], 0666);

    //顔画像ファイルのアクセス権限を変更し、プロジェクト配下に配置する。
    //faceFilePathは、登録後にプロジェクト配下に保存された顔画像を削除するために利用する。
    $faceFilePath = [];
    $faces = $_FILES['faceImage'];
    $faceUploadSuccess = true;
    for ($i=0; $i<count($faces['name']); $i++) {
        chmod($faces['tmp_name'][$i], 0666);
        $faceFilePath[] = $faces["name"][$i];
        $isSuccess = move_uploaded_file($faces["tmp_name"][$i], './face/' . $faces["name"][$i]);
        if ($isSuccess != 1) $faceUploadSuccess = false;
    }
    //ファイルをプロジェクト配下のディレクトリに配置する。
    $csvUploadSuccess = move_uploaded_file($_FILES["csvFile"]["tmp_name"], $csvFilePath);

    //ファイルのプロジェクト配下への再配置が成功
    if ($faceUploadSuccess and $csvUploadSuccess) {
        //csvファイルを読み込み
        $csvRecords = loadCsv($csvFilePath);

        //全てのcsvファイルのID行に対応する顔画像が登録されていることを確認する
        $allIdsAreInFile = true;
        foreach ($csvRecords['records'] as $row) {
            if (!in_array($row['ID'].'.png', $faceFilePath)) {
                $allIdsAreInFile = false;
                $err =  'csvファイルのIDに対応する顔画像がありません';
            }
        }

        if($allIdsAreInFile) {
            //新規グループに付与するクラスID(クラスIDの最大+1)を取得する
            $addClassID = query('select max(class_id)id from classes')[0]['id'] + 1;

            //グループ登録sqlを実行する
            $sql = 'insert into classes values (' . $addClassID . ',\'' . $_POST['gname'] . '\');';
            query($sql);

            //グループ内にレコードを追加する
            createCollection($addClassID);
            $colbook = $csvRecords['colbook'];
            foreach ($csvRecords['records'] as $row) {
                //データをDBに保存
                $sql = 'insert into students values (' . $row[$colbook[0]] . ',' . $addClassID . ',\'' . $row[$colbook[2]] . '\',' . $row[$colbook[3]]. ');';
                query($sql);
                $sql = 'insert into classes_students values (' . $addClassID . ',' . $row[$colbook[1]] . ',' . $row[$colbook[0]] . ');';
                query($sql);

                //顔画像と学生ユーザーIDをS3とRekognitionのコレクションに追加
                $putFilePath = $row[$colbook[0]];
                s3PutObject($putFilePath);
                addImageToIndex($putFilePath,$putFilePath,$addClassID);
            }
            return 'success';
        }
    } else {
        //ファイルのプロジェクト配下への再配置が失敗
        $err = 'ファイルアップロードに失敗しました。';
    }
    return $err;
}

//担当クラス追加
if(isset($_GET['class_id'])){
    $selected_class_list = preg_split("/[,]/",$_GET['class_id']);
    foreach ($selected_class_list as $row){
        prepareQuery("insert into teachers_homerooms values (?,'2019',?)", [$row,$_SESSION['teacher_id']]);
    }
    //セッション内の担当クラスリストを更新。
    $_SESSION['class'] = [];
    $class = prepareQuery("
                select TH.class_id,class_name
                from teachers T
                  left join teachers_homerooms th on T.teacher_id = th.teacher_id
                  left join classes c on th.class_id = c.class_id
                where T.teacher_id = ?
                ORDER BY class_id",[$_SESSION['teacher_id']]);
    foreach ($class as $row) {
        $_SESSION['class'][] = ['id' => $row['class_id'],'name' => $row['class_name']];
    }
    header('Location: index.php');
}

$class_list = prepareQuery("select classes.class_id,class_name from classes left join teachers_homerooms h on classes.class_id = h.class_id where teacher_id <> ? or teacher_id is null",[$_SESSION['teacher_id']]);
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" media="all" href="../CSS/All.css">
    <link rel="stylesheet" media="all" href="../CSS/Group.css">
    <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
    <title>Group.html</title>
</head>
<body>


<!--どのアカウントで入ったか確認-->

<div class="header">

    <div class="title">

        <div class="title_text">
            <!--flex-grow: 3;-->
            <h1 class="head">
                グループ管理
            </h1>
        </div>
        <div id="class" class="title_menu">
            <select id="class_id" onchange="selectClass()" disabled>
                <!-- 折り返し処理 -->
                <div id="re">
                    <?php foreach($_SESSION['class'] as $d){?>
                        <!--flex-grow: 1;-->
                        <option
                                data-id="<?=h($d['id'])?>" data-name="<?=h($d['name'])?>"
                            <?php if($d['id'] == $class_id){echo 'selected';}?>>
                            <?=h($d['name'])?>
                        </option>
                    <?}?>
                </div>
            </select>
        </div>
    </div>
</div>


<!--検索バー -->
<div class="container">
    <input type="text" placeholder="Search..." id="sa-ch">
    <div class="search"></div>
</div>

<div class="contents">
    <ul class="nav">
        <li><a href="./index.php">担当グループ</a></li>
        <li><a href="Group.php">グループ管理</a></li>
        <li><a href="Users.php">ユーザー検索</a></li>
        <li><a href="Resuser.php">管理者ユーザー一覧</a></li>
        <li><a href="Classroom.php">教室管理</a></li>
        <li><a href="./logout.php?token=<?=h(generate_token())?>">ログアウト</a></li>
    </ul>
</div>
<div id="d">
    グループ作成
</div>

<div id="b">

    <form action="" method="post" enctype="multipart/form-data">
        グループ名 : <input type="text" name="gname" placeholder="grope_name"><br>
        CSV : <input type="file" name="csvFile" size="30"><br>
        顔画像 : <input type="file" name="faceImage[]" multiple><br>
        <input type="submit" value="送信" id="buto">
    </form>

</div>

<div id="c">
    グループ追加
</div>

<div id="e">
    追加するグループを選んでください。
</div>


<!--フォームタグ-->
<form action="" id = class_list_select method="get">
    <div class="addclass">
        <div class="list">
            <?php
            foreach ($class_list as $row) {
                echo '<p><input type="checkbox" value="'.$row['class_id'].'">'.$row['class_name'].'</p>';
            }
            ?>
        </div>
        <div class="btn">
            <input type="button" id="sub" value="OK" onclick=selectClass()>
        </div>
    </div>

    <script type="text/javascript">
        function selectClass() {
            // 選択されたオプションのバリューを取得する
            var element = document.getElementById("class_list_select");
            // クラスIDを自分に渡すURLを組み立てる
            var class_list = [];
            for (var i = 0; i < element.length-1; i++) {
                if (element[i].checked) {
                    class_list.push(element[i].value);
                }
                // location.hrefに渡して遷移する
                location.href = 'Group.php?class_id=' + class_list;
            }
        }
    </script>
</form>
</body>
</html>