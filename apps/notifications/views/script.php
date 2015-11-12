<script>
function openActivityStream() {
    jQuery("#activityStreamWrapper").show();
}
</script>
<a href="javascript:void(0)" onclick="openActivityStream()">Show Activity Stream</a>
<div id="activityStreamWrapper">
    <div id="activityStream"></div>
</div>
<script>
if (localStorage.getItem('fyre-auth')) {
    var user = jQuery.parseJSON(localStorage.getItem('fyre-auth')).value.profile;
    buildRealStream(user);
}
</script>