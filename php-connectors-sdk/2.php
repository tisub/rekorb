<?php

if( !isset($_POST['connector']) || strlen($_POST['connector']) == 0 )
	throw new Exception("Wrong connector");

function validate_json($txt)
{
	$data = json_decode($txt, true);
	if( $data == null )
		throw new \Exception("Invalid json file : " . json_last_error_msg());
	
	$fail = array();
	
	// check sections
	if( !array_key_exists('general', $data) ) 		$fail[] = "Missing 'general' section in json file";
	if( !array_key_exists('interfaces', $data) ) 	$fail[] = "Missing 'interfaces' section in json file";
	if( !array_key_exists('configs', $data) ) 		$fail[] = "Missing 'configs' section in json file";
	if( !array_key_exists('translation', $data) )	$fail[] = "Missing 'translation' section in json file";
	if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
	
	// check general
	if( !is_array($data['general']) ) 						$fail[] = "Section 'general' must be an object";
	if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
	if( !array_key_exists('language', $data['general']) ) 	$fail[] = "Missing 'language' entry in 'general' section";
	if( !array_key_exists('category', $data['general']) ) 	$fail[] = "Missing 'category' entry in 'general' section";
	if( !array_key_exists('buy_price', $data['general']) ) 	$fail[] = "Missing 'buy_price' entry in 'general' section";
	if( !array_key_exists('use_price', $data['general']) ) 	$fail[] = "Missing 'use_price' entry in 'general' section";
	if( !array_key_exists('small_logo', $data['general']) ) $fail[] = "Missing 'small_logo' entry in 'general' section";
	if( !array_key_exists('big_logo', $data['general']) ) 	$fail[] = "Missing 'big_logo' entry in 'general' section";
	if( $data['general']['language'] != 'PHP' ) 	$fail[] = "Entry 'language' in 'general' section must be set to \"PHP\"";
	if( !in_array($data['general']['category'], array('Business', 'Communication', 'Entertainment', 'Health', 'Home automation', 'Industry', 'News', 'Productivity', 'Social', 'Storage', 'Technology', 'Utilities')) ) 
		$fail[] = "Entry 'category' in 'general' section must be one of 'Business', 'Communication', 'Entertainment', 'Health', 'Home automation', 'Industry', 'News', 'Productivity', 'Social', 'Storage', 'Technology', 'Utilities'";
	if( !is_numeric($data['general']['buy_price']) || $data['general']['buy_price'] < 0 || $data['general']['buy_price'] > 100000 )
		$fail[] = "Entry 'buy_price' in 'general' section must be numeric and must be between 0 and 100000";
	if( !is_numeric($data['general']['use_price']) || $data['general']['use_price'] < 0 || $data['general']['use_price'] > 100000 )
		$fail[] = "Entry 'use_price' in 'general' section must be numeric and must be between 0 and 100000";
	if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
	
	// check interfaces
	if( !is_array($data['interfaces']) ) $fail[] = "Section 'interfaces' must be an array";
	if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
	$i_keys = [];
	foreach( $data['interfaces'] as $i )
	{
		if( !is_array($i) ) { 						$fail[] = "Entry in 'interfaces' must be an object"; continue; }
		if( !array_key_exists('key', $i) ) 			$fail[] = "Missing interface 'key' entry in 'interfaces' section";
		if( !array_key_exists('direction', $i) ) 	$fail[] = "Missing 'direction' entry for interface {$i['key']}";
		if( !array_key_exists('pattern', $i) ) 		$fail[] = "Missing 'pattern' entry for interface {$i['key']}";
		if( !array_key_exists('custom', $i) ) 		$fail[] = "Missing 'custom' entry for interface {$i['key']}";
		if( !array_key_exists('automatic', $i) ) 	$fail[] = "Missing 'automatic' entry for interface {$i['key']}";
		if( !array_key_exists('timer', $i) ) 		$fail[] = "Missing 'timer' entry for interface {$i['key']}";
		if( !array_key_exists('hidden', $i) ) 		$fail[] = "Missing 'hidden' entry for interface {$i['key']}";
		if( !array_key_exists('rule', $i) ) 		$fail[] = "Missing 'rule' entry for interface {$i['key']}";
		if( in_array($i['key'], $i_keys) ) $fail[] = "Non unique interface key interface {$i['key']}";
		$i_keys[] = $i['key'];
		if( is_numeric($i['key']) )		$fail[] = "Entry 'key' cannot be numeric for interface {$i['key']}";
		if( !in_array($i['direction'], array('input', 'output')) )
			$fail[] = "Entry 'direction' must be one of 'input', 'output' for interface {$i['key']}";
		if( !in_array($i['pattern'], array('producer', 'consumer', 'transformer')) )
			$fail[] = "Entry 'pattern' must be one of 'producer', 'consumer', 'transformer' for interface {$i['key']}";
		if( $i['direction'] == 'input' && $i['pattern'] == 'producer' )
			$fail[] = "Pattern 'producer' is not compatible with direction 'input' for interface {$i['key']}";
		if( $i['direction'] == 'output' && $i['pattern'] == 'consumer' )
			$fail[] = "Pattern 'consumer' is not compatible with direction 'output' for interface {$i['key']}";
		if( !is_bool($i['custom']) ) 	$fail[] = "Entry 'custom' must be a boolean (true/false) for interface {$i['key']}";
		if( !is_bool($i['automatic']) ) $fail[] = "Entry 'automatic' must be a boolean (true/false) for interface {$i['key']}";
		if( $i['pattern'] == 'transformer' && $i['automatic'] )
			$fail[] = "Pattern 'transformer' is not compatible with automatic triggering for interface {$i['key']}";
		if( !is_bool($i['hidden']) ) 	$fail[] = "Entry 'hidden' must be a boolean (true/false) for interface {$i['key']}";
		if( $i['timer'] != null && !preg_match("/^(([0-9]{1,4}|E)(\\-|$)){5}$/", $i['timer'])  ) 	
			$fail[] = "Entry 'timer' must be null or a valid time period (see documentation) for interface {$i['key']}";
		if( $i['rule'] != null && !preg_match("!^(domain|email|ip|phone|url|number|text|upper|lower|/.*/g?m?g?i?g?m?g?)$!", $i['rule']) )
			$fail[] = "Entry 'rule' must be a valid rule (see documentation) for interface {$i['key']}";
		if( preg_match("!^/.*/g?m?g?i?g?m?g?$!", $i['rule']) && @preg_match(preg_replace("!/g?m?g?(i)?g?m?g?$!", "/$1", $i['rule']), null) === false )
			$fail[] = "Entry 'rule' contains invalid regex pattern for interface {$i['key']}";
	}
	if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
	
	// check configs
	if( !is_array($data['configs']) ) $fail[] = "Section 'configs' must be an array";
	if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
	$c_keys = [];
	foreach( $data['configs'] as $c )
	{
		if( !is_array($c) ) { 						$fail[] = "Entry in 'configs' must be an object"; continue; }
		if( !array_key_exists('key', $c) ) 			$fail[] = "Missing 'key' entry in 'configs' section";
		if( !array_key_exists('hidden', $c) ) 		$fail[] = "Missing 'hidden' entry for config {$c['key']}";
		if( !array_key_exists('bindable', $c) ) 	$fail[] = "Missing 'bindable' entry for config {$c['key']}";
		if( !array_key_exists('default', $c) ) 		$fail[] = "Missing 'default' entry for config {$c['key']}";
		if( !array_key_exists('rule', $c) ) 		$fail[] = "Missing 'rule' entry for config {$c['key']}";
		if( in_array($c['key'], $c_keys) ) $fail[] = "Non unique config key config {$c['key']}";
		$c_keys[] = $c['key'];
		if( is_numeric($c['key']) )		$fail[] = "Entry 'key' cannot be numeric for config {$c['key']}";
		if( !is_bool($c['hidden']) ) 	$fail[] = "Entry 'hidden' must be a boolean (true/false) for config {$c['key']}";
		if( !is_bool($c['bindable']) ) 	$fail[] = "Entry 'bindable' must be a boolean (true/false) for config {$c['key']}";
		if( $c['rule'] != null && !preg_match("!^(domain|email|ip|phone|url|number|text|upper|lower|/.*/g?m?g?i?g?m?g?)$!", $c['rule']) )
			$fail[] = "Entry 'rule' must be a valid rule (see documentation) for config {$c['key']}";
		if( preg_match("!^/.*/g?m?g?i?g?m?g?$!", $c['rule']) && @preg_match(preg_replace("!/g?m?g?(i)?g?m?g?$!", "/$1", $c['rule']), null) === false )
			$fail[] = "Entry 'rule' contains invalid regex pattern for config {$c['key']}";
	}
	if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
	
	// check translations
	if( !is_array($data['translation']) ) $fail[] = "Section 'translation' must be an object";
	if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
	if( !array_key_exists('en', $data['translation']) ) $fail[] = "English ('en') translation is mandatory in section 'translation'";
	foreach( $data['translation'] as $l=>$t )
	{
		if( !is_array($t) ) { 						$fail[] = "Entry '{$l}' in 'translation' must be an object"; continue; }
		if( !preg_match("/^[a-z]{2}$/", $l) )		$fail[] = "Invalid translation language (see documentation) : {$l}";
		if( !array_key_exists('general', $t) )		$fail[] = "Missing 'general' section for translation in '{$l}'";
		if( !array_key_exists('interfaces', $t) )	$fail[] = "Missing 'interfaces' section for translation in '{$l}'";
		if( !array_key_exists('configs', $t) )		$fail[] = "Missing 'configs' section for translation in '{$l}'";
		if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
		
		// check general translation
		if( !is_array($t['general']) ) { 								$fail[] = "Entry 'general' for translation in '{$l}' must be an object"; continue; }
		if( !array_key_exists('name', $t['general']) )					$fail[] = "Missing 'name' entry for 'general' translation in '{$l}'";
		if( !array_key_exists('short_description', $t['general']) )		$fail[] = "Missing 'short_description' entry for 'general' translation in '{$l}'";
		if( !array_key_exists('long_description', $t['general']) )		$fail[] = "Missing 'long_description' entry for 'general' translation in '{$l}'";
		if( !array_key_exists('prevention', $t['general']) )			$fail[] = "Missing 'prevention' entry for 'general' translation in '{$l}'";
		if( !array_key_exists('config_url', $t['general']) )			$fail[] = "Missing 'config_url' entry for 'general' translation in '{$l}'";
		if( !array_key_exists('panel_url', $t['general']) )				$fail[] = "Missing 'panel_url' entry for 'general' translation in '{$l}'";
		if( !array_key_exists('product_url', $t['general']) )			$fail[] = "Missing 'product_url' entry for 'general' translation in '{$l}'";
		if( $t['general']['name'] == null || strlen($t['general']['name']) == 0 )
			$fail[] = "The 'name' cannot be null or empty for 'general' translation in '{$l}'";
		if( $t['general']['short_description'] == null || strlen($t['general']['short_description']) == 0 )
			$fail[] = "The 'short_description' cannot be null or empty for 'general' translation in '{$l}'";
		if( $t['general']['long_description'] == null || strlen($t['general']['long_description']) == 0 )
			$fail[] = "The 'long_description' cannot be null or empty for 'general' translation in '{$l}'";
		if( $t['general']['config_url'] != null && !preg_match("!^https?://.*$!", $t['general']['config_url']) )
			$fail[] = "The 'config_url' must be null or a valid url for 'general' translation in '{$l}'";
		if( $t['general']['panel_url'] != null && !preg_match("!^https?://.*$!", $t['general']['panel_url']) )
			$fail[] = "The 'panel_url' must be null or a valid url for 'general' translation in '{$l}'";
		if( $t['general']['product_url'] != null && !preg_match("!^https?://.*$!", $t['general']['product_url']) )
			$fail[] = "The 'product_url' must be null or a valid url for 'general' translation in '{$l}'";
		if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
		
		// check interfaces translation
		if( !is_array($t['interfaces']) ) { 			$fail[] = "Entry 'interfaces' for translation in '{$l}' must be an object"; continue; }
		foreach( $i_keys as $key )
			if( !isset($t['interfaces'][$key]) )
				$fail[] = "Missing translation for interface '{$key}' in '{$l}'";
		foreach( $t['interfaces'] as $key=>$i )
		{
			if( !is_array($i) ) { 						$fail[] = "Entry '{$key}' for translation of interfaces in '{$l}' must be an object"; continue; }
			if( !in_array($key, $i_keys) ) { 			$fail[] = "Translation for interface '{$key}' in '{$l}' is useless because there is no such interface"; continue; }
			if( !array_key_exists('name', $i) )			$fail[] = "Missing 'name' entry for translation of interface '{$key}' in '{$l}'";
			if( !array_key_exists('description', $i) )	$fail[] = "Missing 'description' entry for translation of interface '{$key}' in '{$l}'";
			if( !array_key_exists('rule', $i) )			$fail[] = "Missing 'rule' entry for translation of interface '{$key}' in '{$l}'";
			if( $i['name'] == null || strlen($i['name']) == 0 )
				$fail[] = "The 'name' cannot be null or empty for translation of interface '{$key}' in '{$l}'";
			if( $i['description'] == null || strlen($i['description']) == 0 )
				$fail[] = "The 'description' cannot be null or empty for translation of interface '{$key}' in '{$l}'";
			foreach( $data['interfaces'] as $i2 ) if( $i2['key'] == $key && $i2['rule'] != null && ($i['rule'] == null || strlen($i['rule']) == 0) )
				$fail[] = "The 'rule' cannot be null or empty for translation of interface '{$key}' in '{$l}'";
		}
		if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
		
		// check configs translation
		if( !is_array($t['configs']) ) { 			$fail[] = "Entry 'configs' for translation in '{$l}' must be an object"; continue; }
		foreach( $c_keys as $key )
			if( !isset($t['configs'][$key]) )
				$fail[] = "Missing translation for config '{$key}' in '{$l}'";
		foreach( $t['configs'] as $key=>$c )
		{
			if( !is_array($c) ) { 						$fail[] = "Entry '{$key}' for translation of configs in '{$l}' must be an object"; continue; }
			if( !in_array($key, $c_keys) ) { 			$fail[] = "Translation for config '{$key}' in '{$l}' is useless because there is no such config"; continue; }
			if( !array_key_exists('name', $c) )			$fail[] = "Missing 'name' entry for translation of config '{$key}' in '{$l}'";
			if( !array_key_exists('description', $c) )	$fail[] = "Missing 'description' entry for translation of config '{$key}' in '{$l}'";
			if( !array_key_exists('rule', $c) )			$fail[] = "Missing 'rule' entry for translation of config '{$key}' in '{$l}'";
			if( $c['name'] == null || strlen($c['name']) == 0 )
				$fail[] = "The 'name' cannot be null or empty for translation of config '{$key}' in '{$l}'";
			if( $c['description'] == null || strlen($c['description']) == 0 )
				$fail[] = "The 'description' cannot be null or empty for translation of config '{$key}' in '{$l}'";
			foreach( $data['configs'] as $c2 ) if( $c2['key'] == $key && $c2['rule'] != null && ($c['rule'] == null || strlen($c['rule']) == 0) )
				$fail[] = "The 'rule' cannot be null or empty for translation of config '{$key}' in '{$l}'";
		}
		if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
	}
	if( count($fail) ) throw new \Exception("Invalid json file : \n{\n\t" . implode("\n\t", $fail) . "\n}\n");
	
	return $data;
}

