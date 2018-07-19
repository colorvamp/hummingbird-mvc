	<article>
		<header class="header-red">
			<div class="inner">
				<div class="center">
					<h1>Widgets / Table</h1>
					<h2>HummingBird Widgets Documentation</h2>
				</div>
				{{@snippets/tabs.main}}
			</div>
		</header>
		<div class="inner">
			<div class="grid">
				<div class="row">
					<div class="col-9">
						<section>
							<h2>Table Widget</h2>
							<p>Functional table for representing data</p>
							<ul class="table widget-table">
								<li class="header"><div>Material</div><div>Quantity</div><div>Unit price</div></li>
								<li><div>Acrylic (Transparent)</div><div>25</div><div>$2.90</div></li>
								<li><div>Plywood (Birch)</div><div>50</div><div>$1.25</div></li>
								<li><div>Laminate (Gold on Blue)</div><div>10</div><div>$2.35</div></li>
							</ul>
						</section>
						<section>
							<h2>Event Driven API</h2>
							<p>Functional table for representing data</p>
							<ul class="table widget-table" id="table-test-1">
								<li class="header"><div>Material</div><div>Quantity</div><div>Unit price</div></li>
								<li><div>Acrylic (Transparent)</div><div>25</div><div>$2.90</div></li>
								<li><div>Plywood (Birch)</div><div>50</div><div>$1.25</div></li>
								<li><div>Laminate (Gold on Blue)</div><div>10</div><div>$2.35</div></li>
							</ul>
							<div class="btn-group">
								<div class="btn" onclick="document.querySelector('#table-test-1')
									.dispatchEvent(new CustomEvent('widget-item-add',{'detail':{'item':{'columns':['column1','column2','column3']}},'bubbles':true,'cancelable':true}))">Add row to table</div>
							</div>
							<p>
<code>var _table = document.querySelector('#table-test-1');
var _event = new CustomEvent('widget-item-add',{'detail':{'item':{'columns':['column1','column2','column3']}},'bubbles':true,'cancelable':true});
_table.dispatchEvent(_event);
</code>
							</p>
						</section>
					</div>
					<div class="col-3">

					</div>
				</div>
			</div>
		</div>
	</article>
