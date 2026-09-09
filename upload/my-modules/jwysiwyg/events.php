<?php
/**
 * 68KB
 *
 * An open source knowledge base script
 *
 * @package		68kb
 * @author		68kb Dev Team
 * @copyright	Copyright (c) 2009, 68 Designs, LLC
 * @license		http://68kb.com/user_guide/license.html
 * @link		http://68kb.com
 * @since		Version 1.0
 */

// ------------------------------------------------------------------------

/**
 * jwysiwyg
 *
 * @package		68kb
 * @subpackage	Module
 * @category	Module
 * @author		68kb Dev Team
 * @link		http://68kb.com/user_guide/controller/article.html
 * @version 	$Id: events.php 45 2009-07-28 17:20:56Z suzkaw68 $
 */

class jwysiwyg_events
{
	function __construct(&$core_events)
	{
		$core_events->register('glossary/form', $this, 'show_editor');
		$core_events->register('category/form', $this, 'show_editor');
		$core_events->register('articles/form/description', $this, 'show_editor');
		/*$core_events->register('articles/form/description', $this, 'show_custom_editor');*/
		$core_events->register('comment/form', $this, 'show_editor');
	}
	
	function show_editor()
	{
		// Summernote (self-hosted, jQuery based). Assets are loaded by the admin
		// layout (themes/admin/default/layout.php). We initialise the editor on
		// both the standard rich-text textarea (#editcontent, used by article /
		// category / glossary forms) and the admin comment textarea
		// (#comment_content), which previously had no editor at all.
		//
		// Images are embedded inline as base64 (Summernote's default), so no
		// upload endpoint is required; the separate file-attachment field on the
		// article form is unaffected.
		// NOTE: the 'comment/form' hook also fires on the public front-end, where
		// jQuery/Summernote are intentionally NOT loaded. Guarding on typeof
		// jQuery keeps the front-end comment box a plain textarea and avoids a
		// ReferenceError that would break other inline scripts on that page.
		$output = "
		<script type=\"text/javascript\">
		if (typeof jQuery !== 'undefined') {
		jQuery(function($) {
			if (typeof $.fn.summernote === 'undefined') {
				if (window.console) { console.error('Summernote not loaded - check js/summernote paths'); }
				return;
			}
			var kbEditorOptions = {
				height: 300,
				dialogsInBody: true,
				toolbar: [
					['style', ['style', 'bold', 'italic', 'underline', 'clear']],
					['font', ['color']],
					['para', ['ul', 'ol', 'paragraph']],
					['insert', ['link', 'picture']],
					['view', ['codeview']]
				]
			};
			$('#editcontent, #comment_content').each(function() {
				$(this).summernote(kbEditorOptions);
			});
		});
		}
		</script>";

		echo $output;
	}
}

/* End of file events.php */
/* Location: ./upload/my-modules/jwysiwyg/events.php */ 
