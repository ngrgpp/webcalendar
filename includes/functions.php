<?php
/**
 * All of WebCalendar's functions
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */


/*
 * Functions start here.  All non-function code should be above this
 *
 * Note to developers:
 *  Documentation is generated from the function comments below.
 *  When adding/updating functions, please follow the following conventions
 *  seen below.  Your cooperation in this matter is appreciated :-)
 *
 *  If you want your documentation to link to the db documentation,
 *  just make sure you mention the db table name followed by "table"
 *  on the same line.  Here's an example:
 *    Retrieve preferences from the webcal_user_pref table.
 *
 */

/**
 * Gets the value resulting from an HTTP POST method.
 * 
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 * 
 * @param string $name Name used in the HTML form
 *
 * @return string The value used in the HTML form
 *
 * @see getGetValue
 */
function getPostValue ( $name ) {
  global $HTTP_POST_VARS;

  if ( isset ( $_POST ) && is_array ( $_POST ) && ! empty ( $_POST[$name] ) ) {
  $_POST[$name] = ( get_magic_quotes_gpc () != 0? $_POST[$name]: addslashes ( $_POST[$name]) );
   $HTTP_POST_VARS[$name] = $_POST[$name];
    return $_POST[$name];
  } else if ( ! isset ( $HTTP_POST_VARS ) ) {
    return null;
  } else if ( ! isset ( $HTTP_POST_VARS[$name] ) ) {
    return null;
 }
  return ( $HTTP_POST_VARS[$name] );
}

/**
 * Gets the value resulting from an HTTP GET method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * If you need to enforce a specific input format (such as numeric input), then
 * use the {@link getValue()} function.
 *
 * @param string $name Name used in the HTML form or found in the URL
 *
 * @return string The value used in the HTML form (or URL)
 *
 * @see getPostValue
 */
function getGetValue ( $name ) {
  global $HTTP_GET_VARS;

  if ( isset ( $_GET ) && is_array ( $_GET ) && ! empty ( $_GET[$name] ) ) {
  $_GET[$name] = ( get_magic_quotes_gpc () != 0? $_GET[$name]: addslashes ( $_GET[$name]) );
    $HTTP_GET_VARS[$name] = $_GET[$name];
  return $_GET[$name];
  } else if ( ! isset ( $HTTP_GET_VARS ) ) {
    return null;
  } else if ( ! isset ( $HTTP_GET_VARS[$name] ) ){
    return null;
 }
  return ( $HTTP_GET_VARS[$name] );
}

/**
 * Gets the value resulting from either HTTP GET method or HTTP POST method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * <b>Note:</b> If you need to get an integer value, yuou can use the
 * getIntValue function.
 *
 * @param string $name   Name used in the HTML form or found in the URL
 * @param string $format A regular expression format that the input must match.
 *                       If the input does not match, an empty string is
 *                       returned and a warning is sent to the browser.  If The
 *                       <var>$fatal</var> parameter is true, then execution
 *                       will also stop when the input does not match the
 *                       format.
 * @param bool   $fatal  Is it considered a fatal error requiring execution to
 *                       stop if the value retrieved does not match the format
 *                       regular expression?
 *
 * @return string The value used in the HTML form (or URL)
 *
 * @uses getGetValue
 * @uses getPostValue
 */
function getValue ( $name, $format='', $fatal=false ) {
  $val = getPostValue ( $name );
  if ( ! isset ( $val ) )
    $val = getGetValue ( $name );
  // for older PHP versions...
  if ( ! isset ( $val  ) && get_magic_quotes_gpc () == 1 &&
    ! empty ( $GLOBALS[$name] ) )
    $val = $GLOBALS[$name];
  if ( ! isset ( $val  ) )
    return '';
  if ( ! empty ( $format ) && ! preg_match ( "/^" . $format . "$/", $val ) ) {
    // does not match
    if ( $fatal ) {
      die_miserable_death ( 'Fatal Error: Invalid data format for' . $name );
    }
    // ignore value
    return '';
  }
  return $val;
}

/**
 * Gets an integer value resulting from an HTTP GET or HTTP POST method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * @param string $name  Name used in the HTML form or found in the URL
 * @param bool   $fatal Is it considered a fatal error requiring execution to
 *                      stop if the value retrieved does not match the format
 *                      regular expression?
 *
 * @return string The value used in the HTML form (or URL)
 *
 * @uses getValue
 */
function getIntValue ( $name, $fatal=false ) {
  $val = getValue ( $name, "-?[0-9]+", $fatal );
  return $val;
}

/**
 * Loads default system settings (which can be updated via admin.php).
 *
 * System settings are stored in the webcal_config table.
 *
 * <b>Note:</b> If the setting for <var>server_url</var> is not set, the value
 * will be calculated and stored in the database.
 *
 * @global string User's login name
 * @global bool   Readonly
 * @global string HTTP hostname
 * @global int    Server's port number
 * @global string Request string
 * @global array  Server variables
 */
function load_global_settings () {
  global $login, $readonly, $HTTP_HOST, $SERVER_PORT, $REQUEST_URI, $_SERVER;
  global $SERVER_URL, $APPLICATION_NAME, $FONTS, $LANGUAGE;
  // Note: when running from the command line (send_reminders.php),
  // these variables are (obviously) not set.
  // TODO: This type of checking should be moved to a central location
  // like init.php.
  if ( isset ( $_SERVER ) && is_array ( $_SERVER ) ) {
    if ( empty ( $HTTP_HOST ) && isset ( $_SERVER['HTTP_HOST'] ) )
      $HTTP_HOST = $_SERVER['HTTP_HOST'];
    if ( empty ( $SERVER_PORT ) && isset ( $_SERVER['SERVER_PORT'] ) )
      $SERVER_PORT = $_SERVER['SERVER_PORT'];
    if( !isset($_SERVER['REQUEST_URI'] ) ) {
      $arr = explode( '/', $_SERVER['PHP_SELF'] );
      $_SERVER['REQUEST_URI'] = '/' . $arr[count($arr)-1];
      if ( isset ( $_SERVER['argv'][0] ) && $_SERVER['argv'][0]!='')
        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['argv'][0];
    }
    if ( empty ( $REQUEST_URI ) && isset ( $_SERVER['REQUEST_URI'] ) )
      $REQUEST_URI = $_SERVER['REQUEST_URI'];
  }

  $rows = dbi_get_cached_rows (
    'SELECT cal_setting, cal_value FROM webcal_config' );
  for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
    $row = $rows[$i];
    $setting = $row[0];
    $value = $row[1];
    $GLOBALS[$setting] = $value;
  }
 
 
  // Set SERVER TIMEZONE 
  if ( empty ( $GLOBALS['TIMEZONE'] ) )
    $GLOBALS['TIMEZONE'] = $GLOBALS['SERVER_TIMEZONE']; 
  set_env ( 'TZ', $GLOBALS['TIMEZONE'] ); 
  
  // If app name not set.... default to "Title".  This gets translated
  // later since this function is typically called before translate.php
  // is included.
  // Note: We usually use translate($APPLICATION_NAME) instead of
  // translate('Title').
  if ( empty ( $APPLICATION_NAME ) )
    $APPLICATION_NAME = 'Title';

  // If $SERVER_URL not set, then calculate one for them, then store it
  // in the database.
  if ( empty ( $SERVER_URL ) ) {
    if ( ! empty ( $HTTP_HOST ) && ! empty ( $REQUEST_URI ) ) {
      $ptr = strrpos ( $REQUEST_URI, '/' );
      if ( $ptr > 0 ) {
        $uri = substr ( $REQUEST_URI, 0, $ptr + 1 );
        $SERVER_URL = 'http://' . $HTTP_HOST;
        if ( ! empty ( $SERVER_PORT ) && $SERVER_PORT != 80 )
          $SERVER_URL .= ':' . $SERVER_PORT;
        $SERVER_URL .= $uri;

        dbi_execute ( 'INSERT INTO webcal_config ( cal_setting, cal_value ) '.
          'VALUES ( ?, ? )', array( 'SERVER_URL', $SERVER_URL ) );
      }
    }
  }

  // If no font settings, then set some
  if ( empty ( $FONTS ) ) {
  $FONTS = ( $LANGUAGE == 'Japanese' ? 'Osaka, ' : '' ) . 
    'Arial, Helvetica, sans-serif';
  }
}

/**
 * Gets the list of active plugins.
 *
 * Should be called after {@link load_global_settings()} and {@link load_user_preferences()}.
 *
 * @internal cek: ignored since I am not sure this will ever be used...
 *
 * @return array Active plugins
 *
 * @ignore
 */
function get_plugin_list ( $include_disabled=false ) {
  global $error;
  // first get list of available plugins
  $sql = 'SELECT cal_setting FROM webcal_config ' .
    "WHERE cal_setting LIKE '%.plugin_status'";
  if ( ! $include_disabled )
    $sql .= " AND cal_value = 'Y'";
  $sql .= ' ORDER BY cal_setting';
  $res = dbi_execute ( $sql );
  $plugins = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $e = explode ( '.', $row[0] );
      if ( $e[0] != '' ) {
        $plugins[] = $e[0];
      }
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ( true);
  }
  if ( count ( $plugins ) == 0 ) {
    $plugins[] = 'webcalendar';
  }
  return $plugins;
}

/**
 * Get plugins available to the current user.
 *
 * Do this by getting a list of all plugins that are not disabled by the
 * administrator and make sure this user has not disabled any of them.
 * 
 * It's done this was so that when an admin adds a new plugin, it shows up on
 * each users system automatically (until they disable it).
 *
 * @return array Plugins available to current user
 *
 * @ignore
 */
function get_user_plugin_list () {
  $ret = array ();
  $all_plugins = get_plugin_list ();
  for ( $i = 0; $i < count ( $all_plugins ); $i++ ) {
    if ( $GLOBALS[$all_plugins[$i] . '.disabled'] != 'N' )
      $ret[] = $all_plugins[$i];
  }
  return $ret;
}

/**
 * Identify user's browser.
 *
 * Returned value will be one of:
 * - "Mozilla/5" = Mozilla (open source Mozilla 5.0)
 * - "Mozilla/[3,4]" = Netscape (3.X, 4.X)
 * - "MSIE 4" = MSIE (4.X)
 *
 * @return string String identifying browser
 *
 * @ignore
 */
function get_web_browser () {
  if ( ereg ( "MSIE [0-9]", getenv ( 'HTTP_USER_AGENT' ) ) )
    return 'MSIE';
  if ( ereg ( "Mozilla/[234]", getenv ( 'HTTP_USER_AGENT' ) ) )
    return 'Netscape';
  if ( ereg ( "Mozilla/[5678]", getenv ( "HTTP_USER_AGENT" ) ) )
    return 'Mozilla';
  return 'Unknown';
}


/**
 * Logs a debug message.
 *
 * Generally, we do not leave calls to this function in the code.  It is used
 * for debugging only.
 *
 * @param string $msg Text to be logged
 */
function do_debug ( $msg ) {
  // log to /tmp/webcal-debug.log
  //error_log ( date ( 'Y-m-d H:i:s' ) .  "> $msg\n<br />",
  //3, 'd:/php/logs/debug.txt' );
  //fwrite ( $fd, date ( 'Y-m-d H:i:s' ) .  "> $msg\n" );
  //fclose ( $fd );
  //  3, '/tmp/webcal-debug.log' );
  //error_log ( date ( 'Y-m-d H:i:s' ) .  "> $msg\n",
  //  2, 'sockieman:2000' );
}

/**
 * Gets user's preferred view.
 *
 * The user's preferred view is stored in the $STARTVIEW global variable.  This
 * is loaded from the user preferences (or system settings if there are no user
 * prefererences.)
 *
 * @param string $indate Date to pass to preferred view in YYYYMMDD format
 * @param string $args   Arguments to include in the URL (such as "user=joe")
 *
 * @return string URL of the user's preferred view
 */
function get_preferred_view ( $indate='', $args='' ) {
  global $STARTVIEW, $thisdate, $ALLOW_VIEW_OTHER, $is_admin;
    
  //we want user's to set  their pref on first login
  if ( empty ( $STARTVIEW ) ) return false;
  
  $url = $STARTVIEW;
  // We used to just store "month" in $STARTVIEW without the ".php"
  // This is just to prevent users from getting a "404 not found" if
  // they have not updated their preferences.
  $url .= ( ! strpos( $STARTVIEW, '.php' ) ? '.php' : '' );
  
  //prevent endless looping if preferred view is custom and viewing
  //others is not allowed
  if ( substr( $url, 0, 5 ) == 'view_' && $ALLOW_VIEW_OTHER == 'N' && ! $is_admin ) {
    $url = 'month.php';
  }
 
  if ( ! access_can_view_page ( $url ) ) {
    if ( access_can_access_function ( ACCESS_WEEK ) )
      $url = 'week.php';
    else if ( access_can_access_function ( ACCESS_MONTH ) )
      $url = 'month.php';
    else if ( access_can_access_function ( ACCESS_DAY ) )
      $url = 'day.php';
    // At this point, this user cannot view the preferred view in their
    // preferences (and they cannot update their preferences), and they
    // cannot view any of the standard day/week/month/year pages.
    // All that's left is a custom view that is either created by them
    // or a global view.
    if ( count ( $views ) > 0 )
      $url = $views[0]['url'];
  }

  $url = str_replace ( '&amp;', '&', $url );
  $url = str_replace ( '&', '&amp;', $url );

  $xdate = empty ( $indate ) ? $thisdate : $indate;
  $url .= ( ! empty( $xdate )? ( strstr( $url, '?' ) ? '&amp;' :'?' ). "date=$xdate" : '' );
  $url .= ( ! empty( $args ) ? ( strstr( $url, '?' ) ? '&amp;' :'?' ) . $args : '' );

  return $url;
}

/**
 * Sends a redirect to the user's preferred view.
 *
 * The user's preferred view is stored in the $STARTVIEW global variable.  This
 * is loaded from the user preferences (or system settings if there are no user
 * prefererences.)
 *
 * @param string $indate Date to pass to preferred view in YYYYMMDD format
 * @param string $args   Arguments to include in the URL (such as "user=joe")
 */
function send_to_preferred_view ( $indate='', $args='' ) {
  $url = get_preferred_view ( $indate, $args );
  do_redirect ( $url );
}

/** 
 * Sends a redirect to the specified page.
 * The database connection is closed and execution terminates in this function.
 *
 * <b>Note:</b> MS IIS/PWS has a bug in which it does not allow us to send a
 * cookie and a redirect in the same HTTP header.  When we detect that the web
 * server is IIS, we accomplish the redirect using meta-refresh.  See the
 * following for more info on the IIS bug:
 *
 * {@link http://www.faqts.com/knowledge_base/view.phtml/aid/9316/fid/4}
 *
 * @param string $url The page to redirect to.  In theory, this should be an
 *                    absolute URL, but all browsers accept relative URLs (like
 *                    "month.php").
 *
 * @global string   Type of webserver
 * @global array    Server variables
 * @global resource Database connection
 */
function do_redirect ( $url ) {
  global $SERVER_SOFTWARE, $_SERVER, $c;

  // Replace any '&amp;' with '&' since we don't want that in the HTTP
  // header.
  $url = str_replace ( '&amp;', '&', $url );

  if ( empty ( $SERVER_SOFTWARE ) )
    $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];
  if ( ( substr ( $SERVER_SOFTWARE, 0, 5 ) == "Micro" ) ||
    ( substr ( $SERVER_SOFTWARE, 0, 3 ) == "WN/" ) ) {
    $meta = ' <meta http-equiv="refresh" content="0; url=' . $url .'" />';
  } else {
    $meta = '';
    Header( "Location: $url" );
  }
  echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" .
    "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
    "\"DTD/xhtml1-transitional.dtd\">\n" .
    "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n" .
    "<head>\n<title>Redirect</title>\n" . $meta .  "</head><body>\n" .
      "Redirecting to.. <a href=\"" . $url . "\">here</a>.</body>\n</html>";
  dbi_close ( $c );
  exit;
}

/**
 * Sends an HTTP login request to the browser and stops execution.
 */
function send_http_login () {
  global $lang_file, $APPLICATION_NAME;

  if ( strlen ( $lang_file ) ) {
    $title = translate( 'Title' );
    $unauthorized = translate( 'Unauthorized' );
    $not_authorized = translate( 'You are not authorized' );
  } else {
    $title = 'Webcalendar';
    $unauthorized = 'Unauthorized';
    $not_authorized = 'You are not authorized';
  }
  Header ( "WWW-Authenticate: Basic realm=\"$title\"");
  Header ( 'HTTP/1.0 401 Unauthorized' );
  echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" .
    "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
    "\"DTD/xhtml1-transitional.dtd\">\n" .
    "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n" .
    "<head>\n<title>$unauthorized</title>\n</head>\n<body>\n" .
    "<h2>$title</h2>\n$not_authorized\n</body>\n</html>";
  exit;
}

/**
 * Generates a cookie that saves the last calendar view.
 *
 * Cookie is based on the current <var>$REQUEST_URI</var>.
 *
 * We save this cookie so we can return to this same page after a user
 * edits/deletes/etc an event.
 *
 * @global string Request string
 */
function remember_this_view ( $view=false ) {
  global $REQUEST_URI;
  if ( empty ( $REQUEST_URI ) )
    $REQUEST_URI = $_SERVER['REQUEST_URI'];

  // if called from init, only process script named "view_x.php
  if ( $view == true && ! strstr ( $REQUEST_URI, 'view_' ) )
    return;

  // do not use anything with friendly in the URI
  if ( strstr ( $REQUEST_URI, 'friendly=' ) )
    return;

  SetCookie ( 'webcalendar_last_view', $REQUEST_URI );
}

/**
 * Gets the last page stored using {@link remember_this_view()}.
 *
 * @return string The URL of the last view or an empty string if it cannot be
 *                determined.
 *
 * @global array Cookies
 */
function get_last_view () {
  global $HTTP_COOKIE_VARS;
  $val = '';

 if ( isset ( $_COOKIE['webcalendar_last_view'] ) ) {
    $val = $HTTP_COOKIE_VARS['webcalendar_last_view'] = $_COOKIE['webcalendar_last_view'];
  } else if ( isset ( $HTTP_COOKIE_VARS['webcalendar_last_view'] ) ) {
    $val = $HTTP_COOKIE_VARS['webcalendar_last_view'];
 }
  $val =   str_replace ( "&", "&amp;", $val );
  SetCookie ( 'webcalendar_last_view', '', 0 );

  return $val;
}

/**
 * Sends HTTP headers that tell the browser not to cache this page.
 *
 * Different browser use different mechanisms for this, so a series of HTTP
 * header directives are sent.
 *
 * <b>Note:</b> This function needs to be called before any HTML output is sent
 * to the browser.
 */
function send_no_cache_header () {
  header ( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
  header ( 'Last-Modified: ' . gmdate ( 'D, d M Y H:i:s' ) . ' GMT' );
  header ( 'Cache-Control: no-store, no-cache, must-revalidate' );
  header ( 'Cache-Control: post-check=0, pre-check=0', false );
  header ( 'Pragma: no-cache' );
}

/**
 * Loads the current user's preferences as global variables from the webcal_user_pref table.
 *
 * Also loads the list of views for this user (not really a preference, but
 * this is a convenient place to put this...)
 *
 * <b>Notes:</b>
 * - If <var>$ALLOW_COLOR_CUSTOMIZATION</var> is set to 'N', then we ignore any
 *   color preferences.
 * - Other default values will also be set if the user has not saved a
 *   preference and no global value has been set by the administrator in the
 *   system settings.
 */
function load_user_preferences ( $guest='') {
  global $login, $browser, $views, $prefarray, $is_assistant,
    $DATE_FORMAT_MY, $DATE_FORMAT, $DATE_FORMAT_MD, $DATE_FORMAT_TASK,
    $LANGUAGE, $lang_file, $has_boss, $user, $is_nonuser_admin, 
    $ALLOW_COLOR_CUSTOMIZATION;
  $lang_found = false;
  $colors = array (
    'BGCOLOR' => 1,
    'H2COLOR' => 1,
    'THBG' => 1,
    'THFG' => 1,
    'CELLBG' => 1,
    'TODAYCELLBG' => 1,
    'WEEKENDBG' => 1,
    'OTHERMONTHBG' => 1,
    'POPUP_BG' => 1,
    'POPUP_FG' => 1,
  );
 
  //allow __public__ pref to be used if logging in or user not validated
  $tmp_login = ( ! empty( $guest )? 
    ( $guest == 'guest' ? '__public__' : $guest ) : $login );
  
  $browser = get_web_browser ();
  $browser_lang = get_browser_language ();
  $prefarray = array ();

  $rows = dbi_get_cached_rows (
    'SELECT cal_setting, cal_value FROM webcal_user_pref ' .
    'WHERE cal_login = ?', array( $tmp_login ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $setting = $row[0];
      $value = $row[1];
      if ( $setting == 'LANGUAGE' )
        $lang_found = true;
      if ( $ALLOW_COLOR_CUSTOMIZATION == 'N' ) {
        if ( isset ( $colors[$setting] ) )
          continue;
      }
      $sys_setting = 'sys_' . $setting;
      // save system defaults
      if ( ! empty ( $GLOBALS[$setting] ) )
        $GLOBALS['sys_' . $setting] = $GLOBALS[$setting];
      $GLOBALS[$setting] = $value;
      $prefarray[$setting] = $value;
    } 
  }
  
  //set users timezone
  if ( isset ( $GLOBALS['TIMEZONE'] ) ) 
    set_env ( 'TZ', $GLOBALS['TIMEZONE'] );
  
  // get views for this user and global views
  $rows = dbi_get_cached_rows (
    'SELECT cal_view_id, cal_name, cal_view_type, cal_is_global ' .
    'FROM webcal_view ' .
    "WHERE cal_owner = ? OR cal_is_global = 'Y' " .
    'ORDER BY cal_name', array( $tmp_login ) );
  if ( $rows ) {
    $views = array ();
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      if ( $row[2] == 'S' )
        $url = "view_t.php?timeb=1&amp;id=$row[0]";
      else if ( $row[2] == 'T' )
        $url = "view_t.php?timeb=0&amp;id=$row[0]";
      else if ( $row[2] == 'E' )
        $url = "view_r.php?id=$row[0]";
      else
        $url = 'view_' . strtolower ( $row[2] ) . ".php?id=$row[0]";
      $v = array (
        'cal_view_id' => $row[0],
        'cal_name' => $row[1],
        'cal_view_type' => $row[2],
        'cal_is_global' => $row[3],
        'url' => $url
        );
      $views[] = $v;
    }
  }

  // If user has not set a language preference or admin has not specified a
  // language, then use their browser
  // settings to figure it out, and save it in the database for future
  // use (email reminders).
  $lang = 'none';
  if ( ! $lang_found && strlen ( $tmp_login ) && $tmp_login != '__public__' ) {
    if ( $LANGUAGE == 'none' ) {
      $lang =  $browser_lang; 
    }
    dbi_execute ( 'INSERT INTO webcal_user_pref ' .
      '( cal_login, cal_setting, cal_value ) VALUES ' .
      '( ?, ?, ? )', array( $tmp_login, 'LANGUAGE', $lang ) );
  }
  reset_language ( ! empty ( $LANGUAGE) && $LANGUAGE != 'none'? $LANGUAGE : $browser_lang );
  if (  empty ( $DATE_FORMAT ) || $DATE_FORMAT == 'LANGUAGE_DEFINED' ){
    $DATE_FORMAT = translate ( '__month__ __dd__, __yyyy__' );
  }
  if ( empty ( $DATE_FORMAT_MY ) || $DATE_FORMAT_MY == 'LANGUAGE_DEFINED' ){  
    $DATE_FORMAT_MY = translate ( '__month__ __yyyy__' );  
  }
  if ( empty ( $DATE_FORMAT_MD ) || $DATE_FORMAT_MD == 'LANGUAGE_DEFINED' ){  
    $DATE_FORMAT_MD = translate ( '__month__ __dd__' );  
  }
  if ( empty ( $DATE_FORMAT_TASK ) || $DATE_FORMAT_TASK == 'LANGUAGE_DEFINED' ){  
    $DATE_FORMAT_TASK = translate ( '__mm__/__dd__/__yyyy__' );  
  }
    
  $is_assistant = empty ( $user ) ? false :
    user_is_assistant ( $tmp_login, $user );
  $has_boss = user_has_boss ( $tmp_login );
  $is_nonuser_admin = ($user) ? user_is_nonuser_admin ( $tmp_login, $user ) : false;
  //if ( $is_nonuser_admin ) load_nonuser_preferences ($user);
}

/**
 * Gets the list of external users for an event from the 
 *  webcal_entry_ext_user table in an HTML format.
 *
 * @param int $event_id   Event ID
 * @param int $use_mailto When set to 1, email address will contain an href
 *                        link with a mailto URL.
 *
 * @return string The list of external users for an event formated in HTML.
 */
function event_get_external_users ( $event_id, $use_mailto=0 ) {
  global $error;
  $ret = '';

  $rows = dbi_get_cached_rows ( 'SELECT cal_fullname, cal_email ' .
    'FROM webcal_entry_ext_user WHERE cal_id = ? ' .
    'ORDER by cal_fullname', array( $event_id ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      if ( strlen ( $ret ) ) {
        $ret .= "\n";
      }
      // Remove [\d] if duplicate name
      $ret .= trim( preg_replace( '/\[[\d]]/' , '', $row[0] ) );
      if ( strlen ( $row[1] ) ) {
        $row_one = htmlentities( " <$row[1]>" );
        $ret .= ( $use_mailto ? " <a href=\"mailto:$row[1]\">$row_one</a>" : $row_one );
      }
    }
  }
  return $ret;
}


/**
 * Adds something to the activity log for an event.
 *
 * The information will be saved to the webcal_entry_log table.
 *
 * @param int    $event_id Event ID
 * @param string $user     Username of user doing this
 * @param string $user_cal Username of user whose calendar is affected
 * @param string $type     Type of activity we are logging:
 *   - LOG_CREATE
 *   - LOG_APPROVE
 *   - LOG_REJECT
 *   - LOG_UPDATE
 *   - LOG_DELETE
 *   - LOG_CREATE_T
 *   - LOG_APPROVE_T
 *   - LOG_REJECT_T
 *   - LOG_UPDATE_T
 *   - LOG_DELETE_T
 *   - LOG_NOTIFICATION
 *   - LOG_REMINDER
 *   - LOG_ATTACHMENT
 *   - LOG_COMMENT
 * @param string $text     Text comment to add with activity log entry
 */
function activity_log ( $event_id, $user, $user_cal, $type, $text ) {
  $next_id = 1;

  if ( empty ( $type ) ) {
    echo 'Error: type not set for activity log!';
    // but don't exit since we may be in mid-transaction
    return;
  }

  $res = dbi_execute ( 'SELECT MAX(cal_log_id) FROM webcal_entry_log' );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $next_id = $row[0] + 1;
    }
    dbi_free_result ( $res );
  }
  $date = gmdate ( 'Ymd' );
  $time = gmdate ( 'Gis' );
  $sql_text = empty ( $text ) ? NULL : $text;
  
  $sql_user_cal = empty ( $user_cal ) ? NULL : $user_cal;
  $sql = 'INSERT INTO webcal_entry_log ( ' .
    'cal_log_id, cal_entry_id, cal_login, cal_user_cal, cal_type, ' .
    'cal_date, cal_time, cal_text ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )';
  if ( ! dbi_execute ( $sql, array( $next_id, $event_id, $user, $sql_user_cal, $type, $date, $time, $sql_text ) ) ) {
    db_error ( true, $sql);
  }
}

/**
 * Gets a list of users.
 *
 * If groups are enabled, this will restrict the list of users to only those
 * users who are in the same group(s) as the user (unless the user is an admin
 * user).  We allow admin users to see all users because they can also edit
 * someone else's events (so they may need access to users who are not in the
 * same groups that they are in).
 *
 * If user access control is enabled, then we also check to see if this
 * user is allowed to view each user's calendar.  If not, then that user
 * is not included in the list.
 *
 * @return array Array of users, where each element in the array is an array
 *               with the following keys:
 *    - cal_login
 *    - cal_lastname
 *    - cal_firstname
 *    - cal_is_admin
 *    - cal_email
 *    - cal_password
 *    - cal_fullname
 */
