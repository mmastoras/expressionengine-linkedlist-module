<?php
/*
=====================================================
 File: mcp.linklist.php
-----------------------------------------------------
 Purpose: Linklist class - CP
-----------------------------------------------------
 Written by: Yoshi Melrose
-----------------------------------------------------
 http://www.psychodaisy.com/
=====================================================
The MIT License

Copyright &copy; 2005 Yoshiaki Melrose

Permission is hereby granted, free of charge, to any
person obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the 
Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish, 
distribute, sublicense, and/or sell copies of the 
Software, and to permit persons to whom the Software
is furnished to do so, subject to the following 
conditions:

The above copyright notice and this permission notice 
shall be included in all copies or substantial portions
of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF
ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT 
LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO 
EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN
AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE 
OR OTHER DEALINGS IN THE SOFTWARE.
=====================================================
*/

class Linklist {

    var $return_data= '';
	var $version = "1.2.1";
	var $subversion = "0";
	var $reccount = 0;

    // -------------------------------------
    //  Constructor
    // -------------------------------------

    function Linklist()
    {
		// There's nothing to do here. 
    }
	// END
	
	function entries()
	{
		global $TMPL, $DB, $LOC, $FNS, $SESS, $PREFS;
		
		$results = '';
		$linklist_temp = '';

		$sql = 'SELECT exp_linklist.member_id, exp_linklist.linklist_name, exp_linklist.linklist_title, exp_linklist.prepend_str, exp_linklist.append_str,exp_linklist.recently_updated, exp_linklist_urls.*
		FROM exp_linklist_urls INNER JOIN exp_linklist ON exp_linklist_urls.linklist_id = exp_linklist.linklist_id WHERE ';
		if ( ! $sql .= $this->build_query() )
		{
			return;
		}
		
		// we need a total record count. so we must set the limit 0,xxx off the list.
//		if ( eregi('LIMIT',$sql) )
		if ( preg_match('/LIMIT/i', $sql))
		{
			$offset = strlen($sql) - strpos($sql,'LIMIT');
			$reccountsql = substr($sql,0,-$offset);
		}
		else
		{
			$reccountsql = $sql;
		}
		$reccountquery = $DB->query($reccountsql);
		$this->reccount = $reccountquery->num_rows;
		
		$query = $DB->query($sql);

		if ( $query->num_rows == 0 )
		{
			return $TMPL->no_results();
		}


		$pagethru_chunk = array();
		$pagethruCode = '';
		if (preg_match_all("/".LD."paginate(.*?)".RD."(.*?)".LD.SLASH.'paginate'.RD."/s", $TMPL->tagdata, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$pagethru_chunk[] = array($matches['2'][$j], $FNS->assign_parameters($matches['1'][$j]));
			}
		}
		foreach ($TMPL->var_pair as $key => $val)
		{    
			// Added 7/30/05
//			if ( eregi("^paginate",$key) )
			if ( preg_match("/^paginate/i",$key) )
			{
				if ( ! $temp = $this->pagethru($this->reccount,$pagethru_chunk,$TMPL->fetch_param('limit')) )
				{
					$TMPL->tagdata = $TMPL->delete_var_pairs($key, 'paginate', $TMPL->tagdata);
					$pagethruCode = '';
				}
				else
				{
					$pagethruCode = str_replace($pagethru_chunk[0][0],$temp, $pagethru_chunk[0][0]);
					$TMPL->tagdata = $TMPL->delete_var_pairs($key, 'paginate', $TMPL->tagdata);
				}
			}
		}
		
		if ( $TMPL->fetch_param('paginate') == 'top' )
		{
			$results .= $pagethruCode;
		}
		// Parse the output now
		foreach ( $query->result as $row )
		{
			if ( $row['updated'] > 0 )
			{
				// We first have to determine if a site has been recently updated.
				// Offset of Hours since last update
				$updatedoffset = $row['recently_updated'] * 60 * 60;
				// calculate the difference between the site was updated and now.
				$last_updated_time = time() - $row['updated'];
				// if the site updated is less than the updatedoffset, then we'll say it's recent.
				$recentlyupdated = ( $last_updated_time < $updatedoffset ) ? 1 : 0;
				// Ok, now convert the last_updated_time into hours. this would make things easier to know how long ago the 
				// url was actually updated.
				$updated_hours = floor($last_updated_time / 3600);
			}
			else
			{
				$recentlyupdated = 0;
				$updated_hours = 0;
			}

			$tagdata = $TMPL->tagdata;

			$kw_chunk = array();
			
			if (preg_match_all("/".LD."keywords(.*?)".RD."(.*?)".LD.SLASH.'keywords'.RD."/s", $TMPL->tagdata, $matches))
			{
				for ($j = 0; $j < count($matches['0']); $j++)
				{
					$kw_chunk[] = array($matches['2'][$j], $FNS->assign_parameters($matches['1'][$j]));
				}
			}
			// Parse conditionals
			foreach ($TMPL->var_cond as $val)
			{
				// ----------------------------------------
				//   Conditional statements
				// ----------------------------------------
				
				// The $val['0'] variable contains the full contitional statement.
				// For example: if username != 'joe'
				
				// Prep the conditional
								
				$cond = $FNS->prep_conditional($val['0']);
				
				$lcond	= substr($cond, 0, strpos($cond, ' '));
				$rcond	= substr($cond, strpos($cond, ' '));
				
				if ( $val['3'] == 'updated_hours' )
				{
					if ( $updated_hours > 0 )
					{
						$lcond = str_replace($val['3'],"$"."updated_hours", $lcond);
						
						$cond = $lcond.' '.$rcond;
						
						$cond = str_replace("\|","|", $cond);
						
						eval ("\$result = ".$cond.";");
						
						if ($result)
						{
							$tagdata = str_replace($val['1'], $val['2'], $tagdata);                 
						}
						else
						{
							$tagdata = str_replace($val['1'], '', $tagdata);                 
						}   
					}
					else
					{
						$tagdata = str_replace($val['1'], '', $tagdata);                 
					}
				}
				
				if ( $val['3'] == 'recently_updated' )
				{
					if ( $updated_hours > 0 )
					{
						$tagdata = str_replace($val['1'], (($recentlyupdated) ? $val['2'] : ''), $tagdata);
						
					}
					else
					{
						$tagdata = str_replace($val['1'], '', $tagdata);                 
					}
				}
				
				// ----------------------------------------
				//  Parse conditions in standard fields
				// ----------------------------------------
								
				if ( isset($row[$val['3']]))
				{  
					$lcond = str_replace($val['3'], "\$row['".$val['3']."']", $lcond);
					
					$cond = $lcond.' '.$rcond;
					  
					$cond = str_replace("\|", "|", $cond);
			
					eval("\$result = ".$cond.";");
										
					if ($result)
					{
						$tagdata = str_replace($val['1'], $val['2'], $tagdata);                 
					}
					else
					{
						$tagdata = str_replace($val['1'], '', $tagdata);                 
					}   
				}
					
			}
			// END CONDITIONAL PAIRS

            // ----------------------------------------
            //   Parse Variable Pairs
            // ----------------------------------------

            foreach ($TMPL->var_pair as $key => $val)
            {    
	           	// Parse the linklist name
//				if (eregi("^list_heading", $key))
				if (preg_match("/^list_heading/i", $key))
				{
					$linklist_title = $row['linklist_title'];
					if ( $linklist_title == $linklist_temp )
					{
						$tagdata = $TMPL->delete_var_pairs($key, 'list_heading', $tagdata);
					}
					else
					{
						// If we're backspacing, then let's get rid of it now, and see if it works on a per linklist.
						// But note, that this is will ONLY happen when using the list_heading function. otherwise we
						// don't really know that they want it to be parsed like that.
						if ( $TMPL->fetch_param('backspace') != '' )
						{
							$results = substr($results,0,-$TMPL->fetch_param('backspace'));
						}
						$tagdata = $TMPL->swap_var_pairs($key, 'list_heading', $tagdata);
						$linklist_temp = $linklist_title;
					}
				}
				// Added 6/3/05
//				if ( eregi("^keywords",$key) )
				if ( preg_match("/^keywords/i",$key) )
				{
					if ( $row['keywords'] != '' )
					{
						$kwarray = explode(",",$row['keywords']);
						$kwval = $kw_chunk[0];
						if ( $kwval[0] != '' && count($kwarray) > 0)
						{
							$temp = '';
							foreach ( $kwarray as $kw_row )
							{
//								if ( eregi(LD."keyword".RD,$kwval[0]) )
								if ( preg_match("/".LD."keyword".RD."/i",$kwval[0]) )
								{
									$tmp = $TMPL->swap_var_single("keyword", trim($kw_row), $kwval[0]);
								}
								if (preg_match_all("#".LD."path=(.+?)".RD."#", $kwval[0], $matches))
								{
									foreach ($matches['1'] as $match)
									{
										// convert spaces into underscores here, so we can have legal URL's.
										$kw_row = str_replace(" ","_",trim($kw_row));
										$tmp = preg_replace("#".LD."path=.+?".RD."#", $FNS->create_url($match).trim($kw_row), $tmp, 1);
									}
								}
								$temp .= $tmp;
							}
							if ( isset($kwval[1]['backspace']) )
							{
								$temp = substr($temp,0,-$kwval[1]['backspace']);
							}
							$tagdata = str_replace($kwval[0], $temp, $tagdata);
							$tagdata = $TMPL->swap_var_pairs($key, 'keywords', $tagdata);
						}
						else
						{
							$tagdata = $TMPL->delete_var_pairs($key, 'keywords', $tagdata);
						}
					}
					else
					{
						$tagdata = $TMPL->delete_var_pairs($key, 'keywords', $tagdata);
					}
				}
			}
			// Parse single vars
			foreach ($TMPL->var_single as $key => $val)
			{
//				if (eregi("^normal_linkcode", $key))
				if (preg_match("/^normal_linkcode/i", $key))				
				{
					if ( $recentlyupdated )
					{
						$tagdata = $TMPL->swap_var_single(
															$key,
															$row['prepend_str'].
															"<a href='".
															$row['url'].
															"'>".
															$row['url_title'].
															"</a>".
															$row['append_str'],
															$tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single(
															$key,
															"<a href='".
															$row['url'].
															"'>".
															$row['url_title'].
															"</a>",
															$tagdata);
					}
				}
//				if (eregi("^count_linkcode", $key))
				if (preg_match("/^count_linkcode/i", $key))			
				{
					$ACT = $FNS->fetch_action_id('Linklist','jump_url');
					$link = $PREFS->core_ini['site_url'].'/'.$PREFS->core_ini['site_index']."?ACT=".$ACT."&urlid=".$row['url_id'];
					if ( $recentlyupdated )
					{
						$tagdata = $TMPL->swap_var_single(
															$key,
															$row['prepend_str'].
															"<a href='".
															$link.
															"'>".
															$row['url_title'].
															"</a>".
															$row['append_str'],
															$tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single(
															$key,
															"<a href='".
															$link.
															"'>".
															$row['url_title'].
															"</a>",
															$tagdata);
					}
				}
//				if (eregi("^updated_hours", $key))	
				if (preg_match("/^updated_hours/i", $key))
				{
					if ( $last_updated_time > 0 )
					{
						$tagdata = $TMPL->swap_var_single($key, $updated_hours, $tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
					}
				}
//				if (eregi("^relative_updated", $key))
				if (preg_match("/^relative_updated/i", $key))
				{
					if ( $row['updated'] > 0 )
					{
						$tagdata = $TMPL->swap_var_single($val, $LOC->format_timespan(time() - $row['updated']), $tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
					}
				}
//				if (eregi("^last_updated", $key))
				if (preg_match("/^last_updated/i", $key))
				{
					if ( $row['updated'] > 0 )
					{
						if ( preg_match_all("/{last_updated\s+format=[\"'](.*?)[\"']\}/s", $tagdata, $matches) )
						{
							$params = $LOC->fetch_date_params($matches[1][0]);
							foreach ($params as $dvar)
								$val = str_replace($dvar, $this->convert_timestamp($dvar, $row['updated'], TRUE), $val);
							$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);
						}
						else
						{
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						}
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
					}
				}
//				if (eregi("^url_added", $key))
				if (preg_match("/^url_added/i", $key))
				{
					if ( $row['url_added'] != 0 )
					{
						if ( preg_match_all("/{url_added\s+format=[\"'](.*?)[\"']\}/s", $tagdata, $matches) )
						{
							$params = $LOC->fetch_date_params($matches[1][0]);
							foreach ($params as $dvar)
								$val = str_replace($dvar, $this->convert_timestamp($dvar, $row['url_added'], TRUE), $val);
							$tagdata = $TMPL->swap_var_single($key, $val, $tagdata);
						}
						else
						{
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						}
					}
					else
					{
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
					}
				}
//				if (eregi("^xfn_linkcode", $key))
				if (preg_match("/^xfn_linkcode/i", $key))
				{
					$xfncode = $this->get_xfn_code($row);
					if ( $recentlyupdated )
					{
						$tagdata = $TMPL->swap_var_single(
															$key,
															$row['prepend_str'].
															"<a href=\"".
															$row['url'].
															"\" rel=\"".
															$xfncode.
															"\">".
															$row['url_title'].
															"</a>".
															$row['append_str'],
															$tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single(
															$key,
															"<a href=\"".
															$row['url'].
															"\" rel=\"".
															$xfncode.
															"\">".
															$row['url_title'].
															"</a>",
															$tagdata);
					}
				}
//				if (eregi("^countxfn_linkcode", $key))
				if (preg_match("/^countxfn_linkcode/", $key))
				{
					$ACT = $FNS->fetch_action_id('Linklist','jump_url');
					$link = $PREFS->core_ini['site_url'].'/'.$PREFS->core_ini['site_index']."?ACT=".$ACT."&urlid=".$row['url_id'];
					$xfncode = $this->get_xfn_code($row);
					if ( $recentlyupdated )
					{
						$tagdata = $TMPL->swap_var_single(
															$key,
															$row['prepend_str'].
															"<a href='".
															$link.
															"' rel='".
															$xfncode.
															"'>".
															$row['url_title'].
															"</a>".
															$row['append_str'],
															$tagdata);
					}
					else
					{
						$tagdata = $TMPL->swap_var_single(
															$key,
															"<a href='".
															$link.
															"' rel='".
															$xfncode.
															"'>".
															$row['url_title'].
															"</a>",
															$tagdata);
					}
				}
//				if (eregi("^xfncode", $key))
				if (preg_match("/^xfncode/i", $key))
				{
					$tagdata = $TMPL->swap_var_single(
														$key,
														$this->get_xfn_code($row),
														$tagdata);
				}
//				if (eregi("^added_by", $key))
				if (preg_match("/^added_by/i", $key))
				{
					$sql = "SELECT username,screen_name FROM exp_members WHERE member_id =".$row['url_added_by_id'];
					$query2 = $DB->query($sql);
					$added_by = $query2->row['screen_name'] ? $query2->row['screen_name'] : $query2->row['username'];
					$tagdata = $TMPL->swap_var_single(
														$key,
														$added_by,
														$tagdata);
				}
//				if (eregi("^added", $key))
				if (preg_match("/^added/i", $key))	
				{
					$tagdata = $TMPL->swap_var_single(
														$key,
														$LOC->decode_date($val, $row['url_added']),
														$tagdata);
				}
//				if (eregi("^updated", $key))
				if (preg_match("/^updated/i", $key))
				{
					$tagdata = $TMPL->swap_var_single(
														$key,
														$row['updated'],
														$tagdata);
				}
//				if (eregi("^prepend_str", $key))
				if (preg_match("/^prepend_str/i", $key))
				{
					if ( $recentlyupdated )
					{
						$tagdata = $TMPL->swap_var_single(
															$key,
															$row['prepend_str'],
															$tagdata);
					}	
					else
					{
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
					}
				}
//				if (eregi("^append_str", $key))
				if (preg_match("/^append_str/i", $key))
				{
					if ( $recentlyupdated )
					{
						$tagdata = $TMPL->swap_var_single(
															$key,
															$row['append_str'],
															$tagdata);
					}	
					else
					{
						$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
					}
				}
				// Added 6/4/05
//				if ( eregi("^linklist:url_title", $key) )
				if ( preg_match("/^linklist:url_title/i", $key) )
				{
					$tagdata = $TMPL->swap_var_single($val, $row['url_title'], $tagdata);
				}
//				if ( eregi("^linklist:url", $key) )
				if ( preg_match("/^linklist:url/i", $key) )
				
				{
					$tagdata = $TMPL->swap_var_single($val, $row['url'], $tagdata);
				}
//				if (eregi("^counturl",$key))
				if (preg_match("/^counturl/i",$key))			
				{
					$ACT = $FNS->fetch_action_id('Linklist','jump_url');
					$link = $PREFS->core_ini['site_url'].'/'.$PREFS->core_ini['site_index']."?ACT=".$ACT."&urlid=".$row['url_id'];
					$tagdata = $TMPL->swap_var_single($val, $link, $tagdata);
				}
                if (isset($row[$val]))
                {                    
                    $tagdata = $TMPL->swap_var_single($val, $row[$val], $tagdata);
                }
			}
			$results.=$tagdata;
		}
		if ( $TMPL->fetch_param('backspace') != '' )
		{
			$results = substr($results,0,-$TMPL->fetch_param('backspace'));
		}
		if ( ($TMPL->fetch_param('paginate') == 'bottom' || count($pagethru_chunk) > 0) && $TMPL->fetch_param('paginate') != 'top' )
		{
			$results .= $pagethruCode;
		}
		return $results;
	}
	
	function get_keywords()
	{
		global $TMPL, $DB, $FNS;
		
		$linklist_name = ( ! $TMPL->fetch_param('linklist') ) ? '' : $DB->escape_str(trim($TMPL->fetch_param('linklist')));
		$member_id = ( ! $TMPL->fetch_param('member_id') ) ? '' : $DB->escape_str(trim($TMPL->fetch_param('member_id')));
		$status = ( ! $TMPL->fetch_param('status') ) ? 'open' : $DB->escape_str(trim($TMPL->fetch_param('status')));
		$sort = ( ! $TMPL->fetch_param('sort') ) ? 'ASC' : $DB->escape_str(trim($TMPL->fetch_param('sort')));
		// first, we need to get the keywords that exist in the scope we are searching.

		$sql = 'SELECT keywords FROM exp_linklist_urls INNER JOIN exp_linklist ON exp_linklist_urls.linklist_id = exp_linklist.linklist_id WHERE ';
		$tmp = '';
		
		if ( $linklist_name != '' )
		{
			$str = $FNS->sql_andor_string($linklist_name, 'linklist_name', 'exp_linklist').' ';
			$tmp .= $str;
		}

		$tmp .= $FNS->sql_andor_string($status, 'url_status').' ';
		
		if ( $member_id != '' )
		{
			$tmp .= $FNS->sql_andor_string($member_id, 'member_id').' ';
		}

		if (substr($tmp, 0, 3) == 'AND')
		{
			$tmp = substr($tmp, 3);
		}
		
		$sql .= $tmp;

		$query = $DB->query($sql);
		
		$kwarray = array();
		
		foreach ( $query->result as $row )
		{
			$kwarray2 = explode(",",$row['keywords']);
			foreach ( $kwarray2 as $kw )
			{
				if ( ! in_array(trim($kw),$kwarray) )
				{
					if ( trim($kw) != '' )
					{
						$kwarray[] = trim($kw);
					}
				}
			}
		}
		if ( strtoupper($sort) == 'DESC' )
		{
			arsort($kwarray);
		}
		else
		{
			asort($kwarray);
		}
		
		return $kwarray;
	}
	
	// Keywords Function
	function keywords()
	{
		global $TMPL;
		if ( ! $kwarray = $this->get_keywords() )
		{
			return $TMPL->no_results();
		}
		
		if ( $TMPL->fetch_param('related') == 'on' )
		{
			global $TMPL, $FNS, $IN, $DB;
	
			// added 5/31
			$dynamic = ( ! $TMPL->fetch_param('dynamic') ) ? 'on' : $TMPL->fetch_param('dynamic');
			$dynamic = ( $dynamic == 'on' ) ? TRUE : FALSE;
			// This assumes that the keyword is segment 3 of the URL. This seems to make the most sense.
			// If the linklist is getting keywords dynamically, then we can only get one keyword at a time.
			if ( $dynamic )
			{
				$keywords = $DB->escape_str($IN->fetch_uri_segment('3'));
			}
			else
			{
				$keywords = '';
			}
			// If keywords are specified in the parameters, then use these instead of the query string from the URL.
			$keywords = ( ! $TMPL->fetch_param('keywords') ) ? $keywords : $DB->escape_str(trim($TMPL->fetch_param('keywords')));
			$keywords = str_replace("_"," ",$keywords);
			
			if ( $TMPL->fetch_param('keywords') == '' && $IN->fetch_uri_segment('3') == '' )
			{
				return;
			}
//			if ( eregi("\|",$keywords) )
			if ( preg_match("/\|/i",$keywords) )			
			{
				$keywords = explode("|",$keywords);
				foreach ( $keywords as $row )
				{
					$key = array_search($row,$kwarray);
					if ( $key !== FALSE )
					{
						unset($kwarray[$key]);
					}
				}
			}
			else
			{
				$key = array_search($keywords,$kwarray);
				if ( $key !== FALSE )
				{
					unset($kwarray[$key]);
				}
			}
		}
		
		return $this->parse_keywords($kwarray);
	}
	
	function parse_keywords($kwarray)
	{
		global $TMPL, $FNS;
		
		$results = '';

		$fontmin = ( ! $TMPL->fetch_param('fontmin') ) ? '' : trim($TMPL->fetch_param('fontmin'));
		$fontmax = ( ! $TMPL->fetch_param('fontmax') ) ? '' : trim($TMPL->fetch_param('fontmax'));
		
		foreach ( $kwarray as $row )
		{
			$tagdata = $TMPL->tagdata;
			// Parse single vars
			foreach ($TMPL->var_single as $key => $val)
			{
//				if (eregi('^path',$key))
				if (preg_match('/^path/i',$key))				
				{
					if ($FNS->extract_path($key) != '' AND $FNS->extract_path($key) != 'SITE_INDEX')
						{
							$tmp = str_replace(" ","_",trim($row));
							$path = $FNS->extract_path($key).'/'.$tmp;
						}
						else
						{
							$path = $row;
						}
					
						$tagdata = $TMPL->swap_var_single(
															$key, 
															$FNS->create_url($path, 1, 0), 
															$tagdata
														 );				
				}
//				if (eregi('^keyword',$key))
				if (preg_match('/^keyword/i',$key))			
                {                    
                    $tagdata = $TMPL->swap_var_single($val, trim($row), $tagdata);
                }
//                if (eregi('^font_size',$key))
				if (preg_match('/^font_size/i',$key))
                {
                	$tagdata = $TMPL->swap_var_single($val,$this->keyword_font_size($row,$fontmin,$fontmax), $tagdata);
                }
			}
			$results.=$tagdata;
		}
		if ( $TMPL->fetch_param('backspace') != '' )
		{
			$results = substr($results,0,-$TMPL->fetch_param('backspace'));
		}
		return $results;
	}
	
	function keyword_font_size($keyword,$min_size=10,$max_size=30)
	{
		global $DB;
		
		if ( trim($keyword) == '' )
		{
			return '';
		}
		$min_size = ( $min_size == '' ) ? 10 : $min_size;
		$max_size = ( $max_size == '' ) ? 30 : $max_size;
		$sql = "SELECT count(*) as count FROM exp_linklist_urls WHERE keywords LIKE '%".$keyword."%'";
		$query = $DB->query($sql);
		$count = $query->row['count'];
		$size = ($count / $max_size) * 100;
		$font_size = ( $size < $min_size ) ? $min_size : $size;
		$font_size = ( $size > $max_size ) ? $max_size : $font_size;
		return $font_size;
	}
	
	function get_xfn_code($xfnrow='')
	{
		if ( ! is_array($xfnrow) )
		{
			return;
		}
		$xfn = '';
		if ( $xfnrow['xfn_mine'] == 'y' )
		{
			$xfn .= 'me';
		}
		else
		{
			if ( $xfnrow['xfn_friendship'] != 'None')
			{
				$xfn .= $xfnrow['xfn_friendship'];
			}
			if ( $xfnrow['xfn_met'] == 'y' )
			{
				$xfn .= ' met';
			}
			if ( $xfnrow['xfn_coworker'] == 'y' )
			{
				$xfn .= ' co-worker';
			}
			if ( $xfnrow['xfn_colleague'] == 'y' )
			{
				$xfn .= ' colleague';
			}
			if ( $xfnrow['xfn_geographical'] != 'None' )
			{
				$xfn .= ' '.$xfnrow['xfn_geographical'];
			}
			if ( $xfnrow['xfn_family'] != 'None' )
			{
				$xfn .= ' '.$xfnrow['xfn_family'];
			}
			if ( $xfnrow['xfn_muse'] == 'y' )
			{
				$xfn .= ' muse';
			}
			if ( $xfnrow['xfn_crush'] == 'y' )
			{
				$xfn .= ' crush';
			}
			if ( $xfnrow['xfn_date'] == 'y' )
			{
				$xfn .= ' date';
			}
			if ( $xfnrow['xfn_sweetheart'] == 'y' )
			{
				$xfn .= ' sweetheart';
			}
		}
		return $xfn;
	}

	function build_query()
	{
		global $TMPL, $FNS, $DB, $IN;
		
		$linklist_name = ( ! $TMPL->fetch_param('linklist') ) ? '' : $DB->escape_str(trim($TMPL->fetch_param('linklist')));
		$member_id = ( ! $TMPL->fetch_param('member_id') ) ? '' : $DB->escape_str(trim($TMPL->fetch_param('member_id')));
		$orderby = ( ! $TMPL->fetch_param('orderby') ) ? 'linklist_id,disporder' : $DB->escape_str(trim($TMPL->fetch_param('orderby')));
		$sort = ( ! $TMPL->fetch_param('sort') ) ? 'ASC' : $DB->escape_str(trim($TMPL->fetch_param('sort')));
		$status = ( ! $TMPL->fetch_param('status') ) ? 'open' : $DB->escape_str(trim($TMPL->fetch_param('status'))); 
		$limit = ( ! $TMPL->fetch_param('limit') ) ? '' : $DB->escape_str(trim($TMPL->fetch_param('limit')));
		$offset = ( ! $TMPL->fetch_param('offset') ) ? '' : $DB->escape_str(trim($TMPL->fetch_param('offset')));
		
		// added 5/31
		$dynamic = ( ! $TMPL->fetch_param('dynamic') ) ? 'on' : $TMPL->fetch_param('dynamic');
		$dynamic = ( $dynamic == 'on' ) ? TRUE : FALSE;
		// This assumes that the keyword is segment 3 of the URL. This seems to make the most sense.
		// If the linklist is getting keywords dynamically, then we can only get one keyword at a time.
		if ( $dynamic )
		{
			if ( ! preg_match("(LP[0-9]*)",$IN->fetch_uri_segment('3')) )
			{
				$keywords = $DB->escape_str($IN->fetch_uri_segment('3'));
			}
			else
			{
				$keywords = '';
			}
			
		}
		else
		{
			$keywords = '';
		}
		
		// If keywords are specified in the parameters, then use these instead of the query string from the URL.
		$keywords = ( ! $TMPL->fetch_param('keywords') ) ? $keywords : $DB->escape_str(trim($TMPL->fetch_param('keywords')));
		// Replace underscores with spaces.
		$keywords = str_replace("_"," ",$keywords);
		
		$str = '';
		$sql = '';
		
		if ( $linklist_name != '' )
		{
			$str = $FNS->sql_andor_string($linklist_name, 'linklist_name', 'exp_linklist').' ';
			$sql .= $str;
		}
		

		$sql .= $FNS->sql_andor_string($status, 'url_status').' ';
		
		if ( $member_id != '' )
		{
			$sql .= $FNS->sql_andor_string($member_id, 'member_id').' ';
		}
		
		// Added 6/3
		$sql .= $this->keyword_query($keywords, 'keywords').' ';
		
		// Fix the orderby so that if the user sorts by last_updated, it actually sorts by updated. (misjudged this when making the db.)
//		$orderby = eregi_replace("last_updated","updated",$orderby);
		$orderby = preg_replace("/last_updated/i","updated",$orderby);		
		
		if ( $orderby == 'random' )
		{
			$sql .=	' ORDER BY rand() '.$sort;
		}
		else
		{
			$sql .=  ' ORDER BY '.$orderby.' '.$sort;
		}

		if ( $limit != '' )
		{
			// Current page from the last segment
			$currentpage = $IN->QSTR;
			
			// Clean up. if the URL contains a pagination link from normal pagination, clear that out.
			// if the last segment doesn't include the last page, then we just ignore it.
			if ( ! preg_match("(LP[0-9]*)",$currentpage,$matches) )
			{
				$currentpage = 1;
			}
			
			// Get rid of the LP in order to get the actual page number.
			if ( ! empty($matches) )
			{	$currentpage = preg_replace("(LP)","",$matches[0]);	}
			// insanity checks please.
			if ( ! is_numeric($currentpage) )
			{
				$currentpage = 1;
			}
			// Ok, we know what page, now we need to figure out what record to start at.
			// we add the limit + the currentpage to figure that out.
			$startrow=( $limit * ($currentpage - 1) );
			
			// Before we actually put the startrow in, let's determine if they set an offset. If so, then add it to the startrow.
			if ( $offset != '' ) { $startrow += $offset; }

			$sql .= ' LIMIT '.$startrow.','.$limit;
		}

		if (substr($sql, 0, 3) == 'AND')
		{
			$sql = substr($sql, 3);
		}

		return $sql;
	}
	
	// Added 6/3 
	// Since we need to query via LIKE or NOT LIKE instead of = or !=, I had to create this one which works similar.
	// Actually the sql_andor_string could be altered so that it could do the same, so you would do something like:
	// parameter="LIKE check1|check2" OR parameter="NOT LIKE check1|check2"
	function sql_like_str($value, $field)
	{
		global $DB;
		
		if ( $value == '' || $field == '')
		{
			return '';
		}
		
		$sql = '';
		
		if ( preg_match('/\|/',$value) )
		{
			$array = explode('|',$value);
//			if ( eregi('^not',$array[0]) )
			if ( preg_match('/^not/i',$array[0]) )		
			{
				$array[0] = substr($array[0],3);
				$sql .= "AND (";
				for ( $i=0; $i<count($array); $i++ )
				{
					if ( $i != 0 )
					{
						$sql .= "OR ";
					}
					$sql .= $field." NOT LIKE '%".$DB->escape_str(trim($array[$i]))."%' ";
				}
				$sql .= ")";
			}
			else
			{
				$sql .= "AND (";
				for ( $i=0; $i<count($array); $i++ )
				{
					if ( $i != 0 )
					{
						$sql .= "OR ";
					}
					$sql .= $field." LIKE '%".$DB->escape_str(trim($array[$i]))."%' ";
				}
				$sql .= ")";
			}
		}
		else
		{
//			if ( eregi('^not',$value) )
			if ( preg_match('/^not/i',$value) )
			{
				$value = trim(substr($value,3));
				$sql .= "AND ".$field." NOT LIKE '%".$DB->escape_str($value)."%' ";
			}
			else
			{
				$sql .= "AND ".$field." LIKE '%".$DB->escape_str($value)."%' ";
			}
		}
		
		return $sql;
	}
	
	function checkIfUpdated($rssurl,$lastDateModified,$timeout=30)
	{
		global $PREFS;
		$url = parse_url($rssurl);
		$host = $url['host'];
		$path = $url['path'];
		$lastDateModified = ( $lastDateModified == 0 ) ? strtotime("1/1/80") : $lastDateModified;
		// We should convert the date to something the server will most likely understand.
		// The updated time should be stored as UTC/GMT...so.... we just format it.
		$lastDateModified = date('D, d M Y H:i:s \G\M\T',$lastDateModified);
		$snoopy = new Snoopy2;
		$snoopy->rawheader['A-IM'] = "feed, diffe";
		$snoopy->rawheader['If-Modified-Since'] = $lastDateModified;
		$snoopy->read_timeout = $timeout;
		$snoopy->fetchtext($rssurl);
		$data = $snoopy->headers;
		if ( $snoopy->response_code != "304" )
		{
			foreach ( $data as $line )
			{
//				if ( eregi("^last-modified",$line) )
				if ( preg_match("/^last-modified/i",$line) )				
				{
					$array = explode(':',$line);
					$lastModified = substr( $line, strlen( $array[0] ) + 2 );
					break;
				}
			}
		}
		$lastModified = ( isset($lastModified) ? strtotime($lastModified) : '' );

		return $lastModified;
	}

	
	
	function checkUpdated()
	{
		global $DB, $TMPL, $FNS, $LOC;
		
		$limit = ( ! $TMPL->fetch_param('limit') ? '5' : $DB->escape_str($TMPL->fetch_param('limit')) );
		$linklist = ( ! $TMPL->fetch_param('linklist') ? '' : $DB->escape_str($TMPL->fetch_param('linklist')) );
		$status = ( ! $TMPL->fetch_param('status') ) ? 'open' : $DB->escape_str(trim($TMPL->fetch_param('status')));
		$silent = ( ! $TMPL->fetch_param('silent') ) ? 'true' : $TMPL->fetch_param('silent');
		$timeout = ( ! $TMPL->fetch_param('timeout') ) ? '30' : $TMPL->fetch_param('timeout');
		
		$silent = ( strtolower($silent) == 'false' ) ? FALSE : TRUE;
		$timeout = ( is_numeric($timeout) ) ? $timeout : 30;
		
		$return = "";
		
		// Set up the benchmark.
		
		$BM = new Benchmark();
		
		
		$sql = "SELECT url_id, url_rss, last_updated, updated
				FROM exp_linklist_urls
				INNER JOIN exp_linklist ON
				exp_linklist_urls.linklist_id = exp_linklist.linklist_id";

		$sql .= " WHERE ";
		$str = "";
		
		if ( $linklist != '' )
		{
			$str .= $FNS->sql_andor_string($linklist, 'linklist_name', 'exp_linklist').' ';
		}

		$str .= $FNS->sql_andor_string($status, 'url_status').' ';
		
		$str .= ' AND url_rss <> \'\'';
		
		if (substr($str, 0, 3) == 'AND')
		{
			$str = substr($str, 3);
		}
		
		$sql .= $str;

		// ORDER BY
		
		$sql .= ' ORDER BY last_updated';

		// LIMIT
		$sql .= ' LIMIT 0,'.$limit;

		$query = $DB->query($sql);
		
		$updated = time();
		
		$return .= "The time is now (Server Time): ".date("m/d/y H:i",$updated)."<br />";
		$count = 0;
		foreach ( $query->result as $row )
		{
			$timesincelastupdate = $updated - $row['last_updated'];
			if ( $timesincelastupdate > 3600 )
			{
				$count++;
				$return .= "<br />Start checking: ".$row['url_rss']."<br />";
				$BM->mark('start');
				if ( $lastmodified = $this->checkIfUpdated($row['url_rss'],$row['updated'],$timeout) )
				{
					$data = array (
								'updated' 		=>	$lastmodified,
								'last_updated'	=>	$updated
								);
					$DB->query($DB->update_string('exp_linklist_urls',$data,"url_id = ".$row['url_id']));
				}
				else
				{
					$data = array (
						'last_updated'	=>	$updated
					);
					$DB->query($DB->update_string('exp_linklist_urls',$data,"url_id = ".$row['url_id']));
				}
				$BM->mark('end');
				$return .= "Date Last Updated: ".gmdate("m/d/y H:i",$LOC->set_localized_time($lastmodified))."<br />";
				$return .= "Date Last checked (Server Time): ".date("m/d/y H:i",$row['last_updated'])."<br />";
				$return .= "Elapsed Time: ".$BM->elapsed('start', 'end');
				$return .= "<br />";
			}
		}
		
		$return .= $count." Sites Checked";

		if ( ! $silent )
		{
			return $return;
		}
		else
		{
			return;
		}
	
	}
	
	// There's something funky in the localization that in order to get the correct updated date and time (and be accurate)
	// it has to use gmdate, and not date. This is funky, so I'm just putting the convert_timestamp function here instead
	// with the gmdate instead of date.

    function convert_timestamp($which = '', $time = '', $localize = TRUE)
    {
        global $LANG, $SESS, $LOC;

        if ($which == '')
            return;
            
        if ($LOC->ctz == 0)
        {
            $LOC->ctz = $LOC->set_localized_timezone();
        }
            
        $time = ($localize == TRUE) ? $LOC->set_localized_time($time) : $time;     
        
        $may = ($which == '%F' AND date('F', $time) == 'May') ? date('F', $time).'_l' : date('F', $time);
  
  		switch ($which)
  		{
			case '%a': 	return $LANG->line(gmdate('a', $time)); // am/pm
				break;
			case '%A': 	return $LANG->line(gmdate('A', $time)); // AM/PM
				break;
			case '%B': 	return gmdate('B', $time);
				break;
			case '%d': 	return gmdate('d', $time);
				break;
			case '%D': 	return $LANG->line(gmdate('D', $time)); // Mon, Tues
				break;
			case '%F': 	return $LANG->line($may); // January, February
				break;
			case '%g': 	return gmdate('g', $time);
				break;
			case '%G': 	return gmdate('G', $time);
				break;
			case '%h': 	return gmdate('h', $time);
				break;
			case '%H': 	return gmdate('H', $time);
				break;
			case '%i': 	return gmdate('i', $time);
				break;
			case '%I': 	return gmdate('I', $time);
				break;
			case '%j': 	return gmdate('j', $time);
				break;
			case '%l': 	return $LANG->line(gmdate('l', $time)); // Monday, Tuesday
				break;
			case '%L': 	return gmdate('L', $time); 
				break;
			case '%m': 	return gmdate('m', $time);    
				break;
			case '%M': 	return $LANG->line(gmdate('M', $time)); // Jan, Feb
				break;
			case '%n': 	return gmdate('n', $time);
				break;
			case '%O': 	return gmdate('O', $time);
				break;
			case '%r': 	return $LANG->line(gmdate('D', $time)).gmdate(', d ', $time).$LANG->line(gmdate('M', $time)).gmdate(' Y H:i:s O', $time);
				break;
			case '%s': 	return gmdate('s', $time);
				break;
			case '%S': 	return gmdate('S', $time);
				break;
			case '%t': 	return gmdate('t', $time);
				break;
			case '%T': 	return $LOC->ctz;
				break;
			case '%U': 	return gmdate('U', $time);
				break;
			case '%w': 	return gmdate('w', $time);
				break;
			case '%W': 	return gmdate('W', $time);
				break;
			case '%y': 	return gmdate('y', $time);
				break;
			case '%Y': 	return gmdate('Y', $time);
				break;
			case '%Q':	return $LOC->zone_offset($SESS->userdata['timezone']);
				break;
			case '%z': 	return gmdate('z', $time);
				break;
			case '%Z':	return gmdate('Z', $time);
				break;  		
  		}
    }	
	
	function jump_url()
	{
		global $IN, $DB;
		// this is the action to jump to the next url.
		if ( $IN->GBL('urlid') == '' || ! is_numeric($IN->GBL('urlid')) )
		{
			exit('Invalid URL ID');
		}
		
		$url_id = $DB->escape_str($IN->GBL('urlid'));
		
		$sql = "SELECT url FROM exp_linklist_urls WHERE url_id = ".$url_id;
		$query = $DB->query($sql);
		
		if ( $query->num_rows == 0 )
		{
			exit('Invalid URL ID');
		}
		
		// Update the counter 
		$DB->query("UPDATE exp_linklist_urls SET clickthru = clickthru + 1 WHERE url_id = ".$url_id);
		
		header ("Location: ".$query->row['url']);
		exit();
		
	}
	
	function test()
	{
		global $IN, $FNS;
		

		echo $url;
	}
    
	// this function paginates and allows you to put your results on multiple pages
	function pagethru($recordcount=0,$chunk='',$maxrows=10,$maxspan=3)
	{
		global $FNS, $IN, $TMPL, $DB;
		// set up defaults
		$pageOne = "&laquo;";
		$pagePrev = "&lt;";
		$pageNext = "&gt;";
		$pageLast = "&raquo;";
		
		if ( $recordcount == 0 || $chunk == '' )
		{
			return FALSE;
		}
		
		// Current page from the last segment
		$currentpage = $IN->QSTR;
		// if the segment doesn't include the last page, then we just ignore it.
		if ( ! preg_match("(LP[0-9]*)",$currentpage,$matches) )
		{
			$currentpage = 1;
		}
		
		// Get rid of the LP in order to get the actual page number.
		if ( ! empty($matches) )
		{	$currentpage = preg_replace("(LP)","",$matches[0]);	}
		// insanity checks please.
		if ( ! is_numeric($currentpage) )
		{
			$currentpage = 1;
		}
		
		// Determine the total number of pages.
		$totalpages = ceil( $recordcount / $maxrows );
		
		// If the totalpages is 1, we don't need page thru
		if ( $totalpages < 2 )
		{
			return FALSE;
		}
		
		// if the current page is greater than the total pages, then set current page to 1.
		if ( $currentpage > $totalpages )
		{
			$currentpage = 1;
		}
		
		// Total span - how many pages in the span?		
		$totalpagespan = $totalpages > $maxspan ? $currentpage + $maxspan : $totalpages;
		
		// If the total page span is less than the totalpages, then set the count and max
		// pages appropriately.
		if ($totalpagespan < $totalpages)
		{
			$count = $currentpage;
			$maxpage=$totalpagespan;
		}
		elseif ($totalpagespan >= $totalpages)
		{
			$count = $totalpages-$maxspan;
			$maxpage=$totalpages;
		}
		else
		{
			$count=1;
			$maxpage=$totalpages;
		}

		if ($count <= 0)
		{
			$count=1;
		}

		// build the URL query string.
		$path = $IN->URI;
		// remove the LP segment
		$newpath = preg_replace("(/LP[0-9]*)","",$path);
		if ( $IN->QSTR == 'index' && $IN->URI == '' )
		{
			$sql = "SELECT group_name FROM exp_template_groups WHERE is_site_default = 'y'";
			$query = $DB->query($sql);
			$newpath = $query->row['group_name']."/index";
		}
		$newurl = $FNS->create_url($newpath, 1, 0);
		
		// start creating the page thru code.
		// jump to page one URL
		$firstpageCode = "<a href='".$newurl."LP1/'>".$pageOne."</a>&nbsp;";
		// Previous Page URL
		if ($currentpage > 1)
		{
			$prevpage=$currentpage-1;
			$prevpageCode = "<a href='".$newurl."LP".$prevpage."'>".$pagePrev."</a>&nbsp;";
		}
		else
		{
			$prevpageCode = "";
		}
		// the pages that get displayed. 1 2 3 4...etc
		$pageCode = '';
		while ($count <= $maxpage)
		{
			if ($count == $currentpage)
			{
				$pageCode .= "$count&nbsp;";
			}
			else
			{
				$pageCode .= "<a href='".$newurl."LP".$count."'>$count</a>&nbsp;";
			}
			$count++;
		}
		// Next Page URL
		if ($currentpage < $maxpage)
		{
			$nextpage=$currentpage +1;
			$nextpageCode = "&nbsp;<a href='".$newurl."LP".$nextpage."'>".$pageNext."</a>";
		}
		else
		{
			$nextpageCode = "";
		}
		// Last Page URL
		$lastpageCode = "&nbsp;<a href='".$newurl."LP".$totalpages."'>".$pageLast."</a>";
		
		$pagination_links = $firstpageCode.$prevpageCode.$pageCode.$nextpageCode.$lastpageCode;

		// Start parsing the pagination section and display it.
		
		$page_chunk = $chunk[0];
		
//		if (eregi(LD."current_page".RD,$page_chunk[0]))
		if (preg_match("/".LD."current_page".RD."/i",$page_chunk[0]))	
		{
			$temp = $TMPL->swap_var_single("current_page",$currentpage,$page_chunk[0]);
		}
//		if (eregi(LD."total_pages".RD,$page_chunk[0]))
		if (eregi("/".LD."total_pages".RD."/i",$page_chunk[0]))
		{
			$temp = $TMPL->swap_var_single("total_pages",$totalpages,$temp);
		}
//		if (eregi(LD."pagination_links".RD,$page_chunk[0]))
		if (eregi("/".LD."pagination_links".RD."/i",$page_chunk[0]))		
		{
			$temp = $TMPL->swap_var_single("pagination_links",$pagination_links,$temp);
		}
		
		return $temp;
	}

	function keyword_query($value, $field)
	{
		global $DB;
		
		if ( $value == '' || $field == '')
		{
			return '';
		}
		
		$sql = '';
		
		if ( preg_match('/\|/',$value) )
		{
			$array = explode('|',$value);
//			if ( eregi('^not',$array[0]) )
			if ( preg_match('/^not/i',$array[0]) )
			{
				$array[0] = substr($array[0],3);
				$sql .= "AND (";
				for ( $i=0; $i<count($array); $i++ )
				{
					if ( $i != 0 )
					{
						$sql .= "OR ";
					}
					$sql .= $field." NOT REGEXP '[[:<:]]".$DB->escape_str(trim($array[$i]))."[[:>:]]' ";
				}
				$sql .= ")";
			}
			else
			{
				$sql .= "AND (";
				for ( $i=0; $i<count($array); $i++ )
				{
					if ( $i != 0 )
					{
						$sql .= "OR ";
					}
					$sql .= $field." REGEXP '[[:<:]]".$DB->escape_str(trim($array[$i]))."[[:>:]]' ";
				}
				$sql .= ")";
			}
		}
		else
		{
//			if ( eregi('^not',$value) )
			if ( preg_match('/^not/',$value) )			
			{
				$value = trim(substr($value,3));
				$sql .= "AND ".$field." NOT REGEXP '[[:<:]]".$DB->escape_str($value)."[[:>:]]' ";
			}
			else
			{
				$sql .= "AND ".$field." REGEXP '[[:<:]]".$DB->escape_str($value)."[[:>:]]' ";
			}
		}
		
		return $sql;
	}
	
}



/*************************************************

Snoopy - the PHP net client
Author: Monte Ohrt <monte@ispi.net>
Copyright (c): 1999-2000 ispi, all rights reserved
Version: 1.01

 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

You may contact the author of Snoopy by e-mail at:
monte@ispi.net

Or, write to:
Monte Ohrt
CTO, ispi
237 S. 70th suite 220
Lincoln, NE 68510

The latest version of Snoopy can be obtained from:
http://snoopy.sourceforge.net/

*************************************************/

class Snoopy2
{
	/**** Public variables ****/
	
	/* user definable vars */

	var $host			=	"www.php.net";		// host name we are connecting to
	var $port			=	80;					// port we are connecting to
	var $proxy_host		=	"";					// proxy host to use
	var $proxy_port		=	"";					// proxy port to use
	var $proxy_user		=	"";					// proxy user to use
	var $proxy_pass		=	"";					// proxy password to use
	
	var $agent			=	"Snoopy v1.2.3";	// agent we masquerade as
	var	$referer		=	"";					// referer info to pass
	var $cookies		=	array();			// array of cookies to pass
												// $cookies["username"]="joe";
	var	$rawheaders		=	array();			// array of raw headers to send
												// $rawheaders["Content-type"]="text/html";

	var $maxredirs		=	5;					// http redirection depth maximum. 0 = disallow
	var $lastredirectaddr	=	"";				// contains address of last redirected address
	var	$offsiteok		=	true;				// allows redirection off-site
	var $maxframes		=	0;					// frame content depth maximum. 0 = disallow
	var $expandlinks	=	true;				// expand links to fully qualified URLs.
												// this only applies to fetchlinks()
												// submitlinks(), and submittext()
	var $passcookies	=	true;				// pass set cookies back through redirects
												// NOTE: this currently does not respect
												// dates, domains or paths.
	
	var	$user			=	"";					// user for http authentication
	var	$pass			=	"";					// password for http authentication
	
	// http accept types
	var $accept			=	"image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*";
	
	var $results		=	"";					// where the content is put
		
	var $error			=	"";					// error messages sent here
	var	$response_code	=	"";					// response code returned from server
	var	$headers		=	array();			// headers returned from server sent here
	var	$maxlength		=	500000;				// max return data length (body)
	var $read_timeout	=	0;					// timeout on read operations, in seconds
												// supported only since PHP 4 Beta 4
												// set to 0 to disallow timeouts
	var $timed_out		=	false;				// if a read operation timed out
	var	$status			=	0;					// http request status

	var $temp_dir		=	"/tmp";				// temporary directory that the webserver
												// has permission to write to.
												// under Windows, this should be C:\temp

	var	$curl_path		=	"/usr/local/bin/curl";
												// Snoopy will use cURL for fetching
												// SSL content if a full system path to
												// the cURL binary is supplied here.
												// set to false if you do not have
												// cURL installed. See http://curl.haxx.se
												// for details on installing cURL.
												// Snoopy does *not* use the cURL
												// library functions built into php,
												// as these functions are not stable
												// as of this Snoopy release.
	
	/**** Private variables ****/	
	
	var	$_maxlinelen	=	4096;				// max line length (headers)
	
	var $_httpmethod	=	"GET";				// default http request method
	var $_httpversion	=	"HTTP/1.0";			// default http request version
	var $_submit_method	=	"POST";				// default submit method
	var $_submit_type	=	"application/x-www-form-urlencoded";	// default submit type
	var $_mime_boundary	=   "";					// MIME boundary for multipart/form-data submit type
	var $_redirectaddr	=	false;				// will be set if page fetched is a redirect
	var $_redirectdepth	=	0;					// increments on an http redirect
	var $_frameurls		= 	array();			// frame src urls
	var $_framedepth	=	0;					// increments on frame depth
	
	var $_isproxy		=	false;				// set if using a proxy server
	var $_fp_timeout	=	30;					// timeout for socket connection

/*======================================================================*\
	Function:	fetch
	Purpose:	fetch the contents of a web page
				(and possibly other protocols in the
				future like ftp, nntp, gopher, etc.)
	Input:		$URI	the location of the page to fetch
	Output:		$this->results	the output text from the fetch
\*======================================================================*/

	function fetch($URI)
	{
	
		//preg_match("|^([^:]+)://([^:/]+)(:[\d]+)*(.*)|",$URI,$URI_PARTS);
		$URI_PARTS = parse_url($URI);
		if (!empty($URI_PARTS["user"]))
			$this->user = $URI_PARTS["user"];
		if (!empty($URI_PARTS["pass"]))
			$this->pass = $URI_PARTS["pass"];
		if (empty($URI_PARTS["query"]))
			$URI_PARTS["query"] = '';
		if (empty($URI_PARTS["path"]))
			$URI_PARTS["path"] = '';
				
		switch(strtolower($URI_PARTS["scheme"]))
		{
			case "http":
				$this->host = $URI_PARTS["host"];
				if(!empty($URI_PARTS["port"]))
					$this->port = $URI_PARTS["port"];
				if($this->_connect($fp))
				{
					if($this->_isproxy)
					{
						// using proxy, send entire URI
						$this->_httprequest($URI,$fp,$URI,$this->_httpmethod);
					}
					else
					{
						$path = $URI_PARTS["path"].($URI_PARTS["query"] ? "?".$URI_PARTS["query"] : "");
						// no proxy, send only the path
						$this->_httprequest($path, $fp, $URI, $this->_httpmethod);
					}
					
					$this->_disconnect($fp);

					if($this->_redirectaddr)
					{
						/* url was redirected, check if we've hit the max depth */
						if($this->maxredirs > $this->_redirectdepth)
						{
							// only follow redirect if it's on this site, or offsiteok is true
							if(preg_match("|^http://".preg_quote($this->host)."|i",$this->_redirectaddr) || $this->offsiteok)
							{
								/* follow the redirect */
								$this->_redirectdepth++;
								$this->lastredirectaddr=$this->_redirectaddr;
								$this->fetch($this->_redirectaddr);
							}
						}
					}

					if($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0)
					{
						$frameurls = $this->_frameurls;
						$this->_frameurls = array();
						
						while(list(,$frameurl) = each($frameurls))
						{
							if($this->_framedepth < $this->maxframes)
							{
								$this->fetch($frameurl);
								$this->_framedepth++;
							}
							else
								break;
						}
					}					
				}
				else
				{
					return false;
				}
				return true;					
				break;
			case "https":
				if(!$this->curl_path)
					return false;
				if(function_exists("is_executable"))
				    if (!is_executable($this->curl_path))
				        return false;
				$this->host = $URI_PARTS["host"];
				if(!empty($URI_PARTS["port"]))
					$this->port = $URI_PARTS["port"];
				if($this->_isproxy)
				{
					// using proxy, send entire URI
					$this->_httpsrequest($URI,$URI,$this->_httpmethod);
				}
				else
				{
					$path = $URI_PARTS["path"].($URI_PARTS["query"] ? "?".$URI_PARTS["query"] : "");
					// no proxy, send only the path
					$this->_httpsrequest($path, $URI, $this->_httpmethod);
				}

				if($this->_redirectaddr)
				{
					/* url was redirected, check if we've hit the max depth */
					if($this->maxredirs > $this->_redirectdepth)
					{
						// only follow redirect if it's on this site, or offsiteok is true
						if(preg_match("|^http://".preg_quote($this->host)."|i",$this->_redirectaddr) || $this->offsiteok)
						{
							/* follow the redirect */
							$this->_redirectdepth++;
							$this->lastredirectaddr=$this->_redirectaddr;
							$this->fetch($this->_redirectaddr);
						}
					}
				}

				if($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0)
				{
					$frameurls = $this->_frameurls;
					$this->_frameurls = array();

					while(list(,$frameurl) = each($frameurls))
					{
						if($this->_framedepth < $this->maxframes)
						{
							$this->fetch($frameurl);
							$this->_framedepth++;
						}
						else
							break;
					}
				}					
				return true;					
				break;
			default:
				// not a valid protocol
				$this->error	=	'Invalid protocol "'.$URI_PARTS["scheme"].'"\n';
				return false;
				break;
		}		
		return true;
	}

/*======================================================================*\
	Function:	submit
	Purpose:	submit an http form
	Input:		$URI	the location to post the data
				$formvars	the formvars to use.
					format: $formvars["var"] = "val";
				$formfiles  an array of files to submit
					format: $formfiles["var"] = "/dir/filename.ext";
	Output:		$this->results	the text output from the post
\*======================================================================*/

	function submit($URI, $formvars="", $formfiles="")
	{
		unset($postdata);
		
		$postdata = $this->_prepare_post_body($formvars, $formfiles);
			
		$URI_PARTS = parse_url($URI);
		if (!empty($URI_PARTS["user"]))
			$this->user = $URI_PARTS["user"];
		if (!empty($URI_PARTS["pass"]))
			$this->pass = $URI_PARTS["pass"];
		if (empty($URI_PARTS["query"]))
			$URI_PARTS["query"] = '';
		if (empty($URI_PARTS["path"]))
			$URI_PARTS["path"] = '';

		switch(strtolower($URI_PARTS["scheme"]))
		{
			case "http":
				$this->host = $URI_PARTS["host"];
				if(!empty($URI_PARTS["port"]))
					$this->port = $URI_PARTS["port"];
				if($this->_connect($fp))
				{
					if($this->_isproxy)
					{
						// using proxy, send entire URI
						$this->_httprequest($URI,$fp,$URI,$this->_submit_method,$this->_submit_type,$postdata);
					}
					else
					{
						$path = $URI_PARTS["path"].($URI_PARTS["query"] ? "?".$URI_PARTS["query"] : "");
						// no proxy, send only the path
						$this->_httprequest($path, $fp, $URI, $this->_submit_method, $this->_submit_type, $postdata);
					}
					
					$this->_disconnect($fp);

					if($this->_redirectaddr)
					{
						/* url was redirected, check if we've hit the max depth */
						if($this->maxredirs > $this->_redirectdepth)
						{						
							if(!preg_match("|^".$URI_PARTS["scheme"]."://|", $this->_redirectaddr))
								$this->_redirectaddr = $this->_expandlinks($this->_redirectaddr,$URI_PARTS["scheme"]."://".$URI_PARTS["host"]);						
							
							// only follow redirect if it's on this site, or offsiteok is true
							if(preg_match("|^http://".preg_quote($this->host)."|i",$this->_redirectaddr) || $this->offsiteok)
							{
								/* follow the redirect */
								$this->_redirectdepth++;
								$this->lastredirectaddr=$this->_redirectaddr;
								if( strpos( $this->_redirectaddr, "?" ) > 0 )
									$this->fetch($this->_redirectaddr); // the redirect has changed the request method from post to get
								else
									$this->submit($this->_redirectaddr,$formvars, $formfiles);
							}
						}
					}

					if($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0)
					{
						$frameurls = $this->_frameurls;
						$this->_frameurls = array();
						
						while(list(,$frameurl) = each($frameurls))
						{														
							if($this->_framedepth < $this->maxframes)
							{
								$this->fetch($frameurl);
								$this->_framedepth++;
							}
							else
								break;
						}
					}					
					
				}
				else
				{
					return false;
				}
				return true;					
				break;
			case "https":
				if(!$this->curl_path)
					return false;
				if(function_exists("is_executable"))
				    if (!is_executable($this->curl_path))
				        return false;
				$this->host = $URI_PARTS["host"];
				if(!empty($URI_PARTS["port"]))
					$this->port = $URI_PARTS["port"];
				if($this->_isproxy)
				{
					// using proxy, send entire URI
					$this->_httpsrequest($URI, $URI, $this->_submit_method, $this->_submit_type, $postdata);
				}
				else
				{
					$path = $URI_PARTS["path"].($URI_PARTS["query"] ? "?".$URI_PARTS["query"] : "");
					// no proxy, send only the path
					$this->_httpsrequest($path, $URI, $this->_submit_method, $this->_submit_type, $postdata);
				}

				if($this->_redirectaddr)
				{
					/* url was redirected, check if we've hit the max depth */
					if($this->maxredirs > $this->_redirectdepth)
					{						
						if(!preg_match("|^".$URI_PARTS["scheme"]."://|", $this->_redirectaddr))
							$this->_redirectaddr = $this->_expandlinks($this->_redirectaddr,$URI_PARTS["scheme"]."://".$URI_PARTS["host"]);						

						// only follow redirect if it's on this site, or offsiteok is true
						if(preg_match("|^http://".preg_quote($this->host)."|i",$this->_redirectaddr) || $this->offsiteok)
						{
							/* follow the redirect */
							$this->_redirectdepth++;
							$this->lastredirectaddr=$this->_redirectaddr;
							if( strpos( $this->_redirectaddr, "?" ) > 0 )
								$this->fetch($this->_redirectaddr); // the redirect has changed the request method from post to get
							else
								$this->submit($this->_redirectaddr,$formvars, $formfiles);
						}
					}
				}

				if($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0)
				{
					$frameurls = $this->_frameurls;
					$this->_frameurls = array();

					while(list(,$frameurl) = each($frameurls))
					{														
						if($this->_framedepth < $this->maxframes)
						{
							$this->fetch($frameurl);
							$this->_framedepth++;
						}
						else
							break;
					}
				}					
				return true;					
				break;
				
			default:
				// not a valid protocol
				$this->error	=	'Invalid protocol "'.$URI_PARTS["scheme"].'"\n';
				return false;
				break;
		}		
		return true;
	}

/*======================================================================*\
	Function:	fetchlinks
	Purpose:	fetch the links from a web page
	Input:		$URI	where you are fetching from
	Output:		$this->results	an array of the URLs
\*======================================================================*/

	function fetchlinks($URI)
	{
		if ($this->fetch($URI))
		{			
			if($this->lastredirectaddr)
				$URI = $this->lastredirectaddr;
			if(is_array($this->results))
			{
				for($x=0;$x<count($this->results);$x++)
					$this->results[$x] = $this->_striplinks($this->results[$x]);
			}
			else
				$this->results = $this->_striplinks($this->results);

			if($this->expandlinks)
				$this->results = $this->_expandlinks($this->results, $URI);
			return true;
		}
		else
			return false;
	}

/*======================================================================*\
	Function:	fetchform
	Purpose:	fetch the form elements from a web page
	Input:		$URI	where you are fetching from
	Output:		$this->results	the resulting html form
\*======================================================================*/

	function fetchform($URI)
	{
		
		if ($this->fetch($URI))
		{			

			if(is_array($this->results))
			{
				for($x=0;$x<count($this->results);$x++)
					$this->results[$x] = $this->_stripform($this->results[$x]);
			}
			else
				$this->results = $this->_stripform($this->results);
			
			return true;
		}
		else
			return false;
	}
	
	
/*======================================================================*\
	Function:	fetchtext
	Purpose:	fetch the text from a web page, stripping the links
	Input:		$URI	where you are fetching from
	Output:		$this->results	the text from the web page
\*======================================================================*/

	function fetchtext($URI)
	{
		if($this->fetch($URI))
		{			
			if(is_array($this->results))
			{
				for($x=0;$x<count($this->results);$x++)
					$this->results[$x] = $this->_striptext($this->results[$x]);
			}
			else
				$this->results = $this->_striptext($this->results);
			return true;
		}
		else
			return false;
	}

/*======================================================================*\
	Function:	submitlinks
	Purpose:	grab links from a form submission
	Input:		$URI	where you are submitting from
	Output:		$this->results	an array of the links from the post
\*======================================================================*/

	function submitlinks($URI, $formvars="", $formfiles="")
	{
		if($this->submit($URI,$formvars, $formfiles))
		{			
			if($this->lastredirectaddr)
				$URI = $this->lastredirectaddr;
			if(is_array($this->results))
			{
				for($x=0;$x<count($this->results);$x++)
				{
					$this->results[$x] = $this->_striplinks($this->results[$x]);
					if($this->expandlinks)
						$this->results[$x] = $this->_expandlinks($this->results[$x],$URI);
				}
			}
			else
			{
				$this->results = $this->_striplinks($this->results);
				if($this->expandlinks)
					$this->results = $this->_expandlinks($this->results,$URI);
			}
			return true;
		}
		else
			return false;
	}

/*======================================================================*\
	Function:	submittext
	Purpose:	grab text from a form submission
	Input:		$URI	where you are submitting from
	Output:		$this->results	the text from the web page
\*======================================================================*/

	function submittext($URI, $formvars = "", $formfiles = "")
	{
		if($this->submit($URI,$formvars, $formfiles))
		{			
			if($this->lastredirectaddr)
				$URI = $this->lastredirectaddr;
			if(is_array($this->results))
			{
				for($x=0;$x<count($this->results);$x++)
				{
					$this->results[$x] = $this->_striptext($this->results[$x]);
					if($this->expandlinks)
						$this->results[$x] = $this->_expandlinks($this->results[$x],$URI);
				}
			}
			else
			{
				$this->results = $this->_striptext($this->results);
				if($this->expandlinks)
					$this->results = $this->_expandlinks($this->results,$URI);
			}
			return true;
		}
		else
			return false;
	}

	

/*======================================================================*\
	Function:	set_submit_multipart
	Purpose:	Set the form submission content type to
				multipart/form-data
\*======================================================================*/
	function set_submit_multipart()
	{
		$this->_submit_type = "multipart/form-data";
	}

	
/*======================================================================*\
	Function:	set_submit_normal
	Purpose:	Set the form submission content type to
				application/x-www-form-urlencoded
\*======================================================================*/
	function set_submit_normal()
	{
		$this->_submit_type = "application/x-www-form-urlencoded";
	}

	
	

/*======================================================================*\
	Private functions
\*======================================================================*/
	
	
/*======================================================================*\
	Function:	_striplinks
	Purpose:	strip the hyperlinks from an html document
	Input:		$document	document to strip.
	Output:		$match		an array of the links
\*======================================================================*/

	function _striplinks($document)
	{	
		preg_match_all("'<\s*a\s.*?href\s*=\s*			# find <a href=
						([\"\'])?					# find single or double quote
						(?(1) (.*?)\\1 | ([^\s\>]+))		# if quote found, match up to next matching
													# quote, otherwise match up to next space
						'isx",$document,$links);
						

		// catenate the non-empty matches from the conditional subpattern

		while(list($key,$val) = each($links[2]))
		{
			if(!empty($val))
				$match[] = $val;
		}				
		
		while(list($key,$val) = each($links[3]))
		{
			if(!empty($val))
				$match[] = $val;
		}		
		
		// return the links
		return $match;
	}

/*======================================================================*\
	Function:	_stripform
	Purpose:	strip the form elements from an html document
	Input:		$document	document to strip.
	Output:		$match		an array of the links
\*======================================================================*/

	function _stripform($document)
	{	
		preg_match_all("'<\/?(FORM|INPUT|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))'Usi",$document,$elements);
		
		// catenate the matches
		$match = implode("\r\n",$elements[0]);
				
		// return the links
		return $match;
	}

	
	
/*======================================================================*\
	Function:	_striptext
	Purpose:	strip the text from an html document
	Input:		$document	document to strip.
	Output:		$text		the resulting text
\*======================================================================*/

	function _striptext($document)
	{
		
		// I didn't use preg eval (//e) since that is only available in PHP 4.0.
		// so, list your entities one by one here. I included some of the
		// more common ones.
								
		$search = array("'<script[^>]*?>.*?</script>'si",	// strip out javascript
						"'<[\/\!]*?[^<>]*?>'si",			// strip out html tags
						"'([\r\n])[\s]+'",					// strip out white space
						"'&(quot|#34|#034|#x22);'i",		// replace html entities
						"'&(amp|#38|#038|#x26);'i",			// added hexadecimal values
						"'&(lt|#60|#060|#x3c);'i",
						"'&(gt|#62|#062|#x3e);'i",
						"'&(nbsp|#160|#xa0);'i",
						"'&(iexcl|#161);'i",
						"'&(cent|#162);'i",
						"'&(pound|#163);'i",
						"'&(copy|#169);'i",
						"'&(reg|#174);'i",
						"'&(deg|#176);'i",
						"'&(#39|#039|#x27);'",
						"'&(euro|#8364);'i",				// europe
						"'&a(uml|UML);'",					// german
						"'&o(uml|UML);'",
						"'&u(uml|UML);'",
						"'&A(uml|UML);'",
						"'&O(uml|UML);'",
						"'&U(uml|UML);'",
						"'&szlig;'i",
						);
		$replace = array(	"",
							"",
							"\\1",
							"\"",
							"&",
							"<",
							">",
							" ",
							chr(161),
							chr(162),
							chr(163),
							chr(169),
							chr(174),
							chr(176),
							chr(39),
							chr(128),
							"ä",
							"ö",
							"ü",
							"Ä",
							"Ö",
							"Ü",
							"ß",
						);
					
		$text = preg_replace($search,$replace,$document);
								
		return $text;
	}

/*======================================================================*\
	Function:	_expandlinks
	Purpose:	expand each link into a fully qualified URL
	Input:		$links			the links to qualify
				$URI			the full URI to get the base from
	Output:		$expandedLinks	the expanded links
\*======================================================================*/

	function _expandlinks($links,$URI)
	{
		
		preg_match("/^[^\?]+/",$URI,$match);

		$match = preg_replace("|/[^\/\.]+\.[^\/\.]+$|","",$match[0]);
		$match = preg_replace("|/$|","",$match);
		$match_part = parse_url($match);
		$match_root =
		$match_part["scheme"]."://".$match_part["host"];
				
		$search = array( 	"|^http://".preg_quote($this->host)."|i",
							"|^(\/)|i",
							"|^(?!http://)(?!mailto:)|i",
							"|/\./|",
							"|/[^\/]+/\.\./|"
						);
						
		$replace = array(	"",
							$match_root."/",
							$match."/",
							"/",
							"/"
						);			
				
		$expandedLinks = preg_replace($search,$replace,$links);

		return $expandedLinks;
	}

/*======================================================================*\
	Function:	_httprequest
	Purpose:	go get the http data from the server
	Input:		$url		the url to fetch
				$fp			the current open file pointer
				$URI		the full URI
				$body		body contents to send if any (POST)
	Output:		
\*======================================================================*/
	
	function _httprequest($url,$fp,$URI,$http_method,$content_type="",$body="")
	{
		$cookie_headers = '';
		if($this->passcookies && $this->_redirectaddr)
			$this->setcookies();
			
		$URI_PARTS = parse_url($URI);
		if(empty($url))
			$url = "/";
		$headers = $http_method." ".$url." ".$this->_httpversion."\r\n";		
		if(!empty($this->agent))
			$headers .= "User-Agent: ".$this->agent."\r\n";
		if(!empty($this->host) && !isset($this->rawheaders['Host'])) {
			$headers .= "Host: ".$this->host;
			if(!empty($this->port))
				$headers .= ":".$this->port;
			$headers .= "\r\n";
		}
		if(!empty($this->accept))
			$headers .= "Accept: ".$this->accept."\r\n";
		if(!empty($this->referer))
			$headers .= "Referer: ".$this->referer."\r\n";
		if(!empty($this->cookies))
		{			
			if(!is_array($this->cookies))
				$this->cookies = (array)$this->cookies;
	
			reset($this->cookies);
			if ( count($this->cookies) > 0 ) {
				$cookie_headers .= 'Cookie: ';
				foreach ( $this->cookies as $cookieKey => $cookieVal ) {
				$cookie_headers .= $cookieKey."=".urlencode($cookieVal)."; ";
				}
				$headers .= substr($cookie_headers,0,-2) . "\r\n";
			} 
		}
		if(!empty($this->rawheaders))
		{
			if(!is_array($this->rawheaders))
				$this->rawheaders = (array)$this->rawheaders;
			while(list($headerKey,$headerVal) = each($this->rawheaders))
				$headers .= $headerKey.": ".$headerVal."\r\n";
		}
		if(!empty($content_type)) {
			$headers .= "Content-type: $content_type";
			if ($content_type == "multipart/form-data")
				$headers .= "; boundary=".$this->_mime_boundary;
			$headers .= "\r\n";
		}
		if(!empty($body))	
			$headers .= "Content-length: ".strlen($body)."\r\n";
		if(!empty($this->user) || !empty($this->pass))	
			$headers .= "Authorization: Basic ".base64_encode($this->user.":".$this->pass)."\r\n";
		
		//add proxy auth headers
		if(!empty($this->proxy_user))	
			$headers .= 'Proxy-Authorization: ' . 'Basic ' . base64_encode($this->proxy_user . ':' . $this->proxy_pass)."\r\n";


		$headers .= "\r\n";
		
		// set the read timeout if needed
		if ($this->read_timeout > 0)
			socket_set_timeout($fp, $this->read_timeout);
		$this->timed_out = false;
		
		fwrite($fp,$headers.$body,strlen($headers.$body));
		
		$this->_redirectaddr = false;
		unset($this->headers);
						
		while($currentHeader = fgets($fp,$this->_maxlinelen))
		{
			if ($this->read_timeout > 0 && $this->_check_timeout($fp))
			{
				$this->status=-100;
				return false;
			}
				
			if($currentHeader == "\r\n")
				break;
						
			// if a header begins with Location: or URI:, set the redirect
			if(preg_match("/^(Location:|URI:)/i",$currentHeader))
			{
				// get URL portion of the redirect
				preg_match("/^(Location:|URI:)[ ]+(.*)/i",chop($currentHeader),$matches);
				// look for :// in the Location header to see if hostname is included
				if(!preg_match("|\:\/\/|",$matches[2]))
				{
					// no host in the path, so prepend
					$this->_redirectaddr = $URI_PARTS["scheme"]."://".$this->host.":".$this->port;
					// eliminate double slash
					if(!preg_match("|^/|",$matches[2]))
							$this->_redirectaddr .= "/".$matches[2];
					else
							$this->_redirectaddr .= $matches[2];
				}
				else
					$this->_redirectaddr = $matches[2];
			}
		
			if(preg_match("|^HTTP/|",$currentHeader))
			{
                if(preg_match("|^HTTP/[^\s]*\s(.*?)\s|",$currentHeader, $status))
				{
					$this->status= $status[1];
                }				
				$this->response_code = $currentHeader;
			}
				
			$this->headers[] = $currentHeader;
		}

		$results = '';
		do {
    		$_data = fread($fp, $this->maxlength);
    		if (strlen($_data) == 0) {
        		break;
    		}
    		$results .= $_data;
		} while(true);

		if ($this->read_timeout > 0 && $this->_check_timeout($fp))
		{
			$this->status=-100;
			return false;
		}
		
		// check if there is a a redirect meta tag
		
		if(preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",$results,$match))

		{
			$this->_redirectaddr = $this->_expandlinks($match[1],$URI);	
		}

		// have we hit our frame depth and is there frame src to fetch?
		if(($this->_framedepth < $this->maxframes) && preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i",$results,$match))
		{
			$this->results[] = $results;
			for($x=0; $x<count($match[1]); $x++)
				$this->_frameurls[] = $this->_expandlinks($match[1][$x],$URI_PARTS["scheme"]."://".$this->host);
		}
		// have we already fetched framed content?
		elseif(is_array($this->results))
			$this->results[] = $results;
		// no framed content
		else
			$this->results = $results;
		
		return true;
	}

/*======================================================================*\
	Function:	_httpsrequest
	Purpose:	go get the https data from the server using curl
	Input:		$url		the url to fetch
				$URI		the full URI
				$body		body contents to send if any (POST)
	Output:		
\*======================================================================*/
	
	function _httpsrequest($url,$URI,$http_method,$content_type="",$body="")
	{
		if($this->passcookies && $this->_redirectaddr)
			$this->setcookies();

		$headers = array();		
					
		$URI_PARTS = parse_url($URI);
		if(empty($url))
			$url = "/";
		// GET ... header not needed for curl
		//$headers[] = $http_method." ".$url." ".$this->_httpversion;		
		if(!empty($this->agent))
			$headers[] = "User-Agent: ".$this->agent;
		if(!empty($this->host))
			if(!empty($this->port))
				$headers[] = "Host: ".$this->host.":".$this->port;
			else
				$headers[] = "Host: ".$this->host;
		if(!empty($this->accept))
			$headers[] = "Accept: ".$this->accept;
		if(!empty($this->referer))
			$headers[] = "Referer: ".$this->referer;
		if(!empty($this->cookies))
		{			
			if(!is_array($this->cookies))
				$this->cookies = (array)$this->cookies;
	
			reset($this->cookies);
			if ( count($this->cookies) > 0 ) {
				$cookie_str = 'Cookie: ';
				foreach ( $this->cookies as $cookieKey => $cookieVal ) {
				$cookie_str .= $cookieKey."=".urlencode($cookieVal)."; ";
				}
				$headers[] = substr($cookie_str,0,-2);
			}
		}
		if(!empty($this->rawheaders))
		{
			if(!is_array($this->rawheaders))
				$this->rawheaders = (array)$this->rawheaders;
			while(list($headerKey,$headerVal) = each($this->rawheaders))
				$headers[] = $headerKey.": ".$headerVal;
		}
		if(!empty($content_type)) {
			if ($content_type == "multipart/form-data")
				$headers[] = "Content-type: $content_type; boundary=".$this->_mime_boundary;
			else
				$headers[] = "Content-type: $content_type";
		}
		if(!empty($body))	
			$headers[] = "Content-length: ".strlen($body);
		if(!empty($this->user) || !empty($this->pass))	
			$headers[] = "Authorization: BASIC ".base64_encode($this->user.":".$this->pass);
			
		for($curr_header = 0; $curr_header < count($headers); $curr_header++) {
			$safer_header = strtr( $headers[$curr_header], "\"", " " );
			$cmdline_params .= " -H \"".$safer_header."\"";
		}
		
		if(!empty($body))
			$cmdline_params .= " -d \"$body\"";
		
		if($this->read_timeout > 0)
			$cmdline_params .= " -m ".$this->read_timeout;
		
		$headerfile = tempnam($temp_dir, "sno");

		$safer_URI = strtr( $URI, "\"", " " ); // strip quotes from the URI to avoid shell access
		exec($this->curl_path." -D \"$headerfile\"".$cmdline_params." \"".$safer_URI."\"",$results,$return);
		
		if($return)
		{
			$this->error = "Error: cURL could not retrieve the document, error $return.";
			return false;
		}
			
			
		$results = implode("\r\n",$results);
		
		$result_headers = file("$headerfile");
						
		$this->_redirectaddr = false;
		unset($this->headers);
						
		for($currentHeader = 0; $currentHeader < count($result_headers); $currentHeader++)
		{
			
			// if a header begins with Location: or URI:, set the redirect
			if(preg_match("/^(Location: |URI: )/i",$result_headers[$currentHeader]))
			{
				// get URL portion of the redirect
				preg_match("/^(Location: |URI:)\s+(.*)/",chop($result_headers[$currentHeader]),$matches);
				// look for :// in the Location header to see if hostname is included
				if(!preg_match("|\:\/\/|",$matches[2]))
				{
					// no host in the path, so prepend
					$this->_redirectaddr = $URI_PARTS["scheme"]."://".$this->host.":".$this->port;
					// eliminate double slash
					if(!preg_match("|^/|",$matches[2]))
							$this->_redirectaddr .= "/".$matches[2];
					else
							$this->_redirectaddr .= $matches[2];
				}
				else
					$this->_redirectaddr = $matches[2];
			}
		
			if(preg_match("|^HTTP/|",$result_headers[$currentHeader]))
				$this->response_code = $result_headers[$currentHeader];

			$this->headers[] = $result_headers[$currentHeader];
		}

		// check if there is a a redirect meta tag
		
		if(preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",$results,$match))
		{
			$this->_redirectaddr = $this->_expandlinks($match[1],$URI);	
		}

		// have we hit our frame depth and is there frame src to fetch?
		if(($this->_framedepth < $this->maxframes) && preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i",$results,$match))
		{
			$this->results[] = $results;
			for($x=0; $x<count($match[1]); $x++)
				$this->_frameurls[] = $this->_expandlinks($match[1][$x],$URI_PARTS["scheme"]."://".$this->host);
		}
		// have we already fetched framed content?
		elseif(is_array($this->results))
			$this->results[] = $results;
		// no framed content
		else
			$this->results = $results;

		unlink("$headerfile");
		
		return true;
	}

/*======================================================================*\
	Function:	setcookies()
	Purpose:	set cookies for a redirection
\*======================================================================*/
	
	function setcookies()
	{
		for($x=0; $x<count($this->headers); $x++)
		{
		if(preg_match('/^set-cookie:[\s]+([^=]+)=([^;]+)/i', $this->headers[$x],$match))
			$this->cookies[$match[1]] = urldecode($match[2]);
		}
	}

	
/*======================================================================*\
	Function:	_check_timeout
	Purpose:	checks whether timeout has occurred
	Input:		$fp	file pointer
\*======================================================================*/

	function _check_timeout($fp)
	{
		if ($this->read_timeout > 0) {
			$fp_status = socket_get_status($fp);
			if ($fp_status["timed_out"]) {
				$this->timed_out = true;
				return true;
			}
		}
		return false;
	}

/*======================================================================*\
	Function:	_connect
	Purpose:	make a socket connection
	Input:		$fp	file pointer
\*======================================================================*/
	
	function _connect(&$fp)
	{
		if(!empty($this->proxy_host) && !empty($this->proxy_port))
			{
				$this->_isproxy = true;
				
				$host = $this->proxy_host;
				$port = $this->proxy_port;
			}
		else
		{
			$host = $this->host;
			$port = $this->port;
		}
	
		$this->status = 0;
		
		if($fp = fsockopen(
					$host,
					$port,
					$errno,
					$errstr,
					$this->_fp_timeout
					))
		{
			// socket connection succeeded

			return true;
		}
		else
		{
			// socket connection failed
			$this->status = $errno;
			switch($errno)
			{
				case -3:
					$this->error="socket creation failed (-3)";
				case -4:
					$this->error="dns lookup failure (-4)";
				case -5:
					$this->error="connection refused or timed out (-5)";
				default:
					$this->error="connection failed (".$errno.")";
			}
			return false;
		}
	}
/*======================================================================*\
	Function:	_disconnect
	Purpose:	disconnect a socket connection
	Input:		$fp	file pointer
\*======================================================================*/
	
	function _disconnect($fp)
	{
		return(fclose($fp));
	}

	
/*======================================================================*\
	Function:	_prepare_post_body
	Purpose:	Prepare post body according to encoding type
	Input:		$formvars  - form variables
				$formfiles - form upload files
	Output:		post body
\*======================================================================*/
	
	function _prepare_post_body($formvars, $formfiles)
	{
		settype($formvars, "array");
		settype($formfiles, "array");
		$postdata = '';

		if (count($formvars) == 0 && count($formfiles) == 0)
			return;
		
		switch ($this->_submit_type) {
			case "application/x-www-form-urlencoded":
				reset($formvars);
				while(list($key,$val) = each($formvars)) {
					if (is_array($val) || is_object($val)) {
						while (list($cur_key, $cur_val) = each($val)) {
							$postdata .= urlencode($key)."[]=".urlencode($cur_val)."&";
						}
					} else
						$postdata .= urlencode($key)."=".urlencode($val)."&";
				}
				break;

			case "multipart/form-data":
				$this->_mime_boundary = "Snoopy".md5(uniqid(microtime()));
				
				reset($formvars);
				while(list($key,$val) = each($formvars)) {
					if (is_array($val) || is_object($val)) {
						while (list($cur_key, $cur_val) = each($val)) {
							$postdata .= "--".$this->_mime_boundary."\r\n";
							$postdata .= "Content-Disposition: form-data; name=\"$key\[\]\"\r\n\r\n";
							$postdata .= "$cur_val\r\n";
						}
					} else {
						$postdata .= "--".$this->_mime_boundary."\r\n";
						$postdata .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
						$postdata .= "$val\r\n";
					}
				}
				
				reset($formfiles);
				while (list($field_name, $file_names) = each($formfiles)) {
					settype($file_names, "array");
					while (list(, $file_name) = each($file_names)) {
						if (!is_readable($file_name)) continue;

						$fp = fopen($file_name, "r");
						$file_content = fread($fp, filesize($file_name));
						fclose($fp);
						$base_name = basename($file_name);

						$postdata .= "--".$this->_mime_boundary."\r\n";
						$postdata .= "Content-Disposition: form-data; name=\"$field_name\"; filename=\"$base_name\"\r\n\r\n";
						$postdata .= "$file_content\r\n";
					}
				}
				$postdata .= "--".$this->_mime_boundary."--\r\n";
				break;
		}

		return $postdata;
	}
}
?>