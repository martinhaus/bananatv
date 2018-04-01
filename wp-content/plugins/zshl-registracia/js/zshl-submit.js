/**
 * Created by martin on 19.3.2017.
 */


jQuery(document).ready(function () {
    jQuery('.team_reg').click(function () {
        var id = jQuery(this).parent().parent().find('.id').html();

        jQuery('#team_reg_form').find('#team_reg_input').attr('value',id);
        jQuery('#team_reg_form').submit(ajaxSubmit());
   });

    var data = {
        action: 'is_user_logged_in'
    };
    jQuery.post(ajax_object.ajax_url, data, function(response) {
        if(response == 'yes') {
            setInterval(ajaxGetTeams, 1000);
            jQuery('.update-button').click(function () {
                ajaxGetTeams();
            })
        } else {
            // user is not logged in, show login form here
        }
    });

});


function ajaxSubmit() {
    var team_reg_form = jQuery('#team_reg_form').serialize();
    jQuery.ajax({
        type:"POST",
        url: ajax_object.ajax_url,
        data: team_reg_form,
        success:function(data){
            console.log(data);
        },
        error: function(errorThrown){
            console.log(errorThrown);
        }
    });
}

function ajaxGetTeams() {
    var reg_id = jQuery('#reg_id').val();
    jQuery.ajax({
        type:"GET",
        url: ajax_object.ajax_url,
        data: {action: "get_teams", reg_id: reg_id},
        success:function(data){
            console.log(data);
            updateTable(data);
        },
        error: function(errorThrown){
            console.log(errorThrown);
        }
    });
}

function updateTable(teams_json) {
    var rows = jQuery('#registration-table tbody tr');
    var teams = jQuery.parseJSON(teams_json);

    jQuery(rows).find('td.team-name').each(function () {
      jQuery(this).html('');
    });
    jQuery(rows).find('td.team-date').each(function () {
     jQuery(this).html('');
    });
    jQuery(rows).find('.team_reg').each(function () {
     jQuery(this).removeAttr('disabled');
    });

    for (var i = 0;i < teams.length;i++) {
        var place = teams[i].place;
        jQuery(rows).eq(place - 1).find('td.team-name').html(teams[i].name);
        jQuery(rows).eq(place - 1).find('td.team-date').html(teams[i].date_assigned);
        jQuery(rows).eq(place - 1).find('.team_reg').attr('disabled','true');
    }

}
