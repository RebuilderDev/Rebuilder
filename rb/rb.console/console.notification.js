(function ($) {
    'use strict';

    $(function () {
        var $box = $('#notification_box_wrap');
        var $button = $('#notification_top_btn');
        if (!$box.length || !$button.length) return;

        var isOpen = false;
        var currentCategory = 'all';
        var endpoint = $box.attr('data-endpoint');
        var actionToken = $box.attr('data-action-token');
        var iconPaths = {
            board: 'm10.756 6.17 7.07 7.071-7.173 7.174a2 2 0 0 1-1.238.578L9.239 21H4.006a1.01 1.01 0 0 1-1.004-.9l-.006-.11v-5.233a2 2 0 0 1 .467-1.284l.12-.13 7.173-7.174Zm3.14-3.14a2 2 0 0 1 2.701-.117l.127.117 4.243 4.243a2 2 0 0 1 .117 2.7l-.117.128-1.726 1.726-7.07-7.071z',
            shop: 'M10.464 3.282a2 2 0 0 1 2.964-.12l.108.12L17.468 8h2.985a1.49 1.49 0 0 1 1.484 1.655l-.092.766-.1.74-.082.554-.095.595-.108.625-.122.648-.136.661c-.072.333-.149.667-.232.999a21.018 21.018 0 0 1-.832 2.583l-.221.54-.214.488-.202.434-.094.194-.249.49c-.32.61-.924.97-1.563 1.022l-.16.006H6.555a1.929 1.929 0 0 1-1.71-1.008l-.232-.45-.18-.37a20.09 20.09 0 0 1-.095-.205l-.2-.449a21.536 21.536 0 0 1-1.108-3.276 32.366 32.366 0 0 1-.156-.654l-.142-.648-.127-.634-.112-.613-.1-.587-.087-.554-.074-.513-.09-.683-.066-.556a39.802 39.802 0 0 1-.017-.153 1.488 1.488 0 0 1 1.348-1.64L3.543 8h2.989zm-.503 9.44a1 1 0 0 0-1.96.326l.013.116.5 3 .025.114a1 1 0 0 0 1.96-.326l-.013-.116-.5-3zm5.203-.708a1 1 0 0 0-1.125.708l-.025.114-.5 3a1 1 0 0 0 1.947.442l.025-.114.5-3a1 1 0 0 0-.822-1.15M12 4.562 9.135 8h5.73z',
            subscribe: 'M16 14a5 5 0 0 1 4.995 4.783L21 19v1a2 2 0 0 1-1.85 1.995L19 22H5a2 2 0 0 1-1.995-1.85L3 20v-1a5 5 0 0 1 4.783-4.995L8 14zM12 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10',
            notice: 'M5 9a7 7 0 0 1 14 0v3.764l1.822 3.644A1.1 1.1 0 0 1 19.838 18h-3.964a4.002 4.002 0 0 1-7.748 0H4.162a1.1 1.1 0 0 1-.984-1.592L5 12.764zm5.268 9a2 2 0 0 0 3.464 0zM12 4a5 5 0 0 0-5 5v3.764a2 2 0 0 1-.211.894L5.619 16h12.763l-1.17-2.342a2.001 2.001 0 0 1-.212-.894V9a5 5 0 0 0-5-5',
            other: 'M7 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8m0 10a4 4 0 1 0 0 8 4 4 0 0 0 0-8m6-6a4 4 0 1 1 8 0 4 4 0 0 1-8 0m4 6a4 4 0 1 0 0 8 4 4 0 0 0 0-8'
        };

        function closeBox() {
            $box.hide();
            $button.removeClass('notification_open').attr('aria-expanded', 'false');
            isOpen = false;
        }

        window.rb_notification_update_badge = function (count) {
            count = parseInt(count, 10) || 0;
            var $badge = $('#notification_unread_badge');
            $badge.text(count);
            count > 0 ? $badge.show() : $badge.hide();
        };

        function showMessage(message) {
            $box.find('.rb_notification_list').empty().append(
                $('<div>', {'class': 'rb_notification_empty'}).text(message)
            );
        }

        function showLoading() {
            $box.find('.rb_notification_list').empty().append(
                $('<div>', {
                    'class': 'rb_notification_loading',
                    'role': 'status',
                    'aria-label': '알림을 불러오는 중'
                }).append($('<span>', {'aria-hidden': 'true'}))
            );
        }

        function createCategoryIcon(category) {
            var iconCategory = iconPaths[category] ? category : 'other';
            var $icon = $('<span>', {
                'class': 'rb_notification_category_icon rb_notification_category_' + iconCategory,
                'aria-hidden': 'true'
            });
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            svg.setAttribute('width', '24');
            svg.setAttribute('height', '24');
            svg.setAttribute('viewBox', '0 0 24 24');
            path.setAttribute('d', iconPaths[iconCategory]);
            svg.appendChild(path);
            return $icon.append(svg);
        }

        function renderList(items) {
            var $list = $box.find('.rb_notification_list').empty().show();
            $box.find('.rb_notification_view').hide();

            if (!items || !items.length) {
                showMessage('도착한 알림이 없습니다.');
                return;
            }

            $.each(items, function (index, item) {
                var content = String(item.content || item.title || '')
                    .replace(/<br\s*\/?>/gi, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
                var $row = $('<div>', {
                    'class': 'rb_notification_item' + (parseInt(item.is_read, 10) ? ' is_read' : ''),
                    'data-notification-id': item.id
                });
                var $open = $('<button>', {
                    type: 'button',
                    'class': 'rb_notification_item_open',
                    'data-notification-id': item.id
                });
                var $meta = $('<span>', {'class': 'rb_notification_item_meta'})
                    .append($('<b>').text(item.category_label || '기타'))
                    .append($('<em>').text(item.created_at || ''));
                var $text = $('<span>', {'class': 'rb_notification_item_text'})
                    .append($meta)
                    .append($('<span>', {'class': 'rb_notification_item_content'}).text(content));

                $open.append(createCategoryIcon(item.category)).append($text);
                $row.append($open).append(
                    $('<button>', {
                        type: 'button',
                        'class': 'rb_notification_delete',
                        'data-notification-id': item.id,
                        'aria-label': '알림 삭제',
                        title: '삭제'
                    }).text('×')
                );
                $list.append($row);
            });
        }

        function loadList(category) {
            currentCategory = category || 'all';
            showLoading();
            $box.find('.rb_notification_view').hide();
            $box.find('.rb_notification_list').show();

            $.ajax({
                type: 'POST',
                url: endpoint,
                dataType: 'json',
                cache: false,
                data: {act: 'notification_list', category: currentCategory},
                success: function (result) {
                    if (result.msg !== 'SUCCESS') {
                        showMessage('알림을 불러오지 못했습니다.');
                        return;
                    }
                    rb_notification_update_badge(result.unread_count);
                    renderList(result.items);
                },
                error: function () {
                    showMessage('알림을 불러오지 못했습니다.');
                }
            });
        }

        function safeUrl(value) {
            value = String(value || '').trim();
            if (!value) return '';
            try {
                var parsed = new URL(value, window.location.origin);
                return parsed.protocol === 'http:' || parsed.protocol === 'https:' ? parsed.href : '';
            } catch (error) {
                return '';
            }
        }

        function renderView(item) {
            var rawContent = String(item.content || item.title || '');
            var links = [];

            function addLink(value) {
                value = String(value || '').trim();
                if (!value) return;
                if (/^www\./i.test(value)) value = 'https://' + value;
                var normalized = safeUrl(value);
                if (normalized && $.inArray(normalized, links) === -1) links.push(normalized);
            }

            var parser = new DOMParser();
            var parsed = parser.parseFromString(rawContent.replace(/<br\s*\/?>/gi, '\n'), 'text/html');
            var unsafeNodes = parsed.querySelectorAll('script, style');
            var index;
            for (index = unsafeNodes.length - 1; index >= 0; index--) {
                unsafeNodes[index].parentNode.removeChild(unsafeNodes[index]);
            }
            var anchors = parsed.querySelectorAll('a[href]');
            for (index = anchors.length - 1; index >= 0; index--) {
                addLink(anchors[index].getAttribute('href'));
                anchors[index].parentNode.removeChild(anchors[index]);
            }

            var text = parsed.body ? parsed.body.textContent || '' : rawContent;
            text = text.replace(/(?:https?:\/\/|www\.)[^\s<>"']+/gi, function (match) {
                var linkValue = match.replace(/[),.!?]+$/, '');
                addLink(linkValue);
                return match.substring(linkValue.length);
            });
            text = text.replace(/\r/g, '')
                .replace(/[ \t]+\n/g, '\n')
                .replace(/\n[ \t]+/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .trim();
            addLink(item.url);

            $box.find('.rb_notification_view_date').text(item.created_at || '');
            $box.find('.rb_notification_view_content').text(text || item.title || '');
            var $links = $box.find('.rb_notification_view_links').empty();
            $.each(links, function (linkIndex, url) {
                $links.append($('<a>', {href: url}).text('링크열기'));
            });
            $box.find('.rb_notification_list').hide();
            $box.find('.rb_notification_view').show();
            $box.find('.rb_notification_body').scrollTop(0);
        }

        function openView(notificationId) {
            notificationId = parseInt(notificationId, 10) || 0;
            if (!notificationId) return;

            $.ajax({
                type: 'POST',
                url: endpoint,
                dataType: 'json',
                cache: false,
                data: {
                    act: 'notification_view',
                    notification_id: notificationId,
                    action_token: actionToken
                },
                success: function (result) {
                    if (result.msg === 'INVALID_TOKEN') {
                        alert('페이지를 새로고침한 후 다시 이용해 주세요.');
                        return;
                    }
                    if (result.msg !== 'SUCCESS' || !result.item) {
                        $box.find('.rb_notification_view').hide();
                        $box.find('.rb_notification_list').show();
                        showMessage('알림을 찾을 수 없습니다.');
                        return;
                    }
                    $box.find('.rb_notification_item[data-notification-id="' + notificationId + '"]').addClass('is_read');
                    rb_notification_update_badge(result.unread_count);
                    renderView(result.item);
                },
                error: function () {
                    $box.find('.rb_notification_view').hide();
                    $box.find('.rb_notification_list').show();
                    showMessage('알림을 불러오지 못했습니다.');
                }
            });
        }

        window.rb_notification_open_view = function (notificationId) {
            notificationId = parseInt(notificationId, 10) || 0;
            if (!notificationId) return false;
            isOpen = true;
            $box.show();
            $button.addClass('notification_open').attr('aria-expanded', 'true');
            showLoading();
            openView(notificationId);
            return false;
        };

        $button.on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            isOpen = !isOpen;
            if (isOpen) {
                $box.show();
                $button.addClass('notification_open').attr('aria-expanded', 'true');
                loadList(currentCategory);
            } else {
                closeBox();
            }
        });

        $(document).on('click.rbConsoleNotification', function () {
            if (isOpen) closeBox();
        }).on('keydown.rbConsoleNotification', function (event) {
            if (event.key === 'Escape' && isOpen) closeBox();
        });

        $box.on('click', function (event) {
            event.stopPropagation();
        });

        $box.find('.rb_notification_tabs').on('click', 'button', function () {
            $box.find('.rb_notification_tabs button').removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');
            loadList($(this).attr('data-category'));
        });

        $box.find('.rb_notification_list').on('click', '.rb_notification_item_open', function () {
            openView($(this).attr('data-notification-id'));
        }).on('click', '.rb_notification_delete', function (event) {
            event.stopPropagation();
            var notificationId = parseInt($(this).attr('data-notification-id'), 10) || 0;
            if (!notificationId) return;
            $.ajax({
                type: 'POST',
                url: endpoint,
                dataType: 'json',
                cache: false,
                data: {
                    act: 'notification_delete',
                    notification_id: notificationId,
                    action_token: actionToken
                },
                success: function (result) {
                    if (result.msg === 'INVALID_TOKEN') {
                        alert('페이지를 새로고침한 후 다시 이용해 주세요.');
                        return;
                    }
                    if (result.msg === 'SUCCESS') {
                        rb_notification_update_badge(result.unread_count);
                        loadList(currentCategory);
                    }
                }
            });
        });

        function deleteAll() {
            $.ajax({
                type: 'POST',
                url: endpoint,
                dataType: 'json',
                cache: false,
                data: {act: 'notification_delete_all', action_token: actionToken},
                success: function (result) {
                    if (result.msg === 'INVALID_TOKEN') {
                        alert('페이지를 새로고침한 후 다시 이용해 주세요.');
                        return;
                    }
                    if (result.msg === 'SUCCESS') {
                        rb_notification_update_badge(result.unread_count);
                        loadList(currentCategory);
                        return;
                    }
                    alert('알림을 삭제하지 못했습니다.');
                },
                error: function () {
                    alert('알림을 삭제하지 못했습니다.');
                }
            });
        }

        $box.find('.rb_notification_delete_all').on('click', function () {
            var message = '알림을 모두 삭제하시겠습니까?\n삭제된 데이터는 복구가 되지 않습니다.';
            if (typeof rb_confirm === 'function') {
                rb_confirm(message).then(function (confirmed) {
                    if (confirmed) deleteAll();
                });
                return;
            }
            if (window.confirm(message)) deleteAll();
        });

        $box.find('.rb_notification_back').on('click', function () {
            $box.find('.rb_notification_view').hide();
            $box.find('.rb_notification_list').show();
            $box.find('.rb_notification_body').scrollTop(0);
        });
    });
})(jQuery);
