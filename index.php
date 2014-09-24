<?php

$DEFAULT_REDIRECT = "https://someothersite.org";

$db_conn = mysql_pconnect ("localhost", "user", "password") or die ("Could not connect to DB");
mysql_select_db("links_db");

if (isset($_SERVER["QUERY_STRING"]) && ($_SERVER["QUERY_STRING"] != '')) {

  $query = sprintf("SELECT id, redirect_to FROM short_links WHERE short_link = '%s' 
                    ORDER BY id DESC LIMIT 1", mysql_real_escape_string($_SERVER["QUERY_STRING"]));
  $result = mysql_query($query);

  $fail = 0;

  if (!$result)  {
    // query failed
    error_log("Sent an error 404 having executed query $query and got an error of " . mysql_error());
    $fail = 1;
  }

  if (mysql_num_rows($result) < 1) {
    // short link not found in db
    error_log("Sent an error 404 having executed query $query and got no rows back");
    $fail = 1;
  }

  $link = mysql_fetch_assoc($result);
    
  $query = "UPDATE short_links SET count = count + 1 WHERE id = " . $link["id"];
  $result = mysql_query($query);

  if (!$result)  {
    // query failed updating db (this really shouldn't occur ever
    error_log("Sent an error 404 having executed query $query and got an error of " . mysql_error());
    $fail = 1;
  }

  if ($fail == 1) {
    header("HTTP/1.1 404 Not Found");
    print "<html><body><p>The requested short link was not found.</p></body></html>";
  } else {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $link["redirect_to"]);
  }
    
} else {
  // redirect to some default site
  // I don't have a home page, but here you could just output some HTML
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: " . $DEFAULT_REDIRECT);
}

?>
