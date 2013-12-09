<?php
require("first_of_all.php");

$function = $_GET["function"];
$course_id = $_GET["course"];
$classroom_id = $_GET["classroom"];
$months_to_show = isset($_GET["months"]) ? $_GET["months"] : 6;

if (isset($function) && $function == "insert") {
  $startdate = $_POST["startdate"];
  $enddate = $_POST["enddate"];
  $timeperiod_id = $_POST["timeperiod"];

  $query = "SELECT
              *
            FROM
              tbl_booking
            WHERE
              classroom_id = $classroom_id
              AND (
                booking_startdate < '$enddate'
                AND booking_enddate > '$startdate'
              )
              AND timeperiod_id = $timeperiod_id";

  $result = $db->query($query);
  $conflicts = $result->num_rows;
  $result->free();

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

else if (isset($function) && $function == "delete") {
  $booking_id = $_POST["booking_id"];

  $query = "DELETE FROM tbl_booking WHERE booking_id = $booking_id";

  $db->query($query);

  $success = "Bokning borttagen";
}

?>

<script>
$(function(){
  $('#classroom').bind('change', function(){
    if ($(this).val().length > 0)
      $('#selectform').submit();
  });

  $('#calendar .day').bind('click', function(){
    var selectedDay = $(this);

    var bookedFm = selectedDay.hasClass('booked_fm');
    var bookedEm = selectedDay.hasClass('booked_em');

    if (bookedFm && bookedEm) {
      alert('Dagen är redan bokad');
      return;
    }

    $('#calendar .selected').removeClass('selected');
    selectedDay.addClass('selected');

    $('#dialog').dialog('option', { position: { my: "center top", at: "center bottom", of: $(this) }});
    $('#dialog').dialog('open');
  });

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

    if (endDate.val().length != 10) {
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

  $("#dialog").dialog({
    autoOpen: false,
    resizable: false,
    draggable: false,
    height: 30,
    width: 150
  });
});

function setStartDate() {
  var selectedDay = $('#calendar .selected');

  var selectedDate = Date.parse( selectedDay.attr('id') );
  var endDate = Date.parse( $('#calendar .booking_end').attr('id') );

  if (selectedDate > endDate) {
    alert('Startdatum måste ligga före slutdatum');
    return;
  }

  $('#calendar .booking_start').removeClass('booking_start');
  selectedDay.addClass('booking_start');

  $('#startdate').val( selectedDay.attr('id') );

  $('#calendar .selected').removeClass('selected');
  $('#dialog').dialog('close');
}

function setEndDate(div) {
  var selectedDay = $('#calendar .selected');

  var selectedDate = Date.parse( selectedDay.attr('id') );
  var startDate = Date.parse( $('#calendar .booking_start').attr('id') );

  if (selectedDate < startDate) {
    alert('Slutdatum måste ligga efter startdatum');
    return;
  }

  $('#calendar .booking_end').removeClass('booking_end');
  selectedDay.addClass('booking_end');

  $('#enddate').val( selectedDay.attr('id') );

  $('#calendar .selected').removeClass('selected');
  $('#dialog').dialog('close');
}

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
      $query = "SELECT bk.*, tp.timeperiod_start, tp.timeperiod_end, cr.classroom_name FROM tbl_booking AS bk INNER JOIN tbl_timeperiod AS tp ON bk.timeperiod_id = tp.timeperiod_id INNER JOIN tbl_classroom AS cr ON bk.classroom_id = cr.classroom_id WHERE bk.course_id = $course_id ORDER BY bk.classroom_id, bk.booking_startdate, bk.timeperiod_id";
      if ($result = $db->query($query)) {
        $prev_classroom = "";

        while ($row = $result->fetch_assoc()) {
          $curr_classroom = $row["classroom_name"];
          
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
    
    <div id="dialog">
      <input type="button" onclick="setStartDate()" value="Startdatum" />
      <input type="button" onclick="setEndDate()" value="Slutdatum" />
    </div>

    <h3>Status för klassrum</h3>

    <div id="legend">
        <div class="booked_fm"></div>Förmiddag bokad (9-12)
        <div class="booked_em"></div>Eftermiddag bokad (13-16)
        <div class="booking_start"></div>Startdatum
        <div class="booking_end"></div>Slutdatum
    </div>

    <div id="calendar">

      <?php
      // hämta bokningar
      $query = "SELECT *, CURDATE() as a FROM tbl_booking WHERE classroom_id = $classroom_id AND booking_enddate > CURDATE()";

      // spara bokningsdata i array
      $bookings = array();

      // överför data från recordset till array
      if ($result = $db->query($query)) {
        while ($row = $result->fetch_assoc()) {
          // lägg in respektive rad från databasen i arrayen $bookings
          array_push($bookings, $row);
        }
        $result->free();
      }

      $startdate = new DateTime(date("Y-m"));

      $enddate = clone $startdate;
      $enddate->add(new DateInterval('P'.$months_to_show.'M'));

      $month_interval = new DateInterval('P1M');
      $month_period = new DatePeriod($startdate, $month_interval, $enddate);

      foreach ($month_period as $month) { // loop för varje månad i datumspannet
        $days_of_month = $month->format("t");
        $end_of_month = new DateTime($month->format("Y-m-t"));
        $end_of_month->add(new DateInterval('P1D'));

        $day_interval = new DateInterval('P1D');
        $day_period = new DatePeriod($month, $day_interval, $end_of_month);

        $width = $days_of_month*15;

        echo "<div class='month' style='width:".$width."px'>";
        echo   "<div class='dashes' style='width:".$width."px'></div>";
        echo   "<div class='startdate'>".$month->format("j M Y")."</div>";
        echo   "<div class='enddate'>".$month->format("t M")."</div>";
        echo   "<div class='clearfix'></div>";

        foreach ($day_period as $day) {
          $day_totime = strtotime($day->format("Y-m-d"));
          $class = "";

          foreach ($bookings as $booking) {
            $bk_startdate = strtotime($booking["booking_startdate"]);
            $bk_enddate = strtotime($booking["booking_enddate"]);
            $bk_timeperiod = $booking["timeperiod_id"];

            if ($day_totime >= $bk_startdate && $day_totime <= $bk_enddate) {
              if ($bk_timeperiod == 1)
                $class .= " booked_fm";
              else if ($bk_timeperiod == 2)
                $class .= " booked_em";
            }
          }

          echo "<div class='day$class' id='".$day->format("Y-m-d")."'>";
          echo   "<div></div>";
          echo   "<div></div>";
          echo   "<span>".$day->format("j")."</span>";
          echo "</div>";
        }

        echo   "<div class='clearfix'></div>";
        echo "</div>";
      }

      echo "<a href='?course=$course_id&classroom=$classroom_id&months=".($months_to_show+6)."'>[ Visa fler månader ]</a>";
      ?>

    </div>

  <?php } ?>

</div>

<div class="clearfix"></div>

<?php require("last_of_all.php"); ?>