<?php
/**
 * @package	GeSHify
 * @author	Clemens Lang <neverpanic@gmail.com>
 * @link	http://www.neverpanic.de/blog/single/geshify-a-geshi-syntax-highlighting-extension-for-expression-engine
 * @version	0.3.8
 * @license	GPL
 */
class Geshify {
	var $name = 'GeSHify';
	var $version = '0.3.9';
	var $description = 'Passes code through the GeSHi syntax highlighter';
	var $docs_url = 'http://geshify.com/docs';
	var $settings = array();
	var $settings_exist = 'y';
	/*
		Before I start... did I mention EllisLabs' coding guidelines for EE suck?
		I'd rather use KNF identing and camelCase variable names
		And what's with that uppercase keywords true, false and null? That's so 1990...
	*/
	// default values
	var $llimit = '';
	var $rlimit = '';
	var $settings_default = array(
		'cache_dir' => '../cache/geshifier_cache/',
		'ldelimiter' => '[',
		'rdelimiter' => ']',
		'tag_name' => 'code',
		'default_type' => 'html4strict',
		'default_line' => 'normal',
		'keyword_links' => TRUE,
		'geshi_version' => '1.0',
		'geshi_encoding' => 'utf-8',
		'cache_cutoff' => 86400,
		'check_for_updates' => TRUE,
	);

