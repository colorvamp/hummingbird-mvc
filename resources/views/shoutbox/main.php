	<div class="wrapper">
		{%permission.warning%}
		{%#shoutOBs%}
			{%@snippets/shout.node%}
		{%/shoutOBs%}
		<div class="block">
			<div class="image">
				<div class="glow"></div>
				<img src="{%w.indexURL%}/images/avatar"/>
			</div>
			<div class="wrapper">
				<div class="block-header">
					<ul class="tabs">
						<li class="tab active"><span class="label">Write</span></li>
						<li class="tab"><span class="label">Preview</span></li>
						<li class="tab"><span class="label">Options</span></li>
					</ul>
				</div>
				<div class="block-content">
					<form method="post">
						<input type="hidden" name="subcommand" value="comment.add">
						<div class="textarea">
							<textarea placeholder="Leave a comment" name="shoutText"></textarea>
							<div class="textarea-footer">
								Info
							</div>
						</div>

						<div class="btn-group right">
							<div class="btn disabled">Button disabled</div>
							<button class="btn green">Comment</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
