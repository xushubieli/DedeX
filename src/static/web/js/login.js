$(function($) {
    $("#iptUserid").focusout(function() {
        let userid = $(this).val();
        if (userid !== '') {
            $.get("api.php?action=is_need_check_code&userid=" + userid, function(rs) {
                if (rs.code === 0) {
                    if (rs.data.isNeed) {
                        $("#vdimgck").show();
                    } else {
                        $("#vdimgck").hide();
                    }
                }
            });
        }
    });
});