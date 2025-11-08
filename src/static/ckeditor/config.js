/**
 * @license Copyright (c) 2003-2022, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */
CKEDITOR.editorConfig = function(config) {
	config.height = 350;
	config.language = 'zh-cn';
	config.autoParagraph = true;
	config.enterMode = CKEDITOR.ENTER_P;
	config.shiftEnterMode = CKEDITOR.ENTER_P;
	config.toolbarGroups = [
		{name: 'mode', groups: ['mode', 'document', 'doctools']},
		{name: 'cleanup', groups: ['undo', 'cleanup']},
		{name: 'styles', groups: ['styles']},
		{name: 'colors', groups: ['colors']},
		{name: 'paragraph', groups: ['align', 'paragraph', 'textindent', 'indent']},
		{name: 'basicstyles', groups: ['basicstyles', 'list','blocks']},
		{name: 'editing', groups: ['find', 'editing']},
		{name: 'links', groups: ['links']},
		{name: 'insert', groups: ['insert']},
		{name: 'tools', groups: ['tools']}
	];
	config.removePlugins = 'div,exportpdf,bootstrapTable,pagebreak,scayt';
	config.removeButtons = 'About,Button,Checkbox,Flash,Font,bootstrapTable,Form,HiddenField,Iframe,ImageButton,NewPage,Preview,Print,Radio,Save,Select,ShowBlocks,Smiley,Styles,Templates,TextField,Textarea';
};