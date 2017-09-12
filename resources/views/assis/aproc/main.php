<main>
	<header class="node">
		<div class="image s64"><i class="fa fa-code-fork"></i></div>
		<div class="wrapper">
			<h1>Workers</h1>
			<p>Server workers management</p>
		</div>
	</header>
	{%@aproc/snippets/main.tabs%}
	<section>
		<h2><i class="fa fa-list"></i> Scheduled workers awaiting</h2>
		<p>The next table will show the workers scheduled or awaiting to be launched.</p>
		<div class="btn-group">
			<div class="btn dropdown-toggle"><i class="fa fa-plus"></i> Create
				<div class="dropdown-menu padded" style="min-width:400px;">
					<h4><i class="fa fa-plus"></i> Enqueue a worker</h4>
					<p>Add a worker to the process pool.</p>
					<form method="post">
						<input type="hidden" name="subcommand" value="worker.save">
						<ul class="table">
							<li>
								<div>Worker</div>
								<div>
									<select name="worker">
										{%#workers%}
										<option value="{%name%}">{%name%}</option>
										{%/workers%}
									</select>
								</div>
							</li>
							<li>
								<div>Status</div>
								<div>
									<select name="status">
										<option value="test">test</option>
										<option value="awaiting">awaiting</option>
										<option value="scheduled">scheduled</option>
									</select>
								</div>
							</li>
							<li>
								<div>LockMode</div>
								<div>
									<select name="lockMode">
										<option value="shared">shared</option>
										<option value="exclusive">exclusive</option>
									</select>
								</div>
							</li>
							<li>
								<div>Lock</div>
								<div>
									<input type="text" name="lock" placeholder="empty for worker name">
									<p class="font-small">The lock string is used to detect and avoid collisions between workers. Two workers
										can be not compatible, so if the lockMode is exclusive, two with the same lock
										can't run at the same time.</p>
								</div>
							</li>
							<li>
								<div>Minutes</div>
								<div>
									<select name="minutes">
										<option value="*">*</option>
										<option value="0">0</option>
										<option value="*/15">*/15</option>
										<option value="*/30">*/30</option>
									</select>
								</div>
							</li>
							<li>
								<div>Hours</div>
								<div>
									<select name="hours">
										<option value="*">*</option>
										<option value="*/2">*/2</option>
										<option value="*/6">*/6</option>
									</select>
								</div>
							</li>
							<li>
								<div>Params</div>
								<div>
									<input type="text" name="params" placeholder="{'json':'format'}">
								</div>
							</li>
						</ul>
						<div class="btn-group right">
							<div class="btn btn-close"><i class="fa fa-close"></i> Close</div>
							<button class="btn main"><i class="fa fa-save"></i> Save</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<ul class="table">
			<li class="header">
				<div>Status</div>
				<div class="fit">LockMode</div>
				<div>Command</div>
			</li>
			{%html.table.commands%}
			{%#workerOBs%}
			<li>
				<div>
					{%procStatus%}
					<div class="btn-group mini">
						<div class="btn dropdown-toggle"><i class="fa fa-rocket"></i> Launch
							<div class="dropdown-menu padded">
								<div class="box">
									<h4><i class="fa fa-rocket"></i> Manual Launch</h4>
									<p>Launch options for this worker.</p>
									<div>cd resources/cli</div>
									<div>php cli.proc.php worker {%procWorker%} {%_id%}</div>
								</div>
								<div class="box">
									<h4><i class="fa fa-rocket"></i> Launch Now</h4>
									<p>Send the worker to the process queue.</p>
									<form method="post">
										<input type="hidden" name="subcommand" value="worker.awaiting">
										<input type="hidden" name="_id" value="{%_id%}">
										<div class="block-warning">
											This worker will go to the process queue. The launch daemon will start
											it as soon as possible.
											<div class="btn-group mini right">
												<button class="btn"><i class="fa fa-rocket"></i> Launch</button>
											</div>
										</div>
									</form>
								</div>
								<div class="btn-group right">
									<div class="btn btn-close">Close</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div>
					{%procLockMode%}
				</div>
				<div>
					{%procWorker%}
					{%#html.next.launch%}
						({%html.next.launch%})
					{%/html.next.launch%}
					<div class="btn-group mini">
						<div class="btn dropdown-toggle"><i class="fa fa-cogs"></i> Parameters
							<div class="dropdown-menu padded">
								<h4><i class="fa fa-cogs"></i> Parameters</h4>
								<p>Worker parameters.</p>
								<div>{%html.params%}</div>
								<div class="btn-group right">
									<div class="btn btn-close">Close</div>
								</div>
							</div>
						</div>
						<div class="btn dropdown-toggle"><i class="fa fa-trash"></i> Remove
							<div class="dropdown-menu padded">
								<h4><i class="fa fa-trash"></i> Remove</h4>
								<p>Remove Worker.</p>
								<form method="post">
									<input type="hidden" name="subcommand" value="worker.remove">
									<input type="hidden" name="_id" value="{%_id%}">
									<div class="btn-group right">
										<div class="btn btn-close">Close</div>
										<button class="btn"><i class="fa fa-trash"></i> Remove</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</li>
			{%/workerOBs%}
		</ul>
	</section>
	<div class="two-column">
		<div class="column">
			<section>
				<h2><i class="fa fa-cog"></i> Workers launcher</h2>
				<p>To dispatch workers with no delay we need a daemon to start the workers processes.</p>
				<ul class="table radio">
					<li><div>Status</div><div>{%daemon_procStatus%}</div></li>
					<li><div>PID</div><div>{%daemon_pid%}</div></li>
					<li><div>Date</div><div>{%daemon_procDate%} {%daemon_procTime%}</div></li>
				</ul>
			</section>
		</div>
	</div>
</main>
