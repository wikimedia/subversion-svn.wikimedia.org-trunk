<?
# ATTENTION:
# To fit your local settings, PLEASE edit wikiLocalSettings.php ONLY!
# Change settings here ONLY if they're to become global in all wikipedias!

# Essential stuff, so the script knows where it is, whar database to use, etc.
#$wikiCurrentServer = "http://127.0.0.1" ;
$wikiCurrentServer = "http://" . getenv("SERVER_NAME");
$wikiSQLServer = "wiki" ; # The name of the database, actually...
$wikiArticleSource = "$wikiCurrentServer/wiki/$1" ;
$wikiLogoFile = "/wiki.png" ;
$wikiStarTrekImage = "/startrek.png" ;
$THESCRIPT = "/wiki.phtml" ; # The name and location of the script. The $PHP_SELF variable doesn't work with Netscape

# For the MySQL database
$wikiThisDBserver = "127.0.0.1" ;
$wikiThisDBuser = "root" ;
$wikiThisDBpassword = "" ;
$minSrchSize = 4;   # this is smallest word size that is indexed by the MySQL fulltext index
# (can be changed by recompiling MySQL and rebuilding the indexes.)
$wikiDBconnection = "";  # global variable to hold the current DB
			 # connection; should be empty initially.

# Namespace backgrounds
$wikiNamespaceBackground = array () ;
$wikiNamespaceBackground[$wikiTalk] = "#eeFFFF" ;
$wikiNamespaceBackground["user_talk"] = $wikiNamespaceBackground["talk"] ;
$wikiNamespaceBackground["wikipedia_talk"] = $wikiNamespaceBackground["talk"] ;
$wikiNamespaceBackground[$wikiUser] = "#FFeeee" ;
$wikiNamespaceBackground[$wikiWikipedia] = "#eeFFee" ;
$wikiNamespaceBackground["log"] = "#FFFFcc" ;
$wikiNamespaceBackground["special"] = "#eeeeee" ;
 
# Cache system enabled by default
$useCachedPages = true ;

# Use English by default
$wikiLanguage = "en";

# Now, load local site-specific settings
include_once ( "wikiLocalSettings.php" ) ;

# Initialize list of available character encodings to the default if none was set up.
if ( ! isset ( $wikiEncodingCharsets ) ) $wikiEncodingCharsets = array($wikiCharset);
if ( ! isset ( $wikiEncodingNames ) ) $wikiEncodingNames = array($wikiCharset); # Localised names

#
# This file loads up the default English message strings
# and the default server configuration for the English wikipedia.
# This has to be done after the local settings have been read in,
# since variables such as $THESCRIPT are being used.
include_once ( "wikiTextEn.php" ) ;
if ( $wikiLanguage != "en" ) {
    include_once ( "wikiText" . ucfirst ( $wikiLanguage ) . ".php" ) ;
}

# Functions

# Is there any reason to localise this function? Ever?
# Not for a language, but other servers, if others want to use this software!
function wikiLink ( $a ) {
    global $wikiArticleSource ;
    $a = str_replace ( " " , "+" , $a ) ;
    $a = str_replace ( "$1" , $a , $wikiArticleSource ) ;
    return $a ;
    }

?>