function get_my_users ( $user='', $reason='invite') {
  global $login, $is_admin, $GROUPS_ENABLED, $USER_SEES_ONLY_HIS_GROUPS;
  global $my_user_array, $is_nonuser, $is_nonuser_admin;

  $this_user = ( ! empty ( $user ) ? $user : $login );
  // Return the global variable (cached)
  if ( ! empty ( $my_user_array[$this_user] ) && is_array ( $my_user_array ) )
    return $my_user_array[$this_user];

  if ( $GROUPS_ENABLED == 'Y' && $USER_SEES_ONLY_HIS_GROUPS == 'Y' &&
    ! $is_admin ) {
    // get groups that current user is in
    $rows = dbi_get_cached_rows ( 'SELECT cal_group_id FROM webcal_group_user ' .
      'WHERE cal_login = ?', array( $login ) );
    $groups = array ();
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $groups[] = $row[0];
      }
    }
    $groupcnt = count ( $groups );
    // Nonuser (public) can only see themself (unless access control is on)
    if ( $is_nonuser && ! access_is_enabled () ) {
      return array ( $this_user );
    }
    $u = user_get_users ();
    if ( $is_nonuser_admin ) {
      $nonusers = get_nonuser_cals ();
      $u = array_merge( $nonusers, $u );
    }
    $u_byname = array ();
    for ( $i = 0, $cnt = count ( $u ); $i < $cnt; $i++ ) {
      $name = $u[$i]['cal_login'];
      $u_byname[$name] = $u[$i];
    }
    $ret = array ();
    if ( $groupcnt == 0 ) {
      // Eek.  User is in no groups... Return only themselves
      if ( isset ( $u_byname[$this_user] ) ) $ret[] = $u_byname[$this_user];
      $my_user_array[$this_user] = $ret;
      return $ret;
    }
    // get list of users in the same groups as current user
    $sql = 'SELECT DISTINCT(webcal_group_user.cal_login), cal_lastname, cal_firstname ' .
      'FROM webcal_group_user ' .
      'LEFT JOIN webcal_user ON webcal_group_user.cal_login = webcal_user.cal_login ' .
      'WHERE cal_group_id ';
    if ( $groupcnt == 1 )
      $sql .= '= ?';
    else {
    // build count( $groups ) placeholders separated with commas
    $placeholders = '';
    for ( $p_i = 0; $p_i < $groupcnt; $p_i++ ) {
        $placeholders .= ( $p_i == 0 ) ? '?': ', ?';
    }
      $sql .= "IN ( $placeholders )";
    }
    $sql .= ' ORDER BY cal_lastname, cal_firstname, webcal_group_user.cal_login';
    $rows = dbi_get_cached_rows ( $sql, $groups );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        if ( isset ( $u_byname[$row[0]] ) ) $ret[] = $u_byname[$row[0]];
      }
    }
  } else {
    // groups not enabled... return all users
    $ret = user_get_users ();
  }

  // If user access control enabled, remove any users that this user
  // does not have required access.
  if ( access_is_enabled () ) {
    $newlist = array ();
    for ( $i = 0, $cnt = count ( $ret ); $i < $cnt; $i++ ) {
      $can_list = access_user_calendar ( $reason, $ret[$i]['cal_login'], $this_user );
      if (  $can_list == 'Y' ||  $can_list > 0 ) {
        $newlist[] = $ret[$i];
      }
    }
    $ret = $newlist;
  }

  $my_user_array[$this_user] = $ret;
  return $ret;
}

/**
 * Gets a preference setting for the specified user.
 *
 * If no value is found in the database, then the system default setting will
 * be returned.
 *
 * @param string $user    User login we are getting preference for
 * @param string $setting Name of the setting
 *
 * @return string The value found in the webcal_user_pref table for the
 *                specified setting or the sytem default if no user settings
 *                was found.
 */
function get_pref_setting ( $user, $setting ) {
  $ret = '';
  // set default 
  if ( ! isset ( $GLOBALS['sys_' .$setting] ) ) {
    // this could happen if the current user has not saved any pref. yet
    if ( ! empty ( $GLOBALS[$setting] ) )
      $ret = $GLOBALS[$setting];
  } else {
    $ret = $GLOBALS['sys_' .$setting];
  }

  $sql = 'SELECT cal_value FROM webcal_user_pref ' .
    'WHERE cal_login = ? AND cal_setting = ?';
  $rows = dbi_get_cached_rows ( $sql, array( $user, $setting ) );
  if ( $rows ) {
    $row = $rows[0];
    if ( $row && ! empty ( $row[0] ) )
      $ret = $row[0];
  }
  return $ret;
}



/**
 * Loads current user's layer info into layer global variable.
 *
 * If the system setting <var>$ALLOW_VIEW_OTHER</var> is not set to 'Y', then
 * we ignore all layer functionality.  If <var>$force</var> is 0, we only load
 * layers if the current user preferences have layers turned on.
 *
 * @param string $user  Username of user to load layers for
 * @param int    $force If set to 1, then load layers for this user even if
 *                      user preferences have layers turned off.
 */
function load_user_layers ($user='',$force=0) {
  global $login, $layers, $LAYERS_STATUS, $ALLOW_VIEW_OTHER;

  if ( $user == '' )
    $user = $login;

  $layers = array ();

  if ( empty ( $ALLOW_VIEW_OTHER ) || $ALLOW_VIEW_OTHER != 'Y' )
    return; // not allowed to view others' calendars, so cannot use layers

  if ( $force || ( ! empty ( $LAYERS_STATUS ) && $LAYERS_STATUS != 'N' ) ) {
    $rows = dbi_get_cached_rows (
      'SELECT cal_layerid, cal_layeruser, cal_color, cal_dups ' .
      'FROM webcal_user_layers ' .
      'WHERE cal_login = ? ORDER BY cal_layerid', array( $user ) );
    if ( $rows ) {
      $count = 1;
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $layers[$row[0]] = array (
          'cal_layerid' => $row[0],
          'cal_layeruser' => $row[1],
          'cal_color' => $row[2],
          'cal_dups' => $row[3]
        );
        $count++;
      }
    }
  } else {
    //Not loading
  }
}

/**
 * Formats site_extras for display according to their type.
 *
 * This will return an array containing formatted extras indexed on their
 * unique names. Each formatted extra is another array containing two
 * indices: 'name' and 'data', which hold the name of the site_extra and the
 * formatted data, respectively. So, to access the name and data of an extra
 * uniquely name 'Reminder', you would access
 * <var>$array['Reminder']['name']</var> and
 * <var>$array['Reminder']['data']</var>
 *
 * @param array $extras Array of site_extras for an event as returned by
 *                      {@link get_site_extra_fields()}
 *
 * @return array Array of formatted extras.
 */
function format_site_extras ( $extras ) {
  global $SITE_EXTRAS_IN_POPUP, $site_extras;

  if ( empty ($site_extras ) ) return;
  $ret = array();

  foreach ( $site_extras as $site_extra ) {
    $data = '';
    $extra_name = $site_extra[0];
    $extra_desc = $site_extra[1];
    $extra_type = $site_extra[2];
    $extra_arg1 = $site_extra[3];
    $extra_arg2 = $site_extra[4];

    if ( ! empty ( $extras[$extra_name] ) && 
      ! empty ( $extras[$extra_name]['cal_name'] ) ) {

      $name = translate ( $extra_desc );

      if ( $extra_type == EXTRA_DATE ) {

        if ( $extras[$extra_name]['cal_date'] > 0 ) {
          $data = date_to_str ( $extras[$extra_name]['cal_date'] );
        }

      } else if ( $extra_type == EXTRA_TEXT || 
        $extra_type == EXTRA_MULTILINETEXT ) {

        $data = nl2br ( $extras[$extra_name]['cal_data'] );

      } else {
        $data .= $extras[$extra_name]['cal_data'];
      }

      $ret[$extra_name] = array ( 'name' => $name, 'data' => $data );
    }
  }

  return $ret;
}

/**
 * Generates the HTML used in an event popup for the site_extras fields of an event.
 *
 * @param int $id Event ID
 *
 * @return string The HTML to be used within the event popup for any site_extra
 *                fields found for the specified event
 */
function site_extras_for_popup ( $id ) {
  global $SITE_EXTRAS_IN_POPUP;
  
  if ( $SITE_EXTRAS_IN_POPUP != 'Y' ) {
    return '';
  }

  $extras = format_site_extras ( get_site_extra_fields ( $id ) );
  if ( empty ( $extras ) ) return '';
  
  $ret = '';

  foreach ( $extras as $extra ) {
    $ret .= '<dt>' . $extra['name'] . ":</dt>\n<dd>" . $extra['data'] . "</dd>\n";
  }

  return $ret;
}

/**
 * Builds the HTML for the entry popup.
 *
 * @param string $popupid     CSS id to use for event popup
 * @param string $user        Username of user the event pertains to
 * @param string $description Event description
 * @param string $time        Time of the event (already formatted in a display format)
 * @param string $site_extras HTML for any site_extras for this event
 *
 * @return string The HTML for the event popup
 */
function build_entry_popup ( $popupid, $user, $description='', $time,
  $site_extras='', $location='', $name='', $id='', $reminder='' ) {
  global $login, $popup_fullnames, $popuptemp_fullname, $DISABLE_POPUPS,
    $ALLOW_HTML_DESCRIPTION, $SUMMARY_LENGTH, $PARTICIPANTS_IN_POPUP,
    $tempfullname;
  
 if ( ! empty ( $DISABLE_POPUPS ) && $DISABLE_POPUPS == 'Y' ) 
    return;
 
 $ret = "<dl id=\"$popupid\" class=\"popup\">\n";

  if ( empty ( $popup_fullnames ) )
    $popup_fullnames = array ();  
  $partList = array();
  if ( $id != '' && ! empty ( $PARTICIPANTS_IN_POPUP  ) && 
    $PARTICIPANTS_IN_POPUP == 'Y' ) {
    $sql = 'SELECT cal_login, cal_status FROM webcal_entry_user ' .
      "WHERE cal_id = ? AND cal_status IN ('A', 'W' ) ";
    $rows = dbi_get_cached_rows ( $sql,  array ( $id ) );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $participants[] = $row;  
      }
    }
    for ( $i = 0, $cnt = count ( $participants ); $i < $cnt; $i++ ) {
      user_load_variables ( $participants[$i][0], 'temp' );
      $partList[] = $tempfullname . ' '  . 
        ( $participants[$i][1] == 'W'? '(?)':'' );
    }
    $sql = 'SELECT cal_fullname FROM webcal_entry_ext_user ' .
      'WHERE cal_id = ? ORDER by cal_fullname';
    $rows = dbi_get_cached_rows ( $sql, array ( $id ) );
    if ( $rows ) {
      $extStr = translate ( 'External User');
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $partList[] = $row[0] . ' (' . $extStr . ')';
      }
    }
  }
     
  if ( $user != $login ) {
    if ( empty ( $popup_fullnames[$user] ) ) {
      user_load_variables ( $user, 'popuptemp_' );
      $popup_fullnames[$user] = $popuptemp_fullname;
    }
    $ret .= '<dt>' . translate ('User') .
      ":</dt>\n<dd>$popup_fullnames[$user]</dd>\n";
  }
  if ( $SUMMARY_LENGTH < 80 && strlen ( $name ) )
    $ret .= '<dt>' . htmlspecialchars ( substr ( $name, 0 , 40 ) ) . "</dt>\n";  
  if ( strlen ( $time ) )
    $ret .= '<dt>' . translate ('Time') . ":</dt>\n<dd>$time</dd>\n";
  if ( ! empty ( $location ) )
  $ret .= '<dt>' . translate ('Location') . ":</dt>\n<dd> $location</dd>\n";

  if ( ! empty ( $reminder ) )
  $ret .= '<dt>' . translate ('Send Reminder') . ":</dt>\n<dd> $reminder</dd>\n";
  
  if ( ! empty ( $partList ) ) {
    $ret .= '<dt>' . translate ('Participants') . ":</dt>\n";
    foreach ( $partList as $parts ) {
      $ret .= "<dd> $parts</dd>\n";
    }
  }
  
  if ( ! empty ( $description ) ) {
    $ret .= '<dt>' . translate ('Description') . ":</dt>\n<dd>";
    if ( ! empty ( $ALLOW_HTML_DESCRIPTION ) && $ALLOW_HTML_DESCRIPTION == 'Y' ) {
      $str = str_replace ( "&", "&amp;", $description );
      $str = str_replace ( "&amp;amp;", "&amp;", $str );
      //decode special characters
      $str = unhtmlentities( $str);
      // If there is no html found, then go ahead and replace
      // the line breaks ("\n") with the html break.
      if ( strstr ( $str, "<" ) && strstr ( $str, ">" ) ) {
        // found some html...
        $ret .= $str;
      } else {
        // no html, replace line breaks
        $ret .= nl2br ( $str );
      }
    } else {
      // html not allowed in description, escape everything
      $ret .= nl2br ( htmlspecialchars ( $description ) );
    }
    $ret .= "</dd>\n";
  } //if $description
  if ( ! empty ( $site_extras ) )
    $ret .= $site_extras;
  $ret .= "</dl>\n";
  return $ret;
}

/**
 * Builds the HTML for the event label.
 *
 * @param string $can_access
 * @param string $time_only
 *
 * @return string The HTML for the event label
 */
function build_entry_label ( $event, $popupid, $can_access, $timestr, $time_only='N' ) {
  global $login, $user, $eventinfo, $SUMMARY_LENGTH, $UAC_ENABLED; 
  $ret = '';
  //get reminders display string
  $reminder = getReminders ( $event->getId(), true );
  $can_access = ( $UAC_ENABLED == 'Y'? $can_access : 0 );
  $not_my_entry = ( ( $login != $user && strlen ( $user ) ) || 
      ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) );

  $sum_length = $SUMMARY_LENGTH;
  if ( $event->isAllDay() || $event->isUntimed() ) $sum_length += 6;
  $padding = (strlen( $event->getName() ) > $sum_length? '...': '');
  $tmp_ret = htmlspecialchars ( substr( $event->getName(), 0, $sum_length ) . $padding );
        
  if ( $not_my_entry && $event->getAccess() == 'R' && 
    ! ($can_access & PRIVATE_WT  )) {
    if ( $time_only != 'Y' ) $ret = '(' . translate('Private') . ')';
    $eventinfo .= build_entry_popup ( $popupid, $event->getLogin(),
      translate('This event is private'), '' );
  } else if ( $not_my_entry && $event->getAccess() == 'C' && 
    ! ( $can_access& CONF_WT  ) ) {
    if ( $time_only != 'Y' ) $ret = '(' . translate('Conf.') . ')';
    $eventinfo .= build_entry_popup ( $popupid, $event->getLogin(),
      translate('This event is confidential'), '' );
  } else if ( $can_access == 0  && $UAC_ENABLED == 'Y') {
    if ( $time_only != 'Y' ) $ret = $tmp_ret;
    $eventinfo .= build_entry_popup ( $popupid, $event->getLogin(), '', 
      $timestr, '', '', $event->getName(), '' );
  }else {
    if ( $time_only != 'Y' ) $ret = $tmp_ret;
    $eventinfo .= build_entry_popup ( $popupid, $event->getLogin(),
      $event->getDescription(), $timestr, site_extras_for_popup ( $event->getId() ),
      $event->getLocation(), $event->getName(), $event->getId(), $reminder );
  }
  return $ret;
  
}

/**
 * Generate HTML for a date selection for use in a form.
 *
 * @param string $prefix Prefix to use in front of form element names
 * @param string $date   Currently selected date (in YYYYMMDD format)
 * @param bool $trigger   Add onchange event trigger that
 *  calls javascript function $prefix_datechanged()
 *
 * @return string HTML for the selection box
 */
function date_selection ( $prefix, $date, $trigger=false ) {
  $ret = '';
  $num_years = 20;
 $trigger_str = ( ! empty ( $trigger )? $prefix . 'datechanged()' : '');
  if ( strlen ( $date ) != 8 )
    $date = date ( 'Ymd' );
  $thisyear = $year = substr ( $date, 0, 4 );
  $thismonth = $month = substr ( $date, 4, 2 );
  $thisday = $day = substr ( $date, 6, 2 );
  if ( $thisyear - date (  'Y' ) >= ( $num_years - 1 ) )
    $num_years = $thisyear - date (  'Y' ) + 2;
  $ret .= '<select name="' . $prefix . 'day" id="' . $prefix .
   'day"' . (! empty ( $trigger_str )? 'onchange="$trigger_str"': '') . " >\n";
  for ( $i = 1; $i <= 31; $i++ )
    $ret .= "<option value=\"$i\"" .
      ( $i == $thisday ? ' selected="selected"' : '' ) . ">$i</option>\n";
  $ret .= "</select>\n<select name=\"" . $prefix . 'month"' .
   (! empty ( $trigger_str )? 'onchange="$trigger_str"': '') . " >\n";
  for ( $i = 1; $i <= 12; $i++ ) {
    $m = month_short_name ( $i - 1 );
    $ret .= "<option value=\"$i\"" .
      ( $i == $thismonth ? ' selected="selected"' : '' ) . ">$m</option>\n";
  }
  $ret .= "</select>\n<select name=\"" . $prefix . 'year"' .
    (! empty ( $trigger_str )? "onchange=\"$trigger_str\"": '') . " >\n";
  for ( $i = -10; $i < $num_years; $i++ ) {
    $y = $thisyear + $i;
    $ret .= "<option value=\"$y\"" .
      ( $y == $thisyear ? ' selected="selected"' : '' ) . ">$y</option>\n";
  }
  $ret .= "</select>\n";
  $ret .= '<input type="button" name="' . $prefix. 
    "btn\" onclick=\"$trigger_str;selectDate( '" .
    $prefix . "day','" . $prefix . "month','" . $prefix . "year',$date, event)\" value=\"" .
    translate('Select') . "...\" />\n";

  return $ret;
}

function display_navigation( $name, $show_arrows=true ){
  global $single_user, $user_fullname, $is_nonuser_admin, $is_assistant,
  $user, $login, $thisyear, $thismonth, $thisday, $cat_id, $CATEGORIES_ENABLED,
  $nextYmd, $prevYmd, $caturl, $nowYmd, $wkstart, $wkend, $spacer,
  $DISPLAY_WEEKNUMBER, $DISPLAY_SM_MONTH, $DISPLAY_TASKS, $DATE_FORMAT_MY;

  if ( empty ( $name ) ) return;
  $u_url = '';
  if ( ! empty ( $user ) && $user != $login )
    $u_url = "user=$user&amp;";
      
  $ret = '<div style="border-width:0px;">';
  if ( $show_arrows && ( $name != 'month' || $DISPLAY_SM_MONTH == 'N' || 
    $DISPLAY_TASKS == 'Y' ) ){
    $ret .= '<a title="' . translate('Next') . '" class="next" href="' . 
      "$name.php?" . $u_url . "date=$nextYmd$caturl\"><img src=\"images/rightarrow.gif\" alt=\"" .
      translate('Next') . "\" /></a>\n";
    $ret .= '<a title="' . translate('Previous') . '" class="prev" href="' .
      "$name.php?" . $u_url . "date=$prevYmd$caturl\"><img src=\"images/leftarrow.gif\" alt=\"" .
      translate('Previous') . "\" /></a>\n";
  }
  $ret .= '<div class="title">';
  $ret .= '<span class="date">'; 
  if ( $name == 'day' ) {
    $ret .= date_to_str ( $nowYmd );
  } else if ( $name == 'week' ) {
    $ret .= date_to_str ( date ( 'Ymd', $wkstart ), '', false ) .
    '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;' .
    date_to_str ( date ('Ymd', $wkend ), '', false );
    if ( $DISPLAY_WEEKNUMBER == 'Y' ) {
      $ret .= " \n<span class=\"weeknumber\">(" .
      translate('Week') . ' ' . date('W', $wkstart + ONE_DAY ) . ')</span>';
    }      
  } else if ( $name == 'month'  || $name == 'view_l' ){
     $ret .= $spacer . date_to_str ( sprintf ( "%04d%02d01", $thisyear, $thismonth ),
    $DATE_FORMAT_MY, false, false );  
  }
  $ret .= "</span>\n<span class=\"user\">";
  // display current calendar's user (if not in single user)
  if ( $single_user == 'N' ) {
    $ret .= '<br />';
    $ret .= $user_fullname;
  }
  if ( $is_nonuser_admin )
    $ret .= '<br />-- ' . translate('Admin mode') . ' --';
  if ( $is_assistant )
    $ret .= '<br />-- ' . translate('Assistant mode') . ' --';
  $ret .= "</span>\n";
  if ( $CATEGORIES_ENABLED == 'Y' && (!$user || ($user == $login || $is_assistant ))) {
    $ret .= "<br />\n<br />\n";
    $ret .= print_category_menu( $name, sprintf ( "%04d%02d%02d",$thisyear, $thismonth, $thisday ), $cat_id );
  }
 $ret .= '</div></div><br />';

 return $ret;
}

/*
 * Generate html to create a month display
 *
 */
function display_month ( $thismonth, $thisyear, $demo='' ){
 global $WEEK_START, $WEEKENDBG, $user, $login, $today,
   $DISPLAY_ALL_DAYS_IN_MONTH, $DISPLAY_WEEKNUMBER;

  $ret = '<table class="main" style="clear:both;" cellspacing="0" cellpadding="0" width="100%" id="month_main"><tr>';
  if ( $DISPLAY_WEEKNUMBER == 'Y' ) {
      $ret .= '<th class="weekcell" width="5%"></th>' . "\n"; 
  }
  if ( $WEEK_START == 0 ) {
    $ret .= '<th class="weekend">' . translate('Sun') . "</th>\n";
  }
  $ret .= '<th>' . translate('Mon') . "</th>\n";
  $ret .= '<th>' . translate('Tue') . "</th>\n";
  $ret .= '<th>' . translate('Wed') . "</th>\n";
  $ret .= '<th>' . translate('Thu') . "</th>\n";
  $ret .= '<th>' . translate('Fri') . "</th>\n";
  $ret .= '<th class="weekend">' . translate('Sat') . "</th>\n";
  if ( $WEEK_START == 1 ) {
    $ret .= '<th class="weekend">' . translate('Sun') . "</th>\n";
  }
  $ret .= "</tr>\n";
  
  
  $wkstart = get_weekday_before ( $thisyear, $thismonth );
  
  // generate values for first day and last day of month
  $monthstart = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
  $monthend = mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear );
  
  for ( $i = $wkstart; date ('Ymd', $i ) <= date ('Ymd', $monthend );
    $i += ( ONE_DAY * 7 ) ) {
    $ret .= "<tr>\n";
     if ( $DISPLAY_WEEKNUMBER == 'Y' ) {
        $href = ( $demo? 'href=""': 'href="week.php?date='. 
          date ('Ymd', $i + ONE_DAY ) .'"' );
        $ret .= '<td class="weekcell"><a class="weekcell" title="' .
          translate('Week') . '&nbsp;' .
            date('W', $i + ONE_DAY ) . '"' . $href;
        if ( ! empty ( $user) && $user != $login  )
          $ret .= "&amp;user=$user";
        if ( ! empty ( $cat_id ) )
          $ret .= "&amp;cat_id=$cat_id";
        $ret .= ' >';
        $wkStr = translate('WK')  . date('W', $i + ONE_DAY );
        $wkStr2 = '';
        if ( translate('charset') == 'UTF-8' ) {
          $wkStr2 = $wkStr;
        } else {
          for ( $w=0;$w < strlen( $wkStr );$w++) {
            $wkStr2 .= substr( $wkStr, $w, 1 ) . '<br />';
          }
        } 
        $ret .= $wkStr2 . "</a></td>\n";
      }  
    
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * ONE_DAY + ( 12 * 3600 ) );
      $thiswday = date('w', $date  );
      $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
      if ( empty ( $WEEKENDBG ) ) {
        $is_weekend = false;
      }
      if ( ( date ('Ymd', $date ) >= date ('Ymd', $monthstart ) &&
        date ('Ymd', $date ) <= date ('Ymd', $monthend ) ) || 
        ( ! empty ( $DISPLAY_ALL_DAYS_IN_MONTH ) && $DISPLAY_ALL_DAYS_IN_MONTH == 'Y' ) ) {
        $ret .= '<td';
        $class = '';
        if ( date ('Ymd', $date  ) == date ('Ymd', $today ) ) {
          $class = 'today';
        }
        if ( $is_weekend ) {
          if ( strlen ( $class ) ) {
            $class .= ' ';
          }
          $class .= 'weekend';
        }
        //change class if date is not in this month
        if ( date ('Ymd', $date ) < date ('Ymd', $monthstart ) ||
          date ('Ymd', $date ) > date ('Ymd', $monthend ) ) {
          if ( strlen ( $class ) ) {
            $class .= ' ';
          }
          $class .= 'othermonth';
        }
        
        if ( $demo && ( date ( 'd', $date ) == 15 || date ( 'd', $date ) == 12 ) ) {
          $class .= ' entry';
        }
        if ( strlen ( $class ) )  {
        $ret .= " class=\"$class\"";
        }
        $ret .= '>';
        if ( ! $demo ) {
          $ret .= print_date_entries ( date ('Ymd', $date ),
            ( ! empty ( $user ) ) ? $user : $login, false );
        } else {
          if ( date ( 'd', $date ) == 15 || date ( 'd', $date ) == 12 ) 
            $ret .= translate('My event text');
        }
        $ret .= "</td>\n";
      } else {
        $ret .=  '<td ' . ( $is_weekend? 'class="weekend"': '' ) . ">&nbsp;</td>\n";        
      }
    }
    $ret .= "</tr>\n";
  }
  $ret .= '</table>';
  return $ret;
}

/**
 * Prints out a minicalendar for a month.
 *
 * @todo Make day.php NOT be a special case
 *
 * @param int    $thismonth     Number of the month to print
 * @param int    $thisyear      Number of the year
 * @param bool   $showyear      Show the year in the calendar's title?
 * @param bool   $show_weeknums Show week numbers to the left of each row?
 * @param string $minical_id    id attribute for the minical table
 * @param string $month_link    URL and query string for month link that should
 *                              come before the date specification (e.g.
 *                              month.php?  or  view_l.php?id=7&amp;)
 */
