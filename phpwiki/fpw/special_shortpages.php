<?
include_once ( "wikiSettings.php" ) ;

function pagesThatLinkHere ( $t , $connection ) {
    $a = array () ;
    $sql = "SELECT DISTINCT linked_from
            FROM linked
            WHERE linked_to = \"$t\" " ;
    $result = mysql_query ( $sql , $connection ) ;
    while ( $s = mysql_fetch_object ( $result ) )
        array_push ( $a , $s ) ;
    mysql_free_result ( $result ) ;
    return $a ;
}

function ShortPages () {
    global  $THESCRIPT , $user , $vpage , $startat , $wikiStubTitle , $wikiStubText ,
            $showLinksThere , $wikiStubShowLinks , $wikiShowLinks ;
    
    if ( !isset ( $startat ) ) $startat = 1 ;
    $perpage = $user->options["resultsPerPage"] ;
    if ( $perpage == 0 ) $perpage = 20 ;
    $vpage->special ( $wikiStubTitle ) ;
    $vpage->namespace = "" ;
    if ( $showLinksThere == "" ) $showLinksThere = 0 ;
    if ( $showLinksThere == 1 ) $sLT2 = 0 ;
    else $sLT2 = 1 ;
    
    $ret = $wikiStubText ;
    $ret .= "<nowiki><a href=\"" . wikiLink ("special:ShortPages&showLinksThere=$sLT2") . "\">$wikiStubShowLinks</a><br></nowiki>\n";
    $connection = getDBconnection () ;
    $sql = "SELECT COUNT(*) AS number
            FROM cur
            WHERE cur_title NOT LIKE \"%:%\" AND cur_text NOT LIKE \"#redirect%\"" ;
    $result = mysql_query ( $sql , $connection ) ;
    $s = mysql_fetch_object ( $result ) ;
    $total = $s->number ;
    $sql = "SELECT cur_title, LENGTH(cur_text) AS len
            FROM cur
            WHERE cur_title NOT LIKE \"%:%\" AND cur_text NOT LIKE \"#redirect%\"
            ORDER BY LENGTH(cur_text), cur_title" ;
    $result = mysql_query ( $sql , $connection ) ;
    $cnt = 1 ;
    $color1 = $user->options["tabLine1"] ;
    $color2 = $user->options["tabLine2"] ;
    $color = $color1 ;
    $ret .= "<table width=\"100%\">\n" ;
    $ar = array () ;
    while ( $s = mysql_fetch_object ( $result ) and $cnt < $startat+$perpage ) {
        if ( $cnt >= $startat ) {
            $s->cnt = $cnt ;
            array_push ( $ar , $s ) ;
            }
        $cnt++ ;
        }
    mysql_free_result ( $result ) ;

    global $wikiStubChars , $wikiStubDelete , $wikiStubLinkHere ;

    foreach ( $ar as $s ) {
        $k = new wikiTitle ;
        $k->setTitle ( $s->cur_title ) ;
        $ret .= "<tr><td$color align=right valign=top nowrap>$s->cnt</td>" ;
        $ret .= "<td$color align=right valign=top nowrap>(".str_replace("$1",$s->len,$wikiStubChars).")</td>\n" ;
        $ret .= "<td$color nowrap valign=top>[[$s->cur_title|".$k->getNiceTitle()."]]</td>\n";
        if ( in_array ( "is_sysop" , $user->rights ) )
            $ret .= "<td$color valign=top nowrap><nowiki><a href=\"".wikiLink("special:deletepage&target=$k->url")."\">$wikiStubDelete</a></nowiki></td>" ;
#        else $ret .= "<td$color width=\"100%\" nowrap>&nbsp;</td>" ;

        if ( $showLinksThere == 1 ) {
            $lf = "" ;
            $lh = pagesThatLinkHere($s->cur_title,$connection);
            if ( count ( $lh ) <= 5 and count ( $lh ) > 0 ) {
                foreach ( $lh as $ll ) {
                    if ( $lf == "" ) $lf = " <font size=-1>(" ;
                    else $lf .= " - " ;
                    $lf .= "[[$ll->linked_from|".$k->getNiceTitle($ll->linked_from)."]]" ;
                    }
                $lf .= ")</font>" ;
                }
            $ret .= "<td$color width=\"100%\" valign=top>".str_replace("$1",count($lh),$wikiStubLinkHere)."$lf</td>\n";
        } else $ret .= "<td$color valign=top><nowiki><a href=\"".wikiLink("special:whatlinkshere&target=$k->url")."\">" . str_replace ( "$1" , $k->getNiceTitle() , $wikiShowLinks ) . "</a></nowiki></td>\n" ;

        $ret .= "</tr>" ;
        if ( $color == $color1 ) $color = $color2 ;
        else $color = $color1 ;
        }
    $ret .= "</table>\n" ;

    $ret .= "<nowiki>" ;
    $before = $startat - $perpage ; $fin = $before + $perpage - 1 ;
    if ( $startat > 1 ) $ret .= "<a href=\"".wikiLink("special:ShortPages&startat=$before&showLinksThere=$showLinksThere")."\">$before-$fin&lt;&lt;</a> &nbsp;";
    $after = $startat + $perpage ; $fin = $after+$perpage - 1 ; if ( $fin > $total ) $fin = $total ;
    if ( $after-1 < $total ) $ret .= "<a href=\"".wikiLink("special:ShortPages&startat=$after&showLinksThere=$showLinksThere")."\">&gt;&gt;$after-$fin</a>" ;
    $ret .= "</nowiki>" ;
    return $ret ;
    }
?>
