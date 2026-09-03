var show_alarm_exist = false;
var rb_alarm_transitioning = false;
var rb_alarm_queue = [];
var rb_alarm_timer = null;

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
    $.ajax({
        type: 'POST',
        data: {act: 'alarm'},
        url: memo_alarm_url + '/get-events.php',
        dataType: 'json',
        cache: false,
        success: function (result) {
            if (typeof rb_notification_update_badge === 'function') rb_notification_update_badge(result.unread_count);
            rb_alarm_update_memo_badge(result.memo_unread_count);
            if (result.msg === 'SUCCESS' && (typeof rb_alarm_floating_enabled === 'undefined' || rb_alarm_floating_enabled)) show_alarm(result);
        }
    });
}

function rb_alarm_update_memo_badge(count) {
    count = parseInt(count, 10) || 0;
    var $button = $('#rb_memo_top_btn');
    if (!$button.length) return;

    var $badge = $button.children('span').first();
    if (count > 0) {
        if (!$badge.length) {
            $badge = $('<span>', {'class': 'font-H'}).appendTo($button);
        }
        $badge.text(count).show();
    } else if ($badge.length) {
        $badge.hide();
    }
}

function rb_alarm_card_html(data) {
    var eventType = data.event_type === 'memo' ? 'memo' : 'notification';
    var id = eventType === 'memo'
        ? (parseInt(data.memo_id, 10) || 0)
        : (parseInt(data.notification_id, 10) || 0);
    if (!id) return '';

    var title = rb_alarm_escape(data.title);
    var content = rb_alarm_escape(String(data.content || '').replace(/<br\s*\/?>/gi, ' ').replace(/\s+/g, ' ').trim());
    var headingTitle = eventType === 'memo' ? '새 쪽지' : '새 알림';
    var headingIcon = eventType === 'memo'
        ? '<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24"><path d="M20 4a2 2 0 0 1 1.995 1.85L22 6v12a2 2 0 0 1-1.85 1.995L20 20H4a2 2 0 0 1-1.995-1.85L2 18V6a2 2 0 0 1 1.85-1.995L4 4zm0 3.414-6.94 6.94a1.5 1.5 0 0 1-2.12 0L4 7.414V18h16zM18.586 6H5.414L12 12.586z"/></svg>'
        : '<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24"><path d="M5 9a7 7 0 0 1 14 0v3.764l1.822 3.644A1.1 1.1 0 0 1 19.838 18h-3.964a4.002 4.002 0 0 1-7.748 0H4.162a1.1 1.1 0 0 1-.984-1.592L5 12.764zm5.268 9a2 2 0 0 0 3.464 0zM12 4a5 5 0 0 0-5 5v3.764a2 2 0 0 1-.211.894L5.619 16h12.763l-1.17-2.342a2.001 2.001 0 0 1-.212-.894V9a5 5 0 0 0-5-5"/></svg>';
    var createdAt = rb_alarm_escape(data.created_at);
    var body = '';
    if (eventType === 'memo') {
        if (content) body = '<div class="notification-description cut2">' + content + '</div>';
    } else {
        body = '<div class="notification-description cut2">' + (content || title) + '</div>';
    }

    var html = '<div class="notification notification-primary notification-msg animated bounceInUp" id="rb_alarm_' + eventType + '_' + id + '">';
    html += eventType === 'memo'
        ? '<div class="notification-option"><button type="button" class="notification-check" title="닫기" onclick="hide_alarm_item(\'memo\',' + id + ')">'
        : '<div class="notification-option"><button type="button" class="notification-check" title="읽음" onclick="set_recv_notification(' + id + ')">';
    html += '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button></div>';
    html += eventType === 'memo'
        ? '<a href="#" onclick="return open_recv_memo(' + id + ')">'
        : '<a href="#" onclick="return open_recv_notification(' + id + ')">';
    html += '<div class="notification-heading">' + headingIcon + '<span class="font-B">' + headingTitle + '</span><span class="al_date">' + createdAt + '</span></div>';
    html += '<div class="notification-content cursor">' + body + '</div></a></div>';
    return html;
}