function display_small_month ( $thismonth, $thisyear, $showyear,
  $show_weeknums=false, $minical_id='', $month_link='month.php?' ) {
  global $WEEK_START, $user, $login, $boldDays, $get_unapproved;
  global $DISPLAY_WEEKNUMBER, $DATE_FORMAT_MY, $DISPLAY_TASKS;
  global $SCRIPT, $thisday; // Needed for day.php
  global $caturl, $today, $DISPLAY_ALL_DAYS_IN_MONTH, $use_http_auth;
  global $MINI_TARGET; // Used by minical.php

  if ( $user != $login && ! empty ( $user ) ) {
    $u_url = "user=$user" . '&amp;';
  } else {
    $u_url = '';
  }
  $ret = '';
  $header_span = ( $DISPLAY_WEEKNUMBER == true? 8:7 );
  $weekStr = translate('Week');
  //start the minical table for each month
  $ret .= "\n<table class=\"minical\"";
  if ( $minical_id != '' ) {
    $ret .= " id=\"$minical_id\"";
  }
  $ret .= ">\n";

  $monthstart = mktime( 0,0,0,$thismonth,1,$thisyear);
  $monthend = mktime( 0,0,0,$thismonth + 1,0,$thisyear);

  if ( $SCRIPT == 'day.php' ) {
    $month_ago = date ('Ymd',
      mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear ) );
    $month_ahead = date ('Ymd',
      mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear ) );
    $ret .= "<caption>$thisday</caption>\n";
    $ret .= "<thead>\n";
    $ret .= "<tr class=\"monthnav\"><th colspan=\"$header_span\">\n";
    $ret .= '<a title="' . 
      translate('Previous') . '" class="prev" href="day.php?' . $u_url  .
      "date=$month_ago$caturl\"><img src=\"images/leftarrowsmall.gif\" alt=\"" .
      translate('Previous') . "\" /></a>\n";
    $ret .= '<a title="' . 
      translate('Next') . '" class="next" href="day.php?' . $u_url .
      "date=$month_ahead$caturl\"><img src=\"images/rightarrowsmall.gif\" alt=\"" .
      translate('Next') . "\" /></a>\n";
    $ret .= date_to_str ( sprintf ( "%04d%02d%02d", $thisyear, $thismonth, 1 ),
      ( $showyear != '' ? $DATE_FORMAT_MY : '__month__' ),
      false );
    $ret .= "</th></tr>\n<tr>\n";
  } else   if ( $SCRIPT == 'minical.php' ) {
    $month_ago = date ('Ymd',
      mktime ( 0, 0, 0, $thismonth - 1, $thisday, $thisyear ) );
    $month_ahead = date ('Ymd',
      mktime ( 0, 0, 0, $thismonth + 1, $thisday, $thisyear ) );

    $ret .= "<thead>\n";
    $ret .= '<tr class="monthnav"><th colspan="7">' . "\n";
    $ret .= '<a title="' . 
      translate('Previous') . '" class="prev" href="minical.php?' . $u_url  .
      "date=$month_ago\"><img src=\"images/leftarrowsmall.gif\" alt=\"" .
      translate('Previous') . "\" /></a>\n";
    $ret .= '<a title="' . 
      translate('Next') . '" class="next" href="minical.php?' . $u_url .
      "date=$month_ahead\"><img src=\"images/rightarrowsmall.gif\" alt=\"" .
      translate('Next') . "\" /></a>\n";
    $ret .= date_to_str ( sprintf ( "%04d%02d%02d", $thisyear, $thismonth, 1 ),
      ( $showyear != '' ? $DATE_FORMAT_MY : '__month__' ),
      false );
    $ret .= "</th></tr>\n<tr>\n";
    } else {  //not day or minical script
    //print the month name
    $ret .= "<caption><a href=\"{$month_link}{$u_url}year=$thisyear&amp;month=$thismonth\">";
   $ret .= date_to_str ( sprintf ( "%04d%02d%02d", $thisyear, $thismonth, 1 ),
      ( $showyear != '' ? $DATE_FORMAT_MY : '__month__'),
      false );
    $ret .= "</a></caption>\n";

    $ret .= "<thead>\n<tr>\n";
  }

  //determine if the week starts on sunday or monday
  $wkstart = get_weekday_before ( $thisyear, $thismonth );
  
  //print the headers to display the day of the week (sun, mon, tues, etc.)
  // if we're showing week numbers we need an extra column
  if ( $show_weeknums && $DISPLAY_WEEKNUMBER == 'Y' )
    $ret .= "<th class=\"empty\">&nbsp;</th>\n";
  //if the week doesn't start on monday, print the day
  if ( $WEEK_START == 0 ) $ret .= '<th class="weekend">' .
    weekday_short_name ( 0 ) . "</th>\n";
  //cycle through each day of the week until gone
  for ( $i = 1; $i < 7; $i++ ) {
    $ret .= "<th" . ($i==6?' class="weekend"':'') . '>' .  
      weekday_short_name ( $i ) .  "</th>\n";
  }
  //if the week DOES start on monday, print sunday
  if ( $WEEK_START == 1 )
    $ret .= '<th class="weekend">' .  weekday_short_name ( 0 ) .  "</th>\n";
  //end the header row
  $ret .= "</tr>\n</thead>\n<tbody>\n";
  for ($i = $wkstart; date ('Ymd',$i) <= date ('Ymd',$monthend);
    $i += (ONE_DAY * 7) ) {
    $ret .= "<tr>\n";
    if ( $show_weeknums && $DISPLAY_WEEKNUMBER == 'Y' ) {
      $title = 'title="' . $weekStr . '&nbsp;' . 
        date('W', $i + ONE_DAY ) . '" ';
      $href = 'href="week.php?' . $u_url . 'date=' .date ('Ymd', $i+ ONE_DAY). '" ';
      $ret .= '<td class="weeknumber"><a class="weeknumber"' . $title . $href . '>(' . 
        date('W', $i + ONE_DAY ) . ')</a></td>' . "\n";
    }
    for ($j = 0; $j < 7; $j++) {
      $date = $i + ($j * ONE_DAY  + ( 12 * 3600 ));
      $dateYmd = date ('Ymd', $date );
      $wday = date ( 'w', $date );
      $hasEvents = false;
      $title = '';
      if ( $boldDays ) {
        $ev = get_entries ( $dateYmd, $get_unapproved, true, true );
        if ( count ( $ev ) > 0 ) {
          $hasEvents = true;
        $title = $ev[0]->getName();
        } else {
          $rep = get_repeating_entries ( $user, $dateYmd, $get_unapproved );
          if ( count ( $rep ) > 0 ) {
            $hasEvents = true;
            $title = $rep[0]->getName();
          }
        }
      }
      if ( ( $dateYmd >= date ('Ymd',$monthstart) &&
        $dateYmd <= date ('Ymd',$monthend) )  || 
        ( ! empty ( $DISPLAY_ALL_DAYS_IN_MONTH ) && 
          $DISPLAY_ALL_DAYS_IN_MONTH == 'Y' ) ) {
        $ret .= '<td';
        $class = '';
       //add class="weekend" if it's saturday or sunday
        if ( $wday == 0 || $wday == 6 ) {
          if ( $class != '' ) {
            $class .= ' ';
          }
          $class = 'weekend';
        }
        //if the day being viewed is today's date AND script = day.php
        if ( $dateYmd == $thisyear . $thismonth . $thisday &&
          $SCRIPT == 'day.php'  ) {
        //if it's also a weekend, add a space between class names to combine styles
        if ( $class != '' ) {
            $class .= ' ';
          }
          $class .= 'selectedday';
        }
        if ( $hasEvents ) {
          if ( $class != '' ) {
            $class .= ' ';
          }
          $class .= "hasevents";
        }
        if ( $class != '' ) {
          $ret .= " class=\"$class\"";
        }
        if ( date ('Ymd', $date  ) == date ('Ymd', $today ) ){
          $ret .= ' id="today"';
        }
        if ( $SCRIPT == 'minical.php' ) {
          $ret .= '><a href="';
          if ( $use_http_auth ) {
            $ret .= 'day.php?user=' .  $user . '&amp;';
          } else {
            $ret .= 'nulogin.php?login=' . $user . '&amp;return_path=day.php&amp;';
          }
          $ret .= 'date=' .  $dateYmd. '"' . 
            ( ! empty ( $MINI_TARGET )? " target=\"$MINI_TARGET\"": '') . 
            ( ! empty ( $title )? " title=\"$title\"": '') .
            '>';    
        } else {
          $ret .= '><a href="day.php?' .$u_url  . 'date=' .  $dateYmd . '">';
        }
        $ret .= date ( 'j', $date ) . "</a></td>\n";
       } else {
          $ret .= "<td class=\"empty\">&nbsp;</td>\n";
       }
      }                 // end for $j
      $ret .= "</tr>\n";
    }                         // end for $i
  $ret .= "</tbody>\n</table>\n";
  return $ret;
}

/**
 * Prints small task list for this $login user
 *
 */
function display_small_tasks ( $cat_id ) {
  global $user, $login, $is_assistant, $eventinfo, $DATE_FORMAT_TASK;
  static $key = 0;
  if ( ! empty ( $user ) && $user != $login  && ! $is_assistant ) {
    return false;
  }
 
 
  if ( $user != $login && ! empty ( $user ) ) {
    $u_url = "user=$user" . '&amp;';
    $task_user = $user;
  } else {
    $u_url = '';
    $task_user = $login;
  }

  $priorityStr = translate ( 'Priority' );
  $taskStr = translate ( 'Task Name' );
  $dueStr = translate ( 'Task Due Date' );
  $dateFormatStr = $DATE_FORMAT_TASK;
  $completedStr = translate ( 'Completed' );
  $filter = '';
  $task_list = query_events ( $task_user, false, $filter, $cat_id, true  );
  $row_cnt = 1;
  $task_html= '<table class="minitask" cellspacing="0" cellpadding="2">' . "\n";
  $task_html .= '<tr class="header"><th colspan="3" align="left">' . 
    translate ( 'TASKS' ) . '</th><th align="right">' .
    '<a href="edit_entry.php?' . $u_url . 'eType=task">' . 
    '<img src="images/new.gif" alt="+" class="new"/></a></th></tr>' . "\n";
  $task_html .= '<tr class="header"><th>!</th><th>'.  translate ( 'Task_Title' ) . 
    '</th><th>' . translate ('Due' ) . '</th><th>&nbsp;%&nbsp;</th></tr>' . "\n";
  foreach ( $task_list as $E )  {  
    //check UAC
    $task_owner = $E->getLogin();
    if ( access_is_enabled () ) {
      $can_access = access_user_calendar ( 'view', $task_owner, '', 
        $E->getCalType(), $E->getAccess() );
      if ( $can_access == 0 )
        continue;    
    }
    $cal_id = $E->getId();
    //generate popup info
    $popupid = "eventinfo-pop$cal_id-$key";
    $linkid  = "pop$cal_id-$key";
    $key++; 
    $t_url = ( $task_owner != $login ? "user={$task_owner}&amp;":'');
    $link = '<a href="view_entry.php?' . $t_url .'id=' . $cal_id . '"';
    $priority = $link  . ' title="' . $priorityStr . '">' . 
      $E->getPriority() . '</a>';
    $dots = ( strlen ( $E->getName() ) > 10 ? '...': '' );
    $name = $link  . ' title="' . $taskStr . ': ' . $E->getName() . 
      '" >'. substr( $E->getName(), 0, 10 ) . $dots .'</a>';
    $due_date = $link  . " title=\"" . $dueStr . '" >'. 
      date_to_str( $E->getDueDate(), $dateFormatStr, false, false) . 
        '</a>';
    $percent = $link . ' title="% ' . $completedStr . '">'. 
      $E->getPercent() . '</a>';
    $task_html .= "<tr class=\"task\" id=\"$linkid\"><td>$priority</td><td>$name</td>" .
      "<td>$due_date</td><td>&nbsp;&nbsp;$percent</td></tr>\n";
    $row_cnt++;
   //build special string to pass to popup
   // TODO move this logic into build_entry_popup() 
    $timeStr = translate ( 'Due Time' ) . ':' . display_time( $E->getDueTime()) .
      '</dd><dd>' . 
      translate ( 'Due Date' ) . ':' . date_to_str( $E->getDueDate(),'', false ).
      '</dd></dt><dt>' . translate ( 'Percent Complete' ) .
      ':<dt><dd>' . $E->getPercent() . '%' ;

    $eventinfo .= build_entry_popup ( $popupid, $E->getLogin(), $E->getDescription(), 
      $timeStr, '', $E->getLocation(), $E->getName(), $cal_id ); 
  }
  for ($i=7; $i > $row_cnt; $i-- ) {
    $task_html .= "<tr><td colspan=\"4\"  class=\"filler\">&nbsp;</td></tr>\n";        
  }
  $task_html .= "</table>\n";
  return $task_html;
}

/**
 * Prints the HTML for one event in the month view.
 *
 * @param Event  $event The event
 * @param string $date  The data for which we're printing (YYYYMMDD)
 *
 * @staticvar int Used to ensure all event popups have a unique id
 *
 * @uses build_entry_popup
 */
function print_entry ( $event, $date ) {
  global $eventinfo, $login, $user, $PHP_SELF, $layers, 
   $DISPLAY_LOCATION, $DISPLAY_TASKS_IN_GRID,
   $is_assistant, $is_nonuser_admin, $TIME_SPACER, $categories;

  static $key = 0;
  $ret = '';
  $cal_type = $event->getCalTypeName();
    
  if ( access_is_enabled () ) {
    $time_only = access_user_calendar ( 'time', $event->getLogin() );
    $can_access = access_user_calendar ( 'view', $event->getLogin(), '', 
      $event->getCalType(), $event->getAccess() );
    if ( $cal_type == 'task' && $can_access == 0 )
      return false;    
  } else {
    $time_only = 'N';
    $can_access = CAN_DOALL;
  }
  
  $padding = '';
  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $class = 'layerentry';
  } else {
    $class = 'entry';
    if ( $event->getStatus() == 'W' ) $class = 'unapprovedentry';
  }
  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, 'view_m.php' ) ||
    strstr ( $PHP_SELF, 'view_w.php' ) ||
    strstr ( $PHP_SELF, 'view_v.php' ) ||
    strstr ( $PHP_SELF, 'view_t.php' ) )
    $class = 'entry';

  if ( $event->getPriority() == 3 ) $ret .= '<strong>';

  $id = $event->getID();
  $name = $event->getName();


  $cal_link = 'view_entry.php';
  if ( $cal_type == 'task' ) {
    $view_text = translate ( 'View this task' );
  } else {
    $view_text = translate ( 'View this event' );    
  }
    
    
  $popupid = "eventinfo-pop$id-$key";
  $linkid  = "pop$id-$key";
  $key++;

  //build entry link if UAC permits viewing
  if ( $can_access != 0 && $time_only != 'Y' ) {
    //make sure clones have parents url date
    $linkDate = (  $event->getClone()?$event->getClone(): $date );
    $title = " title=\"$view_text\" ";
    $href = "href=\"$cal_link?id=$id&amp;date=$linkDate";
    if ( strlen ( $user ) > 0 )
      $href .= '&amp;user=' . $user;
    $href .= '"';
  } else {
    $title = '';
    $href = '';  
  }   
  $ret .=  "<a $title class=\"$class\" id=\"$linkid\" $href  >";

  $icon =  $cal_type . '.gif';
  $catIcon = '';
  if ( $event->getCategory() > 0 ) {
    $catIcon = "icons/cat-" . $event->getCategory() . '.gif';
    if ( ! file_exists ( $catIcon ) )
      $catIcon = '';
  }

  if ( empty ( $catIcon ) ) {
    $ret .= "<img src=\"images/$icon\" class=\"bullet\" alt=\"" . 
      $view_text  . '" width="5" height="7" />';
  } else {
    // Use category icon
    $catAlt = '';
    if ( ! empty ( $categories[$event->getCategory()] ) )
      $catAlt = translate ( 'Category' ) . ': ' . $categories[$event->getCategory()];
    $ret .= "<img src=\"$catIcon\" alt=\"$catAlt\" title=\"$catAlt\" />";
  }

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ($layer['cal_layeruser'] == $event->getLogin() ) {
        $ret .=('<span style="color:' . $layer['cal_color'] . ';">');
      }
    }
  }

   $time_spacer = ( $time_only == 'Y' ? '' : $TIME_SPACER );
  $timestr = '';
  if ( $event->isAllDay() ) {
    $timestr = translate('All day event');
  } else if ( ! $event->isUntimed() ) {
    $timestr = display_time ( $event->getDateTime() );
    $time_short = getShortTime ( $timestr );
    if ( $cal_type == 'event' ) $ret .= $time_short . $time_spacer;
    if ( $event->getDuration() > 0 ) {
      $timestr .= ' - ' . display_time ( $event->getEndDateTime() );
    }
  }
  $ret .= build_entry_label ( $event, $popupid, $can_access, $timestr, $time_only );
  
  //added to allow a small location to be displayed if wanted
 if ( ! empty ($location) &&
   ! empty ( $DISPLAY_LOCATION ) && $DISPLAY_LOCATION == 'Y') {
   $ret .= '<br /><span class="location">(' . htmlspecialchars ( $location ) . ')</span>';
  }
 
  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
        if($layer['cal_layeruser'] == $event->getLogin() ) {
            $ret .= '</span>';
        }
    }
  }
  $ret .= "</a>\n";
  if ( $event->getPriority() == 3 ) $ret .= "</strong>\n"; //end font-weight span
  $ret .= "<br />";

  return $ret;
}

/** 
 * Gets any site-specific fields for an entry that are stored in the database in the webcal_site_extras table.
 *
 * @param int $eventid Event ID
 *
 * @return array Array with the keys as follows:
 *    - <var>cal_name</var>
 *    - <var>cal_type</var>
 *    - <var>cal_date</var>
 *    - <var>cal_remind</var>
 *    - <var>cal_data</var>
 */
function get_site_extra_fields ( $eventid ) {
  $sql = 'SELECT cal_name, cal_type, cal_date, cal_remind, cal_data ' .
    'FROM webcal_site_extras WHERE cal_id = ?';
  $rows = dbi_get_cached_rows ( $sql, array( $eventid ) );
  $extras = array ();
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      // save by cal_name (e.g. "URL")
      $extras[$row[0]] = array (
        'cal_name' => $row[0],
        'cal_type' => $row[1],
        'cal_date' => $row[2],
        'cal_remind' => $row[3],
        'cal_data' => $row[4]
      );
    }
  }
  return $extras;
}

/**
 * Reads all the events for a user for the specified range of dates.
 *
 * This is only called once per page request to improve performance.  All the
 * events get loaded into the array <var>$events</var> sorted by time of day
 * (not date).
 *
 * @param string $user      Username
 * @param string $startdate Start date range, inclusive (in timestamp format)
 *                          in user's timezone
 * @param string $enddate   End date range, inclusive (in timestamp format)
 *                          in user's timezone
 * @param int    $cat_id    Category ID to filter on
 *
 * @return array Array of Events
 *
 * @uses query_events
 */
function read_events ( $user, $startdate, $enddate, $cat_id = '') {
  global $login, $layers;
 
  //shift date/times to UTC  
  $start_date = gmdate ('Ymd', $startdate );
  $start_time = gmdate ( 'His', $startdate );  
  $end_date = gmdate ('Ymd', $enddate );
  $end_time = gmdate ( 'His', $enddate );
  $date_filter = " AND ( ( webcal_entry.cal_date >= $start_date " .
    "AND webcal_entry.cal_date <= $end_date AND " .
    'webcal_entry.cal_time = -1 ) OR ' .
    "( webcal_entry.cal_date > $start_date AND " .
    "webcal_entry.cal_date < $end_date ) OR " .
    "( webcal_entry.cal_date = $start_date AND " .
    "webcal_entry.cal_time >= $start_time ) OR " .
    "( webcal_entry.cal_date = $end_date AND " .
    "webcal_entry.cal_time <= $end_time ))";
  return query_events ( $user, false, $date_filter, $cat_id  );
}

/**
 * Reads all the tasks for a user with due date within the specified range of dates.
 *
 * This is only called once per page request to improve performance.  All the
 * tasks get loaded into the array <var>$tasks</var> sorted by time of day
 * (not date).
 *
 * @param string $user      Username
 * @param string $duedate   End date range, inclusive (in timestamp format)
 *                          in user's timezone
 * @param int    $cat_id    Category ID to filter on
 *
 * @return array Array of Tasks
 *
 * @uses query_events
 */
function read_tasks ( $user, $duedate, $cat_id = ''  ) {

  $due_date = gmdate ('Ymd', $duedate );
  $due_time = gmdate ( 'His', $duedate );
  $date_filter = " AND ( ( webcal_entry.cal_due_date <= $due_date ) OR " .
    "( webcal_entry.cal_due_date = $due_date AND " .
    "webcal_entry.cal_due_time <= $due_time ) )";

  return query_events ( $user, false, $date_filter, $cat_id, true  );
}

/**
 * Gets all the events for a specific date.
 *
 * Events are retreived from the array of pre-loaded events (which was loaded
 * all at once to improve performance).
 *
 * The returned events will be sorted by time of day.
 *
 * @param string $date           Date to get events for in YYYYMMDD format
 *                               in user's timezone
 * @param bool   $get_unapproved Load unapproved events?
 *
 * @return array Array of Events
 */
function get_entries ( $date, $get_unapproved=true ) {
  global $events;
  $ret = array ();
  $evcnt = count ( $events );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    $event_date = date ('Ymd', $events[$i]->getDateTimeTS() );
    if ( ! $get_unapproved && $events[$i]->getStatus() == 'W' )
      continue;  
    if ( $events[$i]->isAllDay() || $events[$i]->isUntimed() ) {
      if ( $events[$i]->getDate() == $date )
        $ret[] = $events[$i];
    } else {
      if ( $event_date == $date  )
        $ret[] = $events[$i];
    }
  }
  return $ret;
}

/**
 * Gets all the tasks for a specific date.
 *
 * Events are retreived from the array of pre-loaded tasks (which was loaded
 * all at once to improve performance).
 *
 * The returned tasks will be sorted by time of day.
 *
 * @param string $date           Date to get tasks for in YYYYMMDD format
 * @param bool   $get_unapproved Load unapproved events?
 *
 * @return array Array of Tasks
 */
function get_tasks ( $date, $get_unapproved=true ) {
  global $tasks;
  $ret = array ();
  $today = gmdate ('Ymd' );
  $tskcnt = count ( $tasks );
  for ( $i = 0; $i < $tskcnt; $i++ ) {
    // In case of data corruption (or some other bug...)
    if ( empty ( $tasks[$i] ) || $tasks[$i]->getID() == '' )
      continue;
    if ( ! $get_unapproved && $tasks[$i]->getStatus() == 'W' )
      continue;
    $due_date = gmdate ('Ymd', $tasks[$i]->getDueDateTimeTS() );
    //make overdue tasks float to today
    if ( ( $date == $today && $due_date < $today ) || ( $due_date == $date ) ) {
      $ret[] = $tasks[$i];
    }
  }
  return $ret;
}

/**
 * Reads events visible to a user.
 *
 * Includes layers and possibly public access if enabled
 *
 * @param string $user          Username
 * @param bool   $want_repeated Get repeating events?
 * @param string $date_filter   SQL phrase starting with AND, to be appended to
 *                              the WHERE clause.  May be empty string.
 * @param int    $cat_id        Category ID to filter on.  May be empty.
 * @param bool   $is_task       Used to restrict results to events OR tasks
 *
 * @return array Array of Events sorted by time of day
 */
