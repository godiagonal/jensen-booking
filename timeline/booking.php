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
                booking_startdate < '$enddate'
                AND booking_enddate > '$startdate'
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

  // visa val av startdatum eller slutdatum när man klickar på en dag i kalendern
  $('#calendar .day').bind('click', function(){
    var selectedDay = $(this);

    var bookedFm = selectedDay.hasClass('booked_fm');
    var bookedEm = selectedDay.hasClass('booked_em');

    if (bookedFm && bookedEm) {
      alert('Dagen är redan bokad');
      return;
    }

    // visa dagens nummer
    $('#calendar .selected').removeClass('selected');
    selectedDay.addClass('selected');

    // visa tooltip under den klickade dagen
    $('#dialog').dialog('option', { position: { my: "center top", at: "center bottom", of: $(this) }});
    $('#dialog').dialog('open');
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

  // initiera tooltip för val av startdatum eller slutdatum
  $("#dialog").dialog({
    autoOpen: false,
    resizable: false,
    draggable: false,
    height: 30,
    width: 150
  });
});

// markera vald dag som startdatum
function setStartDate() {
  // den valda dagen
  var selectedDay = $('#calendar .selected');

  // gör om till date-objekt
  var selectedDate = Date.parse( selectedDay.attr('id') );
  var endDate = Date.parse( $('#calendar .booking_end').attr('id') );

  // kolla om ett korrekt startdatum är valt
  if (selectedDate > endDate) {
    alert('Startdatum måste ligga före slutdatum');
    return;
  }

  // avmarkera det (eventuella) föregående startdatumet
  $('#calendar .booking_start').removeClass('booking_start');
  selectedDay.addClass('booking_start');

  // lägg till den valda dagens datum i formuläret
  $('#startdate').val( selectedDay.attr('id') );

  // göm tooltip
  $('#calendar .selected').removeClass('selected');
  $('#dialog').dialog('close');
}

