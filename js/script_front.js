jQuery(document).ready(function($){   

$('#import-theatre-post').click( function(){  
    
    $.ajax({
            type :  "post",
            url : wpTheatreSettings.ajaxurl,
            timeout : 200000,
            dataType: 'json',
            data : {
                'action' : 'import-theatre-posts'           
    
            },
            success :  function(data){
            if(data.num == 0)
                alert('No new data found');
            else 
            alert(data.num + ' Offers have been imported successfully!');
             }
    
    
})



})
})