function query_events ( $user, $want_repeated, $date_filter, $cat_id ='', $is_task=false ) {
  global $login, $thisyear, $thismonth, $layers, $result;
  global $PUBLIC_ACCESS_DEFAULT_VISIBLE, $db_connection_info;

  $cloneRepeats = array();
  $result = array ();
  $layers_byuser = array ();
  //new multiple categories requires some checking to see if this cat_id is
  //valid for this cal_id. It could be done with nested sql, but that may not work
  //for all databases. This might be quicker also.
  $catlist = array();
  //None was selected...return only events without categories
  if ( $cat_id == -1 ) {
    $sql = 'SELECT DISTINCT cal_id FROM webcal_entry_categories ';
    $rows = dbi_get_cached_rows ( $sql, array() ); 
  } else if ( $cat_id != '' ) {
    $sql = 'SELECT cal_id FROM webcal_entry_categories WHERE  cat_id = ? ';
    $rows = dbi_get_cached_rows ( $sql, array( $cat_id ) );
  }
  if ( $cat_id != '' ) {
   // $rows = dbi_get_cached_rows ( $sql, array( $cat_id ) );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $catlist[$i] = $row[0];
      }  
    }
  }
  $catlistcnt = count ($catlist);
  $query_params = array();
  $sql = 'SELECT webcal_entry.cal_name, webcal_entry.cal_description, '
    . 'webcal_entry.cal_date, webcal_entry.cal_time, '
    . 'webcal_entry.cal_id, webcal_entry.cal_ext_for_id, '
    . 'webcal_entry.cal_priority, '
    . 'webcal_entry.cal_access, webcal_entry.cal_duration, '
    . 'webcal_entry_user.cal_status, '
    . 'webcal_entry.cal_create_by, '
    . 'webcal_entry_user.cal_login, '
    . 'webcal_entry.cal_type, '
    . 'webcal_entry.cal_location, '
    . 'webcal_entry.cal_url, '
    . 'webcal_entry.cal_due_date, '
    . 'webcal_entry.cal_due_time, '
    . 'webcal_entry_user.cal_percent, '
    . 'webcal_entry.cal_mod_date, '
    . 'webcal_entry.cal_mod_time ';
  if ( $want_repeated ) {
    $sql .= ', '
      . 'webcal_entry_repeats.cal_type, webcal_entry_repeats.cal_end, '
      . 'webcal_entry_repeats.cal_frequency, webcal_entry_repeats.cal_days, '
      . 'webcal_entry_repeats.cal_bymonth, webcal_entry_repeats.cal_bymonthday, '
      . 'webcal_entry_repeats.cal_byday, webcal_entry_repeats.cal_bysetpos, '
      . 'webcal_entry_repeats.cal_byweekno, webcal_entry_repeats.cal_byyearday, '
      . 'webcal_entry_repeats.cal_wkst, webcal_entry_repeats.cal_count, '   
      . 'webcal_entry_repeats.cal_endtime '
      . 'FROM webcal_entry, webcal_entry_repeats, webcal_entry_user '
      . 'WHERE webcal_entry.cal_id = webcal_entry_repeats.cal_id AND ';
  } else {
    $sql .= 'FROM webcal_entry, webcal_entry_user WHERE ';
  }
  $sql .= 'webcal_entry.cal_id = webcal_entry_user.cal_id ' .
    "AND webcal_entry_user.cal_status IN ('A','W') ";

  if (  $catlistcnt > 0 ) {  
    $placeholders = '';
    for ( $p_i = 0; $p_i < $catlistcnt; $p_i++ ) {
      $placeholders .= ( $p_i == 0 ) ? '?': ', ?';
      $query_params[] = $catlist[$p_i];
    }
    if ( $cat_id > 0 ) {
      $sql .= "AND webcal_entry.cal_id IN ( $placeholders ) ";
    } else if ( $cat_id == -1 ){ //eliminate events with categories
      $sql .= "AND webcal_entry.cal_id NOT IN ( $placeholders ) ";
    }
  } else if ( $cat_id != '' ) {
    //force no rows to be returned
    $sql .= 'AND 1 = 0 '; // no matching entries in category  
  }
 
  if ( $is_task == false ) {
      $sql .= "AND webcal_entry.cal_type IN ('E','M')  ";
    } else {
      $sql .= "AND webcal_entry.cal_type IN ('T','N') AND ( webcal_entry.cal_completed IS NULL ) ";    
    }

  if ( strlen ( $user ) > 0 )
    $sql .= 'AND (webcal_entry_user.cal_login = ? ';
    $query_params[] = $user;

  if ( $user == $login && strlen ( $user ) > 0 ) {
    if ($layers) foreach ($layers as $layer) {
      $layeruser = $layer['cal_layeruser'];

      $sql .= 'OR webcal_entry_user.cal_login = ? ';
    $query_params[] = $layeruser;

      // while we are parsing the whole layers array, build ourselves
      // a new array that will help when we have to check for dups
      $layers_byuser[$layeruser] = $layer['cal_dups'];
    }
  }
  if ( $user == $login && strlen ( $user ) &&
    $PUBLIC_ACCESS_DEFAULT_VISIBLE == 'Y' ) {
    $sql .= "OR webcal_entry_user.cal_login = '__public__' ";
  }
  if ( strlen ( $user ) > 0 )
    $sql .= ') ';
  $sql .= $date_filter;

  // now order the results by time, then name
  $sql .= ' ORDER BY webcal_entry.cal_time, webcal_entry.cal_name';
  $rows = dbi_get_cached_rows ( $sql, $query_params );
  if ( $rows ) {
    $i = 0;
    $checkdup_id = -1;
    $first_i_this_id = -1;
    for ( $ii = 0, $cnt = count ( $rows ); $ii < $cnt; $ii++ ) {
      $row = $rows[$ii];
      if ($row[9] == 'R' || $row[9] == 'D') {
        continue;  // don't show rejected/deleted ones
      }
      //get primary category for this event, used for icon and color
      $primary_cat = '';
      $sql2 = 'SELECT webcal_entry_categories.cat_id ' .
        ' FROM webcal_entry_categories ' .
        ' WHERE webcal_entry_categories.cal_id = ? ' . 
        ' ORDER BY webcal_entry_categories.cat_order';
      $rows2 = dbi_get_cached_rows ( $sql2, array( $row[4] ) );
      if ( $rows2 ) {
        $row2 = $rows2[0]; //return first row only
        $primary_cat = $row2[0];
      }  
    
      if ( $want_repeated && ! empty ( $row[20] ) ) {//row[20] = cal_type
        $item =& new RepeatingEvent ( $row[0], $row[1], $row[2], $row[3],
        $row[4], $row[5], $row[6], $row[7], $row[8], $row[9], $row[10], 
        $primary_cat, $row[11], $row[12], $row[13], $row[14], $row[15], 
        $row[16], $row[17], $row[18], $row[19], $row[20], $row[21],
        $row[22], $row[23], $row[24], $row[25], $row[26], $row[27], 
        $row[28], $row[29], $row[30], $row[31], $row[32], array(), array(), array() );
      } else {
        $item =& new Event ( $row[0], $row[1], $row[2], $row[3], $row[4], 
        $row[5], $row[6], $row[7], $row[8], $row[9], $row[10], 
        $primary_cat, $row[11], $row[12], $row[13], $row[14],
        $row[15], $row[16], $row[17], $row[18], $row[19]);
      }

      if ( $item->getID() != $checkdup_id ) {
        $checkdup_id = $item->getID();
        $first_i_this_id = $i;
      }

      if ( $item->getLogin() == $user ) {
        // Insert this one before all other ones with this ID.
        array_splice ( $result, $first_i_this_id, 0, array($item) );
        $i++;

        if ($first_i_this_id + 1 < $i) {
          // There's another one with the same ID as the one we inserted.
          // Check for dup and if so, delete it.
          $other_item = $result[$first_i_this_id + 1];
          if (!empty($layers_byuser[$other_item->getLogin()]) &&
            $layers_byuser[$other_item->getLogin()] == 'N') {
            // NOTE: array_splice requires PHP4
            array_splice ( $result, $first_i_this_id + 1, 1 );
            $i--;
          }
        }
      } else {
        if ($i == $first_i_this_id
          || ( ! empty ( $layers_byuser[$item->getLogin()] ) &&
          $layers_byuser[$item->getLogin()] != 'N' ) ) {
          // This item either is the first one with its ID, or allows dups.
          // Add it to the end of the array.
          $result [$i++] = $item;
        }
      }
      //Does event go past midnight?
      if ( date ('Ymd', $item->getDateTimeTS() ) != 
        date ('Ymd', $item->getEndDateTimeTS() ) && 
        ! $item->isAllDay() && $item->getCalTypeName() == 'event' )  {
        get_OverLap ( $item, $i, true );
        $i = count ( $result );
      }
    }
  }
  
  if ( $want_repeated ) {
     // Now load event exceptions/inclusions and store as array 
    $resultcnt =  count ( $result );
    $max_until = mktime ( 0,0,0,$thismonth + 2,1, $thisyear);
    for ( $i = 0; $i < $resultcnt; $i++ ) {
      if ( $result[$i]->getID() != '' ) {
        $rows = dbi_get_cached_rows ( 'SELECT cal_date, cal_exdate ' .
          'FROM webcal_entry_repeats_not ' .
          'WHERE cal_id = ?', array( $result[$i]->getID() ) );
        $rowcnt = count ( $rows );
        for ( $ii = 0; $ii < $rowcnt; $ii++ ) {
          $row = $rows[$ii];
          //if this is not a clone, add exception date
          if ( ! $result[$i]->getClone() ){
            $except_date = $row[0];          
          }
          if ( $row[1] == 1 ) {
            $result[$i]->addRepeatException($except_date, $result[$i]->getID());
          } else {
            $result[$i]->addRepeatInclusion($except_date);        
          }
        }
        //get all dates for this event
        //if clone, we'll get the dates from parent later
        if ( ! $result[$i]->getClone() ){
          if ( $result[$i]->getRepeatEndDateTimeTS() ) {
            $until = $result[$i]->getRepeatEndDateTimeTS();
          } else { 
            //make sure all January dates will appear in small calendars
            $until = $max_until; 
          }
          //try to minimize the repeat search be shortening until if BySetPos
          // is not used
          if ( ! $result[$i]->getRepeatBySetPos()  && $until > $max_until )
            $until = $max_until; 
          $rpt_count = 999; //some BIG number
          $jump = mktime ( 0, 0, 0, $thismonth -1, 1, $thisyear);
          if ( $result[$i]->getRepeatCount() ) 
            $rpt_count = $result[$i]->getRepeatCount() -1;
          $date = $result[$i]->getDateTimeTS();
          if ( $result[$i]->isAllDay() || $result[$i]->isUntimed() ) {
            $date += (12 * 3600);//a simple hack to prevent DST problems
          }

  //check if this event id has been cached
  $hash = md5 ( $result[$i]->getId() );
  $file = $db_connection_info['cachedir'] . '/' . $hash . '.dat';
  if ( ! empty ( $db_connection_info['cachedir'] ) &&  file_exists ( $file ) ){
      $dates =  unserialize ( file_get_contents ( $file ) );
   } else {

          $dates = get_all_dates ( $date,
            $result[$i]->getRepeatType(), $result[$i]->getRepeatFrequency(),
            $result[$i]->getRepeatByMonth(), $result[$i]->getRepeatByWeekNo(),
            $result[$i]->getRepeatByYearDay(), $result[$i]->getRepeatByMonthDay(),
            $result[$i]->getRepeatByDay(), $result[$i]->getRepeatBySetPos(),
            $rpt_count, $until, $result[$i]->getRepeatWkst(),
            $result[$i]->getRepeatExceptions(), 
            $result[$i]->getRepeatInclusions(), $jump );
          $result[$i]->addRepeatAllDates($dates);
    // serialize and save in cache for later use
   if ( ! empty ( $db_connection_info['cachedir'] ) ) {
      $fd = @fopen ( $file, 'w+b', false );
      if ( empty ( $fd ) ) {
        dbi_fatal_error ( "Cache error: could not write file $file" );
      }
      fwrite ( $fd, serialize ( $dates ) );
      fclose ( $fd );
      chmod ( $file, 0666 );
    }
   }
        } else { //process clones if any
          if ( count ( $result[$i-1]->getRepeatAllDates() > 0 ) ){
            $parentRepeats = $result[$i-1]->getRepeatAllDates();
            $parentRepeatscnt = count ( $parentRepeats);
            for ( $j=0; $j< $parentRepeatscnt; $j++ ) {
              $cloneRepeats[] = date ('Ymd', $parentRepeats[$j] );
            }
            $result[$i]->addRepeatAllDates($cloneRepeats);
          }
        }
      }
    }    
  }
  return $result;
}

/**
 * Reads all the repeated events for a user.
 *
 * This is only called once per page request to improve performance. All the
 * events get loaded into the array <var>$repeated_events</var> sorted by time of day (not
 * date).
 *
 * This will load all the repeated events into memory.
 *
 * <b>Notes:</b>
 * - To get which events repeat on a specific date, use
 *   {@link get_repeating_entries()}.
 * - To get all the dates that one specific event repeats on, call
 *   {@link get_all_dates()}.
 *
 * @param string $user   Username
 * @param int    $cat_id Category ID to filter on  (May be empty)
 * @param int $date      Cutoff date for repeating event cal_end in timestamp
 *                       format (may be empty)
 *
 * @return array Array of RepeatingEvents sorted by time of day
 *
 * @uses query_events
 */
function read_repeated_events ( $user, $cat_id = '', $date = ''  ) {
  global $login;
  global $layers;
  if ( $date != '') $date = date ('Ymd', $date );
  $filter = ($date != '') ? "AND (webcal_entry_repeats.cal_end >= $date OR webcal_entry_repeats.cal_end IS NULL) " : '';
  return query_events ( $user, true, $filter, $cat_id );
}

/**
 * Returns all the dates a specific event will fall on accounting for the repeating.
 *
 * Any event with no end will be assigned one.
 *
 * @param int $date         Initial date in raw format
 * @param string $rpt_type  Repeating type as stored in the database
 * @param int $interval     Interval of repetition
 * @param array $ByMonth    Array of ByMonth values 
 * @param array $ByWeekNo   Array of ByWeekNo values
 * @param array $ByYearDay  Array of ByYearDay values
 * @param array $ByMonthDay Array of ByMonthDay values
 * @param array $ByDay      Array of ByDay values
 * @param array $BySetPos   Array of BySetPos values
 * @param int $Count        Max number of events to return
 * @param string $Until     Last day of repeat
 * @param string $Wkst      First day of week ('MO' is default)
 * @param array $ex_days   Array of exception dates for this event in YYYYMMDD format
 * @param array $inc_days  Array of inclusion dates for this event in YYYYMMDD format
 * @param int $jump         Date to short cycle loop counts to, also makes output YYYYMMDD
 *
 * @return array Array of dates (in UNIX time format)
 */
function get_all_dates ( $date, $rpt_type, $interval=1, $ByMonth ='',
  $ByWeekNo ='', $ByYearDay ='', $ByMonthDay ='', $ByDay ='', 
  $BySetPos ='', $Count=999,
  $Until= NULL, $Wkst= 'MO', $ex_days='', $inc_days='', $jump='' ) {
  global $CONFLICT_REPEAT_MONTHS, $byday_values, $byday_names;  
  $currentdate = floor($date/ONE_DAY)*ONE_DAY;
  $dateYmd   = date ('Ymd', $date );
  $hour      = date('H',$date);
  $minute    = date('i',$date);

  if ($Until == NULL && $Count == 999 ) {
    // Check for $CONFLICT_REPEAT_MONTHS months into future for conflicts
    $thismonth = substr($dateYmd, 4, 2);
    $thisyear = substr($dateYmd, 0, 4);
    $thisday = substr($dateYmd, 6, 2);
    $thismonth += $CONFLICT_REPEAT_MONTHS;
    if ($thismonth > 12) {
      $thisyear++;
      $thismonth -= 12;
    }
    $realend = mktime( $hour, $minute, 0, $thismonth, $thisday, $thisyear ) ;
  } else if ( $Count != 999 ){
   //set $until so some ridiculous value
    $realend = mktime ( 0,0,0,1,1,2038); 
  } else {
    $realend = $Until; 
  }
  $ret = array();
  $date_excluded = false; //flag to track ical results
  //do iterative checking here.
  //I floored the $realend so I check it against the floored date
  if ($rpt_type && $currentdate < $realend) {
    $cdate = $date;
    $n = 0;
    if ( ! empty ( $ByMonth ) ) $bymonth = explode (',',$ByMonth);
    if ( ! empty ( $ByWeekNo ) ) $byweekno = explode (',',$ByWeekNo);  
    if ( ! empty ( $ByYearDay ) ) $byyearday = explode (',',$ByYearDay);
    if ( ! empty ( $ByMonthDay ) ) $bymonthday = explode (',',$ByMonthDay);
    if ( ! empty ( $ByDay ) ) $byday = explode (',',$ByDay);
    if ( ! empty ( $BySetPos ) ) $bysetpos = explode (',',$BySetPos);
    if ($rpt_type == 'daily') {
      //skip to this year/month if called from query_events and we don't need count
      if ( ! empty ( $jump) && $Count == 999 ) {
        while ( $cdate < $jump )
          $cdate = add_dstfree_time ( $cdate, ONE_DAY,  $interval );
      } 
      while ($cdate <= $realend && $n <= $Count) { 
        //check RRULE items
        if ( ! empty ( $bymonth ) ) {
          if ( ! strlen ( array_search ( date( 'n', $cdate ), $bymonth ) ) ) 
            $date_excluded = true;
        }
        if ( ! empty ( $byweekno ) ) {
          if ( ! strlen ( array_search ( date( 'W', $cdate ), $byweekno ) ) )
            $date_excluded = true;
        }  
        if ( ! empty ( $byyearday ) ) {
          $doy = date( 'z', $cdate ); //day of year
          $diy = date('L',$cdate) + 365; //days in year
          $diyReverse = $doy - $diy -1;
          if ( ! array_search ( $doy, $byyearday ) && 
            ! array_search ( $diyReverse, $byyearday ))
            $date_excluded = true;
        } 
        if ( ! empty ( $bymonthday ) ) {
          $dom = date( 'j', $cdate ); //day of month
          $dim = date('t',$cdate); //days in month
          $dimReverse = $dom - $dim -1;
          if ( ! array_search ( $dom, $bymonthday ) && 
            ! array_search ( $dimReverse, $bymonthday ))
            $date_excluded = true;
        }
        if ( ! empty ( $byday ) ) {
          $bydayvalues = get_byday ( $byday, $cdate, 'daily' );
          if (  ! strlen ( array_search ( $cdate, $bydayvalues ) ) ){
            $date_excluded = true;
          }      
        }     
        if ( $date_excluded == false )
          $ret[$n++]=$cdate;
        $cdate = add_dstfree_time ( $cdate, ONE_DAY,  $interval );
        $date_excluded = false;
      }
    } else if ($rpt_type == 'weekly') {
      $r=0;
      $dow = date('w',$date);
      if ( ! empty ( $jump) && $Count == 999 ) {
        while ( $cdate < $jump )
          $cdate = add_dstfree_time ( $cdate, ONE_WEEK,  $interval );
      }
      $cdate = $date - ($dow * ONE_DAY);
      while ($cdate <= $realend && $n <= $Count ) {
        if ( ! empty ( $byday ) ){
          foreach($byday as $day) {
            $td = $cdate + ( $byday_values[$day] * ONE_DAY );
            if ($td >= $date && $td <= $realend && $n <= $Count) {
              $ret[$n++]=$td;
            }     
          }
        } else {  
          $td = $cdate + ( $dow * ONE_DAY ); 
          $cdow = date('w', $td );       
          if ( $cdow == $dow ) {
            $ret[$n++] = $td;    
          }
        }
        //skip to the next week in question.
        $cdate = add_dstfree_time ( $cdate, ONE_WEEK,  $interval );
      }
    } else if ( substr ( $rpt_type, 0 , 7 ) == 'monthly') {  
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $thisday   = substr($dateYmd, 6, 2);
      $hour      = date('H',$date);
      $minute    = date('i',$date);
      //skip to this year if called from query_events and we don't need count
      if ( ! empty ( $jump) && $Count == 999 ) {
        while ( $cdate < $jump ) {
          $thismonth += $interval;
          $cdate = mktime ( $hour, $minute, 0, $thismonth, $thisday, $thisyear );
        }
      }      
      $cdate = mktime ( $hour, $minute, 0, $thismonth, $thisday, $thisyear ) ;
      $mdate = $cdate;
      while ($cdate <= $realend && $n <= $Count) {
        $yret = array();         
        $bydayvalues = $bymonthdayvalues = array();
        if ( isset($byday) )
          $bydayvalues = get_byday ( $byday, $mdate, 'month' );
        if ( isset($bymonthday) ) 
          $bymonthdayvalues = get_bymonthday ( $bymonthday, $mdate, $date, $realend );
        if ( ! empty ( $bydayvalues ) && ! empty ( $bymonthdayvalues )){
          $bydaytemp = array_intersect ( $bymonthdayvalues, $bydayvalues );       
          $yret = array_merge ( $yret, $bydaytemp );  
        } else if ( ! empty ( $bymonthdayvalues ) ) {
          $yret = array_merge ( $yret, $bymonthdayvalues );
        } else if ( ! empty ( $bydayvalues ) ) {
          $yret = array_merge ( $yret, $bydayvalues );      
        } else if ( ! isset($byday) && ! isset($bymonthday)  ) {
          $yret[] = $cdate;      
        }

        if ( isset ( $bysetpos ) ){ //must wait till all other BYxx are processed
          $mth = date('m', $cdate);
          sort ($yret);  
          sort ($bysetpos);
          $setposdate = mktime ( $hour, $minute, 0, $mth, 1, $thisyear ) ;
          $dim = date('t',$setposdate); //days in month
          $yretcnt =  count($yret);
          $bysetposcnt =  count ($bysetpos);       
          for ( $i = 0; $i < $bysetposcnt; $i++ ){ 
            if ($bysetpos[$i] > 0 && $bysetpos[$i] <= $yretcnt ) {
              $ret[] = $yret[$bysetpos[$i] -1];
            } else if ( abs( $bysetpos[$i] ) <= $yretcnt ) {
              $ret[] = $yret[$yretcnt + $bysetpos[$i] ];     
            }
          }       
        } else if ( ! empty ( $yret)){  //add all BYxx additional dates
          $yret = array_unique ($yret);
          $ret = array_merge ( $ret, $yret );
        }  
        sort ( $ret);
        $thismonth += $interval;
        $cdate = mktime ( $hour, $minute, 0, $thismonth, $thisday, $thisyear ) ;
        $mdate = mktime ( $hour, $minute, 0, $thismonth, 1, $thisyear ) ;
        $n=count($ret);
      }//end while  
    } else if ($rpt_type == 'yearly') {
      //this RRULE is VERY difficult to parse becauseRFC2445 doesn't
      //give any guidance on which BYxxx are mutually exclusive
      //We will assume that:
      //BYMONTH, BYMONTHDAY, BYDAY go together. BYDAY will be parsed relative to BYMONTH
      //if BYDAY is used without BYMONTH, then it is relative to the current year (i.e 20MO)
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $thisday   = substr($dateYmd, 6, 2);
   //skip to this year if called from query_events and we don't need count
  if ( ! empty ( $jump) && $Count == 999 ) {
     while ( date ( 'Y',$cdate ) < date ( 'Y', $jump ) ) {
          $thisyear += $interval;
     $cdate = mktime ( $hour, $minute, 0, 1, 1, $thisyear ) ;
    }
      }      
      $cdate = mktime ( $hour,  $minute, 0, $thismonth, $thisday, $thisyear ) ;
      while ($cdate <= $realend && $n <= $Count) {
        $yret = array();
        $ycd = date( 'Y', $cdate);
        $fdoy = mktime ( 0,0,0, 1 , 1, $ycd);//first day of year
        $fdow = date('w', $fdoy ); //day of week first day of year
        $ldoy = mktime ( 0,0,0, 12, 31, $ycd); //last day of year
        $ldow = date('w', $ldoy ); //day of week last day  of year
        $dow = date('w', $cdate ); //day of week
        $week = date('W', $cdate ); //ISO 8601 number of week
        if ( isset($bymonth) ) {
          foreach($bymonth as $month) { 
            $mdate  = mktime( $hour, $minute, 0, $month, 1, $ycd);                    
            $bydayvalues = $bymonthdayvalues = array();
            if ( isset($byday) )
             $bydayvalues = get_byday ( $byday, $mdate, 'month' );
            if ( isset($bymonthday) ) 
             $bymonthdayvalues = get_bymonthday ( $bymonthday, $mdate, $date, $realend );
            if ( ! empty ( $bydayvalues ) && ! empty ( $bymonthdayvalues )){
              $bydaytemp = array_intersect ( $bymonthdayvalues, $bydayvalues );       
              $yret = array_merge ( $yret, $bydaytemp );  
            } else if ( ! empty ( $bymonthdayvalues ) ) {
              $yret = array_merge ( $yret, $bymonthdayvalues );
            } else if ( ! empty ( $bydayvalues ) ) {
              $yret = array_merge ( $yret, $bydayvalues );      
            } else {
              $yret[] = mktime( $hour, $minute, 0, $month, $thisday, $ycd);      
            }
       
          }  //end foreach bymonth
        } else if (isset($byyearday)) {//end if isset bymonth
          foreach ($byyearday as $yearday) {
            ereg ('([-\+]{0,1})?([0-9]{1,3})', $yearday, $match);
            if ($match[1] == '-' && ( $cdate >= $date ) ) {
              $yret[] = mktime($hour, $minute,0,12,31 - $match[2] - 1,$thisyear);
            } else if ( ( $n <= $Count ) && ( $cdate >= $date )){
              $yret[] = mktime($hour, $minute,0,1,$match[2] ,$thisyear);
            }
          } 
        } else if (isset($byweekno)){ 
          $wkst_date = ( $Wkst == 'SU'? $cdate + ( ONE_DAY ): $cdate );
          if ( isset($byday) ) {
            $bydayvalues = get_byday ( $byday, $cdate, 'year' );
          }
          if ( in_array ( $week, $byweekno )  ) {
            if ( isset  ( $bydayvalues ) )  {
              foreach ( $bydayvalues as $bydayvalue ) { 
                if  ( $week == date('W', $bydayvalue ) )         
                  $yret[] = $bydayvalue;
              }            
            } else { 
             $yret[] = $cdate;
            }
          }
        } else  if ( isset($byday) ) {
          $bydayvalues = get_byday ( $byday, $cdate, 'year' );
          if ( ! empty ( $bydayvalues ) )$yret = array_merge ( $yret, $bydayvalues );         
        } else { //No Byxx rules apply
          $ret[] = $cdate;
        }

        if ( isset ( $bysetpos ) ){ //must wait till all other BYxx are processed
          sort ($yret);
          $bysetposcnt =  count ($bysetpos);  
          for ( $i = 0; $i < $bysetposcnt; $i++ ){ 
            if ($bysetpos[$i] > 0 ) {
              $ret[] = $yret[$bysetpos[$i] -1];
            } else {
              $ret[] = $yret[count($yret) + $bysetpos[$i] ];     
            }
          }     
        } else if ( ! empty ( $yret)){  //add all BYxx additional dates
          $yret = array_unique ( $yret );
          $ret = array_merge ( $ret, $yret );
        } 
        sort ($ret);
        $n = count ($ret);
        $thisyear += $interval;
        $cdate = mktime ( $hour, $minute, 0, $thismonth, $thisday, $thisyear ) ;
      }
    } //end if rpt_type
  } 
  if ( ! empty ( $ex_days )  ) {
    foreach ($ex_days as $ex_day ) {
      for ( $i =0, $cnt = count($ret); $i< $cnt;$i++ ) {
        if ( isset($ret[$i] ) &&  date ('Ymd', $ret[$i]) == 
          substr( $ex_day, 0, 8 ) ){
          unset ($ret[$i]);
        }
      }
    }
  }
  if ( ! empty (  $inc_days ) ) {
    foreach ( $inc_days as $inc_day ) {
      $ret[] = strtotime( $inc_day );    
    }
  }
  //remove any unset elements
  sort ( $ret );
  //we want results in YYYYMMDD format
  if ( ! empty ( $jump ) ) {
    for ( $i =0, $retcnt = count($ret); $i< $retcnt;$i++ ) {
      if ( isset( $ret[$i]) )
        $ret[$i] = date ('Ymd', $ret[$i] );  
    }
  } 
  return $ret;
}

/**
 * Get the corrected timestamp after adding ONE_WEEK
 * or ONE_DAY to compensate for DST
 *
 */
function add_dstfree_time ( $date, $span, $interval=1 ) {
  $ctime = date ( 'G', $date );
  $date += $span * $interval;
  $dtime = date ( 'G', $date );
  if ( $ctime == $dtime  ) {
    return $date;  
  } else if ( $ctime == 23 && $dtime == 0 ) {
    $date -= ONE_HOUR;
  } else if ( $ctime == 0 && $dtime == 23 ) {
    $date += ONE_HOUR;
  } else if ( $ctime > $dtime  ) {
    $date += ONE_HOUR;    
  } else if ( $ctime < $dtime  ) {
    $date -= ONE_HOUR;  
  }   
  return $date;
}

/**
 * Get the dates the correspond to the byday values
 *
 * @param array $byday         ByDay values to process (MO,TU,-1MO,20MO...)
 * @param string $cdate         First day of target search (Unix timestamp)
 * @param string $type          Month, Year, Week (default = month)
 *
 * @return array                Dates that match ByDay (YYYYMMDD format)
 *
 */
function get_byday ( $byday, $cdate, $type ='month' ) {
  global $byday_values, $byday_names;

  if ( empty ( $byday ) ) return;
  $ret = array();
  $yr = date ( 'Y', $cdate);
  $mth = date('m', $cdate);
  $hour = date ('H', $cdate);
  $minute = date ('i', $cdate);
  if ( $type == 'month' ) {
    $fday = mktime ( 0,0,0, $mth, 1, $yr);//first day of month
    $lday = mktime ( 0,0,0, $mth +1,  0 , $yr);//last day of month 
    $ditype = date('t',$cdate); //days in month
    $month = $mth;
  } else if ( $type == 'year' ) {
    $fday = mktime ( 0,0,0, 1 , 1, $yr);//first day of year
    $lday = mktime ( 0,0,0, 12, 31, $yr);//last day of year
    $ditype = date('L',$cdate) + 365; //days in year
    $month = 1;
  } else if ( $type == 'daily' ) {
    $fday = $cdate;
    $lday = $cdate;
    $month = $mth;
  } else {
   //we'll see if this is needed
   return;
 }
 $fdow = date('w', $fday ); //day of week first day of $type 
 $ldow = date('w', $lday ); //day of week last day of $type

 foreach($byday as $day) {  
  $byxxxDay = '';  
  $dayTxt = substr ( $day , -2, 2);
  $dayOffset = substr_replace ( $day, '', -2, 2);
  $dowOffset = ( ( -1 * $byday_values[$dayTxt] ) + 7 )  % 7; //SU=0, MO=6, TU=5...
  if ( is_numeric ($dayOffset)  && $dayOffset > 0 ) {
   //offset from beginning of $type
   $dayOffsetDays = (( $dayOffset - 1 ) * 7 ); //1 = 0, 2 = 7, 3 = 14...      
   $forwardOffset = $byday_values[$dayTxt] - $fdow;
   if ($forwardOffset <0 ) $forwardOffset += 7;
   $domOffset = ( 1 + $forwardOffset + $dayOffsetDays );
   if ( $domOffset <= $ditype ) {
     $byxxxDay = mktime ( $hour, $minute,0, $month , $domOffset, $yr);
     if ( $mth == date('m',$byxxxDay ) ) 
   $ret[] = $byxxxDay;
   }

  } else if ( is_numeric ($dayOffset) ){  //offset from end of $type
   $dayOffsetDays = (( $dayOffset + 1 ) * 7 ); //-1 = 0, -2 = 7, -3 = 14...
   $byxxxDay = mktime ( $hour, $minute,0, $month +1, (0 - (( $ldow + $dowOffset ) %7 ) + $dayOffsetDays ), $yr );
   if ( $mth == date('m',$byxxxDay ) )                 
   $ret[] = $byxxxDay; 

  } else {
   if ( $type == 'daily' ) {
     if ( (date('w', $cdate) == $byday_values[$dayTxt]) )   
       $ret[] = $cdate;     
   } else {
     for ( $i = 1; $i<= $ditype; $i++ ){
      $loopdate = mktime ( $hour, $minute, 0, $month, $i,  $yr);     
       if ( (date('w', $loopdate) == $byday_values[$dayTxt]) ) {   
        $ret[] = $loopdate;
        $i += 6; //skip to next week
       }
     }
    } 
  }
 }
 return $ret;
}
 
/**
 * Get the dates the correspond to the bymonthday values
 *
 * @param array $bymonthday     ByMonthDay values to process (1,2,-1,-2...)
 * @param string $cdate         First day of target search (Unix timestamp)
 * @param string $date          First day of event (Unix timestamp)
 * @param string $realend       Last day of event (Unix timestamp)
 *
 * @return array                Dates that match ByMonthDay (YYYYMMDD format)
 *
 */
function get_bymonthday ( $bymonthday, $cdate, $date, $realend ) {
 if ( empty ( $bymonthday ) ) return;
  $ret =array();
  $yr = date ( 'Y', $cdate);
  $mth = date('m', $cdate);
  $hour = date ('H', $cdate);
  $minute = date ('i', $cdate);
 $dim = date('t',$cdate); //days in month
  foreach ( $bymonthday as $monthday) { 
  $adjustedDay = ( $monthday > 0 )? $monthday : $dim + $monthday +1;     
  $byxxxDay = mktime ( $hour, $minute,0, $mth , $adjustedDay, $yr);
  if ( $byxxxDay >= $date )
    $ret[] = $byxxxDay;
 }
 return $ret;
}