$data = validate_json(file_get_contents('CONNECTORS/'.$_POST['connector'].'.json'));

$content = "You selected the connector <strong>{$_POST['connector']}</strong>.
<br />
<form action=\"/3\" method=\"post\">
<input type=\"hidden\" name=\"connector\" value=\"{$_POST['connector']}\" />
<h1>1. Configurations</h1>
<table>
	<tr>
		<th>Name (Key)</th>
		<th>Hidden?</th>
		<th>Bindable?</th>
		<th>Default value</th>
	</tr>
";

foreach( $data['configs'] as $c )
{
	$content .= "
	<tr>
		<td>{$data['translation']['en']['configs'][$c['key']]['name']} ({$c['key']})</td>
		<td>".($c['hidden']?"TRUE":"FALSE")."</td>
		<td>".($c['bindable']?"TRUE":"FALSE")."</td>
		<td>";
	if( $c['hidden'] )
		$content .= "<input type=\"hidden\" name=\"configs[{$c['key']}]\" value=\"{$c['default']}\" />{$c['default']}";
	else
		$content .= "<input type=\"text\" name=\"configs[{$c['key']}]\" value=\"{$c['default']}\" />";
	$content .= "
	</tr>
	";
}

$content .= "</table>
<h1>2. Interfaces</h1>

