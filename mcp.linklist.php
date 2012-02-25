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


class Linklist_CP {

	var $version = "1.2.1";
	var $subversion = "0";

	function linklist_module_install()
	{
		global $DB;        
		
		$sql[] = "INSERT INTO exp_modules (module_id, 
										   module_name, 
										   module_version, 
										   has_cp_backend) 
										   VALUES 
										   ('', 
										   'Linklist', 
										   '$this->version', 
										   'y')";

		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_linklist_urls` (
					`url_id` int(10) unsigned NOT NULL auto_increment,
					`linklist_id` int(10) unsigned NOT NULL default '0',
					`url` varchar(255) NOT NULL default '',
					`url_rss` varchar(255) NOT NULL default '',
					`url_title` varchar(255) NOT NULL default '',
					`url_favicon` varchar(255) NOT NULL default '',
					`url_desc` text NOT NULL,
					`url_added` int(11) NOT NULL default '0',
					`url_added_by_id` int(10) NOT NULL default '0',
					`url_status` varchar(10) NOT NULL default 'open',
					`keywords` text NOT NULL,
					`updated` int(11) NOT NULL default '0',
					`last_updated` int(11) NOT NULL default '0',
					`disporder` int(11) unsigned NOT NULL default '0',
					`clickthru` INT(20) NOT NULL,
					`xfn_mine` char(1) NOT NULL default 'n',
					`xfn_friendship` varchar(50) NOT NULL default '',
					`xfn_met` char(1) NOT NULL default 'n',
					`xfn_coworker` char(1) NOT NULL default 'n',
					`xfn_colleague` char(1) NOT NULL default 'n',
					`xfn_geographical` varchar(50) NOT NULL default '',
					`xfn_family` varchar(50) NOT NULL default '',
					`xfn_muse` char(1) NOT NULL default 'n',
					`xfn_crush` char(1) NOT NULL default 'n',
					`xfn_date` char(1) NOT NULL default 'n',
					`xfn_sweetheart` char(1) NOT NULL default 'n',
					PRIMARY KEY  (`url_id`));";



		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_linklist` (
					`linklist_id` int(10) unsigned NOT NULL auto_increment,
					`member_id` int(10) unsigned NOT NULL default '0',
					`linklist_name` varchar(50) NOT NULL default '',
					`linklist_title` varchar(255) NOT NULL default '',
					`prepend_str` varchar(255) NOT NULL default '',
					`append_str` varchar(255) NOT NULL default '',
					`recently_updated` INT(5) NOT NULL default '12',
					PRIMARY KEY  (`linklist_id`));";