/**
 * Gets all the repeating events for the specified date.
 *
 * <b>Note:</b>
 * The global variable <var>$repeated_events</var> needs to be
 * set by calling {@link read_repeated_events()} first.
 *
 * @param string $user           Username
 * @param string $date           Date to get events for in YYYYMMDD format
 * @param bool   $get_unapproved Include unapproved events in results?
 *
 * @return mixed The query result resource on queries (which can then be
 *               passed to {@link dbi_fetch_row()} to obtain the results), or
 *               true/false on insert or delete queries.
 *
 * @global array Array of {@link RepeatingEvent}s retreived using {@link read_repeated_events()}
 */
function get_repeating_entries ( $user, $dateYmd, $get_unapproved=true ) {
  global $repeated_events;
  $n = 0;
  $ret = array ();
  $repcnt = count ( $repeated_events );
  for ( $i = 0; $i < $repcnt; $i++ ) {
    if ( $repeated_events[$i]->getStatus() == 'A' || $get_unapproved ) {
      if ( in_array ($dateYmd, $repeated_events[$i]->getRepeatAllDates() ) ){
        $ret[$n++] = $repeated_events[$i];
      }
    }
  }
  return $ret;
}

/**
 * Converts a date to a timestamp.
 * 
 * @param string $d Date in YYYYMMDD or YYYYMMDDHHIISS format
 *
 * @return int Timestamp representing, in UTC time
 */
function date_to_epoch ( $d ) {
  if ( $d == 0 )
    return 0;
  $dH = $di = $ds = 0;
  if ( strlen ($d ) == 13 ) { //hour value is single digit
    $dH = substr ( $d, 8, 1 );
    $di = substr ( $d, 9, 2 ); 
    $ds = substr ( $d, 11, 2 );
  }
  if ( strlen ($d ) == 14 ) {
    $dH = substr ( $d, 8, 2 );
    $di = substr ( $d, 10, 2 ); 
    $ds = substr ( $d, 12, 2 );
  }
  $dm = substr ( $d, 4, 2 );
  $dd = substr ( $d, 6, 2 );
  $dY =  substr ( $d, 0, 4 );
  if ( $dY < 1970 || $dY > 2038 ) 
    return 0; 
  return gmmktime ( $dH, $di, $ds, $dm, $dd, $dY );
}

/**
 * Gets the previous weekday of the week that the specified date is in.
 *
 * If the date specified is a Sunday, then that date is returned.
 *
 * @param int $year  Year
 * @param int $month Month (1-12)
 * @param int $day   Day (1-31)
 *
 * @return int The date (in UNIX timestamp format)
 *
 */
function get_weekday_before ( $year, $month, $day=2 ) {
  global $WEEK_START, $DISPLAY_WEEKENDS;
  
  $laststr = ( $WEEK_START == 1 || $DISPLAY_WEEKENDS == 'N' ? 'last Mon':'last Sun' );
  //we default day=2 so if the 1ast is Sunday or Monday it will return the 1st
  $newdate = strtotime ( $laststr, mktime ( 0, 0, 0, $month, $day, $year ) );
  return $newdate;
}

/**
 * Generates the HTML for an add/edit/delete icon.
 *
 * This function is not yet used.  Some of the places that will call it have to
 * be updated to also get the event owner so we know if the current user has
 * access to edit and delete.
 *
 * @param int  $id         Event ID
 * @param bool $can_edit   Can this user edit this event?
 * @param bool $can_delete Can this user delete this event?
 *
 * @return HTML for add/edit/delete icon.
 *
 * @ignore
 */
function icon_text ( $id, $can_edit, $can_delete ) {
  global $readonly, $is_admin;
  $ret = '<a title="' . 
  translate('View this entry') . "\" href=\"view_entry.php?id=$id\"><img src=\"images/view.gif\" alt=\"" . 
  translate('View this entry') . '" style="border-width:0px; width:10px; height:10px;" /></a>';
  if ( $can_edit && $readonly == 'N' )
    $ret .= '<a title="' . translate('Edit entry') . 
    "\" href=\"edit_entry.php?id=$id\"><img src=\"images/edit.gif\" alt=\"" . 
  translate('Edit entry') . '" style="border-width:0px; width:10px; height:10px;" /></a>';
  if ( $can_delete && ( $readonly == 'N' || $is_admin ) )
    $ret .= '<a title="' . 
      translate('Delete entry') . "\" href=\"del_entry.php?id=$id\" onclick=\"return confirm('" .
  translate('Are you sure you want to delete this entry?', true) . "\\n\\n" . 
  translate('This will delete this entry for all users.') . '\');\"><img src="images/delete.gif" alt="' . 
  translate('Delete entry') . '" style="border-width:0px; width:10px; height:10px;" /></a>';
  return $ret;
}

/**
 * Prints all the calendar entries for the specified user for the specified date.
 *
 * If we are displaying data from someone other than
 * the logged in user, then check the access permission of the entry.
 *
 * @param string $date Date in YYYYMMDD format
 * @param string $user Username
 * @param bool   $ssi  Is this being called from week_ssi.php?
 */
function print_date_entries ( $date, $user, $ssi ) {
  global $events, $readonly, $is_admin, $login, $tasks, $DISPLAY_UNAPPROVED,
    $PUBLIC_ACCESS, $PUBLIC_ACCESS_CAN_ADD, $cat_id, $is_nonuser,
    $DISPLAY_TASKS_IN_GRID, $WEEK_START;

  $cnt = 0;

  $get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );

  
  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );
  $moons = getMoonPhases ( $year, $month );
  $can_add = ( $readonly == 'N' || $is_admin );
  if ( $PUBLIC_ACCESS == 'Y' && $PUBLIC_ACCESS_CAN_ADD != 'Y' &&
    $login == '__public__' )
    $can_add = false;
  if ( $readonly == 'Y' )
    $can_add = false;
  if ( $is_nonuser )
    $can_add = false;
  if ( ! $ssi && $can_add ) {
    $ret = '<a title="' .
      translate('New Entry') . '" href="edit_entry.php?';
    if ( strcmp ( $user, $login ) )
      $ret .= "user=$user&amp;";
    if ( ! empty ( $cat_id ) )
      $ret .= "cat_id=$cat_id&amp;";
    $ret .= "date=$date\"><img src=\"images/new.gif\" alt=\"" .
      translate('New Entry') . '" class="new" /></a>';
    $cnt++;
  }
  if ( ! $ssi ) {
    $ret .= '<a class="dayofmonth" href="day.php?';
    if ( strcmp ( $user, $login ) )
      $ret .= "user=$user&amp;";
    if ( ! empty ( $cat_id ) )
      $ret .= "cat_id=$cat_id&amp;";
    $ret .= "date=$date\">$day</a>";
    if ( ! empty ( $moons[$date] ) )
      $ret .= "<img src=\"images/{$moons[$date]}moon.gif\" />";
    $ret .= "<br />\n";
    $cnt++;
  }
 
  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date, $get_unapproved );
  $cur_rep = 0;

  // get all the non-repeating events for this date and store in $ev
  $ev = get_entries ( $date, $get_unapproved );

  // combine and sort the event arrays
  $ev = combine_and_sort_events($ev, $rep);
  if ( empty ( $DISPLAY_TASKS_IN_GRID ) ||  $DISPLAY_TASKS_IN_GRID == 'Y' ) {
  // get all due tasks for this date and before and store in $tk
    $tk = array();
    if ( $date >= date ('Ymd' ) ) {
      $tk = get_tasks ( $date, $get_unapproved );
    }
   $ev = combine_and_sort_events($ev, $tk);
 }
  $evcnt = count ( $ev );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    if ( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
      $ret .= print_entry ( $ev[$i], $date );
      $cnt++;
    }
  }
  if ( $cnt == 0 )
    $ret .= '&nbsp;'; // so the table cell has at least something

  return $ret;
}

/**
 * Checks to see if two events overlap.
 *
 * @param string $time1 Time 1 in HHMMSS format
 * @param int    $duration1 Duration 1 in minutes
 * @param string $time2 Time 2 in HHMMSS format
 * @param int    $duration2 Duration 2 in minutes
 *
 * @return bool True if the two times overlap, false if they do not
 */
function times_overlap ( $time1, $duration1, $time2, $duration2 ) {

  $hour1 = (int) ( $time1 / 10000 );
  $min1 = ( $time1 / 100 ) % 100;
  $hour2 = (int) ( $time2 / 10000 );
  $min2 = ( $time2 / 100 ) % 100;
  // convert to minutes since midnight
  // remove 1 minute from duration so 9AM-10AM will not conflict with 10AM-11AM
  if ( $duration1 > 0 )
    $duration1 -= 1;
  if ( $duration2 > 0 )
    $duration2 -= 1;
  $tmins1start = $hour1 * 60 + $min1;
  $tmins1end = $tmins1start + $duration1;
  $tmins2start = $hour2 * 60 + $min2;
  $tmins2end = $tmins2start + $duration2;

  if ( ( $tmins1start >= $tmins2end ) || ( $tmins2start >= $tmins1end ) )
    return false;
  return true;
}

/**
 * Checks for conflicts.
 *
 * Find overlaps between an array of dates and the other dates in the database.
 *
 * Limits on number of appointments: if enabled in System Settings
 * (<var>$LIMIT_APPTS</var> global variable), too many appointments can also
 * generate a scheduling conflict.
 * 
 * @todo Update this to handle exceptions to repeating events
 *
 * @param array  $dates        Array of dates in Timestamp format that is
 *                             checked for overlaps.
 * @param int    $duration     Event duration in minutes
 * @param int    $eventstart   GMT starttime timestamp
 * @param array  $participants Array of users whose calendars are to be checked
 * @param string $login        The current user name
 * @param int    $id           Current event id (this keeps overlaps from
 *                             wrongly checking an event against itself)
 *
 * @return Empty string for no conflicts or return the HTML of the
 *         conflicts when one or more are found.
 */
function check_for_conflicts ( $dates, $duration, $eventstart,
  $participants, $login, $id ) {
  global $LIMIT_APPTS, $LIMIT_APPTS_NUMBER, $repeated_events, $single_user,
  $single_user_login;

  if (!count($dates)) return false;
  $hour = gmdate ( 'H', $eventstart );
  $minute = gmdate ( 'i', $eventstart ); 
  $evtcnt = $query_params = array();
  

  $sql = 'SELECT distinct webcal_entry_user.cal_login, webcal_entry.cal_time,' .
    'webcal_entry.cal_duration, webcal_entry.cal_name, ' .
    'webcal_entry.cal_id, webcal_entry.cal_ext_for_id, ' .
    'webcal_entry.cal_access, ' .
    'webcal_entry_user.cal_status, webcal_entry.cal_date ' .
    'FROM webcal_entry, webcal_entry_user ' .
    'WHERE webcal_entry.cal_id = webcal_entry_user.cal_id ' .
    'AND (';
  $datecnt = count($dates);
  for ($x = 0; $x < $datecnt; $x++) {
    if ($x != 0) $sql .= ' OR ';
    $sql.='webcal_entry.cal_date = ' . gmdate ('Ymd', $dates[$x] );
  }
  $sql .=  ') AND webcal_entry.cal_time >= 0 ' .
    "AND webcal_entry_user.cal_status IN ('A','W') AND ( ";
  if ( $single_user == 'Y' ) {
     $participants[0] = $single_user_login;
  } else if ( strlen ( $participants[0] ) == 0 ) {
     // likely called from a form with 1 user
     $participants[0] = $login;
  }
  $partcnt = count ( $participants );
  for ( $i = 0; $i < $partcnt; $i++ ) {
    if ( $i > 0 )
      $sql .= ' OR ';

    $sql .= ' webcal_entry_user.cal_login = ?';
  $query_params[] = $participants[$i];
  }
  $sql .= ' )';
  // make sure we don't get something past the end date of the
  // event we are saving.
  $conflicts = '';
  $res = dbi_execute ( $sql, $query_params );
  $found = array();
  $count = 0;
  $privateStr = translate('Private');
  $confidentialStr = translate('Confidential');
  $allDayStr = translate('All day event');
  $exceedsStr = translate ( 'exceeds limit of XXX events per day' );
  if ( $res ) {
    $time1 = sprintf ( "%d%02d00", $hour, $minute );
    $duration1 = sprintf ( "%d", $duration );
    while ( $row = dbi_fetch_row ( $res ) ) {
      //Add to an array to see if it has been found already for the next part.
      $found[$count++] = $row[4];
      // see if either event overlaps one another
      if ( $row[4] != $id && ( empty ( $row[5] ) || $row[5] != $id ) ) {
        $time2 = sprintf ( "%06d", $row[1] );
        $duration2 = $row[2];
        $cntkey = $row[0] . '-' . $row[8];
        if ( empty ( $evtcnt[$cntkey] ) )
          $evtcnt[$cntkey] = 0;
        else
          $evtcnt[$cntkey]++;
        $over_limit = 0;
        if ( $LIMIT_APPTS == 'Y' && $LIMIT_APPTS_NUMBER > 0
          && $evtcnt[$cntkey] >= $LIMIT_APPTS_NUMBER ) {
          $over_limit = 1;
        }
        if ( $over_limit ||
          times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
          $conflicts .= '<li>';
          if ( $single_user != 'Y' )
            $conflicts .= "$row[0]: ";
          if ( $row[6] == 'R' && $row[0] != $login ) {
            $conflicts .=  '(' . $privateStr . ')';
          } else if ( $row[6] == 'C' && $row[0] != $login  && 
            !$is_assistant  && !$is_nonuser_admin) {
            //assistants can see confidential stuff
            $conflicts .=  '(' . $confidentialStr . ')';
          } else {
            $conflicts .=  "<a href=\"view_entry.php?id=$row[4]";
            if ( $row[0] != $login )
              $conflicts .= "&amp;user=$row[0]";
            $conflicts .= "\">$row[3]</a>";
          }
          if ( $duration2 == ( 24 * 60 ) && $time2 == 0 ) {
            $conflicts .= ' (' . $allDayStr . ')';
          } else {
            $conflicts .= ' (' . display_time ( $row[8] . $time2 );
            if ( $duration2 > 0 )
              $conflicts .= '-' .
                display_time ( $row[8] . add_duration ( $time2, $duration2 ) );
            $conflicts .= ')';
          }
          $conflicts .= ' on ' . date_to_str( $row[8] );
          if ( $over_limit ) {
            $tmp = str_replace ( 'XXX', $LIMIT_APPTS_NUMBER, $exceedsStr );
            $conflicts .= ' (' . $tmp . ')';
          }
          $conflicts .= "</li>\n";
        }
      }
    }
    dbi_free_result ( $res );
  } else {
    db_error ( true );
  }
  
  for ($q=0;$q < $partcnt;$q++) {
    $time1 = sprintf ( "%d%02d00", $hour, $minute );
    $duration1 = sprintf ( "%d", $duration );
    //This date filter is not necessary for functional reasons, but it eliminates some of the
    //events that couldn't possibly match.  This could be made much more complex to put more
    //of the searching work onto the database server, or it could be dropped all together to put
    //the searching work onto the client.
    $date_filter  = "AND (webcal_entry.cal_date <= " . gmdate ('Ymd',$dates[count($dates)-1]);
    $date_filter .= " AND (webcal_entry_repeats.cal_end IS NULL OR webcal_entry_repeats.cal_end >= " . gmdate ('Ymd',$dates[0]) . "))";
    //Read repeated events only once for a participant for
    //for performance reasons.
    $repeated_events=query_events($participants[$q],true,$date_filter);
    for ($i=0; $i < $datecnt; $i++) {
      $dateYmd = gmdate ('Ymd', $dates[$i] );
      $list = get_repeating_entries($participants[$q],$dateYmd);
      $thisyear = substr($dateYmd, 0, 4);
      $thismonth = substr($dateYmd, 4, 2);
      $listcnt = count($list);
      for ($j=0; $j < $listcnt;$j++) {
        //okay we've narrowed it down to a day, now I just gotta check the time...
        //I hope this is right...
        $row = $list[$j];
        if ( $row->getID() != $id && ( $row->getExtForID() == '' || 
          $row->getExtForID() != $id ) ) {
          $time2 = sprintf ( "%06d", $row->getTime() );
          $duration2 = $row->getDuration();
          if ( times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
            $conflicts .= '<li>';
            if ( $single_user != 'Y' )
              $conflicts .= $row->getLogin() . ': ';
            if ( $row->getAccess() == 'R' && $row->getLogin() != $login ) {
              $conflicts .=  '(' . translate('Private') . ')';
            } else if ( $row->getAccess() == 'C' && $row->getLogin() != $login &&
              !$is_assistant  && !$is_nonuser_admin) {
              //assistants can see confidential stuff
              $conflicts .=  '(' . translate('Confidential') . ')';
            } else {
              $conflicts .=  '<a href="view_entry.php?id=' . $row->getID();
              if ( ! empty ( $user ) && $user != $login )
                $conflicts .= "&amp;user=$user";
              $conflicts .= '">' . $row->getName() . '</a>';
            }
            $conflicts .= ' (' . display_time ( $dateYmd . $time2 );
            if ( $duration2 > 0 )
              $conflicts .= '-' .
                display_time ( $dateYmd . add_duration ( $time2, $duration2 ) );
            $conflicts .= ')';
            $conflicts .= ' on ' . date('l, F j, Y', $dates[$i]);
            $conflicts .= "</li>\n";
          }
        }

      }
    }
  }
   
  return $conflicts;
}

/**
 * Converts a time format HHMMSS (like 130000 for 1PM) into number of minutes past midnight.
 *
 * @param string $time Input time in HHMMSS format
 *
 * @return int The number of minutes since midnight
 */
function time_to_minutes ( $time ) {
  $h = (int) ( $time / 10000 );
  $m = (int) ( $time / 100 ) % 100;
  $num = $h * 60 + $m;
  return $num;
}

/**
 * Calculates which row/slot this time represents.
 *
 * This is used in day and week views where hours of the time are separeted
 * into different cells in a table.
 *
 * <b>Note:</b> the global variable <var>$TIME_SLOTS</var> is used to determine
 * how many time slots there are and how many minutes each is.  This variable
 * is defined user preferences (or defaulted to admin system settings).
 *
 * @param string $time       Input time in HHMMSS format
 * @param bool   $round_down Should we change 1100 to 1059?
 *                           (This will make sure a 10AM-100AM appointment just
 *                           shows up in the 10AM slow and not in the 11AM slot
 *                           also.)
 *
 * @return int The time slot index
 */
function calc_time_slot ( $time, $round_down = false ) {
  global $TIME_SLOTS;
  $time = sprintf ( "%06d", $time );
  $interval = ( 24 * 60 ) / $TIME_SLOTS;
  $mins_since_midnight = time_to_minutes ( $time ); 
  $ret = (int) ( $mins_since_midnight / $interval );
  if ( $round_down ) {
    if ( $ret * $interval == $mins_since_midnight )
      $ret--;
  }
  if ( $ret > $TIME_SLOTS )
    $ret = $TIME_SLOTS;

  return $ret;
}

/**
 * Generates the HTML for an icon to add a new event.
 *
 * @param string $date   Date for new event in YYYYMMDD format
 * @param int    $hour   Hour of day (0-23)
 * @param int    $minute Minute of the hour (0-59)
 * @param string $user   Participant to initially select for new event
 *
 * @return string The HTML for the add event icon
 */
function html_for_add_icon ( $date=0,$hour='', $minute='', $user='' ) {
  global $login, $readonly, $cat_id;
  $u_url = '';

  if ( $readonly == 'Y' )
    return '';

  if ( $minute < 0 ) {
   $minute = abs($minute);
   $hour = $hour -1;
  }
  if ( ! empty ( $user ) && $user != $login )
    $u_url = "user=$user&amp;";
  return '<a title="' . 
    translate('New Entry') . '" href="edit_entry.php?' . $u_url .
    "date=$date" . ( strlen ( $hour ) > 0 ? "&amp;hour=$hour": '' ) .
    ( $minute > 0 ? "&amp;minute=$minute": '' ) .
    ( empty ( $user ) ? '': "&amp;defusers=$user" ) .
    ( empty ( $cat_id ) ? '': "&amp;cat_id=$cat_id" ) .
    '"><img src="images/new.gif" class="new" alt="' . 
    translate('New Entry') . "\" /></a>\n";
}

/**
 * Generates the HTML for an event to be viewed in the week-at-glance (week.php).
 *
 * The HTML will be stored in an array (global variable $hour_arr)
 * indexed on the event's starting hour.
 *
 * @param Event  $event          The event
 * @param string $date           Date for which we're printing (in YYYYMMDD format)
 * @param string $override_class If set, then this is the class to use
 * @param bool   $show_time      If enabled, then event time is displayed
 */
function html_for_event_week_at_a_glance ( $event, $date, 
  $override_class='', $show_time=true ) {
  global $first_slot, $last_slot, $hour_arr, $rowspan_arr, $rowspan,
    $eventinfo, $login, $user, $is_assistant, $is_nonuser_admin;
  global $DISPLAY_ICONS, $PHP_SELF, $TIME_SPACER;
  global $layers, $DISPLAY_TZ, $categories;
  static $key = 0;
  
  $cal_type = $event->getCalTypeName(); 
  
  if ( access_is_enabled () ) {
    $time_only = access_user_calendar ( 'time', $event->getLogin() );
    $can_access = access_user_calendar ( 'view', $event->getLogin(), '', 
      $event->getCalType(), $event->getAccess() );
    if ( $cal_type == 'task' && $can_access == 0 )
      return false;    
  } else {
    $time_only = 'N';
    $can_access = CAN_DOALL;
  }


    
  $id = $event->getID();
  $name = $event->getName();

  // Figure out which time slot it goes in. Put tasks in with AllDay and Untimed
  if ( ! $event->isUntimed() && ! $event->isAllDay() && $cal_type != 'task' ) {
    $tz_time = date( 'His', $event->getDateTimeTS() );
    $ind = calc_time_slot ( $tz_time );
    if ( $ind < $first_slot )
      $first_slot = $ind;
    if ( $ind > $last_slot )
      $last_slot = $ind;
  } else {
    $ind = 9999;
  }
  
  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $class = 'layerentry';
  } else {
    $class = 'entry';
    if ( $event->getStatus() == 'W' ) $class = 'unapprovedentry';
  }

  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, 'view_m.php' ) ||
    strstr ( $PHP_SELF, 'view_w.php' ) ||
    strstr ( $PHP_SELF, 'view_v.php' ) ||
    strstr ( $PHP_SELF, 'view_r.php' ) ||
    strstr ( $PHP_SELF, 'view_t.php' ) )
    $class = 'entry';

  if ( ! empty ( $override_class ) )
    $class .= ' ' . $override_class;

  // avoid php warning for undefined array index
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = '';

  $catIcon = 'icons/cat-' . $event->getCategory() . '.gif';
  if ( $event->getCategory() > 0 && file_exists ( $catIcon ) ) {
    $catAlt = translate ( 'Category' ) . ': ' . $categories[$event->getCategory()];
    $hour_arr[$ind] .= "<img src=\"$catIcon\" alt=\"$catAlt\" title=\"$catAlt\" />";
  }

  $popupid = "eventinfo-pop$id-$key";
  $linkid  = "pop$id-$key";
  $key++;

  //build entry link if UAC permits viewing
  $time_spacer = ( $time_only == 'Y' ? '' : $TIME_SPACER );
  if ( $can_access != 0 && $time_only != 'Y') {
    //make sure clones have parents url date
    $linkDate = (  $event->getClone()?$event->getClone(): $date ); 
    $href = "href=\"view_entry.php?id=$id&amp;date=$linkDate";
    if ( $cal_type == 'task' ) {
      $title  = '<a title="' . translate ( 'View this task' ) . '"'; 
      $hour_arr[$ind] .= '<img src="images/task.gif" class="bullet" alt="*" /> ';    
    } else { //must be event
      $title  = '<a title="' . translate ( 'View this event' ) . '"';        
      if ( $event->isAllDay()  || $event->isUntimed()) {
        $hour_arr[$ind] .= '<img src="images/circle.gif" class="bullet" alt="*" /> ';
      }
    }
  } else {
    $title =  '<a title="" ' ;
    $href = '';  
  }   

  $hour_arr[$ind] .= $title .  " class=\"$class\" id=\"$linkid\" " . $href;
  if ( strlen ( $GLOBALS['user'] ) > 0 )
    $hour_arr[$ind] .= "&amp;user=" . $GLOBALS['user'];
  $hour_arr[$ind] .= '">';
  if ( $event->getPriority() == 3 )
    $hour_arr[$ind] .= '<strong>';

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ( $layer['cal_layeruser'] == $event->getLogin() ) {
       $in_span = true;
        $hour_arr[$ind] .= '<span style="color:' . $layer['cal_color'] . ';">';
      }
    }
  }
  if ( $event->isAllDay() ) {
    $timestr = translate('All day event');
    // Set start cell of all-day event to beginning of work hours
    if ( empty ( $rowspan_arr[$first_slot] ) )
      $rowspan_arr[$first_slot] = 0; // avoid warning below
    // which slot is end time in? take one off so we don't
    //commented out this section because it was breaking
    // the display if All Day is followed by a timed event
    //$rowspan = $last_slot - $first_slot + 1;
    //if ( $rowspan > $rowspan_arr[$first_slot] && $rowspan > 1 )
    //  $rowspan_arr[$first_slot] = $rowspan;
    //We'll skip tasks  here as well
  } else if ( $event->getTime() >= 0  && $cal_type != 'task' ) {
    if ( $show_time )
      $hour_arr[$ind] .= display_time ( $event->getDatetime() ) . $time_spacer;
    $timestr = display_time ( $event->getDatetime() );
    if ( $event->getDuration() > 0 ) {
      $timestr .= '-' . display_time ( $event->getEndDateTime() , $DISPLAY_TZ );
      $end_time = date( 'His', $event->getEndDateTimeTS() );
      //this fixes the improper display if an event ends at or after midnight
      if ( $end_time <  $tz_time ){
        $end_time += 240000;
      }
    } else {
      $end_time = 0;
    }
    if ( empty ( $rowspan_arr[$ind] ) )
      $rowspan_arr[$ind] = 0; // avoid warning below
    // which slot is end time in? take one off so we don't
    // show 11:00-12:00 as taking up both 11 and 12 slots.
    $endind = calc_time_slot ( $end_time, true );
    if ( $endind == $ind )
      $rowspan = 0;
    else
      $rowspan = $endind - $ind + 1;
    if ( $rowspan > $rowspan_arr[$ind] && $rowspan > 1 )
      $rowspan_arr[$ind] = $rowspan;
  } else {
    $timestr = '';
  }

  // avoid php warning of undefined index when using .= below
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = '';
  $hour_arr[$ind] .= build_entry_label ( $event, $popupid, 
    $can_access, $timestr, $time_only );

  if ( ! empty ( $in_span ) )
    $hour_arr[$ind] .= '</span>'; //end color span
  if ( $event->getPriority() == 3 ) $hour_arr[$ind] .= '</strong>'; //end font-weight span
    $hour_arr[$ind] .= '</a>';
  //if ( $DISPLAY_ICONS == 'Y' ) {
  //  $hour_arr[$ind] .= icon_text ( $id, true, true );
  //}
  $hour_arr[$ind] .= "<br />\n";
}

/**
 * Generates the HTML for an event to be viewed in the day-at-glance (day.php).
 *
 * The HTML will be stored in an array (global variable $hour_arr)
 * indexed on the event's starting hour.
 *
 * @param Event  $event The event
 * @param string $date  Date of event in YYYYMMDD format
 */