	/**
	 * Contructor - accepts settings array
	 * @param	array	$settings	optional		Optional associative Array with options
	 * @return	void
	 * @access	public
	 */
	function Geshify($settings = '')
	{
		if (!empty($settings))
		{
			$this->settings = $settings;
		}
		foreach ($this->settings_default as $key => $val)
		{
			if (!isset($this->settings[$key]))
			{
				$this->settings[$key] = $val;
			}
		}
		unset($key, $val);
		// if you adjust this, make sure you also adjust the foreach loop used to parse the arguments
		$this->llimit = 
			'/' .
			preg_quote($this->settings['ldelimiter'], '/') .
			preg_quote($this->settings['tag_name'], '/') .
			'(?:\s*	# extended regex allows me to comment on what I do; this will be looped, making it possible to specify parameters in any order
				(?:
					# language parameter
					(lang			=
						(?:
							# non-quoted, double-quoted, single-quoted, note that backslashes must be escaped for PHP
							\\w*|
							"[^"]*"|
							\'[^\']*\'
						)
					)|
					# strict mode
					(strict			=
						(?:
							(?:true|false|1|0)|
							"(?:true|false|1|0)"|
							\'(?:true|false|1|0)\'
						)
					)|
					# line number style
					(line			=
						(?:
							(?:none|normal|fancy\\d+)|
							"(?:none|normal|fancy\\d+)"|
							\'(?:none|normal|fancy\\d+)\'
						)
					)|
					# first line number
					(start			=
						(?:
							\\d+|
							"\\d+"|
							\'\\d+\'
						)
					)|
					# whether to link keywords
					(keyword_links	=
						(?:
							(?:true|false|1|0)|
							"(?:true|false|1|0)"|
							\'(?:true|false|1|0)\'
						)
					)|
					# GeSHi overall class; the main reason why parameters can be quoted now
					(overall_class	=
						(?:
							\\w*|
							"[^"]*"|
							\'[^\']*\'
						)
					)|
					# GeSHi overall id
					(overall_id		=
						(?:
							\\w*|
							"[^"]*"|
							\'[^\']*\'
						)
					)
				)
			)*' .
			preg_quote($this->settings['rdelimiter'], '/') .
			'/ix';
		$this->rlimit = $this->settings['ldelimiter'] . '/' . $this->settings['tag_name'] . $this->settings['rdelimiter'];
	}

	/**
	 * Settings function called by the ACP to display the settings
	 * @param	void
	 * @return	array
	 * @access	public
	 */
	function settings()
	{
		$settings = array();
		$settings['cache_dir'] = '../cache/geshifier_cache/';
		$settings['ldelimiter'] = '[';
		$settings['rdelimiter'] = ']';
		$settings['tag_name'] = 'code';
		$settings['default_type'] = array(
			's',
			array(
				'php' => 'php',
				'html4strict' => 'html4strict',
				'html' => 'html',
				'css' => 'css'
			),
			'html4strict'
		);
		$settings['default_line'] = 'normal';
		$settings['keyword_links'] = array(
			'r',
			array(
				1 => 'yes',
				0 => 'no'
			),
			1
		);
		$settings['geshi_version'] = array(
			's',
			array(
				'1.0' => '1.0-stable',
				'1.1' => '1.1-alpha'
			),
			'1.0'
		);
		$settings['geshi_encoding'] = 'utf-8';
		$settings['cache_cutoff'] = '86400';
		$settings['check_for_updates'] = array(
			'r',
			array(
				1 => 'yes',
				0 => 'no'
			),
			1
		);
		return $settings;
	}

	/**
	 * Installs the extension by registering the required extension hooks
	 * @param	void
	 * @return	void
	 * @access	public
	 */
	function activate_extension()
	{
		global $DB, $PREFS;
		$DB->query($DB->insert_string($PREFS->ini('db_prefix').'_extensions',
			array(
				'extension_id' => '',
				'class' => 'Geshify',
				'method' => 'pre_typography',
				'hook' => 'typography_parse_type_start',
				'settings' => serialize($this->settings_default),
				'priority' => 8,
				'version' => $DB->escape_str($this->version),
				'enabled' => 'y'
			)
		));
		$DB->query($DB->insert_string($PREFS->ini('db_prefix').'_extensions',
			array(
				'extension_id' => '',
				'class' => 'Geshify',
				'method' => 'post_typography',
				'hook' => 'typography_parse_type_end',
				'settings' => serialize($this->settings_default),
				'priority' => 8,
				'version' => $DB->escape_str($this->version),
				'enabled' => 'y'
			)
		));
		$DB->query($DB->insert_string($PREFS->ini('db_prefix').'_extensions',
			array(
				'extension_id' => '',
				'class' => 'GeSHify',
				'method' => 'addon_check_register_source',
				'hook' => 'lg_addon_update_register_source',
				'settings' => '',
				'priority' => 10,
				'version' => $DB->escape_str($this->version),
				'enabled' => 'y'
			)
		));
		$DB->query($DB->insert_string($PREFS->ini('db_prefix').'_extensions',
			array(
				'extension_id' => '',
				'class' => 'GeSHify',
				'method' => 'addon_check_register_addon',
				'hook' => 'lg_addon_update_register_addon',
				'settings' => '',
				'version' => $DB->escape_str($this->version),
				'enabled' => 'y'
			)
		));
	}

	/**
	 * Updates the extension by applying the required changes
	 * @param	string	$current		version to upgrade to
	 * @return	void
	 * @access	public
	 */
	function update_extension($current) 
	{
		global $DB, $PREFS;
		// initial version, nothing to update
		if (version_compare($this->version, $current) === 0)
		{
			return FALSE;
		}
		if (version_compare($this->version, '0.3.6.0') === -1)
		{
			// update from pre-0.3.6.0
			$this->settings['keyword_links'] = TRUE;
			$DB->query("UPDATE ".$PREFS->ini('db_prefix')."_extensions SET settings = '".$DB->escape_str(serialize($this->settings))."' WHERE class = 'Geshify'");
		}
		if (version_compare($this->version, '0.3.7') === -1)
		{
			// nothing to do when upgrading from pre-0.3.7
		}
		if (version_compare($this->version, '0.3.8') === -1)
		{
			$this->settings['check_for_updates'] = TRUE;
			// add the necessary hooks for the addon checker
			$DB->query($DB->insert_string($PREFS->ini('db_prefix').'_extensions',
				array(
					'extension_id' => '',
					'class' => 'GeSHify',
					'method' => 'addon_check_register_source',
					'hook' => 'lg_addon_update_register_source',
					'settings' => '',
					'priority' => 10,
					'version' => $DB->escape_str($this->version),
					'enabled' => 'y'
				)
			));
			$DB->query($DB->insert_string($PREFS->ini('db_prefix').'_extensions'),
				array(
					'extension_id' => '',
					'class' => 'GeSHify',
					'method' => 'addon_check_register_addon',
					'hook' => 'lg_addon_update_register_addon',
					'settings' => '',
					'version' => $DB->escape_str($this->version),
					'enabled' => 'y'
				)
			);
		}
		// set the version in the DB to current
		$DB->query("UPDATE ".$PREFS->ini('db_prefix')."_extensions SET version = '".$DB->escape_str($this->version)."' WHERE class = 'Geshify'");
		if (version_compare($this->version, '0.3.9') === -1)
		{
			// I guess the doc url is always loaded from the source code as is the LG Addon Updater URL
		}
	}

	/**
	 * Uninstalls the extension by deleting the extension hooks
	 * @param	void
	 * @return	void
	 * @access	public
	 */
	function disable_extension()
	{
		global $DB, $PREFS;
		$DB->query("DELETE FROM ".$PREFS->ini('db_prefix')."_extensions WHERE class = 'Geshify'");
	}

	/**
	 * Function called by the pre_typography extension hook before the text will be parsed by EE
	 * @param	string	$str	text that will be parsed
	 * @param	object	$typo	Typography object
	 * @param	array	$prefs	Preferences sent to $TYPE->parse_type
	 * @return	string			text where the code has been stripped and code positions marked with an MD5-ID
	 * @access	public
	 * @global	$EXT			Extension-Object to support multiple calls to the same extension hook
	 * @global	$OUT			could be used to display errors - it isn't at the moment
	 * @todo					Display error using $OUT
	 */
	function pre_typography($str, $typo, $prefs)
	{
		// we don't need the DB, nor IN, nor DSP
		// should probably use OUT to display user_error messages
		global $EXT, $OUT;
		// here we're doing the actual work
		if ($EXT->last_call !== FALSE)
		{
			// A different extension has run before us
			$str = $EXT->last_call;
		}
		$cache_dir = dirname(__FILE__).'/'.$this->settings['cache_dir'];
		$rllen = strlen($this->rlimit);
		$pos = array();
		preg_match_all($this->llimit, $str, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $key => $match)
		{
			$pos[$match[1]] = array();
			$pos[$match[1]]['match'] = $match[0];
			
			// lang (called type internally for historical reasons)
			if ($matches[1][$key][0] != '')
			{
				// strip slashes for filesystem security and quotes because the value might be quoted
				$pos[$match[1]]['type'] = str_replace(array('/', '"', "'"), '', substr($matches[1][$key][0], 5));
			}
			else
			{
				$pos[$match[1]]['type'] = NULL;
			}
			
			// strict
			if ($matches[2][$key][0] != '')
			{
				switch (str_replace(array('"', "'"), '', strtolower(substr($matches[2][$key][0], 7))))
				{
					case 'true':
					case '1':
						$pos[$match[1]]['strict'] = TRUE;
						break;
					case 'false':
					case '0':
						$pos[$match[1]]['strict'] = FALSE;
						break;
					default:
						$pos[$match[1]]['strict'] = NULL;
						break;
				}
			}
			else
			{
				$pos[$match[1]]['strict'] = NULL;
			}
			
			// line
			$pos[$match[1]]['line'] = ($matches[3][$key][0] != '') ? str_replace(array('"', "'"), '', substr($matches[3][$key][0], 5)) : NULL;
			
			// start
			$pos[$match[1]]['start'] = ($matches[4][$key][0] != '') ? str_replace(array('"', "'"), '', substr($matches[4][$key][0], 6)) : NULL;
			
			// keyword_links
			if ($matches[5][$key][0] != '')
			{
				switch (str_replace(array("'", '"'), '', strtolower(substr($matches[5][$key][0], 14))))
				{
					case 'true':
					case '1':
						$pos[$match[1]]['keyword_links'] = TRUE;
						break;
					case 'false':
					case '0':
						$pos[$match[1]]['keyword_links'] = FALSE;
						break;
					default:
						$pos[$match[1]]['keyword_links'] = NULL;
						break;
				}
			}
			else
			{
				$pos[$match[1]]['keyword_links'] = NULL;
			}
			
			// overall_class
			$pos[$match[1]]['overall_class'] = ($matches[6][$key][0] != '') ? str_replace(array("'", '"'), '', substr($matches[6][$key][0], 14)) : NULL;
			
			// overall_id
			$pos[$match[1]]['overall_id'] = ($matches[7][$key][0] != '') ? str_replace(array("'", '"'), '', substr($matches[7][$key][0], 11)) : NULL;
		}
		
		// clean variables used in the loop
		unset($matches, $key, $match);
		
		// krsort the array so we can use substr stuff and won't mess future replacements
		krsort($pos);
		
		// Check for the cache dir
		if (file_exists($cache_dir) && is_dir($cache_dir))
		{
			$cache_dir = realpath($cache_dir).'/';
			if (!is_writable($cache_dir))
			{
				// try to chmod it
				@chmod($cache_dir, 0777);
				if (!is_writable($cache_dir))
				{
					// still not writable? display a warning
					print('<b>Warning</b>: Your <i>'.$this->name.'</i> cache directory <b>'.$cache_dir.'</b> is not writable! This will cause severe performance problems, so I suggest you chmod that dir.');
				}
			}
		}
		else
		{
			if (!mkdir($cache_dir, 0777))
			{
				print('<b>Warning</b>: Your <i>'.$this->name.'</i> cache directory <b>'.$cache_dir.'</b> could not be created! This will cause severe performance problems, so I suggest you create and chmod that dir.');
			}
			else
			{
				// create an index.html so the contents will not be listed.
				@touch($cache_dir.'index.html');
			}
		}
		if (mt_rand(0, 10) == 10)
		{
			// on every 10th visit do the garbage collection
			$cur = time();
			$d = dir($cache_dir);
			while (($f = $d->read()) !== FALSE)
			{
				if ($f != 'index.html' && $f{0} != '.')
				{
					if ($cur - filemtime($cache_dir.$f) > $this->settings['cache_cutoff'])
					{
						// File is older than cutoff, delete it.
						@unlink($cache_dir.$f);
					}
				}
			}
		}
		
		// loop through the code snippets
		$i = 0;
		foreach ($pos as $code_pos => $match)
		{
			$error = FALSE;
			if (($code_end_pos = strpos($str, $this->rlimit, ((int) $code_pos + strlen($match['match'])))) !== FALSE)
			{
				// we have a matching end tag.
				// make sure cache is regenerated when changing options, too!
				$md5 = md5(($not_geshified = substr($str, $code_pos + strlen($match['match']), ($code_end_pos - $code_pos - strlen($match['match'])))).print_r($match, TRUE).print_r($this->settings, TRUE));
				
				// check whether we already have this in a cache file
				if (is_file($cache_dir . $md5) && is_readable($cache_dir . $md5))
				{
					if (is_callable('file_get_contents'))
					{
						$geshified = file_get_contents($cache_dir . $md5);
						// this is for the garbage collection
						touch($cache_dir . $md5);
					}
					else
					{
						// screw PHP4!
						$f = fopen($cache_dir . $md5, 'r');
						$geshified = fread($f, filesize($cache_dir . $md5));
						fclose($f);
						touch($cache_dir . $md5);
					}
				}
				else
				{
					// no cache so do the GeSHi thing
					if ($this->settings['geshi_version'] == '1.1')
					{
						// use GeSHi 1.1
						include_once(dirname(__FILE__).'/geshi-1.1/class.geshi.php');
						// highlight code according to type setting, default to setting
						$geshi = new GeSHi($not_geshified, $match['type'] !== NULL ? $match['type'] : $this->settings['default_type']);
						// neither line numbers, nor strict mode is supported in GeSHi 1.1 yet.
						$str_error = $geshi->error();
						if (empty($str_error))
						{
							// set line number style, if GeSHi supports it
							if (is_callable($geshi, 'enableLineNumbers'))
							{
								switch (!empty($match['line']) ? strtolower(preg_replace('/\d*/', '', $match['line'])) : $this->settings['default_line'])
								{
									case 'normal':
										$geshi->enableLineNumbers(GESHI_NORMAL_LINE_NUMBERS);
										break;
									case 'fancy':
										$geshi->enableLineNumbers(GESHI_FANCY_LINE_NUMBERS, (int) preg_replace('/[^\d]*/', '', $match['line']));
										break;
									case 'none':
										$geshi->enableLineNumbers(GESHI_NO_LINE_NUMBERS);
										break;
								}
							}
							
							// set start line number if GeSHi supports it
							if (is_callable(array($geshi, 'startLineNumbersAt')))
							{
								if ($match['start'])
								{
									$geshi->startLineNumbersAt($match['start']);
								}
							}
							
							// set strict mode if GeSHi supports it
							if (is_callable(array($geshi, 'enableStrictMode')))
							{
								if ($match['strict'])
								{
									$geshi->enableStrictMode(TRUE);
								}
							}
							
							// set encoding (afair this is for legacy reasons only anyway)
							if (is_callable(array($geshi, 'setEncoding')))
							{
								$geshi->setEncoding($this->settings['geshi_encoding']);
							}
							
							// set whether to link keywords to the documentation
							if (is_callable(array($geshi, 'enableKeywordLinks')))
							{
								$geshi->enableKeywordLinks((bool) ($match['keyword_links'] !== NULL) ? $match['keyword_links'] : $this->settings['keyword_links']);
							}
							
							// set overall class name
							if (is_callable(array($geshi, 'setOverallClass')))
							{
								if ($match['overall_class'] != NULL)
								{
									$geshi->setOverallClass($match['overall_class']);
								}
							}
							
							// set overall id
							if (is_callable(array($geshi, 'setOverallId')))
							{
								if ($match['overall_id'] != NULL)
								{
									$geshi->setOverallId($match['overall_id']);
								}
							}
							
							// parse the code
							$geshified = $geshi->parseCode();
						}
						else
						{
							$error = TRUE;
						}
					}
					else
					{
						// use GeSHi 1.0
						include_once(dirname(__FILE__).'/geshi-1.0/geshi.php');
						// highlight code according to type setting, default to php
						$geshi = new GeSHi($not_geshified, $match['type'] !== NULL ? $match['type'] : $this->settings['default_type']);
						$str_error = $geshi->error();
						if (empty($str_error))
						{
							// enable line numbers
							switch (!empty($match['line']) ? strtolower(preg_replace('/\d*/', '', $match['line'])) : $this->settings['default_line'])
							{
								case 'normal':
									$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
									break;
								case 'fancy':
									$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, (int) preg_replace('/[^\d]*/', '', $match['line']));
									break;
								case 'none':
									$geshi->enable_line_numbers(GESHI_NO_LINE_NUMBERS);
									break;
							}
							
							// set first line number
							if ($match['start'])
							{
								$geshi->start_line_numbers_at($match['start']);
							}
							
							// set strict mode
							if ($match['strict'])
							{
								$geshi->enable_strict_mode(TRUE);
							}
							
							// enable or disable keyword links
							$geshi->enable_keyword_links((bool) ($match['keyword_links'] !== NULL) ? $match['keyword_links'] : $this->settings['keyword_links']);
							
							// set overall class name
							if ($match['overall_class'] != NULL)
							{
								$geshi->set_overall_class($match['overall_class']);
							}
							
							// set overall id
							if ($match['overall_id'] != NULL)
							{
								$geshi->set_overall_id($match['overall_id']);
							}
							
							// set encoding (for legacy reasons afaik)
							$geshi->set_encoding($this->settings['geshi_encoding']);
							
							// parse the source code
							$geshified = $geshi->parse_code();
						}
						else
						{
							$error = TRUE;
						}
					}
					if ((!file_exists($cache_dir.$md5) && is_writable($cache_dir)) || (file_exists($cache_dir.$md5) && is_writable($cache_dir.$md5)))
					{
						if (!$error)
						{
							// we can write to the cache file
							if (is_callable('file_put_contents'))
							{
								file_put_contents($cache_dir.$md5, $geshified);
								@chmod($cache_dir.$md5, 0777);
							}
							else
							{
								// when will you guys finally drop PHP4 support?
								$f = fopen($cache_dir.$md5, 'w');
								fwrite($f, $geshified);
								fclose($f);
								@chmod($cache_dir.$md5, 0777);
							}
						}
					}
					else
					{
						// We could ignore that, but for performance reasons better warn the user.
						print('<b>Warning</b>: Your <i>'.$this->name.'</i> cache directory <b>'.$cache_dir.'</b> is not writable! This will cause severe performance problems, so I suggest you chmod that dir.');
					}
				}
				// save replacement to cache and mark location with an identifier for later replacement
				if (!isset($_SESSION['cache']['ext.geshify']))
				{
					$_SESSION['cache']['ext.geshify'] = array();
				}
				if (!$error)
				{
					$_SESSION['cache']['ext.geshify'][$md5] = $geshified;
					$str = substr($str, 0, $code_pos).$md5.substr($str, $code_end_pos+$rllen);
				}
			}
			// unset used variables, so we don't get messed up
			unset($code_pos, $code_end_pos, $md5, $geshified, $not_geshified, $geshi, $match, $ident, $error);
		}
		return $str;
	}

	/**
	 * Function called by the post_typography extension hook to replace the MD5-IDs pre_typography put into the text with the HTML equivalent of the source code
	 * @param	string	$str	text that will be parsed
	 * @param	object	$typo	Typography object
	 * @param	array	$prefs	Preferences sent to $TYPE->parse_type
	 * @return	string			text with the GeSHi-rendered source-code
	 * @access	public
	 * @global	$EXT			Extension-Object to support multiple calls to the same extension hook
	 * @global	$OUT			could be used to display errors - it isn't at the moment, though @see next line
	 * @todo					Display error using $OUT
	 */
	function post_typography($str, $typo, $prefs)
	{
		global $EXT;
		if ($EXT->last_call !== FALSE)
		{
			// A different extension has run before us
			$str = $EXT->last_call;
		}
		if (isset($_SESSION['cache']['ext.geshify']))
		{
			// replace idents with values from the cache - this way we passed the code around the usual typography stuff
			foreach ($_SESSION['cache']['ext.geshify'] as $marker => $replacement)
			{
				if (strpos($str, $marker) !== false)
				{
					// this marker is in the text, so replace it
					$str = str_replace($marker, $replacement, $str);
				}
			}
			return $str;
		}
		else
		{
			// load the replacements from the file
			$d = dir($cache_dir = dirname(__FILE__).'/'.$this->settings['cache_dir']);
			while (($file = $d->read()) !== FALSE)
			{
				if ($file != 'index.html' && $file{0} != '.')
				{
					// read file content and replace - I know this is ugly, but it seems you can't trust $_SESSION['cache']
					if (is_readable($cache_dir.$file))
					{
						if (strpos($str, $file) !== false)
						{
							// $file is the marker here, and it exists in the text, so replace it
							if (is_callable('file_get_contents'))
							{
								$repl = file_get_contents($cache_dir.$file);
							}
							else
							{
								$f = fopen($cache_dir.$file, 'r');
								$repl = fread($cache_dir.$file, filesize($cache_dir.$file));
								fclose($f);
							}
							$str = str_replace($file, $repl, $str);
						}
					}
				}
				unset($repl);
			}
			return $str;
		}
	}
	
	/**
	 * registers my source file with the LG Addon Updater
	 * @param	array	$sources	array of source files URIs
	 * @return	array				the same array plus the source file URI for this extension
	 * @access	public
	 * @global	$EXT				Extension object to support multiple calls to this hook
	 */
	function addon_check_register_source($sources)
	{
		global $EXT;
		if ($EXT->last_call !== FALSE)
		{
			$sources = $EXT->last_call;
		}
		// add new source and return it
		if ($this->settings['check_for_updates'] == TRUE)
		{
			$sources[] = 'http://geshify.com/lg-addon-updater.php';
		}
		return $sources;
	}
	
	/**
	 * registers this extension with the LG Addon Updater
	 * @param	array	$addons		array of addon IDs
	 * @return	array				same array plus the addon ID for this extension
	 * @access	public
	 * @global	$EXT				Extension object to support multiple calls to this hook
	 */
	function addon_check_register_addon($addons)
	{
		global $EXT;
		if ($EXT->last_call !== FALSE)
		{
			$addons = $EXT->last_call;
		}
		// register the current version with the LG Addon Updater
		if ($this->settings['check_for_updates'])
		{
			$addons['GeSHify'] = $this->version;
		}
		return $addons;
	}
}
?>