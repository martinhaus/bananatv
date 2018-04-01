jQuery(document).ready(function () {
    jQuery('.add-ad').click(function () {
        var company_id = jQuery(this).parent().find('.company_id').val();
        var form = '<form method="post" action="'+ window.location.href +'">\n' +
            '        <input type="hidden" name="company_id" value="'+ company_id +'">\n' +
            '        <input type="text" name="ad_url" placeholder="URL zobrazovaného obsahu">\n' +
            '        <input type="submit" class="button-primary" name="new_ad_submit" value="Pridať">\n' +
            '    </form>';
        jQuery(this).parent().find('ul').append(form);

    })
});


/*



 */