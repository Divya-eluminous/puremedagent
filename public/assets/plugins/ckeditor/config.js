CKEDITOR.editorConfig = function(config) {
    // Enable extra plugins
    // config.extraPlugins = 'panel,listblock,format,richcombo,floatpanel,font,panelbutton,button,stylescombo,image';
    // config.extraPlugins = 'font,colorbutton,image,stylescombo';
    config.extraPlugins = 'panel,listblock,format,richcombo,floatpanel';

    // Configure color options
    // config.colorButton_colors = '000000,666666,cccccc,eeeeee,ffffff,' +
    //                             'ff0000,ff9900,33cc33,0099ff,cc00cc,ffcc00,' +
    //                             'ccffcc,cceeff,ffcccc,000000,333333,666666,' +
    //                             '999999,cccccc,ffffff';
    // config.colorButton_enableAutomatic = true;
    // config.colorButton_enableMore = true;
    
    // Add font size options
    // config.fontSize_sizes = '8/8px;10/10px;12/12px;14/14px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;30/30px;32/32px;36/36px;48/48px;72/72px';

    // Define the toolbar groups arrangement
    config.toolbarGroups = [
        { name: 'document',   groups: [ 'mode', 'document', 'doctools' ] },
        { name: 'clipboard',  groups: [ 'clipboard', 'undo' ] },
        { name: 'editing',    groups: [ 'find', 'selection', 'spellchecker' ] },
        { name: 'forms' },
        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
        { name: 'paragraph',  groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
        { name: 'links' },
        { name: 'insert' },
        { name: 'styles' },   // Ensure 'styles' is defined correctly
        { name: 'colors' },   // Add 'colors' for color options
        { name: 'tools' },
        { name: 'others' },
        { name: 'about' }
    ];

    // Configure font and font size dropdowns
    // config.fontSize_defaultLabel = 'Font Size';
    // config.fontSize_style = {
    //     element: 'span',
    //     styles: { 'font-size': '#(size)' },
    //     overrides: [ { element: 'font', attributes: { 'size': null } } ]
    // };

    //   // Ensure color button options are set
    // config.colorButton_foreStyle = {
    //     element: 'font',
    //     attributes: { 'color': '#(color)' }
    // };
    // config.colorButton_backStyle = {
    //     element: 'font',
    //     styles: { 'background-color': '#(color)' }
    // };
    
    // Simplify dialog windows if necessary
    config.removeButtons = 'Cut,Copy,Paste,Undo,Redo,Anchor,Underline,Strike,Subscript,Superscript';
    config.removeDialogTabs = 'link:advanced';

    
};