<h2>2.1 Inputs</h2>
<table>
	<tr>
		<th>Name (Key)</th>
		<th>Pattern</th>
		<th>Hidden?</th>
		<th>Automatic?</th>
		<th>Custom?</th>
	</tr>
";

foreach( $data['interfaces'] as $i )
{
	if( $i['direction'] == 'input' )
	{
		$content .= "
		<tr>
			<td>{$data['translation']['en']['interfaces'][$i['key']]['name']} ({$i['key']})</td>
			<td>{$i['pattern']}</td>
			<td>".($i['hidden']?"TRUE":"FALSE")."</td>
			<td>".($i['automatic']?"TRUE":"FALSE")."</td>
			<td>";
		if( $i['custom'] )
			$content .= "<input type=\"text\" name=\"inputs[{$data['translation']['en']['interfaces'][$i['key']]['name']}][value]\" value=\"\" />
						<input type=\"hidden\" name=\"inputs[{$data['translation']['en']['interfaces'][$i['key']]['name']}][key]\" value=\"{$i['key']}\" />";
		else
			$content .= "<input type=\"hidden\" name=\"inputs[{$data['translation']['en']['interfaces'][$i['key']]['name']}][value]\" value=\"\" />
						<input type=\"hidden\" name=\"inputs[{$data['translation']['en']['interfaces'][$i['key']]['name']}][key]\" value=\"{$i['key']}\" />FALSE";
		$content .= "
			</td>
		</tr>
		";
	}
}