function html_for_event_day_at_a_glance ( $event, $date ) {
  global $first_slot, $last_slot, $hour_arr, $rowspan_arr, $rowspan,
    $eventinfo, $login, $user, $DISPLAY_DESC_PRINT_DAY,
    $ALLOW_HTML_DESCRIPTION, $layers, $PHP_SELF, $categories;
  static $key = 0;


  $id = $event->getID();
  $name = $event->getName();

  $cal_type = $event->getCalTypeName();
    
  if ( access_is_enabled () ) {
    $time_only = access_user_calendar ( 'time', $event->getLogin() );
    $can_access = access_user_calendar ( 'view', $event->getLogin(), '', 
      $event->getCalType(), $event->getAccess() );
    if ( $cal_type == 'task' && $can_access == 0 )
      return false;      
  } else {
    $time_only = 'N';
    $can_access = CAN_DOALL;
  }


  $time = $event->getTime();

  // If TZ_OFFSET make this event before the start of the day or
  // after the end of the day, adjust the time slot accordingly.
  if ( ! $event->isUntimed()  && ! $event->isAllDay() && $cal_type != 'task' ) {
   $tz_time = date( 'His', $event->getDateTimeTS() );
    $ind = calc_time_slot ( $tz_time );
    if ( $ind < $first_slot )
      $first_slot = $ind;
    if ( $ind > $last_slot )
      $last_slot = $ind;
  } else {
    $ind = 9999;
  }
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = '';

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $class = 'layerentry';
  } else {
    $class = 'entry';
    if ( $event->getStatus() == 'W' )
      $class = 'unapprovedentry';
  }
  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, 'view_m.php' ) ||
    strstr ( $PHP_SELF, 'view_w.php' )  || 
    strstr ( $PHP_SELF, 'view_v.php' ) ||
    strstr ( $PHP_SELF, 'view_t.php' ) )
    $class = 'entry';

  $popupid = "eventinfo-pop$id-$key";
  $linkid  = "pop$id-$key";
  $key++;
  
  $catIcon = 'icons/cat-' . $event->getCategory() . '.gif';
  if ( $event->getCategory() > 0 && file_exists ( $catIcon ) ) {
    $catAlt = translate ( 'Category' ) . ': ' . $categories[$event->getCategory()];
    $hour_arr[$ind] .= "<img src=\"$catIcon\" alt=\"$catAlt\" title=\"$catAlt\" />";
  }  

  $cal_link = 'view_entry.php';
  if ( $cal_type == 'task' ) {
    $view_text = translate ( 'View this task' );
    $hour_arr[$ind] .= '<img src="images/task.gif" class="bullet" alt="*" /> ';    
  } else {
    $view_text = translate ( 'View this event' );    
  }
  
    //make sure clones have parents url date
    $linkDate = (  $event->getClone()?$event->getClone(): $date );
    $href = '';
    if (  $can_access != 0 && $time_only != 'Y') {
      $href = "href=\"$cal_link?id=$id&amp;date=$linkDate";
      if ( strlen ( $GLOBALS['user'] ) > 0 )
       $href .= '&amp;user=' . $GLOBALS['user'];
       $href .= '"';
    }
    $hour_arr[$ind] .= '<a title="' . $view_text .
      "\" class=\"$class\" id=\"$linkid\" $href";
    $hour_arr[$ind] .= '">';
    
  if ( $event->getPriority() == 3 ) $hour_arr[$ind] .= '<strong>';

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ( $layer['cal_layeruser'] == $event->getLogin() ) {
     $in_span = true;
        $hour_arr[$ind] .= '<span style="color:' . $layer['cal_color'] . ';">';
      }
    }
  }

  if ( $event->isAllDay() ) {
    $hour_arr[$ind] .= '[' . translate('All day event') . '] ';
  } else if ( $time >= 0  && ! $event->isAllDay() && $cal_type != 'task' ) {
    $hour_arr[$ind] .= '[' . display_time ( $event->getDatetime() );
    if ( $event->getDuration() > 0 ) {
      $hour_arr[$ind] .= "-" . display_time ( $event->getEndDateTime() );
      // which slot is end time in? take one off so we don't
      // show 11:00-12:00 as taking up both 11 and 12 slots.
      $end_time = date( 'His', $event->getEndDateTimeTS() );
      //this fixes the improper display if an event ends at or after midnight
      if ( $end_time <  $tz_time ){
        $end_time += 240000;
      }         
      $endind = calc_time_slot ( $end_time, true );
      if ( $endind == $ind )
        $rowspan = 0;
      else
        $rowspan = $endind - $ind + 1;
      if ( ! isset ( $rowspan_arr[$ind] ) )
        $rowspan_arr[$ind] = 0;
      if ( $rowspan > $rowspan_arr[$ind] && $rowspan > 1 )
        $rowspan_arr[$ind] = $rowspan;
    }
    $hour_arr[$ind] .= '] ';
  }
  $hour_arr[$ind] .= build_entry_label ( $event, $popupid, $can_access, '', $time_only );

  if ( $event->getPriority() == 3 ) $hour_arr[$ind] .= '</strong>'; //end font-weight span

  $hour_arr[$ind] .= '</a>';
  if ( $DISPLAY_DESC_PRINT_DAY == 'Y' ) {
    $hour_arr[$ind] .= "\n<dl class=\"desc\">\n";
    $hour_arr[$ind] .= '<dt>' . translate('Description') . ":</dt>\n<dd>";
    if ( ! empty ( $ALLOW_HTML_DESCRIPTION ) && $ALLOW_HTML_DESCRIPTION == 'Y' ) {
      $hour_arr[$ind] .= $event->getDescription();
    } else {
      $hour_arr[$ind] .= strip_tags ( $event->getDescription() );    
    }
    $hour_arr[$ind] .= "</dd>\n</dl>\n";
  }

  $hour_arr[$ind] .= "<br />\n";
}

/**
 * Prints all the calendar entries for the specified user for the specified date in day-at-a-glance format.
 *
 * If we are displaying data from someone other than
 * the logged in user, then check the access permission of the entry.
 *
 * @param string $date Date in YYYYMMDD format
 * @param string $user Username of calendar
 */
function print_day_at_a_glance ( $date, $user, $can_add=0 ) {
  global $first_slot, $last_slot, $hour_arr, $rowspan_arr, $rowspan, $DISPLAY_UNAPPROVED;
  global $TABLEBG, $CELLBG, $TODAYCELLBG, $THFG, $THBG, $TIME_SLOTS;
  global $WORK_DAY_START_HOUR, $WORK_DAY_END_HOUR, $DISPLAY_TASKS_IN_GRID;
  //global $repeated_events;
  $get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );
  $ret = '';
  if ( empty ( $TIME_SLOTS ) ) {
    $ret .= "Error: TIME_SLOTS undefined!<br />\n";
    return $ret;
  }

  // $interval is number of minutes per slot
  $interval = ( 24 * 60 ) / $TIME_SLOTS;
    
  $rowspan_arr = array ();
  for ( $i = 0; $i < $TIME_SLOTS; $i++ ) {
    $rowspan_arr[$i] = 0;
  }



  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date );
  $cur_rep = 0;

  // Get static non-repeating events
  $ev = get_entries ( $date, $get_unapproved );
  // combine and sort the event arrays
  $ev = combine_and_sort_events($ev, $rep);
    
  if ( empty ( $DISPLAY_TASKS_IN_GRID ) ||  $DISPLAY_TASKS_IN_GRID == 'Y' ) {
  // get all due tasks for this date and before and store in $tk
    $tk = array();
    if ( $date >= date ('Ymd' ) ) {
    $tk = get_tasks ( $date, $get_unapproved );
    }
   $ev = combine_and_sort_events($ev, $tk);
 }
    

  $hour_arr = array ();
  $interval = ( 24 * 60 ) / $TIME_SLOTS;
  $first_slot = (int) ( ( ( $WORK_DAY_START_HOUR ) * 60 ) / $interval );
  $last_slot = (int) ( ( ( $WORK_DAY_END_HOUR  ) * 60 ) / $interval);
  $rowspan_arr = array ();
  $evcnt = count ( $ev );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    if ( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
      html_for_event_day_at_a_glance ( $ev[$i], $date );
    }
  }

  // squish events that use the same cell into the same cell.
  // For example, an event from 8:00-9:15 and another from 9:30-9:45 both
  // want to show up in the 8:00-9:59 cell.
  $rowspan = 0;
  $last_row = -1;
  $i = 0;
  if ( $first_slot < 0 )
    $i = $first_slot;
  for ( ; $i < $TIME_SLOTS; $i++ ) {
    if ( $rowspan > 1 ) {
      if ( ! empty ( $hour_arr[$i] ) ) {
        $diff_start_time = $i - $last_row;
        if ( ! empty ( $rowspan_arr[$i] ) && $rowspan_arr[$i] > 1 ) {
          if (  $rowspan_arr[$i] + ( $diff_start_time ) >  $rowspan_arr[$last_row]  ) {
            $rowspan_arr[$last_row] = ( $rowspan_arr[$i] + ( $diff_start_time ) );
          }
          $rowspan += ( $rowspan_arr[$i] - 1 );
        } else {
          if ( ! empty ( $rowspan_arr[$i] ) )
            $rowspan_arr[$last_row] += $rowspan_arr[$i];
        }
        // this will move entries apart that appear in one field,
        // yet start on different hours
        for ( $u = $diff_start_time ; $u > 0 ; $u-- ) {
          $hour_arr[$last_row] .= "<br />\n"; 
        }
        $hour_arr[$last_row] .= $hour_arr[$i];
        $hour_arr[$i] = '';
        $rowspan_arr[$i] = 0;
      }
      $rowspan--;
    } else if ( ! empty ( $rowspan_arr[$i] ) && $rowspan_arr[$i] > 1 ) {
      $rowspan = $rowspan_arr[$i];
      $last_row = $i;
    }
  }
  $ret .= '<table class="glance" cellspacing="0" cellpadding="0">';
  if ( ! empty ( $hour_arr[9999] ) ) {
    $ret .= '<tr><th class="empty">&nbsp;</th>' .
      "\n<td class=\"hasevents\">$hour_arr[9999]</td></tr>\n";
  }
  $rowspan = 0;
  for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
    $time_h = (int) ( ( $i * $interval ) / 60 );
    $time_m = ( $i * $interval ) % 60;
    $time = display_time ( ( $time_h * 100 + $time_m ) * 100 );
    $ret .= "<tr>\n<th class=\"row\">" . $time . "</th>\n";
    if ( $rowspan > 1 ) {
      // this might mean there's an overlap, or it could mean one event
      // ends at 11:15 and another starts at 11:30.
      if ( ! empty ( $hour_arr[$i] ) ) {
        $ret .= '<td class="hasevents">';
        if ( $can_add )
          $ret .= html_for_add_icon ( $date, $time_h, $time_m, $user );
        $ret .= "$hour_arr[$i]</td>\n";
      }
      $rowspan--;
    } else {
      if ( empty ( $hour_arr[$i] ) ) {
        $ret .= '<td>';
        if ( $can_add ) {
          $ret .= html_for_add_icon ( $date, $time_h, $time_m, $user ) . '</td>';
        } else {
          $ret .= "&nbsp;</td>";
        }
      } else {
        if ( empty ( $rowspan_arr[$i] ) )
          $rowspan = '';
        else
          $rowspan = $rowspan_arr[$i];
        if ( $rowspan > 1 ) {
          $ret .= "<td rowspan=\"$rowspan\" class=\"hasevents\">";
          if ( $can_add )
            $ret .= html_for_add_icon ( $date, $time_h, $time_m, $user );
          $ret .= "$hour_arr[$i]</td>\n";
        } else {
          $ret .= '<td class="hasevents">';
          if ( $can_add )
            $ret .= html_for_add_icon ( $date, $time_h, $time_m, $user );
          $ret .= "$hour_arr[$i]</td>\n";
        }
      }
    }
    $ret .= "</tr>\n";    
  }
  $ret .= "</table>\n";
  return $ret;
}

/**
 * Checks for any unnaproved events.
 *
 * If any are found, display a link to the unapproved events (where they can be
 * approved).
 *
 * If the user is an admin user, also count up any public events.
 * If the user is a nonuser admin, count up events on the nonuser calendar.
 *
 * @param string $user Current user login
 */
function display_unapproved_events ( $user ) {
  global $PUBLIC_ACCESS, $is_admin, $NONUSER_ENABLED, $login, $is_nonuser;
  
  $app_users = array ();
  $app_user_hash = array ( );
  $ret = '';
  // Don't do this for public access login, admin user must approve public
  // events if UAC is not enabled
  if ( $user == '__public__' || $is_nonuser )
    return;
  
  $query_params = array();
  $sql = 'SELECT COUNT(webcal_entry_user.cal_id) ' .
    'FROM webcal_entry_user, webcal_entry ' .
    'WHERE webcal_entry_user.cal_id = webcal_entry.cal_id ' .
    "AND webcal_entry_user.cal_status = 'W' " .
    'AND ( webcal_entry.cal_ext_for_id IS NULL ' .
    'OR webcal_entry.cal_ext_for_id = 0 ) ' .
    'AND ( webcal_entry_user.cal_login = ?';
  $query_params[] = $user;

  if ( $PUBLIC_ACCESS == 'Y' && $is_admin && ! access_is_enabled () ) {
    $sql .= " OR webcal_entry_user.cal_login = '__public__'";
  }

  if ( access_is_enabled () ) {
    $app_users[] = $login;
    $app_user_hash[$login] = 1;
    if ( $NONUSER_ENABLED == 'Y' ) {
      $all = array_merge ( get_my_users ( ), get_nonuser_cals ( $login ) );
    } else {
      $all = get_my_users ( );
    }
    for ( $j = 0, $cnt = count ( $all );  $j < $cnt; $j++ ) {
      $x = $all[$j]['cal_login'];
      if ( access_user_calendar ( 'approve', $x ) ) {
        if ( empty ( $app_user_hash[$x] ) ) { 
          $app_users[] = $x;
          $app_user_hash[$x] = 1;
        }
      }
    }    
    for ( $i = 0, $cnt = count ( $app_users ); $i < $cnt; $i++ ) {
      $sql .= ' OR webcal_entry_user.cal_login = ? ';
    $query_params[] = $app_users[$i];
    }
  } else if ( $NONUSER_ENABLED == 'Y' ) {
    $admincals = get_nonuser_cals ( $login );
    for ( $i = 0, $cnt = count ( $admincals ); $i < $cnt; $i++ ) {
      $sql .= ' OR webcal_entry_user.cal_login = ? ';
    $query_params[] = $admincals[$i]['cal_login'];
    }
  }  
  $sql .= ' )';
  $rows = dbi_get_cached_rows ( $sql, $query_params );
  if ( $rows ) {
    $row = $rows[0];
    if ( $row ) {
      if ( $row[0] > 0 ) {
        $str = translate ('You have XXX unapproved entries');
        $str = str_replace ( 'XXX', $row[0], $str );
        $ret .= '<a class="nav" href="list_unapproved.php';
        if ( $user != $login )
          $ret .= "?user=$user\"";
        $ret .= '">' . $str .  "</a><br />\n";
      }
    }
  }

  return $ret;
}

/**
 * Looks for URLs in the given text, and makes them into links.
 *
 * @param string $text Input text
 *
 * @return string The text altered to have HTML links for any web links
 *                (http or https)
 */
function activate_urls ( $text ) {
  $str = eregi_replace ( "(http://[^[:space:]$]+)",
    "<a href=\"\\1\">\\1</a>", $text );
  $str = eregi_replace ( "(https://[^[:space:]$]+)",
    "<a href=\"\\1\">\\1</a>", $str );
  return $str;
}

/**
 * Displays a time in either 12 or 24 hour format.
 *
 *
 * @param string $time          Input time in HHMMSS format
 *   Optionally, the format can be YYYYMMDDHHMMSS
 * @param int   $control bitwise command value 
 *   0 default 
 *   1 ignore_offset Do not use the timezone offset
 *   2 show_tzid Show abbrev TZ id ie EST after time
 *   4 use server's timezone
 * @param int $timestamp  optional input time in timestamp format
 * @param string $format  user's TIME_FORMAT when sending emails
 *
 * @return string The time in the user's timezone and preferred format
 *
 */
function display_time ( $time='', $control=0, $timestamp='', $format='' ) {
  global $TIME_FORMAT, $SERVER_TIMEZONE;
  
  if (  $control & 4 ) { 
    $currentTZ = getenv ( 'TZ' );
    set_env ( 'TZ', $SERVER_TIMEZONE );
  }
  $tzid = date ( ' T' ); //default tzid for today
  $t_format = ( empty ( $format )? $TIME_FORMAT : $format );

  if ( ! empty ( $time ) && strlen ( $time >=13 ) )
    $timestamp = date_to_epoch ( $time );

  if ( ! empty ( $timestamp ) ) {
    // $control & 1 = do not do timezone calculations
    if (  $control & 1 ) {
      $time = gmdate ( 'His',$timestamp );
      $tzid = ' GMT';      
    } else {
      $time = date ( 'His',$timestamp );
      $tzid = date ( ' T', $timestamp );
    }
  }

  $hour = (int) ( $time / 10000 );
  $min = abs( ( $time / 100 ) % 100 );
  //Prevent goofy times like 8:00 9:30 9:00 10:30 10:00 
  if ( $time < 0 && $min > 0 ) $hour = $hour - 1;
  while ( $hour < 0 )
    $hour += 24;
  while ( $hour > 23 )
    $hour -= 24;
  if ( $t_format == '12' ) {
    $ampm = translate ( $hour >= 12  ? 'pm' : 'am' );
    $hour %= 12;
    if ( $hour == 0 )
      $hour = 12;
    $ret = sprintf ( "%d:%02d%s", $hour, $min, $ampm );
  } else {
    $ret = sprintf ( "%02d:%02d", $hour, $min );
  }
  if ( $control & 2 ) $ret .= $tzid;
  //reset timezone to previous value
  if ( ! empty ( $currentTZ ) ) set_env ( 'TZ', $currentTZ );
  return $ret;
}

/**
 * Returns the full name of the specified month.
 *
 * Use {@link month_short_name()} to get the abbreviated name of the month.
 *
 * @param int $m Number of the month (0-11)
 *
 * @return string The full name of the specified month
 *
 * @see month_short_name
 */
function month_name ( $m ) {
  switch ( $m ) {
    case 0: return translate('January');
    case 1: return translate('February');
    case 2: return translate('March');
    case 3: return translate('April');
    case 4: return translate('May_'); // needs to be different than "May"
    case 5: return translate('June');
    case 6: return translate('July');
    case 7: return translate('August');
    case 8: return translate('September');
    case 9: return translate('October');
    case 10: return translate('November');
    case 11: return translate('December');
  }
  return "unknown-month($m)";
}

/**
 * Returns the abbreviated name of the specified month (such as "Jan").
 *
 * Use {@link month_name()} to get the full name of the month.
 *
 * @param int $m Number of the month (0-11)
 *
 * @return string The abbreviated name of the specified month (example: "Jan")
 *
 * @see month_name
 */
function month_short_name ( $m ) {
  switch ( $m ) {
    case 0: return translate('Jan');
    case 1: return translate('Feb');
    case 2: return translate('Mar');
    case 3: return translate('Apr');
    case 4: return translate('May');
    case 5: return translate('Jun');
    case 6: return translate('Jul');
    case 7: return translate('Aug');
    case 8: return translate('Sep');
    case 9: return translate('Oct');
    case 10: return translate('Nov');
    case 11: return translate('Dec');
  }
  return "unknown-month($m)";
}

/**
 * Returns the full weekday name.
 *
 * Use {@link weekday_short_name()} to get the abbreviated weekday name.
 *
 * @param int $w Number of the day in the week (0=Sunday,...,6=Saturday)
 *
 * @return string The full weekday name ("Sunday")
 *
 * @see weekday_short_name
 */
function weekday_name ( $w ) {
  switch ( $w ) {
    case 0: return translate('Sunday');
    case 1: return translate('Monday');
    case 2: return translate('Tuesday');
    case 3: return translate('Wednesday');
    case 4: return translate('Thursday');
    case 5: return translate('Friday');
    case 6: return translate('Saturday');
  }
  return "unknown-weekday($w)";
}

/**
 * Returns the abbreviated weekday name.
 *
 * Use {@link weekday_name()} to get the full weekday name.
 *
 * @param int $w Number of the day in the week (0=Sunday,...,6=Saturday)
 *
 * @return string The abbreviated weekday name ("Sun")
 */
function weekday_short_name ( $w ) {
  switch ( $w ) {
    case 0: return translate('Sun');
    case 1: return translate('Mon');
    case 2: return translate('Tue');
    case 3: return translate('Wed');
    case 4: return translate('Thu');
    case 5: return translate('Fri');
    case 6: return translate('Sat');
  }
  return "unknown-weekday($w)";
}

/**
 * Converts a date in YYYYMMDD format into "Friday, December 31, 1999",
 * "Friday, 12-31-1999" or whatever format the user prefers.
 *
 * @param string $indate       Date in YYYYMMDD format
 * @param string $format       Format to use for date (default is "__month__
 *                             __dd__, __yyyy__")
 * @param bool   $show_weekday Should the day of week also be included?
 * @param bool   $short_months Should the abbreviated month names be used
 *                             instead of the full month names?
 *
 * @return string Date in the specified format
 *
 * @global string Preferred date format
 */
function date_to_str ( $indate, $format='', $show_weekday=true, $short_months=false ) {
  global $DATE_FORMAT;

  if ( strlen ( $indate ) == 0 ) {
    $indate = date ('Ymd' );
  }
  // if they have not set a preference yet...
  if ( $DATE_FORMAT == ''  || $DATE_FORMAT == 'LANGUAGE_DEFINED' )
    $DATE_FORMAT = translate ( '__month__ __dd__, __yyyy__' );

  if ( empty ( $format ) )
    $format = $DATE_FORMAT;

  $y = (int) ( $indate / 10000 );
  $m = (int) ( $indate / 100 ) % 100;
  $d = $indate % 100;
  $j = (int) $d ;
  $date = mktime ( 0, 0, 0, $m, $d, $y );
  $wday = strftime ( "%w", $date );
  $mon = month_short_name ( $m - 1 );
  
  if ( $short_months ) {
    $weekday = weekday_short_name ( $wday );
    $month = $mon;
  } else {
    $weekday = weekday_name ( $wday );
    $month = month_name ( $m - 1 );
  }
  $yyyy = $y;
  $yy = sprintf ( "%02d", $y %= 100 );

  $ret = $format;
  $ret = str_replace ( "__yyyy__", $yyyy, $ret );
  $ret = str_replace ( "__yy__", $yy, $ret );
  $ret = str_replace ( "__month__", $month, $ret );
  $ret = str_replace ( "__mon__", $mon, $ret );
  $ret = str_replace ( "__dd__", $d, $ret );
  $ret = str_replace ( "__j__", $j, $ret );  
  $ret = str_replace ( "__mm__", $m, $ret );

  if ( $show_weekday )
    return "$weekday, $ret";
  else
    return $ret;
}


/**
 * Converts a hexadecimal digit to an integer.
 *
 * @param string $val Hexadecimal digit
 *
 * @return int Equivalent integer in base-10
 *
 * @ignore
 */
function hextoint ( $val ) {
  if ( empty ( $val ) )
    return 0;
  switch ( strtoupper ( $val ) ) {
    case '0': return 0;
    case '1': return 1;
    case '2': return 2;
    case '3': return 3;
    case '4': return 4;
    case '5': return 5;
    case '6': return 6;
    case '7': return 7;
    case '8': return 8;
    case '9': return 9;
    case 'A': return 10;
    case 'B': return 11;
    case 'C': return 12;
    case 'D': return 13;
    case 'E': return 14;
    case 'F': return 15;
  }
  return 0;
}

/**
 * Extracts a user's name from a session id.
 *
 * This prevents users from begin able to edit their cookies.txt file and set
 * the username in plain text.
 *
 * @param string $instr A hex-encoded string. "Hello" would be "678ea786a5".
 * 
 * @return string The decoded string
 *
 * @global array Array of offsets
 *
 * @see encode_string
 */
function decode_string ( $instr ) {
  global $offsets;
  $orig = '';
  for ( $i = 0; $i < strlen ( $instr ); $i += 2 ) {
    $ch1 = substr ( $instr, $i, 1 );
    $ch2 = substr ( $instr, $i + 1, 1 );
    $val = hextoint ( $ch1 ) * 16 + hextoint ( $ch2 );
    $j = ( $i / 2 ) % count ( $offsets );
    $newval = $val - $offsets[$j] + 256;
    $newval %= 256;
    $dec_ch = chr ( $newval );
    $orig .= $dec_ch;
  }
  return $orig;
}

/**
 * Takes an input string and encode it into a slightly encoded hexval that we
 * can use as a session cookie.
 *
 * @param string $instr Text to encode
 *
 * @return string The encoded text
 *
 * @global array Array of offsets
 *
 * @see decode_string
 */
function encode_string ( $instr ) {
  global $offsets;
  $ret = '';
  for ( $i = 0; $i < strlen ( $instr ); $i++ ) {
    $ch1 = substr ( $instr, $i, 1 );
    $val = ord ( $ch1 );
    $j = $i % count ( $offsets );
    $newval = $val + $offsets[$j];
    $newval %= 256;
    $ret .= bin2hex ( chr ( $newval ) );
  }
  return $ret;
}


/**
 * Loads current user's category info and stuff it into category global
 * variable.
 *
 * @param string $ex_global Don't include global categories ('' or '1')
 */
function load_user_categories ($ex_global = '') {
  global $login, $user, $is_assistant;
  global $categories, $category_owners;
  global $CATEGORIES_ENABLED, $is_admin;

  $cat_owner =  ( ( ! empty ( $user ) && strlen ( $user ) ) &&  ( $is_assistant  ||
    $is_admin ) ) ? $user : $login;  
  $categories = array ();
  $categories[-1] = translate ( 'None' );;
  $category_owners = array ();
  if ( $CATEGORIES_ENABLED == 'Y' ) {
    $sql = 'SELECT cat_id, cat_name, cat_owner FROM webcal_categories WHERE ';
    $query_params = array();
    if ( $ex_global == '' ) {
      $sql .= ' (cat_owner = ?) OR (cat_owner IS NULL) ORDER BY cat_owner, cat_name';
    } else {
      $sql .= ' cat_owner = ? ORDER BY cat_name';
    }
    $query_params[] = $cat_owner;
    $rows = dbi_get_cached_rows ( $sql, $query_params );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $cat_id = $row[0];
        $categories[$cat_id] = $row[1];
        $category_owners[$cat_id] = $row[2];
      }
    }
  } else {
    //Categories disabled
  }
}

/**
 * Prints dropdown HTML for categories.
 *
 * @param string $form   The page to submit data to (without .php)
 * @param string $date   Date in YYYYMMDD format
 * @param int    $cat_id Category id that should be pre-selected
 */
function print_category_menu ( $form, $date = '', $cat_id = '' ) {
  global $categories, $category_owners, $user, $login;
  $ret = '';
  $ret .= "<form action=\"{$form}.php\" method=\"get\" name=\"SelectCategory\" class=\"categories\">\n";
  if ( ! empty($date) ) 
    $ret .= "<input type=\"hidden\" name=\"date\" value=\"$date\" />\n";
  if ( ! empty ( $user ) && $user != $login )
    $ret .= "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  $ret .= translate ('Category') . ': <select name="cat_id" onchange="document.SelectCategory.submit()">' . "\n";
  $ret .= '<option value=""';
  if ( $cat_id == '' ) $ret .= ' selected="selected"';
  $ret .= ">" . translate('All') . "</option>\n";
  //'None' is added during load_user_categories
  $cat_owner =  ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login;
  if (  is_array ( $categories ) ) {
    foreach ( $categories as $K => $V ){
      if ( $cat_owner ||
        empty ( $category_owners[$K] ) ) {
        $ret .= "<option value=\"$K\"";
        if ( $cat_id == $K ) $ret .= ' selected="selected"';
        $ret .= ">$V</option>\n";
      }
    }
  }
  $ret .= "</select>\n</form>\n";
  $ret .= '<span id="cat">' . translate ('Category') . ': ';
  $ret .= ( strlen ( $cat_id ) ? $categories[$cat_id] : translate ( 'All' ) ) . 
  "</span>\n";

  return $ret;
}

