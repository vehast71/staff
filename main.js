$(function(){
    console.log('main.js is loaded');

    $('td a').on('click',function(e){
        e.preventDefault();
        var href = $(this).attr('href');
        $.ajax({
            'type': 'POST',
            'url': 'http://staff/get_data.php',
            'data': {href: href.slice(1)}
        }).done(function(d){
            $('.wr').html(d);
        });
    });

});
