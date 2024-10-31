(function() {
    tinymce.PluginManager.add('nurkochen', function( editor, url ) {
        var sh_tag = 'nurkochen';

            editor.addButton('nurkochen', {
			    icon: false,
			    text: 'Nurkochen',
			    tooltip: 'Nurkochen shortcode',
			    onclick: function() {
			        editor.insertContent("[nurkochen]");
			    }
			});
    });
})();