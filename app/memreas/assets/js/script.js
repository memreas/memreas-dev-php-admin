$(function(){

    var ul = $('#upload ul');

    $('#drop a').click(function(){
        // Simulate a click on the file input button
        // to show the file browser dialog
        $(this).parent().find('input').click();
    });    
    // Initialize the jQuery File Upload plugin
    $('#upload').fileupload({

        // This element will accept file drag/drop uploading
        dropZone: $('#drop, #upload-queue'),

        // This function is called when a file is added to the queue;
        // either via the browse button, or via drag/drop:
        add: function (e, data) {            
            
            //For preview 
            var tpl = $('<li class="working"><input type="text" value="0" data-width="48" data-height="48"'+
                ' data-fgColor="#0788a5" data-readOnly="1" data-bgColor="#3e4043" /><p></p><span></span></li>');
            
            // Append the file name and file size
            tpl.find('p').text(data.files[0].name)
                         .append('<i>' + formatFileSize(data.files[0].size) + '</i>');

            // Add the HTML to the UL element
            data.context = tpl.appendTo(ul);                    
            
            //For upload
            var tpl2 = $('<li class="working-upload">' +
                            '<div class="upload_progress" id="table">' +
                                '<div class="upload_progress_img">' +
                                    '<img src="/memreas/img/pic-1.jpg">' +
                                '</div>' +
                                '<div class="upload_progress_bar">' +
                                    '<span></span><div class="progress"></div>' +
                                '</div>' +
                                '<div class="close_progress"><a href="#" class="cancel-upload"><img src="/memreas/img/close.png" alt=""></a></div>' +
                                '<div class="clear"></div>' +
                            '</div>' +
                          '</li>');
            data.context2 = tpl2.appendTo("li.first-upload");
            
            // Initialize the knob plugin
            tpl.find('input').knob();

            // Listen for clicks on the cancel icon
            tpl.find('span').click(function(){

                if(tpl.hasClass('working')){
                    jqXHR.abort();
                }

                tpl.fadeOut(function(){
                    tpl.remove();
                });

            });
            
            tpl2.find("a.cancel-upload").click (function(){
                 if(tpl.hasClass('working')){
                    jqXHR.abort();
                }

                tpl.fadeOut(function(){
                    tpl.remove();
                });
            });
            
            // Automatically upload the file once it is added to the queue
           // var jqXHR = data.submit();
           data.submit();
           $(".start-upload").on ('click', function(){
               $("a[title=queue]").trigger ("click");
                data.submit();
            });
        },        
        progress: function(e, data){

            // Calculate the completion percentage of the upload
            var progress = parseInt(data.loaded / data.total * 100, 10);            

            // Update the hidden input field and trigger a change
            // so that the jQuery knob plugin knows to update the dial
            data.context.find('input').val(progress).change();
            data.context.find(".progress-bar").css ("width", progress + "%");
            data.context.find(".count-progress").html (progress + "%");             
            data.context2.find(".upload_progress_bar .progress").css ("width", progress + "%");
            data.context2.find(".upload_progress_bar span").html (progress + "%");
            

            if(progress == 100){
                data.context.remove('.working');
                data.context.remove('count-progress');                
            }
        },

        fail:function(e, data){
            // Something has gone wrong!
            data.context.addClass('error');
        }

    });


    // Prevent the default action when a file is dropped on the window
    $(document).on('drop dragover', function (e) {
        e.preventDefault();
    });

    // Helper function that formats the file sizes
    function formatFileSize(bytes) {
        if (typeof bytes !== 'number') {
            return '';
        }

        if (bytes >= 1000000000) {
            return (bytes / 1000000000).toFixed(2) + ' GB';
        }

        if (bytes >= 1000000) {
            return (bytes / 1000000).toFixed(2) + ' MB';
        }

        return (bytes / 1000).toFixed(2) + ' KB';
    }

});