        $sql[] = "INSERT INTO exp_actions (action_id, 
                                           class, 
                                           method) 
                                           VALUES 
                                           ('', 
                                           'Linklist',
                                           'jump_url')"; 

		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		
		return true;
	}
	// END
		
		
	// ----------------------------------------
	//  Module de-installer
	// ----------------------------------------
	
	function linklist_module_deinstall()
	{
		global $DB;
	
		$query = $DB->query("SELECT module_id
							 FROM exp_modules 
							 WHERE module_name = 'Linklist'"); 
				
		$sql[] = "DELETE FROM exp_module_member_groups 
				  WHERE module_id = '".$query->row['module_id']."'";      
				  
		$sql[] = "DELETE FROM exp_modules 
				  WHERE module_name = 'Linklist'";
				  
		$sql[] = "DELETE FROM exp_actions 
				  WHERE class = 'Linklist'";
				  
		$sql[] = "DELETE FROM exp_actions 
				  WHERE class = 'Linklist_CP'";
				  
		$sql[] = "DROP TABLE IF EXISTS exp_linklist";
		
		$sql[] = "DROP TABLE IF EXISTS exp_linklist_urls";
	
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
	
		return true;
	}
	// END
	
	// -------------------------
	//  Constructor
	// -------------------------
	
	function Linklist_CP( $switch = TRUE )
	{
		global $IN;
		
		if ($switch)
		{
			switch($IN->GBL('P'))
			{
				case 'add'						:$this->modify_linklist();
					break;
				case 'modify'					:$this->modify_linklist();
					break;
				case 'update'					:$this->update_linklist();
					break;
				case 'delete_linklist'			:$this->delete_linklist();
					break;
				case 'post_linklist'			:$this->post_linklist();
					break;
				case 'delete_link'				:$this->delete_link();
					break;
				case 'edit_links'				:$this->edit_links();
					break;
				case 'add_link'					:$this->modify_link();
					break;
				case 'modify_link'				:$this->modify_link();
					break;
				case 'update_links'				:$this->update_links();
					break;
				case 'move_link'				:$this->move_link();
					break;
				case 'post_link'				:$this->post_link();
					break;
				case 'begin_import'				:$this->import_opml_step1();
					break;
				case 'import_step2'				:$this->import_opml_step2();
					break;
				case 'create_bm'				:$this->create_bookmarklet();
					break;
				case 'reference'				:$this->reference();
					break;
				case 'upgrade'					:$this->upgrade();
					break;
				case 'order_links'				:$this->order_links();
					break;
				default							:$this->linklist_home();
					break;
			}
		}
	}
	// END

	// ----------------------------------------
    //  Module Homepage
    // ----------------------------------------
	function linklist_home($msg='')
	{
		global $DSP, $LANG, $DB, $SESS, $PREFS;

		$DSP->title = $LANG->line('linklist_module_name');
		$DSP->crumb = $DSP->anchor(BASE.
								   AMP.'C=modules'.
								   AMP.'M=linklist',
								   $LANG->line('linklist_module_name'));        

		// Version Check
		$sql = "SELECT * FROM exp_modules WHERE module_name = 'Linklist'";
		$query = $DB->query($sql);
		if ( $query->num_rows > 0 && $query->row['module_version'] != $this->version )
		{
			$DSP->crumb .= $DSP->crumb_item($LANG->line('version_mismatch'));
			
			$r  = '';
			
			$r .=	$DSP->qdiv('alert',$LANG->line('version_mismatch_details'));
			
			$r .=	$DSP->qdiv('itemWrapper',$DSP->anchor(BASE.
													AMP.'C=modules'.
													AMP.'M=linklist'.
													AMP.'P=upgrade',
													$LANG->line('upgrade')));
		}
		else
		{
			$DSP->crumb .= $DSP->crumb_item($LANG->line('view_linklist'));    
			
			$r	=	$DSP->table('tableBorder');
			
			$r	.=	$DSP->tr().
					$DSP->td('tableHeading','','3').
					$LANG->line('linklist_menu').
					$DSP->td_c().
					$DSP->tr_c();
					
					
			$r	.=	$DSP->tr().
					$DSP->td('tableCellOne','33%','','','','center').
					$DSP->anchor(BASE.
								AMP.'C=modules'.
								AMP.'M=linklist'.
								AMP.'P=add',
								$LANG->line('add_linklist')).
					$DSP->td_c().
					$DSP->td('tableCellOne','33%','','','','center').
					$DSP->anchor(BASE.
								AMP.'C=modules'.
								AMP.'M=linklist'.
								AMP.'P=create_bm',
								$LANG->line('bookmarklet')).
					$DSP->td_c().
					$DSP->td('tableCellOne','33%','','','','center').
					$DSP->anchor(BASE.
								AMP.'C=modules'.
								AMP.'M=linklist'.
								AMP.'P=reference',
								$LANG->line('reference')).
					$DSP->td_c().
					$DSP->tr_c();
			
			$r	.=	$DSP->table_c();
			
			$sql = 'SELECT linklist_id,linklist_name,linklist_title FROM exp_linklist WHERE member_id = '.$SESS->userdata['member_id'];
			$query = $DB->query($sql);
	
			if ( $msg != '' )
			{
				$r	.=	$DSP->qdiv('success',$msg);
			}
			
			$r	.=	$DSP->toggle();
	
			$r	.=	$DSP->form("C=modules".AMP."M=linklist".AMP."P=post_linklist","target");
	
			$r	.=	$DSP->table('tableBorder', '0', '', '100%').
					$DSP->tr().
					$DSP->table_qcell('tableHeading',
										array(
											$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\""),
											$LANG->line('id'),
											$LANG->line('linklist_name'),
											$LANG->line('linklist_title'),
											''
										)).
					$DSP->tr_c();
			
			$i = 0;
			
			foreach ( $query->result as $row )
			{
				$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
						
				$r	.=	$DSP->tr();
				
				$r 	.=	$DSP->table_qcell($style, $DSP->input_checkbox('toggle[]',$row['linklist_id']), '20px', 'top');
				
				$r	.=	$DSP->table_qcell($style,$row['linklist_id'],'20px','top');
				
				$r	.=	$DSP->table_qcell($style,$DSP->anchor(BASE.
																AMP.'C=modules'.
																AMP.'M=linklist'.
																AMP.'P=edit_links'.
																AMP.'linklist_id='.
																$row['linklist_id'],
																$row['linklist_name'].
																NBS.
																'('.
																$this->get_linklist_count($row['linklist_id']).
																NBS.
																$LANG->line('linklist_count').
																')'),'', 'top');
				
				$r 	.=	$DSP->table_qcell($style, $row['linklist_title'], '', 'top');
				
				$r	.=	$DSP->table_qcell($style, $DSP->anchor(BASE.
													  AMP.'C=modules'.
													  AMP.'M=linklist'.
													  AMP.'P=modify'.
													  AMP.'linklist_id='.
													  $row['linklist_id'],
													  $LANG->line('linklist_preferences')), '10%', 'top');
	
				$r	.=	$DSP->tr_c();
			}

			if ( $query->num_rows == 0 )
			{
				$r	.=	$DSP->tr().
						$DSP->td('tableCellOne','','4').
						$LANG->line('view_linklist_noresults').
						$DSP->td_c().
						$DSP->tr_c();
			}
			
			if ( $SESS->userdata['group_id'] == 1  )
			{
				
				$sql = 'SELECT linklist_id,linklist_name,linklist_title,member_id FROM exp_linklist WHERE member_id <> '.$SESS->userdata['member_id']." ORDER BY member_id";
				$query = $DB->query($sql);


				if ( $query->num_rows > 0 )
				{
					
					$i = 0;
					
					$u = 0;
					
					foreach ( $query->result as $row )
					{
						if ( $u !=  $row['member_id'] )
						{
							$sql = "SELECT screen_name FROM exp_members WHERE member_id = ".$row['member_id'];
							$sn_query = $DB->query($sql);
							$screen_name = $sn_query->row['screen_name'];
							$u = $row['member_id'];
							$r	.=	$DSP->tr().
									$DSP->td('tableHeading','','5').
									$LANG->line('linklist_for_user').$screen_name.
									$DSP->td_c().
									$DSP->tr_c();
						}
						$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
								
						$r	.=	$DSP->tr();
						
						$r 	.=	$DSP->table_qcell($style, $DSP->input_checkbox('toggle[]',$row['linklist_id']), '20px', 'top');
						
						$r	.=	$DSP->table_qcell($style,$row['linklist_id']);
		
						$r	.=	$DSP->table_qcell($style,$DSP->anchor(BASE.
																		AMP.'C=modules'.
																		AMP.'M=linklist'.
																		AMP.'P=edit_links'.
																		AMP.'linklist_id='.
																		$row['linklist_id'],
																		$row['linklist_name'].
																		NBS.
																		'('.
																		$this->get_linklist_count($row['linklist_id']).
																		NBS.
																		$LANG->line('linklist_count').
																		')'),'', 'top');
						
						$r 	.=	$DSP->table_qcell($style, $row['linklist_title'], '', 'top');
						
						$r	.=	$DSP->table_qcell($style, $DSP->anchor(BASE.
															  AMP.'C=modules'.
															  AMP.'M=linklist'.
															  AMP.'P=modify'.
															  AMP.'linklist_id='.
															  $row['linklist_id'],
															  $LANG->line('linklist_preferences')), '10%', 'top');
			
						$r	.=	$DSP->tr_c();
			
			
					}
				}
			}

			
			$r	.=	$DSP->table_c();
			

			$r	.=	$DSP->table('', '0', '', '100%').
					$DSP->tr();
	
			$r 	.=	$DSP->table_qcell("defaultLeft",$DSP->input_submit($LANG->line('delete_linklist')));
			
			$r	.=	$DSP->table_qcell("defaultRight",$LANG->line('version').$this->version.' ('.$this->subversion.')');
			
			$r	.=	$DSP->tr_c().
					$DSP->table_c();

			$r	.=	$DSP->form_c();





		}
		
		$DSP->body	.=	$r;

	}
    // END
	
	function modify_linklist($msg='')
	{
		global $DSP, $LANG, $IN, $DB;
		
		$DSP->title = $LANG->line('linklist_module_name');
        $DSP->crumb = $DSP->anchor(BASE.
                                   AMP.'C=modules'.
                                   AMP.'M=linklist',
                                   $LANG->line('linklist_module_name'));     

		if ( ! $IN->GBL('linklist_id') )
		{
	        $DSP->crumb .= $DSP->crumb_item($LANG->line('add_linklist'));
			$r	 			= $DSP->heading($LANG->line('add_linklist'));
			$linklist_name	= '';
			$linklist_title = '';
			$prepend_str 	= '';
			$append_str		= '';
			$recently_updated = '12';
		}
		else
		{
	        $DSP->crumb .= $DSP->crumb_item($LANG->line('modify_linklist'));
			$r	 	= $DSP->heading($LANG->line('modify_linklist'));
			$sql	= 'SELECT * FROM exp_linklist WHERE linklist_id = '.$IN->GBL('linklist_id');
			$query	= $DB->query($sql);
			if ( $query->num_rows == 0 )
			{
				$DSP->body	= $DSP->error_message($LANG->line('invalid_linklist_id'));
				return;
			}
			foreach ( $query->row as $key => $val )
			{
				$$key = $val;
			}
		}

		if ( $msg != '' )
		{
			$r	.=	$DSP->qdiv('success',$msg);
		}
		
		$r 	.=	$DSP->form('C=modules'.AMP.'M=linklist'.AMP.'P=update');
		
		if ( $linklist_name != '' )
		{
			$r	.= $DSP->input_hidden('linklist_id', $IN->GBL('linklist_id'));
		}
		$r	.=	$DSP->table('tableBorder', 0, 0, '100%');
		$r	.=	$DSP->tr().
				$DSP->table_qcell('tableHeading',array('','')).
				$DSP->tr_c();
		$r	.=	$DSP->tr();
		$r	.=	$DSP->table_qcell('tableCellOne',$DSP->span('defaultBold').
													$DSP->required().$LANG->line('linklist_name').$DSP->span_c().
													NBS.
													'-'.
													NBS.
													$LANG->line('linklist_name_spec')
													);
		
		$r	.=	$DSP->table_qcell('tableCellOne',$DSP->input_text('linklist_name',$linklist_name,25,50,'input',''));
		
		$r 	.=	$DSP->tr_c();
		
		$r	.=	$DSP->tr();
		
		$r	.=	$DSP->table_qcell( 'tableCellTwo', $DSP->span('defaultBold').
													$DSP->required().$LANG->line('linklist_title').
													$DSP->span_c()
													);
		
		$r	.=	$DSP->table_qcell( 'tableCellTwo', $DSP->input_text('linklist_title',$linklist_title,35,255,'input',''));
		
		$r	.=	$DSP->tr_c();

		$r	.=	$DSP->tr().
				$DSP->td( 'tableCellOne', '', '2').
				$DSP->span('defaultBold').
				$LANG->line('updated_title').
				$DSP->span_c().
				BR.
				$LANG->line('updated_explanation').
				$DSP->td_c().
				$DSP->tr_c();

		$r	.=	$DSP->tr();
		
		$r	.=	$DSP->table_qcell( 'tableCellTwo', $DSP->span('defaultBold').
													$LANG->line('prepend_str').
													$DSP->span_c().
													BR.
													$LANG->line('prepend_string_detail')
													);
		
		$r	.=	$DSP->table_qcell( 'tableCellTwo', $DSP->input_text('prepend_str',$prepend_str,15,255,'input',''));
		
		$r	.=	$DSP->tr_c();
		
		$r	.=	$DSP->tr();
		
		$r	.=	$DSP->table_qcell( 'tableCellOne', $DSP->span('defaultBold').
													$LANG->line('append_str').
													$DSP->span_c().
													BR.
													$LANG->line('append_string_detail')
													);
		
		$r	.=	$DSP->table_qcell( 'tableCellOne', $DSP->input_text('append_str',$append_str,15,255,'input',''));
		
		$r	.=	$DSP->tr_c();

		$r	.=	$DSP->tr();
		
		$r	.=	$DSP->table_qcell( 'tableCellOne', $DSP->span('defaultBold').
													$LANG->line('recently_updated').
													$DSP->span_c().
													BR.
													$LANG->line('recently_updated_detail')
													);
		
		$r	.=	$DSP->table_qcell( 'tableCellOne', $DSP->input_text('recently_updated',$recently_updated,5,255,'input',''));
		
		$r	.=	$DSP->tr_c();

		$r	.=	$DSP->tr();
		
		$r	.=	$DSP->tr_c();
		$r	.=	$DSP->tr();
		$r	.=	$DSP->table_c();
		
		if ( $linklist_name != '' )
		{
			$r	.=	$DSP->input_submit($LANG->line('modify_linklist'));
		}
		else
		{
			$r	.=	$DSP->input_submit($LANG->line('add_linklist'));
		}
		$r	.=	$DSP->form_c();
		
		$DSP->body	=	$r;
	
	}

	function update_linklist()
	{
		global $DSP, $LANG, $SESS, $DB;

		$DSP->title = $LANG->line('linklist_module_name');
        $DSP->crumb = $DSP->anchor(BASE.
                                   AMP.'C=modules'.
                                   AMP.'M=linklist',
                                   $LANG->line('linklist_module_name'));        
        $DSP->crumb .= $DSP->crumb_item($LANG->line('update_linklist')); 
		
		// Check for valid Input
		
		$linklist_name	=	( ! isset($_POST['linklist_name']) ? '' : trim($_POST['linklist_name']));
		$linklist_id	=	( ! isset($_POST['linklist_id']) ? '' : trim($_POST['linklist_id']));
		$linklist_title =	( ! isset($_POST['linklist_title']) ? '' : trim($_POST['linklist_title']));
		$prepend_str	=	( ! isset($_POST['prepend_str']) ? '' : trim($_POST['prepend_str']));
		$append_str		=	( ! isset($_POST['append_str']) ? '' : trim($_POST['append_str']));
		$recently_updated = 	( ! isset($_POST['recently_updated']) ? '' : trim($_POST['recently_updated']));

		if ( $linklist_name == '' )
		{
			$DSP->body	.=	$DSP->error_message($LANG->line('invalid_linklist_name'));
			return;	
		}
		
		if ( $linklist_title == '' )
		{
			$DSP->body	.=	$DSP->error_message($LANG->line('invalid_linklist_title'));
			return;
		}
		
		// Check for already defined Link List name
		$temp_id = ($linklist_id == '') ? $temp_id = 0 : $temp_id = $linklist_id; 
		$sql = "SELECT linklist_id FROM exp_linklist WHERE linklist_name = '".trim($linklist_name)."' AND linklist_id <> ".$temp_id;
		$query = $DB->query($sql);
		
		if ( $query->num_rows > 0 )
		{
			$DSP->body	.=	$DSP->error_message($LANG->line('linklist_name_already_exists'));
			return;
		}
		
		// Insert the information
		
		$data	=	array('linklist_name'	=> trim($linklist_name),
						  'member_id'		=> trim($SESS->userdata['member_id']),
						  'linklist_title'	=> trim($linklist_title),
						  'prepend_str'		=> trim($prepend_str),
						  'append_str'		=> trim($append_str),
						  'recently_updated'=> trim($recently_updated)
						  );
		
		if ( $linklist_id != '' )
		{
			$DB->query($DB->update_string('exp_linklist', $data, 'linklist_id = '.$linklist_id));
			return $this->linklist_home($LANG->line('linklist_updated'));
		}
		else
		{
			$DB->query($DB->insert_string('exp_linklist', $data));
			return $this->linklist_home($LANG->line('linklist_added'));
		}
	}

	function delete_linklist()
	{
		global $DSP, $LANG, $IN, $DB;
		
		// Check for valid data
		$linklist_id = (! isset($_POST['linklist_id']) ? '' : $_POST['linklist_id']);
		
		if ( $linklist_id == '' )
		{
			$DSP->title = $LANG->line('linklist_module_name');
			$DSP->crumb = $DSP->anchor(BASE.
									   AMP.'C=modules'.
									   AMP.'M=linklist',
									   $LANG->line('linklist_module_name'));        
			$DSP->crumb .= $DSP->crumb_item($LANG->line('delete_linklist'));
			$DSP->body	.= $DSP->error_message($LANG->line('invalid_linklist_id'));
			return;
		}
		
		$linklist_query = '(';
		
		foreach( $linklist_id as $val )
		{
			$linklist_query .= $val.',';
		}
		
		$linklist_query = substr( $linklist_query, 0, -1 );
		$linklist_query .= ')';
		
		$sql[] = 'DELETE FROM exp_linklist WHERE linklist_id IN '.$linklist_query;
		$sql[] = 'DELETE FROM exp_linklist_urls WHERE linklist_id IN '.$linklist_query;
		
		foreach ( $sql as $query )
		{
			$DB->query($query);
		}
		
		return $this->linklist_home( $LANG->line('linklist_deleted'));
	}

	function post_linklist()
	{
		global $DSP, $LANG, $IN, $DB;
		
		$DSP->title = $LANG->line('linklist_module_name');
        $DSP->crumb = $DSP->anchor(BASE.
                                   AMP.'C=modules'.
                                   AMP.'M=linklist',
                                   $LANG->line('linklist_module_name'));        
        $DSP->crumb .= $DSP->crumb_item($LANG->line('delete_linklist'));

        $DSP->body .= $DSP->heading($LANG->line('delete_linklist')); 
		
		$linklist_id = $IN->GBL('toggle','POST');

		if ( $linklist_id == '' )
		{		
			$DSP->body .= $DSP->error_message($LANG->line('invalid_linklist_id'));
			return;
		}
		
		$linklist_query = '(';
		
		foreach( $linklist_id as $val )
		{
			$linklist_query .= $val.',';
		}
		
		$linklist_query = substr( $linklist_query, 0, -1 );
		$linklist_query .= ')';
		
		$sql	= 'SELECT linklist_name FROM exp_linklist WHERE linklist_id IN '.$linklist_query;
		
		$query	= $DB->query($sql);
		
		$linklist_name = '';
		
		foreach ( $query->result as $row )
		{
			$linklist_name	.= $row['linklist_name'].BR;
		}

		$DSP->body .= $DSP->qdiv('itemWrapper', $LANG->line('confirm_delete_linklist')).BR;
		
		$DSP->body .= $DSP->qdiv('itemWrapper', '<i>'.$linklist_name.'</i>').BR;
		
		$DSP->body .= $DSP->qdiv('alert', $LANG->line('cannot_be_undone')).BR;
		
		$DSP->body .= $DSP->form('C=modules'.AMP.'M=linklist'.AMP.'P=delete_linklist');

		foreach ( $linklist_id as $val )
		{		
			$DSP->body .= $DSP->input_hidden('linklist_id[]', $val);
		}
		
		$DSP->body .= $DSP->input_submit($LANG->line('delete'));
		
		$DSP->body .= $DSP->form_c();

	}
	
	function edit_links($msg='',$id='')
	{
		global $DSP, $LANG, $IN, $DB, $SESS, $LOC, $PREFS;
		
		$DSP->title = $LANG->line('linklist_module_name');
        $DSP->crumb = $DSP->anchor(BASE.
                                   AMP.'C=modules'.
                                   AMP.'M=linklist',
                                   $LANG->line('linklist_module_name'));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('edit_links'));
        
		if ( $id == '' )
		{
			if ( ! $IN->GBL('linklist_id') )
			{		
				$DSP->body .= $DSP->error_message($LANG->line('invalid_linklist_id'));
				return;
			}
			else
			{
				$linklist_id = $IN->GBL('linklist_id');
			}
		}
		else
		{
			$linklist_id = $id;
		}

		if ( $msg != '' )
		{
			$r	=	$DSP->qdiv('success',$msg);
		}
		else
		{
			$r = '';
		}

		$r 	.=	$DSP->toggle();

		$sql	= 'SELECT linklist_name FROM exp_linklist WHERE linklist_id = '.$linklist_id;
		
		$query	= $DB->query($sql);
		$linklist_name	= $query->row['linklist_name'];

		$r	.=	$DSP->table().
				$DSP->tr();
				
		$r	.=	$DSP->td().
				$DSP->heading($LANG->line('edit_links').' : '.$linklist_name).
				$DSP->td_c();
		
		$r	.=	$DSP->td('defaultRight').
				$DSP->heading($DSP->anchor(BASE.
											AMP.'C=modules'.
											AMP.'M=linklist'.
											AMP.'P=begin_import'.
											AMP.'linklist_id='.
											$linklist_id,
											$LANG->line('import_opml')),5).
				$DSP->heading($DSP->anchor(BASE.
											AMP.'C=modules'.
											AMP.'M=linklist'.
											AMP.'P=add_link'.
											AMP.'linklist_id='.
											$linklist_id,
											$LANG->line('add_url')),5).
				$DSP->td_c();
		
		$r	.=	$DSP->tr_c().
				$DSP->table_c();

		$sql	= 'SELECT * FROM exp_linklist_urls WHERE linklist_id = '.$linklist_id.' ORDER BY disporder';
					
		$query = $DB->query($sql);

		if ( $query->num_rows == 0 )
		{
			$r	.=	$DSP->table('tableBorder', '0', '0', '100%').
					$DSP->tr().
					$DSP->table_qcell('tableHeading',$LANG->line('view_linklist_urls_noresults')).
					$DSP->tr_c().
					$DSP->table_c();
		}
		else
		{
			$r	.=	$DSP->qdiv('itemWrapper',$LANG->line('total_links').$query->num_rows);
			
			$r	.=	$DSP->form("C=modules".AMP."M=linklist".AMP."P=post_link","target");
			
			$r	.=	$DSP->input_hidden('linklist_id',$linklist_id);

			$r	.=	$DSP->table('tableBorder', '0', '0', '100%').
					$DSP->tr();
			$r 	.=	$DSP->table_qcell('tableHeading',
										array(
										$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\""),
										$LANG->line('url_name'),
										$LANG->line('url_value'),
										$LANG->line('url_rss'),
										$LANG->line('url_status'),
										$LANG->line('url_added_date'),
										$LANG->line('last_updated'),
										$LANG->line('clickthrus'),
										$LANG->line('disporder')
										)
									);
										
			$i = 0;
			$themes_folder_url = $PREFS->core_ini['theme_folder_url'];
			
			foreach ( $query->result as $row )
			{
	            $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				
				$r	.=	$DSP->tr();
				
				$r	.=	$DSP->table_qcell($style,$DSP->input_checkbox('toggle[]',$row['url_id']),'20px');

				$r	.=	$DSP->table_qcell($style,$DSP->anchor(BASE.
											AMP."C=modules".
											AMP."M=linklist".
											AMP."P=modify_link".
											AMP."linklist_id=".
											$linklist_id.
											AMP."url_id=".
											$row['url_id'],
											$row['url_title']));
				
				$r	.=	$DSP->table_qcell($style,$DSP->anchor($row['url'],
															$row['url'],
															'',
															'TRUE'));
															
				$r	.=	$DSP->table_qcell($style,$row['url_rss']);
				
				$r	.=	$DSP->table_qcell($style,$row['url_status']);
				
				if ( $row['url_added'] > 0 )
				{
					$r	.=	$DSP->table_qcell($style,gmdate("m/d/y",$LOC->set_localized_time($row['url_added'])));
				}
				else
				{
					$r	.=	$DSP->table_qcell($style,'');
				}
				
				if ( $row['updated'] > 0 )
				{
					$r	.=	$DSP->table_qcell($style,gmdate("m/d/y H:i:s",$LOC->set_localized_time($row['updated'])));
				}
				else
				{
					$r	.=	$DSP->table_qcell($style,'');
				}
				
				$r 	.=	$DSP->table_qcell($style,$row['clickthru']);
				
				// move up/down sort.
				if ( substr($themes_folder_url,-1) != "/" )
				{
					$themes_folder_url = $themes_folder_url . "/";
				}
				
				
				$d	 =	$DSP->anchor(BASE.
											AMP."C=modules".
											AMP."M=linklist".
											AMP."P=order_links".
											AMP."linklist_id=".
											$linklist_id.
											AMP."url_id=".
											$row['url_id'].
											AMP."order=up",
											"<img src='".$themes_folder_url."cp_global_images/arrow_up.gif' border='0' width='16' height='16' />");

				$d	 .=	$DSP->anchor(BASE.
											AMP."C=modules".
											AMP."M=linklist".
											AMP."P=order_links".
											AMP."linklist_id=".
											$linklist_id.
											AMP."url_id=".
											$row['url_id'].
											AMP."order=down",
											"<img src='".$themes_folder_url."cp_global_images/arrow_down.gif' border='0' width='16' height='16' />");
											
				$r	.=	$DSP->table_qcell($style,$d);
				
				$r	.=	$DSP->tr_c();
			}
			
			$r	.=	$DSP->tr_c().
					$DSP->table_c();


			$r	.=	$DSP->qdiv('itemWrapper',
								$DSP->input_submit($LANG->line('delete'),'delete').
								NBS.
								$DSP->input_submit($LANG->line('move'),'move').
								NBS.
								$DSP->input_submit($LANG->line('auto_rss'),'rss'));
								
			$r	.= 	$DSP->form_c();
	

		}
		$DSP->body = $r;
	
	
	}

	function modify_link($msg='')
	{
		global $DSP, $LANG, $IN, $DB, $SESS, $LOC;
		
		$bookmarklet = ( ! $IN->GBL('BK') ? 0 : 1);
		$linklist_id = (! $IN->GBL('linklist_id') ? '' : $IN->GBL('linklist_id'));
		$url_id = (! $IN->GBL('url_id') ? '' : $IN->GBL('url_id'));
		
		$DSP->title = $LANG->line('linklist_module_name');
        $DSP->crumb = $DSP->anchor(BASE.
                                   AMP.'C=modules'.
                                   AMP.'M=linklist',
                                   $LANG->line('linklist_module_name'));
		$DSP->crumb .= $DSP->crumb_item($DSP->anchor(BASE.
										AMP.'C=modules'.
										AMP.'M=linklist'.
										AMP.'P=edit_links'.
										AMP.'linklist_id='.
										$linklist_id,
										$LANG->line('edit_links')));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('add_url'));

		ob_start();
