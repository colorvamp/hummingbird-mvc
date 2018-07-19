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
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/core.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widgets.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.table.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.list.js"></script>
	<script async type="text/javascript" src="{{w.indexURL}}/r/js/widgets/widget.scroll.js"></script>
	{{/JS.WIDGETS}}
</head>
<body>
	<div class="menu-top">
		<div class="inner">
			<ul class="navigation">
				<li><strong>HummingBird</strong></li>
				<li><a href="{{w.indexURL}}/redfox">Admin Panel</a></li>
			</ul>
		</div>
	</div>
	<header>
	</header>
	<main>{{MAIN}}</main>
	<footer>
		<div class="container">

		</div>
	</footer>
</body>
</html>
