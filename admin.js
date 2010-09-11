document.observe('dom:loaded', function() {
	function toggleBtn() {
		$('save_btn', 'del_btn').each(function(element) {
			element.disabled = !($F('edit_id') && $F('edit_display'));
		});
		if ($H(buttons).keys().length <= 1) $('del_btn').disable();
		
		var i = false;
		$$('#att input[type=text]').each(function(element) {i = i || $F(element)});
		if (i) $('clear_btn').enable(); else $('clear_btn').disable();
	}
	
	function select(element) {
		$$('#ed_toolbar span').invoke('removeClassName', 'selected');
		element.addClassName('selected');
	}
	
	function updateFunc(event) {
		var element = Event.element(event);
		var id = element.identify().substr(3);
		$('edit_id').value = id;
		$('edit_display').value = buttons[id].display ? buttons[id].display : '';
		$('edit_start').value = buttons[id].start ? buttons[id].start : '';
		$('edit_end').value = buttons[id].end ? buttons[id].end : '';
		$('edit_access').value = buttons[id].access ? buttons[id].access : '';
		select(element);
		toggleBtn();
	}
	
	function beSortable() {
		Sortable.create('ed_toolbar', {
			tag: 'span',
			overlap: 'horizontal',
			constraint: 'horizontal'
		});
		toggleBtn();
	}
	beSortable();
	
	$$('#ed_toolbar span').invoke('observe', 'click', updateFunc);
	new Form.Observer('att', 0.5, toggleBtn);
	
	$('save_btn').observe('click', function() {
		var id = $F('edit_id');
		var display = $F('edit_display').escapeHTML();
		
		if (id && display) {
			var isExists = buttons[id] ? true : false;
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
				select(new_btn);
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
	
	$('sform').observe('submit', function(){
		$('sort').value = Sortable.serialize('ed_toolbar', {tag: 'span'});
		$('tags').value = $H(buttons).toJSON();
	});
	
	$('cap_check').observe('change', function(){
		$$('#roles input[type=checkbox]').invoke(this.checked ? 'enable' : 'disable');
	});
	
	$('rform').observe('submit', function(event){
		if (!confirm(cfqadminL10n['removeConfirm'])) {
			Event.stop(event);
			return false;
		}
		return true;
	});
});
