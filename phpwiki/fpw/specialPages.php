<?
function error ( $error ) {
	$page = new WikiPage ;
	$page->special ( "Yikes! An error!" ) ;
	$page->contents = "<h2>$error!</h2>Return to the [[:HomePage|HomePage]]!" ;
	return $page->renderPage () ;
	}

function edit ( $title ) {
	global $EditBox , $SaveButton , $PreviewButton , $MinorEdit ;
	global $user , $CommentBox , $vpage ;
	$npage = new WikiPage ;
	$npage->title = $title ;
	$npage->makeAll () ;
	$ret = "" ;
	if ( !$vpage->canEdit() ) return "<h3>You cannot edit this page!</h3>" ;

	if ( isset ( $SaveButton ) ) {
		unset ( $SaveButton ) ;
		$text = $EditBox ;
		$text = str_replace ( "\\'" , "'" , $text ) ;
		$text = str_replace ( "\\\"" , "\"" , $text ) ;
		if ( $user->isLoggedIn ) $text = str_replace ( "~~~" , "[[user:$user->name|$user->name]]" , $text ) ;
		else $text = str_replace ( "~~~" , $user->getLink() , $text ) ;
		$title = str_replace ( "\\'" , "'" , $title ) ;
		$title = str_replace ( "\\\"" , "\"" , $title ) ;
		$npage->title = $title ;
		$npage->makeAll () ;
		if ( $npage->doesTopicExist() ) $npage->backup() ;
		else { $MinorEdit = 2 ; $npage->ensureExistence () ; }
		if ( !$user->isLoggedIn ) $npage->setEntry ( $text , $CommentBox , 0 , $user->getLink() , $MinorEdit*1 ) ;
		else $npage->setEntry ( $text , $CommentBox , $user->id , $user->name , $MinorEdit*1 ) ;
		global $wasSaved ;
		$wasSaved = true ;
		return "" ;
	} else if ( isset ( $PreviewButton ) ) {
		unset ( $PreviewButton ) ;
		$text = $EditBox ;
		$text = str_replace ( "\\'" , "'" , $text ) ;
		$text = str_replace ( "\\\"" , "\"" , $text ) ;
		$append = "<hr>\n<h2>Preview :</h2>\n".$npage->parseContents($text)."<hr><h3>Remember, this is only a preview and not yet saved!</h3>" ;
	} else if ( $npage->doesTopicExist() ) {
		$npage->load ( $npage->title ) ;
		$text = $npage->contents ;
	} else {
		$text = "Describe the new page here." ;
		}

	if ( $MinorEdit ) $checked = "checked" ;
	else $checked = "" ;
	if ( $CommentBox == "" ) $CommentBox = "*" ;


	# Just trying to set the initial keyboard focus to the edit window; doesn't work, though...
	global $bodyOptions , $headerScript ;
	$headerScript = "<script> <!-- function setfocus() { document.f.EditBox.focus(); } --> </script>" ;
	$bodyOptions = " onLoad=setfocus()" ;

	$ret .= "<form method=POST name=f>" ;
	$ret .= "<textarea tabindex=1 name=EditBox rows=".$user->options["rows"]." cols=".$user->options["cols"]." STYLE=\"width:100%\" WRAP=virtual>$text</textarea><br>\n" ;
	$ret .= "Summary:<input tabindex=2 type=text value=\"$CommentBox\" name=CommentBox size=50 maxlength=200> \n" ;
	$ret .= "<input tabindex=3 type=checkbox name=MinorEdit $checked value=1>This is a minor edit \n" ;
	$ret .= "<input tabindex=4 type=submit value=Save name=SaveButton> \n" ;
	$ret .= "<input tabindex=5 type=submit value=Preview name=PreviewButton>\n" ;
	$ret .= "</form>" ;

	return $ret.$append ;
	}

function doEdit ( $title ) {
	global $vpage , $action , $wasSaved ;
	$wasSaved = false ;
	$vpage = new WikiPage ;
	$vpage->isSpecialPage = true ;
	$vpage->title = $title ;
	$vpage->makeSecureTitle () ;
	$action = "" ;
	$ret = $vpage->getHeader() ;
	$action = "edit" ;
	$ret .= $vpage->getMiddle(edit($title)) ;
	$action = "" ;
	$ret .= $vpage->getFooter() ;
	$action = "edit" ;
	if ( $wasSaved ) return view ( $title ) ;
	return $ret ;
	}

function view ( $title ) {
	global $vpage ;
	$vpage = new WikiPage ;
	$vpage->load ( $title ) ;
	return $vpage->renderPage () ;
	}

function userLogout () {
	global $user , $vpage ;
	$vpage->title = "User logout" ;
	setcookie ( "WikiLoggedIn" , "" , time()-3600 ) ;
	if ( $user->options["rememberPassword"] != "on" ) setcookie ( "WikiUserPassword" , "" , time()-3600 ) ;
	$user->isLoggedIn = false ;
	return "<h1>Goodbye, $user->name!</h1>" ;
	}

