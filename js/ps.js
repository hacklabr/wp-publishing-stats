jQuery(document).ready(function(){
    jQuery.datepicker.regional[psDatepicker.lang];
    jQuery('#time_ini, #time_end').datepicker({ 'dateFormat': 'yy-mm-dd' });
    jQuery('.show-user-posts').click(function(){
        postsDiv = jQuery(this).next(); 
        
        if (postsDiv.is(':empty')) {
            jQuery("body").css("cursor", "progress");
            jQuery.get(
                ajaxurl,
                { action: 'ps_get_user_posts', ids: postsDiv.data('posts') }
            ).done(function(data) {
                postsDiv.html(data);
                jQuery("body").css("cursor", "auto");
            });
        }
        
        postsDiv.slideToggle();
    });
});

var i = 0;

var p = jQuery.plot(jQuery('#placeholder'), [{
    'data': jQuery.parseJSON(ps.plotdata),
    'lines': { 'show': true },
    'points': { 'show': true },
}],{
    'grid': {
        'borderWidth': 0
    },
    'xaxis': {
        'mode': 'time',
        'timeformat': '%d/%m',
        'tickSize': [2, 'day']
    }
});
jQuery.each(p.getData()[0].data, function(i, el){
    var o = p.pointOffset({x: el[0], y: el[1]});
    jQuery('<div class="data-point-label">' + el[1] + '</div>').css( {
        position: 'absolute',
        left: o.left - 4,
        top: o.top - 25,
        display: 'none'
    }).appendTo(p.getPlaceholder()).fadeIn('slow');
});