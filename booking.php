<?php
require("first_of_all.php");

$classroom_id = $_GET["classroom"];
$months_to_show = isset($_GET["months"]) ? $_GET["months"] : 6;
?>

<script>
$(function(){
  /*$('#calendar').fullCalendar({
    weekends: false,
    events: [
        {
            title: 'My Event',
            start: '2013-12-05',
            description: 'This is a cool event'
        }
        
    ]
  });*/

  $('#classroom').bind('change', function(){
    if ($(this).val().length > 0)
      $('#selectform').submit();
  });

  $('#calendar .day').bind('click', function(){
    var booked_fm = $(this).hasClass('booked_fm');
    var booked_em = $(this).hasClass('booked_em');

    if (booked_fm && booked_em) {
      alert('Dagen är redan bokad');
      return;
    }

    $('.booking_start').removeClass('booking_start');
    $(this).addClass('booking_start');    

  });

});
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

  </form>

  <?php if (isset($classroom_id) && $classroom_id > 0) { ?>
    <form id="selectform" action="" method="GET">

      <h3>Välj period</h3>

      <input type="text" placeholder="Startdatum" />
      <input type="text" placeholder="Slutdatum" />

    </form>
  <?php } ?>

</div>

<div class="right">

  <?php if (isset($classroom_id) && $classroom_id > 0) { ?>
    <div id="calendar">

      <h3>Status för klassrum</h3>

      <div id="legend">
          <div class="booked_fm"></div>Förmiddag bokad (9-12)
          <div class="booked_em"></div>Eftermiddag bokad (13-16)
      </div>

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
        echo "<div class='dashes' style='width:".$width."px'></div>";
        echo "<div class='startdate'>".$month->format("j M Y")."</div>";
        echo "<div class='enddate'>".$month->format("t M")."</div>";
        echo "<div class='clearfix'></div>";

        foreach ($day_period as $day) {
          /*echo "<div class='day'>";

          $curr_date = strtotime($day->format("Y-m-d"));

          foreach ($bookings as $row) {
            $bk_startdate = strtotime($row["booking_startdate"]);
            $bk_enddate = strtotime($row["booking_enddate"]);
            $bk_timeperiod = $row["timeperiod_id"];
            //$class = 

            if ($curr_date >= $bk_startdate && $curr_date <= $bk_enddate) {
              if ($bk_timeperiod == 1)
                echo "<div class='booked_fm'></div>";
              else if ($bk_timeperiod == 2)
                echo "<div class='booked_em'></div>";
            }
          }*/

          $curr_date = strtotime($day->format("Y-m-d"));
          $class = "";

          foreach ($bookings as $row) {
            $bk_startdate = strtotime($row["booking_startdate"]);
            $bk_enddate = strtotime($row["booking_enddate"]);
            $bk_timeperiod = $row["timeperiod_id"];

            if ($curr_date >= $bk_startdate && $curr_date <= $bk_enddate) {
              if ($bk_timeperiod == 1)
                $class .= " booked_fm";
              else if ($bk_timeperiod == 2)
                $class .= " booked_em";
            }
          }

          echo "<div class='day$class'>";
          echo "<div></div>";
          echo "<div></div>";
          echo "<span>".$day->format("j")."</span>";
          echo "</div>";
        }

        echo "<div class='clearfix'></div>";
        echo "</div>";
      }

      echo "<a href='?classroom=$classroom_id&months=".($months_to_show+6)."'>[Visa fler månader]</a>";
      ?>
    </div>
  <?php } ?>

</div>

<div class="clearfix"></div>




<?php require("last_of_all.php"); ?>