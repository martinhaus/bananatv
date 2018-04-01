/**
 * Created by martin on 19.3.2017.
 */

jQuery(document).ready(function () {
   jQuery('#start_date').datetimepicker({
       dateFormat: 'yy-mm-dd',
       timeFormat: 'HH:mm:ss'});
   jQuery('#end_date').datetimepicker({
       dateFormat: 'yy-mm-dd',
       timeFormat: 'HH:mm:ss'});
});