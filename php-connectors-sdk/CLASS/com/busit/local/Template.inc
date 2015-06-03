<?php
namespace com\busit\local;

class Template
{
	private static function top()
	{
		return "<!Doctype html>
<html>
<head>
	<title>Busit Local Broker</title>
	<link rel=\"stylesheet\" href=\"/ext/main.css\" />
</head>
<body>
<header>Connector Tester<nav><a href=\"/\">Home</a></header>
<main>
";
	}
	
	private static function bottom()
	{
		return "
</main>
<footer>&copy; busit.com</footer>
</body>
</html>";
	}
	
	public static function output($content)
	{
		echo self::top();
		echo $content;
		echo self::bottom();
	}
	
	public static function error()
	{
		echo self::top();
		echo "<pre class=\"error\">" . file_get_contents("TEMP/busit.log") . "</pre>";
		echo self::bottom();
		file_put_contents("TEMP/busit.log", ""); // reset the log
	}
}

?>