function userLogin () {
	global $loginattempt , $user , $vpage , $WikiUserID , $expiration ;
	$vpage->title = "User login" ;

	if ( isset ( $loginattempt ) ) {
		unset ( $loginattempt ) ;
		global $USERNAME , $USERPASSWORD , $RETYPE , $REMEMBERPASSWORD ;
		if ( $REMEMBERPASSWORD == "" ) $REMEMBERPASSWORD = "off" ;
		$nu = new WikiUser ;
		$nu->name = $USERNAME ;
		$nu->password = $USERPASSWORD ;
		$nu->options["rememberPassword"] = $REMEMBERPASSWORD ;
		$nu->retypePassword = $RETYPE ;

		$s = "<h1>".$nu->verify()."</h1>" ;
		if ( $nu->isLoggedIn ) {
			$user = new WikiUser ;
			$user = $nu ;
			setcookie ( "WikiUserID" , $user->id , $expiration ) ;
			setcookie ( "WikiLoggedIn" , "yes" , $expiration ) ;
			setcookie ( "WikiUserPassword" , $user->password , $expiration ) ;
			$user->options["rememberPassword"] = $REMEMBERPASSWORD ;
			$user->saveSettings() ;
		} else if ( $USERPASSWORD == $RETYPE and !($nu->doesUserExist()) ) {
			$nu->addToDatabase () ;
			$user = $nu ;
			$s = "<h1>Welcome, $user->name!</h1><font color=red>Don't forget to personalize your wikipedia perferences!</font>" ;
			setcookie ( "WikiLoggedIn" , "yes" , $expiration ) ;
			setcookie ( "WikiUserID" , $user->id , $expiration ) ;
			if ( $user->options["rememberPassword"] == "on" ) setcookie ( "WikiUserPassword" , $user->password , $expiration ) ;
			$user->options["rememberPassword"] = $REMEMBERPASSWORD ;
			$user->saveSettings() ;
		} else {
			$s .= "<h2>Problem with login</h2>" ;
			$s .= "Try again!" ;
			}
	} else {
		$s = "" ;
		if ( $user->isLoggedIn ) $s .= "<font color=red><b>User $user->name, you are already logged in!</b></font><br>\n" ;
	  	$s .= "<h1>Please log in:</h1>\n" ;
		global $WikiUserID , $WikiUserPassword , $WikiLoggedIn ;
		if ( $WikiUserID != "" ) {
			$user->name = getMySQL ( "user" , "user_name" , "user_id=$WikiUserID" ) ;
			$u = new WikiUser ;
			$u->id = $WikiUserID ;
			$u->isLoggedIn = true ;
			$u->loadSettings() ;
			$user->options["rememberPassword"] = $u->options["rememberPassword"] ;
			if ( $user->options["rememberPassword"] == "on" )
				$user->password = $WikiUserPassword ;
			}
		if ( $user->options["rememberPassword"] == "on" ) $check = "checked" ;
		$s .= "If you are new to wikipedia and want to get a user account, enter a user name, type and re-type a password.<br>\n" ;
	  	$s .= "<FORM action=\"$PHP_SELF?title=special:userLogin\" method=post><font face=courier>\n" ;
	  	$s .= "Your user name&nbsp; : <INPUT TABINDEX=1 TYPE=text NAME=USERNAME VALUE=\"$user->name\" SIZE=20><br>\n" ;
	  	$s .= "Your password&nbsp;&nbsp; : <INPUT TABINDEX=2 TYPE=password NAME=USERPASSWORD VALUE=\"$user->password\" SIZE=20><br>\n" ;
	  	$s .= "Retype password : <INPUT TABINDEX=2 TYPE=password NAME=RETYPE VALUE=\"\" SIZE=20> (new users only)<br>\n" ;
  		$s .= "<INPUT TABINDEX=4 TYPE=checkbox NAME=REMEMBERPASSWORD $check>Remember my password (as a cookie).<br>\n" ;
	  	$s .= "<input TABINDEX=5 type=submit name=loginattempt value=\"Log in\">\n" ;
  		$s .= "</font></FORM>\n" ;
		}

	return $s ;
	}

