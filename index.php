<?php
declare(strict_types=1);
?>
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
</head>
<?php

/**
 * 
 *  Refactored by Cristian Sorescu christian139601@gmail.com
 *  Dynamic code, introduced get_upcoming_events function.
 *
 *  Base code provided by Sarah Bailey.
 *  Case Western Reserve University, Cleveland OH.
 * 
 */

// TO DEBUG UNCOMMENT THESE LINES
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

// GOOGLE API PHP CLIENT LIBRARY
// https://github.com/google/google-api-php-client
//include(__DIR__.'/needed/autoload.php');

include(__DIR__.'/vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * get_upcoming_events gets upcoing events from a google calendar by id.
 *
 * @param $_G_API GOOGLE PUBLIC API key (server),
 *        from DEVELOPERS.GOOGLE.COM for API.
 * @param $_calendarId The calendar id, found in calendar settings.
 *        If your calendar is through google apps
 *        you may need to change the central sharing settings.
 *        The calendar for this script must have all events viewable in
 *        sharing settings.
 * @param $_max_events maximum number of events to be requested, default (4).
 * @return Google_Service_Calendar_Events object.
 */
function get_upcoming_events($_G_API, $_calendarId, $_max_events = 10) {
    // New Google client
    $client = new Google_Client();
    $client->setApplicationName("Calendar of Upcoming Events");
    $client->setDeveloperKey($_G_API);
    $cal = new Google_Service_Calendar($client);

    /*
       tell google how we want the events
       can't use time min without single events turned on,
       it says to treat recurring events as single events.
    */
    $params = array(
        'singleEvents' => true,
        'orderBy' => 'startTime',
        'timeMin' => date(DateTime::ATOM),
        'maxResults' => $_max_events
    );

    // return results
    return $cal->events->listEvents($_calendarId, $params);
}

/**
 * output_upcoming_events creates html element and outputs it.
 *
 * @param $events google calendar list of events to be printed.
 */
function output_upcoming_events($events) {
  $html_res = '';
  $calTimeZone = $events->timeZone;

  // iterate over the events
  foreach ($events->getItems() as $event) {
      // Convert date to month and day
      $eventDateStr = $event->start->dateTime;
      $eventDateEnd = $event->end->dateTime;

      if(empty($eventDateStr)) {
          // it's an all day event
          $eventDateStr = $event->start->date;
          $eventDateEnd = $event->end->date;
      }

      $temp_timezone = $event->start->timeZone;

      if (!empty($temp_timezone)) {
          $timezone = new DateTimeZone($temp_timezone);
      } else {
          // Set your default timezone in case your events don't have one
          $timezone = new DateTimeZone($calTimeZone);
      }

      $link = $event->htmlLink;

      // add tz to event link
      // prevents google from displaying everything in gmt
      $TZlink = $link . "&ctz=" . $calTimeZone;

      // https://www.php.net/manual/en/function.strftime.php

      $month_name = date_format(date_create($eventDateStr), 'M');
      $day_name = date_format(date_create($eventDateStr), 'D');
      $day_num = date_format(date_create($eventDateStr), 'd');
      $start_time = date_format(date_create($eventDateStr), 'g:i A');
      $end_time = date_format(date_create($eventDateEnd), 'g:i A');

      if(isset($event->attachments)) {
          $fileUrl = $event->attachments[0]->fileUrl;
          $url_components = parse_url($fileUrl);
          parse_str($url_components['query'], $params);
          $imageUrl = 'https://drive.google.com/uc?export=view&id='.$params['id'];
      } else {
          $imageUrl = 'http://dev.centralmidtown.org/wp-content/uploads/2022/09/cropped-central-midtown-icon_2-min.png';
      }
      // format html output ?>
          <style>
              .calendar_image img{
                  max-width: 100px;
              }
              @media (min-width: 576px){
                  .calendar_image{
                      max-width: 150px;
                  }
                  .calendar_image img{
                      max-width: 140px;
                  }
              }
          </style>
      <div class="row align-items-center border-bottom border-primary py-3">
          <div class="col-12 col-sm mb-3 text-center calendar_image" >
              <?php if(isset($imageUrl)){ ?>
              <img src="<?php echo($imageUrl);?>
              " class="img-fluid" />
          <?php } ?>
          </div>
          <div class="col-12 col-sm text-center calendar_image" style="max-width: 90px;">
              <span class="d-none d-sm-block fs-1"><?php echo($day_name); ?></span>
              <span class="d-inline d-sm-none"><?php echo($day_name); ?></span>
              <span class="d-inline"><?php echo($month_name); ?> <?php echo($day_num); ?></span>
          </div>
          <div class="col mx-2">
              <p class="mb-0" style="font-size: 1.4rem; @media (min-width: 576px){font-size: 1.1rem;}"><strong><?php echo($event->summary); ?></strong></p>
              <p><strong>Location:</strong> <?php echo($event->location); ?><br />
              <strong>Time:</strong> <?php echo($start_time); ?> - <?php echo($end_time); ?></p>

          </div>
      </div>
<?php
  }
}

// Usage:
$foo = get_upcoming_events($_ENV['API_KEY'], $_ENV['CALENDAR_ID'], 10);
output_upcoming_events($foo);

?>