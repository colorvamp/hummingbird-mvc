<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<link rel="icon" href="{{w.indexURL}}/r/images/favicon.png" type="image/png"/>
	<title>{{PAGE.TITLE}}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="{{META.DESCRIPTION}}"/>
	{{META.OG.IMAGE}}
	<link href="{{w.indexURL}}/r/css/index.css" rel="stylesheet"/>
	<link href="{{w.indexURL}}/r/css/font-awesome.min.css" rel="stylesheet"/>
	{{#JS.WIDGETS}}
	<script type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.list.js"></script>
	<script type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.scroll.js"></script>
	{{/JS.WIDGETS}}
</head>
<body>
	<header>
		<div class="container">
			<ul class="navigation">
				<li><h1>HummingBird</h1></li>
				<li><a href="{{w.indexURL}}/redfox">Admin Panel</a></li>
			</ul>
			<div class="presentation">
				Hi!
			</div>
			<ul class="tabs">
				<li class="tab {{#main.section.main}}active{{/main.section.main}}"><a class="label" href="{{w.indexURL}}"><i class="fa fa-circle-o" aria-hidden="true"></i> Main</a></li>
				<li class="tab {{#main.section.widgets}}active{{/main.section.widgets}}"><a class="label" href="{{w.indexURL}}/widgets"><i class="fa fa-puzzle-piece" aria-hidden="true"></i> Widgets</a></li>
				<li class="tab {{#main.section.shoutbox}}active{{/main.section.shoutbox}}"><a class="label" href="{{w.indexURL}}/shoutbox"><i class="fa fa-bullhorn" aria-hidden="true"></i> Shoutbox</a></li>
				<li class="tab"><span class="label">Options</span></li>
			</ul>
		</div>
	</header>
	<main>
		<div class="container">
			{{MAIN}}
		</div>
	</main>
	<footer>
		<div class="container">

		</div>
	</footer>
</body>
</html>