function editUserSettings () {
	global $ButtonSave ;
	global $vpage , $user ;
	$vpage->title = "User Settings" ;
	if ( !$user->isLoggedIn ) return "You are not logged in! [[special:userLogin|Log in]] or go to the [[:HomePage|Home Page]]" ;
	$ret = "" ;

	if ( isset ( $ButtonSave ) ) {
		unset ( $ButtonSave ) ;
		global $QuickBar , $NewTopics , $UnderlineLinks , $AutoTalk , $ShowHover , $ROWS , $COLS , $doSkin ;
		global $OLDPASSWORD , $NEWPASSWORD , $RETYPEPASSWORD , $EMAIL , $RESULTSPERPAGE , $doJustify ;
		if ( $RESULTSPERPAGE < 2 ) $RESULTSPERPAGE = 20 ;
		$user->options["quickBar"] = $QuickBar ;
		$user->options["markupNewTopics"] = $NewTopics ;
		$user->options["underlineLinks"] = $UnderlineLinks ;
		$user->options["autoTalk"] = $AutoTalk ;
		$user->options["showHover"] = $ShowHover ;
		$user->options["cols"] = $COLS ;
		$user->options["rows"] = $ROWS ;
		$user->options["justify"] = $doJustify ;
		$user->options["resultsPerPage"] = $RESULTSPERPAGE ;
		$user->options["skin"] = $doSkin ;
		$user->email = $EMAIL ;

		if ( $OLDPASSWORD == $user->password ) {
			if ( $NEWPASSWORD == $RETYPEPASSWORD ) $user->password = $NEWPASSWORD ;
			else $ret .= "<h1><font color=red>The new passwords didn't match. PASSWORD UNCHANGED!</font></h1>\n" ;
			}

		$user->saveSettings () ;
		$user->loadSettings () ;
		$msg = "<font color=red size=+1>Your settings have been saved!</font>" ;
		}

	$ret .= "<b>You are logged in as [[user:$user->name|$user->name]]. ";
	$ret .= "Your internal ID is $user->id.</b><br>\n";
	$ret .= "You can get help [[wikipedia:Help/User preferences|here]].\n" ;
	$ret .= "<nowiki><FORM action=\"$PHP_SELF?title=special:editUserSettings\" method=post>" ;
	$ret .= "<table border=1 bordercolor=".$user->options["borderColor"]." cellspacing=0 cellpadding=2>" ;

	# QuickBar options
	$qb[$user->options["quickBar"]] = "checked" ;
	$ret .= "<tr><td valign=top nowrap><b>QuickBar Settings :</b><br>\n" ;
	$ret .= "<input type=radio value=none ".$qb["none"]." name=QuickBar>None (Standard)<br>\n" ;
	$ret .= "<input type=radio value=left ".$qb["left"]." name=QuickBar>Left<br>\n" ;
	$ret .= "<input type=radio value=right ".$qb["right"]." name=QuickBar>Right\n" ;

	# Password change
	$ret .= "</td><td valign=top nowrap><b>Change password :</b><br><font face=courier>\n" ;
  	$ret .= "Old password&nbsp; &nbsp; : <INPUT TYPE=password NAME=OLDPASSWORD VALUE=\"\" SIZE=20><br>\n" ;
  	$ret .= "New password&nbsp; &nbsp; : <INPUT TYPE=password NAME=NEWPASSWORD VALUE=\"\" SIZE=20><br>\n" ;
  	$ret .= "Retype password : <INPUT TYPE=password NAME=RETYPEPASSWORD VALUE=\"\" SIZE=20>\n" ;
	$ret .= "</font></td></tr>" ;

	# Underline Links options
	$ul[$user->options["underlineLinks"]] = "checked" ;
	$ret .= "<tr><td valign=top nowrap><b>Underline Links :</b><br>\n" ;
	$ret .= "<input type=radio value=yes ".$ul["yes"]." name=UnderlineLinks>Yes (Standard)<br>\n" ;
	$ret .= "<input type=radio value=no ".$ul["no"]." name=UnderlineLinks>No<br>\n" ;

	# New Topics options
	$nt[$user->options["markupNewTopics"]] = "checked" ;
	$ret .= "</td><td valign=top nowrap><b>New Topics :</b><br>\n" ;
	$ret .= "<input type=radio value=normal ".$nt["normal"]." name=NewTopics>Normal (Standard)<br>\n" ;
	$ret .= "<input type=radio value=red ".$nt["red"]." name=NewTopics>Red<br>\n" ;
	$ret .= "</td></tr>" ;

	# Automatic Talk page
	$at[$user->options["autoTalk"]] = "checked" ;
	$ret .= "<tr><td valign=top nowrap><b>Automatic /Talk page :</b><br>\n" ;
	$ret .= "<input type=radio value=yes ".$at["yes"]." name=AutoTalk>Yes<br>\n" ;
	$ret .= "<input type=radio value=no ".$at["no"]." name=AutoTalk>No (Standard)<br>\n" ;

	# Show Hover
	$sh[$user->options["showHover"]] = "checked" ;
	$ret .= "</td><td valign=top nowrap><b>Show hoverbox over wiki links :</b><br>\n" ;
	$ret .= "<input type=radio value=yes ".$sh["yes"]." name=ShowHover>Yes (Standard)<br>\n" ;
	$ret .= "<input type=radio value=no ".$sh["no"]." name=ShowHover>No<br>\n" ;
	$ret .= "</td></tr>" ;

	# Cols and rows
	$ret .= "<tr><td valign=top rowspan=3 nowrap><b>Textbox dimensions :</b><br><font face=courier>\n" ;
  	$ret .= "Cols : <INPUT TYPE=text NAME=COLS VALUE=\"".$user->options["cols"]."\" SIZE=5><br>\n" ;
  	$ret .= "Rows : <INPUT TYPE=text NAME=ROWS VALUE=\"".$user->options["rows"]."\" SIZE=5></font><br><br>\n" ;
	$ret .= "<font face=courier size=-1>Recommended sizes:<br>";
	$ret .= "1280x1024 : 135x40<br>\n" ;
	$ret .= "1024x 768 : <br>\n" ;
	$ret .= "&nbsp;800x 600 : </font>\n" ;

	$ret .= "</td><td valign=top nowrap><b>Your email adress :</b><br>" ;
  	$ret .= "<font face=courier>Email : <INPUT TYPE=text NAME=EMAIL VALUE=\"".$user->email."\" SIZE=35></font>\n" ;

	$ret .= "</td></tr><tr><td valign=top nowrap>" ;
	$ret .= "<b>Search Settings :</b><br>" ;
  	$ret .= "<font face=courier>Results per page : ";
	$ret .= "<INPUT TYPE=text NAME=RESULTSPERPAGE VALUE=\"".$user->options["resultsPerPage"]."\" SIZE=18></font>\n" ;

	$jf[$user->options["justify"]] = "checked" ;
	$ret .= "</td></tr><tr><td><b>Justify paragraphs :</b><br>" ;
	$ret .= "<input type=radio value=yes ".$jf["yes"]." name=doJustify>Yes<br>\n" ;
	$ret .= "<input type=radio value=no ".$jf["no"]." name=doJustify>No (Standard)\n" ;
	$ret .= "</td></tr>" ;

	# Skin
	$sk[$user->options["skin"]] = "checked" ;
	$ret .= "<tr><td valign=top nowrap><b>Skin :</b><br>\n" ;
	$ret .= "<input type=radio value=None ".$sk["None"]." name=doSkin>None (Standard)<br>\n" ;
	$ret .= "<input type=radio value=\"Star Trek\" ".$sk["Star Trek"]." name=doSkin>Star Trek<br>\n" ;

	# ??
	$ret .= "</td>" ;
	$ret .= "<td></td></tr>" ;
#	$nt[$user->options["markupNewTopics"]] = "checked" ;
#	$ret .= "</td><td valign=top nowrap><b>New Topics :</b><br>\n" ;
#	$ret .= "<input type=radio value=normal ".$nt["normal"]." name=NewTopics>Normal (Standard)<br>\n" ;
#	$ret .= "<input type=radio value=red ".$nt["red"]." name=NewTopics>Red<br>\n" ;
#	$ret .= "</td></tr>" ;

	$ret .= "<tr><td><center><input type=submit value=Save name=ButtonSave></center></td>" ;
	$ret .= "<td><center><input type=reset value=Reset name=ButtonReset></center></td></tr>" ;

	$ret .= "</table></FORM>$msg</nowiki>" ;
	return $ret ;
	}

function WantedPages () {
	global $linkedLinks , $unlinkedLinks , $vpage ;
	$vpage->special ( "The Most Wanted Topics" ) ;
	$vpage->namespace = "" ;
	$allPages = array () ;
	$linkedLinks = array () ;
	$unlinkedLinks = array () ;
	$ret = "'''These articles don't exist, but other articles link to them!'''\n\n" ;

	$connection = getDBconnection () ;
	mysql_select_db ( "wikipedia" , $connection ) ;
	$sql = "SELECT cur_title FROM cur" ;
	$result = mysql_query ( $sql , $connection ) ;
	while ( $s = mysql_fetch_object ( $result ) ) array_push ( $allPages , $s->cur_title ) ;
	mysql_free_result ( $result ) ;
	mysql_close ( $connection ) ;

	foreach ( $allPages as $x ) {
		$p = new WikiPage ;
		$p->load ( $x ) ;
		$p->replaceInternalLinks ( $p->contents ) ;
		}

	arsort ( $unlinkedLinks ) ;
	while ( count ( $unlinkedLinks ) > 20 ) array_pop ( $unlinkedLinks ) ;
	$a = array_keys ( $unlinkedLinks ) ;
	foreach ( $a as $x )
		$ret .= "[[$x]] (linked from $unlinkedLinks[$x] other topics)<br>\n" ;

	return $ret ;
	}