// markera vald dag som slutdatum
function setEndDate(div) {
  // den valda dagen
  var selectedDay = $('#calendar .selected');

  // gör om till date-objekt
  var selectedDate = Date.parse( selectedDay.attr('id') );
  var startDate = Date.parse( $('#calendar .booking_start').attr('id') );

  // kolla om ett korrekt slutdatum är valt
  if (selectedDate < startDate) {
    alert('Slutdatum måste ligga efter startdatum');
    return;
  }

  // avmarkera det (eventuella) föregående startdatumet
  $('#calendar .booking_end').removeClass('booking_end');
  selectedDay.addClass('booking_end');

  // lägg till den valda dagens datum i formuläret
  $('#enddate').val( selectedDay.attr('id') );

  // göm tooltip
  $('#calendar .selected').removeClass('selected');
  $('#dialog').dialog('close');
}

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
    
    <div id="dialog">
      <input type="button" onclick="setStartDate()" value="Startdatum" />
      <input type="button" onclick="setEndDate()" value="Slutdatum" />
    </div>

    <h3>Schema för klassrum</h3>

    <div id="legend">
        <div class="booked_fm"></div>Förmiddag bokad (9-12)
        <div class="booked_em"></div>Eftermiddag bokad (13-16)
        <div class="booking_start"></div>Startdatum
        <div class="booking_end"></div>Slutdatum
    </div>

    <div id="calendar">

      <?php
      // hämta bokningar som:
      // - har samma klassrum som det valda klassrummet
      // - inte redan har utgått
      $query = "SELECT * FROM tbl_booking WHERE classroom_id = $classroom_id AND booking_enddate > CURDATE()";

      // spara bokningsdata i array för senare användning
      $bookings = array();

      // överför data från resultatet till array
      if ($result = $db->query($query)) {
        while ($row = $result->fetch_assoc()) {
          // lägg in respektive rad från databasen i arrayen $bookings
          array_push($bookings, $row);
        }
        $result->free();
      }

      // startdatum för kalendern: första dagen i aktuell månad
      // Y-m = ex. 2013-12-01
      $startdate = new DateTime(date("Y-m"));

      // slutdatum för kalendern: startdatum + 6 månader (eller mer)
      // ex. $months_to_show = 6 -> P6M -> 6 månader
      $enddate = clone $startdate;
      $enddate->add(new DateInterval('P'.$months_to_show.'M'));

      // fastställ hur kalendern ska grupperas
      // P1M = 1 månad = månadsvis
      $month_interval = new DateInterval('P1M');

      // fastställ hur många grupperingar som ska förekomma i datumspannet
      // mellan startdatum och slutdatum om man delar in det i månader ($month_interval)
      $month_period = new DatePeriod($startdate, $month_interval, $enddate);

      // loop för varje månad i datumspannet
      foreach ($month_period as $month) {
        // ta ut sista datumet i månaden
        // t = sista dagens nummer i månaden, ex. 31
        $days_of_month = $month->format("t");
        $end_of_month = new DateTime($month->format("Y-m-t"));
        // måste lägga till en extra dag för att datumintervallen
        // ska fungera av någon anledning
        $end_of_month->add(new DateInterval('P1D'));

        // fastställ hur månaden ska grupperas
        // P1D = 1 dag = dagsvis
        $day_interval = new DateInterval('P1D');

        // fastställ hur många grupperingar (av dagar) som ska förekomma i månaden
        // antalet dagar mellan första dagen i månaden och sista dagen i månaden
        $day_period = new DatePeriod($month, $day_interval, $end_of_month);

        // den totala bredden av månaden beror på antalet dagar
        // en fullösning...
        $width = $days_of_month*15;

        // skriv ut HTML-koden för respektive månad
        echo "<div class='month' style='width:".$width."px'>";
        echo   "<div class='dashes' style='width:".$width."px'></div>";
        echo   "<div class='startdate'>".$month->format("j M Y")."</div>";
        echo   "<div class='enddate'>".$month->format("t M")."</div>";
        echo   "<div class='clearfix'></div>";

        // loop för varje dag i månaden
        foreach ($day_period as $day) {
          // gör om till time för att kunna jämföra med andra datum
          $day_totime = strtotime($day->format("Y-m-d"));

          // klass för dagens div (booked_fm och/elelr booked_em)
          $class = "";

          // loop för alla bokningar av det aktuella klassrummet (se SQL query ovanför i koden)
          // avgör om den finns en eller flera överlappande bokning för respektive dag
          foreach ($bookings as $booking) {
            // gör om till time för att kunna jämföra datum
            $bk_startdate = strtotime($booking["booking_startdate"]);
            $bk_enddate = strtotime($booking["booking_enddate"]);
            $bk_timeperiod = $booking["timeperiod_id"];

            // om det finns en överlappande bokning: markera som "bokad" med css-klass
            if ($day_totime >= $bk_startdate && $day_totime <= $bk_enddate) {
              if ($bk_timeperiod == 1)
                $class .= " booked_fm";
              else if ($bk_timeperiod == 2)
                $class .= " booked_em";
            }
          }

          // skriv ut HTML-koden för respektive dag
          // de tomma divarna blir orange respektive röd om
          // de har css-klasserna booked_fm eller booked_em
          echo "<div class='day$class' id='".$day->format("Y-m-d")."'>";
          echo   "<div></div>";
          echo   "<div></div>";
          echo   "<span>".$day->format("j")."</span>";
          echo "</div>";
        }

        // avsluta diven för månad
        echo   "<div class='clearfix'></div>";
        echo "</div>";
      }

      // skriv ut länk för att visa fler månader i kalendern
      echo "<a href='?course=$course_id&classroom=$classroom_id&months=".($months_to_show+6)."'>[ Visa fler månader ]</a>";
      ?>

    </div>

  <?php } ?>

</div>

<div class="clearfix"></div>

<?php require("last_of_all.php"); ?>