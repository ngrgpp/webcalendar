<?php

function display_small_month ( $thismonth, $thisyear, $showyear ) {
  global $WEEK_START, $user, $login;

  if ( $user != $login && strlen ( $user ) > 0 )
    $u_url = "&user=$user";
  else
    $u_url = "";

  echo "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"2\">";
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
  else
    $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );

  $monthstart = mktime(2,0,0,$thismonth,1,$thisyear);
  $monthend = mktime(2,0,0,$thismonth + 1,0,$thisyear);
  echo "<TR><TD COLSPAN=7 ALIGN=\"center\">"
     . "<A HREF=\"month.php?year=$thisyear&month=$thismonth"
     . $u_url . "\" CLASS=\"monthlink\">";
  echo month_name ( $thismonth - 1 ) .
    "</A></TD></TR>";
  echo "<TR>";
  if ( $WEEK_START == 0 ) echo "<TD><FONT SIZE=\"-3\">" .
    weekday_short_name ( 0 ) . "</TD>";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<TD><FONT SIZE=\"-3\">" .
      weekday_short_name ( $i ) . "</TD>";
  }
  if ( $WEEK_START == 1 ) echo "<TD><FONT SIZE=\"-3\">" .
    weekday_short_name ( 0 ) . "</TD>";
  for ($i = $wkstart; date("Ymd",$i) <= date ("Ymd",$monthend);
    $i += (24 * 3600 * 7) ) {
    echo "<TR>";
    for ($j = 0; $j < 7; $j++) {
      $date = $i + ($j * 24 * 3600);
      if ( date("Ymd",$date) >= date ("Ymd",$monthstart) &&
        date("Ymd",$date) <= date ("Ymd",$monthend) ) {
        echo "<TD align=right><a href=\"day.php?year=" .
          date("Y", $date) . "&month=" .
          date("m", $date) . "&day=" . date("d", $date) . $u_url .
          "\" CLASS=\"dayofmonthyearview\">";
        echo "<FONT SIZE=\"-1\">" . date ( "j", $date ) .
          "</a></FONT></TD>";
      } else
        echo "<TD></TD>";
    }                 // end for $j
    echo "</TR>";
  }                         // end for $i
  echo "</TABLE>";
}

if ( empty ( $year ) )
  $year = date("Y");

$thisyear = $year;
if ( $year != date ( "Y") )
  $thismonth = 1;

if ( $year > "1903" )
  $prevYear = $year - 1;
else
  $prevYear=$year;

$nextYear= $year + 1;

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/user.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

?>

<HTML>

<HEAD>

<TITLE><?php etranslate ( "Title") ?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR=<?php echo "\"$BGCOLOR\"";?>>
<TABLE WIDTH="100%">
<TR>
<?php if ( ! $friendly ) { ?>
<TD ALIGN="left"><FONT SIZE="-1">
<A HREF="year.php?year=<?php echo $prevYear; if ( strlen ( $user ) > 0 ) echo "&user=$user";?>" CLASS="monthlink">&lt;&lt;<?php echo $prevYear?></A>
</FONT></TD>
<?php } ?>
<TD ALIGN="center">
<FONT SIZE="+2" COLOR="<?php echo $H2COLOR?>"><B>
<?php echo $thisyear ?>
</B></FONT>
<FONT COLOR="<?php echo $H2COLOR?>" SIZE="+1">
<?php
  if ( ! strlen ( $single_user_login ) ) {
    echo "<BR>\n";
    if ( strlen ( $lastname ) && strlen ( $firstname ) )
      echo "$firstname $lastname";
    else
      echo $login;
  }
?>
</FONT></TD>
<?php if ( ! $friendly ) { ?>
<TD ALIGN="right"><FONT SIZE="-1">
<A HREF="year.php?year=<?php echo $nextYear;; if ( strlen ( $user ) > 0 ) echo "&user=$user";?>" CLASS="monthlink"><?php echo $nextYear?>&gt;&gt;</A>
</FONT></TD>
<?php } ?>
</TR>
</TABLE>

<CENTER>
<TABLE BORDER="0" CELLSPACING="4" CELLPADDING="4">
<TR>
<TD VALIGN="top"><? display_small_month(1,$year,False); ?></TD>
<TD VALIGN="top"><? display_small_month(2,$year,False); ?></TD>
<TD VALIGN="top"><? display_small_month(3,$year,False); ?></TD>
<TD VALIGN="top"><? display_small_month(4,$year,False); ?></TD>
</TR>
<TR>
<TD VALIGN="top"><? display_small_month(5,$year,False); ?></TD>
<TD VALIGN="top"><? display_small_month(6,$year,False); ?></TD>
<TD VALIGN="top"><? display_small_month(7,$year,False); ?></TD>
<TD VALIGN="top"><? display_small_month(8,$year,False); ?></TD>
</TR>
<TR>
<TD VALIGN="top"><? display_small_month(9,$year,False); ?></TD>
<TD VALIGN="top"><? display_small_month(10,$year,False); ?></TD>
<TD VALIGN="top"><? display_small_month(11,$year,False); ?></TD>
<TD VALIGN="top"><? display_small_month(12,$year,False); ?></TD>
</TR>
</TABLE>
</CENTER>

<P>

<?php if ( ! $friendly ) {

display_unapproved_events ( $login );

?>
<P>
<A HREF="year.php?<?php
  if ( $thisyear )
    echo "year=$thisyear&";
  if ( $user != $login )
    echo "user=$user&";
?>friendly=1" TARGET="cal_printer_friendly"
onMouseOver="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</A>

<?php include "includes/trailer.inc"; ?>

<?php } ?>

</BODY>
</HTML>