?>
<script>
function showHide(entryID, entryLink, htmlObj, type) {
if (type == "comments") {
extTextDivID = ('comText' + (entryID));
extLinkDivID = ('comLink' + (entryID));
} else {
extTextDivID = ('extText' + (entryID));
extLinkDivID = ('extLink' + (entryID));
}
if( document.getElementById ) {
if( document.getElementById(extTextDivID).style.display ) {
if( entryLink != 0 ) {
document.getElementById(extTextDivID).style.display = "block";
document.getElementById(extLinkDivID).style.display = "none";
htmlObj.blur();
} else { 
document.getElementById(extTextDivID).style.display = "none";
document.getElementById(extLinkDivID).style.display = "block";
}
} else {
location.href = entryLink;
return true;
}
} else {
location.href = entryLink;
return true;
}
}
</script>
<?php
		$r= ob_get_contents();
		ob_end_clean();            

		if ( $linklist_id == '' )
		{
			$DSP->body .= $DSP->error_message($LANG->line('invalid_linklist_id'));
			return;
		}
		else
		{
			$sql = 'SELECT linklist_name FROM exp_linklist WHERE linklist_id = '.$linklist_id;
			$query = $DB->query($sql);
			$linklist_name = $query->row['linklist_name'];
		}

		if ( $url_id == '' )
		{
			$head_string = $LANG->line('add_url').' : '.$linklist_name;

			$url_title = ( ! $IN->GBL('url_title') ) ? '' : $IN->GBL('url_title');
			$url = ( ! $IN->GBL('url') ) ? '' : $IN->GBL('url');
			// Some post processing to fix the invalid GET data stuff
			$url_title = eregi_replace(":qm:","?",$url_title);
			$url_title = eregi_replace(":ds:","$",$url_title);
			$url_title = stripslashes($url_title);
			$url = eregi_replace(":qm:","?",$url);
			$url = eregi_replace(":ds:","$",$url);
			$url_rss = '';
			$url_favicon = '';
			$url_added = $LOC->now;
			$url_desc = '';
			$url_status = 'open';
			$clickthru = '0';
			$keywords = '';
			$xfn_mine = 'n';
			$xfn_friendship = 'None';
			$xfn_met = 'n';
			$xfn_coworker = 'n';
			$xfn_colleague = 'n';
			$xfn_geographical = 'None';
			$xfn_family = 'None';
			$xfn_muse = 'n';
			$xfn_crush = 'n';
			$xfn_date = 'n';
			$xfn_sweetheart = 'n';
			
			if ( $bookmarklet )
			{
				// if it's a bookmarklet, let's retrieve the RSS Feed now.
				$fetch_urls = $this->retrieve_rss_url($url);
				$url_rss = $fetch_urls['rssurl'];
				$url_favicon = $fetch_urls['faviconurl'];
			}
			
			// get a list of the links in this linklist so we can see where to put this link in the disp order.
			$sql = "SELECT url_id,url_title FROM exp_linklist_urls WHERE linklist_id = ".$linklist_id." ORDER BY disporder";
			$url_list = $DB->query($sql);
		}
		else
		{
			$head_string = $LANG->line('modify_url').' : '.$linklist_name;
			$sql = 'SELECT * FROM exp_linklist_urls WHERE url_id = '.$url_id;
			$query = $DB->query($sql);
			
			foreach ( $query->row as $key => $val )
			{
				$$key = $val;
			}
		}

		
		if ( $msg != '' )
		{
			$r	.=	$DSP->qdiv('success',$msg);
		}
		
		$r .= $DSP->form('C=modules'.AMP.'M=linklist'.AMP.'P=update_links');
		
		$r .= $DSP->input_hidden('linklist_id',$linklist_id);
		
		if ( $bookmarklet )
		{
			// If it's a bookmarklet, let's label it as such, so we know to jump back to where they were after they add this to the list.
			$r	.=	$DSP->input_hidden('bookmarklet','1');
		}
		
		$r .= $DSP->input_hidden('url_added_by_id',$SESS->userdata['member_id']);
		
		$r .= $DSP->input_hidden('url_added',$url_added);
		
		if ( $url_id != '' )
		{
			$r .= $DSP->input_hidden('url_id',$url_id);
		}
		
		$r .= $DSP->table('tableBorder', '0', '0', '100%').
			  $DSP->tr().
			  $DSP->td('tablePad');
			  
		$r .= $DSP->table('', '0').
			  $DSP->tr().
			  $DSP->td('tableHeading','','2').
			  $head_string.
			  $DSP->td_c().
			  $DSP->tr_c();

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellOne',
			  						array(
										$DSP->required().$DSP->span('defaultBold').$LANG->line('url_name').$DSP->span_c(),
										$DSP->input_text('url_title',
										$url_title,
										30,
										255,
										'input',''))
									).
			  $DSP->tr_c();

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellTwo',
			  						array(
										$DSP->required().$DSP->span('defaultBold').$LANG->line('url_value').$DSP->span_c(),
										$DSP->input_text('url',
										$url,
										50,
										255,
										'input',''))
									).
			  $DSP->tr_c();

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellOne',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_rss').$DSP->span_c(),
										$DSP->input_text('url_rss',
										$url_rss,
										50,
										255,
										'input',''))
									).
			  $DSP->tr_c();

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellTwo',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_favicon').$DSP->span_c(),
										$DSP->input_text('url_favicon',
										$url_favicon,
										50,
										255,
										'input',''))
									).
			  $DSP->tr_c();

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellOne',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_desc').$DSP->span_c(),
										$DSP->input_textarea('url_desc',
										$url_desc,
										5))
									).
			  $DSP->tr_c();

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellOne',
			  						array(
										$DSP->span('defaultBold').
										$LANG->line('url_keywords').
										$DSP->span_c().
										BR.
										$LANG->line('keywords_desc'),
										$DSP->input_text('keywords',
										$keywords,
										75,
										512,
										'input',''))
									).
			  $DSP->tr_c();


		$d  = $DSP->input_select_header('url_status',0,1);
		
		$d .= $DSP->input_select_option('open','Open',( $url_status == 'open' ) ? 1 : 0);
		
		$d .= $DSP->input_select_option('closed','Closed',( $url_status == 'closed' ) ? 1 : 0);
		
		$d .= $DSP->input_select_option('pending','Pending',( $url_status == 'pending' ) ? 1 : 0);
		
		$d .= $DSP->input_select_footer();

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellTwo',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_status').$DSP->span_c(),
										$d)
									).
			  $DSP->tr_c();

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellOne',
			  						array(
										$DSP->span('defaultBold').$LANG->line('clickthrus').$DSP->span_c(),
										$DSP->input_text('clickthru',
										$clickthru,
										5,
										20,
										'input',''))
									).
			  $DSP->tr_c();
	
		if ( $url_id == '' )
		{
			$d  = $DSP->input_select_header('disporder',0,1);
			
			$d .= $DSP->input_select_option('last',$LANG->line('endoflist'));
			
			$d .= $DSP->input_select_option('first',$LANG->line('beginningoflist'));

			foreach ( $url_list->result as $row )
			{
				$d .= $DSP->input_select_option($row['url_id'],$LANG->line('after').$row['url_title']);
			}
			
			$d .= $DSP->input_select_footer();		

			$r .= $DSP->tr().
				  $DSP->table_qcell('tableCellTwo',
										array(
											$DSP->span('defaultBold').$LANG->line('disporder').$DSP->span_c(),
											$d)
										).
				  $DSP->tr_c();
		
		
		}

		if ( $url_rss == '' || $url_favicon == '')
		{
			$r .= $DSP->tr().
				  $DSP->table_qcell('tableCellTwo',
										array(
											$DSP->span('defaultBold').$LANG->line('auto_detect_rss').$DSP->span_c(),
											$DSP->input_checkbox('autodetect_rss','y')
											)
									).
				  $DSP->tr_c();
		}

		$r .= $DSP->table_c();
			  
		$r .= $DSP->td_c().
			  $DSP->tr_c().
			  $DSP->table_c();

		$r .= BR;
		
		$r .= "<div id='extLink1' style='display: block; padding: 0;'>";
		$r .= $DSP->table('tableBorder', '0', '0', '100%').
			  $DSP->tr().
			  $DSP->td('tablePad');

		$r .= $DSP->table('', '0').
			  $DSP->tr().
			  $DSP->td('tableHeading','','2').
			  '<a href="javascript:void(0);" name="ext1" onclick="showHide(1,\'xfn\',this,\'entry\');return false;">'.
			  $LANG->line('xfn_preferences').
			  '</a>'.
			  $DSP->td_c().
			  $DSP->tr_c();
			  
		$r .= $DSP->table_c();
		
		$r .= $DSP->td_c().
			  $DSP->tr_c().
			  $DSP->table_c();

		$r .= "</div>";
		
		$r .= "<div id='extText1' style='display: none; padding: 0;'>";
			  
		$r .= $DSP->table('tableBorder', '0', '0', '100%').
			  $DSP->tr().
			  $DSP->td('tablePad');

		$r .= $DSP->table('', '0').
			  $DSP->tr().
			  $DSP->td('tableHeading','','2').
			  '<a href="javascript:void(0);" onclick="showHide(1,0,this,\'entry\');return true;">'.
			  $LANG->line('xfn_preferences').
			  '</a>'.
			  $DSP->td_c().
			  $DSP->tr_c();

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellTwo',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_xfn_mine'),
										$DSP->input_checkbox('xfn_mine',
															'y',
															$xfn_mine))
									).
			  $DSP->tr_c();

		$d  = "<label for='".$LANG->line('url_xfn_contact')."'>".
			  $DSP->input_radio('xfn_friendship',
			  					'contact',
			  					( $xfn_friendship == 'contact' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_contact')).
								$LANG->line('url_xfn_contact').
								"</label>".
								NBS.
								NBS.
								"<label for='".$LANG->line('url_xfn_acquaintance')."'>".
			  $DSP->input_radio('xfn_friendship',
			  					'acquaintance',
								( $xfn_friendship == 'acquaintance' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_acquaintance')).
								$LANG->line('url_xfn_acquaintance').
								"</label>".
								NBS.
								NBS.
								"<label for='".$LANG->line('url_xfn_friend')."'>".
			  $DSP->input_radio('xfn_friendship',
			  					'friend',
								( $xfn_friendship == 'friend' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_friend')).
								$LANG->line('url_xfn_friend').
								"</label>".
								NBS.
								NBS.
								"<label for='".$LANG->line('url_xfn_friendship').$LANG->line('url_xfn_none')."'>".
			  $DSP->input_radio('xfn_friendship',
			  					'None',
								( $xfn_friendship == 'None' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_friendship').$LANG->line('url_xfn_none')).
								$LANG->line('url_xfn_none').
								"</label>";

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellOne',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_xfn_friendship').$DSP->span_c(),
										$d)
									).
			  $DSP->tr_c();

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellTwo',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_xfn_physical').$DSP->span_c(),
										"<label for='".$LANG->line('url_xfn_met')."'>".
										$DSP->input_checkbox('xfn_met',
															'y',
															$xfn_met,
															'id='.$LANG->line('url_xfn_met')).
															$LANG->line('url_xfn_met').
															"</label>")
									).
			  $DSP->tr_c();


		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellOne',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_xfn_professional').$DSP->span_c(),
										"<label for='".$LANG->line('url_xfn_coworker')."'>".
										$DSP->input_checkbox('xfn_coworker',
															'y',
															$xfn_coworker,
															'id='.$LANG->line('url_xfn_coworker')).
										$LANG->line('url_xfn_coworker').
										"</label>".
										NBS.
										NBS.
										"<label for='".$LANG->line('url_xfn_colleague')."'>".
										$DSP->input_checkbox('xfn_colleague',
															'y',
															$xfn_colleague,
															'id='.$LANG->line('url_xfn_colleague')).
										$LANG->line('url_xfn_colleague').
										"</label>")
									).
			  $DSP->tr_c();

		$d  = "<label for='".$LANG->line('url_xfn_coresident')."'>".
			  $DSP->input_radio('xfn_geographical',
			  					'co-resident',
								( $xfn_geographical == 'Co-resident' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_coresident')).
			  $LANG->line('url_xfn_coresident').
			  "</label>".
			  NBS.
			  NBS.
			  "<label for='".$LANG->line('url_xfn_neighbor')."'>".
			  $DSP->input_radio('xfn_geographical',
			  					'neighbor',
								( $xfn_geographical == 'Neighbor' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_neighbor')).
			  $LANG->line('url_xfn_neighbor').
			  "</label>".
			  NBS.
			  NBS.
			  "<label for='".$LANG->line('url_xfn_geographical').$LANG->line('url_xfn_none')."'>".
			  $DSP->input_radio('xfn_geographical',
			  					'None',
								( $xfn_geographical == 'None' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_geographical').$LANG->line('url_xfn_none')).
			  $LANG->line('url_xfn_none').
			  "</label>";

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellTwo',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_xfn_geographical').$DSP->span_c(),
										$d)
									).
			  $DSP->tr_c();

		$d  = "<label for='".$LANG->line('url_xfn_child')."'>".
			  $DSP->input_radio('xfn_family',
			  					'child',
								( $xfn_family == 'Child' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_child')).
			  $LANG->line('url_xfn_child').
			  "</label>".
			  NBS.
			  NBS.
			  "<label for='".$LANG->line('url_xfn_parent')."'>".
			  $DSP->input_radio('xfn_family',
			  					'parent',
								( $xfn_family == 'Parent' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_parent')).
			  $LANG->line('url_xfn_parent').
			  "</label>".
			  NBS.
			  NBS.
			  "<label for='".$LANG->line('url_xfn_sibling')."'>".
			  $DSP->input_radio('xfn_family',
			  					'sibling',
								( $xfn_family == 'Sibling' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_sibling')).
			  $LANG->line('url_xfn_sibling').
			  "</label>".
			  NBS.
			  NBS.
			  "<label for='".$LANG->line('url_xfn_spouse')."'>".
			  $DSP->input_radio('xfn_family',
			  					'spouse',
								( $xfn_family == 'Spouse' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_spouse')).
			  $LANG->line('url_xfn_spouse').
			  "</label>".
			  NBS.
			  NBS.
			  "<label for='".$LANG->line('url_xfn_kin')."'>".
			  $DSP->input_radio('xfn_family',
			  					'kin',
								( $xfn_family == 'Kin' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_kin')).
			  $LANG->line('url_xfn_kin').
			  "</label>".
			  NBS.
			  NBS.
			  "<label for='".$LANG->line('url_xfn_family').$LANG->line('url_xfn_none')."'>".
			  $DSP->input_radio('xfn_family',
			  					'None',
								( $xfn_family == 'None' ) ? 1 : 0,
								'id='.$LANG->line('url_xfn_family').$LANG->line('url_xfn_none')).
			  $LANG->line('url_xfn_none').
			  "</label>";

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellOne',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_xfn_family').$DSP->span_c(),
										$d)
									).
			  $DSP->tr_c();

		$d  = "<label for='".$LANG->line('url_xfn_muse')."'>".
			  $DSP->input_checkbox('xfn_muse',
									'y',
									$xfn_muse,
									'id='.$LANG->line('url_xfn_muse')).
			  $LANG->line('url_xfn_muse').
			  "</label>".
			  NBS.
			  NBS.
			  "<label for='".$LANG->line('url_xfn_crush')."'>".
			  $DSP->input_checkbox('xfn_crush',
			  						'y',
									$xfn_crush,
									'id='.$LANG->line('url_xfn_crush')).
			  $LANG->line('url_xfn_crush').
			  "</label>".
			  NBS.
			  NBS.
			  "<label for='".$LANG->line('url_xfn_date')."'>".
			  $DSP->input_checkbox('xfn_date',
			  						'y',
									$xfn_date,
									'id='.$LANG->line('url_xfn_date')).
			  $LANG->line('url_xfn_date').
			  "</label>".
			  NBS.
			  NBS.
			  "<label for='".$LANG->line('url_xfn_sweetheart')."'>".
			  $DSP->input_checkbox('xfn_sweetheart',
			  						'y',
									$xfn_sweetheart,
									'id='.$LANG->line('url_xfn_sweetheart')).
			  $LANG->line('url_xfn_sweetheart').
			  "</label>";

		$r .= $DSP->tr();
		
		$r .= $DSP->table_qcell('tableCellTwo',
			  						array(
										$DSP->span('defaultBold').$LANG->line('url_xfn_romantic').$DSP->span_c(),
										$d)
									).
			  $DSP->tr_c();

		$r .= $DSP->table_c();

		$r .= $DSP->td_c().
			  $DSP->tr_c().
			  $DSP->table_c();
		
		$r .= "</div>";
			  
		$r .= BR.
			  $DSP->table().
			  $DSP->table_qrow('',$DSP->required('1')).
			  $DSP->table_c().
			  BR;

		if ( $url_id == '')
		{
			$r .= $DSP->input_submit($LANG->line('add_link'));
		}
		else
		{
			$r .= $DSP->input_submit($LANG->line('modify_link'));
		}
			  

		$r .= $DSP->form_c();


	
	
		$DSP->body = $r;
	
	}


	function update_links($msg='')
	{
		global $DB, $DSP, $LANG, $IN, $SESS;
		
		$linklist_id	=	( ! isset($_POST['linklist_id']) ? '' : trim($_POST['linklist_id']));
		$url_title		=	( ! isset($_POST['url_title']) ? '' : trim($_POST['url_title']));
		$url			=	( ! isset($_POST['url']) ? '' : trim($_POST['url']));
		$url_id 		= 	( ! isset($_POST['url_id']) ? '' : trim($_POST['url_id']));
		$bookmarklet	=	( ! isset($_POST['bookmarklet']) ? FALSE : TRUE);
		
		if ( $linklist_id == '' )
		{
			$DSP->body	.=	$DSP->error_message($LANG->line('invalid_linklist_id'));
			return;	
		}

		if ( $url_title == '' )
		{
			$DSP->body	.=	$DSP->error_message($LANG->line('invalid_url_title'));
			return;	
		}

		if ( $url == '' )
		{
			$DSP->body	.=	$DSP->error_message($LANG->line('invalid_url'));
			return;	
		}

		$data	=	array('url_title'		=> trim($url_title),
						  'linklist_id'		=> trim($linklist_id),
						  'url'				=> trim($url),
						  'url_rss'			=> ( isset($_POST['url_rss']) ) ? trim($_POST['url_rss']) : '',
						  'url_title'		=> trim($url_title),
						  'url_favicon'		=> ( isset($_POST['url_favicon']) ) ? trim($_POST['url_favicon']) : '',
						  'url_desc'		=> ( isset($_POST['url_desc']) ) ? trim($_POST['url_desc']) : '',
						  'url_added'		=> ( isset($_POST['url_added']) ) ? trim($_POST['url_added']) : 0,
						  'url_added_by_id' => ( isset($_POST['url_added_by_id']) ) ? trim($_POST['url_added_by_id']) : 0,
						  'url_status'		=> ( isset($_POST['url_status']) ) ? trim($_POST['url_status']) : 2,
						  'keywords'		=> ( isset($_POST['keywords']) ) ? trim($_POST['keywords']) : '',
						  'clickthru'		=> ( isset($_POST['clickthru']) ) ? trim($_POST['clickthru']) : 0,
						  'xfn_mine'		=> ( isset($_POST['xfn_mine']) ) ? trim($_POST['xfn_mine']) : 'n',
						  'xfn_friendship'	=> ( isset($_POST['xfn_friendship']) ) ? trim($_POST['xfn_friendship']) : 'None',
						  'xfn_met'			=> ( isset($_POST['xfn_met']) ) ? trim($_POST['xfn_met']) : 'n',
						  'xfn_coworker'	=> ( isset($_POST['xfn_coworker']) ) ? trim($_POST['xfn_coworker']) : 'n',
						  'xfn_colleague'	=> ( isset($_POST['xfn_colleague']) ) ? trim($_POST['xfn_colleague']) : 'n',
						  'xfn_geographical'=> ( isset($_POST['xfn_geographical']) ) ? trim($_POST['xfn_geographical']) : 'None',
						  'xfn_family'		=> ( isset($_POST['xfn_family']) ) ? trim($_POST['xfn_family']) : 'None',
						  'xfn_muse'		=> ( isset($_POST['xfn_muse']) ) ? trim($_POST['xfn_muse']) : 'n',
						  'xfn_crush'		=> ( isset($_POST['xfn_crush']) ) ? trim($_POST['xfn_crush']) : 'n',
						  'xfn_date'		=> ( isset($_POST['xfn_date']) ) ? trim($_POST['xfn_date']) : 'n',
						  'xfn_sweetheart'	=> ( isset($_POST['xfn_sweetheart']) ) ? trim($_POST['xfn_sweetheart']) : 'n'
						  );
		
		if ( isset($_POST['autodetect_rss']) )
		{
			$fetch_urls = $this->retrieve_rss_url($url);
			$data['url_rss'] = ( ! $data['url_rss'] == '' ) ? $data['url_rss'] : $fetch_urls['rssurl'];
			$data['url_favicon'] = ( ! $data['url_favicon'] == '' ) ? $data['url_favicon'] : $fetch_urls['faviconurl'];
		}
		
		if ( $url_id != '' )
		{
			$DB->query($DB->update_string('exp_linklist_urls', $data, 'url_id = '.$url_id));
			return $this->edit_links($LANG->line('url_updated'),$linklist_id);
		}
		else
		{
			// Now we have to figure out display order
			// first, let's figure out where they want to place it.
			// default it to last.
			$placement = $IN->GBL('disporder');
			if ( $placement == 'first' )
			{
				$sql = "SELECT url_id FROM exp_linklist_urls WHERE linklist_id = ".$linklist_id." ORDER BY disporder";
				$query = $DB->query($sql);
				$count = 1;
				foreach ( $query->result as $row )
				{
					$DB->query("UPDATE exp_linklist_urls SET disporder = ".$count." WHERE url_id = ".$row['url_id']);
					$count++;
				}
				$data['disporder'] = 0;
			}
			elseif ( is_numeric($placement) )
			{
				$totalrecords = $this->get_linklist_count($linklist_id);
				$sql = "SELECT disporder FROM exp_linklist_urls WHERE linklist_id = ".$linklist_id." AND url_id = ".$placement;
				$query = $DB->query($sql);
				$currOrder = $query->row['disporder'];
				$newOrder = $currOrder + 1;
				if ( $newOrder > $totalrecords )
				{
					$data['disporder'] = $totalrecords + 1;
				}
				else
				{
					$sql = "SELECT url_id FROM exp_linklist_urls WHERE linklist_id = ".$linklist_id." AND disporder >= ".$newOrder." ORDER BY disporder";
					$query = $DB->query($sql);
					$count = $newOrder + 1;
					foreach ( $query->result as $row )
					{
						$DB->query("UPDATE exp_linklist_urls SET disporder = ".$count." WHERE url_id = ".$row['url_id']);
						$count++;
					}
					$data['disporder'] = $newOrder;
				}
			}
			else
			{
				$sql = "SELECT MAX(disporder) + 1 AS newrow FROM exp_linklist_urls WHERE linklist_id = ".$linklist_id;
				$query = $DB->query($sql);
				if ( $query->row['newrow'] != NULL )
				{
					$data['disporder'] = $query->row['newrow'];
				}
				else
				{
					$data['disporder'] = 0;
				}
			}
			
			$DB->query($DB->insert_string('exp_linklist_urls', $data));
			// If this was a bookmarklet, then go back to where they were.
			if ( $bookmarklet )
			{
				header("Location:".$url);
				exit();
			}
			else
			{
				return $this->edit_links($LANG->line('url_added'),$linklist_id);
			}
		}
		
		
	}

	function delete_link()
	{
		global $DSP, $LANG, $IN, $DB;

		// Check for valid data
		$linklist_id = (! isset($_POST['linklist_id']) ? '' : trim($_POST['linklist_id']));
		$url_id = ( ! isset($_POST['url_id']) ? '' : $_POST['url_id']);

		if ( $linklist_id == '' )
		{
			$DSP->body	.= $DSP->error_message($LANG->line('invalid_linklist_id'));
			return;
		}

		if ( $url_id == '' )
		{
			$DSP->body	.= $DSP->error_message($LANG->line('invalid_url_id'));
			return;
		}

		$url_query = '(';
		
		foreach( $url_id as $val )
		{
			$url_query .= $val.',';
		}
		
		$url_query = substr( $url_query, 0, -1 );
		$url_query .= ')';
				
		$sql = 'DELETE from exp_linklist_urls where url_id IN '.$url_query;
		$DB->query($sql);
		
		return $this->edit_links( $LANG->line('link_deleted'),$linklist_id);
	}

	function move_link()
	{
		global $IN, $DB, $LANG;
		
		// Check for valid data
		$linklist_id = ( ! isset($_POST['linklist_id']) ? '' : $_POST['linklist_id'] );
		$new_linklist_id = ( ! isset($_POST['new_linklist_id']) ? '' : $_POST['new_linklist_id'] );
		$url_id = ( ! isset($_POST['url_id']) ? '' : $_POST['url_id'] );
		
		if ( $linklist_id == '' )
		{
			$DSP->body	.= $DSP->error_message($LANG->line('invalid_linklist_id'));
			return;
		}
		
		if ( $new_linklist_id == '' )
		{
			$DSP->body	.= $DSP->error_message($LANG->line('invalid_linklist_id'));
			return;
		}

		if ( $url_id == '' )
		{
			$DSP->body	.= $DSP->error_message($LANG->line('invalid_url_id'));
			return;
		}

		$url_query = '(';
		
		foreach( $url_id as $val )
		{
			$url_query .= $val.',';
		}
		
		$url_query = substr( $url_query, 0, -1 );
		$url_query .= ')';
		
		$sql = 'UPDATE exp_linklist_urls SET linklist_id = '.$new_linklist_id.' WHERE url_id IN '.$url_query;
		$DB->query($sql);
		
		return $this->edit_links( $LANG->line('link_moved'),$linklist_id);
		
	}

	function post_link()
	{
		global $TMPL, $IN, $DB, $DSP, $LANG;
		
		// check the values
		$linklist_id = $IN->GBL('linklist_id','POST');
		$url_id = $IN->GBL('toggle','POST');
		$action = ( ! isset($_POST['delete']) ) ? ( ! isset($_POST['move']) ? ( ! isset($_POST['rss']) ? '' : 'rss'  ) : 'move' ) : 'delete';
		
		if ( $action == '' )
		{
			$DSP->body .= $DSP->error_message($LANG->line('no_action'));
			return;
		}
		
		if ( $url_id == '' )
		{
			$DSP->body .= $DSP->error_message($LANG->line('no_url_id'));
			return;
		}
		
		if ( $linklist_id == '' )
		{
			$DSP->body .= $DSP->error_message($LANG->line('invalid_linklist_id'));
			return;
		}

		$DSP->title = $LANG->line('linklist_module_name');
		$DSP->crumb = $DSP->anchor(BASE.
								   AMP.'C=modules'.
								   AMP.'M=linklist',
								   $LANG->line('linklist_module_name'));        
		$DSP->crumb .= $DSP->crumb_item($DSP->anchor(BASE.
										AMP.'C=modules'.
										AMP.'M=linklist'.
										AMP.'P=edit_links'.
										AMP.'linklist_id='.
										$linklist_id,
										$LANG->line('edit_links')));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('delete_link'));

		$url_query = '(';
		
		foreach( $url_id as $val )
		{
			$url_query .= $val.',';
		}
		
		$url_query = substr( $url_query, 0, -1 );
		$url_query .= ')';

		if ( $action == 'move' )
		{
	        $r	= $DSP->heading($LANG->line('move_link')); 
			$r .= $DSP->qdiv('itemWrapper', $LANG->line('confirm_move_link')).BR;
		}
		elseif ( $action == 'delete' )
		{
			$r	= $DSP->heading($LANG->line('delete_link')); 
			$r .= $DSP->qdiv('itemWrapper', $LANG->line('confirm_delete_link')).BR;
		}
		elseif ( $action == 'rss' )
		{
			$sql = 'SELECT * FROM exp_linklist_urls WHERE url_id IN '.$url_query;
			$query = $DB->query($sql);
			foreach ( $query->result as $row )
			{
				if ( $row['url_rss'] == '' || $row['url_favicon'] == '')
				{
					$fetch_urls = $this->retrieve_rss_url($row['url']);
					$url_rss = ( ! $row['url_rss'] == '' ) ? $row['url_rss'] : $fetch_urls['rssurl'];
					$url_favicon = ( ! $row['url_favicon'] == '' ) ? $row['url_favicon'] : $fetch_urls['faviconurl'];
					$data = array ( 
									'url_rss' => $url_rss,
									'url_favicon' => $url_favicon
					);
					$DB->query($DB->update_string('exp_linklist_urls', $data, 'url_id = '.$row['url_id']));
				}
			}
			return $this->edit_links($LANG->line('success_rss'),$linklist_id);
		}
	
		$sql	= 'SELECT url_title FROM exp_linklist_urls WHERE url_id IN '.$url_query;
		
		$query	= $DB->query($sql);

		$url_title = '';

		foreach ( $query->result as $row )
		{
			$url_title	.= $row['url_title'].BR;
		}
		$r .= $DSP->qdiv('itemWrapper', '<i>'.$url_title.'</i>').BR;

		if ( $action == 'move' )
		{
			$r .= $DSP->form('C=modules'.AMP.'M=linklist'.AMP.'P=move_link');
			$r .= $this->get_linklist_dropdown();
			$r .= $DSP->input_submit($LANG->line('move'));
		}			
		elseif ( $action == 'delete' )
		{
			$r .= $DSP->qdiv('alert', $LANG->line('cannot_be_undone')).BR;
			$r .= $DSP->form('C=modules'.AMP.'M=linklist'.AMP.'P=delete_link');
			$r .= $DSP->input_submit($LANG->line('delete'));
		}
		$r .= $DSP->input_hidden('linklist_id', $linklist_id);
		
		foreach ( $url_id as $value )
		{
			$r .= $DSP->input_hidden('url_id[]', $value);
		}

		$r .= $DSP->form_c();

		$DSP->body	.= $r;
	}


	function get_linklist_dropdown()
	{
		global $DB, $SESS, $DSP, $LANG;
		
		// Get list of linklists 
		$sql = 'SELECT * FROM exp_linklist WHERE member_id = '.$SESS->userdata['member_id'];
		
		$query = $DB->query($sql);
		$s = $DSP->input_select_header('new_linklist_id','',1).
			 $DSP->input_select_option('',$LANG->line('choose_linklist'));
		
		foreach ( $query->result as $row )
		{
			$s .= $DSP->input_select_option($row['linklist_id'],$row['linklist_name']);
		}
		
		$s .= $DSP->input_select_footer();
			 
		return $s;
	}

	function get_linklist_count($linklist_id='0')
	{
		global $DB;
		
		$sql = 'SELECT url_id FROM exp_linklist_urls WHERE linklist_id = '.$linklist_id;
		
		$query = $DB->query($sql);
		
		return $query->num_rows;
	}

	function retrieve_favicon_url($url='')
	{
	
	
		return $url;
	}
	
	function retrieve_rss_url($site='')
	{
		$rssurl = "";
		$faviconurl = "";
		
		if ( $site == "" )
		{
			return FALSE;
		}
	
		if ( ! $links = $this->parse_file($site,'','HTML') )
		{
			return FALSE;
		}
		
		foreach ( $links as $row )
		{
			if ( strtolower($row['rel']) == "alternate" )
			{
				if ( strtolower($row['type']) == "application/rss+xml" || strtolower($row['type']) == "application/rss+atom")
				{
					$rssurl = $row['href'];
				}
			}
			if ( strtolower($row['rel']) == "shortcut icon" )
			{
				$faviconurl = $row['href'];
			}
		}
		
		return array ( 
						"rssurl" 		=> $rssurl,
						"faviconurl"	=>	$faviconurl
		);
	}
	
	function jump_url()
	{
	
	}
	
	function jump_bm()
	{
	
	}
	
	function create_bookmarklet()
	{
		global $DSP, $LANG, $DB, $SESS, $PREFS, $IN;

		$DSP->title = $LANG->line('linklist_module_name');
        $DSP->crumb = $DSP->anchor(BASE.
                                   AMP.'C=modules'.
                                   AMP.'M=linklist',
                                   $LANG->line('linklist_module_name'));        
        $DSP->crumb .= $DSP->crumb_item($LANG->line('bookmarklet'));
	
		if ( $IN->GBL('create','POST') )
		{
			$linklist_id = $IN->GBL('linklist_id','POST');
			$bm_name = $IN->GBL('bm_name','POST');
			$path = $PREFS->ini('cp_url').'?C=modules'.AMP.'M=linklist'.AMP.'BK=1'.AMP.'P=add_link'.AMP.'linklist_id='.$linklist_id.AMP;
			$replacetext = "bmTitle=document.title;bmTitle=bmTitle.replace('?',':qm:');bmTitle=bmTitle.replace('$',':ds:');bmHref=window.location.href;bmHref=bmHref.replace('?',':qm:');bmHref=bmHref.replace('$',':ds:');";
			$bookmarklet = "<a href=\"javascript:".$replacetext."location.href='".$path."url_title='+encodeURIComponent(bmTitle)+'&url='+encodeURIComponent(bmHref)\">".$bm_name."</a>";

			$r  = $DSP->table('tableBorder', '0', '0', '100%').
				  $DSP->tr().
				  $DSP->td('tablePad');
				  
			$d  = $DSP->qdiv('success',$LANG->line('bm_created')).
				  $DSP->qdiv('itemWrapper',$LANG->line('save_bm_to_bar')).
				  $DSP->qdiv('defaultBold',$bookmarklet);
				  
			$r .= $DSP->table('','0','0','100%').
				  $DSP->tr().
				  $DSP->table_qcell('tableHeading',$LANG->line('bookmarklet')).
				  $DSP->tr_c();
				  
			$r .= $DSP->tr().
				  $DSP->td('tableCellPlain').
				  $d.
				  $DSP->td_c().
				  $DSP->tr_c().
				  $DSP->table_c();
				  
			$r .= $DSP->td_c().
				  $DSP->tr_c().
				  $DSP->table_c();
				  
			$DSP->body = $r;
		}
		else
		{
			$r  = $DSP->form('C=modules'.AMP.'M=linklist'.AMP.'P=create_bm','','post','enctype="multipart/form-data"');
			
			$r .= $DSP->table('tableBorder', '0', '0', '100%').
				  $DSP->tr().
				  $DSP->td('tablePad');

			$d  = $DSP->qspan('defaultBold',$LANG->line('bm_name')).BR.BR.
				  $LANG->line('linklist_name_spec').BR.BR.
				  $DSP->input_text('bm_name','Bookmarklet','15','30','input','150px').BR.BR.
				  BR.
				  $LANG->line('bm_choose_linklist').BR.BR.
				  $DSP->input_select_header('linklist_id',0,1);
	
			$sql = 'SELECT * FROM exp_linklist';
			$query = $DB->query($sql);
				  
			foreach ( $query->result as $row )
			{
				$d .= $DSP->input_select_option($row['linklist_id'],$row['linklist_title'],'n');
			}
				  
			$d .= $DSP->input_select_footer();
				  
			$r .= $DSP->table('','0','0','100%').
				  $DSP->tr().
				  $DSP->table_qcell('tableHeading',$LANG->line('bookmarklet')).
				  $DSP->tr_c();
				  
			$r .= $DSP->tr().
				  $DSP->td('tableCellPlain').
				  $d.
				  $DSP->td_c().
				  $DSP->tr_c().
				  $DSP->table_c();
				  
	
			$r .= $DSP->td_c().
				  $DSP->tr_c().
				  $DSP->table_c();	
			$r .= $DSP->input_submit($LANG->line('create_bm'),'create');
			
			$r .= $DSP->form_c();
		
			$DSP->body = $r;
		}	

	}
	
	function import_opml_step1()
	{
		global $DSP, $LANG, $IN, $DB, $SESS;
		
		$linklist_id = ( ! $IN->GBL('linklist_id') ) ? '' : $IN->GBL('linklist_id');
		
		if ( $linklist_id == '' )
		{
			$DSP->body = $DSP->error_message($LANG->line('invalid_linklist_id'));
			return;
		}
		
		$DSP->title = $LANG->line('linklist_module_name');
        $DSP->crumb = $DSP->anchor(BASE.
                                   AMP.'C=modules'.
                                   AMP.'M=linklist',
                                   $LANG->line('linklist_module_name'));
		$DSP->crumb .= $DSP->crumb_item($DSP->anchor(BASE.
										AMP.'C=modules'.
										AMP.'M=linklist'.
										AMP.'P=edit_links'.
										AMP.'linklist_id='.
										$linklist_id,
										$LANG->line('edit_links')));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('import_opml'));
	
		$r  = $DSP->form('C=modules'.AMP.'M=linklist'.AMP.'P=import_step2','','post','enctype="multipart/form-data"');
		
		$r .= $DSP->input_hidden('linklist_id',$linklist_id);
		
		$r .= $DSP->table('tableBorder', '0', '0', '100%').
			  $DSP->tr().
			  $DSP->td('tablePad');
			  
		$r .= $DSP->table('', '0').
			  $DSP->tr().
			  $DSP->td('tableHeading','','2').
			  $DSP->span('defaultBold').
			  $LANG->line('import_opml').
			  $DSP->span_c().
			  BR.
			  $LANG->line('opml_import_instructions').
			  $DSP->td_c().
			  $DSP->tr_c();
	
		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellTwo',
			  						array(	
										$DSP->span('defaultBold').
										$LANG->line('opml_url').
										$DSP->span_c().
										BR.
										$LANG->line('opml_blogrolling_instructions'),
										$DSP->input_text('opml_url',
															'',
															'30',
															'255',
															'input',
															'')
										)).
			  $DSP->tr_c();
			
		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellOne',
			  						array(	
										$DSP->span('defaultBold').
										$LANG->line('opml_upload').
										$DSP->span_c().
										BR.
										$LANG->line('opml_upload_instructions'),
										"<input type='file' name='userfile' size='40'>"
										)).
			  $DSP->tr_c();
			  
		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellTwo',
			  						array(
			  							$DSP->span('defaultBold').
			  							$LANG->line('auto_detect_rss').
			  							$DSP->span_c().
			  							BR.
			  							$LANG->line('opml_autodetect_instructions'),
			  							$DSP->input_checkbox('autodetect_rss','y',0))).
			  $DSP->tr_c();

		$r .= $DSP->table_c();
		
		$r .= $DSP->td_c().
			  $DSP->tr_c().
			  $DSP->table_c();

		$r .= $DSP->input_submit($LANG->line('opml_import'));
		
		$r .= $DSP->form_c();
	
		$DSP->body = $r;
	
	}

	function import_opml_step2()
	{
		global $DSP, $LANG, $IN, $DB;

		$linklist_id = $IN->GBL('linklist_id','POST');

		require PATH_CORE.'core.upload'.EXT;

		$UP = new Upload();
		
		$r = '';
		
		if ($UP->set_upload_path(PATH_CACHE) !== TRUE)
		{
			$DSP->body .= $DSP->error_message($LANG->line('upload_path_error'));
			return;
		}
		
		$UP->set_allowed_types('all');
		
		if ( ! $UP->upload_file() )
		{
			if ( $UP->error_msg == 'invalid_filetype' )
			{
				$DSP->body .= $DSP->error_message($LANG->line($UP->error_msg));
				return;
			}
			elseif ( $IN->GBL('opml_url','POST') == '' && $UP->error_msg == 'no_file_selected' )
			{
				$DSP->body .= $DSP->error_message($LANG->line('import_nothing_specified'));
				return;
			}
			else
			{
				$file_name = $IN->GBL('opml_url','POST');
			}
		}
		else
		{
			$file_name = PATH_CACHE.$UP->file_name;
		}
		
		if ( ! $links = $this->parse_file($file_name,$linklist_id) )
		{
			$DSP->body .= $DSP->error_message($LANG->line('unexpected_error'));
			return;
		}
		
		$sql = "SELECT MAX(disporder) + 1 AS newrow FROM exp_linklist_urls WHERE linklist_id = ".$linklist_id;
		$query = $DB->query($sql);
		if ( $query->row['newrow'] != NULL )
		{
			$count = $query->row['newrow'];
		}
		else
		{
			$count = 0;
		}
		foreach ($links as $row)
		{
			// Do we want to autodetect the RSS?
			if ( isset($_POST['autodetect_rss']) )
			{
				if ( $row['url_rss'] == '' )
				{
					$fetch_urls = $this->retrieve_rss_url($row['url']);
					$row['url_rss'] = $fetch_urls['rssurl'];
					$row['url_favicon'] = $fetch_urls['faviconurl'];
				}
			}
			$row['disporder'] = $count;
			$sql = $DB->insert_string('exp_linklist_urls',$row);
			$DB->query($sql);
			$count++;
		}
				
		// clean up. if the user uploaded a file, let's get rid of it to prevent clutter basically anything crazy.
		
		if ( ! eregi("http://",$file_name) )
		{
			@unlink($file_name);
		}
		
		
		$this->edit_links($LANG->line('opml_import_success'),$linklist_id);
	}

	function checkUpdated($site="")
	{
		if ( $site == "" )
		{
			return FALSE;
		}
		
		return TRUE;
	}

	
	function parse_file($file='',$id='',$type='OPML')
	{
		if ( $file == '' )
		{
			return FALSE;
		}
		$PAR = new Parser;
		$PAR->parse($file,$id,$type);
		return $PAR->links;
	}
	
	function upgrade()
	{
		global $DB, $LANG;
		// First we need to know what version they are now.
		$query = $DB->query("SELECT * FROM exp_modules WHERE module_name = 'Linklist'");
		// Update the version. The version will be upgraded, regardless of what the prior version was.
		$sql[] = "UPDATE exp_modules SET module_version = '".$this->version."' WHERE module_id = ".$query->row['module_id'];
		
		// Regardless of the version, we will verify and see if a field exists. if it doesn't, then add it.
		// We also need to add/delete certain things within the exp_actions table.
		// But we should verify that they don't exist before inserting them.
		$sql[] = "DELETE FROM exp_actions WHERE class='Linklist' AND method='insert_new_link'";
		$query2 = $DB->query("SELECT * FROM exp_actions WHERE class='Linklist' AND method='jump_url'");
		if ( $query2->num_rows == 0 ) $sql[] = "INSERT INTO exp_actions (action_id, class, method) VALUES ('', 'Linklist','jump_url')";
		$query2 = $DB->query("DESCRIBE exp_linklist_urls 'last_updated'");
		if ( $query2->num_rows == 0 ) $sql[] = "ALTER TABLE exp_linklist_urls ADD last_updated INT(11) DEFAULT '0' NOT NULL AFTER updated";
		$query2 = $DB->query("DESCRIBE exp_linklist 'recently_updated'");
		if ( $query2->num_rows == 0 ) $sql[] = "ALTER TABLE exp_linklist ADD recently_updated INT(5) UNSIGNED DEFAULT '12' NOT NULL";
		$query2 = $DB->query("DESCRIBE exp_linklist_urls 'clickthru'");
		if ( $query2->num_rows == 0 ) $sql[] = "ALTER TABLE exp_linklist_urls ADD clickthru INT(20) NOT NULL AFTER updated";
		
		$query2 = $DB->query("DESCRIBE exp_linklist_urls 'disporder'");
		if ( $query2->num_rows == 0 )
		{
			$sql[] = "ALTER TABLE exp_linklist_urls ADD disporder INT(10) UNSIGNED NOT NULL AFTER last_updated";
			$updatedisporder = TRUE;
		}
		else
		{
			$updatedisporder = FALSE;
		}

		foreach ( $sql as $query )
		{
			$DB->query($query);
		}

		if ( $updatedisporder )
		{
			// this small routine here will update the linklist urls to be "in order" so that the order arrows will work.
			$query = $DB->query("SELECT DISTINCT linklist_id FROM exp_linklist");
			foreach ( $query->result as $row )
			{
				$query2 = $DB->query("SELECT url_id,disporder FROM exp_linklist_urls WHERE linklist_id = ".$row['linklist_id']);
				$count = 0;
				foreach ( $query2->result as $row2 )
				{
					$DB->query("UPDATE exp_linklist_urls SET disporder = ".$count." WHERE url_id = ".$row2['url_id']);
					$count++;
				}
			}
		}
		
		return $this->linklist_home($LANG->line('upgrade_complete'));
	}
	
	function order_links()
	{
		global $IN, $DB, $DSP;
		
		$linklist_id = $IN->GBL('linklist_id');
		$url_id = $IN->GBL('url_id');
		$order = $IN->GBL('order');
		
		$sql = "SELECT disporder FROM exp_linklist_urls WHERE url_id = ".$url_id;
		$query = $DB->query($sql);
		$currOrder = $query->row['disporder'];
		if ( $order == 'up' )
		{
			$newOrder = $currOrder - 1;
			if ( $newOrder >= 0 )
			{
				$sql = "SELECT url_id FROM exp_linklist_urls WHERE linklist_id = ".$linklist_id." AND disporder = ".$newOrder;
				$query = $DB->query($sql);
				$next_url_id = $query->row['url_id'];
				$DB->query("UPDATE exp_linklist_urls SET disporder = ".$newOrder." WHERE url_id = ".$url_id);
				$DB->query("UPDATE exp_linklist_urls SET disporder = ".$currOrder." WHERE url_id = ".$next_url_id);
			}
		}
		elseif ( $order == 'down' )
		{
			$newOrder = $currOrder + 1;
			$sql = "SELECT url_id FROM exp_linklist_urls WHERE linklist_id = ".$linklist_id." AND disporder = ".$newOrder;
			$query = $DB->query($sql);
			if ( $query->num_rows > 0 )
			{
				$next_url_id = $query->row['url_id'];
				$DB->query("UPDATE exp_linklist_urls SET disporder = ".$newOrder." WHERE url_id = ".$url_id);
				$DB->query("UPDATE exp_linklist_urls SET disporder = ".$currOrder." WHERE url_id = ".$next_url_id);
			}
		}
		return $this->edit_links('',$linklist_id);
	}
	
	
	
	
	
	
	
	
	
	
	
	

	function reference()
	{
		global $DSP, $LANG;

		$DSP->title = $LANG->line('linklist_module_name');
        $DSP->crumb = $DSP->anchor(BASE.
                                   AMP.'C=modules'.
                                   AMP.'M=linklist',
                                   $LANG->line('linklist_module_name'));        
        $DSP->crumb .= $DSP->crumb_item($LANG->line('reference'));    
		ob_start();
?>
<div id="linklist_container">

<h2 class="tableHeading"><span>Expression Engine Module : Link List Documentation : Version <?php echo $this->version; ?></span></h2>

<div id="navigation">

</div>

<div id="mainbody">

<p>
The Link List module organizes your links to other websites. With the Link List module, you can add/edit/delete links, display links on any webpage. Link List is XFN friendly as well. For more information about XFN, please visit their website: <a href="http://gmpg.org/xfn/">http://gmpg.org/xfn/</a>.
</p>
<br />
<p>
This module is being released under the <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>. Please read the <a href="#licensebox">License Information</a>.
</p>
<br />
<p>
Please report your bugs at pd-x : ee-blog ( <a href="http://www.psychodaisy.com/index.php/ee/index">http://www.psychodaisy.com/index.php/ee/index</a> ) or pd-x : the forums ( <a href="http://www.psychodaisy.com/index.php/forums">http://www.psychodaisy.com/index.php/forums</a> ).
</p>
<br />
<p>
<em><strong>Notes about using the recently updated feature:</strong></em> This feature will only work with links which have an RSS URL. There are many reasons for this. The foremost reason would be that without actually spidering every single site on your linklist and analyzing each page, there's really no way to know if a site has been updated. Honestly, that kind of analysis is beyond this mere module. Another option of course, is to use the same technique displayed in blogrolling or weblogs.com, which would be insane since it would kill your server with pings. (provided all those people in the world pinged you!) Yet another option is to parse the weblogs.com or blogrolling changes.xml. This was done in the past, and I have yet to come up with a good way to do it. If I recall correctly, blogrolling tried to do this with weblogs.com changes.xml file, and eventually, they just started their own ping server.
</p>
<br />
<p>
So in order to keep updated with your links, you should be sure to run the <a href="#linklist_checkupdated">{exp:linklist:checkupdated} tag</a> at a regular interval. Remember that the tag only checks EACH link once an hour. (Usually this is acceptable to do, anything more often might put you on a blacklist.) So if you have 100 links, and by default, the checkupdated tag checks 5 sites at a time, (you can increase or lower this by using the <a href="#param_checkupdated_limit">limit</a> parameter) then it will take approximately 20 hits to the checkupdated tag in order to process all your links. I say 20 hits, because it will roll to the next batch whenever you run it again. If you were to run the checkupdated tag every hour, then it would take 20 hours.
</p>
<br />
<p>
Honestly, I recommend running no more than 5 links at a time (default) if you run the script as part of a template. If you are able to set up a cron event or scheduled task, then I recommend placing the tag there, and schedule it as often as you can run it. I'd almost recommend setting the limit higher than 5. You can play around with this value and determine what is best for you.
</p>

<h2 class="tableHeading">HOW TO INSTALL</h2>

<h4>1. Upload/copy your files</h4>

<p>The files you need are already located in their own directories
that they need to be in. simply copy the lang.linklist.php file into
the /system/language/english folder, and the mod.linklist.php and
mcp.linklist.php files into the /system/modules/linklist/ folder.
You can also simply unzip them into your system folder as well,
though I do recommend using caution with this.</p>

<h4>2. Install the module</h4>

<p>In your Control Panel, go to Modules and then you should see
the Linklist module listed as "Not Installed". All you need to do is
click on Install, and then you should be on your way!</p>

<p>By clicking on the module name, "Link List" you will be able
to add/edit/delete links and link lists.</p>

<h2 class="tableHeading">HOW TO UPGRADE</h2>

<h4>1. Upload/copy your files</h4>

<p>
Upload your files, overwriting the existing files on your server.
</p>

<h4>2. Run the upgrade script</h4>

<p>
After copying your files to the server, simply go into Modules, then select Linklist. The Linklist should present itself by telling you you are running an older version, and click to upgrade. Go ahead and click to upgrade. Then that's it! The script will autodetect what version you're currently running and upgrade itself appropriately.
</p>

<h2 class="tableHeading">HOW TO USE</h2>

<p>The Link List is basically broken up into 2 different parts.</p>

<p>You have the main Link Lists and you have individual links that are a part of each Link List.</p>

<p>When you create a new Link List ( Add New Link List ), you enter the name of your list and a description (or title).</p>

<p>You should now see the Link List and you can click on the linklist name to add/edit/delete Links to that list.</p>

<h2 class="tableHeading">Keywords</h2>

<p>
I feel this feature has a right to have it's own section, so I'll explain how keywords (or tags, as some of you may call them) work.
</p>

<p>
When you edit a link, you will notice a field called "Keywords". These keywords should be separated by commas, and may contain spaces. An example would be: "expression engine, pmachine, blog, reference" (without the quotes). These keywords are used by the linklist to display only links associated by keyword. (should you choose to.)
<br /><br />
Using keywords, you will be able to group your links together in a fashion very similar to del.ico.us. Basically keywords are a way to organize and share your links. (see below -- {exp:linklist:keywords})
<br /><br />
Linklist results can be influenced by either specifying the keywords in the parameter of the {exp:linklist:entries} tag OR by the URL of the page (dynamic="on"). Please read the parameter functions for more details.
</p>

<h2 class="tableHeading" id="toc">Contents</h2>

<p>
	<a href="#linklist">{exp:linklist:entries} tag</a>
	<br />
	<a href="#linklist_checkupdated">{exp:linklist:checkupdated} tag</a>
	<br />
	<a href="#linklist_keywords">{exp:linklist:keywords} tag</a>
	<br />
	<a href="#troubleshooting">Troubleshooting</a>
	<br />
	<a href="#tipsandtricks">Tips and Tricks</a>
</p>

<h2 class="tableHeading" id="linklist">{exp:linklist:entries}</h2>

<p>
<a href="#parameter_top">parameters</a>
<br />
<a href="#variables_top">variables</a>
<br />
<a href="#variable_pairs_top">variable pairs</a>
<br />
<a href="#conditionals_top">Conditionals</a>
</p>

<p>
Within your Expression Engine Templates you may use the {exp:linklist:entries} tag. This tag is the core of this module. Here is an example showing you in action:
</p>

<div class="codeview"><pre>
{exp:linklist:entries linklist="mylinks|newlinks" orderby="url_title" sort="ASC" status="open" limit="10"}
{list_heading}
&lt;h2&gt;{linklist_title}&lt;/h2&gt;
{/list_heading}
{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
{/exp:linklist:entries}
</pre></div>

<h2 class="tableCellOne" id="parameter_top">Parameters</h2>

<p>
	<a href="#param_linklist">linklist="linklist"</a><br />
	<a href="#param_member_id">member_id="1"</a><br />
	<a href="#param_orderby">orderby="url_title"</a><br />
	<a href="#param_sort">sort="ASC"</a><br />
	<a href="#param_status">status="open"</a><br />
	<a href="#param_limit">limit=10</a><br />
	<a href="#param_offset">offset=10</a><br />
	<a href="#param_dynamic">dynamic="on"</a><br />
	<a href="#param_keywords">keywords="keyword1|keyword2"</a><br />
	<a href="#param_paginate">paginate="bottom"</a><br />
	<a href="#param_backspace">backspace="1"</a>
</p>


<p id="param_linklist">
	<h4>linklist="linklist"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	Choose which linklist you will be displaying links from. If you omit this parameter, all linklists will be displayed in order. You can also opt to display only certain linklists using pipe character:
	<br />
	<div class="codeview"><pre>linklist = "mylinks|newlinks"</pre></div>
	<br />
	Choosing <em>not</em> will display any linklists that are NOT in the list you provide:
	<br />
	<div class="codeview"><pre>linklist = "not mylinks|oldlinks"</pre></div>
</p>

<p id="param_member_id">
	<h4>member_id="1"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	Chooses what user's linklist to retrieve. If you omit this parameter, the linklist will assume all users. This can be very useful when you want to display linklists only for certain users.
	<br /><br />
	Like the linklist parameter, you can use the pipe character as well as the <em>not</em>.
	<br />
	<div class="codeview"><pre>member_id = "1|3|4"</pre></div>
</p>

<p id="param_orderby">
	<h4>orderby="url_title"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	This will order the list of links in the order you specify. eg., this will sort the list in the order of the links being added, then by the title:
	<br />
	<div class="codeview"><pre>orderby="url_added,url_title"</pre></div>
	<br /><br />
	You may additionally specify the list to show the links in random order:
	<br />
	<div class="codeview"><pre>orderby="random"</pre></div>
	<br /><br />
	The default orderby is by Linklist ID, Display Order. (orderby="linklist_id,disporder")
</p>

<p id="param_sort">
	<h4>sort="ASC"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	This determines what sort order the orderby will be in. If you do not specify this parameter, it defaults to ascending (ASC).
</p>

<p id="param_status">
	<h4>status="open"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	Each individual link has a status associated to it, "open", "closed", and "pending". This parameter allows you to specify what status to show. You may also use the pipe character to specify more than one option:
	<br />
	<div class="codeview"><pre>status="open|pending"</pre></div>
</p>

<p id="param_limit">
	<h4>limit="10"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	This will limit the results to the number you specify. Please note this option limits the number of links returned for that one result set, and does not limit by linklist. i.e. if you specified 2 linklists that had 10 links each, and specified a limit of 10, it will show only the first ten links from the first linklist, and not show any from the 2nd linklist.
	<br />
	<br />
	However, let's say that you wish to show 5 links from those 2 linklists. It's perfectly acceptable to have multiple linklist tags per template:
	<br />
	<div class="codeview"><pre>
		{exp:linklist:entries linklist="mylinks" limit="5"}
		{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
		{/exp:linklist:entries}
		{exp:linklist:entries linklist="newlinks" limit="5"}
		{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
		{/exp:linklist:entries}
	</pre></div>
</p>

<p id="param_limit">
	<h4>offset="10"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	Used in conjunction with the limit parameter, with offset, you can offset the start record by a certain number of links. So if you specified, limit=10 offset=10, this will start on the 10th link and show 10 links. This is especially useful in a scenario when you want to display your links in multiple columns.
	<br />
	<br />
	Example:
	<br />
	<div class="codeview"><pre>
		{exp:linklist:entries linklist="mylinks" limit="5" offset="5"}
		{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
		{/exp:linklist:entries}
		{exp:linklist:entries linklist="newlinks" limit="5"}
		{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
		{/exp:linklist:entries}
	</pre></div>
</p>

<p id="param_dynamic">
	<h4>dynamic="on"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	Dynamic tells the linklist to either gather information from your URL or not to gather information from the URL. By default, dynamic is set to "on", meaning it will use segments in your URL to dynamically display your linklist. Linklist looks in particular for segment 3. i.e. if you're URL is: http://www.yourdomain.com/index.php/weblog/index/keyword then segment 3 is "keyword". The third segment is used for queries associated with keywords. If you do not want the linklist to change it's results because of the URL, then specify dynamic="off" and it will not be influenced at all.
	<br />
	<br />
	By default segment 3 of the URL will be used as the primary "keyword". This enables you specify a URL that will show for instance all the links with the keyword "expression engine". You're URL in this case would be http://www.yourdomain.com/index.php/weblog/template/expression_engine. (please note that underscores are equivalents to spaces in the URL.)
</p>

<p id="param_keywords">
	<h4>keywords="keyword1|keyword2"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	The keywords param allows you to specify exactly what links to display via keywords. If you wanted to display all links with the keywords "expression engine" and "pmachine", then the parameter would be: keywords="expression engine|pmachine". In this case, you may use the space and not use an underscore, as in the URL. This is permitted, although either way will work.
</p>

<p id="param_paginate">
	<h4>paginate="bottom"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	By default, paginate is set to bottom. if you omit this parameter and include the {paginate}..{/paginate} variable pair, the pagination links will appear towards the bottom. You can also set this to paginate="top" to include the pagination links above your links to display. View the paginate variable pair for an example.
</p>

<p id="param_backspace">
	<h4>backspace="1"</h4> <a href="#parameter_top">Back to Parameters</a><br /><br />
	You can also specify a backspace with a value meaning that on the last iteration, it will remove that many characters at the end. i.e. if you use backspace="6", then it will remove the last 6 characters.
	<br /><br />
	When using this in conjuction with the list_heading parameter, this will also backspace out that many characters at the end of the list heading group. eg. when displaying multiple linklists at one time.
</p>


<h2 class="tableCellOne" id="variables_top">Variables</h2>

<p>
	<a href="#vars_normal">{normal_linkcode}</a><br />
	<a href="#vars_xfncode">{xfn_linkcode}</a><br />
	<a href="#vars_xfn">{xfncode}</a><br />
	<a href="#vars_countcode">{count_linkcode}</a><br />
	<a href="#vars_countxfncode">{countxfn_linkcode}</a><br />
	<a href="#vars_counturl">{counturl}</a><br />
	<a href="#vars_ll_name">{linklist_name}</a><br />
	<a href="#vars_ll_title">{linklist_title}</a><br />
	<a href="#vars_url">{linklist:url}</a><br />
	<a href="#vars_url_rss">{url_rss}</a><br />
	<a href="#vars_url_favicon">{url_favicon}</a><br />
	<a href="#vars_url_title">{linklist:url_title}</a><br />
	<a href="#vars_url_desc">{url_desc}</a><br />
	<a href="#vars_relative_updated">{relative_updated}</a><br />
	<a href="#vars_updated_hours">{updated_hours}</a><br />
	<a href="#vars_last_updated">{last_updated format="%m/%d/%y"}</a><br />
	<a href="#vars_url_added">{url_added format="%m/%d/%y"}</a><br />
	<a href="#vars_prepend_str">{prepend_str}</a><br />
	<a href="#vars_append_str">{append_str}</a><br />
	<a href="#vars_recently_updated">{recently_updated}</a><br />

</p>

<p id="vars_normal">
	<h4>{normal_linkcode}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays your link with the anchor code tag already in place. eg.:
	<br />
	<div class="codeview"><pre>&lt;a href='http://www.pmachine.com/'>pMachine&lt;/a></pre></div>
</p>

<p id="vars_xfncode">
	<h4>{xfn_linkcode}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Identical to the {normal_linkcode} variables, except this will display the xfn code in the "rel" parameter. eg.:
	<br />
	<div class="codeview"><pre>&lt;a href="http://www.cybrpunk.com/" rel="friend met">cybrpunk.com&lt;/a></pre></div>
</p>

<p id="vars_xfn">
	<h4>{xfncode}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	If for some reason you wish to display only the xfncode itself, you can use this variable to do so. This will display:
	<br />
	<div class="codeview"><pre>friend met</pre></div>
</p>

<p id="vars_countcode">
	<h4>{count_linkcode}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Identical to the {normal_linkcode} variable, except this will show the URL that will increment the clickthru counter for the link. eg.:
	<br />
	<div class="codeview"><pre>&lt;a href="http://www.yourdomain.com/index.php?ACT=28&urlid=1">cybrpunk.com&lt;/a></pre></div>
</p>

<p id="vars_countxfncode">
	<h4>{countxfn_linkcode}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Identical to the {xfn_linkcode} variable, except this will show the URL that will increment the clickthru counter for the link. eg.:
	<br />
	<div class="codeview"><pre>&lt;a href="http://www.yourdomain.com/index.php?ACT=28&urlid=1">cybrpunk.com&lt;/a></pre></div>
</p>

<p id="vars_counturl">
	<h4>{counturl}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	This will show the URL that will increment the clickthru counter for the link. eg.:
	<br />
	<div class="codeview"><pre>http://www.yourdomain.com/index.php?ACT=28&urlid=1</pre></div>
</p>

<p id="vars_ll_name">
	<h4>{linklist_name}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays the "short" name of the current Linklist. This would be commonly used within the <a href="#list_heading">{list_heading}</a> variable pair. eg:
	<br />
	<div class="codeview"><pre>
		{list_heading}
			{linklist_name}
		{/list_heading}
	</pre></div>
</p>

<p id="vars_ll_title">
	<h4>{linklist_title}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Identical to the linklist_name variable, however this will show the "long" name of the linklist. eg:
	<br />
	<div class="codeview"><pre>
		{list_heading}
			{linklist_title}
		{/list_heading}
	</pre></div>
</p>

<p id="vars_url">
	<h4>{linklist:url}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	This will display the actual URL of the link. eg:
	<br />
	<div class="codeview"><pre>
	{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
	</pre></div>
</p>

<p id="vars_url_rss">
	<h4>{url_rss}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	This will display the actual URL of the RSS (if any). eg.:
	<br />
	<div class="codeview"><pre>
	{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
	&lt;a href="{url_rss}">RSS&lt;/a>
	</pre></div>
</p>

<p id="vars_url_favicon">
	<h4>{url_favicon}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	This will display the actual URL of the favicon for a website (if any). eg:
	<br />
	<div class="codeview"><pre>
	{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
	</pre></div>
</p>

<p id="vars_url_title">
	<h4>{linklist:url_title}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays the Title of the link. eg:
	<br />
	<div class="codeview"><pre>
	{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
	</pre></div>
</p>

<p id="vars_url_desc">
	<h4>{url_desc}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays the description of the link (if any). eg.:
	<br />
	<div class="codeview"><pre>
	{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
	Description: {url_desc}
	</pre></div>
</p>

<p id="vars_relative_updated">
	<h4>{relative_updated}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays the last date updated in Relative Time. i.e. 12 hours, 33 minutes.
</p>

<p id="vars_updated_hours">
	<h4>{updated_hours}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays in hours the last time a site was updated.
</p>

<p id="vars_last_updated">
	<h4>{last_updated format="%m/%d/%y"}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays the date the site was last updated according to the format.
	<br />
	<div class="codeview"><pre>{normal_linkcode} {last_updated format="%m/%d/%y"}</pre></div>
</p>

<p id="vars_url_added">
	<h4>{url_added format="%m/%d/%y"}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays the date the site was added to the linklist according to the format.
	<br />
	<div class="codeview"><pre>{normal_linkcode} {url_added format="%m/%d/%y"}</pre></div>
</p>

<p id="vars_prepend_str">
	<h4>{prepend_str}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays the Prepend String you specified within the LinkList Preferences. Use this when you are manually building your URL. Note that the {prepend_str} variable will only show when a site has been recently updated.
	<br />
	<div class="codeview"><pre>
	{prepend_str}&lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>{append_str}&lt;br />
	Description: {url_desc}
	</pre></div>
</p>

<p id="vars_append_str">
	<h4>{append_str}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays the Append String you specified within the LinkList Preferences. Use this when you are manually building your URL. Note that the {append_str} variable will only show when a site has been recently updated.
	<br />
	<div class="codeview"><pre>
	{prepend_str}&lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>{append_str}&lt;br />
	Description: {url_desc}
	</pre></div>
</p>

<p id="vars_recently_updated">
	<h4>{recently_updated}</h4> <a href="#variables_top">Back to variables</a><br /><br />
	Displays the "Time Recently Updated Offset (in hours)" value that is in the Linklist preferences.
</p>

<h2 class="tableCellOne" id="variable_pairs_top">Variable Pairs</h2>

<p id="list_heading">
	<h4>{list_heading}...{/list_heading}</h4> <a href="#variable_pairs_top">Back to variable pairs</a><br /><br />
	You can specify the code to execute one time per linklist using this variable pairs. It's very much like the {date_heading} parameter used in the {exp:weblog:entries} tag. You can specify within the variable pair any code that you want to show everytime it goes to the new linklist.
	<br />
	<div class="codeview"><pre>
	{exp:linklist:entries linklist="mylinks|newlinks" orderby="url_title" sort="ASC" status="open" limit="10"}
	{list_heading}
	&lt;h2&gt;{linklist_title}&lt;/h2&gt;
	{/list_heading}
	{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
	{/exp:linklist:entries}
	</pre></div>
</p>

<p id="keywords">
	<h4>{keywords backspace="6"}...{/keywords}</h4> <a href="#variable_pairs_top">Back to variable pairs</a><br /><br />
	When used within the {exp:linklist:entries} tag, this variable pair will enable you to show all the keywords that are associated with that certain link. This works similar to the categories variable pair in the weblogs tag.
	<br /><br />
	You can also specify a backspace with a value meaning that on the last iteration, it will remove that many characters at the end. i.e. if you use backspace="6", then it will remove the last 6 characters.
	<br /><br />
	Variables for this tag:
	<h4>{keyword}</h4>
	{keyword} will display the keyword for the link.
	<br />
	{path="weblog/keywords"} will display the URL to point to, according to the template you specify (weblog/keywords in this case) and append the keyword to the URL. eg. http://www.yourdomain.com/index.php/weblog/keywords/expression_engine.
	<h4>{path="weblog/template"}</h4>
	This will display the URL to point to with the keyword attached to it. i.e. {path="weblog/keywords"} results in: http://www.yourdomain.com/index.php/weblog/keywords/thekeyword.
	<br /><br />
	Here is an example of the usage:
	<br />
	<div class="codeview"><pre>
	{exp:linklist:entries dynamic="off" linklist="mylinks"}
		&lt;div>&lt;a href="{counturl}">{linklist:url_title}&lt;/a>&lt;/div>
		&lt;div>{keywords backspace="3"}&lt;a href="{path="weblog/keywords"}">{keyword}&lt;/a> | {/keywords}&lt;/div>
	{/exp:linklist:entries}
	</pre></div>
</p>

<p id="pagination">
	<h4>{paginate}...{/paginate}</h4> <a href="#variable_pairs_top">Back to variable pairs</a><br /><br />
	If you want to paginate your linklist, you can do so using the paginate feature. This feature works identical to the paginate method for weblog entries and comments.
	<br /><br />
	Pagination defaults to bottom. If you include the {paginate} tags, then they will be placed at the bottom or top. To specify the location, simply use the paramter paginate="top" or paginate="bottom".
	<br /><br />
	Example usage:
	<br />
	<div class="codeview"><pre>
	{exp:linklist:entries dynamic="off" linklist="mylinks" paginate="bottom"}
		&lt;div>&lt;a href="{counturl}">{linklist:url_title}&lt;/a>&lt;/div>
		&lt;div>{keywords backspace="3"}&lt;a href="{path="weblog/keywords"}">{keyword}&lt;/a> | {/keywords}&lt;/div>
		{paginate}
			&lt;p>Page {current_page} of {total_pages} Pages {pagination_links}&lt;/p>
		{/paginate}
	{/exp:linklist:entries}
	</pre></div>


</p>
<h2 class="tableCellOne" id="conditionals_top">Conditionals</h2>

<p>
	<a href="#cond_normal">Regular Conditionals</a><br />
	<a href="#cond_updated_hours">Updated Hours</a><br />
	<a href="#cond_recently_updated">Recently Updated</a>
</p>

<p id="cond_normal">
	<h4>{if <variablename> == "<value>"}...do something...{/if}</h4> <a href="#conditionals_top">Back to conditionals</a><br /><br />
	You can typically use any variable used above to develop show or hide certain things. Here are a few examples to get you going.
	<br />
	<div class="codeview"><pre>
	{if url_favicon}&lt;img src="{url_favicon}"&gt;{/if} &lt;a href="{linklist:url}">{linklist:url_title}&lt;/a>&lt;br />
	Description: {url_desc}
	{if url_rss != ""}{url_rss}{/if}
	{if url_desc != "" }Description: {url_desc}{/if}
	</pre></div>
</p>

<p id="cond_updated_hours">
	<h4>{if updated_hours < "12"}...do something...{/if}</h4> <a href="#conditionals_top">Back to conditionals</a><br /><br />
	This is to show an example of using Updated Hours to "bypass" your default "recently updated" value in your Linklist preferences. Regardless of what you may have in there, (default to 12) you can always bypass it by using this conditional.
	<br />
	<div class="codeview"><pre>
		{if updated_hours < "24"}&lt;a href="{linklist:url}" rel="{xfn_code}">{linklist:url_title}&lt;/a>{append_str}{/if}
	</pre></div>
	The example above will show any link you have as Updated if the site has been updated within 24 hours, <em>regardless</em> of what your linklist preferences might be set to. This can get handy if you want to change certain links to show updated at different times.
</p>

<p id="cond_recently_updated">
	<h4>{if recently_updated}...do something...{/if}</h4> <a href="#conditionals_top">Back to conditionals</a><br /><br />
	This is an example of using Recently Updated as a way to show more than just the prepend string or append string.
	<br />
	<div class="codeview"><pre>
		&lt;a href="{linklist:url}" rel="{xfn_code}">{linklist:url_title}&lt;/a>{append_str}{if recently_updated}{last_update format="%m/%d/%y"}{/if}
	</pre></div>
	As you can see from the example, the last updated date will only show if the link has been recently updated. This can get handy again if you want to highlight those sites that have been updated recently.
</p>

<h2 class="tableHeading" id="linklist_checkupdated">{exp:linklist:checkupdated}</h2>

<p>
This tag is mainly used as a "background" function. Basically what this tag will do is go out to the web and spider the RSS URL's of your links, and determine if the site has been updated or not. Most likely this tag will be added to the header of every template, or could be set to a template that you call at a specified interval.
</p>

<p>
BASIC USAGE:
<br />
<div class="codeview"><pre>{exp:linklist:checkupdated limit="5" silent="true" timeout="30"}</pre></div>
</p>

<p>
This tag will only check a batch of sites within a 1 hour period. When initially run, this tag will try to get all sites updated dates, so I recommend putting this tag into a test or temporary template and refresh that page until all your links have been updated with the last updated date. By default, this tag will only check 5 sites at a time. You can change this by using the <a href="#param_checkupdated_limit">limit</a> parameter. If you wish to specify only a certain linklist or linklists to be updated at a time you can also specify that using the <a href="#param_checkupdated_linklist">linklist</a> parameter.
</p>

<p>
Example Usage:
<br />
<div class="codeview"><pre>{exp:linklist:checkupdated limit="5" linklist="linklist"}</pre></div>
</p>

<h2 class="tableCellOne" id="checkupdated_top">{exp:linklist:checkupdated} Parameters</h2>

<p id="param_checkupdated_limit">
	<h4>limit="5"</h4> <a href="#checkupdated_top">Back to Parameters</a><br /><br />
	This parameter will limit the number of sites you will check to see for updates. While 5 may be a low number of sites to spider, please consider that this tag will be executed on the top of every page. I recommend tweaking this setting to your liking. Please remember that it will only check the latest 5 (or whatever specified number) sites within 1 hour. By default, limit is set to 5.
	<br />
	Tips: You may consider putting this into a special template and setting a Cron or Scheduled task to hit this page every so often. This would essentially be best, as it would not slow down access to your website when you have visitors.
</p>

<p id="param_checkupdated_silent">
	<h4>silent="true"</h4><a href="#checkupdated_top">Back to Parameters</a><br /><br />
	This param if set to false, will display exactly what it is doing. It will display the starting check of each link, and supply a benchmark time of how long it took for each RSS URL. By default, silent="true", meaning, there will be no display at all. (Use this only when you are debugging)
</p>

<p id="param_checkupdated_linklist">
	<h4>linklist="linklist"</h4> <a href="#checkupdated_top">Back to Parameters</a><br /><br />
	If you prefer only to see if certain linklists are updated, then you can specify the linklist that you want to have this module spider. You can also specify multiple linklists with the pipe character.
	<br />
	<div id="codeview">
		{exp:linklist:checkupdated linklist="new|old"}
	</div>
</p>

<p id="param_checkupdated_linklist">
	<h4>timeout="30"</h4> <a href="#checkupdated_top">Back to Parameters</a><br /><br />
	When checking for updated sites, there are some sites that take forever to come up, and often this slows down the checkupdated tag. I'm still not quite sure what the problem happening is, however, you can now specify a timeout time (in seconds) for a site before linklist will give up and stop waiting. The default is 30 seconds.
</p>

<h2 class="tableHeading" id="linklist_keywords">{exp:linklist:keywords}</h2>

<p>
This tag is used to display the keywords for a linklist. This tag uses the same tags as the {exp:linklist:entries} tag. You can specify displaying of keywords by single linklist, all linklists, some linklists, and so on. If you so choose, you may also display them as weighted keywords, meaning the more links you have associated to a certain keyword, the larger the size of the font.
</p>

<p>
BASIC USAGE:
<br />
<div class="codeview"><pre>
{exp:linklist:keywords linklist="mylinks" backspace="3"}
&lt;a href="{path="weblog/keywords"}">{keyword}&lt;/a> |
{/exp:linklist:keywords}
</pre></div>
That will display a series of keywords with links to the template "weblog/keywords".
</p>

<h2 class="tableCellOne" id="keywords_top">{exp:linklist:keywords} Parameters</h2>

<p id="param_keywords_min_size">
	<h4>min_size="10"</h4> <a href="#linklist_keywords">Back to Keywords</a><br /><br />
	min_size will make the minimum {font_size} to whatever you specify. You may wish to use this to control the sizes of your weighted keywords.
</p>

<p id="param_keywords_max_size">
	<h4>max_size="50"</h4> <a href="#linklist_keywords">Back to Keywords</a><br /><br />
	max_size will make the maximum {font_size} to whatever you specify. You may wish to use this to control the sizes of your weighted keywords.
</p>

<p id="param_keywords_related">
	<h4>related="on"</h4> <a href="#linklist_keywords">Back to Keywords</a><br /><br />
	This parameter allows you to display only the related Keywords to the current viewing keyword. i.e. if you reached a page at: http://www.yourdomain.com/index.php/weblog/keywords/expression_engine, this parameter tells the exp:linklist:keywords tag to look for related keywords to "expression engine", provided dynamic="on". You can also override the URL by specifying the keywords to relate to via the keywords="" parameter. Here is an example, this will only display keywords that are related to "expression engine" and "pmachine":
	<br />
	<div class="codeview"><pre>
	{exp:linklist:keywords linklist="mylinks" backspace="3" related="on" keywords="expression engine|pmachine"}
	&lt;a href="{path="weblog/keywords"}">{keyword}&lt;/a> |
	{/exp:linklist:keywords}
	</pre></div>
</p>

<p id="param_keywords_sort">
	<h4>sort="ASC"</h4> <a href="#linklist_keywords">Back to Keywords</a><br /><br />
	This will sort your resulting keywords in either ASC or DESC order. 
</p>


<h2 class="tableCellOne">{exp:linklist:keywords} Variables</h2>
<p id="vars_keywords_keyword">
	<h4>{keyword}</h4> <a href="#linklist_keywords">Back to Keywords</a><br /><br />
	{keyword} displays the keyword used in the links for that linklist.
</p>

<p id="vars_keywords_path">
	<h4>{path="weblog/template"}</h4> <a href="#linklist_keywords">Back to Keywords</a><br /><br />
	This will display the URL to point to with the keyword attached to it. i.e. {path="weblog/keywords"} results in: http://www.yourdomain.com/index.php/weblog/keywords/thekeyword.
</p>

<p id="vars_keywords_fontsize">
	<h4>{font_size}</h4> <a href="#linklist_keywords">Back to Keywords</a><br /><br />
	Will return a font size equivalent to the weight of the keyword. i.e. the more links associated to a certain keyword, the bigger the font size.
	<br />
	<div class="codeview"><pre>
	{exp:linklist:keywords linklist="mylinks"}
	&lt;span style="font-size:{font_size}px;">&lt;a href="{path="weblog/keywords"}">{keyword}&lt;/a>&lt;/span>
	{/exp:linklist:keywords}
	</pre></div>
</p>

<h2 class="tableHeading" id="troubleshooting">Troubleshooting</h2>

<p>
<strong>Problem:</strong> I'm having a problem importing my OPML file. I can import by using the URL, but if I upload a file, I keep getting an error that the file is not a valid file type.
<br />
<br />
<strong>Solution:</strong> As of ExpressionEngine version 1.3, files are checked against an array of mime types. You need to edit your mimes.php file located in your <font color=red>system/libs folder</font>. Check your file type and make sure that it's listed as an acceptable file type in your list. If you are using the typical file format, the file is probably an OPML file. so you'll need to add this line to your mimes.php file:

<strong>'opml'=>'application/opml'</strong>

Here is what it should look like:

<div class="codeview">
<pre>&lt;?php

$mimes = array(
'psd'=>'application/octet-stream',
'pdf'=>'application/pdf',
'swf'=>'application/x-shockwave-flash',
'sit'=>'application/x-stuffit',
'tar'=>'application/x-tar',
'tgz'=>'application/x-tar',
'zip'=>'application/zip',
'gzip'=>'application/x-gzip',
'bmp'=>'image/bmp',
'gif'=>'image/gif',
'jpeg'=>'image/jpeg',
'jpg'=>'image/jpeg',
'jpe'=>'image/jpeg',
'png'=>'image/png',
'txt'=>'text/plain',
'html'=>'text/html',
'doc'=>'application/msword',
'xl'=>'application/excel',
'xls'=>'application/excel',
'opml'=>'application/opml' <======= Notice this line.
);
?>
</pre>
</div>
</p>

<p>
<strong>Problem:</strong> I have the linklist in a sidebar on my main page, and it works great! But when you navigate to any other page, (such as the comments page) nothing shows up at all. What gives?
<br />
<br />
<strong>Solution:</strong> Remember that the linklist by default uses the third segment (segment_3) for keyword/tag searching. Typically then it display related links to that keyword. However if you are navigating to a comments page, (just as an example) odds are, the url would look like: http://www.yoursite.com/index.php/weblog/comments/title_permalink. In which case, the "title_permalink" segment is seen as a keyword/tag and it will attempt to display any link you have that has that keyword. (odds are good it's probably none) To disable this functionality, use the "dynamic=off" parameter to fix this issue:
<div class="codeview">
<pre>
{exp:linklist:entries dynamic="off" linklist="mylinks" limit="10"}
....
{/exp:linklist:entries}
</pre>
</div>
</p>

<h2 class="tableHeading" id="tipsandtricks">Tips and Tricks</h2>

<p>
I've had a few questions and basic ponderings of how to make some incredibly customized outputs using this linklist. To see some really cool techniques, check out <a href="http://www.lisajill.net">LisaJill's site</a> and you'll see some pretty cool stuff that she's done with it.
</p>

<p>
I'll be showing some examples in the following area to show you how some things can be displayed.
</p>

<div class="tipQuestion">
<p><strong>Q:</strong> I want to provide my linklist as an OPML or RSS Feed. How do I do that?
</div>
<div class="tipAnswer">
A few folks have already asked me to be able to share their linklists with other people by using OPML or RSS. (via OPML mainly to import into other sites, such as Furl) Here's a handy "tutorial" for accomplishing this. (Actually it's more of a template guide.) Instead of actually typing the code in for your template, I figured it's much easier for you to download the templates instead. You can find them with the distrobution of this module in the templates directory, named rsstemplate.txt and opmltemplate.txt.
</div>

<div class="tipQuestion">
<p><strong>Q:</strong> I want to display the linklist with the most recently updated sites on top. I do an <em>orderby="last_updated"</em> and I get the lists in order, but:
<br />
1) The linklist name headers show up multiple times and
<br />
2) The most recently updated are at the bottom, not the top!</p>
</div>
<div class="tipAnswer">
<p><strong>A:</strong> No problem.
<br />
1) The linklist name headers will show up multiple times, because you are sorting ONLY by the <em>last_updated</em> method. Fear not. In order to put the linklists into groups that will work, simply do a <em>orderby="linklist_title,last_updated"</em>. You can alternatively also do <em>orderby="linklist_id,last_updated"</em> as well.
<br />
2) By default, the linklist will be sorted in Ascending order. You can easily change this by setting the sort to Descending. (so the most recently updated who up first). i.e. <em>sort="DESC"</em>.
<br />
Your template code may look something like this:
<br />
<div class="codeview">
<pre>
{exp:linklist:entries orderby="linklist_title,last_updated" sort="DESC"}
	{list_heading}
	&lt;h3>{linklist_title}&lt;/h3>
	{/list_heading}
	{normal_linkcode}&lt;br />
	{/exp:linklist:entries}
</pre>
</div>
</p>
</div>


</div>

<div id="footer">

<div id="licensebox" name="licensebox">
<p><strong>The MIT License</strong></p>

<p>Copyright &copy; 2005 Yoshiaki Melrose</p>

<p>Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:</p>

<p>The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.</p>

<p>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.</p>
</div>

</div>

</div>


<?php
		$buffer = ob_get_contents();
			
		ob_end_clean(); 
		
		$DSP->body = $buffer;
	
	}


}


/*
Parser Class

parse("file_or_url","type") will parse a file or URL and return the results in an multi-dimensional array called $links

"type" = HTML or OPML. defaults to OPML.

*/

class Parser {
	var $id;
	var $links = array();
	var $type;
	
	
	// Constructor
	function Parser()
	{
		// do nothing
	}

	function parse($file='',$id='',$type='OPML')
	{
		global $LOC;
		
		$this->type = $type;
		$this->id = $id;
		
		if ( $file == '' )
		{
			return FALSE;
		}
		
		$parser = xml_parser_create();
		
		if ( eregi("^http://",$file) )
		{
			$snoopy = new Snoopy;
			$snoopy->fetch($file);
			$data = $snoopy->results;
		}
		else
		{
			$data = @implode('',@file($file));
		}

		xml_parse_into_struct($parser,$data,$values,$index);
		
		foreach ( $values as $attrs )
		{
			if ( $this->type == 'OPML' )
			{
				if ( $attrs['tag'] == 'OUTLINE' )
				{
					if ( strtolower($attrs['type']) == 'complete' )
					{
						$url = ( isset($attrs['attributes']['URL']) ) ? $attrs['attributes']['URL'] : '';
						$url = ( isset($attrs['attributes']['HTMLURL']) ) ? $attrs['attributes']['HTMLURL'] : $attrs['attributes']['URL'];
						$this->links[] = array (
'url_title'		=> ( isset($attrs['attributes']['TITLE']) 		? $attrs['attributes']['TITLE'] 			: $attrs['attributes']['TEXT'] ),
'url'			=> ( $url ),
'url_rss' 		=> ( isset($attrs['attributes']['XMLURL']) 		? $attrs['attributes']['XMLURL'] 			: '' ),
'url_desc'		=> ( isset($attrs['attributes']['DESCRIPTION']) 	? $attrs['attributes']['DESCRIPTION'] 	: '' ),
'linklist_id'	=> $this->id,
'url_added'		=> $LOC->now
											);
					}
				}
			}
			elseif ( $this->type == 'HTML' )
			{
				if ( $attrs['tag'] == 'LINK' )
				{
					$this->links[] = array ('rel'			=> ( isset($attrs['attributes']['REL']) 			? $attrs['attributes']['REL'] 			: '' ),
											'type'			=> ( isset($attrs['attributes']['TYPE']) 			? $attrs['attributes']['TYPE'] 			: '' ),
											'title' 		=> ( isset($attrs['attributes']['TITLE']) 		? $attrs['attributes']['TITLE'] 			: '' ),
											'href'			=> ( isset($attrs['attributes']['HREF']) 			? $attrs['attributes']['HREF'] 	: '' )
											);
				}
			}
		}
		return TRUE;
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

class Snoopy
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