function LonelyPages () {
	global $linkedLinks , $unlinkedLinks , $vpage ;
	$vpage->special ( "The Lonely Pages" ) ;
	$vpage->namespace = "" ;
	$allPages = array () ;
	$linkedLinks = array () ;
	$unlinkedLinks = array () ;
	$ret = "'''These articles exist, but no articles link to them!'''\n\n" ;

	$connection = getDBconnection () ;
	mysql_select_db ( "wikipedia" , $connection ) ;
	$sql = "SELECT cur_title FROM cur" ;
	$result = mysql_query ( $sql , $connection ) ;
	while ( $s = mysql_fetch_object ( $result ) ) array_push ( $allPages , $s->cur_title ) ;
	mysql_free_result ( $result ) ;
	mysql_close ( $connection ) ;

	$r = array () ;
	foreach ( $allPages as $x ) {
		$p = new WikiPage ;
		$p->load ( $x ) ;
		$r["$p->secureTitle"] = 0 ;
		$p->replaceInternalLinks ( $p->contents ) ;
		}

	$a = array_keys ( $linkedLinks ) ;
	foreach ( $a as $x ) $r[$x]++ ;

	$a = array_keys ( $r ) ;
	foreach ( $a as $x ) {
		if ( $r[$x] == 0 )
			$ret .= "[[$x]]<br>\n" ;
		}
	
	return $ret ;
	}

function AllPages () {
	global $linkedLinks , $unlinkedLinks , $vpage ;
	$vpage->special ( "All Pages Index" ) ;
	$vpage->namespace = "" ;
	$ret = "'''These are all the articles in the database!'''\n\n" ;
	$connection = getDBconnection () ;
	mysql_select_db ( "wikipedia" , $connection ) ;
	$sql = "SELECT cur_title FROM cur ORDER BY cur_title" ;
	$result = mysql_query ( $sql , $connection ) ;
	while ( $s = mysql_fetch_object ( $result ) ) $ret .= "[[$s->cur_title]]<br>\n";
	mysql_free_result ( $result ) ;
	mysql_close ( $connection ) ;
	return $ret ;
	}

function searchLineDisplay ( $v ) {
	global $search ;
	$v = trim(str_replace("\n","",$v)) ;
	$v = str_replace ( "'''" , "" , $v ) ;
	$v = str_replace ( "''" , "" , $v ) ;
	$v = ereg_replace ( "\{\{\{.*\}\}\}" , "?" , $v ) ;
	$v = trim ( $v ) ;
	while ( substr($v,0,1) == ":" ) $v = substr($v,1) ;
	while ( substr($v,0,1) == "*" ) $v = substr($v,1) ;
	while ( substr($v,0,1) == "#" ) $v = substr($v,1) ;
	$v = eregi_replace ( $search , "'''".$search."'''" , $v ) ;
	$v = "<font size=-1>$v</font>" ;
	return $v ;
	}

function doSearch () {
	global $vpage , $search , $startat , $user ;
	$vpage = new WikiPage ;
	$vpage->special ( "Search" ) ;
	$r = array () ;
	$s = "" ;

	if ( $search == "" ) $s = "<h2>As you were searching for the void, you just found it.</h2>" ;
	else {
		if ( !isset ( $startat ) ) $startat = 1 ;
		$perpage = $user->options["resultsPerPage"] ;
		$connection = getDBconnection () ;
		mysql_select_db ( "wikipedia" , $connection ) ;
		$sql = "SELECT * FROM cur WHERE cur_title LIKE \"%$search%\" OR cur_text LIKE \"%$search%\" ORDER BY cur_title" ;
		$result = mysql_query ( $sql , $connection ) ;
		if ( $result != "" ) {
			$totalcnt = 0 ;
			while ( $s = mysql_fetch_object ( $result ) ) {
				if ( $totalcnt+1 >= $startat and count ( $r ) < $perpage )
					array_push ( $r , $s ) ;
					$totalcnt++ ;
				}
			mysql_free_result ( $result ) ;
			}
		mysql_close ( $connection ) ;
		}

	if ( $s == "" and count ( $r ) == 0 ) {
		$s = "<h2>Sorry, we were unable to find an article containing \"$search\" in any article title or body.</h2>" ;
	} else if ( $s == "" ) {
		$n = count ( $r ) ;
		if ( $n > 1 ) { $z1 = "s" ; $z2 = "were" ; }
		else $z2 = "was" ;
		$s .= "<table width=100% bgcolor=#FFFFCC><tr><td><font size=+1><b>Wikipedia Articles</b></font><br>\n" ;
		$s .= "$totalcnt occurence$z1 of ''$search'' $z2 found. " ;
		$s .= "For each article, you can see its first paragraph and the first paragraph that contains ''$search''.</td></tr></table>\n" ;
		$s .= "<table>" ;
		$realcnt = $startat ;
		$minlen = strlen ( $realcnt + count ( $r ) ) ;
		foreach ( $r as $x ) {
			$u = spliti ( "\n" , $x->cur_text ) ;
			$y = searchLineDisplay ( array_shift ( $u ) ) ;
			foreach ( $u as $v ) {
				if ( stristr($v,$search) != false ) {
					$y .= "...<br>...".searchLineDisplay($v) ;
					break ;
					}
				}

			for ( $z = $realcnt ; strlen ( $z ) < $minlen ; $z = "0$z" ) ;
			$ct = $vpage->getNiceTitle ( $x->cur_title ) ;
			$s .= "<tr><td valign=top width=20 align=right><b>$z</b></td><td><font face=\"Helvetica,Arial\">'''[[$ct]]'''</font><br>" ;
			$s .= $y ;
			$s .= "</td></tr>" ;
			$realcnt++ ;
			}
		$s .= "</table>" ;
		if ( $totalcnt > $perpage ) {
			$s .= "<nowiki>" ;
			$last = $startat-$perpage ;
			$next = $startat+$perpage ;
			if ( $startat != 1 ) $s .= "<a href=\"$PHP_SELF?search=$search&startat=".$last."\">&lt;&lt;</a> | ";
			for ( $a = 1 ; $a <= $totalcnt ; $a += $perpage ) {
				if ( $a != 1 ) $s .= " | " ;
				if ( $a != $startat ) $s .= "<a href=\"$PHP_SELF?search=$search&startat=$a\">";
				$s .= "$a-" ;
				$s .= $a+$perpage-1 ;
				if ( $a != $startat ) $s .= "</a>" ;
				}
			if ( $startat != $a-$perpage ) $s .= " | <a href=\"$PHP_SELF?search=$search&startat=".$next."\">&gt;&gt;</a>";
			$s .= "</nowiki>" ;
			}
		}

	$vpage->contents = $s ;
	return $vpage->renderPage () ;
	}

