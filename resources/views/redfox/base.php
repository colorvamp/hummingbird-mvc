<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	{{#PAGE.FAVICON}}
	<link rel="icon" href="{{PAGE.FAVICON}}" type="image/png"/>
	{{/PAGE.FAVICON}}
	{{^PAGE.FAVICON}}
	<link rel="icon" href="{{w.indexURL}}/r/images/favicon.png" type="image/png"/>
	{{/PAGE.FAVICON}}
	<title>{{PAGE.TITLE}}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="{{META.DESCRIPTION}}"/>
	{{META.OG.IMAGE}}
	<script type="text/javascript" src="{{w.indexURL}}/r/js/coreJS.402.js"></script>
	<script type="text/javascript" src="{{w.indexURL}}/r/js/app.js"></script>
	<script type="text/javascript" src="{{w.indexURL}}/r/js/dropdown.js"></script>
	<script type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.tabs.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/svg.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.select.box.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.list.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.select.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.table.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.calendar.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.url.generator.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.node.checkbox.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.ad.search.js"></script>
	{{PAGE.SCRIPT}}
	{{PAGE.STYLE}}
	<link href="{{w.indexURL}}/r/css/redfox.css" type="text/css" rel="stylesheet">
	<link href="{{w.indexURL}}/r/css/font-awesome.min.css" type="text/css" rel="stylesheet">
</head>
<body class="redfox">
	<header>
		<div class="menu-button"><i class="fa fa-chevron-left" aria-hidden="true"></i></div>
		<h1>Red<span class="soft-orange">Fox</span></h1>
		{{#user__id}}
		<div class="logo">
			<a href="{{w.assisURL}}"><img src="{{user_src.user.64}}"></a>
			<div>{{user_userName}}</div>
		</div>
		{{/user__id}}
		<nav>
			<a href="{{w.redfoxURL}}/aproc" {{#display.aproc}}class="active"{{/display.aproc}}><i class="fa fa-cogs" aria-hidden="true"></i>Process</a>
			<a href="{{w.redfoxURL}}/auser" {{#display.auser}}class="active"{{/display.auser}}><i class="fa fa-users" aria-hidden="true"></i>Users</a>
		</nav>
	</header>
	<main>
		<div class="menu-button"><i class="fa fa-bars" aria-hidden="true"></i></div>
		{{MAIN}}
	</main>
</body>
</html>