function show_alarm(data) {
    if (typeof rb_alarm_floating_enabled !== 'undefined' && !rb_alarm_floating_enabled) return;
    var events = $.isArray(data.events) && data.events.length ? data.events : [data];
    rb_alarm_queue = [];
    $.each(events, function(index, eventData) {
        if (rb_alarm_card_html(eventData)) rb_alarm_queue.push(eventData);
    });
    if (!rb_alarm_queue.length) return;

    if (show_alarm_exist) {
        rb_alarm_finish_current(true);
    } else {
        rb_alarm_show_next();
    }
}

function rb_alarm_show_next() {
    if (show_alarm_exist || rb_alarm_transitioning || !rb_alarm_queue.length) return;

    var card = rb_alarm_card_html(rb_alarm_queue.shift());
    if (!card) {
        rb_alarm_show_next();
        return;
    }

    show_alarm_exist = true;
    var layerStyle = typeof rb_alarm_floating_style === 'string'
        ? rb_alarm_floating_style
        : 'left:50px;right:auto;top:auto;bottom:40px;transform:none;';
    var html = '<div id="alarm_layer" class="wrapper-notification bottom right side" style="' + layerStyle + 'display:none">' + card + '</div>';

    $('body').prepend(html);
    $('#alarm_layer').fadeIn();
    rb_alarm_timer = setTimeout(function () { rb_alarm_finish_current(true); }, 30000);
}

function hide_alarm() {
    rb_alarm_finish_current(true);
}

function rb_alarm_finish_current(showNext) {
    if (rb_alarm_timer) {
        clearTimeout(rb_alarm_timer);
        rb_alarm_timer = null;
    }

    if (rb_alarm_transitioning) return;
    show_alarm_exist = false;
    var $layer = $('#alarm_layer');
    if (!$layer.length) {
        if (showNext) rb_alarm_show_next();
        return;
    }

    rb_alarm_transitioning = true;
    $layer.fadeOut(200, function () {
        $layer.remove();
        rb_alarm_transitioning = false;
        if (showNext) rb_alarm_show_next();
    });
}

function hide_alarm_item(eventType, eventId) {
    eventType = eventType === 'memo' ? 'memo' : 'notification';
    eventId = parseInt(eventId, 10) || 0;
    var $item = $('#rb_alarm_' + eventType + '_' + eventId);
    if (!$item.length) return;

    rb_alarm_finish_current(true);
}

function open_recv_notification(notification_id) {
    if (typeof rb_notification_open_view === 'function') {
        rb_notification_open_view(notification_id);
        hide_alarm_item('notification', notification_id);
        return false;
    }
    set_recv_notification(notification_id);
    return false;
}

function open_recv_memo(memo_id) {
    memo_id = parseInt(memo_id, 10) || 0;
    if (!memo_id) return false;

    var memoUrl = memo_alarm_bbs_url + '/memo_view.php?me_id=' + memo_id + '&kind=recv';
    if (typeof win_memo === 'function') {
        win_memo(memoUrl);
    } else {
        window.open(memoUrl, 'win_memo');
    }

    var currentCount = parseInt($('#rb_memo_top_btn').children('span').first().text(), 10) || 0;
    rb_alarm_update_memo_badge(Math.max(0, currentCount - 1));
    hide_alarm_item('memo', memo_id);
    return false;
}

function set_recv_notification(notification_id) {
    $.ajax({
        type: 'POST',
        data: {act: 'read_notification', notification_id: notification_id},
        url: memo_alarm_url + '/get-events.php',
        dataType: 'json',
        cache: false,
        success: function (result) {
            if (typeof rb_notification_update_badge === 'function') rb_notification_update_badge(result.unread_count);
        },
        complete: function () { hide_alarm_item('notification', notification_id); }
    });
}