$content .= "
</table>
<h2>2.2 Outputs</h2>
<table>
	<tr>
		<th>Name (Key)</th>
		<th>Pattern</th>
		<th>Hidden?</th>
		<th>Automatic?</th>
		<th>Custom?</th>
	</tr>
";
foreach( $data['interfaces'] as $i )
{
	if( $i['direction'] == 'output' )
	{
		$content .= "
		<tr>
			<td>{$data['translation']['en']['interfaces'][$i['key']]['name']} ({$i['key']})</td>
			<td>{$i['pattern']}</td>
			<td>".($i['hidden']?"TRUE":"FALSE")."</td>
			<td>".($i['automatic']?"TRUE":"FALSE")."</td>
			<td>";
		if( $i['custom'] )
			$content .= "<input type=\"text\" name=\"outputs[{$data['translation']['en']['interfaces'][$i['key']]['name']}][value]\" value=\"\" />
						<input type=\"hidden\" name=\"outputs[{$data['translation']['en']['interfaces'][$i['key']]['name']}][key]\" value=\"{$i['key']}\" />";
		else
			$content .= "<input type=\"hidden\" name=\"outputs[{$data['translation']['en']['interfaces'][$i['key']]['name']}][value]\" value=\"\" />
						<input type=\"hidden\" name=\"outputs[{$data['translation']['en']['interfaces'][$i['key']]['name']}][key]\" value=\"{$i['key']}\" />FALSE";
	}
}