/**
 * Converts HTML entities in 8bit.
 *
 * <b>Note:</b> Only supported for PHP4 (not PHP3).
 *
 * @param string $html HTML text
 *
 * @return string The converted text
 */
function html_to_8bits ( $html ) {
  if ( floor(phpversion()) < 4 ) {
    return $html;
  } else {
    return strtr ( $html, array_flip (
      get_html_translation_table (HTML_ENTITIES) ) );
  }
}

// ***********************************************************************
// Functions for getting information about boss and their assistant.
// ***********************************************************************

/**
 * Gets a list of an assistant's boss from the webcal_asst table.
 *
 * @param string $assistant Login of assistant
 *
 * @return array Array of bosses, where each boss is an array with the following
 *               fields:
 * - <var>cal_login</var>
 * - <var>cal_fullname</var>
 */
function user_get_boss_list ( $assistant ) {
  global $bosstemp_fullname;

  $rows = dbi_get_cached_rows (
    'SELECT cal_boss ' .
    'FROM webcal_asst ' .
    'WHERE cal_assistant = ?', array( $assistant ) );
  $count = 0;
  $ret = array ();
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      user_load_variables ( $row[0], 'bosstemp_' );
      $ret[$count++] = array (
        'cal_login' => $row[0],
        'cal_fullname' => $bosstemp_fullname
      );
    }
  }
  return $ret;
}

/**
 * Is this user an assistant of this boss?
 *
 * @param string $assistant Login of potential assistant
 * @param string $boss      Login of potential boss
 * 
 * @return bool True or false
 */
function user_is_assistant ( $assistant, $boss ) {
  $ret = false;

  if ( empty ( $boss ) )
    return false;
  $rows = dbi_get_cached_rows ( 'SELECT * FROM webcal_asst ' . 
     'WHERE cal_assistant = ? AND cal_boss = ?', array( $assistant, $boss ) );
  if ( $rows ) {
    $row = $rows[0];
    if ( ! empty ( $row[0] ) )
      $ret = true;
  }
  return $ret;
}

/**
 * Is this user an assistant?
 *
 * @param string $assistant Login for user
 *
 * @return bool true if the user is an assistant to one or more bosses
 */
function user_has_boss ( $assistant ) {
  $ret = false;
  $rows = dbi_get_cached_rows ( 'SELECT * FROM webcal_asst ' .
    'WHERE cal_assistant = ?', array( $assistant ) );
  if ( $rows ) {
    $row = $rows[0];
    if ( ! empty ( $row[0] ) )
      $ret = true;
  }
  return $ret;
}

/**
 * Checks the boss user preferences to see if the boss wants to be notified via
 * email on changes to their calendar.
 *
 * @param string $assistant Assistant login
 * @param string $boss      Boss login
 *
 * @return bool True if the boss wants email notifications
 */
function boss_must_be_notified ( $assistant, $boss ) {
  if (user_is_assistant ( $assistant, $boss ) )
    return ( get_pref_setting ( $boss, 'EMAIL_ASSISTANT_EVENTS' )=='Y' ? true : false );
  return true;
}

/**
 * Checks the boss user preferences to see if the boss must approve events
 * added to their calendar.
 *
 * @param string $assistant Assistant login
 * @param string $boss      Boss login
 *
 * @return bool True if the boss must approve new events
 */
function boss_must_approve_event ( $assistant, $boss ) {
  if (user_is_assistant ( $assistant, $boss ) )
    return ( get_pref_setting ( $boss, 'APPROVE_ASSISTANT_EVENT' )=='Y' ? true : false );
  return true;
}

/**
 * Fakes an email for testing purposes.
 *
 * @param string $mailto Email address to send mail to
 * @param string $subj   Subject of email
 * @param string $text   Email body
 * @param string $hdrs   Other email headers
 *
 * @ignore
 */
function fake_mail ( $mailto, $subj, $text, $hdrs ) { 
  echo "To: $mailto <br />\n" .
    "Subject: $subj <br />\n" .
    nl2br ( $hdrs ) . "<br />\n" .
    nl2br ( $text );
}

/**
 * Prints all the entries in a time bar format for the specified user for the
 * specified date.
 *
 * If we are displaying data from someone other than the logged in user, then
 * check the access permission of the entry.
 *
 * @param string $date Date in YYYYMMDD format
 * @param string $user Username
 * @param bool   $ssi  Should we not include links to add new events?
 */
function print_date_entries_timebar ( $date, $user, $ssi ) {
  global $events, $readonly, $is_admin, $DISPLAY_UNAPPROVED,
    $PUBLIC_ACCESS, $PUBLIC_ACCESS_CAN_ADD;
  $ret = '';
  $cnt = 0;
  $get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );

  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );

  $can_add = ( $readonly == 'N' || $is_admin );
  if ( $PUBLIC_ACCESS == 'Y' && $PUBLIC_ACCESS_CAN_ADD != 'Y' &&
    $GLOBALS['login'] == '__public__' )
    $can_add = false;

  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date ) ;
  $cur_rep = 0;

  // get all the non-repeating events for this date and store in $ev
  $ev = get_entries ( $date, $get_unapproved );

  // combine and sort the event arrays
  $ev = combine_and_sort_events($ev, $rep);
  $evcnt = count ( $ev );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    if ( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
      $ret .= print_entry_timebar ( $ev[$i], $date );
      $cnt++;
    }
  }
  if ( $cnt == 0 )
    $ret .= '&nbsp;'; // so the table cell has at least something

  return $ret;
}

/**
 * Prints the HTML for an event with a timebar.
 *
 * @param Event  $event The event
 * @param string $date  Date for which we're printing in YYYYMMDD format
 *
 * @staticvar int Used to ensure all event popups have a unique id
 */
function print_entry_timebar ( $event, $date ) {
  global $eventinfo, $login, $user, $PHP_SELF, $prefarray, $is_assistant,
    $is_nonuser_admin, $layers, $PUBLIC_ACCESS_FULLNAME;

  static $key = 0;
  $insidespan = false;
  $ret = '';
  if ( access_is_enabled () ) {
    $time_only = access_user_calendar ( 'time', $event->getLogin() );
    $can_access = access_user_calendar ( 'view', $event->getLogin(), '', 
      $event->getCalType(), $event->getAccess() );    
  } else {
    $time_only = 'N';
    $can_access = CAN_DOALL;
  }

  // compute time offsets in % of total table width
  $day_start=$prefarray['WORK_DAY_START_HOUR'] * 60;
  if ( $day_start == 0 ) $day_start = 1*60;
  $day_end=$prefarray['WORK_DAY_END_HOUR'] * 60;
  if ( $day_end == 0 ) $day_end = 23*60;
  if ( $day_end <= $day_start ) $day_end = $day_start + 60; //avoid exceptions

  $time = date ( 'His', $event->getDateTimeTS() );
  $endminutes = time_to_minutes ( date ( 'His', $event->getEndDateTimeTS() ) );
  if ( $time >= 0 ) {
  $bar_units= 100/(($day_end - $day_start)/60) ; // Percentage each hour occupies
  $ev_start = round((floor(($time/10000) - ($day_start/60)) + (($time/100)%100)/60) * $bar_units);
  }else{
    $ev_start= 0;
  }
  if ($ev_start < 0) $ev_start = 0;
  if ( $event->isAllDay() ) {
  // All day event
   $ev_start = 0;
   $ev_duration = 100;
  } else  if ( $endminutes < $day_start ) {
    $ev_duration = 1;  
  } else  if ($event->getDuration() > 0 ) {
    $ev_duration = round(100 * $event->getDuration() / ($day_end - $day_start)) ;
    if ($ev_start + $ev_duration > 100 ) {
      $ev_duration = 100 - $ev_start;
    } 
  } else {
    if ( $time >= 0) {
      $ev_duration = 1;
    } else {
      $ev_duration=100-$ev_start;
    }
  }
  $ev_padding = 100 - $ev_start - $ev_duration;
  // choose where to position the text (pos=0->before,pos=1->on,pos=2->after)
  if ($ev_duration > 20)   { $pos = 1; }
   elseif ($ev_padding > 20)   { $pos = 2; }
   else        { $pos = 0; }
 
  $ret .= "\n<!-- ENTRY BAR -->\n<table class=\"entrycont\" cellpadding=\"0\" cellspacing=\"0\">\n";
  $ret .= "<tr>\n";
  $ret .= ($ev_start > 0 ?  "<td style=\"text-align:right;  width:$ev_start%;\">": '' );
  if ( $pos > 0 ) {
    $ret .= ($ev_start > 0 ?  "&nbsp;</td>\n": '' ) ;
    $ret .= "<td style=\"width:$ev_duration%;\">\n<table class=\"entrybar\">\n<tr>\n<td class=\"entry\">";
    if ( $pos > 1 ) {
      $ret .= "&nbsp;</td>\n</tr>\n</table></td>\n";
      $ret .= "<td style=\"text-align:left; width:$ev_padding%;\">";
    }
  };

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $class = 'layerentry';
  } else {
    $class = 'entry';
    if ( $event->getStatus() == 'W' ) $class = 'unapprovedentry';
  }
  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, 'view_t.php' ) ||
    strstr ( $PHP_SELF, 'view_w.php' ) ||
    strstr ( $PHP_SELF, 'view_v.php' ) ||
    strstr ( $PHP_SELF, 'view_m.php' ) )
    $class = 'entry';

  if ( $event->getPriority() == 3 ) $ret .= '<strong>';

  $id = $event->getID();
  $name = $event->getName();

  $popupid = "eventinfo-pop$id-$key";
  $linkid  = "pop$id-$key";
  $key++;
  if ( $can_access != 0 && $time_only != 'Y' ) {
    //make sure clones have parents url date
    $linkDate = (  $event->getClone()?$event->getClone(): $date ); 
    $ret .= "<a class=\"$class\" id=\"$linkid\" " . 
      " href=\"view_entry.php?id=$id&amp;date=$linkDate";
    if ( strlen ( $user ) > 0 )
      $ret .= "&amp;user=" . $user;
    $ret .= '">';
  }
  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
        if($layer['cal_layeruser'] == $event->getLogin() ) {
            $insidespan = true;
            $ret .=('<span style="color:' . $layer['cal_color'] . ';">');
        }
    }
  }

  $ret .= '[' . ( $event->getLogin() == '__public__' ? 
    $PUBLIC_ACCESS_FULLNAME : $event->getLogin() ) . ']&nbsp;';
  $timestr = '';
  if ( $event->isAllDay() ) {
    $timestr = translate('All day event');
  } else if ( $time >= 0 ) {
    $timestr = display_time ( $event->getDatetime() );
    if ( $event->getDuration() > 0 ) {
      $timestr .= ' - ' . display_time ( $event->getEndDateTime(), 2 );
    }
  }
  $ret .= build_entry_label ( $event, $popupid, $can_access, $timestr, $time_only );

  if ( $insidespan ) { $ret .= ('</span>'); } //end color span
  $ret .= '</a>';
  if ( $event->getPriority() == 3 ) $ret .= '</strong>'; //end font-weight span
  $ret .= "</td>\n";
  if ( $pos < 2 ) {
    if ( $pos < 1 ) {
      $ret .= "<td style=\"width:$ev_duration%;\"><table  class=\"entrybar\">\n<tr>\n<td class=\"entry\">&nbsp;</td>\n";
    }
    $ret .= "</tr>\n</table></td>\n";
    $ret .= ($ev_padding > 0 ? "<td style=\"text-align:left; width:$ev_padding%;\">&nbsp;</td>\n": '' );
  }
  $ret .= "</tr>\n</table>\n";

  return $ret;
}

/**
 * Prints the header for the timebar.
 *
 * @param int $start_hour Start hour
 * @param int $end_hour   End hour
 */
function print_header_timebar($start_hour, $end_hour) {
  //      sh+1   ...   eh-1
  // +------+----....----+------+
  // |      |            |      |
  $ret = '';
  // print hours
  if ( ($end_hour - $start_hour) == 0 )
    $offset = 0;
  else
    $offset = (100/($end_hour - $start_hour)/2);
  //  if ( $offset < 3 ) $offset = 0;
    $ret .= "\n<!-- TIMEBAR -->\n<table class=\"timebar\">\n<tr><td style=\"width:$offset%;\">&nbsp;</td>\n";
   for ($i = $start_hour+1; $i < $end_hour; $i++) {
    $prev_offset = $offset;
    $offset = round(100/($end_hour - $start_hour)*($i - $start_hour + .5));
    $width = $offset - $prev_offset;
    if ( $i > 10 ) $width += .1;
    $ret .= "<td style=\"width:$width%;text-align:center;\">$i</td>\n";
   }
   $width = 100 - ( $offset * 2 );
   $ret .= "<td width=\"$width%\">&nbsp;</td>\n";
   $ret .= "</tr></table>\n";
 
   // print yardstick
  $ret .= "\n<!-- YARDSTICK -->\n<table class=\"yardstick\">\n<tr>\n";
  $offset = 0;
  for ($i = $start_hour; $i < $end_hour; $i++) {
    $prev_offset = $offset;

    $width = $offset - $prev_offset;
    $ret .= "<td style=\"width:$width%;\">&nbsp;</td>\n";
    $offset = round(100/($end_hour - $start_hour)*($i - $start_hour));
   }
   $ret .= "</tr>\n</table>\n<!-- /YARDSTICK -->\n";

  return $ret;
 }


/**
 * Determine if the specified user is a participant in the event.
 * User must have status 'A' or 'W'.
 *
 * @param int $id event id
 * @param string $user user login
 */
function user_is_participant ( $id, $user )
{
  $ret = false;

  $sql = 'SELECT COUNT(cal_id) FROM webcal_entry_user ' .
    'WHERE cal_id = ? AND cal_login = ? AND ' .
    "cal_status IN ('A','W')";
  $rows = dbi_get_cached_rows ( $sql, array( $id, $user ) );
  if ( ! $rows )
    die_miserable_death ( translate ( 'Database error') . ': ' .
      dbi_error () );

  if ( ! empty ( $rows[0] ) ) {
    $row = $rows[0];
    if ( ! empty ( $row ) )
      $ret = ( $row[0] > 0 );
  }

  return $ret;
}


/**
 * Gets a list of nonuser calendars and return info in an array.
 *
 * @param string $user Login of admin of the nonuser calendars
 * @param bool $remote Return only remote calendar  records
 *
 * @return array Array of nonuser cals, where each is an array with the
 *               following fields:
 * - <var>cal_login</var>
 * - <var>cal_lastname</var>
 * - <var>cal_firstname</var>
 * - <var>cal_admin</var>
 * - <var>cal_fullname</var>
 * - <var>cal_is_public</var>
 */
function get_nonuser_cals ($user = '', $remote=false) {
  global  $is_admin;
  $count = 0;
  $ret = array ();
  $sql = 'SELECT cal_login, cal_lastname, cal_firstname, ' .
    'cal_admin, cal_is_public, cal_url FROM webcal_nonuser_cals ';
  $query_params = array();

  if ($remote == false) { 
    $sql .= 'WHERE cal_url IS NULL ';
  } else {
    $sql .= 'WHERE cal_url IS NOT NULL ';  
  }
  
  if ($user != '') {
    $sql .= 'AND  cal_admin = ? ';
    $query_params[] = $user;
  }
  
  $sql .= 'ORDER BY cal_lastname, cal_firstname, cal_login';
  
  $rows = dbi_get_cached_rows ( $sql, $query_params );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      if ( strlen ( $row[1] ) || strlen ( $row[2] ) )
        $fullname = "$row[2] $row[1]";
      else
        $fullname = $row[0];
      $ret[$count++] = array (
        'cal_login' => $row[0],
        'cal_lastname' => $row[1],
        'cal_firstname' => $row[2],
        'cal_admin' => $row[3],
        'cal_is_public' => $row[4],
        'cal_url' => $row[5],
        'cal_fullname' => $fullname
      );
    }
  }
    // If user access control enabled, remove any users that this user
  // does not have 'view' access to.
  if ( access_is_enabled () && ! $is_admin ) {
    $newlist = array ();
    for ( $i = 0, $cnt = count ( $ret ); $i < $cnt; $i++ ) {
      if ( access_user_calendar ( 'view', $ret[$i]['cal_login'] ) )
        $newlist[] = $ret[$i];
    }
    $ret = $newlist;
  }
  return $ret;
}

/**
 * Loads nonuser variables (login, firstname, etc.).
 *
 * The following variables will be set:
 * - <var>login</var>
 * - <var>firstname</var>
 * - <var>lastname</var>
 * - <var>fullname</var>
 * - <var>admin</var>
 * - <var>email</var>
 *
 * @param string $login  Login name of nonuser calendar
 * @param string $prefix Prefix to use for variables that will be set.
 *                       For example, if prefix is "temp_", then the login will
 *                       be stored in the <var>$temp_login</var> global variable.
 */
function nonuser_load_variables ( $login, $prefix ) {
  global $error,$nuloadtmp_email;
  $ret =  false;
  $rows = dbi_get_cached_rows (
    'SELECT cal_login, cal_lastname, cal_firstname, ' .
    'cal_admin, cal_is_public, cal_url FROM ' .
    'webcal_nonuser_cals WHERE cal_login = ?', array( $login ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      if ( strlen ( $row[1] ) || strlen ( $row[2] ) )
        $fullname = "$row[2] $row[1]";
      else
        $fullname = $row[0];

        $GLOBALS[$prefix . 'login'] = $row[0];
        $GLOBALS[$prefix . 'firstname'] = $row[2];
        $GLOBALS[$prefix . 'lastname'] = $row[1];
        $GLOBALS[$prefix . 'fullname'] = $fullname;
        $GLOBALS[$prefix . 'admin'] = $row[3];
        $GLOBALS[$prefix . 'is_public'] = $row[4];
        $GLOBALS[$prefix . 'url'] = $row[5];
        $GLOBALS[$prefix . 'is_admin'] = false;
        $GLOBALS[$prefix . 'is_nonuser'] = true;
        // We need the email address for the admin
        user_load_variables ( $row[3], 'nuloadtmp_' );
        $GLOBALS[$prefix . 'email'] = $nuloadtmp_email;
        $ret = true;
    }
  }
  return $ret;
}

/**
  * Checks the webcal_nonuser_cals table to determine if the user is the
  * administrator for the nonuser calendar.
  *
  * @param string $login   Login of user that is the potential administrator
  * @param string $nonuser Login name for nonuser calendar
  *
  * @return bool True if the user is the administrator for the nonuser calendar
  */
function user_is_nonuser_admin ( $login, $nonuser ) {
  $ret = false;

  $rows = dbi_get_cached_rows ( 'SELECT cal_admin FROM webcal_nonuser_cals ' .
    'WHERE cal_login = ? AND cal_admin = ?', array( $nonuser, $login ) );
  if ( $rows ) {
    if ( ! empty ( $rows[0] ) )
      $ret = true;
  }
  return $ret;
}

/**
 * Loads nonuser preferences from the webcal_user_pref table if on a nonuser
 * admin page.
 *
 * @param string $nonuser Login name for nonuser calendar
 */
function load_nonuser_preferences ($nonuser) {
  global $prefarray, $DATE_FORMAT_MY, $DATE_FORMAT, $DATE_FORMAT_MD;
  $rows = dbi_get_cached_rows (
    'SELECT cal_setting, cal_value FROM webcal_user_pref ' .
    'WHERE cal_login = ?', array( $nonuser ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $setting = $row[0];
      $value = $row[1];
      $sys_setting = 'sys_' . $setting;
      // save system defaults
      // ** don't override ones set by load_user_prefs
      if ( ! empty ( $GLOBALS[$setting] ) && empty ( $GLOBALS['sys_' . $setting] ))
        $GLOBALS['sys_' . $setting] = $GLOBALS[$setting];
      $GLOBALS[$setting] = $value;
      $prefarray[$setting] = $value;
    }
  }
 // reset_language ( empty ( $LANGUAGE) || $LANGUAGE != 'none'? $LANGUAGE : $browser_lang );
  if (  empty ( $DATE_FORMAT ) || $DATE_FORMAT == 'LANGUAGE_DEFINED' ){
    $DATE_FORMAT = translate ( '__month__ __dd__, __yyyy__' );
  }
  if ( empty ( $DATE_FORMAT_MY ) || $DATE_FORMAT_MY == 'LANGUAGE_DEFINED' ){  
    $DATE_FORMAT_MY = translate ( '__month__ __yyyy__' );  
  }
  if ( empty ( $DATE_FORMAT_MD ) || $DATE_FORMAT_MD == 'LANGUAGE_DEFINED' ){  
    $DATE_FORMAT_MD = translate ( '__month__ __dd__' );  
  }   
}

/**
 * Determines what the day is and sets it globally.
 * All times are in the user's timezone
 *
 * The following global variables will be set:
 * - <var>$thisyear</var>
 * - <var>$thismonth</var>
 * - <var>$thisday</var>
 * - <var>$thisdate</var>
 * - <var>$today</var>
 *
 * @param string $date The date in YYYYMMDD format
 */
function set_today($date='') {
  global $thisyear, $thisday, $thismonth, $thisdate, $today;
  global $month, $day, $year, $thisday;

  $today = mktime() ;

  if ( ! empty ( $date ) ) {
    $thisyear = substr ( $date, 0, 4 );
    $thismonth = substr ( $date, 4, 2 );
    $thisday = substr ( $date, 6, 2 );
  } else {
    $thismonth = ( empty ( $month ) || $month == 0 ? date('m', $today): $month );
    $thisyear = ( empty ( $year ) || $year == 0 ? date( 'Y', $today):$year );
    $thisday = ( empty ( $day ) || $day == 0 ? date ( 'd', $today):$day );
  }
  $thisdate = sprintf ( "%04d%02d%02d", $thisyear, $thismonth, $thisday );
}

/**
 * Converts from Gregorian Year-Month-Day to ISO YearNumber-WeekNumber-WeekDay.
 *
 * @internal JGH borrowed gregorianToISO from PEAR Date_Calc Class and added

 * $GLOBALS['WEEK_START'] (change noted)
 *
 * @param int $day   Day of month
 * @param int $month Number of month
 * @param int $year  Year
 *
 * @return string Date in ISO YearNumber-WeekNumber-WeekDay format
 *
 * @ignore
 */
function gregorianToISO($day,$month,$year) {
  global $WEEK_START;
    $mnth = array (0,31,59,90,120,151,181,212,243,273,304,334);
    $y_isleap = isLeapYear($year);
    $y_1_isleap = isLeapYear($year - 1);
    $day_of_year_number = $day + $mnth[$month - 1];
    if ($y_isleap && $month > 2) {
        $day_of_year_number++;
    }
    // find Jan 1 weekday (monday = 1, sunday = 7)
    $yy = ($year - 1) % 100;
    $c = ($year - 1) - $yy;
    $g = $yy + intval($yy/4);
    $jan1_weekday = 1 + intval((((($c / 100) % 4) * 5) + $g) % 7);


    // JGH added next if/else to compensate for week begins on Sunday
    if (! $WEEK_START && $jan1_weekday < 7) {
      $jan1_weekday++;
    } elseif (! $WEEK_START && $jan1_weekday == 7) {
      $jan1_weekday=1;
    }

    // weekday for year-month-day
    $h = $day_of_year_number + ($jan1_weekday - 1);
    $weekday = 1 + intval(($h - 1) % 7);
    // find if Y M D falls in YearNumber Y-1, WeekNumber 52 or
    if ($day_of_year_number <= (8 - $jan1_weekday) && $jan1_weekday > 4){
        $yearnumber = $year - 1;
        if ($jan1_weekday == 5 || ($jan1_weekday == 6 && $y_1_isleap)) {
            $weeknumber = 53;
        } else {
            $weeknumber = 52;
        }
    } else {
        $yearnumber = $year;
    }
    // find if Y M D falls in YearNumber Y+1, WeekNumber 1
    if ($yearnumber == $year) {
        if ($y_isleap) {
            $i = 366;
        } else {
            $i = 365;
        }
        if (($i - $day_of_year_number) < (4 - $weekday)) {
            $yearnumber++;
            $weeknumber = 1;
        }
    }
    // find if Y M D falls in YearNumber Y, WeekNumber 1 through 53
    if ($yearnumber == $year) {
        $j = $day_of_year_number + (7 - $weekday) + ($jan1_weekday - 1);
        $weeknumber = intval($j / 7);
        if ($jan1_weekday > 4) {
            $weeknumber--;
        }
    }
    // put it all together
    if ($weeknumber < 10)
        $weeknumber = '0'.$weeknumber;
    return "{$yearnumber}-{$weeknumber}-{$weekday}";
}

/**
 * Is this a leap year?
 *
 * @internal JGH Borrowed isLeapYear from PEAR Date_Calc Class
 *
 * @param int $year Year
 *
 * @return bool True for a leap year, else false
 *
 * @ignore
 */
function isLeapYear($year='') {
  if (empty($year)) $year = strftime("%Y",time());
  if (strlen($year) != 4) return false;
  if (preg_match('/\D/',$year)) return false;
  return (($year % 4 == 0 && $year % 100 != 0) || $year % 400 == 0);
}

/**
 * Replaces unsafe characters with HTML encoded equivalents.
 *
 * @param string $value Input text
 *
 * @return string The cleaned text
 */
function clean_html($value){
  $value = htmlspecialchars($value, ENT_QUOTES);
  $value = strtr($value, array(
    '('   => '&#40;',
    ')'   => '&#41;'
  ));
  return $value;
}

/**
 * Removes non-word characters from the specified text.
 *
 * @param string $data Input text
 *
 * @return string The converted text
 */
function clean_word($data) { 
  return preg_replace("/\W/", '', $data);
}

/**
 * Removes non-digits from the specified text.
 *
 * @param string $data Input text
 *
 * @return string The converted text
 */
function clean_int($data) { 
  return preg_replace("/\D/", '', $data);
}

/**
 * Removes whitespace from the specified text.
 *
 * @param string $data Input text
 * 
 * @return string The converted text
 */
function clean_whitespace($data) { 
  return preg_replace("/\s/", '', $data);
}

/**
 * Converts language names to their abbreviation.
 *
 * @param string $name Name of the language (such as "French")
 *
 * @return string The abbreviation ("fr" for "French")
 */
function languageToAbbrev ( $name ) {
  global $browser_languages;
  foreach ( $browser_languages as $abbrev => $langname ) {
    if ( $langname == $name )
      return $abbrev;
  }
  return false;
}

/**
 * Draws a daily outlook style availability grid showing events that are
 * approved and awaiting approval.
 *
 * @param string $date         Date to show the grid for
 * @param array  $participants Which users should be included in the grid
 * @param string $popup        Not used
 */
