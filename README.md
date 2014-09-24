[Copied from original published at http://mbharris.co.uk/content/very-simple-php-link-shortener-simplifier](http://mbharris.co.uk/content/very-simple-php-link-shortener-simplifier)

There's lots of web sites that provide link shorteners (bit.ly, tinyurl.com, imc.li, tiny.booki.cc; to name but a few).  In the past I'd always used tinyurl.com, but then I started working on a project to provide a book of useful technical tips for political activists and grassroots campaigners and some people on the project were concerned about the security implications of third parties logging the redirects, having to have a named account in order to edit the links and the potential of the links being taken down due to policy or petition.  The commercial offerings were therefore not fit for purpose for this project.

And so we cunningly used a free, non-commercial link shortener for our links.  All seemed good, but then what happened was that the destination links changed (without notice I might add).  We were unable to change the shortened links as there was no management side, nor did we manage to get in touch with the authors to get them to do it for us.  Another link shortener being offered to us to use didn't offer any editing of the links either, so I thought to myself how hard could it be to write one?  Not very hard surely...?

## The spec

I decided that the important thing was the redirector: it needed to be simple so that I could get it up and running quickly.  I also didn't want those auto-generated URLs either; I wanted something with URLs (web addresses) that made sense, that I could speak out loud to someone and they'd be able to remember it in their head or write it down.  I guess that although we call them link shorteners, shortening is but one goal, another being simplifying the URL.  Therefore this was to be a link simplifier (in the sense of making the URL simple to communicate verbally and rememeber) as well as a shortener.

I realised that providing for the administration side of it was going to be more complex and that I could get really bogged down with the specification for my amazing and fully featured Short Links Manager (should I even write it in Drupal perhaps and really create a behemoth to crack a nut? :P) 

I therefore came up with this feature list:

*     Use query strings, for URLs likle /?shortlink
*     Accept URLs with or without a proceding question mark, so /shortlink is also allowed.
*     If there is no entry for the short link then return a 404 Not Found error.
*     If robots.txt is requested, provide it.
*     In any other case redirect to another web site.
*     No flashy admininistrator, as I haven't got time to write one!
*     No need for a home page: noone has to know about it (at least for now).
*     Keep track of the count of hits for each shortened/simplified URL.

## Database

My MySQL database table looks like this:

    CREATE TABLE `short_links` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `short_link` varchar(15) NOT NULL,
      `redirect_to` varchar(255) NOT NULL,
      `count` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 ;

The column names for the table should, I hope, be self explanatory.  I decided that it would be useful to know how many times a given short link was requested, so that's what the count column is for.
Back-end administrator

So, having decided to use a mySQL database to store the links, I decided that for the back-end I'd just use phpMyAdmin to do the job (or one could just use the command line mySQL console, or another tool).  Job done! ;)

## .htaccess file

Here's my .htaccess file, it does two things:

* Allows the file robots.txt to be requested; search engines are happy and know not to bother indexing this site.
* Rewrites any URL in the form http://mysite.com/short to http://mysite.com/?short

    <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteBase /
      RewriteCond %{REQUEST_FILENAME} !robots.txt
      RewriteCond %{QUERY_STRING} ^$
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteCond %{REQUEST_FILENAME} !-l
      RewriteRule ^(.*)$ http://mysite.com/?$1 [L]
   </IfModule>

## Main PHP link shortener script

And here's the PHP script that does the redirect:

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

There's some overkill in that script I think.  The first and third mySQL query checks are not really needed, I just wanted to fail gracefully and log it if my query didn't work.  Also you don't really need to log to the error log, that may simply hog or clog up your log files with spam requests.  Lastly, I decided not to have a home page, so my default fall back action is to redirect to another site: You may like to simply output some HTML instead.

## Robots.txt

Finally, let's create a robots.txt file that will tell any well-behaved search engine spiders not to index our site:

    User-agent: *
    Disallow: / 

That's it

## In Summary

And that's all there is to it.  I works fine and I'm happy with phpMyAdmin to administrate it; no need to write some complex manager for it.  Obviously it's only for my use, so I don't need a home page, don't need to offer the service to anyone else, but do have the ability to change my redirects and count how many people visit them.

Here's a summary list of the technology employed:

*     One single PHP script.
*     One single .htaccess file in Apache.
*     One single mySQL database table.
*     One single robots.txt file (optional).
*     phpMyAdmin or mySQL console for adminsitration.
