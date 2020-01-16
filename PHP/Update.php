<?php
require_once './function/db.php';
?>
<?php
try {
    $time = $_POST['time_period'];
    $day = $_POST['datepicker'];

    $sql = "";
    for ($i = 0; ; $i++) {
        if (!isset($_POST[$i])) {
            break;
        }
        $sql = "UPDATE students_attend_lesson SET  attend_id = " . $_POST['attend_id_' . $i] . " WHERE student_id = " . $_POST[$i] . " AND date='" . $_POST['datepicker'] . "' AND time_period=" . $_POST['time_period'] . ";<br>";
        query($sql);
    }
    header('Location: ACM1.php?day='.$day.'&time='.$time);
    exit();
} catch (Exception $e) {
    echo 'error:' . $e->getMessage();
}

?>