function daily_matrix ( $date, $participants, $popup = '' ) {
  global $CELLBG, $TODAYCELLBG, $THFG, $THBG, $TABLEBG;
  global $user_fullname, $repeated_events, $events, $TIME_FORMAT;
  global $WORK_DAY_START_HOUR, $WORK_DAY_END_HOUR, $ENTRY_SLOTS;
 
  $ret = '';
  $entrySlots = ( $ENTRY_SLOTS >288 ? 288 : ( $ENTRY_SLOTS <72 ? 72 : $ENTRY_SLOTS ) ); 
  $increment = (int)( 1440 / $entrySlots );
  $interval = (int)( 60 / $increment );

  $participant_pct = '20%'; //use percentage

  $first_hour = $WORK_DAY_START_HOUR;
  $last_hour = $WORK_DAY_END_HOUR;
  $hours = $last_hour - $first_hour;
  $cols = (($hours * $interval) + 1);
  $total_pct = '80%';
  $cell_pct =  (int) (80 /($hours * $interval) );
  $master = array();
  $dateTS = date_to_epoch ( $date);
  // Build a master array containing all events for $participants
  $cnt = count ( $participants );
  for ( $i = 0; $i < $cnt; $i++ ) {
    /* Pre-Load the repeated events for quckier access */
    $repeated_events = read_repeated_events ( $participants[$i], '', $dateTS );
    /* Pre-load the non-repeating events for quicker access */
    $events = read_events ( $participants[$i], $dateTS , $dateTS );

    // get all the repeating events for this date and store in array $rep
    $rep = get_repeating_entries ( $participants[$i], $dateTS );
    // get all the non-repeating events for this date and store in $ev
    $ev = get_entries ( $date );

    // combine into a single array for easy processing
    $ALL = array_merge ( $rep, $ev );
    foreach ( $ALL as $E ) {
      if ($E->getTime() == 0) { 
        $time = $first_hour.'0000';
        $duration = 60 * $hours;
      } else {
        $time = date ( 'His', $E->getDateTimeTS());
        $duration = $E->getDuration();
      }
      $hour = substr($time, 0, 2 );
      $mins = substr($time, 2, 2 );
       
      // convert cal_time to slot
      $slot= $hour + substr ($mins,0,1) ;

      // convert cal_duration to bars
      $bars = $duration / $increment;

      // never replace 'A' with 'W'
      for ($q = 0; $bars > $q; $q++) {
        $slot = sprintf ("%02.2f",$slot);
        if (strlen($slot) == 4) $slot = '0'.$slot; // add leading zeros
        $slot = $slot.''; // convert to a string
        if ( empty ( $master['_all_'][$slot] ) ||
          $master['_all_'][$slot]['stat'] != 'A') {
          $master['_all_'][$slot]['stat'] = $E->getStatus();
        }
        if ( empty ( $master[$participants[$i]][$slot] ) ||
          $master[$participants[$i]][$slot]['stat'] != 'A' ) {
          $master[$participants[$i]][$slot]['stat'] = $E->getStatus();
          $master[$participants[$i]][$slot]['ID'] = $E->getID();
        }
        $slot = $slot + ($increment * .01);
        if ( $slot - (int)$slot >= .59 ) $slot = (int)$slot +1;
      }

    }
  }
$partStr = translate('Participants');

$ret .= <<<EOT
  <br />
  <table  align="center" class="matrixd" style="width:{$total_pct};" cellspacing="0" cellpadding="0">
  <tr><td class="matrix" colspan="{$cols}"></td></tr>
  <tr><th style="width:{$participant_pct};">{$partStr}</th>
EOT;

  $str = '';
  //$MouseOut = 'onmouseout="this.style.backgroundColor=\'' .$THBG . '\';"';
 // $MouseOver = "onmouseover=\"this.style.backgroundColor='#CCFFCC';\"";
  $MouseOut = '';
  $MouseOver = "";
  $titleStr = ' title="' .translate ( 'Schedule an appointment for' ) . ' ';
  $CC = 1;
  for($i=$first_hour;$i<$last_hour;$i++) {
    $hour = $i;
    if ( $TIME_FORMAT == '12' ) {
      $hour %= 12;
      if ( $hour == 0 ) $hour = 12;
      $hourfmt = "%d";
    } else {
      $hourfmt = "%02d";
    }
     $halfway = (int)(($interval /2 ) -1 );
     for($j=0;$j<$interval;$j++) {
        $str .= ' <td  id="C'.$CC.'" class="dailymatrix" ';
        $MouseDown = 'onmousedown="schedule_event('.$i.','.
          sprintf ("%02d",($increment * $j)).');"';
        switch($j) {
          case $halfway:
            $k = ($hour<=9?'0':substr($hour,0,1));
            $str .= 'style="width:'.$cell_pct.'%; text-align:right;"  '.
              $MouseDown. $MouseOver . $MouseOut. $titleStr .
              sprintf ($hourfmt, $hour).':'.($increment * $j<=9?'0':'').
              ($increment * $j). '.">';
            $str .= $k."</td>\n";
            break;
          case $halfway +1:
           $k = ($hour<=9?substr($hour,0,1):substr($hour,1,2));
           $str .= 'style="width:'.$cell_pct.'%; text-align:left;" '.
             $MouseDown. $MouseOver .
             $MouseOut.$titleStr .sprintf ($hourfmt, $hour).':'.($increment * $j<=9?'0':'').
             ($increment * $j). '.">';
            $str .= $k."</td>\n";
            break;
          default:
            $str .= 'style="width:'.$cell_pct.'%;" '.
              $MouseDown . $MouseOver . $MouseOut.$titleStr .
              sprintf ($hourfmt, $hour).':'.($increment * $j<=9?'0':'').
              ($increment * $j). '.">';
            $str .= "&nbsp;&nbsp;</td>\n";
            break;
        }
       $CC++;
     }
  }
  $ret .= $str .
    "</tr>\n<tr><td class=\"matrix\" colspan=\"$cols\"></td></tr>\n";

  // Add user _all_ to beginning of $participants array
  array_unshift($participants, '_all_');
  // Javascript for cells
  //$MouseOut = 'onmouseout="this.style.backgroundColor=\'' . $CELLBG. '\';"';
$MouseOut = '';
  $viewMsg = translate ( 'View this entry' );
  // Display each participant
  for ( $i = 0; $i <= $cnt; $i++ ) {
    if ($participants[$i] != '_all_') {
      // Load full name of user
      user_load_variables ( $participants[$i], 'user_' );
  
      // exchange space for &nbsp; to keep from breaking
      $user_nospace = preg_replace ( '/\s/', '&nbsp;', $user_fullname );
    } else {
      $user_nospace = translate('All Attendees');
      $user_nospace = preg_replace ( '/\s/', '&nbsp;', $user_nospace );
    }

    $ret .= "<tr>\n<th class=\"row\" style=\"width:{$participant_pct};\">". 
      $user_nospace."</th>\n";
    $col = 1;

    // check each timebar
    for ( $j = $first_hour; $j < $last_hour; $j++ ) {
       for ( $k = 0; $k < $interval; $k++ ) {
         $border = ($k == '0') ? ' border-left: 1px solid #000000;' : '';
         $MouseDown = 'onmousedown="schedule_event('.$j.','.sprintf ("%02d",($increment * $k)).');"';
        $RC = $CELLBG;
         //$space = '';
         $space = '&nbsp;';

         $r = sprintf ("%02d",$j) . '.' . sprintf ("%02d", ($increment * $k)).'';
         if ( empty ( $master[$participants[$i]][$r] ) ) {
           // ignore this..
         } else if ( empty ( $master[$participants[$i]][$r]['ID'] ) ) {
           // This is the first line for 'all' users.  No event here.
           $space = "<span class=\"matrix\"><img src=\"images/pix.gif\" alt=\"\" style=\"height: 8px\" /></span>";
         } else if ($master[$participants[$i]][$r]['stat'] == "A") {
           $space = "<a class=\"matrix\" href=\"view_entry.php?id={$master[$participants[$i]][$r]['ID']}\"><img src=\"images/pix.gif\" title=\"$viewMsg\" alt=\"$viewMsg\" /></a>";
         } else if ($master[$participants[$i]][$r]['stat'] == "W") {
           $space = "<a class=\"matrix\" href=\"view_entry.php?id={$master[$participants[$i]][$r]['ID']}\"><img src=\"images/pixb.gif\" title=\"$viewMsg\" alt=\"$viewMsg\" /></a>";
         }

         $ret .= "<td class=\"matrixappts\" style=\"width:{$cell_pct}%;$border\" ";
         if ($space == '&nbsp;') $ret .= "$MouseDown $MouseOver $MouseOut";
         $ret .= ">$space</td>\n";
         $col++;
      }
    }
    
    $ret .= "</tr><tr>\n<td class=\"matrix\" colspan=\"$cols\">" .
      "<img src=\"images/pix.gif\" alt=\"-\" /></td></tr>\n";
  } // End foreach participant
  $busy = translate ('Busy');
  $tentative = translate ('Tentative');  
  $ret .= <<<EOT
    </table><br />
    <table align="center"><tr><td class="matrixlegend" >
      <img src="images/pix.gif" title="{$busy}" alt="{$busy}" />{$busy}&nbsp;&nbsp;&nbsp;
      <img src="images/pixb.gif" title="{$tentative}" alt="{$tentative}" />{$tentative}
     </td></tr></table>
EOT;

  return $ret;
} 

/**
 * Return the time in HHMMSS format of input time + duration
 *
 *
 * <b>Note:</b> The gd library module needs to be available to use gradient
 * images.  If it is not available, a single background color will be used
 * instead.
 *
 * @param string $time   format "235900"
 * @param int $duration  number of minutes
 *
 * @return string The time in HHMMSS format
 */
function add_duration ( $time, $duration ) {
  $time = sprintf ( "%06d", $time );
  $hour = (int) ( $time / 10000 );
  $min = ( $time / 100 ) % 100;
  $minutes = $hour * 60 + $min + $duration;
  $h = $minutes / 60;
  $m = $minutes % 60;
  $ret = sprintf ( "%d%02d00", $h, $m );

  return $ret;
}

/**
 * Extract the names of all site_extras
 *
 * @return array Array of site_extras names
 */
function get_site_extras_names () {
  global $site_extras;

  $ret = array();

  foreach ( $site_extras as $extra ) {
    $ret[] = $extra[0];
  }

  return $ret;
}

/*
 * Prints Timezone select for use on forms
 *
 * @param string  $prefix   Prefix for select control's name
 * @param string  $tz  Current timezone of logged in user
 *
 * @return string  $ret
 *    html for select control
*/
function print_timezone_select_html ( $prefix, $tz ) {
  $ret = '';
   
    //Import Timezone name. This file will not normally be available
    //on windows platforms, so we'll just include it with WebCalendar
    $tz_file = 'includes/zone.tab';
   if (!$fd=@fopen( $tz_file,'r', false )) {
     $error = "Can't read timezone file: $tz_file\n";
      return $error;
   } else {  
     while (($data = fgets($fd, 1000)) !== FALSE) {
       if ( ( substr (trim($data),0,1) == '#' ) || strlen( $data ) <=2 ) {
        continue;
       } else {
         $data = trim ( $data, strrchr( $data, '#' ) ) ;
          $data = preg_split("/[\s,]+/", trim ($data ) ) ;
          $timezones[] = $data[2];
        }   
      }
      fclose($fd);
   }
   sort ( $timezones);
   //allows different SETTING names between SERVER and USER
   if ( $prefix == 'admin_' ) $prefix .= 'SERVER_';
   $ret =  '<select name="' . $prefix . 'TIMEZONE" id="' . 
     $prefix . 'TIMEZONE">' . "\n";
   for ( $i=0, $cnt = count ($timezones); $i < $cnt; $i++ ) {
     $ret .= "<option value=\"$timezones[$i]\"" . 
        ( $timezones[$i] == $tz ? ' selected="selected" ' : '' ) . 
         '>' . unhtmlentities ( $timezones[$i] ) . "</option>\n";
   }
   $ret .= "</select>\n";
   return $ret;
}

/*
* Checks to see if user's IP in in the IP Domain
* specified by the /icludes/blacklist.php file
*
* @return bool <b>Is user's IP in required domain?</b>
*/
function validate_domain ( ) {

  $ip_authorized = false;
  $deny_found = array();
  $deny_true = false;
  $allow_found = array();
  $allow_true = false;
  $rmt_ip = explode( '.',  $_SERVER['REMOTE_ADDR'] );
  $fd = @fopen ( 'includes/blacklist.php', 'rb', false );
  if ( ! empty ( $fd ) ) {
    // We don't use fgets() since it seems to have problems with Mac-formatted
    // text files.  Instead, we read in the entire file, then split the lines
    // manually.
    $data = '';
    while ( ! feof ( $fd ) ) {
      $data .= fgets ( $fd, 4096 );
    }
    fclose ( $fd );

    // Replace any combination of carriage return (\r) and new line (\n)
    // with a single new line.
    $data = preg_replace ( "/[\r\n]+/", "\n", $data );

    // Split the data into lines.
    $blacklistLines = explode ( "\n", $data );
    for ( $n = 0, $cnt = count ( $blacklistLines ); $n < $cnt; $n++ ) {
      $buffer = $blacklistLines[$n];
      $buffer = trim ( $buffer, "\r\n " );
      if ( preg_match ( "/^#/", $buffer ) )
        continue; 
      if ( preg_match ( "/(\S+):\s*(\S+):\s*(\S+)/", $buffer, $matches ) ) {
        $permission = $matches[1];
        $blacklist_ip = explode( '.',  $matches[2] );
        $blacklist_nm = explode( '.',  $matches[3] );
        if ( $permission == 'deny' ) {
          for ( $i = 0; $i < 4; $i++ ) {
            // Do bitwise AND on IP and Netmask
            if ( (abs($rmt_ip[$i]) & abs($blacklist_nm[$i])) == 
              (abs($blacklist_ip[$i]) & abs($blacklist_nm[$i])) ) {
              $deny_found[$i] = 1;          
            } else {
              $deny_found[$i] = 0;      
            }    
          }
          //This value will be true if rmt_ip is any deny network
          // Once set, it can not be reset be other deny statements 
          if ( ! array_search ( 0, $deny_found ) ) {
            $deny_true = true;   
          } 
        } else if ( $permission == 'allow') {
          for ( $i = 0; $i < 4; $i++ ) {
            // Do bitwise AND on IP and Netmask
            if ( (abs($rmt_ip[$i]) & abs($blacklist_nm[$i])) == 
              (abs($blacklist_ip[$i]) & abs($blacklist_nm[$i])) ) {
              $allow_found[$i] = 1;           
            } else {
              $allow_found[$i] = 0;     
            }    
          }
          //This value will be true if rmt_ip is any allow network
          // Once set, it can not be reset be other allow statements 
          if ( ! array_search ( 0, $allow_found ) ) {
            $allow_true = true;    
          }
        }
      }
    } //end for loop
    $ip_authorized = ( $deny_true == true && $allow_true == false? false : true ); 
  } // if fd not empty
  return $ip_authorized;
}


/**
 * Returns a custom header, stylesheet or tailer.
 * The data will be loaded from the webcal_user_template table.
 * If the global variable $ALLOW_EXTERNAL_HEADER is set to 'Y', then
 * we load an external file using include.
 * This can have serious security issues since a
 * malicous user could open up /etc/passwd.
 *
 * @param string  $login Current user login
 * @param string  $type  type of template ('H' = header,
 *    'S' = stylesheet, 'T' = trailer)
 */
function load_template ( $login, $type )
{
  global $ALLOW_USER_HEADER, $ALLOW_EXTERNAL_HEADER;
  $found = false;
  $ret = '';

  // First, check for a user-specific template
  if ( ! empty ( $ALLOW_USER_HEADER ) && $ALLOW_USER_HEADER == 'Y' ) {
    $rows = dbi_get_cached_rows (
      'SELECT cal_template_text FROM webcal_user_template ' .
      'WHERE cal_type = ? and cal_login = ?', array( $type, $login ) );
    if ( $rows && ! empty ( $rows[0] ) ) {
      $row = $rows[0];
      $ret .= $row[0];
      $found = true;
    }
  }

  // If no user-specific template, check for the system template
  if ( ! $found ) {
    $rows = dbi_get_cached_rows (
      'SELECT cal_template_text FROM webcal_user_template ' .
      "WHERE cal_type = ? and cal_login = '__system__'", array( $type ) );
    if ( $rows && ! empty ( $rows[0] ) ) {
      $row = $rows[0];
      $ret .= $row[0];
      $found = true;
    }
  }

  // If still not found, the check the old location (WebCalendar 1.0 and
  // before)
  if ( ! $found ) {
    $rows = dbi_get_cached_rows (
      'SELECT cal_template_text FROM webcal_report_template ' .
      'WHERE cal_template_type = ? and cal_report_id = 0', array( $type ) );
    if ( $rows && ! empty ( $rows[0] ) ) {
      $row = $rows[0];
      if ( ! empty ( $row ) ) {
        $ret .= $row[0];
        $found = true;
      }
    }
  }

  if ( $found ) {
    if ( ! empty ( $ALLOW_EXTERNAL_HEADER ) &&
      $ALLOW_EXTERNAL_HEADER == 'Y' ) {
      if ( file_exists ( $ret ) ) {
        ob_start ();
        include "$ret";
        $ret .= ob_get_contents ();
        ob_end_clean ();
      }
    }
  }
  
  return $ret;
}


function error_check ( $nextURL ) {
  $ret = '';
  if ( ! empty ($error) ) {
    print_header( '', '', '', true );
    $ret .= '<h2>' . translate('Error') . '</h2>';
    $ret .= '<blockquote>' . $error . "</blockquote>\n</body></html>";
  } else if ( empty ($error) ) {
    $ret .= "<html><head></head><body onload=\"alert('" . 
      translate('Changes successfully saved', true) . 
      "');  window.parent.location.href='$nextURL';\"></body></html>";
  }
  return $ret;
}

/**
 * Sorts the combined event arrays by timestamp then name
 *
 * <b>Note:</b> This is a user-defined comparison function for usort()
 *
 * @params passed automatically by usort, don't pass them in your call
 */
function sort_events ( $a, $b ) { 
  $retval = strnatcmp( $a->getDateTimeTS(), $b->getDateTimeTS() ); 
  if( ! $retval ) return strnatcmp( $a->getName(), $b->getName() );
  return $retval; 
} 

/**
 * Sorts the combined event arrays by timestamp then name (case insensitive)
 *
 * <b>Note:</b> This is a user-defined comparison function for usort()
 *
 * @params passed automatically by usort, don't pass them in your call
 */
function sort_events_insensitive ( $a, $b ) { 
  $retval = strnatcmp( $a->getDateTimeTS(), $b->getDateTimeTS() ); 
  if( ! $retval ) return strnatcmp( strtolower($a->getName()), strtolower($b->getName()) ); 
  return $retval; 
} 

/**
 * Combines the repeating and nonrepeating event arrays and sorts them
 *
 * The returned events will be sorted by time of day.
 *
 * @param array $ev          Array of events
 * @param array $rep         Array of repeating events
 *
 * @return array Array of Events
 */
function combine_and_sort_events ( $ev, $rep ) { 

   $ids = array();

  // repeating events show up in $ev and $rep
  // record their ids and don't add them to the combined array
  foreach ( $rep as $obj ) {
    $ids[] = $obj->getID();
  }
  foreach ( $ev as $obj ) {
    if ( ! in_array( $obj->getID(), $ids ) ) $rep[] = $obj;
  }
  usort( $rep, 'sort_events' );
  return $rep;
} 

//calculate rollover to next day and add partial event as needed
function get_OverLap ( $item, $i, $parent=true ) {
  global $result, $DISABLE_CROSSDAY_EVENTS;
  static $realEndTS, $originalDate, $originalItem;

  if ( $DISABLE_CROSSDAY_EVENTS == 'Y' ) {
    return false;
  }
    
  $recurse = 0;
  $lt = localtime ( $item->getDateTimeTS() );
  $tz_offset = date ( 'Z', $item->getDateTimeTS() ) / 3600;
  $midnight = gmmktime ( -$tz_offset, 0, 0, $lt[4] +1, $lt[3] +1, $lt[5] );
  if ( $parent ) {
    $realEndTS = $item->getEndDateTimeTS();
    $originalDate = $item->getDate();
    $originalItem = $item;
  }
  $new_duration = ( $realEndTS - $midnight) /60;
  if ( $new_duration > 1440 ) {
    $recurse = 1;
    $new_duration = 1439;
  }
  if ( $realEndTS  >  $midnight ) {          
    $result[$i] = clone ( $originalItem );
    $result[$i]->setClone( $originalDate );
    $result[$i]->setDuration( $new_duration );
    $result[$i]->setTime( gmdate ( 'G0000', $midnight ) );
    $result[$i]->setDate( gmdate ('Ymd', $midnight ) );
    $result[$i]->setName( $originalItem->getName() . ' (' . translate ( 'cont.' ) . ')'); 
    
    $i++;  
    if ( $parent )$item->setDuration( ( ( $midnight - $item->getDateTimeTS() ) /60 ) -1 );    
  }
  //call this function recursively until duration < ONE_DAY
  if ( $recurse == 1 ) get_OverLap ( $result[$i -1], $i, false );
}

if (version_compare(phpversion(), '5.0') < 0) {
    eval('
    function clone($item) {
      return $item;
    }
    ');
}

/**
 * Get the moonphases for a given year and month.
 *
 * Will only work if optional moon_phases.php file exists in includes folder.
 *
 * @param int $year Year in YYYY format
 * @param int $month Month in m format Jan =1
 *
 * #returns array  $key = phase name, $val = Ymd value
 */
function getMoonPhases ( $year, $month ) {
  global $DISPLAY_MOON_PHASES;
  static $moons;
  
  if ( empty ( $DISPLAY_MOON_PHASES ) || $DISPLAY_MOON_PHASES == 'N' ) {
    return false;
  }
  if ( empty ( $moons ) && file_exists ( 'includes/moon_phases.php' ) ){
    include_once ( 'includes/moon_phases.php' );
    $moons = calculateMoonPhases( $year, $month );
  }
  return $moons;
}

/**
 * Get the reminder data for a given entry id
 *
 * @param int $id         cal_id of requested entry
 * @param bool $display   if true, will create a displayable string
 *
 * #returns string  $str  string to display Reminder value
 * #returns array   $reminder 
 */
function getReminders ( $id, $display=false ) {
  $reminder = array();
  $str = '';
  //get reminders 
  $sql = 'SELECT  cal_id, cal_date, cal_offset, cal_related, cal_before, ' .
    ' cal_repeats, cal_duration, cal_action, cal_last_sent, cal_times_sent ' .
    ' FROM webcal_reminders ' .
    ' WHERE cal_id = ?  ORDER BY cal_date, cal_offset, cal_last_sent';
  $rows = dbi_get_cached_rows ( $sql, array( $id ) );
  if ( $rows ) {
    $rowcnt = count ( $rows );
    for ( $i = 0; $i < $rowcnt; $i++ ) {
      $row = $rows[$i];
      $reminder['id'] = $row[0];      
      if ( $row[1] != 0 ) {
        $reminder['timestamp'] = $row[1];
        $reminder['date'] = date ('Ymd', $row[1] );
        $reminder['time'] = date( 'His', $row[1] );
      }
      $reminder['offset'] = $row[2];
      $reminder['related'] = $row[3];
      $reminder['before'] = $row[4];
      $reminder['repeats'] = $row[5];
      $reminder['duration'] = $row[6];
      $reminder['action'] = $row[7];
      $reminder['last_sent'] = $row[8];
      $reminder['times_sent'] = $row[9];
    }  
    //create display string if needed in user's timezone
    if ( ! empty ( $reminder ) && $display == true ) {
       $str .= translate ( 'Yes' );
       $str .= '&nbsp;&nbsp;-&nbsp;&nbsp;';
        if ( ! empty ( $reminder['date'] ) ) {
          $str .= date ('Ymd', $reminder['timestamp'] );
        } else  { //must be an offset even if zero
          $d = $h = $minutes = 0;
          if ( $reminder['offset'] > 0 ) {
            $minutes = $reminder['offset'];
            $d = (int) ( $minutes / ONE_DAY );
            $minutes -= ( $d * ONE_DAY );
            $h = (int) ( $minutes / 60 );
            $minutes -= ( $h * 60 );
          }
          if ( $d > 1 ) {
            $str .= $d . ' ' . translate('days') . ' ';
          } else if ( $d == 1 ) {
           $str .= $d . ' ' . translate('day') . ' ';
          }
          if ( $h > 1 ) {
            $str .= $h . ' ' . translate('hours') . ' ';
          } else if ( $h == 1 ) {
           $str .= $h . ' ' . translate('hour') . ' ';
          }
          if ( $minutes != 1 ) {
            $str .= $minutes . ' ' . translate('minutes');
          } else {
            $str .= $minutes . ' ' . translate('minute');
          }
          // let translations get picked up
          // translate ( 'before' ) translate ( 'after' )
          // translate ( 'start' ) translate ( 'end' ) 
          $str .= ' ' . translate( $reminder['before'] == 'Y' ? 'before' : 'after' ) . 
            ' ' .  translate( $reminder['related'] == 'S' ? 'start' : 'end' ) ;  
        }
      return $str;
    }
  }
  return $reminder; 
}

/**
 * Set an environment variable if system allows it
 *
 * @param string   $val   name of environment variable
 * @param string   $setting  value to assign
 *
 * #returns bool true= success false = not allowed
 */
function set_env ( $val, $setting ) {
  $ret = false;
  //test if safe_mode is enabled. If so, we then  check
  //safe_mode_allowed_env_vars for $val
  if( ini_get('safe_mode') ){
    $allowed_vars = explode ( ',' , ini_get('safe_mode_allowed_env_vars') );
    if ( array_search ( $val, $allowed_vars ) >=0 ) {
      $ret = true;
    }
  } else {
     $ret = true;  
  }
  
  if ( $ret == true ) 
    putenv ( $val . '=' . $setting );
    
  //some say this is required to properly init timezone changes
  if ( $val == 'TZ' ) mktime ( 0,0,0,1,1,1970);
    
  return $ret;
}

/**
 * Updates event status and logs activity
 *
 * @param string   $status   A,W,R,D to set cal_status
 * @param string   $user     user to apply changes to
 * @param int      $id       event id
 * @param string   $type     event type for logging
 *
 */
function update_status ( $status, $user, $id, $type='E' ) {
  global $login, $error;
  if ( empty ( $status ) )
    return;
  $log_type = '';  
  switch ( $type ) {
    case 'T':
    case 'N';
      $log_type = '_T';
      break;
    case 'J':
    case 'O';
      $log_type = '_J';
      break;
    default;
      break;  
  }
  switch ( $status ) {
    case 'A':
        $log_type = constant ( 'LOG_APPROVE' . $log_type );
        $error_msg = translate('Error approving event');
      break;
    case 'D';
      $log_type = constant ( 'LOG_DELETE' . $log_type );
      $error_msg = translate('Error deleting event');
      break;
    case 'R';
      $log_type = constant ( 'LOG_REJECT' . $log_type );
      $error_msg = translate('Error rejecting event');
      break;  
  }  
  
  if ( ! dbi_execute ( 'UPDATE webcal_entry_user SET cal_status = ? ' .
    'WHERE cal_login = ? AND cal_id = ?', array( $status, $user, $id ) ) ) {
    $error = $error_msg . ': ' . dbi_error ();
  } else {
    activity_log ( $id, $login, $user, $log_type, '' );
  }


}

/**
 * Generate html to add Printer Friendly Link
 *  if called without parameter, return only the href string
 *
 * @param string   $hrefin  script name
 *
 *
 */
function generate_printer_friendly ( $hrefin='' ) {
  global $_SERVER, $SCRIPT, $MENU_ENABLED;
  $href = ( ! empty ( $href ) ? $hrefin : $SCRIPT );
  $href .= '?' . $_SERVER['QUERY_STRING'];
  $href .= ( substr ( $href, -1) == '?' ? '' : '&') . 'friendly=1';
  if ( empty ( $hrefin ) ) //menu will call this function without parameter
    return $href;
  if ( $MENU_ENABLED == 'Y' ) //return nothing if using menus
    return '';
  $href = str_replace ( '&', '&amp;', $href );
  $statusStr = translate ( 'Generate printer-friendly version' );
  $displayStr = translate ( 'Printer Friendly' );
  $ret = <<<EOT
  <a title="{$statusStr}" class="printer" href="{$href}" target="cal_printer_friendly">[{$displayStr}]</a>
EOT;
return $ret;
}
/**
 * Remove :00 from times based on $DISPLAY_MINUTES
 *  value
 *
 * @param string   $timestr  time value to shorten
 *
 *
 */
function getShortTime ( $timestr ) {
  global $DISPLAY_MINUTES;
  
  if ( empty ( $DISPLAY_MINUTES ) || $DISPLAY_MINUTES == 'N' ) {
    return preg_replace ('/(:00)/', '', $timestr);
  }
  else {
    return $timestr;
  }
}
?>
