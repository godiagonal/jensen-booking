<?php
require 'db_con.php';

// array för att spara bokningar
$bookings = array();

$classroom_id = $_GET['classroom'];

// hämta bokningar för klassrummet från databasen (ej redan passerade)
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
            bk.classroom_id = $classroom_id";

// överför data från resultatet till array
if ($result = $db->query($query)) {
  while ($row = $result->fetch_assoc()) {

    $startdate = new DateTime($row['booking_startdate']);
    $enddate = new DateTime($row['booking_enddate']);
    $enddate->add(new DateInterval('P1D'));

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($startdate, $interval, $enddate);

    // dela upp bokningarna dagsvis genom att loopa igenom alla dagar
    // i datumspannet
    foreach ($period as $date) {
      $start = new DateTime($date->format('Y-m-d').' '.$row['timeperiod_start']);
      $end = new DateTime($date->format('Y-m-d').' '.$row['timeperiod_end']);

      // skapa en array innehållande boknignsinformation för respektive dag
      // varje $booking kommer att motsvara ett event i FullCalendar
      // ett event innehåller title, start och end
      $booking = array(
        'title' => $row['course_name'],
        'start' => $start->getTimestamp(),
        'end' => $end->getTimestamp(),
        'allDay' => false
      );

      // lägg till eventen i arrayen
      array_push($bookings, $booking);
    }
  }

  $result->free();
}

// omvandlda arrayen till json skriv ut resultatet
echo json_encode($bookings);

$db->close();
?>