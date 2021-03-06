<?php
require 'db.php';
?>

<?php
//テスト用データ
    //Users.html -web
        //$_POST['user_search'] 遷移先画面のurl /PHP/['ここを入れる']
        //$_POST['name'] 検索するユーザーの学籍番号 or 学生ユーザー名。

    //出席情報取得API -electron POST
        //request_flg : "user_search"
        //pc_id : リクエスト元PCのpc_id

    //クラス出席状況取得API -electron POST
        //request_flg : "getClassAttendanceInfomation"
        //day_week : 何曜日かの数字。月:1 火:2 ・・・
        //time_period : 何限目かの数字。 1限目:1 2限目:2　・・・
        //pc_id : リクエスト元PCのpc_id

    //時限・授業名取得API -electron POST
        //request_flg : getCurrentSubjct
        //day_week : 何曜日かの数字。月:1 火:2 ・・・
        //time_period : 何限目かの数字。 1限目:1 2限目:2　・・・
        //pc_id : リクエスト元PCのpc_id

    //出席予定学生一覧取得API -electron POST
        //request_flg : getAttendableStudent
        //day_week : 何曜日かの数字。月:1 火:2 ・・・
        //time_period : 何限目かの数字。 1限目:1 2限目:2　・・・
        //subject_id : 何の教科かを示す数字。

    //出席用端末登録API -electron POST
        //request_flg : getPCID
        //teacher_id : PCの認証を行う管理者アカウントのユーザーID
        //password : PCの認証を行う管理者アカウントのパスワード
        //classroom_id : 端末を配置する教室の教室ID

    $day = date("Y-m-d");
?>

<?php

    if(isset($_POST['pc_id']) and isset($_POST['request_flg'])) {
        if($_POST['request_flg'] == 'user_search'){
            if(isset($_POST['user_search'])) {
                //Users.html -web
                $data = prepareQuery('select student_id from students where student_id = ? or student_name = ?', [$_POST['user_search'], $_POST['user_search']]);
                $user_search = '?student_id=' . $data[0]['student_id'];
                var_dump($data[0]);
                header("Location: http://localhost:8081/mm_project/PHP/StudentPro.php" . $user_search);
            }else{
                print json_encode(['必須パラメータが送信されていません'], JSON_PRETTY_PRINT);
            }
        }

        //出席情報取得API -electron
        if ($_POST['request_flg'] == 'faceValidation') {
            if(isset($_POST['subject_id']) and isset($_POST['faceImageName'])) {
                $result = faceValidation($_POST['faceImageName']);
                if ($result != false) {
                    print json_encode(getPersonAttendanceInfomation($result, $_POST['subject_id']), JSON_PRETTY_PRINT);
                }
            }else{
                print json_encode(['必須パラメータが送信されていません'], JSON_PRETTY_PRINT);
            }
        }
        //クラス出席状況取得API -electron
        if ($_POST['request_flg'] == 'getClassAttendanceInfomation') {
            if(isset($_POST['pc_id']) and isset($_POST['day_week']) and isset($_POST['time_period'])) {
                $data = prepareQuery("
                    select student_id,attend_id
                    from(
                      select time_period, subject_id
                      from pc_classroom PC
                      left join classrooms_lesson_schedule CLS on PC.classroom_id = CLS.classroom_id
                      where PC.pc_id = ?
                            and day_of_the_week = ?
                            and time_period = ?)SQ
                      left join students_attend_lesson SAS on SAS.time_period = SQ.time_period and SAS.subject_id = SQ.subject_id
                    where date = ?",
                    [$_POST['pc_id'], $_POST['day_week'], $_POST['time_period'], $day]
                );
                print json_encode($data, JSON_PRETTY_PRINT);
            }else{
                print json_encode(['必須パラメータが送信されていません'], JSON_PRETTY_PRINT);
            }

        }
        //時限・授業名取得API -electron
        if ($_POST['request_flg'] == 'getCurrentSubjct') {
            if(isset($_POST['pc_id']) and isset($_POST['day_week']) and isset($_POST['time'])) {
                $data = prepareQuery("
                    select time_period, CLS.subject_id, subject_name
                    from pc_classroom PC
                      left join classrooms_lesson_schedule CLS on PC.classroom_id = CLS.classroom_id
                      left join subjects s on CLS.subject_id = s.subject_id
                    where pc_id = ? and day_of_the_week = ? and ? between reception_start and reception_end",
                    [$_POST['pc_id'], $_POST['day_week'], $_POST['time']]
                );
                print json_encode($data, JSON_PRETTY_PRINT);
            }
        }else{
            print json_encode(['必須パラメータが送信されていません'], JSON_PRETTY_PRINT);
        }
        //出席予定学生一覧取得API -electron
        if ($_POST['request_flg'] == 'getAttendableStudent') {
            if(isset($_POST['day_week']) and isset($_POST['time_period']) and isset($_POST['subject_id'])) {
                //曜日・時限・教科IDを指定して、授業の現状での出席情報を取得する。
                //教科IDに関しては、同じ日・時限には同一の授業は1つしか行われないものとしている。
                $attend_data = prepareQuery("
                    select S.student_id, student_name
                    from classes_lesson_schedule CLS
                        left join students S on S.class_id = CLS.class_id
                        left join students_subjects ss on S.student_id = ss.student_id and CLS.subject_id = ss.subject_id
                    where day_of_the_week = ? and time = ? and CLS.subject_id = ?",
                    [$_POST['day_week'], $_POST['time_period'], $_POST['subject_id']]
                );
                if ($attend_data = []) {
                    $student_list = prepareQuery("
                        select *
                        from students_subjects
                        where subject_id = '111'",
                        [$_POST['subject_id']]);
                    echo 'a';
                    var_dump($student_list);
                }
                print json_encode($attend_data, JSON_PRETTY_PRINT);
            }else{
                print json_encode(['必須パラメータが送信されていません'], JSON_PRETTY_PRINT);
            }
        }
        //出席用端末認証API -electron
        if ($_POST['request_flg'] == 'getPCID') {
            if(isset($_POST['teacher_id']) and isset($_POST['password'])) {
                $result = prepareQuery('select * from login where login_id = ? and login_password = ?',[$_POST['teacher_id'], $_POST['password']] );
            }
            if($result){
                $pass = substr(base_convert(hash('sha256', uniqid()), 16, 36), 0, 8);
                prepareQuery('insert into pc_classroom values (?,?)',[$_POST['classroom_id'], $pass]);
                print json_encode($pass, JSON_PRETTY_PRINT);
            }
        }
    }

    //引数で受け取った学生ユーザーIDの学生の出席情報を返す
    function getPersonAttendanceInfomation($student_id, $subject_id){
        $data = prepareQuery(
            "select SID.student_id,student_name,COALESCE(ROUND(LR.rate),0)totalRate,COALESCE(ROUND(LRM.rate),0)monthRate
                from (
                  select student_id,student_name
                  from students
                  where student_id = ?
                )SID
                  left join lesson_rate LR on SID.student_id = LR.student_id and LR.subject_id = ?
                  left join lesson_rate_month LRM on SID.student_id = LRM.student_id and LRM.subject_id = LR.subject_id and LRM.month = ?",
            [$student_id, $subject_id, date('m') + 1]
        );
        return $data;
    }
?>