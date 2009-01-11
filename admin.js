document.observe('dom:loaded', function() {
	toggleBtn = function() {
		$$('#att input[type=button]').invoke(($F('edit_id') && $F('edit_display')) ? 'enable' : 'disable');
		if ($H(buttons).keys().length <= 1) $('del_btn').disable();
	}
	
	updateFunc = function(event) {
		var id = Event.element(event).identify().substr(3);
		$('edit_id').value = id;
		$('edit_display').value = buttons[id].display ? buttons[id].display : '';
		$('edit_start').value = buttons[id].start ? buttons[id].start : '';
		$('edit_end').value = buttons[id].end ? buttons[id].end : '';
		$('edit_access').value = buttons[id].access ? buttons[id].access : '';
		toggleBtn();
	};
	
	beSortable = function() {
		Sortable.create('ed_toolbar', {
			tag: 'span',
			overlap: 'horizontal',
			constraint: 'horizontal'
		});
		toggleBtn();
	}
	beSortable();
	
	$$('#ed_toolbar span').invoke('observe', 'click', updateFunc);
	
	$('edit_id', 'edit_display').invoke('observe', 'change', toggleBtn);
	
	$('save_btn').observe('click', function() {
		var id = $F('edit_id');
		var display = $F('edit_display');
		
		if (id && display) {
			isExists = buttons[id] ? true : false;
			buttons[id] = {};
			buttons[id].display = display;
			buttons[id].start = $F('edit_start');
			buttons[id].end = $F('edit_end');
			buttons[id].access = $F('edit_access');
			
			if (isExists) {
				$('ed_'+id).update(display);
			} else {
				var new_btn = new Element('span', {'class': 'ed_button', 'id': 'ed_'+id}).update(display).hide().observe('click', updateFunc);
				$('ed_toolbar').appendChild(new_btn);
				new_btn.appear({duration: 0.6});
				beSortable();
			}
		}
	});
	
	$('del_btn').observe('click', function() {
		if ($H(buttons).keys().length == 1) return;
		
		var id = $F('edit_id');
		
		if (id && buttons[id]) {
			delete buttons[id];
			$('ed_'+id).fade({
				duration: 0.6,
				afterFinishInternal: function(effect){
					effect.element.remove();
					$$('#att input[type=text]').invoke('clear');
					beSortable();
				}
			});
		}
	});
	
	$('clear_btn').observe('click', function(){
		$$('#att input[type=text]').invoke('clear');
		toggleBtn();
	});
	
	$('sform').observe('submit', function(event){
		$('sort').value = Sortable.serialize('ed_toolbar', {tag: 'span'});
		$('tags').value = $H(buttons).toJSON();
	});
	
	$('rform').observe('submit', function(event){
		if (!confirm(cfqadminL10n['removeConfirm'])) {
			Event.stop(event);
			return false;
		}
	});
});