function listUsers () {
	global $user , $vpage , $startat ;
	if ( !isset ( $startat ) ) $startat = 1 ;
	$perpage = $user->options["resultsPerPage"] ;
	if ( $perpage == 0 ) $perpage = 20 ;
	
	$vpage->special ( "User List" ) ;
	$vpage->namespace = "" ;
	$ret = "'''These are all wikipedia users (that have an account)!'''\n\n" ;
	$connection = getDBconnection () ;
	mysql_select_db ( "wikipedia" , $connection ) ;
	$sql = "SELECT COUNT(*) AS number FROM cur WHERE cur_title LIKE \"User:%\"" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	$total = $s->number ;
	$sql = "SELECT * FROM cur WHERE cur_title LIKE \"User:%\" ORDER BY cur_title" ;
	$result = mysql_query ( $sql , $connection ) ;
	$cnt = 1 ;
	$color1 = $user->options["tabLine1"] ;
	$color2 = $user->options["tabLine2"] ;
	$color = $color1 ;
	$ret .= "<table width=100%>\n" ;
	while ( $s = mysql_fetch_object ( $result ) and $cnt < $startat+$perpage ) {
		if ( $cnt >= $startat ) {
			$u = $s->cur_title ;
			$u = substr ( strstr ( $u , ":" ) , 1 ) ;
			$u = $vpage->getNiceTitle ( $u ) ;
			$ret .= "<tr><td$color align=right nowrap>$cnt</td>" ;
			$ret .= "<td$color width=100% valign=top>[[$s->cur_title|$u]]</td></tr>\n";
			if ( $color == $color1 ) $color = $color2 ;
			else $color = $color1 ;
			}
		$cnt++ ;
		}
	$ret .= "</table>\n" ;
	
	$ret .= "<nowiki>" ;
	$before = $startat - $perpage ; $fin = $before + $perpage - 1 ;
	if ( $startat > 1 ) $ret .= "<a href=\"$PHP_SELF?title=special:UserList&startat=$before\">$before-$fin&lt;&lt;</a> &nbsp;" ;
	$after = $startat + $perpage ; $fin = $after+$perpage - 1 ; if ( $fin > $total ) $fin = $total ;
	if ( $after-1 < $total ) $ret .= "<a href=\"$PHP_SELF?title=special:UserList&startat=$after\">&gt;&gt;$after-$fin</a>" ;
	$ret .= "</nowiki>" ;
	mysql_free_result ( $result ) ;
	mysql_close ( $connection ) ;
	return $ret ;
	}

function randompage () {
	global $vpage ;
	$connection=getDBconnection() ;
	mysql_select_db ( "wikipedia" , $connection ) ;
	$sql = "SELECT COUNT(*) AS number FROM cur" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	mt_srand((double)microtime()*1000000);
	$randval = mt_rand(0,$s->number-1);
	mysql_free_result ( $result ) ;

	$sql = "SELECT cur_title FROM cur" ;
	$result = mysql_query ( $sql , $connection ) ;
	while ( $randval >= 0 ) {
		$s = mysql_fetch_object ( $result ) ;
		$randval-- ;
		}
	$thelink = $s->cur_title ;
	$ret = "<h2>Loading random page [[$thelink|".$vpage->getNiceTitle($thelink)."]]...</h2>" ;
	$ret .= "<META HTTP-EQUIV=Refresh CONTENT=\"0; URL=$PHP_SELF?title=$thelink\">" ;
	mysql_free_result ( $result ) ;
	mysql_close ( $connection ) ;

	return $ret ;
	}

# Convert MySQL timestame to date
function tsc ( $t ) {
	$year = substr ( $t , 0 , 4 ) ;
	$month = substr ( $t , 4 , 2 ) ;
	$day = substr ( $t , 6 , 2 ) ;
	$hour = substr ( $t , 8 , 2 ) ;
	$min = substr ( $t , 10 , 2 ) ;
	$sec = substr ( $t , 12 , 2 ) ;
	return mktime ( $hour , $min , $sec , $month , $day , $year ) ;
	}

function recentchanges () {
	global $vpage , $maxcnt , $PHP_SELF , $SERVER_NAME ;
	$vpage->special ( "Recent Changes" ) ;
	$vpage->makeSecureTitle() ;
	if ( !isset ( $maxcnt ) ) $maxcnt = 100 ;

	$ret = "" ;
	$ret .= "<b>These are the last $maxcnt changes made on [[Wikipedia]].</b> View the last " ;
	$ret .= "[http://$SERVER_NAME$PHP_SELF?title=special:RecentChanges&maxcnt=50 50] / " ;
	$ret .= "[http://$SERVER_NAME$PHP_SELF?title=special:RecentChanges&maxcnt=100 100] / " ;
	$ret .= "[http://$SERVER_NAME$PHP_SELF?title=special:RecentChanges&maxcnt=250 250] / " ;
	$ret .= "[http://$SERVER_NAME$PHP_SELF?title=special:RecentChanges&maxcnt=500 500] " ;
	$ret .= "changes." ;
	$arr = array () ;

	$connection=getDBconnection() ;
	mysql_select_db ( "wikipedia" , $connection ) ;
	$sql = "SELECT * FROM cur ORDER BY cur_timestamp DESC LIMIT $maxcnt" ;
	$result = mysql_query ( $sql , $connection ) ;
	while ( $s = mysql_fetch_object ( $result ) ) array_push ( $arr , $s ) ;
	mysql_free_result ( $result ) ;
	mysql_close ( $connection ) ;
	$ret .= recentChangesLayout ( $arr ) ;
	return $ret ;
	}

