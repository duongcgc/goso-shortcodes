/* ----------------------------------------------------- */
/* This file for register button insert shortcode to TinyMCE
 /* ----------------------------------------------------- */
(function () {
	tinymce.create( 'tinymce.plugins.goso_pre_shortcodes_button', {
		init         : function ( ed, url ) {
			title = 'goso_pre_shortcodes_button';
			tinymce.plugins.goso_pre_shortcodes_button.theurl = url;
			ed.addButton( 'goso_pre_shortcodes_button', {
				title: 'Select Shortcode',
				text: 'Authow',
				icon : 'wp_code',
				type : 'menubutton',
				/* List Button */
				menu : [
					/* --- Text Padding --- */
					{text: 'Text Padding',classes: 'text-padding', menu: [
						{
							text: 'Text ⇠',
							value: 'text-padding-right-1',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<div class="goso-tpadding-1">' + ed.selection.getContent() + '</div>');
							}
						},
						{
							text: '⇢ Text',
							value: 'text-padding-left-1',
							onclick: function () {
								content = ed.selection.getContent();
								ed.execCommand('mceInsertContent', 0, '<div class="goso-tpadding-2">' + ed.selection.getContent() + '</div>');
							}
						},
						{
							text: '⇢ Text ⇠',
							value: 'text-padding-1',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<div class="goso-tpadding-3">' + ed.selection.getContent() + '</div>');
							}
						},
						{
							text: '⇢ Text ⇠⇠',
							value: 'text-padding-right-2',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<div class="goso-tpadding-4">' + ed.selection.getContent() + '</div>');
							}
						},
						{
							text: '⇢⇢ Text ⇠',
							value: 'text-padding-left-2',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<div class="goso-tpadding-5">' + ed.selection.getContent() + '</div>');
							}
						},
						{
							text: '⇢⇢  Text ⇠⇠',
							value: 'text-padding-2',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<div class="goso-tpadding-6">' + ed.selection.getContent() + '</div>');
							}
						},
						{
							text: '⇢⇢⇢ Text ⇠⇠⇠',
							value: 'text-padding-3',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<div class="goso-tpadding-7">' + ed.selection.getContent() + '</div>');
							}
						},
					]},
					/* --- Dropcap --- */
					{text: 'Drop cap',classes: 'drop-cap', menu: [
						{
							text: 'Box',
							value: 'goso-dropcap-box',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-dropcap-box">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'Box Outline',
							value: 'goso-dropcap-box',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-dropcap-box-outline">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'Circle',
							value: 'goso-dropcap-circle',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-dropcap-circle">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'CircleOutline',
							value: 'goso-dropcap-circle',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-dropcap-circle-outline">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'Regular',
							value: 'goso-dropcap-regular',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-dropcap-regular">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'Bold',
							value: 'goso-dropcap-bold',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-dropcap-bold">' + ed.selection.getContent() + '</span>');
							}
						}
					]},
					/* --- Text Highlight --- */
					{text: 'Text highlight',classes: 'text-highlight', menu: [
						{
							text: 'Black',
							value: 'goso-highlight-black',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-highlight-black">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'Highlighted Black',
							value: 'goso-highlighted-black',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-highlighted-black">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'Highlighted Red',
							value: 'goso-highlight-red',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-highlighted-red">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'Highlighted Blue',
							value: 'goso-highlight-blue',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-highlighted-blue">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'Highlighted Green',
							value: 'goso-highlight-green',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-highlighted-green">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'Highlighted Yellow',
							value: 'goso-highlight-yellow',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-highlighted-yellow">' + ed.selection.getContent() + '</span>');
							}
						},
						{
							text: 'Highlighted Pink',
							value: 'goso-highlight-pink',
							onclick: function () {
								ed.execCommand('mceInsertContent', 0, '<span class="goso-highlighted-pink">' + ed.selection.getContent() + '</span>');
							}
						}
					]},
					/* --- Button --- */
					{text   : 'Button',
						value  : 'Button',
						onclick: function () {
							ed.windowManager.open( {
								title   : 'Button',
								body    : [
									{ type: 'textbox', name: 'content', label: 'Text', value: 'Click me' },
									{ type: 'textbox', name: 'link', label: 'Link', value: '#' },
									{ type: 'textbox', name: 'radius', label: 'Border Radius (E.g: 10px)', value: '' },
									{ type: 'textbox', name: 'text_color', label: 'Custom Text Color', value: '' },
									{ type: 'textbox', name: 'background', label: 'Custom Background Color', value: '' },
									{ type: 'textbox', name: 'text_hcolor', label: 'Custom Text Hover Color', value: '' },
									{ type: 'textbox', name: 'hbackground', label: 'Custom Background Hover Color', value: '' },
									{ type: 'listbox', name: 'size', label   : 'Size', 'values': [{ text: 'Default', value: '' }, { text: 'Small', value: 'small' }, { text: 'Large', value: 'large' }] },
									{ type: 'textbox', name: 'icon', label: 'Icon', value: 'fa fa-address-book' },
									{ type: 'listbox', name: 'icon_position', label   : 'Icon Position', 'values': [{ text: 'Left', value: 'left' }, { text: 'Right', value: 'right' }] },
									{ type: 'listbox', name: 'align', label   : 'Align', 'values': [{ text: 'None', value: '' }, { text: 'Left', value: 'left' }, { text: 'Center', value: 'center' }, { text: 'Right', value: 'right' }] },
									{ type: 'listbox', name: 'full', label   : 'Full Width', 'values': [{ text: 'No', value: '' }, { text: 'Yes', value: '1' }] },
									{ type: 'listbox', name: 'target', label   : 'Link Target', 'values': [{ text: 'Default', value: '' }, { text: 'New window/tab', value: '_blank' }] },
									{ type: 'listbox', name: 'nofollow', label   : 'Nofollow', 'values': [{ text: 'No', value: '' }, { text: 'Yes', value: '1' }] },
									{ type: 'textbox', name: 'id', label: 'ID', value: '' },
									{ type: 'textbox', name: 'class', label: 'Class', value: '' },
									{ type: 'textbox', name: 'margin_bottom', label: 'Margin button(E.g: 20px)', value: '' },
								],
								onsubmit: function ( e ) {
									content = ed.selection.getContent();

									var $shortcode = '[goso_button  link="' + e.data.link + '" icon="' + e.data.icon + '" icon_position="' + e.data.icon_position + '"' +
									                 ( e.data.radius ? ' radius="' + e.data.radius + '"' : '' ) +
													 ( e.data.align ? ' align="' + e.data.align + '"' : '' ) +
									                 ( e.data.full ? ' full="' + e.data.full + '"' : '' ) +
									                 ( e.data.size ? ' size="' + e.data.size + '"' : '' ) +
									                 ( e.data.text_color ? ' text_color="' + e.data.text_color + '"' : '' ) +
									                 ( e.data.background ? ' background="' + e.data.background + '"' : '' ) +
									                 ( e.data.text_hcolor ? ' text_hover_color="' + e.data.text_hcolor + '"' : '' ) +
									                 ( e.data.hbackground ? ' hover_bgcolor="' + e.data.hbackground + '"' : '' ) +
									                 ( e.data.target ? ' target="' + e.data.target + '"' : '' ) +
									                 ( e.data.nofollow ? ' nofollow="' + e.data.nofollow + '"' : '' ) +
									                 ( e.data.id ? ' id="' + e.data.id + '"' : '' ) +
									                 ( e.data.class ? ' class="' + e.data.class + '"' : '' ) +
									                 ( e.data.margin_bottom ? ' margin_bottom="' + e.data.margin_bottom + '"' : '' ) +
									                 ']' +
									                 e.data.content + '[/goso_button]';

									ed.execCommand('mceInsertContent', 0, $shortcode );
								}
							} );
						}
					},
					/* --- Custom List --- */
					{text: 'Custom list',classes: 'custom-list', menu: [
						{
							text: 'Check List',
							value: 'goso-check-list',
							onclick: function () {
								ed.execCommand("InsertUnorderedList", false);
								setTimeout( GosoInsertUnorderedList( ed , 'goso_list-checklist' ), 200 );
							}
						},
						{
							text: 'Star List',
							value: 'goso-star-list',
							onclick: function () {
								ed.execCommand("InsertUnorderedList", false);
								setTimeout( GosoInsertUnorderedList( ed , 'goso_list-starlist' ), 200 );
							}
						},
						{
							text: 'Edit List',
							value: 'goso-edit-list',
							onclick: function () {
								ed.execCommand("InsertUnorderedList", false);
								setTimeout( GosoInsertUnorderedList( ed , 'goso_list-editlist' ), 200 );
							}
						},{
							text: 'Thumbup List',
							value: 'goso-thumbup-list',
							onclick: function () {
								ed.execCommand("InsertUnorderedList", false);
								setTimeout( GosoInsertUnorderedList( ed , 'goso_list-thumbuplist' ), 200 );
							}
						},{
							text: 'Thumbdown List',
							value: 'goso-thumbdown-list',
							onclick: function () {
								ed.execCommand("InsertUnorderedList", false);
								setTimeout( GosoInsertUnorderedList( ed , 'goso_list-thumbdownlist' ), 200 );
							}
						},{
							text: 'Plus List',
							value: 'goso-plus-list',
							onclick: function () {
								ed.execCommand("InsertUnorderedList", false);
								setTimeout( GosoInsertUnorderedList( ed , 'goso_list-pluslist' ), 200 );
							}
						},
						{
							text: 'Minus  List',
							value: 'goso-minus-list',
							onclick: function () {
								ed.execCommand("InsertUnorderedList", false);
								setTimeout( GosoInsertUnorderedList( ed , 'goso_list-minuslist' ), 200 );
							}
						},
						{
							text: 'Asterisk List',
							value: 'goso-asterisk-list',
							onclick: function () {
								ed.execCommand("InsertUnorderedList", false);
								setTimeout( GosoInsertUnorderedList( ed , 'goso_list-asterisklist' ), 200 );
							}
						},
						{
							text: 'Folder List',
							value: 'goso-folder-list',
							onclick: function () {
								ed.execCommand("InsertUnorderedList", false);
								setTimeout( GosoInsertUnorderedList( ed , 'goso_list-folderlist' ), 200 );
							}
						},
						{
							text: 'Heart List',
							value: 'goso-heart-list',
							onclick: function () {
								ed.execCommand("InsertUnorderedList", false);
								setTimeout( GosoInsertUnorderedList( ed , 'goso_list-heartlist' ), 200 );
							}
						}
					]},
					
					/* --- Blockquote --- */
					{
						text   : 'Blockquote',
						value  : 'Blockquote',
						onclick: function () {
							ed.windowManager.open( {
								title   : 'Blockquote',
								body    : [
									{ type    : 'listbox', name    : 'align', label   : 'Quote Align', 'values': [{ text: 'None', value: 'none' }, { text: 'Aligh Left', value: 'left' }, { text: 'Aligh Right', value: 'right' }] },
									{ type: 'textbox', name: 'author', label: 'Quote Author', value: '' },
								],
								onsubmit: function ( e ) {
									content = ed.selection.getContent();
									ed.insertContent( '[blockquote align="' + e.data.align + '" author="' + e.data.author + '"]' + content + '[/blockquote]' );
								}
							} );
						}
					},

					/* -----------	Columns Simple	-----------	*/
					{
						text   : 'Columns',
						value  : 'Columns',
						onclick: function () {
							ed.windowManager.open( {
								title   : 'Column',
								body    : [
									{ type    : 'listbox', name    : 'size', label   : 'Select type of column', 'values': [{ text: '1/2', value: '1/2' }, { text: '1/3', value: '1/3' }, { text: '2/3', value: '2/3' }, { text: '1/4', value: '1/4' }, { text: '3/4', value: '3/4' },] },
									{ type: 'checkbox', name: 'last', label: 'Last column?', checked: false, }
								],
								onsubmit: function ( e ) {
									content = ed.selection.getContent();
									ed.insertContent( '[columns size="' + e.data.size + '" last="' + e.data.last + '"]' + content + '[/columns]' );
								}
							} );
						}

					},
					
					/* -----------	Video Shortcode	-----------	*/
					{
						text   : 'Video',
						value  : 'Video',
						onclick: function () {
							ed.windowManager.open( {
								title   : 'Video',
								body    : [
									{ type : 'textbox', name : 'url', label: 'Video URL. E.g: https://www.youtube.com/watch?v=YQHsXMglC9A' },
									{ type    : 'listbox', name    : 'align', label   : 'Video Align?', 'values': [{ text: 'Center', value: 'center' }, { text: 'Left', value: 'left' }, { text: 'Right', value: 'right' },] },
									{ type : 'textbox', name : 'width', label: 'Video Width, Unit is pixel. E.g: 450' },
								],
								onsubmit: function ( e ) {
									content = ed.selection.getContent();
									ed.insertContent( '[goso_video url="' + e.data.url + '" align="' + e.data.align + '" width="' + e.data.width + '" /]' );
								}
							} );
						}

					},
					
					/* -----------	Inline Related Posts -----------	*/
					{
						text   : 'Inline Related Posts',
						value  : 'inline-related-posts',
						onclick: function () {
							ed.windowManager.open( {
								title   : 'Inline Related posts',
								body    : [
									{ type : 'textbox', name : 'title', label: 'Heading Text', value: 'You Might Be Interested In' },
									{ type: 'listbox', name: 'title_align', label: 'Heading Text Align', 'values': [{ text: 'Left', value: 'left' }, { text: 'Center', value: 'center' }, { text: 'Right', value: 'right' }], },
									{ type : 'listbox', name    : 'style', label   : 'Select Style', 'values': [{ text: 'List', value: 'list' }, { text: 'Grid', value: 'grid' }] },
									{ type : 'textbox', name : 'number', label: 'Numbers Post to Show?', value: '6' },
									{ type: 'listbox', name: 'align', label: 'Select Float:', 'values': [{ text: 'None', value: 'none' }, { text: 'Left', value: 'left' }, { text: 'Right', value: 'right' }], },
									{ type : 'textbox', name : 'ids', label: 'Display Related Posts With Post Ids. ( Enter Post IDs and separated by comas. Example: 12, 14 )', value: '' },
									{ type: 'listbox', name: 'by', label: 'Display Related Posts By', 'values': [{ text: 'Same Categories', value: 'categories' }, { text: 'Same Tags', value: 'tags' }, { text: 'Primary Category from "Yoast SEO" or "Rank Math" plugin', value: 'primary_cat' }], },
									{ type: 'listbox', name: 'orderby', label: 'Order By:', 'values': [{ text: 'Random', value: 'rand' }, { text: 'Post date', value: 'date' }, { text: 'Post title', value: 'title' }, { text: 'Post ID', value: 'ID' }, { text: 'Modified Date', value: 'modified' }, { text: 'Comment Count', value: 'comment_count' }, { text: 'Most Viewed Posts All Time', value: 'popular' }, { text: 'Most Viewed Posts Once Weekly', value: 'popular7' }, { text: 'Most Viewed Posts Once a Month', value: 'popular_month' }], },
									{ type: 'listbox', name: 'order', label: 'Order:', 'values': [{ text: 'Descending Order', value: 'DESC' }, { text: 'Ascending  Order', value: 'ASC' }], },
									{ type : 'listbox', name    : 'hide_thumb', label   : 'Hide Thumbnail on Grid Style?', 'values': [{ text: 'No', value: 'no' }, { text: 'Yes', value: 'yes' }] },
									{ type : 'listbox', name    : 'thumb_right', label   : 'Show thumbnail on the right side?', 'values': [{ text: 'No', value: 'no' }, { text: 'Yes', value: 'yes' }] },
									{ type : 'listbox', name    : 'views', label   : 'Show Post Views on Grid Style?', 'values': [{ text: 'No', value: 'no' }, { text: 'Yes', value: 'yes' }] },
									{ type : 'listbox', name    : 'date', label   : 'Show Post Date on Grid Style?', 'values': [{ text: 'Yes', value: 'yes' }, { text: 'No', value: 'no' } ] },
									{ type : 'listbox', name    : 'grid_columns', label   : 'Custom Columns for Grid Style', 'values': [{ text: '2 Columns', value: '2' }, { text: '1 Column', value: '1' }, { text: '3 Columns', value: '3' } ] },
									{ type : 'textbox', name : 'post_type', label: 'Related Posts for a Custom Post Type( not Posts )?', value: '' },
									{ type : 'textbox', name : 'tax', label: 'Related Posts for a Custom Taxonomy?', value: '' },
								],
								onsubmit: function ( e ) {
									content = ed.selection.getContent();
									ed.insertContent( '[inline_related_posts title="' + e.data.title + '" title_align="' + e.data.title_align + '" style="' + e.data.style + '"  number="' + e.data.number + '" align="' + e.data.align + '" ids="' + e.data.ids + '" by="' + e.data.by + '" orderby="' + e.data.orderby + '" order="' + e.data.order + '" hide_thumb="' + e.data.hide_thumb + '" thumb_right="' + e.data.thumb_right + '" views="' + e.data.views + '" date="' + e.data.date + '" grid_columns="' + e.data.grid_columns + '" post_type="' + e.data.post_type + '" tax="' + e.data.tax + '"]' );
								}
							} );
						}

					},
					
					/* --- Portfolio--- */
					{
						text   : 'Portfolio',
						value  : 'Portfolio',
						onclick: function () {
							ed.windowManager.open( {
								title   : 'Portfolio',
								body    : [
									{ type    : 'listbox', name    : 'style', label   : 'Portfolio Style', 'values': [{ text: 'Masonry', value: 'masonry' }, { text: 'Grid', value: 'grid' }] },
									{ type : 'textbox', name : 'cat', label: 'Portfolio Categories Slug To Display. E.g: cat-1, cat-2' },
									{ type : 'textbox', name : 'number', label: 'Numbers Post to Show? If you want display all, fill -1', value: '15' },
									{ type    : 'listbox', name    : 'pagination', label   : 'Page Navigation Type', 'values': [{ text: 'Numberic Navigation', value: 'number' }, { text: 'Load More Button', value: 'load_more' }, { text: 'Infinite Load More', value: 'infinite' }] },
									{ type : 'textbox', name : 'numbermore', label: 'How Much Posts to Show Each Time Load More?', value: '6' },
									{ type    : 'listbox', name    : 'image_type', label   : 'Images Type ( Apply for Grid Style only )', 'values': [{ text: 'Landscape', value: 'landscape' }, { text: 'Square', value: 'square' }, { text: 'Vertical', value: 'vertical' }] },
									{ type    : 'listbox', name    : 'filter', label   : 'Display Filter?', 'values': [{ text: 'Yes', value: 'true' }, { text: 'No', value: 'false' }] },
									{ type    : 'listbox', name    : 'column', label   : 'Number Columns?', 'values': [{ text: '3 Columns', value: '3' }, { text: '2 Columns', value: '2' }] },
									{ type: 'textbox', name: 'all_text', label: 'All text', value: 'All' },
									{ type    : 'listbox', name    : 'lightbox', label   : 'Enable Click on Thumbnails to Open Lightbox?', 'values': [{ text: 'No', value: 'fasle' }, { text: 'Yes', value: 'true' }] },
								],
								onsubmit: function ( e ) {
									ed.insertContent( '[portfolio style="' + e.data.style + '" cat="' + e.data.cat + '" number="' + e.data.number + '" pagination="' + e.data.pagination + '" numbermore="' + e.data.numbermore + '" image_type="' + e.data.image_type + '" filter="' + e.data.filter + '" column="' + e.data.column + '" all_text="' + e.data.all_text + '" lightbox="' + e.data.lightbox + '" /]' );
								}
							} );
						}
					},
					
					/* --- Goso Recipe --- */
					{
						text   : 'Goso Recipe',
						value  : 'Goso Recipe',
						onclick: function () {
							ed.insertContent( '[goso_recipe]' );
						}
					},

					/* --- Goso Recipe Index--- */
					{
						text   : 'Goso Recipe Index',
						value  : 'Goso Recipe Index',
						onclick: function () {
							ed.windowManager.open( {
								title   : 'Goso Recipe Index',
								body    : [
									{ type : 'textbox', name : 'title', label: 'Recipe Index Title', value: 'My Recipe Index' },
									{ type : 'textbox', name : 'cat', label: 'Recipe Index Category Slug' },
									{ type : 'textbox', name : 'numbers_posts', label: 'Numbers Posts to Show?', value: '3' },
									{ type    : 'listbox', name    : 'columns', label   : 'Select Columns', 'values': [{ text: '3 Columns', value: '3' }, { text: '2 Columns', value: '2' }, { text: '4 Columns', value: '4' }] },
									{ type    : 'listbox', name    : 'display_title', label   : 'Display Posts Title?', 'values': [{ text: 'Yes', value: 'yes' }, { text: 'No', value: 'no' }] },
									{ type    : 'listbox', name    : 'display_cat', label   : 'Display Posts Categories?', 'values': [{ text: 'No', value: 'no' }, { text: 'Yes', value: 'yes' }] },
									{ type    : 'listbox', name    : 'display_date', label   : 'Display Posts Date?', 'values': [{ text: 'Yes', value: 'yes' }, { text: 'No', value: 'no' }] },
									{ type    : 'listbox', name    : 'display_image', label   : 'Display Posts Featured Image?', 'values': [{ text: 'Yes', value: 'yes' }, { text: 'No', value: 'no' }] },
									{ type    : 'listbox', name    : 'image_size', label   : 'Images Size for Featured Image', 'values': [{ text: 'Square', value: 'square' }, { text: 'Vertical', value: 'vertical' }, { text: 'Horizontal', value: 'horizontal' }] },
									{ type    : 'listbox', name    : 'cat_link', label   : 'Display View All Posts ( Category Link )?', 'values': [{ text: 'Yes', value: 'yes' }, { text: 'No', value: 'no' }] },
									{ type : 'textbox', name : 'cat_link_text', label: 'Custom "View All" button text', value: 'View All' }
								],
								onsubmit: function ( e ) {
									ed.insertContent( '[goso_index title="' + e.data.title + '" cat="' + e.data.cat + '" numbers_posts="' + e.data.numbers_posts + '" columns="' + e.data.columns + '" display_title="' + e.data.display_title + '" display_cat="' + e.data.display_cat + '" display_date="' + e.data.display_date + '" display_image="' + e.data.display_image + '" image_size="' + e.data.image_size + '" cat_link="' + e.data.cat_link + '" cat_link_text="' + e.data.cat_link_text + '" /]' );
								}
							} );
						}
					},
				]
			} );

		},
		createControl: function ( n, cm ) {
			return null;
		}
	} );

	tinymce.PluginManager.add( 'goso_pre_shortcodes_button', tinymce.plugins.goso_pre_shortcodes_button );
	
	function GosoInsertUnorderedList( ed, styleList ){
		var $checkList = jQuery( ed.dom.getParent( ed.selection.getNode(), 'ul' ) );

		if ( $checkList.hasClass( 'goso_list_shortcode' ) && $checkList.hasClass( styleList ) ) {
			$checkList.removeClass( styleList ).removeClass( 'goso_list_shortcode' );
		} else if ( $checkList.hasClass( 'goso_list_shortcode' ) && ! $checkList.hasClass( styleList ) ) {
			$checkList[0].className = $checkList[0].className.replace( /goso_list\-.*/ig, '' ).trim();
			$checkList.addClass( 'goso_list_shortcode' );
		} else {
			$checkList.addClass( 'goso_list_shortcode' ).addClass( styleList );
		}
	}

})();