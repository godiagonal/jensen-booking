<?php
require("first_of_all.php");

$function = $_GET["function"];
$course_id = $_GET["course"];
$classroom_id = $_GET["classroom"];
$months_to_show = isset($_GET["months"]) ? $_GET["months"] : 6;

// lägg till ny bokning
if (isset($function) && $function == "insert") {
  $startdate = $_POST["startdate"];
  $enddate = $_POST["enddate"];
  $timeperiod_id = $_POST["timeperiod"];

  // kolla om det finns en överlappande bokning
  // överlappande innebär:
  // - samma klassrum
  // - korsande datum
  // - samma tid på dagen
  $query = "SELECT
              *
            FROM
              tbl_booking
            WHERE
              classroom_id = $classroom_id
              AND (
                booking_startdate <= '$enddate'
                AND booking_enddate >= '$startdate'
              )
              AND timeperiod_id = $timeperiod_id";

  $result = $db->query($query);
  $conflicts = $result->num_rows;
  $result->free();

  // om ingen bokningskonflikt: spara ny bokning
  if ($conflicts > 0) {
    $error = "Bokningen kunde inte sparas! Den överlappar en annan bokning";
  }
  else {
    $query = "INSERT INTO tbl_booking (
      booking_startdate,
      booking_enddate,
      timeperiod_id,
      course_id,
      classroom_id
    ) VALUES (
      '$startdate',
      '$enddate',
      $timeperiod_id,
      $course_id,
      $classroom_id
    )";

    $db->query($query);

    $success = "Bokning sparad";
  }
}

// ta bort bokning
else if (isset($function) && $function == "delete") {
  $booking_id = $_POST["booking_id"];

  $query = "DELETE FROM tbl_booking WHERE booking_id = $booking_id";

  $db->query($query);

  $success = "Bokning borttagen";
}
?>

<script>
$(function(){
  // uppdatera sidan när man byter klassrum
  $('#classroom').bind('change', function(){
    if ($(this).val().length > 0)
      $('#selectform').submit();
  });

  // validera formuläret för ny bokning
  $('#insertform').bind('submit', function(){
    var error = false;

    var startDate = $('#startdate');
    var endDate = $('#enddate');
    var timeperiod = $('#timeperiod');

    startDate.removeClass('error');
    endDate.removeClass('error');
    timeperiod.removeClass('error');

    if (startDate.val().length != 10) {
      startDate.addClass('error');
      error = true;
    }

    if (endDate.val().length != 10 || Date.parse( startDate.val() ) > Date.parse( endDate.val() )) {
      endDate.addClass('error');
      error = true;
    }

    if (timeperiod.val().length == 0) {
      timeperiod.addClass('error');
      error = true;
    }

    if (error)
      return false;
  });

  // initiera datepicker för startdatum
  $('#startdate').datepicker({
    dateFormat: 'yy-mm-dd'
  });

  // initiera datepicker för slutdatum
  $('#enddate').datepicker({
    dateFormat: 'yy-mm-dd'
  });
});

// ta bort klickad bokning
function deleteBooking(id) {
  if (confirm("Vill du verkligen ta bort bokningen?")) {
    $('#booking_id').val(id);
    $('#deleteform').submit();
  }
}
</script>

