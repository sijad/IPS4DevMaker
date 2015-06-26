<?php
/**
 * (c) 2015 skinod.com | author Sijad aka Mr.Wosi
 * Path to your IP.Board directory with a trailing /
 */

$_SERVER['SCRIPT_FILENAME']	= __FILE__;
$path	= '';
require_once $path . 'init.php';

// change it to your app key name
$appkey = 'cms';

/** !!!Stop Editing!!! **/
$path = \IPS\ROOT_PATH . '/applications/' . $appkey;
$i = 0;
$offset = NULL;
$inserted = 0;
if(!is_dir($path . '/dev')) {
	\mkdir($path . '/dev', \IPS\IPS_FOLDER_PERMISSION);

	// import css & html & resources
	if(is_file($path . "/data/theme.xml") && is_readable($path . "/data/theme.xml")) {
		/* Open XML file */
		$xml = new \XMLReader;
		$xml->open( $path . "/data/theme.xml" );
		$xml->read();

		while( $xml->read() )
		{
			if( $xml->nodeType != \XMLReader::ELEMENT )
			{
				continue;
			}

			$i++;

			if ( $offset !== null )
			{
				if ( $i - 1 < $offset )
				{
					$xml->next();
					continue;
				}
			}

			$inserted++;

			if( $xml->name == 'template' )
			{
				$template	= array(
					'group'		=> $xml->getAttribute('template_group'),
					'name'		=> $xml->getAttribute('template_name'),
					'variables'	=> $xml->getAttribute('template_data'),
					'content'	=> $xml->readString(),
					'location'	=> $xml->getAttribute('template_location'),
					'_default_template' => true
				);
				
				if(!is_dir($path . '/dev/html/' . $template['location'] . '/' . $template['group'])) {
					\mkdir($path . '/dev/html/' . $template['location'] . '/' . $template['group'], \IPS\IPS_FOLDER_PERMISSION, TRUE);
				}
				
				$file = $path . '/dev/html/' . $template['location'] . '/' . $template['group'] . '/' . $template['name'] . '.phtml';

				$data = <<<HTML
	<ips:template parameters="{$template['variables']}" />
	{$template['content']}
HTML;
				file_put_contents($file, $data);
			}
			else if( $xml->name == 'css' )
			{
				$css	= array(
					'location'	=> $xml->getAttribute('css_location'),
					'path'		=> $xml->getAttribute('css_path'),
					'name'		=> $xml->getAttribute('css_name'),
					'content'	=> $xml->readString(),
					'_default_template' => true
				);

				if(!is_dir($path . '/dev/css/' . $css['location'])) {
					\mkdir($path . '/dev/css/' . $css['location'], \IPS\IPS_FOLDER_PERMISSION, TRUE);
				}

				$file = $path . '/dev/css/' . $css['location'] . '/' . $css['name'];

				file_put_contents($file, $css['content']);
			}
			else if( $xml->name == 'resource' )
			{
				$resource	= array(
					'location'	=> $xml->getAttribute('location'),
					'path'		=> $xml->getAttribute('path'),
					'name'		=> $xml->getAttribute('name'),
					'content'	=> base64_decode( $xml->readString() ),
				);

				if(!is_dir($path . '/dev/resources/' . $resource['location'])) {
					\mkdir($path . '/dev/resources/' . $resource['location'], \IPS\IPS_FOLDER_PERMISSION, TRUE);
				}

				$file = $path . '/dev/resources/' . $resource['location'] . '/' . $resource['name'];

				file_put_contents($file, $resource['content']);
			}
		}
	}else{
		echo "\n\nError: Can't read theme.xml\n\n";
	}

	// import lang & js lang
	if(is_file($path . "/data/lang.xml") && is_readable($path . "/data/lang.xml")) {
		/* Open XML file */
		$xml = new \XMLReader;
		$xml->open( $path . "/data/lang.xml" );
		$xml->read();
		$xml->read();
		$xml->read();

		$langs = array();
		$js_langs = array();

		while( $xml->read() )
		{
			if( $xml->nodeType != \XMLReader::ELEMENT )
			{
				continue;
			}

			$lang	= array(
				'key'		=> $xml->getAttribute('key'),
				'js'		=> intval($xml->getAttribute('js')),
				'content'	=> $xml->readString(),
			);

			if($lang['js'] === 1) {
				$js_langs[$lang['key']] = $lang['content'];
			}else{
				$langs[$lang['key']] = $lang['content'];
			}

		}

		if(count($langs)) {
			$file = $path . '/dev/lang.php';
			$langs = var_export($langs, true);
			$data = <<<PHP
	<?php
	\$lang = $langs;
PHP;
			file_put_contents($file, $data);
		}

		if(count($js_langs)) {
			$file = $path . '/dev/jslang.php';
			$js_langs = var_export($js_langs, true);
			$data = <<<PHP
	<?php
	\$lang = $js_langs;
PHP;
			file_put_contents($file, $data);
		}
	}else{
		echo "\n\nError: Can't read lang.xml\n\n";
	}

	// import js
	if(is_file($path . "/data/javascript.xml") && is_readable($path . "/data/javascript.xml")) {
		$xml = new \XMLReader;
		$xml->open( $path . "/data/javascript.xml" );
		$xml->read();

		while( $xml->read() ) {
			if( $xml->nodeType != \XMLReader::ELEMENT )
			{
				continue;
			}

			$js_file = $path . '/dev/js/' . $xml->getAttribute('javascript_location') . '/' . $xml->getAttribute('javascript_path') . '/';
			if(!is_dir($js_file)) {
				\mkdir($js_file, \IPS\IPS_FOLDER_PERMISSION, TRUE);
			}
			file_put_contents($js_file . $xml->getAttribute('javascript_name'), $xml->readString());
		}
	}else{
		echo "\n\nError: Can't read javascript.xml\n\n";
	}
}else{
	echo "\n\nError: Dev folder already exists\n\n";
}