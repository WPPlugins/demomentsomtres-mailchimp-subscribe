(function ($) {
    $('form.dms3MCsubscribe').submit(function (event) {
        event.preventDefault();
        var $form = $(this),
                email = $form.find("input[name='email']").val(),
                id = $form.find("input[name='id']").val(),
                url = dms3mcsubscribe.ajaxurl;
        var gaIntegration = dms3mcsubscribe.ga;
        var data = {
            action: 'dms3mcsubscribe',
            email: email,
            id: id,
        };
        $form.find(".spinner").show();
        var posting = $.post(url, data);
        posting.done(function (data) {
            $form.find(".spinner").hide();
            $form.find(".messages").empty().append(data);
            $form.find("input").addClass('completed');
            $form.find("label").addClass('completed');
        });
        if (gaIntegration) {
            window.ga('send',
                    'event',
                    'dms3mcsubscribe',
                    'subscribe-widget',
                    id + '-' + email
                    );
        }
    });
    $('form.dms3MCsubscribeGeneral').submit(function (event) {
        event.preventDefault();
        var $form = $(this);
        var email = $form.find("input[name='email']").val();
        var url = dms3mcsubscribe.ajaxurl;
        var button = $form.find("input[type='submit']");
        var gaIntegration = dms3mcsubscribe.ga;
        var data = {
            action: 'dms3mcquery',
            email: email,
        };
        $form.find(".fase1 .spinner").show();
        $form.find(".fase2").empty();
        var posting = $.post(url, data);
        posting.done(function (data) {
            $form.find(".fase2").empty().append(data);
            $form.find(".fase1 .spinner").hide();
            $form.find(".subscribe").bind('click', subscribeClick);
            $form.find(".unsubscribe").bind('click', unsubscribeClick);
            button.removeClass('loading');
        });
        if (gaIntegration) {
            window.ga('send',
                    'event',
                    'dms3mcsubscribe',
                    'verify-email-shortcode',
                    email
                    );
        }
    });

    function unsubscribeClick() {
        var tr = $(this).parent().parent();
        var id = tr.attr('id');
        var email = tr.attr('key');
        var name = tr.find('td.name').text();
        var url = dms3mcsubscribe.ajaxurl;
        var gaIntegration = dms3mcsubscribe.ga;
        var data = {
            action: 'dms3mcunsubscribebutton',
            email: email,
            name: name,
            id: id
        };
        var posting = $.post(url, data);
        tr.find(".spinner").show();
        posting.done(function (data) {
            tr.empty().append(data);
            tr.find(".subscribe").bind('click', subscribeClick);
            tr.find(".unsubscribe").bind('click', unsubscribeClick);
        });
        if (gaIntegration) {
            window.ga('send',
                    'event',
                    'dms3mcsubscribe',
                    'unsubscribe-shortcode',
                    id + '-' + email
                    );
        }
    }

    function subscribeClick() {
        var tr = $(this).parent().parent();
        var id = tr.attr('id');
        var email = tr.attr('key');
        var name = tr.find('td.name').text();
        var url = dms3mcsubscribe.ajaxurl;
        var gaIntegration = dms3mcsubscribe.ga;
        var data = {
            action: 'dms3mcsubscribebutton',
            email: email,
            name: name,
            id: id
        };
        tr.find(".spinner").show();
        var posting = $.post(url, data);
        posting.done(function (answer) {
            tr.empty().append(answer);
            tr.find(".unsubscribe").bind('click', unsubscribeClick);
            tr.find(".subscribe").bind('click', subscribeClick);
        });
        if (gaIntegration) {
            window.ga('send',
                    'event',
                    'dms3mcsubscribe',
                    'subscribe-shortcode',
                    id + '-' + email
                    );
        }
    }
})(jQuery);