$content .= "</table>
<h1>3. Try</h1>
<h2>3.1 Sample data</h2>";

$tmp = "
<select name=\"sample_output\">";

$ok = false;
foreach( $data['interfaces'] as $i )
{
	if( $i['direction'] == 'output' )
	{
		$tmp .= "<option value=\"{$data['translation']['en']['interfaces'][$i['key']]['name']}\">{$data['translation']['en']['interfaces'][$i['key']]['name']}</option>";
		$ok = true;
	}
}


$tmp .= "
</select>
<input type=\"submit\" name=\"action[sample]\" value=\"Get sample\" />";

if( $ok ) $content .= $tmp;
else $content .= "<em>There are no candidate output interfaces to provide sample data.</em>";

$content .= "
<h2>3.2 Automatic</h2>";

$tmp = "
<select name=\"cron_output\">
";

$ok = false;
foreach( $data['interfaces'] as $i )
{
	if( $i['direction'] == 'output' && $i['automatic'] )
	{
		$tmp .= "<option value=\"{$data['translation']['en']['interfaces'][$i['key']]['name']}\">{$data['translation']['en']['interfaces'][$i['key']]['name']}</option>";
		$ok = true;
	}
}

$tmp .= "
</select>
<input type=\"submit\" name=\"action[cron]\" value=\"Trigger now\" />";

if( $ok ) $content .= $tmp;
else $content .= "<em>There are no candidate automatic output interfaces.</em>";

$content .= "
<h2>3.3 Send message</h2>";

$tmp = "
<textarea name=\"content\"></textarea><br />
<select name=\"send_input\">";

$ok = false;
foreach( $data['interfaces'] as $i )
{
	if( $i['direction'] == 'input' )
	{
		$tmp .= "<option value=\"{$data['translation']['en']['interfaces'][$i['key']]['name']}\">{$data['translation']['en']['interfaces'][$i['key']]['name']}</option>";
		$ok = true;
	}
}
	
$tmp .= "<input type=\"submit\" name=\"action[send]\" value=\"Send now\" />";

if( $ok ) $content .= $tmp;
else $content .= "<em>There are no candidate input interfaces.</em>";

$content .= "</form>";

return $content;

?>