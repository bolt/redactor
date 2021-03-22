(function($R)
{
    $R.add('plugin', 'audio', {
        
        // How the modal should look
        modals: {
            'audiomodal': '<form action="">'
                + '<div class="form-item">'
                    + '<label>Last opp en fil</label>'
                    + '<input type="file" name="file" accept="audio/*">'
                + '</div>'
            + '</form>'
        },
        
        // Initialize the different things we need
        init: function(app){
            this.app = app;
            this.toolbar = app.toolbar;
            this.insertion = app.insertion;
            this.opts = app.opts;
        },
        
        // The various callback functions related to the modal
        onmodal: {
            audiomodal: {
                
                // When we open the modal
                open: function($modal, $form){
                    this._setUpload($form);
                    
                },
                
                // When the modal has been opened
                opened: function($modal, $form){
                    $form.getField('text').focus();
                },
                
                // When we click insert
                insert: function($modal, $form){
                    var data = $form.getData();
                    this._insert(data);
               }
            }
        },
        
        // Callback functions for the upload
        onupload: {
            myupload: {
                // When the upload is done
                complete: function(response){
                    this._insert(response);
                },
                // When the upload failed
                error: function(response){
                    this._uploadError(response);
                }
            }
        },
        
        // Let's make the button to put in the toolbar and put it there
        start: function(){
            var buttonData = {
                title: 'Audio',
                api: 'plugin.audio.open'
            };

            var $button = this.toolbar.addButton('audio', buttonData);
        },
        
        // We open the modal
        open: function(){
        
            // With these settings
            var options = {
                title: 'Audio', // the modal title
                name: 'audiomodal', // the modal variable in modals object
                handle: 'insert', // The command to run when pressing "Enter"
                commands: {
                    insert: { title: 'Insert' },
                    cancel: { title: 'Cancel' } // the cancel button in the modal
                }
            };

            // open the modal with API
            this.app.api('module.modal.build', options);
        },
        
        // Settings for the upload
        _setUpload: function($form){
            var options = {
                // URL to the upload script. What should this be?
                url: "/bolt/async/redactor_upload?location=files",
                element: $form.getField('file'),
                name: 'myupload'
            };

            this.app.api('module.upload.build', options);
        },
        
        _uploadError: function(response){
            this.app.broadcast('myuploadError', response);
        },
        
        // What we do when when we click "Insert"
        _insert: function(data){
            
            // close the modal
            this.app.api('module.modal.close');

            this.app.broadcast('myuploadComplete', data);
            
            for (file in data) {
                this.insertion.insertHtml('<audio controls="" src="/'+data[file]['url']+'"></audio>');
            }
        }
    });
})(Redactor);
