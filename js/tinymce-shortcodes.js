(function() {

    tinymce.PluginManager.add('oembedrteshortcodes', function( editor )
    {
		
		 editor.addMenuItem('shortcode_karte', {
                        text: 'Lageplan (FAU-Karte) einfügen',
                        context: 'tools',
                        onclick: function() {
                                editor.insertContent('[faukarte url=""]');
                        }
                });

    });
})();