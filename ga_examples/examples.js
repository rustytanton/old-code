function loadExample(id) {
	
	var examples = {
		js01 : {
			lang : 'javascript',
			src : 'js01.src',
			title : 'Javascript example 1: external JS structure'
		},
		js02 : {
			lang : 'javascript',
			src : 'js02.src',
			title : 'Javascript example 2: load/initiate Google Analytics'
		},
		js03 : {
			lang : 'javascript',
			src : 'js03.src',
			title : 'Javascript example 3: global page tracking function'
		},
		js04 : {
			lang : 'javascript',
			src : 'js04.src',
			title : 'Javascript example 4: add tracking to external links within blog entries'
		},
		js05 : {
			lang : 'javascript',
			src : 'js05.src',
			title : 'Javascript example 5: add tracking to sidebar blogroll module'
		},
		js06 : {
			lang : 'javascript',
			src : 'js06.src',
			title : 'Javascript example 6: add tracking to comment submit form'
		},
		js07 : {
			lang : 'javascript',
			src : 'js07.src',
			title : 'Javascript example 7: add tracking to thickboxes'
		},
		php01 : {
			lang : 'php',
			src : 'php01.src',
			title : 'PHP example 1: organizing plugin/functions.php code'
		},
		php02 : {
			lang : 'php',
			src : 'php02.src',
			title : 'PHP example 2: add external Javascript file to the page'
		},
		php03 : {
			lang : 'php',
			src : 'php03.src',
			title : 'PHP example 3: create associative array of JS parameters'
		},
		php04 : {
			lang : 'php',
			src : 'php04.src',
			title : 'PHP example 4: modify RSS URLs with campaign parameters'
		}
	};
	
	location.hash = '#' + id;
	
	$.get('code.php', examples[id], function(data) {
		$('#title h1, #code').hide();
		$('#title h1').text(examples[id].title).show('slow', function() {
			$('#code').html(data).show('slow');
		});
	});
	
}

$(function() {
	
	// add click events to nav
	$('#nav a').click(function() {
		var id = this.href.split('#')[1];
		loadExample(id);
		return false;
	});
	
	// load first example, default to php01 if none specified
	loadExample(window.location.hash.replace(/#/g, '') || 'php01');
});