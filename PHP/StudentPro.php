<?php
require_once 'functions.php';
require_logined_session();
require 'db.php';
header('Content-Type:text/html; charset=UTF-8');
?>
<?php
if( ( !isset($_GET['student_num'])or!isset($_GET['class_id']) ) and !isset($_GET['student_id']) ){
    header('Location: index.php');
}
if(isset($_GET['student_num']) and isset($_GET['class_id'])) {
    $student = prepareQuery("
    select LS1.student_id, LS1.student_name, C.class_name, S.sex, late, absence, early, LS1.attend_rate , month, ARM.attend_rate attend_rate_month
    from load_responsible_1 LS1
      left join students S on LS1.student_id = S.student_id
      left join classes C on S.class_id = C.class_id
    left join attend_rate_month ARM on S.student_id = ARM.student_id
    where LS1.student_num = ? and LS1.class_id = ? and month = ?"
        , [$_GET['student_num'], $_GET['class_id'], date('m')]);
}elseif(isset($_GET['student_id'])){
    $student = prepareQuery("
    select LS1.student_id, LS1.student_name, C.class_name, S.sex, late, absence, early, LS1.attend_rate , month, ARM.attend_rate attend_rate_month
    from load_responsible_1 LS1
      left join students S on LS1.student_id = S.student_id
      left join classes C on S.class_id = C.class_id
    left join attend_rate_month ARM on S.student_id = ARM.student_id
    where S.student_id = ? and month = ?",
        [$_GET['student_id'], date('m')]);
}
if(isset($student)){
    $student = $student[0];
}
?>


<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" media="all" href="../CSS/All.css">
    <link rel="stylesheet" media="all" href="../CSS/StudentPro.css">
    <link rel="stylesheet" media="all" href="../CSS/Responsible.css">
    <link rel="stylesheet" media="all" href="../CSS/Style.css">
    <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
    <title>StudentPro.html</title>
</head>
<body>


<!--どのアカウントで入ったか確認-->

<div class="header">

    <div class="title">

        <div class="title_text">
            <!--flex-grow: 3;-->
            <h1 class="head">
                <!-- 題名 -->
                学生プロフィール
            </h1>
        </div>
    </div>

</div>

<!-- 上のメニューバー -->
<div class="bu">
    <!--    <a href="AttendanceConfirmation.php" id="attend">状況管理</a>-->
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
        <li><a href="Groupmake.php">グループ作成</a></li>
        <li><a href="Classroom.php">教室管理</a></li>
        <li><a href="./logout.php?token=<?=h(generate_token())?>">ログアウト</a></li>
    </ul>
</div>
<p id="number">学籍番号</p>
<a id="snumber"><?php echo $student['student_id']; ?></a>

<p id="subj">学科</p>
<a id="subject" style="right: 300px; position: fixed;"><?php echo h($student['class_name'])?></a>


<p id="stname">名前</p>
<a id="name" style="right: 310px; position: fixed;"><?php echo h($student['student_name'])?></a>


<p id="stuhum">性別</p>
<a id="sex" style="right: 330px; position: fixed;"><?php echo $student['sex']?></a>




<!--今月の出席率は新しく追加したので、idがついていないです。必要であれば付けてください。-->
<table style="width: 700px; right: 200px; position: fixed;top: 550px">
    <tr>
        <th>
            今月の出席率<br>
            <a><?php echo $student['attend_rate_month'] ?></a>
            <br><br>
        </th>
        <th>
            遅刻数<br>
            <a id="late"><?php echo $student['late'] ?></a>
            <br><br>
        </th>

        <th>
            欠席数<br>
            <a id="absence"><?php echo $student['absence'] ?></a>
            <br><br>
        </th>

        <th>
            早退数<br>
            <a id="absence"><?php echo $student['early'] ?></a>
            <br><br>
        </th>

        <th>
            出席率<br>
            <a id="absence"><?php echo $student['attend_rate'] ?></a>
            <br><br>
        </th>
    </tr>
</table>


<form action="a.php" method="get">
    <input type="hidden" name="student_id" value="<?=$student['student_id']?>">
    <input type="submit" value="グラフ表示"
           style="display: block;
  background-color: #afc6e2;
  padding: 10px 20px;
  text-decoration: none;
  right: 100px;
  bottom: 100px;
  color: white;
  border-radius: 3px;
  position:absolute;">
</form>
</body>
</html>