function recentChangesLayout ( &$arr ) {
	global $user ;
	$lastDay = "" ;
	$color1 = $user->options["tabLine1"] ;
	$color2 = $user->options["tabLine2"] ;
	$xyz = new WikiTitle ;
	$editTypes = array ( "0"=>"" , "1"=>"<font color=green>M</font>" , "2"=>"<font color=red>N</font>" ) ;
	$ret = " ('''Legend :''' ".$editTypes["1"]."=Minor edit ; ".$editTypes["2"]."=New article.)" ;
	$ret .= "<table width=100% border=0 cellpadding=2 cellspacing=0>\n" ;
	$dummy = "$PHP_SELF?x=y" ;
	foreach ( $arr as $s ) {
		$nt = $xyz->getNiceTitle ( $s->cur_title ) ;
		$day = date ( "l, F d, Y" , tsc ( $s->cur_timestamp ) ) ;
		$time = date ( "H:i" , tsc ( $s->cur_timestamp ) ) ;
		if ( $day != $lastDay ) {
			$lastDay = $day ;
			$ret .= "<tr><td width=100% colspan=6".$user->options["tabLine0"]."><b>$day</b></td></tr>" ;
			$color = $color1 ;
			}
		$u = $s->cur_user_text ;
		if ( $s->cur_user != 0 ) $u = "[[user:$u|$u]]" ;
		$comment = trim($s->cur_comment) ;
		if ( $comment == "*" ) $comment = "" ;
		if ( $s->cur_minor_edit == 1 ) $comment = "<font size=-1><i>$comment</i></font>" ;

		$minor = $editTypes[$s->cur_minor_edit] ;

		$t = "<tr>" ;
		$t .= "<td$color valign=top>(diff)&nbsp;</td>" ;
		if ( $s->version == "current" ) $t .= "<td$color valign=top><a href=\"$PHP_SELF?$s->cur_title\">$nt</a></td>" ;
		else if ( $s->version != "" ) $t .= "<td$color valign=top><a href=\"$PHP_SELF?$s->cur_title&oldID=$s->old_id\">$nt ($s->version)</a></td>" ;
		else $t .= "<td$color valign=top>[[$s->cur_title|$nt]]</td>" ;
		$t .= "<td$color valign=top>$time</td>" ;
		if ( $s->version != "" ) {
			$t .= "<td$color valign=top nowrap>$s->cur_user_text</td>" ;
			}
		else $t .= "<td$color valign=top nowrap>$u</td>" ;
		$t .= "<td$color valign=top>$minor</td>" ;
		$t .= "<td$color >$comment</td>" ;
		$ret .= $t."</tr>\n" ;
		if ( $color == $color1 ) $color = $color2 ;
		else $color = $color1 ;
		}
	$ret .= "</table>" ;
	return $ret ;
	}

function modifyArray ( $a , $sep , $rem , $add = "" ) {
	$b = explode ( $sep , $a ) ;
	$c = array () ;
	foreach ( $b as $x ) {
		if ( $x != "" and $x != $rem )
			array_push ( $c , $x ) ;
		}
	if ( $add != "" ) array_push ( $c , $add ) ;
	return implode ( "'" , $c ) ;
	}

function watch ( $t , $m ) {
	global $user ;
	if ( !$user->isLoggedIn ) return "NOT LOGGED IN!" ;

	# Modifying user_watch
	$a = getMySQL ( "user" , "user_watch" , "user_id=$user->id" ) ;
	if ( $m == "yes" ) $a = modifyArray ( $a , "'" , $t , $t ) ;
	else $a = modifyArray ( $a , "'" , $t ) ;
	setMySQL ( "user" , "user_watch" , $a , "user_id=$user->id" ) ;

	$ret = "Watching $t ($m)" ;
	$ret .= "<META HTTP-EQUIV=Refresh CONTENT=\"0; URL='$PHP_SELF?title=$t'\">" ;
	return $ret ;
	}

function WatchList () {
	global $vpage , $user ;
	$vpage->special ( "My watchlist" ) ;
	$ret = "'''Currently, you are watching the following articles :''' " ;
	$a = getMySQL ( "user" , "user_watch" , "user_id=$user->id" ) ;
	$b = explode ( "'" , $a ) ;
	$vpage->namespace = "" ;

	$n = array () ;
	foreach ( $b as $x )
		$n[$x] = getMySQL ( "cur" , "cur_timestamp" , "cur_title=\"$x\"" ) ;
	arsort ( $n ) ;
	$k = array_keys ( $n ) ;

	$connection=getDBconnection() ;
	mysql_select_db ( "wikipedia" , $connection ) ;
	$arr = array () ;
	foreach ( $k as $x ) {
		$sql = "SELECT * FROM cur WHERE cur_title=\"$x\"" ;
		$result = mysql_query ( $sql , $connection ) ;
		$s = mysql_fetch_object ( $result ) ;
		array_push ( $arr , $s ) ;
		mysql_free_result ( $result ) ;
		}
	mysql_close ( $connection ) ;
	$ret .= recentChangesLayout ( $arr ) ;	

	return $ret ;
	}

