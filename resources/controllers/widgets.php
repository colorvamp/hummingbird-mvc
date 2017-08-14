<?php
	function widgets_main(){
		global $TEMPLATE;

		$TEMPLATE['PAGE.TITLE'] = 'widgets';
		return common_renderTemplate('widgets/main');
	}

	function widgets_list(){
		global $TEMPLATE;

		$TEMPLATE['JS.WIDGETS'] = true;
		$TEMPLATE['PAGE.TITLE'] = 'widgets - list';
		return common_renderTemplate('widgets/list');
	}

	function widgets_scroll(){
		global $TEMPLATE;

		$TEMPLATE['JS.WIDGETS'] = true;
		$TEMPLATE['PAGE.TITLE'] = 'widgets - list';
		return common_renderTemplate('widgets/scroll');
	}

