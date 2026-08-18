/* 웹과 앱에서 동일한 알림 테이블과 읽음 처리를 사용합니다. */
var show_alarm_exist = false;

function rb_alarm_escape(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
}

function rb_alarm_url(value) {
    value = String(value || '').trim();
    if (!value) return '';
    try {
        var parsed = new URL(value, window.location.origin);
        return (parsed.protocol === 'http:' || parsed.protocol === 'https:') ? parsed.href : '';
    } catch (e) {
        return '';
    }
}

function check_alarm() {
    $.ajax({type:'POST', data:{act:'alarm'}, url:memo_alarm_url + '/get-events.php', dataType:'json', cache:false,
        success:function (result) { if (result.msg === 'SUCCESS') show_alarm(result); }});
}

function show_alarm(data) {
    if (show_alarm_exist) hide_alarm();
    show_alarm_exist = true;
    var id = parseInt(data.notification_id, 10) || 0;
    var url = rb_alarm_url(data.url);
    var title = rb_alarm_escape(data.title);
    var content = rb_alarm_escape(String(data.content || '').replace(/<br\s*\/?>/gi, ' ').replace(/\s+/g, ' ').trim());
    var category = rb_alarm_escape(data.category_label || '기타');
    var body = '<div class="notification-title font-B">' + title + '</div>';
    if (content && content !== title) body += '<div class="notification-description cut2">' + content + '</div>';
    var html = '<div id="alarm_layer" class="wrapper-notification bottom right side" style="display:none"><div class="notification notification-primary notification-msg animated bounceInUp" id="rb_notification_' + id + '">';
    html += '<div class="notification-option"><button type="button" class="notification-check" title="읽음" onclick="set_recv_notification(' + id + ')"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button></div>';
    html += url ? '<a href="' + rb_alarm_escape(url) + '" onclick="mark_recv_notification(' + id + ')">' : '<a href="#" onclick="set_recv_notification(' + id + '); return false;">';
    html += '<div class="notification-heading"><span class="font-B">' + category + '</span>　<span class="al_date">' + rb_alarm_escape(data.created_at) + '</span></div><div class="notification-content cursor">' + body + '</div></a></div></div>';
    $('body').prepend(html); $('#alarm_layer').fadeIn(); setTimeout(function () { hide_alarm(); }, 30000);
}

function hide_alarm() { if (show_alarm_exist) { show_alarm_exist=false; $('#alarm_layer').fadeOut(400, function () { $('#alarm_layer').remove(); }); } }
function mark_recv_notification(id) { if (navigator.sendBeacon) { var data=new FormData(); data.append('act','read_notification'); data.append('notification_id',id); navigator.sendBeacon(memo_alarm_url + '/get-events.php',data); } else { $.post(memo_alarm_url + '/get-events.php',{act:'read_notification',notification_id:id}); } }
function set_recv_notification(id) { $.ajax({type:'POST', data:{act:'read_notification', notification_id:id}, url:memo_alarm_url + '/get-events.php', dataType:'json', cache:false, complete:function () { hide_alarm(); }}); }
