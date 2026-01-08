/**
 * TinyMCE Initialization
 */

// Initialize TinyMCE
tinymce.init({
    selector: '.tinymce',
    browser_spellcheck: true,
    resize: true,
    min_height: 300,
    max_height: 600,
    promotion: false,
    branding: false,
    menubar: false,
    statusbar: false,
    license_key: 'gpl',
    toolbar: [
        { name: 'styles', items: [ 'styles' ] },
        { name: 'formatting', items: [ 'bold', 'italic', 'forecolor' ] },
        { name: 'lists', items: [ 'bullist', 'numlist' ] },
        { name: 'alignment', items: [ 'alignleft', 'aligncenter', 'alignright', 'alignjustify' ] },
        { name: 'indentation', items: [ 'outdent', 'indent' ] },
        { name: 'table', items: [ 'table' ] },
        { name: 'extra', items: [ 'fullscreen' ] }
    ],
    mobile: {
        menubar: false,
        plugins: 'autosave lists autolink',
        toolbar: 'undo bold italic styles',
    },
    plugins: 'link image lists table code codesample fullscreen autoresize',
});
