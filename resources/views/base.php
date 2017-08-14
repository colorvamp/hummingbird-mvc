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
	<script type="text/javascript" src="{%w.indexURL%}/r/js/widgets/widget.scroll.js"></script>
	{%/JS.WIDGETS%}
</head>
<body>
	<header>
		<div class="container">
			<a href="{%w.indexURL%}"><img src="{%w.indexURL%}/r/images/logo.png" alt="ColorVamp" title="ColorVamp"/></a>
			<h1><a href="{%w.indexURL%}">{%HTML.TITLE%}</a></h1>
			<h6>{%HTML.DESCRIPTION%}</h6>
			<ul class="tabs">
				<li class="tab {%#main.section.conversation%}active{%/main.section.conversation%}"><span class="label"><i class="fa fa-comments" aria-hidden="true"></i> Conversation</span></li>
				<li class="tab {%#main.section.widgets%}active{%/main.section.widgets%}"><a class="label" href="{%w.indexURL%}/widgets"><i class="fa fa-puzzle-piece" aria-hidden="true"></i> Widgets</a></li>
				<li class="tab"><span class="label">Options</span></li>
			</ul>
		</div>
	</header>
	<main>
		<div class="container">
			{%MAIN%}
		</div>
	</main>
	<footer>
		<div class="container">

		</div>
	</footer>
</body>
</html>