<div class="left">

  <form id="selectform" action="" method="GET">

    <h3>Välj klassrum</h3>

    <select id="classroom" name="classroom">
      <option value="">Välj klassrum</option>
      <?php
      // lägg till alla klassrum i listan
      $query = "SELECT * FROM tbl_classroom";
      if ($result = $db->query($query)) {
        while ($row = $result->fetch_assoc()) {
          echo "<option ".($row["classroom_id"] == $classroom_id ? "selected" : "")." value=".$row["classroom_id"].">".$row["classroom_name"]."</option>";
        }
        $result->free();
      }
      ?>
    </select>

    <input type="hidden" name="course" id="course" value="<?php echo $course_id; ?>" />

  </form>

  <?php if (isset($classroom_id) && $classroom_id > 0) { ?>

    <form id="insertform" action="?function=insert&course=<?php echo $course_id; ?>&classroom=<?php echo $classroom_id; ?>&months=<?php echo $months_to_show; ?>" method="POST">

      <h3>Välj period</h3>

      <input type="text" name="startdate" id="startdate" placeholder="Startdatum" />
      -
      <input type="text" name="enddate" id="enddate" placeholder="Slutdatum" />

      <select id="timeperiod" name="timeperiod">
        <option value="">Välj tid</option>
        <?php
        // lägg till alla tillgängliga tidspass i listan
        $query = "SELECT * FROM tbl_timeperiod";
        if ($result = $db->query($query)) {
          while ($row = $result->fetch_assoc()) {
            $tp_id = $row["timeperiod_id"];
            $tp_start = substr($row["timeperiod_start"],0,5);
            $tp_end = substr($row["timeperiod_end"],0,5);
            echo "<option value='$tp_id'>$tp_start - $tp_end</option>";
          }
          $result->free();
        }
        ?>
      </select>

      <input type="submit" value="Spara" />

      <div id="error"><?php echo $error; ?></div>
      <div id="success"><?php echo $success; ?></div>

    </form>

  <?php } ?>

  <div id="courseinfo" class="box">

    <form id="deleteform" action="?function=delete&course=<?php echo $course_id; ?>&classroom=<?php echo $classroom_id; ?>&months=<?php echo $months_to_show; ?>" method="POST">

      <h3>Vald kurs</h3>

      <?php
      // visa namnet på den valda kursen
      $query = "SELECT * FROM tbl_course WHERE course_id = $course_id";
      if ($result = $db->query($query)) {
        while ($row = $result->fetch_assoc()) {
          echo "<p>".$row["course_name"]."</p>";
        }
        $result->free();
      }
      ?>

      <h3>Bokningar för denna kurs</h3>
      
      <?php
      // lägg till en lista med alla bokningar för den valda kursen
      // listan ska innehålla tidspass (INNER JOIN tbl_timeperiod)
      // listan ska innehålla namnen på klassrum (INNER JOIN tbl_classroom)
      // sortera efter klassrum -> startdatum -> tidspass
      $query = "SELECT
                  bk.*,
                  tp.timeperiod_start,
                  tp.timeperiod_end,
                  cr.classroom_name

                FROM
                  tbl_booking AS bk
                  INNER JOIN tbl_timeperiod AS tp
                    ON bk.timeperiod_id = tp.timeperiod_id
                  INNER JOIN tbl_classroom AS cr
                    ON bk.classroom_id = cr.classroom_id

                WHERE
                  bk.course_id = $course_id

                ORDER BY
                  bk.classroom_id,
                  bk.booking_startdate,
                  bk.timeperiod_id";

      if ($result = $db->query($query)) {
        $prev_classroom = "";

        while ($row = $result->fetch_assoc()) {
          $curr_classroom = $row["classroom_name"];
          
          // gruppera efter klassrum
          // visa bara namnet på klassrummet om det inte är samma som det förra
          // fungerar eftersom att vi sorterat resultatet efter klassrum
          if ($prev_classroom != $curr_classroom)
            echo "<div class='booking_classroom'>".$curr_classroom."</div>";

          echo "<div class='booking_date'>";
          echo   $row["booking_startdate"]." - ".$row["booking_enddate"];
          echo   "<a onclick='deleteBooking(".$row["booking_id"].")' class='delete'>[ x ]</a>";
          echo   "<br>";
          echo   "<b>".substr($row["timeperiod_start"],0,5)." - ".substr($row["timeperiod_end"],0,5)."</b>";
          echo "</div>";

          $prev_classroom = $curr_classroom;
        }
        $result->free();
      }
      ?>

      <input type="hidden" name="booking_id" id="booking_id" value="" />

    </form>

  </div>

</div>

<div class="right">

  <?php if (isset($classroom_id) && $classroom_id > 0) { ?>

    <h3>Bokningar för klassrum</h3>

    <div id="calendar">

      <table>
        <tr>
          <th>Startdatum</th>
          <th>Slutdatum</th>
          <th>Tid</th>
          <th>Kurs</th>
        </tr>
        <?php
        // hämta bokningar som:
        // - har samma klassrum som det valda klassrummet
        // - inte redan har utgått
        $query = "SELECT
                    bk.*,
                    tp.timeperiod_start,
                    tp.timeperiod_end,
                    c.course_name

                  FROM
                    tbl_booking AS bk
                    INNER JOIN tbl_timeperiod AS tp
                      ON bk.timeperiod_id = tp.timeperiod_id
                    INNER JOIN tbl_course AS c
                      ON bk.course_id = c.course_id

                  WHERE
                    bk.classroom_id = $classroom_id

                  ORDER BY
                    bk.booking_startdate,
                    bk.timeperiod_id";
      
        // loopa igenom resultatet av bokningar
        if ($result = $db->query($query)) {
          while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo   "<td>".$row["booking_startdate"]."</td>";
            echo   "<td>".$row["booking_enddate"]."</td>";
            echo   "<td>".substr($row["timeperiod_start"],0,5)." - ".substr($row["timeperiod_end"],0,5)."</td>";
            echo   "<td>".$row["course_name"]."</td>";
            echo "</tr>";
          }
          $result->free();
        }
        ?>
      </table>

    </div>

  <?php } ?>

</div>

<div class="clearfix"></div>

<?php require("last_of_all.php"); ?>