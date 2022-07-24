(function ($) {
    var alert = 0;
    $('body').on('click', '.start-video', function () {
        var id = 'v' + Math.random().toString(36).substring(7), video_wrapper = $(this).closest('.yt-video-place'),
            h = video_wrapper.width() * 3 / 5,
            vid = document.createElement('div'), playNxt = $('#hpp_mPlayNxt');
        video_wrapper.height(h);
        $(this).closest('.video-fit.video').removeClass('lazy-vd');    //flatsome ux_video.php
        //_HWIO.detectMob()? 'height:150px':'height:300px'
        if (playNxt.length == 0) {
            playNxt = $('<div id="hpp_mPlayNxt"></div>');
            playNxt.html('<div class="cover"></div><div class="yt-video-place embed-responsive">' + video_wrapper.html() + '</div>');
            playNxt.find('.start-video').click(function () {
                playNxt.data('pl').playVideo();
                playNxt.hide();
                video_wrapper[0].scrollIntoView();
            });
            playNxt.hide().appendTo('body');
        } else playNxt.find('img:eq(0)').attr('src', video_wrapper.find('img:eq(0)').attr('src'));

        video_wrapper.empty().append(vid);
        var player = function () {
            if (typeof YT == 'object') {
                var _player = new YT.Player(vid, {
                    height: h + 'px',
                    width: '100%',
                    videoId: video_wrapper.data('yt-url').replace(/\?.+/g, '').replace('https://www.youtube.com/embed/', ''),
                    controls: 1,
                    loop: 1,
                    events: {
                        onReady: function (event) {
                            //_player.setPlaybackRate(2);
                            event.target.playVideo();
                            playNxt.data('pl', event.target);
                            var pl = event.target;
                            setTimeout(function () {
                                pl.playVideo();
                            }, 1000)    //if safari, try a least 1s
                            if (alert) setTimeout(function () {
                                //hit(event.data==YT.PlayerState.PLAYING);
                                if ([YT.PlayerState.PLAYING, YT.PlayerState.BUFFERING].indexOf(window.ytevt.data) === -1) {
                                    playNxt.show();
                                }
                            }, 2000);
                        },
                        onStateChange: function (event) {
                            window.ytevt = event;//_player.stopVideo();   //test
                            if (event.data == YT.PlayerState.ENDED) {
                                _player.seekTo(0);
                            }
                        }
                    }
                });
            } else video_wrapper.html('<iframe id="' + id + '" allowfullscreen frameborder="0" width="100%" style="height:' + (h) + 'px !important" src="' + video_wrapper.data('yt-url') + '"></iframe>');
        };
        if (typeof YT == 'object') player(); else _HWIO.waitForExist(player, 'YT');
    });
})(jQuery);	// EOF
