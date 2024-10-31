jQuery(function ($) {
    if ($('.bulk-octify').size() > 0) {
        $('.bulk-octify').click(function () {
            var that = $(this);
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'start_bulk_octify'
                },
                beforeSend: function () {
                    that.attr('disabled', 'disabled');
                },
                success: function (data) {
                    if (data.success == 1) {
                        location.reload();
                    } else {
                        that.parent().find('.is-danger').html(data.data.error);
                        that.parent().find('.is-danger').removeClass('is-hidden');
                        that.removeAttr('disabled', 'disabled');
                    }
                }
            })
        })
    }

    if ($('.octify-progress').size() > 0) {
        listen_bulk_status();
    }

    function listen_bulk_status() {
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'get_bulk_octify_status'
            },
            success: function (data) {
                if (data.success == 1) {
                    $('.octify-progress').attr('value', data.data.percent).text(data.data.percent + '%');
                    $('.octify-log').html(data.data.log);
                    if (data.data.is_done === true) {
                        location.reload();
                    } else {
                        setTimeout(listen_bulk_status, 1000);
                    }
                } else {
                    //location.reload();
                }
            }
        })
    }

    $('body').on('click', '.octify-compress', function () {
        var id = $(this).data('id');
        var that = $(this);
        var parent = that.parent();
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'octify_compress',
                id: id
            },
            beforeSend: function () {
                that.attr('disabled', 'disabled');
            },
            success: function (data) {
                if (data.status == 1) {
                    that.text(data.button);
                    listen_to_image_status(id, parent);
                } else {
                    that.removeAttr('disabled');
                    if (parent.find('.error').size() == 0) {
                        parent.append('<p class="error"/>');
                    }
                    parent.find('.error').html(data.error);
                }
            }
        })
    })

    function listen_to_image_status(id, parent) {
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'octify_img_stats',
                id: id
            },
            success: function (data) {
                if (data.status == 1) {
                    parent.html(data.text);
                } else {
                    setTimeout(function () {
                        listen_to_image_status(id, parent)
                    }, 3000)
                }
            }
        })
    }

    $('#cancel-frm').submit(function () {
        var that = $(this);
        $.ajax({
            type: 'POST',
            data: that.serialize(),
            url: ajaxurl,
            beforeSend: function () {
                that.find('button').attr('disabled', 'disabled')
            },
            success: function () {
                location.reload();
            }
        })
        return false;
    })

    $('body').on('click', '.octify-revert', function () {
        var that = $(this);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'octify_revert',
                id: that.data('id')
            },
            beforeSend: function () {
                that.attr('disabled', 'disabled');
            },
            success: function (data) {
                if (data.status == 1) {
                    that.closest('div').html(data.html)
                } else {
                    alert(data.error);
                }
            }
        })
    })
    if (window.location.hash) {
        var target = window.location.hash;
        $('.octify .tabs li').removeClass('is-active');
        $('a[href="' + target + '"]').closest('li').addClass('is-active');
    }
    $('.octify-content').hide();
    if ($('.octify .tabs').size() > 0) {
        var target = $('.octify .tabs li.is-active a').attr('href');
        $(target).show();
    }
    $('.octify .tabs li a').click(function (e) {
        e.preventDefault();
        $('.octify .tabs li').removeClass('is-active');
        var parent = $(this).closest('li');
        parent.addClass('is-active');
        target = $(this).attr('href');
        $('.octify-content').hide();
        window.location.hash = target;
        $(target).show();
    })
})