function statistics () {
	$connection=getDBconnection() ;
	mysql_select_db ( "wikipedia" , $connection ) ;
	$ret = "" ;
	$ret .= "<h2>Article statistics</h2><ul>" ;

	$nf1 = "<font color=red><b>" ;
	$nf2 = "</b></font>" ;

	# TOTAL	
	$sql = "SELECT COUNT(*) AS number FROM cur" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	$totalPages = $s->number ;
	$ret .= "<li>There are $nf1$totalPages$nf2 pages in the database</li>" ;
	mysql_free_result ( $result ) ;

	# /TALK
	$sql = "SELECT COUNT(*) as number FROM cur WHERE cur_title LIKE \"%/Talk\"" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	$talkPages = $s->number ;
	$ret .= "<li>There are $nf1$talkPages$nf2 <b>/Talk</b> pages</li>" ;
	mysql_free_result ( $result ) ;

	# , NOT /TALK
	$sql = "SELECT COUNT(*) as number FROM cur WHERE cur_title NOT LIKE \"%/Talk\" AND cur_text LIKE \"%,%\"" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	$commaPages = $s->number ;
	$ret .= "<li>There are $nf1$commaPages$nf2 with a comma that are <i>not</i> <b>/Talk</b> pages</li>" ;
	mysql_free_result ( $result ) ;

	# WIKIPEDIA NOT /TALK
	$sql = "SELECT COUNT(*) as number FROM cur WHERE cur_title NOT LIKE \"%/Talk\" AND cur_title LIKE \"%ikipedia%\"" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	$wikiPages = $s->number ;
	$ret .= "<li>There are $nf1$wikiPages$nf2 that have \"ikipedia\" in the title and are <i>not</i> <b>/Talk</b> pages</li>" ;
	mysql_free_result ( $result ) ;

	# WIKIPEDIA NOT /TALK
	$sql = "SELECT COUNT(*) as number FROM cur WHERE cur_title LIKE \"%/%\"" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	$subPages = $s->number - $talkPages;
	$ret .= "<li>There are $nf1$subPages$nf2 subpages that are <i>not</i> <b>/Talk</b> pages</li>" ;
	mysql_free_result ( $result ) ;

	# RESULT
	$x = $commaPages - $wikiPages ; # Comma (no /Talk) - wiki pages = articles, including subpages
	$ret .= "<li>That means there are about $nf1$x$nf2 articles, including subpages (except <b>/Talk</b>).</li>" ;
	$y = $x - $subPages ;
	$ret .= "<li>Or, there are about $nf1$y$nf2 articles, not counting any subpages!</li>" ;
	$z = $totalPages - $talkPages - $commaPages ;
	$ret .= "<li>Finally, there are about $nf1$z$nf2 junk pages :-(</li>" ;

	# OLD PAGES
	$sql = "SELECT COUNT(*) as number FROM old" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	$oldPages = $s->number - $talkPages;
	$p = round ( $oldPages / $totalPages , 2 ) ;
	$ret .= "<li>And, there are $nf1$oldPages$nf2 old page versions in the database, giving an average of $p old pages on every active page.</li>" ;
	mysql_free_result ( $result ) ;


	$ret .= "</ul><hr>" ;
	$ret .= "<h2>User statistics</h2><ul>" ;
	
	# USERS
	$sql = "SELECT COUNT(*) as number FROM user" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	$numUser = $s->number ;
	$ret .= "<li>There are currently $nf1$numUser$nf2 users signed up.</li>" ;
	mysql_free_result ( $result ) ;
	
	# EDITORS AND SYSOPS
	$sql = "SELECT COUNT(*) as number FROM user WHERE user_rights LIKE \"%is_editor%\" OR user_rights LIKE \"%is_sysop%\"" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	$numEditors = $s->number ;
	$ret .= "<li>$nf1$numEditors$nf2 of them have sysop status.</li>" ;
	mysql_free_result ( $result ) ;

	mysql_close ( $connection ) ;
	$ret .= "</ul>" ;
	return $ret ;
	}

function upload () {
	global $removeFile , $xtitle , $removeFile , $Upload , $Upload_name , $no_copyright ;
	global $user , $vpage ;
	$vpage->special ( "Upload Page" ) ;

#	if ( $USERLOGGEDIN != "YES" ) return "You are not logged in! You have to be logged in to upload a file. <a href=\"$PHP_SELF?action=login\">Log in</a> or return to the <a href=\"$PHP_SELF?no\">HomePage</a>" ;

#	$rights = ",".getUserSetting ( $USERNAME , "user_rights" )."," ;
#	if ( strstr ( $rights , ",is_editor," ) or strstr ( $rights , ",is_sysop" ) ) $isEditor = true ;
#	else $isEditor = false ;
#	if ( strstr ( $rights , ",is_sysop," ) or strstr ( $rights , ",is_sysop" ) ) $isSysop = true ;
#	else $isSysop = false ;

	$xtitle = "File upload page";
	$ret = "<nowiki>" ;

	$message = "" ;

	if (isset($removeFile)) {
#		if ( !$isSysop and !$isEditor ) return "You are neither an editor nor a sysop. Return to the <a href=\"$PHP_SELF?action=upload\">Upload page</a>" ;
		if (is_file("upload/$removeFile") ) unlink ("./upload/$removeFile");
		$message = "File <b>$removeFile</b> deleted!" ;
		unset ( $removeFile ) ;
	} else if (isset($Upload_name) or isset($Upload)) {
		if ( $no_copyright != "AFFIRMED" ) return "You need to affirm that the file is not violating copygights. Return to the <a href=\"$PHP_SELF?action=upload\">Upload page</a>" ;
		$Upload_name = ereg_replace(" ", "_", $Upload_name);
		$abc = split("\.", $Upload_name);

		$num = exec ("df");
		$readata = substr($num,(strpos($num, "%")-2),2);

		if ($readata > 96) {
			$ret .= "<body bgcolor=white>\n";
			$ret .= "<br><b>Sorry, we are almost out of disk space. We can't let you upload any files right now.</b>\n";
			return $ret ;
			}

		copy ( $Upload , "./upload/$Upload_name" ) ;
		system ("chmod 777 ./upload/$Upload_name");
		$message = "File <b>$Upload_name</b> was successfully uploaded!" ;

		unset ( $Upload_name ) ;
	}

	if ( $message != "" ) $ret .= "<font color=red>$message</font><br>\n" ;

	$ret .= "<h2>Instructions:</h2><ul>\n";
	$ret .= "<li><strong>Use this form to upload various files</strong></li>\n";
	$ret .= "<li>To replace a previously-uploaded file (e.g., a\n";
	$ret .= "new version of the article), simply re-upload the\n";
	$ret .= "same file. But first look below and make sure you\n";
	$ret .= "haven't changed the name.</li>\n";
	$ret .= "<li><strong>Here's how to upload your file. </strong>Click\n";
	$ret .= "&quot;Browse...&quot; to your find the file you\n";
	$ret .= "want to upload on your hard drive. This will open\n";
	$ret .= "a &quot;Choose file&quot; dialogue window.</li>\n";
	$ret .= "<li>When you've found the file, click &quot;Open.&quot;\n";
	$ret .= "This will select the file and close the &quot;Choose\n";
	$ret .= "file&quot; dialogue window.</li>\n";
	$ret .= "<li>Don't forget to check the copyright statement!</li>\n";
	$ret .= "<li>Then click &quot;Upload.&quot; The file will start uploading. This may take some time, if it's\n";
	$ret .= "a big file and you have a slow Internet connection.</li>\n";
	$ret .= "<li>A message will tell you when the file has successfully uploaded.</li>\n";
	$ret .= "<li>You can upload as many files you like. Please don't try to crash our server, ha ha.</li>\n";
	$ret .= "</ul>\n";

	$ret .= " <form enctype=\"multipart/form-data\" action=\"$PHP_SELF?title=special:upload\" method=post>\n";
	$ret .= " <input type=hidden name=max value=20096>\n";
	$ret .= " <input name=Upload type=\"file\"><br>\n";
	$ret .= " <input type=hidden name=update value=1>\n";
	$ret .= " <input type=hidden name=step value=$step>\n";
	$ret .= "<INPUT TYPE=checkbox NAME=\"no_copyright\" VALUE=\"AFFIRMED\">I hereby affirm that this file is <b>not copyrighted</b>, or that I own the copyright for this file and donate it to Wikipedia.<br>\n" ;
	$ret .= " <input type=submit value=UPLOAD>\n";
	$ret .= "</form>\n";

	if (is_dir("upload")) {
		$mydir = dir("upload");
			while ($entry = $mydir->read()) {
			if ($entry != "." and $entry != "..")
				$file = "yes";
			}
		$mydir->close();

		if ($file == "yes") {
			$ret .= "<h2>Previously-uploaded files:</h2>";
			$mydir = opendir("upload");
			$i = 0;
			$ret .= "<table border=1 width=\"100%\">\n";
			$ret .= "<tr><th>File</th><th>Size (byte)</th>";
#			if ( $isSysop or $isEditor )
			$ret .= "<th>File removal</th>";
			$ret .= "</tr>\n" ;
			while ($entry = readdir($mydir)) {
				if ($entry != '.' && $entry != '..') {
					$ret .= "<tr><td align=center>" ;
					$ret .= "<a href=upload/$entry>$entry</a></td>";
					$ret .= "<td align=center>".filesize("upload/$entry")." bytes</td>";
#					if ( $isSysop or $isEditor )
					$ret .= "<td align=center><a href=\"$PHP_SELF?title=special:upload&removeFile=$entry\">Click here to remove $entry.</a></td>" ;
					$ret .= "</tr>" ;
					$i++;
				}
			}
		$ret .= "</table>\n";
		closedir($mydir);
		}
	}
	$ret .= "</nowiki>" ;
	return $ret ;
	}

