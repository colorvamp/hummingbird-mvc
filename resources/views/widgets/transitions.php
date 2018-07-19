	<article>
		<header class="header-red">
			<div class="inner">
				<div class="center">
					<h1>Widgets / Transitions</h1>
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
							<h2>Transitionable content</h2>
							<p>Transitionable content</p>
							<div class="widget-state">
								<div data-state="initial">
									Initial State
									<div class="btn-group">
										<button class="btn main" data-to-state="second-state" onclick="$transition.toState(this.parentNode.parentNode.parentNode,'second-state');">Go!</button>
									</div>
								</div>
								<div class="hidden" data-state="second-state">
									<span>Second State</span>
									<p>With more height</p>
									<div class="btn-group">
										<button class="btn main" data-to-state="initial" onclick="$transition.toState(this.parentNode.parentNode.parentNode,'initial');">Go!</button>
									</div>
								</div>
							</div>
							<p>Test line</p>
						</section>
					</div>
					<div class="col-3">

					</div>
				</div>
			</div>
		</div>
	</article>
