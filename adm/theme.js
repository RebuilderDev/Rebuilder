$(function() {
    $(".theme_active").on("click", function() {
        var $btn = $(this);
        var theme = $btn.data("theme");
        var name  = $btn.data("name");
        var imported = $btn.data("imported");

        function useConfirm(msg) {
            if (typeof rb_confirm === 'function') {
                return rb_confirm(msg);
            }
            return Promise.resolve(window.confirm(msg));
        }

        function doApply(set_default_skin) {
            $.ajax({
                type: "POST",
                url: "./theme_update.php",
                data: {
                    "theme": theme,
                    "set_default_skin": set_default_skin
                },
                cache: false,
                success: function(data) {
                    if (data) {
                        alert(data);
                        return false;
                    }
                    document.location.reload();
                }
            });
        }

        function doSkinConfirm() {
            if ($btn.data("set_default_skin") == true) {
                useConfirm("기본환경설정, 1:1문의, 쇼핑몰 스킨을\n테마에서 설정된 스킨으로 변경하시겠습니까?\n\n변경을 선택하시면 테마에서 지정된 스킨으로\n회원스킨 등이 변경됩니다.").then(function(ok) {
                    doApply(ok ? 1 : 0);
                });
            } else {
                doApply(0);
            }
        }

        if (imported === false || imported === 'false') {
            // 최초 적용: 두 번 확인
            useConfirm(name + " 테마를 적용합니다.\n\n해당 테마의 기존 모듈/섹션/설정 데이터가 초기화됩니다.\n계속하시겠습니까?").then(function(ok) {
                if (!ok) return;
                useConfirm("초기화 후 적용합니다.\n정말 진행하시겠습니까?").then(function(ok2) {
                    if (!ok2) return;
                    doSkinConfirm();
                });
            });
        } else {
            // 재적용: 한 번 확인
            useConfirm(name + " 테마를 적용하시겠습니까?").then(function(ok) {
                if (!ok) return;
                doSkinConfirm();
            });
        }
    });

    $(".theme_deactive").on("click", function() {
        var theme = $(this).data("theme");
        var name  = $(this).data("name");

        function useConfirm(msg) {
            if (typeof rb_confirm === 'function') {
                return rb_confirm(msg);
            }
            return Promise.resolve(window.confirm(msg));
        }

        useConfirm(name + " 테마 사용설정을 해제하시겠습니까?\n테마 설정을 해제하셔도 게시판 등의 스킨은\n변경되지 않으므로 개별 변경작업이 필요합니다.").then(function(ok) {
            if (!ok) return;
            $.ajax({
                type: "POST",
                url: "./theme_update.php",
                data: {
                    "theme": theme,
                    "type": "reset"
                },
                cache: false,
                success: function(data) {
                    if (data) {
                        alert(data);
                        return false;
                    }
                    document.location.reload();
                }
            });
        });
    });

    $(".theme_reset_data").on("click", function() {
        var $btn = $(this);
        var theme = $btn.data("theme");
        var name  = $btn.data("name");

        function useConfirm(msg) {
            if (typeof rb_confirm === 'function') {
                return rb_confirm(msg);
            }
            return Promise.resolve(window.confirm(msg));
        }

        useConfirm(name + " 테마를 초기화 하시겠습니까?\n모듈/섹션/설정 데이터가 모두\nJSON 파일을 기준으로 초기화되며, 기존 설정 데이터는\n/data/rb.backup/ 으로 백업 됩니다.").then(function(ok) {
            if (!ok) return;
            useConfirm("정말 초기화 하시겠습니까?\n이 작업은 되돌릴 수 없습니다.").then(function(ok2) {
                if (!ok2) return;
                $.ajax({
                    type: "POST",
                    url: "./theme_update.php",
                    data: {
                        "theme": theme,
                        "type": "reset_data"
                    },
                    cache: false,
                    success: function(data) {
                        if (data) {
                            alert(data);
                            return false;
                        }
                        document.location.reload();
                    }
                });
            });
        });
    });

    $(".theme_preview").on("click", function() {
        var theme = $(this).data("theme");

        $("#theme_detail").remove();

        $.ajax({
            type: "POST",
            url: "./theme_detail.php",
            data: {
                "theme": theme
            },
            cache: false,
            async: false,
            success: function(data) {
                $("#theme_list").after(data);
            }
        });
    });
});