function doHistory ( $title ) {
	global $vpage ;
	$vpage = new WikiPage ;
	$vpage->title = $title ;
	$vpage->makeSecureTitle () ;
	$ti = $vpage->secureTitle ;
	$vpage->special ( "History of $title" ) ;
	$vpage->makeSecureTitle () ;

	$a = array () ;
	$connection = getDBconnection () ;
	mysql_select_db ( "wikipedia" , $connection ) ;
	$sql = "SELECT * FROM cur WHERE cur_title=\"$ti\"" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	array_push ( $a , $s ) ;
	mysql_free_result ( $result ) ;
	$o = $s->cur_old_version ;
	while ( $o != 0 ) {
		$sql = "SELECT * FROM old WHERE old_id=$o" ;
		$result = mysql_query ( $sql , $connection ) ;
		$s = mysql_fetch_object ( $result ) ;
		$s->cur_timestamp = $s->old_timestamp ;
		$s->cur_title = $s->old_title ;
		$s->cur_user = $s->old_user ;
		$s->cur_user_text = $s->old_user_text ;
		$s->cur_minor_edit = $s->old_minor_edit ;
		array_push ( $a , $s ) ;
		$o = $s->old_old_version ;
		mysql_free_result ( $result ) ;
		}
	mysql_close ( $connection ) ;

	$i = count ( $a ) ;
	$k = array_keys ( $a ) ;
	foreach ( $k as $x ) {
		if ( $i != count ( $a ) ) $a[$x]->version = $i ;
		else $a[$x]->version = "current" ;
		$i-- ;
		}

	$t = "<b>This is the history of <a href=\"$PHP_SELF?title=$title\">$title</a></b>".recentChangesLayout ( $a ) ;

	$ret = $vpage->getHeader() ;
	$ret .= $vpage->getMiddle($t) ;
	$ret .= $vpage->getFooter() ;
	return $ret ;
	}

function special_pages () {
	global $vpage , $user ;
	$vpage->special ( "Special Pages" ) ;
	$ret = "<b>This is a list of special pages.</b><br>" ;
	$ret .= "Some of them are only available if you are logged in. If you are logged in, you can have this list automatically displayed on the right or left of each page as a QuickBar.<br><br>" ;
	$ret .= $vpage->getQuickBar () ;
	return $ret ;
	}

function ShortPages () {
	global $user , $vpage , $startat ;
	if ( !isset ( $startat ) ) $startat = 1 ;
	$perpage = $user->options["resultsPerPage"] ;
	if ( $perpage == 0 ) $perpage = 20 ;
	
	$vpage->special ( "Short 'stub' articles" ) ;
	$vpage->namespace = "" ;
	$ret = "'''These are all the articles in the database, sorted by length!'''\n\n" ;
	$connection = getDBconnection () ;
	mysql_select_db ( "wikipedia" , $connection ) ;
	$sql = "SELECT COUNT(*) AS number FROM cur" ;
	$result = mysql_query ( $sql , $connection ) ;
	$s = mysql_fetch_object ( $result ) ;
	$total = $s->number ;
	$sql = "SELECT * FROM cur ORDER BY LENGTH(cur_text)" ;
	$result = mysql_query ( $sql , $connection ) ;
	$cnt = 1 ;
	$color1 = $user->options["tabLine1"] ;
	$color2 = $user->options["tabLine2"] ;
	$color = $color1 ;
	$ret .= "<table width=100%>\n" ;
	while ( $s = mysql_fetch_object ( $result ) and $cnt < $startat+$perpage ) {
		if ( $cnt >= $startat ) {
			$ret .= "<tr><td$color align=right nowrap>$cnt</td>" ;
			$ret .= "<td$color align=right nowrap>(".strlen($s->cur_text)." chars)</td>\n" ;
			$ret .= "<td$color width=100% valign=top>[[$s->cur_title]]</td></tr>\n";
			if ( $color == $color1 ) $color = $color2 ;
			else $color = $color1 ;
			}
		$cnt++ ;
		}
	$ret .= "</table>\n" ;
	
	$ret .= "<nowiki>" ;
	$before = $startat - $perpage ; $fin = $before + $perpage - 1 ;
	if ( $startat > 1 ) $ret .= "<a href=\"$PHP_SELF?title=special:ShortPages&startat=$before\">$before-$fin&lt;&lt;</a> &nbsp;" ;
	$after = $startat + $perpage ; $fin = $after+$perpage - 1 ; if ( $fin > $total ) $fin = $total ;
	if ( $after-1 < $total ) $ret .= "<a href=\"$PHP_SELF?title=special:ShortPages&startat=$after\">&gt;&gt;$after-$fin</a>" ;
	$ret .= "</nowiki>" ;
	mysql_free_result ( $result ) ;
	mysql_close ( $connection ) ;
	return $ret ;
	}
?>