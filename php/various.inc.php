<?php
function titleLink ($title)
{
    return str_replace('%2F', '/', urlencode(str_replace(' ', '_', $title)));
}

function timeoffset($lang)
{
    $tstimezone = date_default_timezone_get();
    $tz = '';
    switch($lang) {
        case 'nl':
            $tz = 'Europe/Amsterdam';
            break;
        case 'de':
            $tz = 'Europe/Berlin';
            break;
        case 'fr':
            $tz = 'Europe/Paris';
            break;
    }
    if (!empty($tz)) {
        date_default_timezone_set($tz);
        $offset = date('Z');
        date_default_timezone_set($tstimezone);
        return $offset;
    } else {
        return false;
    }
}

function createDateObject($timestamp)
{
        $year = substr($timestamp, 0, 4);
        $month = substr($timestamp, 4, 2);
        $day = substr($timestamp, 6, 2);
        $hour = substr($timestamp, 8, 2);
        $minute = substr($timestamp, 10, 2);
        $second = substr($timestamp, 12, 2);
        return mktime($hour, $minute, $second, $month, $day, $year);
}

function formatDate($timestamp, $lang = 'en', $offset = 0)
{
        $localmonth =   array('nl' => array(    '01' => 'jan',
                                                                        '02' => 'feb',
                                                                        '03' => 'mrt',
                                                                        '04' => 'apr',
                                                                        '05' => 'mei',
                                                                        '06' => 'jun',
                                                                        '07' => 'jul',
                                                                        '08' => 'aug',
                                                                        '09' => 'sep',
                                                                        '10' => 'okt',
                                                                        '11' => 'nov',
                                                                        '12' => 'dec')
                                        );

        $year = substr($timestamp, 0, 4);
        $month = substr($timestamp, 4, 2);
        $day = substr($timestamp, 6, 2);
        $hour = substr($timestamp, 8, 2);
        $minute = substr($timestamp, 10, 2);
        $second = substr($timestamp, 12, 2);
        switch($lang)
        {
                case 'nl':
                        $date = date('j _ Y H:i', mktime($hour, $minute, $second, $month, $day, $year) + $offset);
                        return str_replace('_', $localmonth['nl'][$month], $date);
                        break;
                case 'enspecial':
                        return date('Y-m-d H:i:s', mktime($hour, $minute, $second, $month, $day, $year) + $offset);
                        break;
                default:
                        return date('H:i, j F Y', mktime($hour, $minute, $second, $month, $day, $year) + $offset);
                        break;
        }
}

function formatCommentown($comment, $page_title, $domain)
{
        $comment = htmlentities($comment);
        $spaceinanchor = '/\/\*([^(?:\*\/)]+?) ([^(?:\*\/)]+?)\*\//';
        $ctemp1 = preg_replace($spaceinanchor, '/*\1_\2*/', $comment);
        $ctemp2 = $comment;
        while ($ctemp1 != $ctemp2)
        {
                $ctemp2 = $ctemp1;
                $ctemp1 = preg_replace($spaceinanchor, '/*\1_\2*/', $ctemp2);
        }
        $comment = $ctemp1;

        $anchor = '/\/\*[ ]*(.*?)[ ]*\*\//';
        $comment = preg_replace($anchor, '<span class="autocomment"><a href="http://' . $domain . '/wiki/' . $page_title . '#\1">→</a>\1 -</span>', $comment);

        $link = '/\[\[([^\|]*?)\]\]/';
        $comment = preg_replace($link, '<a href="http://' . $domain . '/wiki/\1">\1</a>', $comment);

        $linkalt = '/\[\[([^\|]*?)\|([^|]*?)\]\]/';
        $comment = preg_replace($linkalt, '<a href="http://' . $domain . '/wiki/\1">\2</a>', $comment);

        return $comment;
}

function formatLinksInComment( $comment )
{
        global $domain;
        $link = '/\[\[([^\|]*?)\]\]/';
        $comment = preg_replace($link, '<a href="http://' . $domain . '/wiki/\1">\1</a>', $comment);

        $linkalt = '/\[\[([^\|]*?)\|([^|]*?)\]\]/';
        $comment = preg_replace($linkalt, '<a href="http://' . $domain . '/wiki/\1">\2</a>', $comment);

        return $comment;
}
//Code below is from MediaWiki
function commentBlock( $comment, $title = NULL, $local = False)
{

        // '*' used to be the comment inserted by the software way back
        // in antiquity in case none was provided, here for backwards
        // compatability, acc. to brion -ævar

        if( $comment == '' || $comment == '*' )
        {
                return '';
        }
        else
        {
                $formatted = formatComment( $comment, $title, $local );
                return " <span class=\"comment\">($formatted)</span>";
        }
}

function formatComment($comment, $title = NULL, $local = false)
{
        # Sanitize text a bit:
        $comment = str_replace( "\n", " ", $comment );
        $comment = htmlspecialchars( $comment );

        # Render autocomments and make links:
        $comment = formatAutoComments( $comment, $title, $local );
        $comment = formatLinksInComment( $comment );

        return $comment;
}

function formatAutocomments( $comment, $title = NULL, $local = False)
{
        global $domain;
        $match = array();
        while (preg_match('!(.*)/\*\s*(.*?)\s*\*/(.*)!', $comment,$match))
        {
                $pre=$match[1];
                $auto=$match[2];
                $post=$match[3];
                $link='';
                if( $title )
                {
                        $section = $auto;

                        # Generate a valid anchor name from the section title.
                        # Hackish, but should generally work - we strip wiki
                        # syntax, including the magic [[: that is used to
                        # "link rather than show" in case of images and
                        # interlanguage links.
                        $section = str_replace( '[[:', '', $section );
                        $section = str_replace( '[[', '', $section );
                        $section = str_replace( ']]', '', $section );
                        $section = str_replace( ' ', '_', $section );
                        /*if ( $local )
                        {
                                //$sectionTitle = Title::newFromText( '#' . $section);
                                $sectionTitle = 'ERROR';
                        }
                        else
                        {
                                $sectionTitle = $title;
                                $mFragment = $section;
                        }*/
                        $link = '<a href = "http://' . $domain . '/wiki/' . $title . '#' . $section . '">→</a>';
                        #$this->makeKnownLinkObj( $sectionTitle, wfMsg( 'sectionlink' ) );
                        #<span class="autocomment"><a href="http://' . $domain . '/wiki/' . $page_title . '#\1">→</a>\1 -</span>
                }
                $sep='-';
                $auto=$link.$auto;
                if($pre) { $auto = $sep.' '.$auto; }
                if($post) { $auto .= ' '.$sep; }
                $auto='<span class="autocomment">'.$auto.'</span>';
                $comment=$pre.$auto.$post;
        }
        return $comment;
}
?>
