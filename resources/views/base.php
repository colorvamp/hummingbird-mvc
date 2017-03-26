<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<link rel="icon" href="{%w.indexURL%}/r/images/favicon.png" type="image/png"/>
	<title>{%PAGE.TITLE%}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="{%META.DESCRIPTION%}"/>
	{%META.OG.IMAGE%}
	<link href="{%w.indexURL%}/r/css/index.css" rel="stylesheet"/>
	<link href="{%w.indexURL%}/r/css/font-awesome.min.css" rel="stylesheet"/>
	{%#JS.WIDGETS%}
	<script type="text/javascript" src="{%w.indexURL%}/r/js/widgets/widget.list.js"></script>
	{%/JS.WIDGETS%}
</head>
<body>
	<div class="wrapper">
		<div class="header">
			<a href="{%w.indexURL%}"><img src="{%w.indexURL%}/r/images/logo.png" alt="ColorVamp" title="ColorVamp"/></a>
			<h1><a href="{%w.indexURL%}">{%HTML.TITLE%}</a></h1>
			<h6>{%HTML.DESCRIPTION%}</h6>
		</div>
		<div class="body">
			{%MAIN%}
		</div>
		<div class="footer">
			
		</div>
	</div>
</body>
</html>
